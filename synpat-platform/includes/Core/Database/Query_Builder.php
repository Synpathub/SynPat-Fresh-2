<?php
/**
 * Fluent query builder interface.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core\Database;

/**
 * Query_Builder Class
 */
class Query_Builder {
	
	/**
	 * WordPress database instance
	 *
	 * @var \wpdb
	 */
	private $wpdb;
	
	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table;
	
	/**
	 * SELECT clause
	 *
	 * @var string
	 */
	private $select = '*';
	
	/**
	 * WHERE clauses
	 *
	 * @var array
	 */
	private $where = array();
	
	/**
	 * ORDER BY clause
	 *
	 * @var string
	 */
	private $order_by = '';
	
	/**
	 * LIMIT clause
	 *
	 * @var int
	 */
	private $limit = 0;
	
	/**
	 * OFFSET clause
	 *
	 * @var int
	 */
	private $offset = 0;
	
	/**
	 * Constructor
	 *
	 * @param string $table Table name
	 */
	public function __construct( $table ) {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table = $wpdb->prefix . 'synpat_' . $table;
	}
	
	/**
	 * Set SELECT clause
	 *
	 * @param string $columns Columns to select
	 * @return self
	 */
	public function select( $columns = '*' ) {
		$this->select = $columns;
		return $this;
	}
	
	/**
	 * Add WHERE clause
	 *
	 * @param string $column   Column name
	 * @param mixed  $value    Value to compare
	 * @param string $operator Comparison operator
	 * @return self
	 */
	public function where( $column, $value, $operator = '=' ) {
		$this->where[] = array(
			'column'   => $column,
			'value'    => $value,
			'operator' => $operator,
		);
		return $this;
	}
	
	/**
	 * Add WHERE IN clause
	 *
	 * @param string $column Column name
	 * @param array  $values Values array
	 * @return self
	 */
	public function where_in( $column, $values ) {
		$this->where[] = array(
			'column'   => $column,
			'value'    => $values,
			'operator' => 'IN',
		);
		return $this;
	}
	
	/**
	 * Add ORDER BY clause
	 *
	 * @param string $column    Column name
	 * @param string $direction Direction (ASC or DESC)
	 * @return self
	 */
	public function order_by( $column, $direction = 'ASC' ) {
		$this->order_by = "$column $direction";
		return $this;
	}
	
	/**
	 * Set LIMIT
	 *
	 * @param int $limit Limit value
	 * @return self
	 */
	public function limit( $limit ) {
		$this->limit = absint( $limit );
		return $this;
	}
	
	/**
	 * Set OFFSET
	 *
	 * @param int $offset Offset value
	 * @return self
	 */
	public function offset( $offset ) {
		$this->offset = absint( $offset );
		return $this;
	}
	
	/**
	 * Get results
	 *
	 * @return array
	 */
	public function get() {
		$sql = $this->build_select_query();
		return $this->wpdb->get_results( $sql );
	}
	
	/**
	 * Get single row
	 *
	 * @return object|null
	 */
	public function first() {
		$this->limit( 1 );
		$results = $this->get();
		return ! empty( $results ) ? $results[0] : null;
	}
	
	/**
	 * Count rows
	 *
	 * @return int
	 */
	public function count() {
		$original_select = $this->select;
		$this->select( 'COUNT(*) as count' );
		
		$sql = $this->build_select_query();
		$result = $this->wpdb->get_var( $sql );
		
		$this->select = $original_select;
		
		return absint( $result );
	}
	
	/**
	 * Insert record
	 *
	 * @param array $data Data to insert
	 * @return int|false Insert ID or false
	 */
	public function insert( $data ) {
		$result = $this->wpdb->insert( $this->table, $data );
		return $result ? $this->wpdb->insert_id : false;
	}
	
	/**
	 * Update records
	 *
	 * @param array $data Data to update
	 * @return int|false Number of rows affected or false
	 */
	public function update( $data ) {
		$where_clause = $this->build_where_clause();
		
		if ( empty( $where_clause ) ) {
			return false;
		}
		
		$sql = $this->wpdb->prepare(
			"UPDATE {$this->table} SET " . $this->build_update_set( $data ) . " WHERE " . $where_clause
		);
		
		return $this->wpdb->query( $sql );
	}
	
	/**
	 * Delete records
	 *
	 * @return int|false Number of rows affected or false
	 */
	public function delete() {
		$where_clause = $this->build_where_clause();
		
		if ( empty( $where_clause ) ) {
			return false;
		}
		
		$sql = "DELETE FROM {$this->table} WHERE " . $where_clause;
		return $this->wpdb->query( $sql );
	}
	
	/**
	 * Build SELECT query
	 *
	 * @return string
	 */
	private function build_select_query() {
		$sql = "SELECT {$this->select} FROM {$this->table}";
		
		$where_clause = $this->build_where_clause();
		if ( ! empty( $where_clause ) ) {
			$sql .= " WHERE " . $where_clause;
		}
		
		if ( ! empty( $this->order_by ) ) {
			$sql .= " ORDER BY {$this->order_by}";
		}
		
		if ( $this->limit > 0 ) {
			$sql .= " LIMIT {$this->limit}";
		}
		
		if ( $this->offset > 0 ) {
			$sql .= " OFFSET {$this->offset}";
		}
		
		return $sql;
	}
	
	/**
	 * Build WHERE clause
	 *
	 * @return string
	 */
	private function build_where_clause() {
		if ( empty( $this->where ) ) {
			return '';
		}
		
		$conditions = array();
		
		foreach ( $this->where as $condition ) {
			if ( 'IN' === $condition['operator'] ) {
				$placeholders = implode( ',', array_fill( 0, count( $condition['value'] ), '%s' ) );
				$conditions[] = $this->wpdb->prepare(
					"{$condition['column']} IN ($placeholders)",
					$condition['value']
				);
			} else {
				$conditions[] = $this->wpdb->prepare(
					"{$condition['column']} {$condition['operator']} %s",
					$condition['value']
				);
			}
		}
		
		return implode( ' AND ', $conditions );
	}
	
	/**
	 * Build UPDATE SET clause
	 *
	 * @param array $data Data to update
	 * @return string
	 */
	private function build_update_set( $data ) {
		$sets = array();
		
		foreach ( $data as $column => $value ) {
			$sets[] = $this->wpdb->prepare( "$column = %s", $value );
		}
		
		return implode( ', ', $sets );
	}
	
	/**
	 * Reset query builder
	 *
	 * @return self
	 */
	public function reset() {
		$this->select = '*';
		$this->where = array();
		$this->order_by = '';
		$this->limit = 0;
		$this->offset = 0;
		return $this;
	}
}
