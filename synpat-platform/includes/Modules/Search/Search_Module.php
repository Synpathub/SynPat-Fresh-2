<?php
/**
 * Search Module
 *
 * @package SynPatPlatform\Modules\Search
 */

namespace SynPat\Modules\Search;

/**
 * Search Module Class
 */
class Search_Module {

	/**
	 * Search Service instance
	 *
	 * @var Search_Service
	 */
	private $service;

	/**
	 * Initialize the module
	 */
	public function __construct() {
		$this->init_dependencies();
		$this->init_hooks();
	}

	/**
	 * Initialize module dependencies
	 */
	private function init_dependencies() {
		$this->service = new Search_Service();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_synpat_search_all', array( $this, 'ajax_search_all' ) );
		add_action( 'wp_ajax_synpat_search_patents', array( $this, 'ajax_search_patents' ) );
		add_action( 'wp_ajax_synpat_search_portfolios', array( $this, 'ajax_search_portfolios' ) );
		add_action( 'wp_ajax_synpat_save_search', array( $this, 'ajax_save_search' ) );
	}

	/**
	 * AJAX handler for searching all entities
	 */
	public function ajax_search_all() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$args  = array(
			'limit' => absint( $_POST['limit'] ?? 20 ),
		);

		$results = $this->service->search_all( $query, $args );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * AJAX handler for searching patents
	 */
	public function ajax_search_patents() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$args  = array(
			'limit'  => absint( $_POST['limit'] ?? 20 ),
			'status' => sanitize_text_field( $_POST['status'] ?? '' ),
		);

		$results = $this->service->search_patents( $query, $args );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * AJAX handler for searching portfolios
	 */
	public function ajax_search_portfolios() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$args  = array(
			'limit' => absint( $_POST['limit'] ?? 20 ),
		);

		$results = $this->service->search_portfolios( $query, $args );

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * AJAX handler for saving a search
	 */
	public function ajax_save_search() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$name  = sanitize_text_field( $_POST['name'] ?? '' );
		$query = sanitize_text_field( $_POST['query'] ?? '' );
		$type  = sanitize_text_field( $_POST['type'] ?? 'all' );

		$result = $this->service->save_search( $name, $query, $type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'search_id' => $result ) );
	}

	/**
	 * Get the search service instance
	 *
	 * @return Search_Service
	 */
	public function get_service() {
		return $this->service;
	}
}
