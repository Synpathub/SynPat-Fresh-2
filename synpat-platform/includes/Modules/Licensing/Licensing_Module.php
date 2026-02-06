<?php
/**
 * Licensing module main class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Licensing;

use SynPat\Modules\Base_Module;
use SynPat\Core\Loader;

/**
 * Licensing_Module Class
 */
class Licensing_Module extends Base_Module {
	
	/**
	 * License service instance
	 *
	 * @var License_Service
	 */
	private $license_service;
	
	/**
	 * Customer service instance
	 *
	 * @var Customer_Service
	 */
	private $customer_service;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name    = 'licensing';
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
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );
		
		$this->loader->add_action( 'wp_ajax_synpat_get_license', $this, 'ajax_get_license' );
		$this->loader->add_action( 'wp_ajax_synpat_activate_license', $this, 'ajax_activate_license' );
		$this->loader->add_action( 'wp_ajax_synpat_deactivate_license', $this, 'ajax_deactivate_license' );
		$this->loader->add_action( 'wp_ajax_synpat_get_customer', $this, 'ajax_get_customer' );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route( 'synpat/v1', '/licenses', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_licenses' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_license' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/licenses/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_license' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_license' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_license' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/licenses/(?P<id>\d+)/activate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_activate_license' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/licenses/(?P<id>\d+)/deactivate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_deactivate_license' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/licenses/(?P<id>\d+)/suspend', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_suspend_license' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/licenses/(?P<id>\d+)/renew', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_renew_license' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/customers', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_customers' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_customer' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/customers/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_customer' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_customer' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_customer' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
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
	 * REST: Get licenses
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_licenses( $request ) {
		$args = array(
			'page'        => $request->get_param( 'page' ) ?: 1,
			'per_page'    => $request->get_param( 'per_page' ) ?: 20,
			'status'      => $request->get_param( 'status' ),
			'customer_id' => $request->get_param( 'customer_id' ),
			'patent_id'   => $request->get_param( 'patent_id' ),
			'search'      => $request->get_param( 'search' ),
			'order_by'    => $request->get_param( 'order_by' ) ?: 'created_at',
			'order'       => $request->get_param( 'order' ) ?: 'DESC',
		);
		
		$result = $this->get_license_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_license( $request ) {
		$id = $request->get_param( 'id' );
		$license = $this->get_license_service()->get( $id );
		
		if ( ! $license ) {
			return new \WP_Error( 'not_found', __( 'License not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $license );
	}
	
	/**
	 * REST: Create license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_license( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_license_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_license( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_license_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_license( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_license_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Activate license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_activate_license( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_license_service()->activate( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Deactivate license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_deactivate_license( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_license_service()->deactivate( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Suspend license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_suspend_license( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_license_service()->suspend( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Renew license
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_renew_license( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_license_service()->renew( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get customers
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_customers( $request ) {
		$args = array(
			'page'     => $request->get_param( 'page' ) ?: 1,
			'per_page' => $request->get_param( 'per_page' ) ?: 20,
			'search'   => $request->get_param( 'search' ),
			'order_by' => $request->get_param( 'order_by' ) ?: 'created_at',
			'order'    => $request->get_param( 'order' ) ?: 'DESC',
		);
		
		$result = $this->get_customer_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single customer
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_customer( $request ) {
		$id = $request->get_param( 'id' );
		$customer = $this->get_customer_service()->get( $id );
		
		if ( ! $customer ) {
			return new \WP_Error( 'not_found', __( 'Customer not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $customer );
	}
	
	/**
	 * REST: Create customer
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_customer( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_customer_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update customer
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_customer( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_customer_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete customer
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_customer( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_customer_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * AJAX: Get license
	 */
	public function ajax_get_license() {
		check_ajax_referer( 'synpat-licensing', 'nonce' );
		
		$license_id = isset( $_GET['license_id'] ) ? absint( $_GET['license_id'] ) : 0;
		
		if ( ! $license_id ) {
			wp_send_json_error( array( 'message' => 'Invalid license ID' ) );
		}
		
		$license = $this->get_license_service()->get( $license_id );
		
		if ( ! $license ) {
			wp_send_json_error( array( 'message' => 'License not found' ) );
		}
		
		wp_send_json_success( $license );
	}
	
	/**
	 * AJAX: Activate license
	 */
	public function ajax_activate_license() {
		check_ajax_referer( 'synpat-licensing', 'nonce' );
		
		$license_id = isset( $_POST['license_id'] ) ? absint( $_POST['license_id'] ) : 0;
		
		if ( ! $license_id ) {
			wp_send_json_error( array( 'message' => 'Invalid license ID' ) );
		}
		
		$result = $this->get_license_service()->activate( $license_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array( 'message' => 'License activated successfully' ) );
	}
	
	/**
	 * AJAX: Deactivate license
	 */
	public function ajax_deactivate_license() {
		check_ajax_referer( 'synpat-licensing', 'nonce' );
		
		$license_id = isset( $_POST['license_id'] ) ? absint( $_POST['license_id'] ) : 0;
		
		if ( ! $license_id ) {
			wp_send_json_error( array( 'message' => 'Invalid license ID' ) );
		}
		
		$result = $this->get_license_service()->deactivate( $license_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array( 'message' => 'License deactivated successfully' ) );
	}
	
	/**
	 * AJAX: Get customer
	 */
	public function ajax_get_customer() {
		check_ajax_referer( 'synpat-licensing', 'nonce' );
		
		$customer_id = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;
		
		if ( ! $customer_id ) {
			wp_send_json_error( array( 'message' => 'Invalid customer ID' ) );
		}
		
		$customer = $this->get_customer_service()->get( $customer_id );
		
		if ( ! $customer ) {
			wp_send_json_error( array( 'message' => 'Customer not found' ) );
		}
		
		wp_send_json_success( $customer );
	}
	
	/**
	 * Get license service instance
	 *
	 * @return License_Service
	 */
	public function get_license_service() {
		if ( null === $this->license_service ) {
			$this->license_service = new License_Service();
		}
		
		return $this->license_service;
	}
	
	/**
	 * Get customer service instance
	 *
	 * @return Customer_Service
	 */
	public function get_customer_service() {
		if ( null === $this->customer_service ) {
			$this->customer_service = new Customer_Service();
		}
		
		return $this->customer_service;
	}
	
	/**
	 * Get service instance (required by Base_Module)
	 *
	 * @return License_Service
	 */
	public function get_service() {
		return $this->get_license_service();
	}
}
