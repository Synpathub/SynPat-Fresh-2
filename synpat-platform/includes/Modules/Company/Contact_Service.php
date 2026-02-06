<?php
/**
 * Contact service layer for business logic.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Company;

use SynPat\Core\Plugin;
use WP_Error;

/**
 * Contact_Service Class
 */
class Contact_Service {
	
	/**
	 * Repository instance
	 *
	 * @var Contact_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new Contact_Repository();
	}
	
	/**
	 * Create a new contact
	 *
	 * @param array $data Contact data
	 * @return array|WP_Error
	 */
	public function create( $data ) {
		// Validate required fields
		$validation = $this->validate_contact_data( $data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Verify company exists
		if ( ! $this->company_exists( $data['company_id'] ) ) {
			return new WP_Error(
				'invalid_company',
				__( 'Invalid company ID.', 'synpat-platform' )
			);
		}
		
		// If setting as primary, unset other primary contacts
		if ( ! empty( $data['is_primary'] ) ) {
			$this->repository->unset_primary_contacts( $data['company_id'] );
		}
		
		// Save contact
		$contact_id = $this->repository->save( $data );
		
		if ( ! $contact_id ) {
			return new WP_Error(
				'save_failed',
				__( 'Failed to create contact.', 'synpat-platform' )
			);
		}
		
		// Get created contact
		$contact = $this->repository->find( $contact_id );
		
		// Clear cache
		$this->clear_cache( $data['company_id'] );
		
		// Log activity
		$this->log_activity( 'contact_created', $contact_id, 'Created contact: ' . $data['first_name'] . ' ' . $data['last_name'] );
		
		// Dispatch event
		$this->dispatch_event( 'contact_created', array( 'contact' => $contact ) );
		
		return $contact;
	}
	
	/**
	 * Update a contact
	 *
	 * @param int   $id   Contact ID
	 * @param array $data Contact data
	 * @return array|WP_Error
	 */
	public function update( $id, $data ) {
		// Check if contact exists
		$contact = $this->repository->find( $id );
		if ( ! $contact ) {
			return new WP_Error(
				'not_found',
				__( 'Contact not found.', 'synpat-platform' )
			);
		}
		
		// Validate data
		$validation = $this->validate_contact_data( $data, $id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// If changing company, verify new company exists
		if ( isset( $data['company_id'] ) && $data['company_id'] != $contact['company_id'] ) {
			if ( ! $this->company_exists( $data['company_id'] ) ) {
				return new WP_Error(
					'invalid_company',
					__( 'Invalid company ID.', 'synpat-platform' )
				);
			}
		}
		
		$company_id = isset( $data['company_id'] ) ? $data['company_id'] : $contact['company_id'];
		
		// If setting as primary, unset other primary contacts
		if ( ! empty( $data['is_primary'] ) ) {
			$this->repository->unset_primary_contacts( $company_id, $id );
		}
		
		// Update contact
		$result = $this->repository->update( $id, $data );
		
		if ( false === $result ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update contact.', 'synpat-platform' )
			);
		}
		
		// Get updated contact
		$updated_contact = $this->repository->find( $id );
		
		// Clear cache
		$this->clear_cache( $company_id );
		
		// Log activity
		$this->log_activity( 'contact_updated', $id, 'Updated contact: ' . $updated_contact['first_name'] . ' ' . $updated_contact['last_name'] );
		
		// Dispatch event
		$this->dispatch_event( 'contact_updated', array(
			'contact' => $updated_contact,
			'old_data' => $contact,
		) );
		
		return $updated_contact;
	}
	
	/**
	 * Get a contact by ID
	 *
	 * @param int $id Contact ID
	 * @return array|null
	 */
	public function get( $id ) {
		return $this->repository->find( $id );
	}
	
	/**
	 * Get contacts by company
	 *
	 * @param int $company_id Company ID
	 * @return array
	 */
	public function get_by_company( $company_id ) {
		// Try cache
		$cache_key = 'company_contacts_' . $company_id;
		$cached = $this->get_cache( $cache_key );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get from database
		$contacts = $this->repository->find_by_company( $company_id );
		
		// Cache results
		$this->set_cache( $cache_key, $contacts );
		
		return $contacts;
	}
	
	/**
	 * Get primary contact for company
	 *
	 * @param int $company_id Company ID
	 * @return array|null
	 */
	public function get_primary_contact( $company_id ) {
		return $this->repository->find_primary_contact( $company_id );
	}
	
	/**
	 * Delete a contact
	 *
	 * @param int $id Contact ID
	 * @return bool|WP_Error
	 */
	public function delete( $id ) {
		// Check if contact exists
		$contact = $this->repository->find( $id );
		if ( ! $contact ) {
			return new WP_Error(
				'not_found',
				__( 'Contact not found.', 'synpat-platform' )
			);
		}
		
		$company_id = $contact['company_id'];
		
		// Delete contact
		$result = $this->repository->delete( $id );
		
		if ( ! $result ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete contact.', 'synpat-platform' )
			);
		}
		
		// Clear cache
		$this->clear_cache( $company_id );
		
		// Log activity
		$this->log_activity( 'contact_deleted', $id, 'Deleted contact: ' . $contact['first_name'] . ' ' . $contact['last_name'] );
		
		// Dispatch event
		$this->dispatch_event( 'contact_deleted', array( 'contact' => $contact ) );
		
		return true;
	}
	
	/**
	 * Validate contact data
	 *
	 * @param array    $data Contact data
	 * @param int|null $id   Contact ID (for updates)
	 * @return bool|WP_Error
	 */
	private function validate_contact_data( $data, $id = null ) {
		// Required fields for new contacts
		if ( null === $id ) {
			if ( empty( $data['company_id'] ) ) {
				return new WP_Error(
					'missing_company',
					__( 'Company ID is required.', 'synpat-platform' )
				);
			}
			
			if ( empty( $data['first_name'] ) ) {
				return new WP_Error(
					'missing_first_name',
					__( 'First name is required.', 'synpat-platform' )
				);
			}
			
			if ( empty( $data['last_name'] ) ) {
				return new WP_Error(
					'missing_last_name',
					__( 'Last name is required.', 'synpat-platform' )
				);
			}
		}
		
		// Validate email if provided
		if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Invalid email address.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Check if company exists
	 *
	 * @param int $company_id Company ID
	 * @return bool
	 */
	private function company_exists( $company_id ) {
		$company_service = new Company_Service();
		$company = $company_service->get( $company_id );
		return ! empty( $company );
	}
	
	/**
	 * Clear cache
	 *
	 * @param int $company_id Company ID
	 */
	private function clear_cache( $company_id ) {
		$cache = Plugin::get_instance()->cache();
		$cache->delete( 'company_contacts_' . $company_id );
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
		$logger->log( $action, 'contact', $entity_id, $description );
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
