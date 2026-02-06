<?php
/**
 * PDF Service
 *
 * @package SynPatPlatform\Modules\PDF
 */

namespace SynPat\Modules\PDF;

use WP_Error;

/**
 * PDF Service Class
 */
class PDF_Service {

	/**
	 * Docket PDF generator
	 *
	 * @var Generators\Docket_PDF
	 */
	private $docket_generator;

	/**
	 * Claim Chart PDF generator
	 *
	 * @var Generators\ClaimChart_PDF
	 */
	private $claim_chart_generator;

	/**
	 * License PDF generator
	 *
	 * @var Generators\License_PDF
	 */
	private $license_generator;

	/**
	 * Constructor
	 *
	 * @param Generators\Docket_PDF     $docket_generator      Docket PDF generator.
	 * @param Generators\ClaimChart_PDF $claim_chart_generator Claim Chart PDF generator.
	 * @param Generators\License_PDF    $license_generator     License PDF generator.
	 */
	public function __construct( $docket_generator, $claim_chart_generator, $license_generator ) {
		$this->docket_generator      = $docket_generator;
		$this->claim_chart_generator = $claim_chart_generator;
		$this->license_generator     = $license_generator;
	}

	/**
	 * Generate a docket PDF
	 *
	 * @param int   $patent_id Patent ID.
	 * @param array $options   Generation options.
	 * @return string|WP_Error PDF file path or error.
	 */
	public function generate_docket( $patent_id, $options = array() ) {
		if ( ! $patent_id ) {
			return new WP_Error( 'invalid_patent', __( 'Invalid patent ID', 'synpat-platform' ) );
		}

		$patent_data = $this->get_patent_data( $patent_id );

		if ( is_wp_error( $patent_data ) ) {
			return $patent_data;
		}

		try {
			return $this->docket_generator->generate( $patent_data, $options );
		} catch ( \Exception $e ) {
			return new WP_Error( 'pdf_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Generate a claim chart PDF
	 *
	 * @param int   $patent_id Patent ID.
	 * @param array $options   Generation options.
	 * @return string|WP_Error PDF file path or error.
	 */
	public function generate_claim_chart( $patent_id, $options = array() ) {
		if ( ! $patent_id ) {
			return new WP_Error( 'invalid_patent', __( 'Invalid patent ID', 'synpat-platform' ) );
		}

		$patent_data = $this->get_patent_data( $patent_id );

		if ( is_wp_error( $patent_data ) ) {
			return $patent_data;
		}

		try {
			return $this->claim_chart_generator->generate( $patent_data, $options );
		} catch ( \Exception $e ) {
			return new WP_Error( 'pdf_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Generate a license PDF
	 *
	 * @param int   $license_id License ID.
	 * @param array $options    Generation options.
	 * @return string|WP_Error PDF file path or error.
	 */
	public function generate_license( $license_id, $options = array() ) {
		if ( ! $license_id ) {
			return new WP_Error( 'invalid_license', __( 'Invalid license ID', 'synpat-platform' ) );
		}

		$license_data = $this->get_license_data( $license_id );

		if ( is_wp_error( $license_data ) ) {
			return $license_data;
		}

		try {
			return $this->license_generator->generate( $license_data, $options );
		} catch ( \Exception $e ) {
			return new WP_Error( 'pdf_generation_failed', $e->getMessage() );
		}
	}

	/**
	 * Get patent data
	 *
	 * @param int $patent_id Patent ID.
	 * @return array|WP_Error Patent data or error.
	 */
	private function get_patent_data( $patent_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_patents';

		$patent = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$patent_id
			),
			ARRAY_A
		);

		if ( ! $patent ) {
			return new WP_Error( 'patent_not_found', __( 'Patent not found', 'synpat-platform' ) );
		}

		return $patent;
	}

	/**
	 * Get license data
	 *
	 * @param int $license_id License ID.
	 * @return array|WP_Error License data or error.
	 */
	private function get_license_data( $license_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_licenses';

		$license = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$license_id
			),
			ARRAY_A
		);

		if ( ! $license ) {
			return new WP_Error( 'license_not_found', __( 'License not found', 'synpat-platform' ) );
		}

		return $license;
	}
}
