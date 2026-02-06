<?php
/**
 * Customer service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Licensing;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * Customer_Service Class
 */
class Customer_Service {
	
	/**
	 * Repository instance
	 *
	 * @var Customer_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new Customer_Repository();
	}
	
	/**
	 * Create a new customer
	 *
	 * @param array $data Customer data
	 * @return array|WP_Error
	 */
	public function create( $data ) {
		$validation = $this->validate_customer_data( $data, 'create' );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		if ( ! empty( $data['email'] ) && $this->repository->find_by_email( $data['email'] ) ) {
			return new WP_Error(
				'duplicate_email',
				__( 'A customer with this email already exists.', 'synpat-platform' )
			);
		}
		
		if ( ! isset( $data['status'] ) ) {
			$data['status'] = 'active';
		}
		
		$customer_id = $this->repository->save( $data );
		
		if ( ! $customer_id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create customer.', 'synpat-platform' )
			);
		}
		
		$customer = $this->repository->find( $customer_id );
		
		$this->clear_cache();
		
		$this->log_activity( 'customer_created', $customer_id, 'Created customer: ' . $data['name'] );
		
		$this->dispatch_event( 'customer_created', array( 'customer' => $customer ) );
		
		return $this->get( $customer_id );
	}
	
	/**
	 * Update a customer
	 *
	 * @param int   $id   Customer ID
	 * @param array $data Customer data
	 * @return array|WP_Error
	 */
	public function update( $id, $data ) {
		$validation = $this->validate_customer_data( $data, 'update' );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		$customer = $this->repository->find( $id );
		if ( ! $customer ) {
			return new WP_Error(
				'not_found',
				__( 'Customer not found.', 'synpat-platform' )
			);
		}
		
		if ( ! empty( $data['email'] ) && $data['email'] !== $customer->email ) {
			if ( $this->repository->find_by_email( $data['email'] ) ) {
				return new WP_Error(
					'duplicate_email',
					__( 'A customer with this email already exists.', 'synpat-platform' )
				);
			}
		}
		
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update customer.', 'synpat-platform' )
			);
		}
		
		$updated_customer = $this->get( $id );
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'customer_updated', $id, 'Updated customer: ' . $customer->name );
		
		$this->dispatch_event( 'customer_updated', array(
			'customer' => $updated_customer,
			'old_data' => $customer,
		) );
		
		return $updated_customer;
	}
	
	/**
	 * Get a customer by ID with related data
	 *
	 * @param int $id Customer ID
	 * @return array|null
	 */
	public function get( $id ) {
		$cache_key = 'customer_full_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$customer = $this->repository->find( $id );
		
		if ( ! $customer ) {
			return null;
		}
		
		$result = array(
			'customer'       => $customer,
			'licenses'       => $this->get_customer_licenses( $id ),
			'licenses_count' => $this->repository->count_licenses( $id ),
		);
		
		$this->set_cache( $cache_key, $result );
		
		return $result;
	}
	
	/**
	 * Get all customers with pagination and filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		$cache_key = 'customers_' . md5( wp_json_encode( $args ) );
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$result = $this->repository->find_all( $args );
		
		$this->set_cache( $cache_key, $result, 300 );
		
		return $result;
	}
	
	/**
	 * Delete a customer
	 *
	 * @param int $id Customer ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		$customer = $this->repository->find( $id );
		if ( ! $customer ) {
			return new WP_Error(
				'not_found',
				__( 'Customer not found.', 'synpat-platform' )
			);
		}
		
		$licenses_count = $this->repository->count_licenses( $id );
		if ( $licenses_count > 0 ) {
			return new WP_Error(
				'has_licenses',
				__( 'Cannot delete customer with active licenses.', 'synpat-platform' )
			);
		}
		
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete customer.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'customer_deleted', $id, 'Deleted customer: ' . $customer->name );
		
		$this->dispatch_event( 'customer_deleted', array( 'customer' => $customer ) );
		
		return true;
	}
	
	/**
	 * Search customers
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
	 * Get customer licenses
	 *
	 * @param int $customer_id Customer ID
	 * @return array
	 */
	public function get_customer_licenses( $customer_id ) {
		$cache_key = 'customer_licenses_' . $customer_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		$licenses = $this->repository->get_licenses( $customer_id );
		
		$this->set_cache( $cache_key, $licenses );
		
		return $licenses;
	}
	
	/**
	 * Deactivate customer and all licenses
	 *
	 * @param int $id Customer ID
	 * @return array|WP_Error
	 */
	public function deactivate( $id ) {
		$customer = $this->repository->find( $id );
		
		if ( ! $customer ) {
			return new WP_Error(
				'not_found',
				__( 'Customer not found.', 'synpat-platform' )
			);
		}
		
		if ( 'inactive' === $customer->status ) {
			return $this->get( $id );
		}
		
		$data = array( 'status' => 'inactive' );
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'deactivation_failed',
				__( 'Failed to deactivate customer.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'customer_deactivated', $id, 'Deactivated customer: ' . $customer->name );
		
		$this->dispatch_event( 'customer_deactivated', array( 'customer' => $this->repository->find( $id ) ) );
		
		return $this->get( $id );
	}
	
	/**
	 * Activate customer
	 *
	 * @param int $id Customer ID
	 * @return array|WP_Error
	 */
	public function activate( $id ) {
		$customer = $this->repository->find( $id );
		
		if ( ! $customer ) {
			return new WP_Error(
				'not_found',
				__( 'Customer not found.', 'synpat-platform' )
			);
		}
		
		if ( 'active' === $customer->status ) {
			return $this->get( $id );
		}
		
		$data = array( 'status' => 'active' );
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'activation_failed',
				__( 'Failed to activate customer.', 'synpat-platform' )
			);
		}
		
		$this->clear_cache( $id );
		
		$this->log_activity( 'customer_activated', $id, 'Activated customer: ' . $customer->name );
		
		$this->dispatch_event( 'customer_activated', array( 'customer' => $this->repository->find( $id ) ) );
		
		return $this->get( $id );
	}
	
	/**
	 * Validate customer data
	 *
	 * @param array  $data    Customer data
	 * @param string $context Validation context (create/update)
	 * @return true|WP_Error
	 */
	private function validate_customer_data( $data, $context = 'create' ) {
		if ( 'create' === $context ) {
			if ( empty( $data['name'] ) ) {
				return new WP_Error(
					'missing_name',
					__( 'Customer name is required.', 'synpat-platform' )
				);
			}
			
			if ( empty( $data['email'] ) ) {
				return new WP_Error(
					'missing_email',
					__( 'Customer email is required.', 'synpat-platform' )
				);
			}
		}
		
		if ( isset( $data['email'] ) && ! is_email( $data['email'] ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Invalid email address.', 'synpat-platform' )
			);
		}
		
		if ( isset( $data['status'] ) && ! in_array( $data['status'], array( 'active', 'inactive' ), true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid customer status.', 'synpat-platform' )
			);
		}
		
		if ( isset( $data['customer_type'] ) && ! in_array( $data['customer_type'], array( 'individual', 'company', 'organization' ), true ) ) {
			return new WP_Error(
				'invalid_type',
				__( 'Invalid customer type.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id Customer ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'customer_full_' . $id );
			$cache->delete( 'customer_licenses_' . $id );
		}
		
		$cache->clear_tag( 'customers' );
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
		$cache->set( $key, $value, $ttl, array( 'customers' ) );
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
		$logger->log( $action, 'customer', $entity_id, $description );
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
