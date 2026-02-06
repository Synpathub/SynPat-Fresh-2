<?php
/**
 * Portfolio service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Portfolio;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * Portfolio_Service Class
 */
class Portfolio_Service {
	
	/**
	 * Repository instance
	 *
	 * @var Portfolio_Repository
	 */
	private $repository;
	
	/**
	 * Validator instance
	 *
	 * @var Portfolio_Validator
	 */
	private $validator;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new Portfolio_Repository();
		$this->validator  = new Portfolio_Validator();
	}
	
	/**
	 * Create a new portfolio
	 *
	 * @param array $data Portfolio data
	 * @return Portfolio_Entity|WP_Error
	 */
	public function create( $data ) {
		// Validate data
		$validation = $this->validator->validate_create( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Generate slug if not provided
		if ( empty( $data['slug'] ) ) {
			$data['slug'] = Portfolio_Entity::generate_slug( $data['title'] );
			
			// Ensure uniqueness
			$counter = 1;
			$original_slug = $data['slug'];
			while ( $this->repository->find_by_slug( $data['slug'] ) ) {
				$data['slug'] = $original_slug . '-' . $counter;
				$counter++;
			}
		}
		
		// Set defaults
		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 'draft';
		}
		
		if ( ! isset( $data['visibility'] ) ) {
			$data['visibility'] = 'private';
		}
		
		if ( ! isset( $data['currency'] ) ) {
			$data['currency'] = 'USD';
		}
		
		// Save portfolio
		$portfolio_id = $this->repository->save( $data );
		
		if ( ! $portfolio_id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create portfolio.', 'synpat-platform' )
			);
		}
		
		// Get created portfolio
		$portfolio = $this->repository->find( $portfolio_id );
		
		// Clear cache
		$this->clear_cache();
		
		// Log activity
		$this->log_activity( 'portfolio_created', $portfolio_id, 'Created portfolio: ' . $data['title'] );
		
		// Dispatch event
		$this->dispatch_event( 'portfolio_created', array( 'portfolio' => $portfolio ) );
		
		return $portfolio;
	}
	
	/**
	 * Update a portfolio
	 *
	 * @param int   $id   Portfolio ID
	 * @param array $data Portfolio data
	 * @return Portfolio_Entity|WP_Error
	 */
	public function update( $id, $data ) {
		// Validate data
		$validation = $this->validator->validate_update( $id, $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Get current portfolio
		$portfolio = $this->repository->find( $id );
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		// Handle slug generation if title changed but slug not provided
		if ( isset( $data['title'] ) && ! isset( $data['slug'] ) ) {
			$new_slug = Portfolio_Entity::generate_slug( $data['title'] );
			if ( $new_slug !== $portfolio->slug ) {
				// Ensure uniqueness
				$counter = 1;
				$original_slug = $new_slug;
				while ( $this->repository->find_by_slug( $new_slug ) ) {
					$new_slug = $original_slug . '-' . $counter;
					$counter++;
				}
				$data['slug'] = $new_slug;
			}
		}
		
		// Update portfolio
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update portfolio.', 'synpat-platform' )
			);
		}
		
		// Get updated portfolio
		$updated_portfolio = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'portfolio_updated', $id, 'Updated portfolio: ' . $updated_portfolio->title );
		
		// Dispatch event
		$this->dispatch_event( 'portfolio_updated', array(
			'portfolio' => $updated_portfolio,
			'old_data'  => $portfolio,
		) );
		
		return $updated_portfolio;
	}
	
	/**
	 * Get a portfolio by ID with related data
	 *
	 * @param int $id Portfolio ID
	 * @return array|null
	 */
	public function get( $id ) {
		// Try to get from cache
		$cache_key = 'portfolio_full_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get portfolio
		$portfolio = $this->repository->find( $id );
		
		if ( ! $portfolio ) {
			return null;
		}
		
		// Get related data
		$result = array(
			'portfolio' => $portfolio,
			'patents'   => $this->get_patents( $id ),
			'category'  => null,
			'company'   => null,
		);
		
		// Get category if set
		if ( $portfolio->category_id ) {
			$result['category'] = $this->get_category( $portfolio->category_id );
		}
		
		// Get company if set
		if ( $portfolio->company_id ) {
			$result['company'] = $this->get_company( $portfolio->company_id );
		}
		
		// Cache result
		$this->set_cache( $cache_key, $result );
		
		return $result;
	}
	
	/**
	 * Get all portfolios with pagination
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		// Build cache key from args
		$cache_key = 'portfolios_' . md5( serialize( $args ) );
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$result = $this->repository->find_all( $args );
		
		// Cache result
		$this->set_cache( $cache_key, $result, 300 );
		
		return $result;
	}
	
	/**
	 * Delete a portfolio
	 *
	 * @param int $id Portfolio ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		// Check if portfolio exists
		$portfolio = $this->repository->find( $id );
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		// Remove all patent associations first
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_portfolio_patents';
		$wpdb->delete( $table, array( 'portfolio_id' => $id ), array( '%d' ) );
		
		// Delete portfolio
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete portfolio.', 'synpat-platform' )
			);
		}
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'portfolio_deleted', $id, 'Deleted portfolio: ' . $portfolio->title );
		
		// Dispatch event
		$this->dispatch_event( 'portfolio_deleted', array( 'portfolio' => $portfolio ) );
		
		return true;
	}
	
	/**
	 * Publish a portfolio
	 *
	 * @param int $id Portfolio ID
	 * @return Portfolio_Entity|WP_Error
	 */
	public function publish( $id ) {
		$portfolio = $this->repository->find( $id );
		
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		// Check if already published
		if ( $portfolio->is_published() ) {
			return $portfolio;
		}
		
		// Update status and published_at
		$data = array(
			'status'       => 'published',
			'published_at' => current_time( 'mysql' ),
		);
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'publish_failed',
				__( 'Failed to publish portfolio.', 'synpat-platform' )
			);
		}
		
		// Get updated portfolio
		$updated_portfolio = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'portfolio_published', $id, 'Published portfolio: ' . $portfolio->title );
		
		// Dispatch event
		$this->dispatch_event( 'portfolio_published', array( 'portfolio' => $updated_portfolio ) );
		
		return $updated_portfolio;
	}
	
	/**
	 * Add patents to portfolio
	 *
	 * @param int   $portfolio_id Portfolio ID
	 * @param array $patent_ids   Array of patent IDs
	 * @return bool|WP_Error
	 */
	public function add_patents( $portfolio_id, $patent_ids ) {
		// Check if portfolio exists
		$portfolio = $this->repository->find( $portfolio_id );
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		// Validate patent IDs
		if ( empty( $patent_ids ) || ! is_array( $patent_ids ) ) {
			return new WP_Error(
				'invalid_patents',
				__( 'Invalid patent IDs.', 'synpat-platform' )
			);
		}
		
		// Verify patents exist
		$valid_patent_ids = $this->validate_patent_ids( $patent_ids );
		if ( empty( $valid_patent_ids ) ) {
			return new WP_Error(
				'no_valid_patents',
				__( 'No valid patents provided.', 'synpat-platform' )
			);
		}
		
		// Add patents
		$result = $this->repository->add_patents( $portfolio_id, $valid_patent_ids );
		
		// Clear cache
		$this->clear_cache( $portfolio_id );
		
		// Log activity
		$this->log_activity(
			'patents_added',
			$portfolio_id,
			sprintf( 'Added %d patents to portfolio', count( $valid_patent_ids ) )
		);
		
		// Dispatch event
		$this->dispatch_event( 'portfolio_patents_added', array(
			'portfolio_id' => $portfolio_id,
			'patent_ids'   => $valid_patent_ids,
		) );
		
		return true;
	}
	
	/**
	 * Remove patents from portfolio
	 *
	 * @param int   $portfolio_id Portfolio ID
	 * @param array $patent_ids   Array of patent IDs
	 * @return bool|WP_Error
	 */
	public function remove_patents( $portfolio_id, $patent_ids ) {
		// Check if portfolio exists
		$portfolio = $this->repository->find( $portfolio_id );
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		// Validate patent IDs
		if ( empty( $patent_ids ) || ! is_array( $patent_ids ) ) {
			return new WP_Error(
				'invalid_patents',
				__( 'Invalid patent IDs.', 'synpat-platform' )
			);
		}
		
		// Remove patents
		$result = $this->repository->remove_patents( $portfolio_id, $patent_ids );
		
		// Clear cache
		$this->clear_cache( $portfolio_id );
		
		// Log activity
		$this->log_activity(
			'patents_removed',
			$portfolio_id,
			sprintf( 'Removed %d patents from portfolio', count( $patent_ids ) )
		);
		
		// Dispatch event
		$this->dispatch_event( 'portfolio_patents_removed', array(
			'portfolio_id' => $portfolio_id,
			'patent_ids'   => $patent_ids,
		) );
		
		return true;
	}
	
	/**
	 * Get portfolio patents
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return array
	 */
	public function get_patents( $portfolio_id ) {
		// Try cache
		$cache_key = 'portfolio_patents_' . $portfolio_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$patents = $this->repository->get_patents( $portfolio_id );
		
		// Cache results
		$this->set_cache( $cache_key, $patents );
		
		return $patents;
	}
	
	/**
	 * Calculate portfolio valuation based on patents
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return float|WP_Error
	 */
	public function calculate_valuation( $portfolio_id ) {
		$portfolio = $this->repository->find( $portfolio_id );
		
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		$patents = $this->get_patents( $portfolio_id );
		
		if ( empty( $patents ) ) {
			return 0.0;
		}
		
		// Simple valuation: count of active patents * base value
		// In real-world, this would use complex algorithms
		$active_count = 0;
		$total_citations = 0;
		
		foreach ( $patents as $patent ) {
			if ( isset( $patent->status ) && 'active' === $patent->status ) {
				$active_count++;
			}
			
			if ( isset( $patent->forward_citations ) ) {
				$total_citations += intval( $patent->forward_citations );
			}
		}
		
		// Base value per patent
		$base_value = 50000;
		
		// Citation multiplier (more citations = higher value)
		$citation_value = $total_citations * 1000;
		
		// Calculate total
		$valuation = ( $active_count * $base_value ) + $citation_value;
		
		// Update portfolio valuation
		$this->repository->update( $portfolio_id, array(
			'valuation' => $valuation,
		) );
		
		// Clear cache
		$this->clear_cache( $portfolio_id );
		
		return $valuation;
	}
	
	/**
	 * Validate patent IDs exist
	 *
	 * @param array $patent_ids Patent IDs
	 * @return array Valid patent IDs
	 */
	private function validate_patent_ids( $patent_ids ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_patents';
		$placeholders = implode( ',', array_fill( 0, count( $patent_ids ), '%d' ) );
		
		$sql = "SELECT id FROM {$table} WHERE id IN ({$placeholders})";
		$results = $wpdb->get_col( $wpdb->prepare( $sql, $patent_ids ) );
		
		return $results;
	}
	
	/**
	 * Get category
	 *
	 * @param int $category_id Category ID
	 * @return object|null
	 */
	private function get_category( $category_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_categories';
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$category_id
		) );
	}
	
	/**
	 * Get company
	 *
	 * @param int $company_id Company ID
	 * @return object|null
	 */
	private function get_company( $company_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_companies';
		
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$company_id
		) );
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id Portfolio ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'portfolio_full_' . $id );
			$cache->delete( 'portfolio_patents_' . $id );
		}
		
		// Clear list caches
		$cache->delete_group( 'portfolios_' );
	}
	
	/**
	 * Get from cache
	 *
	 * @param string $key Cache key
	 * @return mixed
	 */
	private function get_cache( $key ) {
		$cache = Plugin::get_instance()->cache();
		return $cache->get( $key );
	}
	
	/**
	 * Set cache
	 *
	 * @param string $key   Cache key
	 * @param mixed  $value Cache value
	 * @param int    $ttl   Time to live in seconds
	 */
	private function set_cache( $key, $value, $ttl = 3600 ) {
		$cache = Plugin::get_instance()->cache();
		$cache->set( $key, $value, $ttl );
	}
	
	/**
	 * Log activity
	 *
	 * @param string $action      Action performed
	 * @param int    $entity_id   Entity ID
	 * @param string $description Description
	 */
	private function log_activity( $action, $entity_id, $description ) {
		$logger = Plugin::get_instance()->logger();
		$logger->log( $action, 'portfolio', $entity_id, $description );
	}
	
	/**
	 * Dispatch event
	 *
	 * @param string $event Event name
	 * @param array  $data  Event data
	 */
	private function dispatch_event( $event, $data ) {
		$events = Plugin::get_instance()->events();
		$events->dispatch( $event, $data );
	}
}
