<?php
/**
 * DueDiligence module main class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\DueDiligence;

use SynPat\Modules\Base_Module;
use SynPat\Core\Loader;

/**
 * DueDiligence_Module Class
 */
class DueDiligence_Module extends Base_Module {
	
	/**
	 * Service instance
	 *
	 * @var DueDiligence_Service
	 */
	private $service;
	
	/**
	 * Claim chart service instance
	 *
	 * @var ClaimChart_Service
	 */
	private $claim_chart_service;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name    = 'due_diligence';
		$this->version = '1.0.0';
	}
	
	/**
	 * Initialize the module
	 *
	 * @param Loader $loader Loader instance
	 */
	public function init( Loader $loader ) {
		$this->loader = $loader;
		$this->register_hooks();
	}
	
	/**
	 * Register hooks
	 */
	protected function register_hooks() {
		// Register REST API endpoints
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );
		
		// Register AJAX handlers
		$this->loader->add_action( 'wp_ajax_synpat_get_due_diligences', $this, 'ajax_get_due_diligences' );
		$this->loader->add_action( 'wp_ajax_synpat_assign_due_diligence', $this, 'ajax_assign_due_diligence' );
		$this->loader->add_action( 'wp_ajax_synpat_update_dd_status', $this, 'ajax_update_status' );
		$this->loader->add_action( 'wp_ajax_synpat_get_claim_charts', $this, 'ajax_get_claim_charts' );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		// Due Diligence routes
		register_rest_route( 'synpat/v1', '/due-diligence', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_due_diligences' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_due_diligence' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/due-diligence/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_due_diligence' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_due_diligence' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_due_diligence' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/due-diligence/(?P<id>\d+)/assign', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_assign_due_diligence' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/due-diligence/(?P<id>\d+)/complete', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_complete_due_diligence' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		// Claim Chart routes
		register_rest_route( 'synpat/v1', '/claim-charts', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_claim_charts' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_claim_chart' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/claim-charts/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_claim_chart' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_claim_chart' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_claim_chart' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/due-diligence/(?P<id>\d+)/claim-charts', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_due_diligence_claim_charts' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
	}
	
	/**
	 * Check REST API permissions
	 *
	 * @return bool
	 */
	public function check_permissions() {
		return current_user_can( 'manage_options' );
	}
	
	/**
	 * REST: Get due diligences
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_due_diligences( $request ) {
		$args = array(
			'page'         => $request->get_param( 'page' ) ?: 1,
			'per_page'     => $request->get_param( 'per_page' ) ?: 20,
			'status'       => $request->get_param( 'status' ),
			'assigned_to'  => $request->get_param( 'assigned_to' ),
			'patent_id'    => $request->get_param( 'patent_id' ),
			'portfolio_id' => $request->get_param( 'portfolio_id' ),
			'search'       => $request->get_param( 'search' ),
			'order_by'     => $request->get_param( 'order_by' ) ?: 'created_at',
			'order'        => $request->get_param( 'order' ) ?: 'DESC',
		);
		
		$result = $this->get_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_due_diligence( $request ) {
		$id = $request->get_param( 'id' );
		$due_diligence = $this->get_service()->get( $id );
		
		if ( ! $due_diligence ) {
			return new \WP_Error( 'not_found', __( 'Due diligence not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $due_diligence );
	}
	
	/**
	 * REST: Create due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_due_diligence( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_due_diligence( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_due_diligence( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Assign due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_assign_due_diligence( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$user_id = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : 0;
		
		if ( ! $user_id ) {
			return new \WP_Error( 'missing_user_id', __( 'User ID is required', 'synpat-platform' ), array( 'status' => 400 ) );
		}
		
		$result = $this->get_service()->assign( $id, $user_id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Complete due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_complete_due_diligence( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_service()->complete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get claim charts
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_claim_charts( $request ) {
		$args = array(
			'page'              => $request->get_param( 'page' ) ?: 1,
			'per_page'          => $request->get_param( 'per_page' ) ?: 20,
			'due_diligence_id'  => $request->get_param( 'due_diligence_id' ),
			'patent_id'         => $request->get_param( 'patent_id' ),
			'search'            => $request->get_param( 'search' ),
			'order_by'          => $request->get_param( 'order_by' ) ?: 'created_at',
			'order'             => $request->get_param( 'order' ) ?: 'DESC',
		);
		
		$result = $this->get_claim_chart_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single claim chart
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_claim_chart( $request ) {
		$id = $request->get_param( 'id' );
		$claim_chart = $this->get_claim_chart_service()->get( $id );
		
		if ( ! $claim_chart ) {
			return new \WP_Error( 'not_found', __( 'Claim chart not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $claim_chart );
	}
	
	/**
	 * REST: Create claim chart
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_claim_chart( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_claim_chart_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update claim chart
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_claim_chart( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_claim_chart_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete claim chart
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_claim_chart( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_claim_chart_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Get claim charts by due diligence
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_due_diligence_claim_charts( $request ) {
		$id = $request->get_param( 'id' );
		$claim_charts = $this->get_claim_chart_service()->get_by_due_diligence( $id );
		
		return rest_ensure_response( $claim_charts );
	}
	
	/**
	 * AJAX: Get due diligences
	 */
	public function ajax_get_due_diligences() {
		check_ajax_referer( 'synpat-due-diligence', 'nonce' );
		
		$args = array(
			'page'         => isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1,
			'per_page'     => isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 20,
			'status'       => isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : null,
			'assigned_to'  => isset( $_GET['assigned_to'] ) ? absint( $_GET['assigned_to'] ) : null,
		);
		
		$result = $this->get_service()->get_all( $args );
		wp_send_json_success( $result );
	}
	
	/**
	 * AJAX: Assign due diligence
	 */
	public function ajax_assign_due_diligence() {
		check_ajax_referer( 'synpat-due-diligence', 'nonce' );
		
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		
		if ( ! $id || ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'synpat-platform' ) ) );
		}
		
		$result = $this->get_service()->assign( $id, $user_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( $result );
	}
	
	/**
	 * AJAX: Update status
	 */
	public function ajax_update_status() {
		check_ajax_referer( 'synpat-due-diligence', 'nonce' );
		
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		
		if ( ! $id || ! $status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'synpat-platform' ) ) );
		}
		
		$result = $this->get_service()->update( $id, array( 'status' => $status ) );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( $result );
	}
	
	/**
	 * AJAX: Get claim charts
	 */
	public function ajax_get_claim_charts() {
		check_ajax_referer( 'synpat-claim-charts', 'nonce' );
		
		$due_diligence_id = isset( $_GET['due_diligence_id'] ) ? absint( $_GET['due_diligence_id'] ) : 0;
		
		if ( ! $due_diligence_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid due diligence ID', 'synpat-platform' ) ) );
		}
		
		$claim_charts = $this->get_claim_chart_service()->get_by_due_diligence( $due_diligence_id );
		
		wp_send_json_success( $claim_charts );
	}
	
	/**
	 * Get service instance
	 *
	 * @return DueDiligence_Service
	 */
	public function get_service() {
		if ( null === $this->service ) {
			$this->service = new DueDiligence_Service();
		}
		
		return $this->service;
	}
	
	/**
	 * Get claim chart service instance
	 *
	 * @return ClaimChart_Service
	 */
	public function get_claim_chart_service() {
		if ( null === $this->claim_chart_service ) {
			$this->claim_chart_service = new ClaimChart_Service();
		}
		
		return $this->claim_chart_service;
	}
}
