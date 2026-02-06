<?php
/**
 * Due Diligence service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\DueDiligence;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * DueDiligence_Service Class
 */
class DueDiligence_Service {
	
	/**
	 * Repository instance
	 *
	 * @var DueDiligence_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new DueDiligence_Repository();
	}
	
	/**
	 * Create a new due diligence
	 *
	 * @param array $data Due diligence data
	 * @return object|WP_Error
	 */
	public function create( $data ) {
		// Validate required fields
		$validation = $this->validate_due_diligence_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Set defaults
		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 'pending';
		}
		
		if ( ! isset( $data['priority'] ) ) {
			$data['priority'] = 'medium';
		}
		
		// Save due diligence
		$id = $this->repository->save( $data );
		
		if ( ! $id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create due diligence.', 'synpat-platform' )
			);
		}
		
		// Get created due diligence
		$due_diligence = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache();
		
		// Log activity
		$this->log_activity( 'due_diligence_created', $id, 'Created due diligence: ' . $data['title'] );
		
		// Dispatch event
		$this->dispatch_event( 'due_diligence_created', array( 'due_diligence' => $due_diligence ) );
		
		return $due_diligence;
	}
	
	/**
	 * Update a due diligence
	 *
	 * @param int   $id   Due diligence ID
	 * @param array $data Due diligence data
	 * @return object|WP_Error
	 */
	public function update( $id, $data ) {
		// Check if due diligence exists
		$due_diligence = $this->repository->find( $id );
		if ( ! $due_diligence ) {
			return new WP_Error(
				'not_found',
				__( 'Due diligence not found.', 'synpat-platform' )
			);
		}
		
		// Validate data
		$validation = $this->validate_due_diligence_data( $data, $id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Track status change
		$old_status = $due_diligence->status;
		$new_status = isset( $data['status'] ) ? $data['status'] : $old_status;
		
		// Update due diligence
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update due diligence.', 'synpat-platform' )
			);
		}
		
		// Get updated due diligence
		$updated = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'due_diligence_updated', $id, 'Updated due diligence: ' . $updated->title );
		
		// Dispatch events
		$this->dispatch_event( 'due_diligence_updated', array(
			'due_diligence' => $updated,
			'old_data' => $due_diligence,
		) );
		
		// Dispatch status change event if applicable
		if ( $old_status !== $new_status ) {
			$this->dispatch_event( 'due_diligence_status_changed', array(
				'due_diligence' => $updated,
				'old_status' => $old_status,
				'new_status' => $new_status,
			) );
		}
		
		return $updated;
	}
	
	/**
	 * Get a due diligence by ID
	 *
	 * @param int $id Due diligence ID
	 * @return object|null
	 */
	public function get( $id ) {
		// Try to get from cache
		$cache_key = 'due_diligence_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$due_diligence = $this->repository->find( $id );
		
		// Cache result
		if ( $due_diligence ) {
			$this->set_cache( $cache_key, $due_diligence );
		}
		
		return $due_diligence;
	}
	
	/**
	 * Get all due diligences with pagination
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		// Build cache key from args
		$cache_key = 'due_diligences_' . md5( serialize( $args ) );
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
	 * Delete a due diligence
	 *
	 * @param int $id Due diligence ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		// Check if due diligence exists
		$due_diligence = $this->repository->find( $id );
		if ( ! $due_diligence ) {
			return new WP_Error(
				'not_found',
				__( 'Due diligence not found.', 'synpat-platform' )
			);
		}
		
		// Check for dependencies (claim charts)
		$has_dependencies = $this->has_dependencies( $id );
		if ( $has_dependencies ) {
			return new WP_Error(
				'has_dependencies',
				__( 'Cannot delete due diligence with existing claim charts. Please delete associated claim charts first.', 'synpat-platform' )
			);
		}
		
		// Delete due diligence
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete due diligence.', 'synpat-platform' )
			);
		}
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'due_diligence_deleted', $id, 'Deleted due diligence: ' . $due_diligence->title );
		
		// Dispatch event
		$this->dispatch_event( 'due_diligence_deleted', array( 'due_diligence' => $due_diligence ) );
		
		return true;
	}
	
	/**
	 * Assign due diligence to a user
	 *
	 * @param int $id      Due diligence ID
	 * @param int $user_id User ID
	 * @return object|WP_Error
	 */
	public function assign( $id, $user_id ) {
		// Check if due diligence exists
		$due_diligence = $this->repository->find( $id );
		if ( ! $due_diligence ) {
			return new WP_Error(
				'not_found',
				__( 'Due diligence not found.', 'synpat-platform' )
			);
		}
		
		// Validate user exists
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return new WP_Error(
				'invalid_user',
				__( 'Invalid user ID.', 'synpat-platform' )
			);
		}
		
		// Update assignment
		$data = array(
			'assigned_to' => $user_id,
			'assigned_at' => current_time( 'mysql' ),
		);
		
		// Set status to in_progress if currently pending
		if ( 'pending' === $due_diligence->status ) {
			$data['status'] = 'in_progress';
		}
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'assignment_failed',
				__( 'Failed to assign due diligence.', 'synpat-platform' )
			);
		}
		
		// Get updated due diligence
		$updated = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'due_diligence_assigned', $id, sprintf( 'Assigned due diligence to user %d', $user_id ) );
		
		// Dispatch event
		$this->dispatch_event( 'due_diligence_assigned', array(
			'due_diligence' => $updated,
			'user_id' => $user_id,
		) );
		
		return $updated;
	}
	
	/**
	 * Mark due diligence as complete
	 *
	 * @param int $id Due diligence ID
	 * @return object|WP_Error
	 */
	public function complete( $id ) {
		// Check if due diligence exists
		$due_diligence = $this->repository->find( $id );
		if ( ! $due_diligence ) {
			return new WP_Error(
				'not_found',
				__( 'Due diligence not found.', 'synpat-platform' )
			);
		}
		
		// Check if already completed
		if ( 'completed' === $due_diligence->status ) {
			return new WP_Error(
				'already_completed',
				__( 'Due diligence is already completed.', 'synpat-platform' )
			);
		}
		
		// Update status
		$data = array(
			'status'       => 'completed',
			'completed_at' => current_time( 'mysql' ),
			'completed_by' => get_current_user_id(),
		);
		
		// Calculate completion percentage
		$data['completion_percentage'] = 100;
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'completion_failed',
				__( 'Failed to complete due diligence.', 'synpat-platform' )
			);
		}
		
		// Get updated due diligence
		$updated = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'due_diligence_completed', $id, 'Completed due diligence: ' . $updated->title );
		
		// Dispatch event
		$this->dispatch_event( 'due_diligence_completed', array( 'due_diligence' => $updated ) );
		
		return $updated;
	}
	
	/**
	 * Get due diligences by patent
	 *
	 * @param int $patent_id Patent ID
	 * @return array
	 */
	public function get_by_patent( $patent_id ) {
		// Try cache
		$cache_key = 'patent_due_diligences_' . $patent_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$results = $this->repository->find_by_patent( $patent_id );
		
		// Cache results
		$this->set_cache( $cache_key, $results );
		
		return $results;
	}
	
	/**
	 * Get due diligences by portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return array
	 */
	public function get_by_portfolio( $portfolio_id ) {
		// Try cache
		$cache_key = 'portfolio_due_diligences_' . $portfolio_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$results = $this->repository->find_by_portfolio( $portfolio_id );
		
		// Cache results
		$this->set_cache( $cache_key, $results );
		
		return $results;
	}
	
	/**
	 * Get due diligences assigned to user
	 *
	 * @param int $user_id User ID
	 * @return array
	 */
	public function get_by_assigned_user( $user_id ) {
		// Try cache
		$cache_key = 'user_due_diligences_' . $user_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$results = $this->repository->find_by_assigned_user( $user_id );
		
		// Cache results
		$this->set_cache( $cache_key, $results, 300 );
		
		return $results;
	}
	
	/**
	 * Update completion tracking
	 *
	 * @param int $id                     Due diligence ID
	 * @param int $completion_percentage  Completion percentage (0-100)
	 * @return object|WP_Error
	 */
	public function update_completion( $id, $completion_percentage ) {
		// Validate percentage
		$completion_percentage = absint( $completion_percentage );
		if ( $completion_percentage < 0 || $completion_percentage > 100 ) {
			return new WP_Error(
				'invalid_percentage',
				__( 'Completion percentage must be between 0 and 100.', 'synpat-platform' )
			);
		}
		
		$data = array(
			'completion_percentage' => $completion_percentage,
		);
		
		// If 100%, mark as completed
		if ( 100 === $completion_percentage ) {
			$data['status'] = 'completed';
			$data['completed_at'] = current_time( 'mysql' );
			$data['completed_by'] = get_current_user_id();
		}
		
		return $this->update( $id, $data );
	}
	
	/**
	 * Validate due diligence data
	 *
	 * @param array    $data Due diligence data
	 * @param int|null $id   Due diligence ID (for updates)
	 * @return bool|WP_Error
	 */
	private function validate_due_diligence_data( $data, $id = null ) {
		// Title is required for new due diligences
		if ( null === $id && empty( $data['title'] ) ) {
			return new WP_Error(
				'missing_title',
				__( 'Due diligence title is required.', 'synpat-platform' )
			);
		}
		
		// Validate status if provided
		$valid_statuses = array( 'pending', 'in_progress', 'under_review', 'completed', 'cancelled' );
		if ( ! empty( $data['status'] ) && ! in_array( $data['status'], $valid_statuses, true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid due diligence status.', 'synpat-platform' )
			);
		}
		
		// Validate priority if provided
		$valid_priorities = array( 'low', 'medium', 'high', 'urgent' );
		if ( ! empty( $data['priority'] ) && ! in_array( $data['priority'], $valid_priorities, true ) ) {
			return new WP_Error(
				'invalid_priority',
				__( 'Invalid priority level.', 'synpat-platform' )
			);
		}
		
		// Validate dates if provided
		$date_fields = array( 'start_date', 'due_date', 'assigned_at', 'completed_at' );
		foreach ( $date_fields as $field ) {
			if ( ! empty( $data[ $field ] ) && ! $this->validate_date( $data[ $field ] ) ) {
				return new WP_Error(
					'invalid_date',
					sprintf( __( 'Invalid date format for %s.', 'synpat-platform' ), $field )
				);
			}
		}
		
		// Validate completion percentage if provided
		if ( isset( $data['completion_percentage'] ) ) {
			$percentage = absint( $data['completion_percentage'] );
			if ( $percentage < 0 || $percentage > 100 ) {
				return new WP_Error(
					'invalid_percentage',
					__( 'Completion percentage must be between 0 and 100.', 'synpat-platform' )
				);
			}
		}
		
		// Validate user IDs if provided
		$user_fields = array( 'assigned_to', 'completed_by' );
		foreach ( $user_fields as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				$user = get_user_by( 'id', absint( $data[ $field ] ) );
				if ( ! $user ) {
					return new WP_Error(
						'invalid_user',
						sprintf( __( 'Invalid user ID for %s.', 'synpat-platform' ), $field )
					);
				}
			}
		}
		
		// Validate foreign key references if provided
		if ( ! empty( $data['patent_id'] ) ) {
			if ( ! $this->validate_patent_exists( absint( $data['patent_id'] ) ) ) {
				return new WP_Error(
					'invalid_patent',
					__( 'Invalid patent ID.', 'synpat-platform' )
				);
			}
		}
		
		if ( ! empty( $data['portfolio_id'] ) ) {
			if ( ! $this->validate_portfolio_exists( absint( $data['portfolio_id'] ) ) ) {
				return new WP_Error(
					'invalid_portfolio',
					__( 'Invalid portfolio ID.', 'synpat-platform' )
				);
			}
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
		// Accept MySQL datetime format
		$d = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date );
		if ( $d && $d->format( 'Y-m-d H:i:s' ) === $date ) {
			return true;
		}
		
		// Accept date only format
		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
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
	 * Validate portfolio exists
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return bool
	 */
	private function validate_portfolio_exists( $portfolio_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_portfolios';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$portfolio_id
		) );
		return $exists > 0;
	}
	
	/**
	 * Check if due diligence has dependencies
	 *
	 * @param int $id Due diligence ID
	 * @return bool
	 */
	private function has_dependencies( $id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_claim_charts';
		
		$claim_charts = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE due_diligence_id = %d",
			$id
		) );
		
		return $claim_charts > 0;
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id Due diligence ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'due_diligence_' . $id );
		}
		
		// Clear list caches
		$cache->delete_group( 'due_diligences_' );
		$cache->delete_group( 'patent_due_diligences_' );
		$cache->delete_group( 'portfolio_due_diligences_' );
		$cache->delete_group( 'user_due_diligences_' );
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
		$logger->log( $action, 'due_diligence', $entity_id, $description );
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
