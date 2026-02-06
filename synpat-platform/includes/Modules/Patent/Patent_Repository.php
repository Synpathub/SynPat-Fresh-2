<?php
/**
 * Patent repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Patent;

use SynPat\Core\Database\Query_Builder;

/**
 * Patent_Repository Class
 */
class Patent_Repository {
	
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
		$this->query = new Query_Builder( 'patents' );
	}
	
	/**
	 * Find patent by ID
	 *
	 * @param int $id Patent ID
	 * @return Patent_Entity|null
	 */
	public function find( $id ) {
		$result = $this->query
			->where( 'id', $id )
			->first();
		
		$this->query->reset();
		
		return Patent_Entity::from_object( $result );
	}
	
	/**
	 * Find patent by patent number and country
	 *
	 * @param string $patent_number Patent number
	 * @param string $country       Country code
	 * @return Patent_Entity|null
	 */
	public function find_by_number( $patent_number, $country = 'US' ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_patents';
		
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE patent_number = %s AND country = %s LIMIT 1",
			$patent_number,
			$country
		) );
		
		return Patent_Entity::from_object( $result );
	}
	
	/**
	 * Find all patents with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'        => 1,
			'per_page'    => 20,
			'status'      => null,
			'patent_type' => null,
			'country'     => null,
			'assignee'    => null,
			'search'      => null,
			'order_by'    => 'filing_date',
			'order'       => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Build query
		$query = new Query_Builder( 'patents' );
		
		// Apply filters
		if ( ! empty( $args['status'] ) ) {
			$query->where( 'status', $args['status'] );
		}
		
		if ( ! empty( $args['patent_type'] ) ) {
			$query->where( 'patent_type', $args['patent_type'] );
		}
		
		if ( ! empty( $args['country'] ) ) {
			$query->where( 'country', $args['country'] );
		}
		
		if ( ! empty( $args['assignee'] ) ) {
			$this->apply_assignee_filter( $query, $args['assignee'] );
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
		$patents = array();
		foreach ( $results as $result ) {
			$patents[] = Patent_Entity::from_object( $result );
		}
		
		return array(
			'data'        => $patents,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => ceil( $total / $args['per_page'] ),
		);
	}
	
	/**
	 * Find patents by portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return array
	 */
	public function find_by_portfolio( $portfolio_id ) {
		global $wpdb;
		
		$patents_table = $wpdb->prefix . 'synpat_patents';
		$pivot_table = $wpdb->prefix . 'synpat_portfolio_patents';
		
		$sql = $wpdb->prepare(
			"SELECT p.* FROM {$patents_table} p
			INNER JOIN {$pivot_table} pp ON p.id = pp.patent_id
			WHERE pp.portfolio_id = %d
			ORDER BY pp.position ASC, p.filing_date DESC",
			$portfolio_id
		);
		
		$results = $wpdb->get_results( $sql );
		
		$patents = array();
		foreach ( $results as $result ) {
			$patents[] = Patent_Entity::from_object( $result );
		}
		
		return $patents;
	}
	
	/**
	 * Search patents using FULLTEXT index
	 *
	 * @param string $query Search query
	 * @param array  $args  Additional arguments
	 * @return array
	 */
	public function search( $query, $args = array() ) {
		global $wpdb;
		
		$defaults = array(
			'page'     => 1,
			'per_page' => 20,
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$table = $wpdb->prefix . 'synpat_patents';
		$search = $wpdb->esc_like( $query );
		
		// Use FULLTEXT search if available, otherwise fall back to LIKE
		$count_sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			WHERE MATCH(title, abstract) AGAINST(%s IN NATURAL LANGUAGE MODE)
			   OR patent_number LIKE %s
			   OR assignee LIKE %s",
			$query,
			'%' . $search . '%',
			'%' . $search . '%'
		);
		
		$total = $wpdb->get_var( $count_sql );
		
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE MATCH(title, abstract) AGAINST(%s IN NATURAL LANGUAGE MODE)
			   OR patent_number LIKE %s
			   OR assignee LIKE %s
			ORDER BY filing_date DESC
			LIMIT %d OFFSET %d",
			$query,
			'%' . $search . '%',
			'%' . $search . '%',
			$args['per_page'],
			$offset
		);
		
		$results = $wpdb->get_results( $sql );
		
		$patents = array();
		foreach ( $results as $result ) {
			$patents[] = Patent_Entity::from_object( $result );
		}
		
		return array(
			'data'        => $patents,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => ceil( $total / $args['per_page'] ),
		);
	}
	
	/**
	 * Apply assignee filter to query
	 *
	 * @param Query_Builder $query   Query builder instance
	 * @param string        $assignee Assignee name
	 */
	private function apply_assignee_filter( $query, $assignee ) {
		global $wpdb;
		$search_term = '%' . $wpdb->esc_like( $assignee ) . '%';
		$query->where( 'assignee', $search_term, 'LIKE' );
	}
	
	/**
	 * Apply search to query
	 *
	 * @param Query_Builder $query  Query builder instance
	 * @param string        $search Search term
	 */
	private function apply_search( $query, $search ) {
		global $wpdb;
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';
		$query->where( 'title', $search_term, 'LIKE' );
	}
	
	/**
	 * Save new patent
	 *
	 * @param array $data Patent data
	 * @return int|false Patent ID or false on failure
	 */
	public function save( $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Add timestamps
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update patent
	 *
	 * @param int   $id   Patent ID
	 * @param array $data Patent data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Remove created fields
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'patents' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete patent
	 *
	 * @param int $id Patent ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'patents' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Sanitize patent data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array(
			'patent_number', 'application_number', 'patent_type', 'status',
			'country', 'assignee', 'patent_family_id', 'legal_status',
			'maintenance_fee_status', 'source', 'source_id'
		);
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		$textarea_fields = array(
			'title', 'abstract', 'inventors', 'ipc_classification',
			'cpc_classification', 'us_classification'
		);
		
		foreach ( $textarea_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		
		$date_fields = array(
			'filing_date', 'publication_date', 'grant_date',
			'expiration_date', 'priority_date'
		);
		
		foreach ( $date_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		$int_fields = array(
			'claims_count', 'independent_claims', 'forward_citations', 'backward_citations'
		);
		
		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = absint( $data[ $field ] );
			}
		}
		
		// Validate URL
		if ( isset( $data['pdf_url'] ) && ! empty( $data['pdf_url'] ) ) {
			$clean['pdf_url'] = esc_url_raw( $data['pdf_url'] );
		}
		
		// Handle meta_data as JSON
		if ( isset( $data['meta_data'] ) ) {
			if ( is_array( $data['meta_data'] ) ) {
				$clean['meta_data'] = wp_json_encode( $data['meta_data'] );
			} else {
				$clean['meta_data'] = sanitize_textarea_field( $data['meta_data'] );
			}
		}
		
		return $clean;
	}
	
	/**
	 * Get patents by status
	 *
	 * @param string $status Patent status
	 * @return array
	 */
	public function find_by_status( $status ) {
		$results = $this->query
			->where( 'status', $status )
			->order_by( 'filing_date', 'DESC' )
			->get();
		
		$this->query->reset();
		
		$patents = array();
		foreach ( $results as $result ) {
			$patents[] = Patent_Entity::from_object( $result );
		}
		
		return $patents;
	}
	
	/**
	 * Get patents by type
	 *
	 * @param string $type Patent type
	 * @return array
	 */
	public function find_by_type( $type ) {
		$results = $this->query
			->where( 'patent_type', $type )
			->order_by( 'filing_date', 'DESC' )
			->get();
		
		$this->query->reset();
		
		$patents = array();
		foreach ( $results as $result ) {
			$patents[] = Patent_Entity::from_object( $result );
		}
		
		return $patents;
	}
	
	/**
	 * Get patents expiring soon
	 *
	 * @param int $days Number of days
	 * @return array
	 */
	public function find_expiring_soon( $days = 90 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_patents';
		$date = date( 'Y-m-d', strtotime( "+{$days} days" ) );
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE expiration_date IS NOT NULL
			  AND expiration_date <= %s
			  AND expiration_date >= CURDATE()
			  AND status = 'active'
			ORDER BY expiration_date ASC",
			$date
		);
		
		$results = $wpdb->get_results( $sql );
		
		$patents = array();
		foreach ( $results as $result ) {
			$patents[] = Patent_Entity::from_object( $result );
		}
		
		return $patents;
	}
}
