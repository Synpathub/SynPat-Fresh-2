<?php
/**
 * License service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Licensing;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * License_Service Class
 */
class License_Service {
	
	/**
	 * Repository instance
	 *
	 * @var License_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new License_Repository();
	}
	
	/**
	 * Create a new license
	 *
	 * @param array $data License data
	 * @return array|WP_Error
	 */
	public function create( $data ) {
		$validation = $this->validate_license_data( $data, 'create' );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		if ( empty( $data['license_key'] ) ) {
			$data['license_key'] = $this->generate_license_key();
		}
		
		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 'pending';
		}
		
		if ( isset( $data['start_date'] ) && isset( $data['duration_months'] ) ) {
			$data['expiry_date'] = $this->calculate_expiry_date( $data['start_date'], $data['duration_months'] );
		}
		
		$license_id = $this->repository->save( $data );
		
		if ( ! $license_id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create license.', 'synpat-platform' )
			);
		}
		
		$license = $this->repository->find( $license_id );
		
		$this->clear_cache();
		
		$this->log_activity( 'license_created', $license_id, 'Created license: ' . $data['license_key'] );
		
		$this->dispatch_event( 'license_created', array( 'license' => $license ) );
		
		return $this->get( $license_id );
	}
	
	/**
	 * Update a license
	 *
	 * @param int   $id   License ID
	 * @param array $data License data
	 * @return array|WP_Error
	 */
	public function update( $id, $data ) {
		$validation = $this->validate_license_data( $data, 'update' );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		$license = $this->repository->find( $id );
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'License not found.', 'synpat-platform' )
			);
		}
		
		if ( isset( $data['start_date'] ) && isset( $data['duration_months'] ) ) {
			$data['expiry_date'] = $this->calculate_expiry_date( $data['start_date'], $data['duration_months'] );
		}
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update license.', 'synpat-platform' )
			);
		}
		
		$updated_license = $this->get( $id );
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'license_updated', $id, 'Updated license: ' . $license->license_key );
		
		$this->dispatch_event( 'license_updated', array(
			'license' => $updated_license,
			'old_data' => $license,
		) );
		
		return $updated_license;
	}
	
	/**
	 * Get a license by ID with related data
	 *
	 * @param int $id License ID
	 * @return array|null
	 */
	public function get( $id ) {
		$cache_key = 'license_full_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$license = $this->repository->find( $id );
		
		if ( ! $license ) {
			return null;
		}
		
		$result = array(
			'license'  => $license,
			'customer' => null,
			'patent'   => null,
		);
		
		if ( $license->customer_id ) {
			$result['customer'] = $this->get_customer( $license->customer_id );
		}
		
		if ( $license->patent_id ) {
			$result['patent'] = $this->get_patent( $license->patent_id );
		}
		
		$this->set_cache( $cache_key, $result );
		
		return $result;
	}
	
	/**
	 * Get all licenses with pagination and filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		$cache_key = 'licenses_' . md5( serialize( $args ) );
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$result = $this->repository->find_all( $args );
		
		$this->set_cache( $cache_key, $result, 300 );
		
		return $result;
	}
	
	/**
	 * Delete a license
	 *
	 * @param int $id License ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		$license = $this->repository->find( $id );
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'License not found.', 'synpat-platform' )
			);
		}
		
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete license.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'license_deleted', $id, 'Deleted license: ' . $license->license_key );
		
		$this->dispatch_event( 'license_deleted', array( 'license' => $license ) );
		
		return true;
	}
	
	/**
	 * Activate a license
	 *
	 * @param int $id License ID
	 * @return array|WP_Error
	 */
	public function activate( $id ) {
		$license = $this->repository->find( $id );
		
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'License not found.', 'synpat-platform' )
			);
		}
		
		if ( 'active' === $license->status ) {
			return $this->get( $id );
		}
		
		if ( 'suspended' === $license->status ) {
			return new WP_Error(
				'suspended',
				__( 'Cannot activate a suspended license.', 'synpat-platform' )
			);
		}
		
		if ( $license->expiry_date && strtotime( $license->expiry_date ) < time() ) {
			return new WP_Error(
				'expired',
				__( 'Cannot activate an expired license.', 'synpat-platform' )
			);
		}
		
		$data = array(
			'status'       => 'active',
			'activated_at' => current_time( 'mysql' ),
		);
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'activation_failed',
				__( 'Failed to activate license.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'license_activated', $id, 'Activated license: ' . $license->license_key );
		
		$this->dispatch_event( 'license_activated', array( 'license' => $this->repository->find( $id ) ) );
		
		return $this->get( $id );
	}
	
	/**
	 * Deactivate a license
	 *
	 * @param int $id License ID
	 * @return array|WP_Error
	 */
	public function deactivate( $id ) {
		$license = $this->repository->find( $id );
		
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'License not found.', 'synpat-platform' )
			);
		}
		
		if ( 'inactive' === $license->status ) {
			return $this->get( $id );
		}
		
		$data = array(
			'status' => 'inactive',
		);
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'deactivation_failed',
				__( 'Failed to deactivate license.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'license_deactivated', $id, 'Deactivated license: ' . $license->license_key );
		
		$this->dispatch_event( 'license_deactivated', array( 'license' => $this->repository->find( $id ) ) );
		
		return $this->get( $id );
	}
	
	/**
	 * Suspend a license
	 *
	 * @param int $id License ID
	 * @return array|WP_Error
	 */
	public function suspend( $id ) {
		$license = $this->repository->find( $id );
		
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'License not found.', 'synpat-platform' )
			);
		}
		
		if ( 'suspended' === $license->status ) {
			return $this->get( $id );
		}
		
		$data = array(
			'status'       => 'suspended',
			'suspended_at' => current_time( 'mysql' ),
		);
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'suspension_failed',
				__( 'Failed to suspend license.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'license_suspended', $id, 'Suspended license: ' . $license->license_key );
		
		$this->dispatch_event( 'license_suspended', array( 'license' => $this->repository->find( $id ) ) );
		
		return $this->get( $id );
	}
	
	/**
	 * Renew a license
	 *
	 * @param int   $id   License ID
	 * @param array $data Renewal data (duration_months)
	 * @return array|WP_Error
	 */
	public function renew( $id, $data ) {
		$license = $this->repository->find( $id );
		
		if ( ! $license ) {
			return new WP_Error(
				'not_found',
				__( 'License not found.', 'synpat-platform' )
			);
		}
		
		$duration_months = isset( $data['duration_months'] ) ? absint( $data['duration_months'] ) : 12;
		
		$start_date = current_time( 'mysql' );
		if ( $license->expiry_date && strtotime( $license->expiry_date ) > time() ) {
			$start_date = $license->expiry_date;
		}
		
		$new_expiry = $this->calculate_expiry_date( $start_date, $duration_months );
		
		$update_data = array(
			'expiry_date' => $new_expiry,
			'status'      => 'active',
		);
		
		$result = $this->repository->update( $id, $update_data );
		
		if ( false === $result ) {
			return new WP_Error(
				'renewal_failed',
				__( 'Failed to renew license.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'license_renewed', $id, sprintf( 'Renewed license %s for %d months', $license->license_key, $duration_months ) );
		
		$this->dispatch_event( 'license_renewed', array(
			'license' => $this->repository->find( $id ),
			'duration_months' => $duration_months,
		) );
		
		return $this->get( $id );
	}
	
	/**
	 * Search licenses
	 *
	 * @param string $search Search term
	 * @param array  $args   Additional query arguments
	 * @return array
	 */
	public function search( $search, $args = array() ) {
		$args['search'] = $search;
		return $this->repository->find_all( $args );
	}
	
	/**
	 * Check if license is valid
	 *
	 * @param string $license_key License key
	 * @return bool
	 */
	public function is_valid( $license_key ) {
		$license = $this->repository->find_by_key( $license_key );
		
		if ( ! $license ) {
			return false;
		}
		
		if ( 'active' !== $license->status ) {
			return false;
		}
		
		if ( $license->expiry_date && strtotime( $license->expiry_date ) < time() ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check expiring licenses and send notifications
	 *
	 * @param int $days_before Number of days before expiry to check
	 * @return array Expiring licenses
	 */
	public function check_expiring_licenses( $days_before = 30 ) {
		$licenses = $this->repository->find_expiring( $days_before );
		
		foreach ( $licenses as $license ) {
			$this->dispatch_event( 'license_expiring', array(
				'license' => $license,
				'days_remaining' => $this->get_days_until_expiry( $license->expiry_date ),
			) );
		}
		
		return $licenses;
	}
	
	/**
	 * Validate license data
	 *
	 * @param array  $data License data
	 * @param string $context Validation context (create/update)
	 * @return true|WP_Error
	 */
	private function validate_license_data( $data, $context = 'create' ) {
		if ( 'create' === $context ) {
			if ( empty( $data['customer_id'] ) ) {
				return new WP_Error(
					'missing_customer',
					__( 'Customer ID is required.', 'synpat-platform' )
				);
			}
			
			if ( empty( $data['patent_id'] ) ) {
				return new WP_Error(
					'missing_patent',
					__( 'Patent ID is required.', 'synpat-platform' )
				);
			}
		}
		
		if ( isset( $data['customer_id'] ) && ! $this->customer_exists( $data['customer_id'] ) ) {
			return new WP_Error(
				'invalid_customer',
				__( 'Invalid customer ID.', 'synpat-platform' )
			);
		}
		
		if ( isset( $data['patent_id'] ) && ! $this->patent_exists( $data['patent_id'] ) ) {
			return new WP_Error(
				'invalid_patent',
				__( 'Invalid patent ID.', 'synpat-platform' )
			);
		}
		
		if ( isset( $data['status'] ) && ! in_array( $data['status'], array( 'pending', 'active', 'inactive', 'suspended', 'expired' ), true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid license status.', 'synpat-platform' )
			);
		}
		
		if ( isset( $data['license_type'] ) && ! in_array( $data['license_type'], array( 'exclusive', 'non-exclusive', 'sole' ), true ) ) {
			return new WP_Error(
				'invalid_type',
				__( 'Invalid license type.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Generate a unique license key
	 *
	 * @return string
	 */
	private function generate_license_key() {
		do {
			$key = sprintf(
				'%s-%s-%s-%s',
				$this->random_string( 4 ),
				$this->random_string( 4 ),
				$this->random_string( 4 ),
				$this->random_string( 4 )
			);
		} while ( $this->repository->find_by_key( $key ) );
		
		return strtoupper( $key );
	}
	
	/**
	 * Generate random string
	 *
	 * @param int $length String length
	 * @return string
	 */
	private function random_string( $length = 4 ) {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$result = '';
		
		for ( $i = 0; $i < $length; $i++ ) {
			$result .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
		}
		
		return $result;
	}
	
	/**
	 * Calculate expiry date from start date and duration
	 *
	 * @param string $start_date      Start date
	 * @param int    $duration_months Duration in months
	 * @return string Expiry date
	 */
	private function calculate_expiry_date( $start_date, $duration_months ) {
		$timestamp = strtotime( $start_date );
		$expiry_timestamp = strtotime( "+{$duration_months} months", $timestamp );
		return date( 'Y-m-d H:i:s', $expiry_timestamp );
	}
	
	/**
	 * Get days until expiry
	 *
	 * @param string $expiry_date Expiry date
	 * @return int Days remaining
	 */
	private function get_days_until_expiry( $expiry_date ) {
		$now = time();
		$expiry = strtotime( $expiry_date );
		$diff = $expiry - $now;
		return max( 0, floor( $diff / DAY_IN_SECONDS ) );
	}
	
	/**
	 * Check if customer exists
	 *
	 * @param int $customer_id Customer ID
	 * @return bool
	 */
	private function customer_exists( $customer_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_customers';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$customer_id
		) );
		return $exists > 0;
	}
	
	/**
	 * Check if patent exists
	 *
	 * @param int $patent_id Patent ID
	 * @return bool
	 */
	private function patent_exists( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$patent_id
		) );
		return $exists > 0;
	}
	
	/**
	 * Get customer data
	 *
	 * @param int $customer_id Customer ID
	 * @return object|null
	 */
	private function get_customer( $customer_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_customers';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$customer_id
		) );
	}
	
	/**
	 * Get patent data
	 *
	 * @param int $patent_id Patent ID
	 * @return object|null
	 */
	private function get_patent( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$patent_id
		) );
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id License ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'license_full_' . $id );
		}
		
		$cache->clear_tag( 'licenses' );
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
		$cache->set( $key, $value, $ttl, array( 'licenses' ) );
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
		$logger->log( $action, 'license', $entity_id, $description );
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
