<?php
/**
 * Search Service
 *
 * @package SynPatPlatform\Modules\Search
 */

namespace SynPat\Modules\Search;

use WP_Error;

/**
 * Search Service Class
 */
class Search_Service {

	/**
	 * Search across all entities
	 *
	 * @param string $query Search query.
	 * @param array  $args  Search arguments.
	 * @return array Search results.
	 */
	public function search_all( $query, $args = array() ) {
		$defaults = array(
			'limit' => 20,
		);

		$args = wp_parse_args( $args, $defaults );

		$patents    = $this->search_patents( $query, array( 'limit' => $args['limit'] / 2 ) );
		$portfolios = $this->search_portfolios( $query, array( 'limit' => $args['limit'] / 2 ) );

		return array(
			'patents'    => $patents,
			'portfolios' => $portfolios,
			'total'      => count( $patents ) + count( $portfolios ),
		);
	}

	/**
	 * Search patents
	 *
	 * @param string $query Search query.
	 * @param array  $args  Search arguments.
	 * @return array Patent results.
	 */
	public function search_patents( $query, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'  => 20,
			'offset' => 0,
			'status' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'synpat_patents';
		$where      = array( '1=1' );
		$query_vars = array();

		if ( ! empty( $query ) ) {
			$where[]      = '(patent_number LIKE %s OR title LIKE %s OR abstract LIKE %s)';
			$like_query   = '%' . $wpdb->esc_like( $query ) . '%';
			$query_vars[] = $like_query;
			$query_vars[] = $like_query;
			$query_vars[] = $like_query;
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]      = 'status = %s';
			$query_vars[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );
		$query_vars[] = $args['limit'];
		$query_vars[] = $args['offset'];

		$sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";

		if ( ! empty( $query_vars ) ) {
			$sql = $wpdb->prepare( $sql, $query_vars );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Search portfolios
	 *
	 * @param string $query Search query.
	 * @param array  $args  Search arguments.
	 * @return array Portfolio results.
	 */
	public function search_portfolios( $query, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'  => 20,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'synpat_portfolios';
		$where      = array( '1=1' );
		$query_vars = array();

		if ( ! empty( $query ) ) {
			$where[]      = '(name LIKE %s OR description LIKE %s)';
			$like_query   = '%' . $wpdb->esc_like( $query ) . '%';
			$query_vars[] = $like_query;
			$query_vars[] = $like_query;
		}

		$where_clause = implode( ' AND ', $where );
		$query_vars[] = $args['limit'];
		$query_vars[] = $args['offset'];

		$sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";

		if ( ! empty( $query_vars ) ) {
			$sql = $wpdb->prepare( $sql, $query_vars );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Save a search for later use
	 *
	 * @param string $name  Search name.
	 * @param string $query Search query.
	 * @param string $type  Search type (all, patents, portfolios).
	 * @return int|WP_Error Search ID or error.
	 */
	public function save_search( $name, $query, $type = 'all' ) {
		global $wpdb;

		if ( empty( $name ) || empty( $query ) ) {
			return new WP_Error( 'invalid_search', __( 'Search name and query are required', 'synpat-platform' ) );
		}

		$table_name = $wpdb->prefix . 'synpat_saved_searches';

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name'       => $name,
				'query'      => $query,
				'type'       => $type,
				'user_id'    => get_current_user_id(),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', __( 'Failed to save search', 'synpat-platform' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get saved searches for current user
	 *
	 * @return array Saved searches.
	 */
	public function get_saved_searches() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_saved_searches';

		$searches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY created_at DESC",
				get_current_user_id()
			),
			ARRAY_A
		);

		return $searches ?: array();
	}

	/**
	 * Delete a saved search
	 *
	 * @param int $search_id Search ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_saved_search( $search_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_saved_searches';

		$deleted = $wpdb->delete(
			$table_name,
			array(
				'id'      => $search_id,
				'user_id' => get_current_user_id(),
			),
			array( '%d', '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'db_error', __( 'Failed to delete search', 'synpat-platform' ) );
		}

		return true;
	}
}
