<?php
/**
 * License repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Licensing;

use SynPat\Core\Database\Query_Builder;

/**
 * License_Repository Class
 */
class License_Repository {
	
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
		$this->query = new Query_Builder( 'licenses' );
	}
	
	/**
	 * Find license by ID
	 *
	 * @param int $id License ID
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
	 * Find license by license key
	 *
	 * @param string $license_key License key
	 * @return object|null
	 */
	public function find_by_key( $license_key ) {
		$result = $this->query
			->where( 'license_key', $license_key )
			->first();
		
		$this->query->reset();
		
		return $result;
	}
	
	/**
	 * Find licenses by customer ID
	 *
	 * @param int $customer_id Customer ID
	 * @return array
	 */
	public function find_by_customer( $customer_id ) {
		$results = $this->query
			->where( 'customer_id', $customer_id )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find licenses by patent ID
	 *
	 * @param int $patent_id Patent ID
	 * @return array
	 */
	public function find_by_patent( $patent_id ) {
		$results = $this->query
			->where( 'patent_id', $patent_id )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find licenses by status
	 *
	 * @param string $status License status
	 * @return array
	 */
	public function find_by_status( $status ) {
		$results = $this->query
			->where( 'status', $status )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find expiring licenses
	 *
	 * @param int $days_before Number of days before expiry
	 * @return array
	 */
	public function find_expiring( $days_before = 30 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_licenses';
		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "+{$days_before} days" ) );
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} 
			WHERE status = 'active' 
			AND expiry_date IS NOT NULL 
			AND expiry_date <= %s 
			AND expiry_date > NOW()
			ORDER BY expiry_date ASC",
			$date_threshold
		);
		
		return $wpdb->get_results( $sql );
	}
	
	/**
	 * Find all licenses with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'        => 1,
			'per_page'    => 20,
			'status'      => null,
			'customer_id' => null,
			'patent_id'   => null,
			'search'      => null,
			'order_by'    => 'created_at',
			'order'       => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$query = new Query_Builder( 'licenses' );
		
		if ( ! empty( $args['status'] ) ) {
			$query->where( 'status', $args['status'] );
		}
		
		if ( ! empty( $args['customer_id'] ) ) {
			$query->where( 'customer_id', $args['customer_id'] );
		}
		
		if ( ! empty( $args['patent_id'] ) ) {
			$query->where( 'patent_id', $args['patent_id'] );
		}
		
		if ( ! empty( $args['search'] ) ) {
			$this->apply_search( $query, $args['search'] );
		}
		
		$total = $query->count();
		
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
	 * Apply search to query
	 *
	 * @param Query_Builder $query  Query builder instance
	 * @param string        $search Search term
	 */
	private function apply_search( $query, $search ) {
		global $wpdb;
		
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';
		
		$licenses_table = $wpdb->prefix . 'synpat_licenses';
		$customers_table = $wpdb->prefix . 'synpat_customers';
		$patents_table = $wpdb->prefix . 'synpat_patents';
		
		$query->where_raw(
			$wpdb->prepare(
				"({$licenses_table}.license_key LIKE %s 
				OR {$licenses_table}.id IN (
					SELECT id FROM {$licenses_table} l
					LEFT JOIN {$customers_table} c ON l.customer_id = c.id
					LEFT JOIN {$patents_table} p ON l.patent_id = p.id
					WHERE c.name LIKE %s OR c.email LIKE %s OR p.patent_number LIKE %s
				))",
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);
	}
	
	/**
	 * Save new license
	 *
	 * @param array $data License data
	 * @return int|false License ID or false on failure
	 */
	public function save( $data ) {
		$clean_data = $this->sanitize_data( $data );
		
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update license
	 *
	 * @param int   $id   License ID
	 * @param array $data License data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		$clean_data = $this->sanitize_data( $data );
		
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'licenses' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete license
	 *
	 * @param int $id License ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'licenses' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Get license count by status
	 *
	 * @param string|null $status License status (null for all)
	 * @return int
	 */
	public function count_by_status( $status = null ) {
		$query = new Query_Builder( 'licenses' );
		
		if ( $status ) {
			$query->where( 'status', $status );
		}
		
		return $query->count();
	}
	
	/**
	 * Get revenue by date range
	 *
	 * @param string $start_date Start date
	 * @param string $end_date   End date
	 * @return float Total revenue
	 */
	public function get_revenue( $start_date, $end_date ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_licenses';
		
		$sql = $wpdb->prepare(
			"SELECT SUM(license_fee) as total 
			FROM {$table} 
			WHERE created_at BETWEEN %s AND %s 
			AND status IN ('active', 'inactive')",
			$start_date,
			$end_date
		);
		
		$result = $wpdb->get_var( $sql );
		
		return $result ? floatval( $result ) : 0.0;
	}
	
	/**
	 * Sanitize license data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array( 'license_key', 'status', 'license_type' );
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		$textarea_fields = array( 'terms', 'restrictions', 'notes' );
		
		foreach ( $textarea_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		
		$int_fields = array( 'customer_id', 'patent_id', 'duration_months' );
		
		foreach ( $int_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = absint( $data[ $field ] );
			}
		}
		
		$decimal_fields = array( 'license_fee', 'royalty_rate' );
		
		foreach ( $decimal_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = floatval( $data[ $field ] );
			}
		}
		
		$date_fields = array( 'start_date', 'expiry_date', 'activated_at', 'suspended_at' );
		
		foreach ( $date_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		if ( isset( $data['territories'] ) ) {
			if ( is_array( $data['territories'] ) ) {
				$clean['territories'] = wp_json_encode( array_map( 'sanitize_text_field', $data['territories'] ) );
			} else {
				$clean['territories'] = sanitize_textarea_field( $data['territories'] );
			}
		}
		
		if ( isset( $data['meta_data'] ) ) {
			if ( is_array( $data['meta_data'] ) ) {
				$clean['meta_data'] = wp_json_encode( $data['meta_data'] );
			} else {
				$clean['meta_data'] = sanitize_textarea_field( $data['meta_data'] );
			}
		}
		
		return $clean;
	}
}
