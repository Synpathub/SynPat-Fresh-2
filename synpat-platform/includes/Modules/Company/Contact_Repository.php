<?php
/**
 * Contact repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Company;

use SynPat\Core\Database\Query_Builder;

/**
 * Contact_Repository Class
 */
class Contact_Repository {
	
	/**
	 * Query builder instance
	 *
	 * @var Query_Builder
	 */
	private $query;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->query = new Query_Builder( 'contacts' );
	}
	
	/**
	 * Find contact by ID
	 *
	 * @param int $id Contact ID
	 * @return array|null
	 */
	public function find( $id ) {
		$result = $this->query
			->where( 'id', $id )
			->first();
		
		$this->query->reset();
		
		return $result ? (array) $result : null;
	}
	
	/**
	 * Find contacts by company
	 *
	 * @param int $company_id Company ID
	 * @return array
	 */
	public function find_by_company( $company_id ) {
		$results = $this->query
			->where( 'company_id', $company_id )
			->order_by( 'is_primary', 'DESC' )
			->order_by( 'last_name', 'ASC' )
			->get();
		
		$this->query->reset();
		
		$contacts = array();
		foreach ( $results as $result ) {
			$contacts[] = (array) $result;
		}
		
		return $contacts;
	}
	
	/**
	 * Find primary contact for company
	 *
	 * @param int $company_id Company ID
	 * @return array|null
	 */
	public function find_primary_contact( $company_id ) {
		$result = $this->query
			->where( 'company_id', $company_id )
			->where( 'is_primary', 1 )
			->first();
		
		$this->query->reset();
		
		return $result ? (array) $result : null;
	}
	
	/**
	 * Save new contact
	 *
	 * @param array $data Contact data
	 * @return int|false Contact ID or false on failure
	 */
	public function save( $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Add created_by
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update contact
	 *
	 * @param int   $id   Contact ID
	 * @param array $data Contact data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Remove created fields
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'contacts' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete contact
	 *
	 * @param int $id Contact ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'contacts' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Unset primary contacts for company
	 *
	 * @param int      $company_id Company ID
	 * @param int|null $exclude_id Contact ID to exclude
	 * @return int|false
	 */
	public function unset_primary_contacts( $company_id, $exclude_id = null ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_contacts';
		$query = $wpdb->prepare(
			"UPDATE {$table} SET is_primary = 0 WHERE company_id = %d",
			$company_id
		);
		
		if ( $exclude_id ) {
			$query .= $wpdb->prepare( " AND id != %d", $exclude_id );
		}
		
		return $wpdb->query( $query );
	}
	
	/**
	 * Sanitize contact data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		if ( isset( $data['company_id'] ) ) {
			$clean['company_id'] = absint( $data['company_id'] );
		}
		
		$text_fields = array( 'first_name', 'last_name', 'title', 'phone', 'mobile' );
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		if ( isset( $data['email'] ) ) {
			$clean['email'] = sanitize_email( $data['email'] );
		}
		
		if ( isset( $data['notes'] ) ) {
			$clean['notes'] = sanitize_textarea_field( $data['notes'] );
		}
		
		if ( isset( $data['is_primary'] ) ) {
			$clean['is_primary'] = ! empty( $data['is_primary'] ) ? 1 : 0;
		}
		
		return $clean;
	}
	
	/**
	 * Search contacts
	 *
	 * @param string $search_term Search term
	 * @param int    $limit       Maximum results
	 * @return array
	 */
	public function search( $search_term, $limit = 10 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_contacts';
		$search = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE first_name LIKE %s
			   OR last_name LIKE %s
			   OR email LIKE %s
			   OR title LIKE %s
			ORDER BY last_name ASC, first_name ASC
			LIMIT %d",
			$search,
			$search,
			$search,
			$search,
			$limit
		);
		
		$results = $wpdb->get_results( $sql );
		
		$contacts = array();
		foreach ( $results as $result ) {
			$contacts[] = (array) $result;
		}
		
		return $contacts;
	}
	
	/**
	 * Get contact count by company
	 *
	 * @param int $company_id Company ID
	 * @return int
	 */
	public function count_by_company( $company_id ) {
		return $this->query
			->where( 'company_id', $company_id )
			->count();
	}
}
