<?php
/**
 * Patent importer for USPTO and Google Patents.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Patent;

use WP_Error;

/**
 * Patent_Importer Class
 */
class Patent_Importer {
	
	/**
	 * Patent service instance
	 *
	 * @var Patent_Service
	 */
	private $service;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new Patent_Service();
	}
	
	/**
	 * Import patent from USPTO
	 *
	 * @param string $patent_number Patent number
	 * @return Patent_Entity|WP_Error
	 */
	public function import_from_uspto( $patent_number ) {
		// TODO: Implement USPTO API integration
		// This is a placeholder implementation
		
		// Sanitize patent number
		$patent_number = $this->sanitize_patent_number( $patent_number );
		
		if ( empty( $patent_number ) ) {
			return new WP_Error(
				'invalid_patent_number',
				__( 'Invalid patent number provided.', 'synpat-platform' )
			);
		}
		
		// Check if patent already exists
		$repository = new Patent_Repository();
		$existing = $repository->find_by_number( $patent_number, 'US' );
		
		if ( $existing ) {
			return new WP_Error(
				'patent_exists',
				__( 'This patent has already been imported.', 'synpat-platform' )
			);
		}
		
		// TODO: Make API call to USPTO
		// For now, return placeholder data
		$raw_data = $this->fetch_from_uspto_api( $patent_number );
		
		if ( is_wp_error( $raw_data ) ) {
			return $raw_data;
		}
		
		// Parse the data
		$patent_data = $this->parse_patent_data( $raw_data, 'uspto' );
		
		if ( is_wp_error( $patent_data ) ) {
			return $patent_data;
		}
		
		// Create patent
		$patent = $this->service->create( $patent_data );
		
		return $patent;
	}
	
	/**
	 * Import patent from Google Patents
	 *
	 * @param string $patent_number Patent number
	 * @return Patent_Entity|WP_Error
	 */
	public function import_from_google( $patent_number ) {
		// TODO: Implement Google Patents scraping/API integration
		// This is a placeholder implementation
		
		// Sanitize patent number
		$patent_number = $this->sanitize_patent_number( $patent_number );
		
		if ( empty( $patent_number ) ) {
			return new WP_Error(
				'invalid_patent_number',
				__( 'Invalid patent number provided.', 'synpat-platform' )
			);
		}
		
		// Check if patent already exists
		$repository = new Patent_Repository();
		$existing = $repository->find_by_number( $patent_number, 'US' );
		
		if ( $existing ) {
			return new WP_Error(
				'patent_exists',
				__( 'This patent has already been imported.', 'synpat-platform' )
			);
		}
		
		// TODO: Fetch data from Google Patents
		// For now, return placeholder data
		$raw_data = $this->fetch_from_google_api( $patent_number );
		
		if ( is_wp_error( $raw_data ) ) {
			return $raw_data;
		}
		
		// Parse the data
		$patent_data = $this->parse_patent_data( $raw_data, 'google' );
		
		if ( is_wp_error( $patent_data ) ) {
			return $patent_data;
		}
		
		// Create patent
		$patent = $this->service->create( $patent_data );
		
		return $patent;
	}
	
	/**
	 * Parse and normalize patent data
	 *
	 * @param array  $raw_data Raw patent data
	 * @param string $source   Data source (uspto, google)
	 * @return array|WP_Error Normalized patent data
	 */
	public function parse_patent_data( $raw_data, $source = 'uspto' ) {
		// TODO: Implement data parsing based on source
		// This is a placeholder implementation
		
		if ( empty( $raw_data ) ) {
			return new WP_Error(
				'invalid_data',
				__( 'Invalid patent data received.', 'synpat-platform' )
			);
		}
		
		// Basic data structure (to be customized based on actual API response)
		$parsed_data = array(
			'patent_number'      => isset( $raw_data['patent_number'] ) ? $raw_data['patent_number'] : '',
			'application_number' => isset( $raw_data['application_number'] ) ? $raw_data['application_number'] : '',
			'title'              => isset( $raw_data['title'] ) ? $raw_data['title'] : '',
			'abstract'           => isset( $raw_data['abstract'] ) ? $raw_data['abstract'] : '',
			'patent_type'        => isset( $raw_data['type'] ) ? $this->normalize_patent_type( $raw_data['type'] ) : 'utility',
			'status'             => isset( $raw_data['status'] ) ? $this->normalize_status( $raw_data['status'] ) : 'active',
			'country'            => isset( $raw_data['country'] ) ? $raw_data['country'] : 'US',
			'filing_date'        => isset( $raw_data['filing_date'] ) ? $this->normalize_date( $raw_data['filing_date'] ) : null,
			'publication_date'   => isset( $raw_data['publication_date'] ) ? $this->normalize_date( $raw_data['publication_date'] ) : null,
			'grant_date'         => isset( $raw_data['grant_date'] ) ? $this->normalize_date( $raw_data['grant_date'] ) : null,
			'expiration_date'    => isset( $raw_data['expiration_date'] ) ? $this->normalize_date( $raw_data['expiration_date'] ) : $this->calculate_expiration_date( $raw_data ),
			'assignee'           => isset( $raw_data['assignee'] ) ? $raw_data['assignee'] : '',
			'inventors'          => isset( $raw_data['inventors'] ) ? $this->normalize_inventors( $raw_data['inventors'] ) : '',
			'claims_count'       => isset( $raw_data['claims_count'] ) ? absint( $raw_data['claims_count'] ) : 0,
			'independent_claims' => isset( $raw_data['independent_claims'] ) ? absint( $raw_data['independent_claims'] ) : 0,
			'priority_date'      => isset( $raw_data['priority_date'] ) ? $this->normalize_date( $raw_data['priority_date'] ) : null,
			'patent_family_id'   => isset( $raw_data['family_id'] ) ? $raw_data['family_id'] : '',
			'ipc_classification' => isset( $raw_data['ipc'] ) ? $this->normalize_classifications( $raw_data['ipc'] ) : '',
			'cpc_classification' => isset( $raw_data['cpc'] ) ? $this->normalize_classifications( $raw_data['cpc'] ) : '',
			'us_classification'  => isset( $raw_data['uspc'] ) ? $this->normalize_classifications( $raw_data['uspc'] ) : '',
			'pdf_url'            => isset( $raw_data['pdf_url'] ) ? $raw_data['pdf_url'] : '',
			'source'             => $source,
			'source_id'          => isset( $raw_data['id'] ) ? $raw_data['id'] : '',
			'meta_data'          => isset( $raw_data['meta'] ) ? $raw_data['meta'] : array(),
		);
		
		return $parsed_data;
	}
	
	/**
	 * Fetch patent data from USPTO API
	 *
	 * @param string $patent_number Patent number
	 * @return array|WP_Error Raw patent data
	 */
	private function fetch_from_uspto_api( $patent_number ) {
		// TODO: Implement actual USPTO API call
		// USPTO Patent Examination Data System (PEDS) API
		// or PatentsView API: https://patentsview.org/apis/api-endpoints
		
		// Placeholder implementation
		return new WP_Error(
			'not_implemented',
			__( 'USPTO API integration is not yet implemented. Please add patent data manually.', 'synpat-platform' )
		);
		
		/* Example implementation structure:
		$api_url = 'https://api.patentsview.org/patents/query';
		
		$query = array(
			'q' => array( 'patent_number' => $patent_number ),
			'f' => array(
				'patent_number', 'patent_title', 'patent_abstract',
				'patent_date', 'app_date', 'assignee_organization',
				'inventor_last_name', 'inventor_first_name'
			)
		);
		
		$response = wp_remote_post( $api_url, array(
			'body' => wp_json_encode( $query ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 30,
		) );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( empty( $data['patents'][0] ) ) {
			return new WP_Error(
				'patent_not_found',
				__( 'Patent not found in USPTO database.', 'synpat-platform' )
			);
		}
		
		return $this->transform_uspto_response( $data['patents'][0] );
		*/
	}
	
	/**
	 * Fetch patent data from Google Patents
	 *
	 * @param string $patent_number Patent number
	 * @return array|WP_Error Raw patent data
	 */
	private function fetch_from_google_api( $patent_number ) {
		// TODO: Implement Google Patents scraping or API
		// Google Patents doesn't have a public API, so this would require web scraping
		// Consider using a third-party service or official API if available
		
		// Placeholder implementation
		return new WP_Error(
			'not_implemented',
			__( 'Google Patents import is not yet implemented. Please add patent data manually.', 'synpat-platform' )
		);
		
		/* Example implementation structure:
		$url = 'https://patents.google.com/patent/US' . $patent_number;
		
		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		$body = wp_remote_retrieve_body( $response );
		
		// Parse HTML using DOMDocument or similar
		return $this->parse_google_html( $body );
		*/
	}
	
	/**
	 * Sanitize patent number
	 *
	 * @param string $patent_number Raw patent number
	 * @return string Sanitized patent number
	 */
	private function sanitize_patent_number( $patent_number ) {
		// Remove common prefixes and formatting
		$patent_number = strtoupper( trim( $patent_number ) );
		$patent_number = str_replace( array( 'US', 'US-', 'USPTO-' ), '', $patent_number );
		$patent_number = preg_replace( '/[^0-9A-Z]/', '', $patent_number );
		
		return $patent_number;
	}
	
	/**
	 * Normalize patent type
	 *
	 * @param string $type Raw patent type
	 * @return string Normalized type
	 */
	private function normalize_patent_type( $type ) {
		$type_map = array(
			'utility'              => 'utility',
			'design'               => 'design',
			'plant'                => 'plant',
			'reissue'              => 'reissue',
			'statutory invention'  => 'statutory_invention',
			'provisional'          => 'provisional',
		);
		
		$type = strtolower( trim( $type ) );
		
		return isset( $type_map[ $type ] ) ? $type_map[ $type ] : 'utility';
	}
	
	/**
	 * Normalize patent status
	 *
	 * @param string $status Raw status
	 * @return string Normalized status
	 */
	private function normalize_status( $status ) {
		$status_map = array(
			'pending'   => 'pending',
			'active'    => 'active',
			'granted'   => 'active',
			'expired'   => 'expired',
			'abandoned' => 'abandoned',
			'revoked'   => 'revoked',
		);
		
		$status = strtolower( trim( $status ) );
		
		return isset( $status_map[ $status ] ) ? $status_map[ $status ] : 'active';
	}
	
	/**
	 * Normalize date format
	 *
	 * @param string $date Raw date
	 * @return string|null Normalized date (Y-m-d)
	 */
	private function normalize_date( $date ) {
		if ( empty( $date ) ) {
			return null;
		}
		
		$timestamp = strtotime( $date );
		
		if ( ! $timestamp ) {
			return null;
		}
		
		return date( 'Y-m-d', $timestamp );
	}
	
	/**
	 * Normalize inventors list
	 *
	 * @param mixed $inventors Raw inventors data
	 * @return string Semicolon-separated inventor names
	 */
	private function normalize_inventors( $inventors ) {
		if ( is_array( $inventors ) ) {
			return implode( '; ', array_map( 'trim', $inventors ) );
		}
		
		return sanitize_textarea_field( $inventors );
	}
	
	/**
	 * Normalize classification codes
	 *
	 * @param mixed $classifications Raw classification data
	 * @return string Semicolon-separated classification codes
	 */
	private function normalize_classifications( $classifications ) {
		if ( is_array( $classifications ) ) {
			return implode( '; ', array_map( 'trim', $classifications ) );
		}
		
		return sanitize_textarea_field( $classifications );
	}
	
	/**
	 * Calculate expiration date
	 *
	 * @param array $data Patent data
	 * @return string|null Expiration date
	 */
	private function calculate_expiration_date( $data ) {
		// US utility patents typically expire 20 years from filing date
		if ( empty( $data['filing_date'] ) ) {
			return null;
		}
		
		$filing_timestamp = strtotime( $data['filing_date'] );
		
		if ( ! $filing_timestamp ) {
			return null;
		}
		
		// Add 20 years for utility patents
		$patent_type = isset( $data['type'] ) ? $this->normalize_patent_type( $data['type'] ) : 'utility';
		
		if ( 'utility' === $patent_type ) {
			$years = 20;
		} elseif ( 'design' === $patent_type ) {
			// Design patents expire 15 years from grant date (changed from 14 in 2015)
			if ( ! empty( $data['grant_date'] ) ) {
				$filing_timestamp = strtotime( $data['grant_date'] );
				$years = 15;
			} else {
				return null;
			}
		} else {
			return null;
		}
		
		$expiration_timestamp = strtotime( "+{$years} years", $filing_timestamp );
		
		return date( 'Y-m-d', $expiration_timestamp );
	}
}
