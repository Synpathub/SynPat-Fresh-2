<?php
/**
 * Data sanitization utilities.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Shared;

/**
 * Sanitizer Class
 */
class Sanitizer {
	
	/**
	 * Sanitize portfolio data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	public static function sanitize_portfolio( $data ) {
		return array(
			'title'        => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'description'  => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'category_id'  => isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 0,
			'company_id'   => isset( $data['company_id'] ) ? absint( $data['company_id'] ) : 0,
			'status'       => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'draft',
			'visibility'   => isset( $data['visibility'] ) ? sanitize_text_field( $data['visibility'] ) : 'private',
			'price'        => isset( $data['price'] ) ? floatval( $data['price'] ) : 0,
			'currency'     => isset( $data['currency'] ) ? sanitize_text_field( $data['currency'] ) : 'USD',
			'asking_price' => isset( $data['asking_price'] ) ? floatval( $data['asking_price'] ) : 0,
			'valuation'    => isset( $data['valuation'] ) ? floatval( $data['valuation'] ) : 0,
			'tags'         => isset( $data['tags'] ) ? sanitize_textarea_field( $data['tags'] ) : '',
		);
	}
	
	/**
	 * Sanitize patent data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	public static function sanitize_patent( $data ) {
		return array(
			'patent_number'      => isset( $data['patent_number'] ) ? sanitize_text_field( $data['patent_number'] ) : '',
			'application_number' => isset( $data['application_number'] ) ? sanitize_text_field( $data['application_number'] ) : '',
			'title'              => isset( $data['title'] ) ? sanitize_textarea_field( $data['title'] ) : '',
			'abstract'           => isset( $data['abstract'] ) ? sanitize_textarea_field( $data['abstract'] ) : '',
			'patent_type'        => isset( $data['patent_type'] ) ? sanitize_text_field( $data['patent_type'] ) : '',
			'status'             => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '',
			'country'            => isset( $data['country'] ) ? sanitize_text_field( $data['country'] ) : '',
			'filing_date'        => isset( $data['filing_date'] ) ? sanitize_text_field( $data['filing_date'] ) : '',
			'publication_date'   => isset( $data['publication_date'] ) ? sanitize_text_field( $data['publication_date'] ) : '',
			'grant_date'         => isset( $data['grant_date'] ) ? sanitize_text_field( $data['grant_date'] ) : '',
			'expiration_date'    => isset( $data['expiration_date'] ) ? sanitize_text_field( $data['expiration_date'] ) : '',
			'assignee'           => isset( $data['assignee'] ) ? sanitize_text_field( $data['assignee'] ) : '',
			'inventors'          => isset( $data['inventors'] ) ? sanitize_textarea_field( $data['inventors'] ) : '',
		);
	}
	
	/**
	 * Sanitize company data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	public static function sanitize_company( $data ) {
		return array(
			'name'           => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'legal_name'     => isset( $data['legal_name'] ) ? sanitize_text_field( $data['legal_name'] ) : '',
			'type'           => isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : '',
			'industry'       => isset( $data['industry'] ) ? sanitize_text_field( $data['industry'] ) : '',
			'website'        => isset( $data['website'] ) ? esc_url_raw( $data['website'] ) : '',
			'email'          => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
			'phone'          => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
			'address_line1'  => isset( $data['address_line1'] ) ? sanitize_text_field( $data['address_line1'] ) : '',
			'address_line2'  => isset( $data['address_line2'] ) ? sanitize_text_field( $data['address_line2'] ) : '',
			'city'           => isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '',
			'state'          => isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : '',
			'country'        => isset( $data['country'] ) ? sanitize_text_field( $data['country'] ) : '',
			'postal_code'    => isset( $data['postal_code'] ) ? sanitize_text_field( $data['postal_code'] ) : '',
			'notes'          => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
		);
	}
	
	/**
	 * Sanitize contact data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	public static function sanitize_contact( $data ) {
		return array(
			'company_id'  => isset( $data['company_id'] ) ? absint( $data['company_id'] ) : 0,
			'first_name'  => isset( $data['first_name'] ) ? sanitize_text_field( $data['first_name'] ) : '',
			'last_name'   => isset( $data['last_name'] ) ? sanitize_text_field( $data['last_name'] ) : '',
			'title'       => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'email'       => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
			'phone'       => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
			'mobile'      => isset( $data['mobile'] ) ? sanitize_text_field( $data['mobile'] ) : '',
			'notes'       => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
			'is_primary'  => isset( $data['is_primary'] ) ? (bool) $data['is_primary'] : false,
		);
	}
	
	/**
	 * Sanitize license data
	 *
	 * @param array $data Raw data
	 * @return array Sanitized data
	 */
	public static function sanitize_license( $data ) {
		return array(
			'license_number'         => isset( $data['license_number'] ) ? sanitize_text_field( $data['license_number'] ) : '',
			'portfolio_id'           => isset( $data['portfolio_id'] ) ? absint( $data['portfolio_id'] ) : 0,
			'customer_id'            => isset( $data['customer_id'] ) ? absint( $data['customer_id'] ) : 0,
			'license_type'           => isset( $data['license_type'] ) ? sanitize_text_field( $data['license_type'] ) : '',
			'status'                 => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'draft',
			'start_date'             => isset( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : '',
			'end_date'               => isset( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : '',
			'territory'              => isset( $data['territory'] ) ? sanitize_text_field( $data['territory'] ) : '',
			'field_of_use'           => isset( $data['field_of_use'] ) ? sanitize_textarea_field( $data['field_of_use'] ) : '',
			'license_fee'            => isset( $data['license_fee'] ) ? floatval( $data['license_fee'] ) : 0,
			'royalty_rate'           => isset( $data['royalty_rate'] ) ? floatval( $data['royalty_rate'] ) : 0,
			'minimum_royalty'        => isset( $data['minimum_royalty'] ) ? floatval( $data['minimum_royalty'] ) : 0,
			'currency'               => isset( $data['currency'] ) ? sanitize_text_field( $data['currency'] ) : 'USD',
			'payment_schedule'       => isset( $data['payment_schedule'] ) ? sanitize_text_field( $data['payment_schedule'] ) : '',
			'exclusivity'            => isset( $data['exclusivity'] ) ? sanitize_text_field( $data['exclusivity'] ) : '',
			'sublicensing_allowed'   => isset( $data['sublicensing_allowed'] ) ? (bool) $data['sublicensing_allowed'] : false,
			'contract_url'           => isset( $data['contract_url'] ) ? esc_url_raw( $data['contract_url'] ) : '',
			'notes'                  => isset( $data['notes'] ) ? sanitize_textarea_field( $data['notes'] ) : '',
		);
	}
}
