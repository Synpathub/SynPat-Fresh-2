<?php
/**
 * Company repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Company;

use SynPat\Core\Database\Query_Builder;

/**
 * Company_Repository Class
 */
class Company_Repository {
	
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
		$this->query = new Query_Builder( 'companies' );
	}
	
	/**
	 * Find company by ID
	 *
	 * @param int $id Company ID
	 * @return Company_Entity|null
	 */
	public function find( $id ) {
		$result = $this->query
			->where( 'id', $id )
			->first();
		
		$this->query->reset();
		
		return Company_Entity::from_object( $result );
	}
	
	/**
	 * Find all companies with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
			'type'     => null,
			'industry' => null,
			'search'   => null,
			'order_by' => 'name',
			'order'    => 'ASC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Build query
		$query = new Query_Builder( 'companies' );
		
		// Apply filters
		if ( ! empty( $args['type'] ) ) {
			$query->where( 'type', $args['type'] );
		}
		
		if ( ! empty( $args['industry'] ) ) {
			$query->where( 'industry', $args['industry'] );
		}
		
		if ( ! empty( $args['search'] ) ) {
			$this->apply_search( $query, $args['search'] );
		}
		
		// Get total count
		$total = $query->count();
		
		// Apply pagination and ordering
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		
		$results = $query
			->order_by( $args['order_by'], $args['order'] )
			->limit( $args['per_page'] )
			->offset( $offset )
			->get();
		
		// Convert to entities
		$companies = array();
		foreach ( $results as $result ) {
			$companies[] = Company_Entity::from_object( $result );
		}
		
		return array(
			'data'       => $companies,
			'total'      => $total,
			'page'       => $args['page'],
			'per_page'   => $args['per_page'],
			'total_pages' => ceil( $total / $args['per_page'] ),
		);
	}
	
	/**
	 * Apply search to query
	 *
	 * @param Query_Builder $query Query builder instance
	 * @param string        $search Search term
	 */
	private function apply_search( $query, $search ) {
		global $wpdb;
		
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';
		
		// Note: Query_Builder doesn't support OR conditions yet
		// So we'll use a simple LIKE on name field
		// In production, consider enhancing Query_Builder for complex WHERE clauses
		$query->where( 'name', $search_term, 'LIKE' );
	}
	
	/**
	 * Save new company
	 *
	 * @param array $data Company data
	 * @return int|false Company ID or false on failure
	 */
	public function save( $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Add timestamps
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update company
	 *
	 * @param int   $id   Company ID
	 * @param array $data Company data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Remove created fields
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'companies' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete company
	 *
	 * @param int $id Company ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'companies' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Search companies by name, email, or website
	 *
	 * @param string $search_term Search term
	 * @param int    $limit       Maximum results
	 * @return array
	 */
	public function search( $search_term, $limit = 10 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_companies';
		$search = '%' . $wpdb->esc_like( $search_term ) . '%';
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE name LIKE %s
			   OR legal_name LIKE %s
			   OR email LIKE %s
			   OR website LIKE %s
			ORDER BY name ASC
			LIMIT %d",
			$search,
			$search,
			$search,
			$search,
			$limit
		);
		
		$results = $wpdb->get_results( $sql );
		
		$companies = array();
		foreach ( $results as $result ) {
			$companies[] = Company_Entity::from_object( $result );
		}
		
		return $companies;
	}
	
	/**
	 * Sanitize company data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array(
			'name', 'legal_name', 'type', 'industry', 'website',
			'email', 'phone', 'address_line1', 'address_line2',
			'city', 'state', 'country', 'postal_code'
		);
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		if ( isset( $data['notes'] ) ) {
			$clean['notes'] = sanitize_textarea_field( $data['notes'] );
		}
		
		// Validate email
		if ( isset( $clean['email'] ) && ! empty( $clean['email'] ) ) {
			$clean['email'] = sanitize_email( $clean['email'] );
		}
		
		// Validate URL
		if ( isset( $clean['website'] ) && ! empty( $clean['website'] ) ) {
			$clean['website'] = esc_url_raw( $clean['website'] );
		}
		
		return $clean;
	}
	
	/**
	 * Get companies by type
	 *
	 * @param string $type Company type
	 * @return array
	 */
	public function find_by_type( $type ) {
		$results = $this->query
			->where( 'type', $type )
			->order_by( 'name', 'ASC' )
			->get();
		
		$this->query->reset();
		
		$companies = array();
		foreach ( $results as $result ) {
			$companies[] = Company_Entity::from_object( $result );
		}
		
		return $companies;
	}
	
	/**
	 * Get companies by industry
	 *
	 * @param string $industry Industry
	 * @return array
	 */
	public function find_by_industry( $industry ) {
		$results = $this->query
			->where( 'industry', $industry )
			->order_by( 'name', 'ASC' )
			->get();
		
		$this->query->reset();
		
		$companies = array();
		foreach ( $results as $result ) {
			$companies[] = Company_Entity::from_object( $result );
		}
		
		return $companies;
	}
}
