<?php
/**
 * Portfolio module main class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Portfolio;

use SynPat\Modules\Base_Module;
use SynPat\Core\Loader;

/**
 * Portfolio_Module Class
 */
class Portfolio_Module extends Base_Module {
	
	/**
	 * Service instance
	 *
	 * @var Portfolio_Service
	 */
	private $service;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name    = 'portfolio';
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
		$this->loader->add_action( 'wp_ajax_synpat_get_portfolio', $this, 'ajax_get_portfolio' );
		$this->loader->add_action( 'wp_ajax_synpat_add_portfolio_patents', $this, 'ajax_add_patents' );
		$this->loader->add_action( 'wp_ajax_synpat_remove_portfolio_patents', $this, 'ajax_remove_patents' );
		$this->loader->add_action( 'wp_ajax_synpat_calculate_valuation', $this, 'ajax_calculate_valuation' );
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		register_rest_route( 'synpat/v1', '/portfolios', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_portfolios' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_create_portfolio' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/portfolios/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_portfolio' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_update_portfolio' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_portfolio' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/portfolios/(?P<id>\d+)/publish', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_publish_portfolio' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		register_rest_route( 'synpat/v1', '/portfolios/(?P<id>\d+)/patents', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_portfolio_patents' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_add_patents' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_remove_patents' ),
				'permission_callback' => array( $this, 'check_permissions' ),
			),
		) );
		
		register_rest_route( 'synpat/v1', '/portfolios/(?P<id>\d+)/valuation', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_calculate_valuation' ),
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
	 * REST: Get portfolios
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_portfolios( $request ) {
		$args = array(
			'page'        => $request->get_param( 'page' ) ?: 1,
			'per_page'    => $request->get_param( 'per_page' ) ?: 20,
			'status'      => $request->get_param( 'status' ),
			'visibility'  => $request->get_param( 'visibility' ),
			'category_id' => $request->get_param( 'category_id' ),
			'company_id'  => $request->get_param( 'company_id' ),
			'search'      => $request->get_param( 'search' ),
			'order_by'    => $request->get_param( 'order_by' ) ?: 'created_at',
			'order'       => $request->get_param( 'order' ) ?: 'DESC',
		);
		
		$result = $this->get_service()->get_all( $args );
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get single portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_portfolio( $request ) {
		$id = $request->get_param( 'id' );
		$portfolio = $this->get_service()->get( $id );
		
		if ( ! $portfolio ) {
			return new \WP_Error( 'not_found', __( 'Portfolio not found', 'synpat-platform' ), array( 'status' => 404 ) );
		}
		
		return rest_ensure_response( $portfolio );
	}
	
	/**
	 * REST: Create portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_create_portfolio( $request ) {
		$data = $request->get_json_params();
		$result = $this->get_service()->create( $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Update portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_update_portfolio( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$result = $this->get_service()->update( $id, $data );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Delete portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_delete_portfolio( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_service()->delete( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Publish portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_publish_portfolio( $request ) {
		$id = $request->get_param( 'id' );
		$result = $this->get_service()->publish( $id );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * REST: Get portfolio patents
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_get_portfolio_patents( $request ) {
		$id = $request->get_param( 'id' );
		$patents = $this->get_service()->get_patents( $id );
		
		return rest_ensure_response( $patents );
	}
	
	/**
	 * REST: Add patents to portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_add_patents( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$patent_ids = isset( $data['patent_ids'] ) ? $data['patent_ids'] : array();
		
		if ( empty( $patent_ids ) ) {
			return new \WP_Error( 'missing_patents', __( 'Patent IDs are required', 'synpat-platform' ), array( 'status' => 400 ) );
		}
		
		$result = $this->get_service()->add_patents( $id, $patent_ids );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Remove patents from portfolio
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_remove_patents( $request ) {
		$id = $request->get_param( 'id' );
		$data = $request->get_json_params();
		$patent_ids = isset( $data['patent_ids'] ) ? $data['patent_ids'] : array();
		
		if ( empty( $patent_ids ) ) {
			return new \WP_Error( 'missing_patents', __( 'Patent IDs are required', 'synpat-platform' ), array( 'status' => 400 ) );
		}
		
		$result = $this->get_service()->remove_patents( $id, $patent_ids );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array( 'success' => true ) );
	}
	
	/**
	 * REST: Calculate portfolio valuation
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response
	 */
	public function rest_calculate_valuation( $request ) {
		$id = $request->get_param( 'id' );
		$valuation = $this->get_service()->calculate_valuation( $id );
		
		if ( is_wp_error( $valuation ) ) {
			return $valuation;
		}
		
		return rest_ensure_response( array( 'valuation' => $valuation ) );
	}
	
	/**
	 * AJAX: Get portfolio
	 */
	public function ajax_get_portfolio() {
		check_ajax_referer( 'synpat-portfolio', 'nonce' );
		
		$portfolio_id = isset( $_GET['portfolio_id'] ) ? absint( $_GET['portfolio_id'] ) : 0;
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( array( 'message' => 'Invalid portfolio ID' ) );
		}
		
		$portfolio = $this->get_service()->get( $portfolio_id );
		
		if ( ! $portfolio ) {
			wp_send_json_error( array( 'message' => 'Portfolio not found' ) );
		}
		
		wp_send_json_success( $portfolio );
	}
	
	/**
	 * AJAX: Add patents to portfolio
	 */
	public function ajax_add_patents() {
		check_ajax_referer( 'synpat-portfolio', 'nonce' );
		
		$portfolio_id = isset( $_POST['portfolio_id'] ) ? absint( $_POST['portfolio_id'] ) : 0;
		$patent_ids = isset( $_POST['patent_ids'] ) ? array_map( 'absint', $_POST['patent_ids'] ) : array();
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( array( 'message' => 'Invalid portfolio ID' ) );
		}
		
		if ( empty( $patent_ids ) ) {
			wp_send_json_error( array( 'message' => 'No patents provided' ) );
		}
		
		$result = $this->get_service()->add_patents( $portfolio_id, $patent_ids );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array( 'message' => 'Patents added successfully' ) );
	}
	
	/**
	 * AJAX: Remove patents from portfolio
	 */
	public function ajax_remove_patents() {
		check_ajax_referer( 'synpat-portfolio', 'nonce' );
		
		$portfolio_id = isset( $_POST['portfolio_id'] ) ? absint( $_POST['portfolio_id'] ) : 0;
		$patent_ids = isset( $_POST['patent_ids'] ) ? array_map( 'absint', $_POST['patent_ids'] ) : array();
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( array( 'message' => 'Invalid portfolio ID' ) );
		}
		
		if ( empty( $patent_ids ) ) {
			wp_send_json_error( array( 'message' => 'No patents provided' ) );
		}
		
		$result = $this->get_service()->remove_patents( $portfolio_id, $patent_ids );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		
		wp_send_json_success( array( 'message' => 'Patents removed successfully' ) );
	}
	
	/**
	 * AJAX: Calculate valuation
	 */
	public function ajax_calculate_valuation() {
		check_ajax_referer( 'synpat-portfolio', 'nonce' );
		
		$portfolio_id = isset( $_POST['portfolio_id'] ) ? absint( $_POST['portfolio_id'] ) : 0;
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( array( 'message' => 'Invalid portfolio ID' ) );
		}
		
		$valuation = $this->get_service()->calculate_valuation( $portfolio_id );
		
		if ( is_wp_error( $valuation ) ) {
			wp_send_json_error( array( 'message' => $valuation->get_error_message() ) );
		}
		
		wp_send_json_success( array( 'valuation' => $valuation ) );
	}
	
	/**
	 * Get service instance
	 *
	 * @return Portfolio_Service
	 */
	public function get_service() {
		if ( null === $this->service ) {
			$this->service = new Portfolio_Service();
		}
		
		return $this->service;
	}
}
