<?php
/**
 * Security utilities for the plugin.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core;

/**
 * Security Class
 */
class Security {
	
	/**
	 * Verify nonce for security
	 *
	 * @param string $nonce  The nonce value
	 * @param string $action The action name
	 * @return bool
	 */
	public function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, $action );
	}
	
	/**
	 * Create nonce for security
	 *
	 * @param string $action The action name
	 * @return string
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $action );
	}
	
	/**
	 * Check if current user has capability
	 *
	 * @param string $capability The capability to check
	 * @return bool
	 */
	public function current_user_can( $capability ) {
		return current_user_can( $capability );
	}
	
	/**
	 * Sanitize text input
	 *
	 * @param string $input The input to sanitize
	 * @return string
	 */
	public function sanitize_text( $input ) {
		return sanitize_text_field( $input );
	}
	
	/**
	 * Sanitize textarea input
	 *
	 * @param string $input The input to sanitize
	 * @return string
	 */
	public function sanitize_textarea( $input ) {
		return sanitize_textarea_field( $input );
	}
	
	/**
	 * Sanitize email
	 *
	 * @param string $email The email to sanitize
	 * @return string
	 */
	public function sanitize_email( $email ) {
		return sanitize_email( $email );
	}
	
	/**
	 * Sanitize URL
	 *
	 * @param string $url The URL to sanitize
	 * @return string
	 */
	public function sanitize_url( $url ) {
		return esc_url_raw( $url );
	}
	
	/**
	 * Sanitize integer
	 *
	 * @param mixed $input The input to sanitize
	 * @return int
	 */
	public function sanitize_int( $input ) {
		return absint( $input );
	}
	
	/**
	 * Sanitize array of integers
	 *
	 * @param array $input The array to sanitize
	 * @return array
	 */
	public function sanitize_int_array( $input ) {
		return array_map( 'absint', (array) $input );
	}
	
	/**
	 * Escape HTML output
	 *
	 * @param string $text The text to escape
	 * @return string
	 */
	public function esc_html( $text ) {
		return esc_html( $text );
	}
	
	/**
	 * Escape attribute output
	 *
	 * @param string $text The text to escape
	 * @return string
	 */
	public function esc_attr( $text ) {
		return esc_attr( $text );
	}
	
	/**
	 * Escape URL output
	 *
	 * @param string $url The URL to escape
	 * @return string
	 */
	public function esc_url( $url ) {
		return esc_url( $url );
	}
	
	/**
	 * Check if request is AJAX
	 *
	 * @return bool
	 */
	public function is_ajax() {
		return wp_doing_ajax();
	}
	
	/**
	 * Validate file upload
	 *
	 * @param array $file         The uploaded file array
	 * @param array $allowed_types Allowed MIME types
	 * @param int   $max_size     Maximum file size in bytes
	 * @return array|WP_Error
	 */
	public function validate_file_upload( $file, $allowed_types = array(), $max_size = 10485760 ) {
		// Check for upload errors
		if ( ! empty( $file['error'] ) ) {
			return new \WP_Error( 'upload_error', __( 'File upload error.', 'synpat-platform' ) );
		}
		
		// Check file size
		if ( $file['size'] > $max_size ) {
			return new \WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %s: Maximum file size in MB */
					__( 'File size exceeds maximum allowed size of %s MB.', 'synpat-platform' ),
					number_format( $max_size / 1048576, 2 )
				)
			);
		}
		
		// Check file type
		if ( ! empty( $allowed_types ) ) {
			$filetype = wp_check_filetype( $file['name'], $allowed_types );
			
			if ( ! in_array( $filetype['type'], $allowed_types, true ) ) {
				return new \WP_Error(
					'invalid_file_type',
					__( 'Invalid file type.', 'synpat-platform' )
				);
			}
		}
		
		return $file;
	}
	
	/**
	 * Hash sensitive data
	 *
	 * @param string $data The data to hash
	 * @return string
	 */
	public function hash( $data ) {
		return wp_hash( $data );
	}
	
	/**
	 * Generate random token
	 *
	 * @param int $length Token length
	 * @return string
	 */
	public function generate_token( $length = 32 ) {
		return bin2hex( random_bytes( $length / 2 ) );
	}
}
