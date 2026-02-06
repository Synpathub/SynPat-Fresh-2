<?php
/**
 * Portfolio validator class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Portfolio;

use WP_Error;

/**
 * Portfolio_Validator Class
 */
class Portfolio_Validator {
	
	/**
	 * Repository instance
	 *
	 * @var Portfolio_Repository
	 */
	private $repository;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->repository = new Portfolio_Repository();
	}
	
	/**
	 * Validate portfolio creation data
	 *
	 * @param array $data Portfolio data
	 * @return bool|WP_Error
	 */
	public function validate_create( $data ) {
		// Title is required
		if ( empty( $data['title'] ) ) {
			return new WP_Error(
				'missing_title',
				__( 'Portfolio title is required.', 'synpat-platform' )
			);
		}
		
		// Validate title length
		if ( strlen( $data['title'] ) > 255 ) {
			return new WP_Error(
				'title_too_long',
				__( 'Portfolio title must be less than 255 characters.', 'synpat-platform' )
			);
		}
		
		// Validate slug if provided
		if ( ! empty( $data['slug'] ) ) {
			$slug_validation = $this->validate_slug( $data['slug'] );
			if ( is_wp_error( $slug_validation ) ) {
				return $slug_validation;
			}
		}
		
		// Validate status
		if ( ! empty( $data['status'] ) ) {
			$status_validation = $this->validate_status( $data['status'] );
			if ( is_wp_error( $status_validation ) ) {
				return $status_validation;
			}
		}
		
		// Validate visibility
		if ( ! empty( $data['visibility'] ) ) {
			$visibility_validation = $this->validate_visibility( $data['visibility'] );
			if ( is_wp_error( $visibility_validation ) ) {
				return $visibility_validation;
			}
		}
		
		// Validate price fields
		$price_validation = $this->validate_price_fields( $data );
		if ( is_wp_error( $price_validation ) ) {
			return $price_validation;
		}
		
		// Validate currency
		if ( ! empty( $data['currency'] ) ) {
			$currency_validation = $this->validate_currency( $data['currency'] );
			if ( is_wp_error( $currency_validation ) ) {
				return $currency_validation;
			}
		}
		
		// Validate category if provided
		if ( ! empty( $data['category_id'] ) ) {
			$category_validation = $this->validate_category( $data['category_id'] );
			if ( is_wp_error( $category_validation ) ) {
				return $category_validation;
			}
		}
		
		// Validate company if provided
		if ( ! empty( $data['company_id'] ) ) {
			$company_validation = $this->validate_company( $data['company_id'] );
			if ( is_wp_error( $company_validation ) ) {
				return $company_validation;
			}
		}
		
		return true;
	}
	
	/**
	 * Validate portfolio update data
	 *
	 * @param int   $id   Portfolio ID
	 * @param array $data Portfolio data
	 * @return bool|WP_Error
	 */
	public function validate_update( $id, $data ) {
		// Check if portfolio exists
		$portfolio = $this->repository->find( $id );
		if ( ! $portfolio ) {
			return new WP_Error(
				'not_found',
				__( 'Portfolio not found.', 'synpat-platform' )
			);
		}
		
		// Validate title if provided
		if ( isset( $data['title'] ) ) {
			if ( empty( $data['title'] ) ) {
				return new WP_Error(
					'missing_title',
					__( 'Portfolio title cannot be empty.', 'synpat-platform' )
				);
			}
			
			if ( strlen( $data['title'] ) > 255 ) {
				return new WP_Error(
					'title_too_long',
					__( 'Portfolio title must be less than 255 characters.', 'synpat-platform' )
				);
			}
		}
		
		// Validate slug if provided
		if ( isset( $data['slug'] ) ) {
			$slug_validation = $this->validate_slug( $data['slug'], $id );
			if ( is_wp_error( $slug_validation ) ) {
				return $slug_validation;
			}
		}
		
		// Validate status if provided
		if ( isset( $data['status'] ) ) {
			$status_validation = $this->validate_status( $data['status'] );
			if ( is_wp_error( $status_validation ) ) {
				return $status_validation;
			}
		}
		
		// Validate visibility if provided
		if ( isset( $data['visibility'] ) ) {
			$visibility_validation = $this->validate_visibility( $data['visibility'] );
			if ( is_wp_error( $visibility_validation ) ) {
				return $visibility_validation;
			}
		}
		
		// Validate price fields if provided
		$price_validation = $this->validate_price_fields( $data );
		if ( is_wp_error( $price_validation ) ) {
			return $price_validation;
		}
		
		// Validate currency if provided
		if ( isset( $data['currency'] ) ) {
			$currency_validation = $this->validate_currency( $data['currency'] );
			if ( is_wp_error( $currency_validation ) ) {
				return $currency_validation;
			}
		}
		
		// Validate category if provided
		if ( isset( $data['category_id'] ) && ! empty( $data['category_id'] ) ) {
			$category_validation = $this->validate_category( $data['category_id'] );
			if ( is_wp_error( $category_validation ) ) {
				return $category_validation;
			}
		}
		
		// Validate company if provided
		if ( isset( $data['company_id'] ) && ! empty( $data['company_id'] ) ) {
			$company_validation = $this->validate_company( $data['company_id'] );
			if ( is_wp_error( $company_validation ) ) {
				return $company_validation;
			}
		}
		
		return true;
	}
	
	/**
	 * Validate slug uniqueness
	 *
	 * @param string   $slug       Portfolio slug
	 * @param int|null $exclude_id Portfolio ID to exclude from check
	 * @return bool|WP_Error
	 */
	public function validate_slug( $slug, $exclude_id = null ) {
		// Check slug format
		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return new WP_Error(
				'invalid_slug',
				__( 'Portfolio slug must contain only lowercase letters, numbers, and hyphens.', 'synpat-platform' )
			);
		}
		
		// Check slug length
		if ( strlen( $slug ) > 255 ) {
			return new WP_Error(
				'slug_too_long',
				__( 'Portfolio slug must be less than 255 characters.', 'synpat-platform' )
			);
		}
		
		// Check uniqueness
		$existing = $this->repository->find_by_slug( $slug );
		
		if ( $existing ) {
			// If excluding current portfolio, check if it's different
			if ( $exclude_id && $existing->id == $exclude_id ) {
				return true;
			}
			
			return new WP_Error(
				'duplicate_slug',
				__( 'A portfolio with this slug already exists.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Validate portfolio status
	 *
	 * @param string $status Portfolio status
	 * @return bool|WP_Error
	 */
	private function validate_status( $status ) {
		$valid_statuses = array( 'draft', 'published', 'archived' );
		
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return new WP_Error(
				'invalid_status',
				sprintf(
					__( 'Invalid portfolio status. Must be one of: %s', 'synpat-platform' ),
					implode( ', ', $valid_statuses )
				)
			);
		}
		
		return true;
	}
	
	/**
	 * Validate portfolio visibility
	 *
	 * @param string $visibility Portfolio visibility
	 * @return bool|WP_Error
	 */
	private function validate_visibility( $visibility ) {
		$valid_visibilities = array( 'private', 'public', 'unlisted' );
		
		if ( ! in_array( $visibility, $valid_visibilities, true ) ) {
			return new WP_Error(
				'invalid_visibility',
				sprintf(
					__( 'Invalid portfolio visibility. Must be one of: %s', 'synpat-platform' ),
					implode( ', ', $valid_visibilities )
				)
			);
		}
		
		return true;
	}
	
	/**
	 * Validate price fields
	 *
	 * @param array $data Portfolio data
	 * @return bool|WP_Error
	 */
	private function validate_price_fields( $data ) {
		$price_fields = array( 'price', 'asking_price', 'valuation' );
		
		foreach ( $price_fields as $field ) {
			if ( isset( $data[ $field ] ) && ! empty( $data[ $field ] ) ) {
				if ( ! is_numeric( $data[ $field ] ) ) {
					return new WP_Error(
						'invalid_price',
						sprintf(
							__( 'Invalid %s. Must be a numeric value.', 'synpat-platform' ),
							str_replace( '_', ' ', $field )
						)
					);
				}
				
				if ( $data[ $field ] < 0 ) {
					return new WP_Error(
						'negative_price',
						sprintf(
							__( 'Invalid %s. Must be a positive value.', 'synpat-platform' ),
							str_replace( '_', ' ', $field )
						)
					);
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Validate currency code
	 *
	 * @param string $currency Currency code
	 * @return bool|WP_Error
	 */
	private function validate_currency( $currency ) {
		$valid_currencies = array( 'USD', 'EUR', 'GBP', 'JPY', 'CNY', 'CAD', 'AUD' );
		
		if ( ! in_array( $currency, $valid_currencies, true ) ) {
			return new WP_Error(
				'invalid_currency',
				sprintf(
					__( 'Invalid currency code. Must be one of: %s', 'synpat-platform' ),
					implode( ', ', $valid_currencies )
				)
			);
		}
		
		return true;
	}
	
	/**
	 * Validate category ID
	 *
	 * @param int $category_id Category ID
	 * @return bool|WP_Error
	 */
	private function validate_category( $category_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_categories';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$category_id
		) );
		
		if ( ! $exists ) {
			return new WP_Error(
				'invalid_category',
				__( 'The specified category does not exist.', 'synpat-platform' )
			);
		}
		
		return true;
	}
	
	/**
	 * Validate company ID
	 *
	 * @param int $company_id Company ID
	 * @return bool|WP_Error
	 */
	private function validate_company( $company_id ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_companies';
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE id = %d",
			$company_id
		) );
		
		if ( ! $exists ) {
			return new WP_Error(
				'invalid_company',
				__( 'The specified company does not exist.', 'synpat-platform' )
			);
		}
		
		return true;
	}
}
