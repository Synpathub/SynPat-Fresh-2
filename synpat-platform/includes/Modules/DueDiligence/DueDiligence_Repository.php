<?php
/**
 * Due Diligence repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\DueDiligence;

use SynPat\Core\Database\Query_Builder;

/**
 * DueDiligence_Repository Class
 */
class DueDiligence_Repository {
	
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
		$this->query = new Query_Builder( 'due_diligence' );
	}
	
	/**
	 * Find due diligence by ID
	 *
	 * @param int $id Due diligence ID
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
	 * Find all due diligences with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'         => 1,
			'per_page'     => 20,
			'status'       => null,
			'assigned_to'  => null,
			'patent_id'    => null,
			'portfolio_id' => null,
			'priority'     => null,
			'search'       => null,
			'order_by'     => 'created_at',
			'order'        => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Build query
		$query = new Query_Builder( 'due_diligence' );
		
		// Apply filters
		if ( ! empty( $args['status'] ) ) {
			$query->where( 'status', $args['status'] );
		}
		
		if ( ! empty( $args['assigned_to'] ) ) {
			$query->where( 'assigned_to', absint( $args['assigned_to'] ) );
		}
		
		if ( ! empty( $args['patent_id'] ) ) {
			$query->where( 'patent_id', absint( $args['patent_id'] ) );
		}
		
		if ( ! empty( $args['portfolio_id'] ) ) {
			$query->where( 'portfolio_id', absint( $args['portfolio_id'] ) );
		}
		
		if ( ! empty( $args['priority'] ) ) {
			$query->where( 'priority', $args['priority'] );
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
	 * Find due diligences by patent
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
	 * Find due diligences by portfolio
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return array
	 */
	public function find_by_portfolio( $portfolio_id ) {
		$results = $this->query
			->where( 'portfolio_id', absint( $portfolio_id ) )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find due diligences assigned to user
	 *
	 * @param int $user_id User ID
	 * @return array
	 */
	public function find_by_assigned_user( $user_id ) {
		$results = $this->query
			->where( 'assigned_to', absint( $user_id ) )
			->order_by( 'due_date', 'ASC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find due diligences by status
	 *
	 * @param string $status Status
	 * @return array
	 */
	public function find_by_status( $status ) {
		$results = $this->query
			->where( 'status', sanitize_text_field( $status ) )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find overdue due diligences
	 *
	 * @return array
	 */
	public function find_overdue() {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_due_diligence';
		$current_date = current_time( 'mysql' );
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE due_date IS NOT NULL
			  AND due_date < %s
			  AND status NOT IN ('completed', 'cancelled')
			ORDER BY due_date ASC",
			$current_date
		);
		
		return $wpdb->get_results( $sql );
	}
	
	/**
	 * Find due soon
	 *
	 * @param int $days Number of days
	 * @return array
	 */
	public function find_due_soon( $days = 7 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_due_diligence';
		$current_date = current_time( 'mysql' );
		$future_date = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days} days" ) );
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE due_date IS NOT NULL
			  AND due_date >= %s
			  AND due_date <= %s
			  AND status NOT IN ('completed', 'cancelled')
			ORDER BY due_date ASC",
			$current_date,
			$future_date
		);
		
		return $wpdb->get_results( $sql );
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
		
		// Search in title and description
		$query->where_raw(
			"(title LIKE %s OR description LIKE %s)",
			array( $search_term, $search_term )
		);
	}
	
	/**
	 * Save new due diligence
	 *
	 * @param array $data Due diligence data
	 * @return int|false Due diligence ID or false on failure
	 */
	public function save( $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Add metadata
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update due diligence
	 *
	 * @param int   $id   Due diligence ID
	 * @param array $data Due diligence data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		// Sanitize data
		$clean_data = $this->sanitize_data( $data );
		
		// Remove created fields
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'due_diligence' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete due diligence
	 *
	 * @param int $id Due diligence ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'due_diligence' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Sanitize due diligence data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array(
			'title', 'status', 'priority', 'type'
		);
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		$textarea_fields = array( 'description', 'notes', 'findings' );
		
		foreach ( $textarea_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		
		$date_fields = array(
			'start_date', 'due_date', 'assigned_at', 'completed_at'
		);
		
		foreach ( $date_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		$int_fields = array(
			'patent_id', 'portfolio_id', 'assigned_to', 'completed_by', 'completion_percentage'
		);
		
		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = absint( $data[ $field ] );
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
	 * Get statistics for due diligences
	 *
	 * @param array $filters Optional filters
	 * @return array
	 */
	public function get_statistics( $filters = array() ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_due_diligence';
		
		$where_clauses = array( '1=1' );
		$where_values = array();
		
		if ( ! empty( $filters['patent_id'] ) ) {
			$where_clauses[] = 'patent_id = %d';
			$where_values[] = absint( $filters['patent_id'] );
		}
		
		if ( ! empty( $filters['portfolio_id'] ) ) {
			$where_clauses[] = 'portfolio_id = %d';
			$where_values[] = absint( $filters['portfolio_id'] );
		}
		
		if ( ! empty( $filters['assigned_to'] ) ) {
			$where_clauses[] = 'assigned_to = %d';
			$where_values[] = absint( $filters['assigned_to'] );
		}
		
		$where_sql = implode( ' AND ', $where_clauses );
		
		$sql = "SELECT 
			COUNT(*) as total,
			SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
			SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
			SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
			SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
			SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
			AVG(completion_percentage) as avg_completion
			FROM {$table}
			WHERE {$where_sql}";
		
		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values );
		}
		
		return $wpdb->get_row( $sql, ARRAY_A );
	}
	
	/**
	 * Bulk update status
	 *
	 * @param array  $ids    Due diligence IDs
	 * @param string $status New status
	 * @return int|false Number of rows affected or false
	 */
	public function bulk_update_status( $ids, $status ) {
		global $wpdb;
		
		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return false;
		}
		
		$table = $wpdb->prefix . 'synpat_due_diligence';
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		
		$sql = $wpdb->prepare(
			"UPDATE {$table} SET status = %s WHERE id IN ({$ids_placeholder})",
			array_merge( array( $status ), $ids )
		);
		
		return $wpdb->query( $sql );
	}
	
	/**
	 * Get completion trend data
	 *
	 * @param int $days Number of days to look back
	 * @return array
	 */
	public function get_completion_trend( $days = 30 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_due_diligence';
		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );
		
		$sql = $wpdb->prepare(
			"SELECT 
				DATE(completed_at) as date,
				COUNT(*) as count
			FROM {$table}
			WHERE status = 'completed'
			  AND completed_at >= %s
			GROUP BY DATE(completed_at)
			ORDER BY date ASC",
			$start_date
		);
		
		return $wpdb->get_results( $sql, ARRAY_A );
	}
}
