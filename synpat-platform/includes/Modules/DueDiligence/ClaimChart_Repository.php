<?php
/**
 * Claim Chart repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\DueDiligence;

use SynPat\Core\Database\Query_Builder;

/**
 * ClaimChart_Repository Class
 */
class ClaimChart_Repository {
	
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
		$this->query = new Query_Builder( 'claim_charts' );
	}
	
	/**
	 * Find claim chart by ID
	 *
	 * @param int $id Claim chart ID
	 * @return object|null
	 */
	public function find( $id ) {
		$result = $this->query
			->where( 'id', $id )
			->first();
		
		$this->query->reset();
		
		return $result;
	}
	
	/**
	 * Find all claim charts with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'              => 1,
			'per_page'          => 20,
			'due_diligence_id'  => null,
			'patent_id'         => null,
			'search'            => null,
			'order_by'          => 'created_at',
			'order'             => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Build query
		$query = new Query_Builder( 'claim_charts' );
		
		// Apply filters
		if ( ! empty( $args['due_diligence_id'] ) ) {
			$query->where( 'due_diligence_id', absint( $args['due_diligence_id'] ) );
		}
		
		if ( ! empty( $args['patent_id'] ) ) {
			$query->where( 'patent_id', absint( $args['patent_id'] ) );
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
		
		return array(
			'data'        => $results,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => ceil( $total / $args['per_page'] ),
		);
	}
	
	/**
	 * Find claim charts by due diligence
	 *
	 * @param int $due_diligence_id Due diligence ID
	 * @return array
	 */
	public function find_by_due_diligence( $due_diligence_id ) {
		$results = $this->query
			->where( 'due_diligence_id', absint( $due_diligence_id ) )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find claim charts by patent
	 *
	 * @param int $patent_id Patent ID
	 * @return array
	 */
	public function find_by_patent( $patent_id ) {
		$results = $this->query
			->where( 'patent_id', absint( $patent_id ) )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
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
		
		// Search in title and claim_text
		$query->where_raw(
			"(title LIKE %s OR claim_text LIKE %s OR product_description LIKE %s)",
			array( $search_term, $search_term, $search_term )
		);
	}
	
	/**
	 * Save new claim chart
	 *
	 * @param array $data Claim chart data
	 * @return int|false Claim chart ID or false on failure
	 */
	public function save( $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Add metadata
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update claim chart
	 *
	 * @param int   $id   Claim chart ID
	 * @param array $data Claim chart data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Remove created fields
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'claim_charts' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete claim chart
	 *
	 * @param int $id Claim chart ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'claim_charts' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Sanitize claim chart data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array(
			'title', 'claim_number', 'product_name', 'infringement_type'
		);
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		$textarea_fields = array(
			'claim_text', 'product_description', 'analysis', 'notes'
		);
		
		foreach ( $textarea_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		
		$int_fields = array(
			'due_diligence_id', 'patent_id'
		);
		
		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = absint( $data[ $field ] );
			}
		}
		
		// Handle claim_elements as JSON
		if ( isset( $data['claim_elements'] ) ) {
			if ( is_array( $data['claim_elements'] ) ) {
				$clean['claim_elements'] = wp_json_encode( $data['claim_elements'] );
			} else {
				$clean['claim_elements'] = sanitize_textarea_field( $data['claim_elements'] );
			}
		}
		
		// Handle product_features as JSON
		if ( isset( $data['product_features'] ) ) {
			if ( is_array( $data['product_features'] ) ) {
				$clean['product_features'] = wp_json_encode( $data['product_features'] );
			} else {
				$clean['product_features'] = sanitize_textarea_field( $data['product_features'] );
			}
		}
		
		// Handle evidence_mapping as JSON
		if ( isset( $data['evidence_mapping'] ) ) {
			if ( is_array( $data['evidence_mapping'] ) ) {
				$clean['evidence_mapping'] = wp_json_encode( $data['evidence_mapping'] );
			} else {
				$clean['evidence_mapping'] = sanitize_textarea_field( $data['evidence_mapping'] );
			}
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
	 * Get claim charts with patent data
	 *
	 * @param int $due_diligence_id Due diligence ID
	 * @return array
	 */
	public function find_with_patent_data( $due_diligence_id ) {
		global $wpdb;
		
		$claim_charts_table = $wpdb->prefix . 'synpat_claim_charts';
		$patents_table = $wpdb->prefix . 'synpat_patents';
		
		$sql = $wpdb->prepare(
			"SELECT 
				cc.*,
				p.patent_number,
				p.title as patent_title,
				p.assignee
			FROM {$claim_charts_table} cc
			LEFT JOIN {$patents_table} p ON cc.patent_id = p.id
			WHERE cc.due_diligence_id = %d
			ORDER BY cc.created_at DESC",
			$due_diligence_id
		);
		
		return $wpdb->get_results( $sql );
	}
	
	/**
	 * Get claim chart count by due diligence
	 *
	 * @param int $due_diligence_id Due diligence ID
	 * @return int
	 */
	public function count_by_due_diligence( $due_diligence_id ) {
		return $this->query
			->where( 'due_diligence_id', absint( $due_diligence_id ) )
			->count();
	}
	
	/**
	 * Get claim chart count by patent
	 *
	 * @param int $patent_id Patent ID
	 * @return int
	 */
	public function count_by_patent( $patent_id ) {
		return $this->query
			->where( 'patent_id', absint( $patent_id ) )
			->count();
	}
	
	/**
	 * Bulk delete by due diligence
	 *
	 * @param int $due_diligence_id Due diligence ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete_by_due_diligence( $due_diligence_id ) {
		$query = new Query_Builder( 'claim_charts' );
		return $query
			->where( 'due_diligence_id', absint( $due_diligence_id ) )
			->delete();
	}
	
	/**
	 * Bulk delete by patent
	 *
	 * @param int $patent_id Patent ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete_by_patent( $patent_id ) {
		$query = new Query_Builder( 'claim_charts' );
		return $query
			->where( 'patent_id', absint( $patent_id ) )
			->delete();
	}
}
