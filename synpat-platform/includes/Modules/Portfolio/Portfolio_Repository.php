<?php
/**
 * Portfolio repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Portfolio;

use SynPat\Core\Database\Query_Builder;

/**
 * Portfolio_Repository Class
 */
class Portfolio_Repository {
	
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
		$this->query = new Query_Builder( 'portfolios' );
	}
	
	/**
	 * Find portfolio by ID
	 *
	 * @param int $id Portfolio ID
	 * @return Portfolio_Entity|null
	 */
	public function find( $id ) {
		$result = $this->query
			->where( 'id', $id )
			->first();
		
		$this->query->reset();
		
		return Portfolio_Entity::from_object( $result );
	}
	
	/**
	 * Find portfolio by slug
	 *
	 * @param string $slug Portfolio slug
	 * @return Portfolio_Entity|null
	 */
	public function find_by_slug( $slug ) {
		$result = $this->query
			->where( 'slug', $slug )
			->first();
		
		$this->query->reset();
		
		return Portfolio_Entity::from_object( $result );
	}
	
	/**
	 * Find portfolios by status
	 *
	 * @param string $status Portfolio status
	 * @return array
	 */
	public function find_by_status( $status ) {
		$results = $this->query
			->where( 'status', $status )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		$portfolios = array();
		foreach ( $results as $result ) {
			$portfolios[] = Portfolio_Entity::from_object( $result );
		}
		
		return $portfolios;
	}
	
	/**
	 * Find portfolios by category
	 *
	 * @param int $category_id Category ID
	 * @return array
	 */
	public function find_by_category( $category_id ) {
		$results = $this->query
			->where( 'category_id', $category_id )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		$portfolios = array();
		foreach ( $results as $result ) {
			$portfolios[] = Portfolio_Entity::from_object( $result );
		}
		
		return $portfolios;
	}
	
	/**
	 * Find all portfolios with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'        => 1,
			'per_page'    => 20,
			'status'      => null,
			'visibility'  => null,
			'category_id' => null,
			'company_id'  => null,
			'search'      => null,
			'order_by'    => 'created_at',
			'order'       => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Build query
		$query = new Query_Builder( 'portfolios' );
		
		// Apply filters
		if ( ! empty( $args['status'] ) ) {
			$query->where( 'status', $args['status'] );
		}
		
		if ( ! empty( $args['visibility'] ) ) {
			$query->where( 'visibility', $args['visibility'] );
		}
		
		if ( ! empty( $args['category_id'] ) ) {
			$query->where( 'category_id', $args['category_id'] );
		}
		
		if ( ! empty( $args['company_id'] ) ) {
			$query->where( 'company_id', $args['company_id'] );
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
		$portfolios = array();
		foreach ( $results as $result ) {
			$portfolios[] = Portfolio_Entity::from_object( $result );
		}
		
		return array(
			'data'        => $portfolios,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => ceil( $total / $args['per_page'] ),
		);
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
	 * Save new portfolio
	 *
	 * @param array $data Portfolio data
	 * @return int|false Portfolio ID or false on failure
	 */
	public function save( $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Add created_by
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update portfolio
	 *
	 * @param int   $id   Portfolio ID
	 * @param array $data Portfolio data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Remove created fields
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'portfolios' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete portfolio
	 *
	 * @param int $id Portfolio ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'portfolios' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Add patents to portfolio
	 *
	 * @param int   $portfolio_id Portfolio ID
	 * @param array $patent_ids   Array of patent IDs
	 * @return bool
	 */
	public function add_patents( $portfolio_id, $patent_ids ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_portfolio_patents';
		$added_by = get_current_user_id();
		
		foreach ( $patent_ids as $patent_id ) {
			// Check if already exists
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE portfolio_id = %d AND patent_id = %d",
				$portfolio_id,
				$patent_id
			) );
			
			if ( $exists ) {
				continue;
			}
			
			// Insert
			$wpdb->insert(
				$table,
				array(
					'portfolio_id' => $portfolio_id,
					'patent_id'    => $patent_id,
					'added_by'     => $added_by,
				),
				array( '%d', '%d', '%d' )
			);
		}
		
		return true;
	}
	
	/**
	 * Remove patents from portfolio
	 *
	 * @param int   $portfolio_id Portfolio ID
	 * @param array $patent_ids   Array of patent IDs
	 * @return bool
	 */
	public function remove_patents( $portfolio_id, $patent_ids ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_portfolio_patents';
		
		foreach ( $patent_ids as $patent_id ) {
			$wpdb->delete(
				$table,
				array(
					'portfolio_id' => $portfolio_id,
					'patent_id'    => $patent_id,
				),
				array( '%d', '%d' )
			);
		}
		
		return true;
	}
	
	/**
	 * Get portfolio patents
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return array
	 */
	public function get_patents( $portfolio_id ) {
		global $wpdb;
		
		$patents_table = $wpdb->prefix . 'synpat_patents';
		$pivot_table = $wpdb->prefix . 'synpat_portfolio_patents';
		
		$sql = $wpdb->prepare(
			"SELECT p.* FROM {$patents_table} p
			INNER JOIN {$pivot_table} pp ON p.id = pp.patent_id
			WHERE pp.portfolio_id = %d
			ORDER BY pp.position ASC, pp.added_at DESC",
			$portfolio_id
		);
		
		$results = $wpdb->get_results( $sql );
		
		return $results ? $results : array();
	}
	
	/**
	 * Get patent count for portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return int
	 */
	public function get_patent_count( $portfolio_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_portfolio_patents';
		
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE portfolio_id = %d",
			$portfolio_id
		) );
	}
	
	/**
	 * Check if patent is in portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @param int $patent_id    Patent ID
	 * @return bool
	 */
	public function has_patent( $portfolio_id, $patent_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_portfolio_patents';
		
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE portfolio_id = %d AND patent_id = %d",
			$portfolio_id,
			$patent_id
		) );
		
		return $exists > 0;
	}
	
	/**
	 * Update patent position in portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @param int $patent_id    Patent ID
	 * @param int $position     Position
	 * @return bool
	 */
	public function update_patent_position( $portfolio_id, $patent_id, $position ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_portfolio_patents';
		
		return $wpdb->update(
			$table,
			array( 'position' => $position ),
			array(
				'portfolio_id' => $portfolio_id,
				'patent_id'    => $patent_id,
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}
	
	/**
	 * Sanitize portfolio data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array( 'title', 'slug', 'status', 'visibility', 'currency' );
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		if ( isset( $data['description'] ) ) {
			$clean['description'] = sanitize_textarea_field( $data['description'] );
		}
		
		$int_fields = array( 'category_id', 'company_id' );
		
		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = absint( $data[ $field ] );
			}
		}
		
		$decimal_fields = array( 'price', 'asking_price', 'valuation' );
		
		foreach ( $decimal_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = floatval( $data[ $field ] );
			}
		}
		
		if ( isset( $data['tags'] ) ) {
			if ( is_array( $data['tags'] ) ) {
				$clean['tags'] = implode( ',', array_map( 'sanitize_text_field', $data['tags'] ) );
			} else {
				$clean['tags'] = sanitize_text_field( $data['tags'] );
			}
		}
		
		if ( isset( $data['meta_data'] ) ) {
			if ( is_array( $data['meta_data'] ) ) {
				$clean['meta_data'] = wp_json_encode( $data['meta_data'] );
			} else {
				$clean['meta_data'] = sanitize_textarea_field( $data['meta_data'] );
			}
		}
		
		if ( isset( $data['published_at'] ) ) {
			$clean['published_at'] = sanitize_text_field( $data['published_at'] );
		}
		
		return $clean;
	}
}
