<?php
/**
 * Customer repository for data access.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Licensing;

use SynPat\Core\Database\Query_Builder;

/**
 * Customer_Repository Class
 */
class Customer_Repository {
	
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
		$this->query = new Query_Builder( 'customers' );
	}
	
	/**
	 * Find customer by ID
	 *
	 * @param int $id Customer ID
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
	 * Find customer by email
	 *
	 * @param string $email Customer email
	 * @return object|null
	 */
	public function find_by_email( $email ) {
		$result = $this->query
			->where( 'email', $email )
			->first();
		
		$this->query->reset();
		
		return $result;
	}
	
	/**
	 * Find customers by status
	 *
	 * @param string $status Customer status
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
	 * Find customers by type
	 *
	 * @param string $customer_type Customer type
	 * @return array
	 */
	public function find_by_type( $customer_type ) {
		$results = $this->query
			->where( 'customer_type', $customer_type )
			->order_by( 'created_at', 'DESC' )
			->get();
		
		$this->query->reset();
		
		return $results;
	}
	
	/**
	 * Find all customers with filters
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function find_all( $args = array() ) {
		$defaults = array(
			'page'          => 1,
			'per_page'      => 20,
			'status'        => null,
			'customer_type' => null,
			'search'        => null,
			'order_by'      => 'created_at',
			'order'         => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$query = new Query_Builder( 'customers' );
		
		if ( ! empty( $args['status'] ) ) {
			$query->where( 'status', $args['status'] );
		}
		
		if ( ! empty( $args['customer_type'] ) ) {
			$query->where( 'customer_type', $args['customer_type'] );
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
		
		$query->where_raw(
			$wpdb->prepare(
				'(name LIKE %s OR email LIKE %s OR company LIKE %s OR phone LIKE %s)',
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);
	}
	
	/**
	 * Save new customer
	 *
	 * @param array $data Customer data
	 * @return int|false Customer ID or false on failure
	 */
	public function save( $data ) {
		$clean_data = $this->sanitize_data( $data );
		
		$clean_data['created_by'] = get_current_user_id();
		
		return $this->query->insert( $clean_data );
	}
	
	/**
	 * Update customer
	 *
	 * @param int   $id   Customer ID
	 * @param array $data Customer data
	 * @return int|false Number of rows affected or false
	 */
	public function update( $id, $data ) {
		$clean_data = $this->sanitize_data( $data );
		
		unset( $clean_data['created_by'], $clean_data['created_at'] );
		
		$query = new Query_Builder( 'customers' );
		$result = $query
			->where( 'id', $id )
			->update( $clean_data );
		
		return $result;
	}
	
	/**
	 * Delete customer
	 *
	 * @param int $id Customer ID
	 * @return int|false Number of rows affected or false
	 */
	public function delete( $id ) {
		$query = new Query_Builder( 'customers' );
		return $query
			->where( 'id', $id )
			->delete();
	}
	
	/**
	 * Get customer licenses
	 *
	 * @param int $customer_id Customer ID
	 * @return array
	 */
	public function get_licenses( $customer_id ) {
		global $wpdb;
		
		$licenses_table = $wpdb->prefix . 'synpat_licenses';
		
		$sql = $wpdb->prepare(
			"SELECT * FROM {$licenses_table} WHERE customer_id = %d ORDER BY created_at DESC",
			$customer_id
		);
		
		$results = $wpdb->get_results( $sql );
		
		return $results ? $results : array();
	}
	
	/**
	 * Count customer licenses
	 *
	 * @param int    $customer_id Customer ID
	 * @param string $status      Optional status filter
	 * @return int
	 */
	public function count_licenses( $customer_id, $status = null ) {
		global $wpdb;
		
		$licenses_table = $wpdb->prefix . 'synpat_licenses';
		
		if ( $status ) {
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$licenses_table} WHERE customer_id = %d AND status = %s",
				$customer_id,
				$status
			) );
		} else {
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$licenses_table} WHERE customer_id = %d",
				$customer_id
			) );
		}
		
		return (int) $count;
	}
	
	/**
	 * Get customer statistics
	 *
	 * @param int $customer_id Customer ID
	 * @return array
	 */
	public function get_statistics( $customer_id ) {
		global $wpdb;
		
		$licenses_table = $wpdb->prefix . 'synpat_licenses';
		
		$sql = $wpdb->prepare(
			"SELECT 
				COUNT(*) as total_licenses,
				SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_licenses,
				SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_licenses,
				SUM(license_fee) as total_revenue
			FROM {$licenses_table}
			WHERE customer_id = %d",
			$customer_id
		);
		
		$stats = $wpdb->get_row( $sql, ARRAY_A );
		
		return array(
			'total_licenses'   => (int) $stats['total_licenses'],
			'active_licenses'  => (int) $stats['active_licenses'],
			'expired_licenses' => (int) $stats['expired_licenses'],
			'total_revenue'    => floatval( $stats['total_revenue'] ),
		);
	}
	
	/**
	 * Get customer count by status
	 *
	 * @param string|null $status Customer status (null for all)
	 * @return int
	 */
	public function count_by_status( $status = null ) {
		$query = new Query_Builder( 'customers' );
		
		if ( $status ) {
			$query->where( 'status', $status );
		}
		
		return $query->count();
	}
	
	/**
	 * Sanitize customer data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	private function sanitize_data( $data ) {
		$clean = array();
		
		$text_fields = array( 'name', 'email', 'phone', 'company', 'status', 'customer_type', 'tax_id' );
		
		foreach ( $text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}
		
		if ( isset( $data['email'] ) ) {
			$clean['email'] = sanitize_email( $data['email'] );
		}
		
		$textarea_fields = array( 'address', 'notes' );
		
		foreach ( $textarea_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = sanitize_textarea_field( $data[ $field ] );
			}
		}
		
		$url_fields = array( 'website' );
		
		foreach ( $url_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$clean[ $field ] = esc_url_raw( $data[ $field ] );
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
