<?php
/**
 * Patent service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Patent;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * Patent_Service Class
 */
class Patent_Service {
	
	/**
	 * Repository instance
	 *
	 * @var Patent_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new Patent_Repository();
	}
	
	/**
	 * Create a new patent
	 *
	 * @param array $data Patent data
	 * @return Patent_Entity|WP_Error
	 */
	public function create( $data ) {
		// Validate required fields
		$validation = $this->validate_patent_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Check for duplicate patent number
		if ( $this->is_duplicate_patent( $data['patent_number'], $data['country'] ?? 'US' ) ) {
			return new WP_Error(
				'duplicate_patent',
				__( 'A patent with this number already exists in the specified country.', 'synpat-platform' )
			);
		}
		
		// Save patent
		$patent_id = $this->repository->save( $data );
		
		if ( ! $patent_id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create patent.', 'synpat-platform' )
			);
		}
		
		// Get created patent
		$patent = $this->repository->find( $patent_id );
		
		// Clear cache
		$this->clear_cache();
		
		// Log activity
		$this->log_activity( 'patent_created', $patent_id, 'Created patent: ' . $data['patent_number'] );
		
		// Dispatch event
		$this->dispatch_event( 'patent_created', array( 'patent' => $patent ) );
		
		return $patent;
	}
	
	/**
	 * Update a patent
	 *
	 * @param int   $id   Patent ID
	 * @param array $data Patent data
	 * @return Patent_Entity|WP_Error
	 */
	public function update( $id, $data ) {
		// Check if patent exists
		$patent = $this->repository->find( $id );
		if ( ! $patent ) {
			return new WP_Error(
				'not_found',
				__( 'Patent not found.', 'synpat-platform' )
			);
		}
		
		// Validate data
		$validation = $this->validate_patent_data( $data, $id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Check for duplicate patent number (excluding current patent)
		if ( isset( $data['patent_number'] ) ) {
			$country = isset( $data['country'] ) ? $data['country'] : $patent->country;
			if ( $this->is_duplicate_patent( $data['patent_number'], $country, $id ) ) {
				return new WP_Error(
					'duplicate_patent',
					__( 'A patent with this number already exists in the specified country.', 'synpat-platform' )
				);
			}
		}
		
		// Update patent
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update patent.', 'synpat-platform' )
			);
		}
		
		// Get updated patent
		$updated_patent = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'patent_updated', $id, 'Updated patent: ' . $updated_patent->patent_number );
		
		// Dispatch event
		$this->dispatch_event( 'patent_updated', array(
			'patent' => $updated_patent,
			'old_data' => $patent,
		) );
		
		return $updated_patent;
	}
	
	/**
	 * Get a patent by ID
	 *
	 * @param int $id Patent ID
	 * @return Patent_Entity|null
	 */
	public function get( $id ) {
		// Try to get from cache
		$cache_key = 'patent_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$patent = $this->repository->find( $id );
		
		// Cache result
		if ( $patent ) {
			$this->set_cache( $cache_key, $patent );
		}
		
		return $patent;
	}
	
	/**
	 * Get all patents with pagination
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		// Build cache key from args
		$cache_key = 'patents_' . md5( serialize( $args ) );
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
	 * Delete a patent
	 *
	 * @param int $id Patent ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		// Check if patent exists
		$patent = $this->repository->find( $id );
		if ( ! $patent ) {
			return new WP_Error(
				'not_found',
				__( 'Patent not found.', 'synpat-platform' )
			);
		}
		
		// Check for dependencies (portfolios, claim charts, etc.)
		$has_dependencies = $this->has_dependencies( $id );
		if ( $has_dependencies ) {
			return new WP_Error(
				'has_dependencies',
				__( 'Cannot delete patent with existing portfolio associations or claim charts.', 'synpat-platform' )
			);
		}
		
		// Delete patent
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete patent.', 'synpat-platform' )
			);
		}
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'patent_deleted', $id, 'Deleted patent: ' . $patent->patent_number );
		
		// Dispatch event
		$this->dispatch_event( 'patent_deleted', array( 'patent' => $patent ) );
		
		return true;
	}
	
	/**
	 * Search patents
	 *
	 * @param string $query Search query
	 * @param array  $args  Additional arguments
	 * @return array
	 */
	public function search( $query, $args = array() ) {
		if ( empty( $query ) ) {
			return array();
		}
		
		// Try cache
		$cache_key = 'patent_search_' . md5( $query . serialize( $args ) );
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Search database
		$results = $this->repository->search( $query, $args );
		
		// Cache results
		$this->set_cache( $cache_key, $results, 300 );
		
		return $results;
	}
	
	/**
	 * Get patents by portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return array
	 */
	public function get_by_portfolio( $portfolio_id ) {
		// Try cache
		$cache_key = 'portfolio_patents_' . $portfolio_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$patents = $this->repository->find_by_portfolio( $portfolio_id );
		
		// Cache results
		$this->set_cache( $cache_key, $patents );
		
		return $patents;
	}
	
	/**
	 * Validate patent data
	 *
	 * @param array    $data Patent data
	 * @param int|null $id   Patent ID (for updates)
	 * @return bool|WP_Error
	 */
	private function validate_patent_data( $data, $id = null ) {
		// Patent number is required for new patents
		if ( null === $id && empty( $data['patent_number'] ) ) {
			return new WP_Error(
				'missing_patent_number',
				__( 'Patent number is required.', 'synpat-platform' )
			);
		}
		
		// Title is required for new patents
		if ( null === $id && empty( $data['title'] ) ) {
			return new WP_Error(
				'missing_title',
				__( 'Patent title is required.', 'synpat-platform' )
			);
		}
		
		// Validate patent type if provided
		$valid_types = array( 'utility', 'design', 'plant', 'reissue', 'statutory_invention', 'provisional' );
		if ( ! empty( $data['patent_type'] ) && ! in_array( $data['patent_type'], $valid_types, true ) ) {
			return new WP_Error(
				'invalid_patent_type',
				__( 'Invalid patent type.', 'synpat-platform' )
			);
		}
		
		// Validate status if provided
		$valid_statuses = array( 'pending', 'active', 'expired', 'abandoned', 'revoked' );
		if ( ! empty( $data['status'] ) && ! in_array( $data['status'], $valid_statuses, true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid patent status.', 'synpat-platform' )
			);
		}
		
		// Validate dates if provided
		$date_fields = array( 'filing_date', 'publication_date', 'grant_date', 'expiration_date', 'priority_date' );
		foreach ( $date_fields as $field ) {
			if ( ! empty( $data[ $field ] ) && ! $this->validate_date( $data[ $field ] ) ) {
				return new WP_Error(
					'invalid_date',
					sprintf( __( 'Invalid date format for %s.', 'synpat-platform' ), $field )
				);
			}
		}
		
		// Validate numeric fields if provided
		$numeric_fields = array( 'claims_count', 'independent_claims', 'forward_citations', 'backward_citations' );
		foreach ( $numeric_fields as $field ) {
			if ( isset( $data[ $field ] ) && ! is_numeric( $data[ $field ] ) ) {
				return new WP_Error(
					'invalid_number',
					sprintf( __( 'Invalid number format for %s.', 'synpat-platform' ), $field )
				);
			}
		}
		
		// Validate URL if provided
		if ( ! empty( $data['pdf_url'] ) && ! filter_var( $data['pdf_url'], FILTER_VALIDATE_URL ) ) {
			return new WP_Error(
				'invalid_url',
				__( 'Invalid PDF URL.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Validate date format
	 *
	 * @param string $date Date string
	 * @return bool
	 */
	private function validate_date( $date ) {
		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}
	
	/**
	 * Check if patent number is duplicate
	 *
	 * @param string   $patent_number Patent number
	 * @param string   $country       Country code
	 * @param int|null $exclude_id    Patent ID to exclude
	 * @return bool
	 */
	private function is_duplicate_patent( $patent_number, $country, $exclude_id = null ) {
		$existing = $this->repository->find_by_number( $patent_number, $country );
		
		if ( ! $existing ) {
			return false;
		}
		
		// If excluding current patent, check if IDs match
		if ( $exclude_id && $existing->id == $exclude_id ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check if patent has dependencies
	 *
	 * @param int $patent_id Patent ID
	 * @return bool
	 */
	private function has_dependencies( $patent_id ) {
		global $wpdb;
		
		$prefix = $wpdb->prefix . 'synpat_';
		
		// Check portfolio associations
		$portfolios = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}portfolio_patents WHERE patent_id = %d",
			$patent_id
		) );
		
		if ( $portfolios > 0 ) {
			return true;
		}
		
		// Check claim charts
		$claim_charts = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}claim_charts WHERE patent_id = %d",
			$patent_id
		) );
		
		if ( $claim_charts > 0 ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id Patent ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'patent_' . $id );
		}
		
		// Clear list caches
		$cache->delete_group( 'patents_' );
		$cache->delete_group( 'patent_search_' );
		$cache->delete_group( 'portfolio_patents_' );
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
		$logger->log( $action, 'patent', $entity_id, $description );
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
