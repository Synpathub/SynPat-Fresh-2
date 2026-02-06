<?php
/**
 * Patent module main class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Patent;

use SynPat\Modules\Base_Module;
use SynPat\Core\Loader;

/**
 * Patent_Module Class
 */
class Patent_Module extends Base_Module {
	
	/**
	 * Service instance
	 *
	 * @var Patent_Service
	 */
	private $service;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name    = 'patent';
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
		$this->loader->add_action( 'wp_ajax_synpat_search_patents', $this, 'ajax_search_patents' );
		$this->loader->add_action( 'wp_ajax_synpat_import_patent', $this, 'ajax_import_patent' );
		$this->loader->add_action( 'wp_ajax_synpat_get_portfolio_patents', $this, 'ajax_get_portfolio_patents' );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route( 'synpat/v1', '/patents', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_patents' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_patent' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/patents/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_patent' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_patent' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_patent' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/patents/search', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_search_patents' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/patents/import', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_import_patent' ),
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
	 * REST: Get patents
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_patents( $request ) {
		$args = array(
			'page'         => $request->get_param( 'page' ) ?: 1,
			'per_page'     => $request->get_param( 'per_page' ) ?: 20,
			'status'       => $request->get_param( 'status' ),
			'patent_type'  => $request->get_param( 'patent_type' ),
			'country'      => $request->get_param( 'country' ),
			'assignee'     => $request->get_param( 'assignee' ),
			'search'       => $request->get_param( 'search' ),
			'order_by'     => $request->get_param( 'order_by' ) ?: 'filing_date',
			'order'        => $request->get_param( 'order' ) ?: 'DESC',
		);
		
		$result = $this->get_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single patent
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_patent( $request ) {
		$id = $request->get_param( 'id' );
		$patent = $this->get_service()->get( $id );
		
		if ( ! $patent ) {
			return new \WP_Error( 'not_found', __( 'Patent not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $patent );
	}
	
	/**
	 * REST: Create patent
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_patent( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update patent
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_patent( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete patent
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_patent( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Search patents
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_search_patents( $request ) {
		$query = $request->get_param( 'q' ) ?: '';
		$args = array(
			'page'     => $request->get_param( 'page' ) ?: 1,
			'per_page' => $request->get_param( 'per_page' ) ?: 20,
		);
		
		$results = $this->get_service()->search( $query, $args );
		return rest_ensure_response( $results );
	}
	
	/**
	 * REST: Import patent
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_import_patent( $request ) {
		$data = $request->get_json_params();
		$patent_number = isset( $data['patent_number'] ) ? $data['patent_number'] : '';
		$source = isset( $data['source'] ) ? $data['source'] : 'uspto';
		
		if ( empty( $patent_number ) ) {
			return new \WP_Error( 'missing_patent_number', __( 'Patent number is required', 'synpat-platform' ), array( 'status' => 400 ) );
		}
		
		$importer = new Patent_Importer();
		
		if ( 'uspto' === $source ) {
			$result = $importer->import_from_uspto( $patent_number );
		} elseif ( 'google' === $source ) {
			$result = $importer->import_from_google( $patent_number );
		} else {
			return new \WP_Error( 'invalid_source', __( 'Invalid import source', 'synpat-platform' ), array( 'status' => 400 ) );
		}
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * AJAX: Search patents
	 */
	public function ajax_search_patents() {
		check_ajax_referer( 'synpat-search', 'nonce' );
		
		$query = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
		$results = $this->get_service()->search( $query );
		
		wp_send_json_success( $results );
	}
	
	/**
	 * AJAX: Import patent
	 */
	public function ajax_import_patent() {
		check_ajax_referer( 'synpat-import', 'nonce' );
		
		$patent_number = isset( $_POST['patent_number'] ) ? sanitize_text_field( $_POST['patent_number'] ) : '';
		$source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : 'uspto';
		
		if ( empty( $patent_number ) ) {
			wp_send_json_error( array( 'message' => 'Patent number is required' ) );
		}
		
		$importer = new Patent_Importer();
		
		if ( 'uspto' === $source ) {
			$result = $importer->import_from_uspto( $patent_number );
		} else {
			$result = $importer->import_from_google( $patent_number );
		}
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( $result );
	}
	
	/**
	 * AJAX: Get portfolio patents
	 */
	public function ajax_get_portfolio_patents() {
		check_ajax_referer( 'synpat-portfolio', 'nonce' );
		
		$portfolio_id = isset( $_GET['portfolio_id'] ) ? absint( $_GET['portfolio_id'] ) : 0;
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( array( 'message' => 'Invalid portfolio ID' ) );
		}
		
		$patents = $this->get_service()->get_by_portfolio( $portfolio_id );
		
		wp_send_json_success( $patents );
	}
	
	/**
	 * Get service instance
	 *
	 * @return Patent_Service
	 */
	public function get_service() {
		if ( null === $this->service ) {
			$this->service = new Patent_Service();
		}
		
		return $this->service;
	}
}
