<?php
/**
 * Analytics Module
 *
 * @package SynPatPlatform\Modules\Analytics
 */

namespace SynPat\Modules\Analytics;

/**
 * Analytics Module Class
 */
class Analytics_Module {

	/**
	 * Analytics Service instance
	 *
	 * @var Analytics_Service
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
		$this->service = new Analytics_Service();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_synpat_get_dashboard_stats', array( $this, 'ajax_get_dashboard_stats' ) );
		add_action( 'wp_ajax_synpat_get_portfolio_analytics', array( $this, 'ajax_get_portfolio_analytics' ) );
		add_action( 'wp_ajax_synpat_get_patent_analytics', array( $this, 'ajax_get_patent_analytics' ) );
	}

	/**
	 * AJAX handler for getting dashboard statistics
	 */
	public function ajax_get_dashboard_stats() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$stats = $this->service->get_dashboard_stats();

		wp_send_json_success( array( 'stats' => $stats ) );
	}

	/**
	 * AJAX handler for getting portfolio analytics
	 */
	public function ajax_get_portfolio_analytics() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$portfolio_id = absint( $_POST['portfolio_id'] ?? 0 );

		$analytics = $this->service->get_portfolio_analytics( $portfolio_id );

		if ( is_wp_error( $analytics ) ) {
			wp_send_json_error( array( 'message' => $analytics->get_error_message() ) );
		}

		wp_send_json_success( array( 'analytics' => $analytics ) );
	}

	/**
	 * AJAX handler for getting patent analytics
	 */
	public function ajax_get_patent_analytics() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$patent_id = absint( $_POST['patent_id'] ?? 0 );

		$analytics = $this->service->get_patent_analytics( $patent_id );

		if ( is_wp_error( $analytics ) ) {
			wp_send_json_error( array( 'message' => $analytics->get_error_message() ) );
		}

		wp_send_json_success( array( 'analytics' => $analytics ) );
	}

	/**
	 * Get the analytics service instance
	 *
	 * @return Analytics_Service
	 */
	public function get_service() {
		return $this->service;
	}
}
