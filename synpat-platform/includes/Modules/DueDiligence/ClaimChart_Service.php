<?php
/**
 * Claim Chart service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\DueDiligence;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * ClaimChart_Service Class
 */
class ClaimChart_Service {
	
	/**
	 * Repository instance
	 *
	 * @var ClaimChart_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new ClaimChart_Repository();
	}
	
	/**
	 * Create a new claim chart
	 *
	 * @param array $data Claim chart data
	 * @return object|WP_Error
	 */
	public function create( $data ) {
		// Validate required fields
		$validation = $this->validate_claim_chart_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Save claim chart
		$id = $this->repository->save( $data );
		
		if ( ! $id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create claim chart.', 'synpat-platform' )
			);
		}
		
		// Get created claim chart
		$claim_chart = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache();
		
		// Log activity
		$this->log_activity( 'claim_chart_created', $id, 'Created claim chart: ' . $data['title'] );
		
		// Dispatch event
		$this->dispatch_event( 'claim_chart_created', array( 'claim_chart' => $claim_chart ) );
		
		return $claim_chart;
	}
	
	/**
	 * Update a claim chart
	 *
	 * @param int   $id   Claim chart ID
	 * @param array $data Claim chart data
	 * @return object|WP_Error
	 */
	public function update( $id, $data ) {
		// Check if claim chart exists
		$claim_chart = $this->repository->find( $id );
		if ( ! $claim_chart ) {
			return new WP_Error(
				'not_found',
				__( 'Claim chart not found.', 'synpat-platform' )
			);
		}
		
		// Validate data
		$validation = $this->validate_claim_chart_data( $data, $id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Update claim chart
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update claim chart.', 'synpat-platform' )
			);
		}
		
		// Get updated claim chart
		$updated = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'claim_chart_updated', $id, 'Updated claim chart: ' . $updated->title );
		
		// Dispatch event
		$this->dispatch_event( 'claim_chart_updated', array(
			'claim_chart' => $updated,
			'old_data' => $claim_chart,
		) );
		
		return $updated;
	}
	
	/**
	 * Get a claim chart by ID
	 *
	 * @param int $id Claim chart ID
	 * @return object|null
	 */
	public function get( $id ) {
		// Try to get from cache
		$cache_key = 'claim_chart_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$claim_chart = $this->repository->find( $id );
		
		// Cache result
		if ( $claim_chart ) {
			$this->set_cache( $cache_key, $claim_chart );
		}
		
		return $claim_chart;
	}
	
	/**
	 * Get all claim charts with pagination
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		// Build cache key from args
		$cache_key = 'claim_charts_' . md5( serialize( $args ) );
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
	 * Delete a claim chart
	 *
	 * @param int $id Claim chart ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		// Check if claim chart exists
		$claim_chart = $this->repository->find( $id );
		if ( ! $claim_chart ) {
			return new WP_Error(
				'not_found',
				__( 'Claim chart not found.', 'synpat-platform' )
			);
		}
		
		// Delete claim chart
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete claim chart.', 'synpat-platform' )
			);
		}
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'claim_chart_deleted', $id, 'Deleted claim chart: ' . $claim_chart->title );
		
		// Dispatch event
		$this->dispatch_event( 'claim_chart_deleted', array( 'claim_chart' => $claim_chart ) );
		
		return true;
	}
	
	/**
	 * Get claim charts by due diligence
	 *
	 * @param int $due_diligence_id Due diligence ID
	 * @return array
	 */
	public function get_by_due_diligence( $due_diligence_id ) {
		// Try cache
		$cache_key = 'dd_claim_charts_' . $due_diligence_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$claim_charts = $this->repository->find_by_due_diligence( $due_diligence_id );
		
		// Cache results
		$this->set_cache( $cache_key, $claim_charts );
		
		return $claim_charts;
	}
	
	/**
	 * Get claim charts by patent
	 *
	 * @param int $patent_id Patent ID
	 * @return array
	 */
	public function get_by_patent( $patent_id ) {
		// Try cache
		$cache_key = 'patent_claim_charts_' . $patent_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$claim_charts = $this->repository->find_by_patent( $patent_id );
		
		// Cache results
		$this->set_cache( $cache_key, $claim_charts );
		
		return $claim_charts;
	}
	
	/**
	 * Validate claim chart data
	 *
	 * @param array    $data Claim chart data
	 * @param int|null $id   Claim chart ID (for updates)
	 * @return bool|WP_Error
	 */
	private function validate_claim_chart_data( $data, $id = null ) {
		// Title is required for new claim charts
		if ( null === $id && empty( $data['title'] ) ) {
			return new WP_Error(
				'missing_title',
				__( 'Claim chart title is required.', 'synpat-platform' )
			);
		}
		
		// Due diligence ID is required for new claim charts
		if ( null === $id && empty( $data['due_diligence_id'] ) ) {
			return new WP_Error(
				'missing_due_diligence_id',
				__( 'Due diligence ID is required.', 'synpat-platform' )
			);
		}
		
		// Patent ID is required for new claim charts
		if ( null === $id && empty( $data['patent_id'] ) ) {
			return new WP_Error(
				'missing_patent_id',
				__( 'Patent ID is required.', 'synpat-platform' )
			);
		}
		
		// Validate foreign key references if provided
		if ( ! empty( $data['due_diligence_id'] ) ) {
			if ( ! $this->validate_due_diligence_exists( absint( $data['due_diligence_id'] ) ) ) {
				return new WP_Error(
					'invalid_due_diligence',
					__( 'Invalid due diligence ID.', 'synpat-platform' )
				);
			}
		}
		
		if ( ! empty( $data['patent_id'] ) ) {
			if ( ! $this->validate_patent_exists( absint( $data['patent_id'] ) ) ) {
				return new WP_Error(
					'invalid_patent',
					__( 'Invalid patent ID.', 'synpat-platform' )
				);
			}
		}
		
		return true;
	}
	
	/**
	 * Validate due diligence exists
	 *
	 * @param int $due_diligence_id Due diligence ID
	 * @return bool
	 */
	private function validate_due_diligence_exists( $due_diligence_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_due_diligence';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$due_diligence_id
		) );
		return $exists > 0;
	}
	
	/**
	 * Validate patent exists
	 *
	 * @param int $patent_id Patent ID
	 * @return bool
	 */
	private function validate_patent_exists( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$patent_id
		) );
		return $exists > 0;
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id Claim chart ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'claim_chart_' . $id );
		}
		
		// Clear list caches
		$cache->delete_group( 'claim_charts_' );
		$cache->delete_group( 'dd_claim_charts_' );
		$cache->delete_group( 'patent_claim_charts_' );
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
		$logger->log( $action, 'claim_chart', $entity_id, $description );
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
