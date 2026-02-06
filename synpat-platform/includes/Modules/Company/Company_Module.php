<?php
/**
 * Company module main class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Company;

use SynPat\Modules\Base_Module;
use SynPat\Core\Loader;

/**
 * Company_Module Class
 */
class Company_Module extends Base_Module {
	
	/**
	 * Service instance
	 *
	 * @var Company_Service
	 */
	private $service;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name    = 'company';
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
		$this->loader->add_action( 'wp_ajax_synpat_search_companies', $this, 'ajax_search_companies' );
		$this->loader->add_action( 'wp_ajax_synpat_get_company_contacts', $this, 'ajax_get_company_contacts' );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route( 'synpat/v1', '/companies', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_companies' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_company' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/companies/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_company' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_company' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_company' ),
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
	 * REST: Get companies
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_companies( $request ) {
		$args = array(
			'page'     => $request->get_param( 'page' ) ?: 1,
			'per_page' => $request->get_param( 'per_page' ) ?: 20,
			'type'     => $request->get_param( 'type' ),
			'industry' => $request->get_param( 'industry' ),
			'search'   => $request->get_param( 'search' ),
		);
		
		$result = $this->get_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single company
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_company( $request ) {
		$id = $request->get_param( 'id' );
		$company = $this->get_service()->get( $id );
		
		if ( ! $company ) {
			return new \WP_Error( 'not_found', __( 'Company not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $company );
	}
	
	/**
	 * REST: Create company
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_company( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update company
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_company( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete company
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_company( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * AJAX: Search companies
	 */
	public function ajax_search_companies() {
		check_ajax_referer( 'synpat-search', 'nonce' );
		
		$query = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
		$results = $this->get_service()->search( $query );
		
		wp_send_json_success( $results );
	}
	
	/**
	 * AJAX: Get company contacts
	 */
	public function ajax_get_company_contacts() {
		check_ajax_referer( 'synpat-contacts', 'nonce' );
		
		$company_id = isset( $_GET['company_id'] ) ? absint( $_GET['company_id'] ) : 0;
		
		if ( ! $company_id ) {
			wp_send_json_error( array( 'message' => 'Invalid company ID' ) );
		}
		
		$contact_service = new Contact_Service();
		$contacts = $contact_service->get_by_company( $company_id );
		
		wp_send_json_success( $contacts );
	}
	
	/**
	 * Get service instance
	 *
	 * @return Company_Service
	 */
	public function get_service() {
		if ( null === $this->service ) {
			$this->service = new Company_Service();
		}
		
		return $this->service;
	}
}
