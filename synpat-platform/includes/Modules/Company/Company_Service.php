<?php
/**
 * Company service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Company;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * Company_Service Class
 */
class Company_Service {
	
	/**
	 * Repository instance
	 *
	 * @var Company_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new Company_Repository();
	}
	
	/**
	 * Create a new company
	 *
	 * @param array $data Company data
	 * @return Company_Entity|WP_Error
	 */
	public function create( $data ) {
		// Validate required fields
		$validation = $this->validate_company_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Check for duplicate company name
		if ( $this->is_duplicate_name( $data['name'] ) ) {
			return new WP_Error(
				'duplicate_company',
				__( 'A company with this name already exists.', 'synpat-platform' )
			);
		}
		
		// Save company
		$company_id = $this->repository->save( $data );
		
		if ( ! $company_id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create company.', 'synpat-platform' )
			);
		}
		
		// Get created company
		$company = $this->repository->find( $company_id );
		
		// Clear cache
		$this->clear_cache();
		
		// Log activity
		$this->log_activity( 'company_created', $company_id, 'Created company: ' . $data['name'] );
		
		// Dispatch event
		$this->dispatch_event( 'company_created', array( 'company' => $company ) );
		
		return $company;
	}
	
	/**
	 * Update a company
	 *
	 * @param int   $id   Company ID
	 * @param array $data Company data
	 * @return Company_Entity|WP_Error
	 */
	public function update( $id, $data ) {
		// Check if company exists
		$company = $this->repository->find( $id );
		if ( ! $company ) {
			return new WP_Error(
				'not_found',
				__( 'Company not found.', 'synpat-platform' )
			);
		}
		
		// Validate data
		$validation = $this->validate_company_data( $data, $id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Check for duplicate name (excluding current company)
		if ( isset( $data['name'] ) && $this->is_duplicate_name( $data['name'], $id ) ) {
			return new WP_Error(
				'duplicate_company',
				__( 'A company with this name already exists.', 'synpat-platform' )
			);
		}
		
		// Update company
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update company.', 'synpat-platform' )
			);
		}
		
		// Get updated company
		$updated_company = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'company_updated', $id, 'Updated company: ' . $updated_company->name );
		
		// Dispatch event
		$this->dispatch_event( 'company_updated', array(
			'company' => $updated_company,
			'old_data' => $company,
		) );
		
		return $updated_company;
	}
	
	/**
	 * Get a company by ID
	 *
	 * @param int $id Company ID
	 * @return Company_Entity|null
	 */
	public function get( $id ) {
		// Try to get from cache
		$cache_key = 'company_' . $id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$company = $this->repository->find( $id );
		
		// Cache result
		if ( $company ) {
			$this->set_cache( $cache_key, $company );
		}
		
		return $company;
	}
	
	/**
	 * Get all companies with pagination
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_all( $args = array() ) {
		// Build cache key from args
		$cache_key = 'companies_' . md5( serialize( $args ) );
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$result = $this->repository->find_all( $args );
		
		// Cache result
		$this->set_cache( $cache_key, $result, 300 ); // 5 minutes
		
		return $result;
	}
	
	/**
	 * Delete a company
	 *
	 * @param int $id Company ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		// Check if company exists
		$company = $this->repository->find( $id );
		if ( ! $company ) {
			return new WP_Error(
				'not_found',
				__( 'Company not found.', 'synpat-platform' )
			);
		}
		
		// Check for dependencies
		$has_dependencies = $this->has_dependencies( $id );
		if ( $has_dependencies ) {
			return new WP_Error(
				'has_dependencies',
				__( 'Cannot delete company with existing patents, contacts, or portfolios.', 'synpat-platform' )
			);
		}
		
		// Delete company
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete company.', 'synpat-platform' )
			);
		}
		
		// Clear cache
		$this->clear_cache( $id );
		
		// Log activity
		$this->log_activity( 'company_deleted', $id, 'Deleted company: ' . $company->name );
		
		// Dispatch event
		$this->dispatch_event( 'company_deleted', array( 'company' => $company ) );
		
		return true;
	}
	
	/**
	 * Search companies
	 *
	 * @param string $query Search query
	 * @return array
	 */
	public function search( $query ) {
		if ( empty( $query ) ) {
			return array();
		}
		
		// Try cache
		$cache_key = 'company_search_' . md5( $query );
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Search database
		$results = $this->repository->search( $query );
		
		// Cache results
		$this->set_cache( $cache_key, $results, 300 );
		
		return $results;
	}
	
	/**
	 * Validate company data
	 *
	 * @param array    $data Company data
	 * @param int|null $id   Company ID (for updates)
	 * @return bool|WP_Error
	 */
	private function validate_company_data( $data, $id = null ) {
		// Name is required for new companies
		if ( null === $id && empty( $data['name'] ) ) {
			return new WP_Error(
				'missing_name',
				__( 'Company name is required.', 'synpat-platform' )
			);
		}
		
		// Validate email if provided
		if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Invalid email address.', 'synpat-platform' )
			);
		}
		
		// Validate URL if provided
		if ( ! empty( $data['website'] ) && ! filter_var( $data['website'], FILTER_VALIDATE_URL ) ) {
			return new WP_Error(
				'invalid_url',
				__( 'Invalid website URL.', 'synpat-platform' )
			);
		}
		
		// Validate type if provided
		$valid_types = array( 'client', 'competitor', 'partner', 'vendor', 'other' );
		if ( ! empty( $data['type'] ) && ! in_array( $data['type'], $valid_types, true ) ) {
			return new WP_Error(
				'invalid_type',
				__( 'Invalid company type.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Check if company name is duplicate
	 *
	 * @param string   $name Company name
	 * @param int|null $exclude_id Company ID to exclude
	 * @return bool
	 */
	private function is_duplicate_name( $name, $exclude_id = null ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_companies';
		$query = $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s", $name );
		
		if ( $exclude_id ) {
			$query .= $wpdb->prepare( " AND id != %d", $exclude_id );
		}
		
		$result = $wpdb->get_var( $query );
		
		return ! empty( $result );
	}
	
	/**
	 * Check if company has dependencies
	 *
	 * @param int $company_id Company ID
	 * @return bool
	 */
	private function has_dependencies( $company_id ) {
		global $wpdb;
		
		$prefix = $wpdb->prefix . 'synpat_';
		
		// Check contacts
		$contacts = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}contacts WHERE company_id = %d",
			$company_id
		) );
		
		if ( $contacts > 0 ) {
			return true;
		}
		
		// Check patents
		$patents = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}patents WHERE company_id = %d",
			$company_id
		) );
		
		if ( $patents > 0 ) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Clear cache
	 *
	 * @param int|null $id Company ID
	 */
	private function clear_cache( $id = null ) {
		$cache = Plugin::get_instance()->cache();
		
		if ( $id ) {
			$cache->delete( 'company_' . $id );
		}
		
		// Clear list caches
		$cache->delete_group( 'companies_' );
		$cache->delete_group( 'company_search_' );
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
		$logger->log( $action, 'company', $entity_id, $description );
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
