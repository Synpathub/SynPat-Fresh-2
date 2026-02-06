<?php
/**
 * PDF Module
 *
 * @package SynPatPlatform\Modules\PDF
 */

namespace SynPat\Modules\PDF;

use SynPat\Modules\PDF\Generators\Docket_PDF;
use SynPat\Modules\PDF\Generators\ClaimChart_PDF;
use SynPat\Modules\PDF\Generators\License_PDF;

/**
 * PDF Module Class
 */
class PDF_Module {

	/**
	 * PDF Service instance
	 *
	 * @var PDF_Service
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
		$docket_generator      = new Docket_PDF();
		$claim_chart_generator = new ClaimChart_PDF();
		$license_generator     = new License_PDF();

		$this->service = new PDF_Service(
			$docket_generator,
			$claim_chart_generator,
			$license_generator
		);
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_synpat_generate_docket_pdf', array( $this, 'ajax_generate_docket' ) );
		add_action( 'wp_ajax_synpat_generate_claim_chart_pdf', array( $this, 'ajax_generate_claim_chart' ) );
		add_action( 'wp_ajax_synpat_generate_license_pdf', array( $this, 'ajax_generate_license' ) );
	}

	/**
	 * AJAX handler for docket PDF generation
	 */
	public function ajax_generate_docket() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$patent_id = absint( $_POST['patent_id'] ?? 0 );

		$result = $this->service->generate_docket( $patent_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'pdf_path' => $result ) );
	}

	/**
	 * AJAX handler for claim chart PDF generation
	 */
	public function ajax_generate_claim_chart() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$patent_id = absint( $_POST['patent_id'] ?? 0 );

		$result = $this->service->generate_claim_chart( $patent_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'pdf_path' => $result ) );
	}

	/**
	 * AJAX handler for license PDF generation
	 */
	public function ajax_generate_license() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$license_id = absint( $_POST['license_id'] ?? 0 );

		$result = $this->service->generate_license( $license_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'pdf_path' => $result ) );
	}

	/**
	 * Get the PDF service instance
	 *
	 * @return PDF_Service
	 */
	public function get_service() {
		return $this->service;
	}
}
