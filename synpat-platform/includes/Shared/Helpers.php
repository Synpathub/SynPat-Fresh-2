<?php
/**
 * Helper utilities.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Shared;

/**
 * Helpers Class
 */
class Helpers {
	
	/**
	 * Format currency
	 *
	 * @param float  $amount   Amount to format
	 * @param string $currency Currency code
	 * @return string
	 */
	public static function format_currency( $amount, $currency = 'USD' ) {
		$symbols = array(
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
		);
		
		$symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';
		return $symbol . number_format( $amount, 2 );
	}
	
	/**
	 * Generate unique slug
	 *
	 * @param string $title      Title to slugify
	 * @param string $table      Table name
	 * @param int    $exclude_id ID to exclude
	 * @return string
	 */
	public static function generate_unique_slug( $title, $table, $exclude_id = 0 ) {
		global $wpdb;
		
		$slug = sanitize_title( $title );
		$original_slug = $slug;
		$counter = 1;
		
		$table_name = $wpdb->prefix . 'synpat_' . $table;
		
		while ( true ) {
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE slug = %s",
				$slug
			);
			
			if ( $exclude_id ) {
				$query = $wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE slug = %s AND id != %d",
					$slug,
					$exclude_id
				);
			}
			
			$count = $wpdb->get_var( $query );
			
			if ( ! $count ) {
				break;
			}
			
			$slug = $original_slug . '-' . $counter;
			$counter++;
		}
		
		return $slug;
	}
	
	/**
	 * Truncate text
	 *
	 * @param string $text   Text to truncate
	 * @param int    $length Maximum length
	 * @param string $suffix Suffix to add
	 * @return string
	 */
	public static function truncate( $text, $length = 100, $suffix = '...' ) {
		if ( mb_strlen( $text ) <= $length ) {
			return $text;
		}
		
		return mb_substr( $text, 0, $length ) . $suffix;
	}
	
	/**
	 * Parse tags string to array
	 *
	 * @param string $tags_string Comma-separated tags
	 * @return array
	 */
	public static function parse_tags( $tags_string ) {
		if ( empty( $tags_string ) ) {
			return array();
		}
		
		$tags = explode( ',', $tags_string );
		return array_map( 'trim', $tags );
	}
	
	/**
	 * Convert array to tags string
	 *
	 * @param array $tags Tags array
	 * @return string
	 */
	public static function tags_to_string( $tags ) {
		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return '';
		}
		
		return implode( ', ', $tags );
	}
	
	/**
	 * Get user display name
	 *
	 * @param int $user_id User ID
	 * @return string
	 */
	public static function get_user_display_name( $user_id ) {
		$user = get_userdata( $user_id );
		return $user ? $user->display_name : __( 'Unknown User', 'synpat-platform' );
	}
	
	/**
	 * Check if user can perform action
	 *
	 * @param string $capability Capability to check
	 * @param int    $user_id    User ID (optional, defaults to current user)
	 * @return bool
	 */
	public static function user_can( $capability, $user_id = null ) {
		if ( null === $user_id ) {
			return current_user_can( $capability );
		}
		
		$user = get_userdata( $user_id );
		return $user && $user->has_cap( $capability );
	}
	
	/**
	 * Get pagination data
	 *
	 * @param int $total      Total items
	 * @param int $per_page   Items per page
	 * @param int $current    Current page
	 * @return array
	 */
	public static function get_pagination( $total, $per_page, $current = 1 ) {
		$total_pages = ceil( $total / $per_page );
		
		return array(
			'total'        => $total,
			'per_page'     => $per_page,
			'current_page' => max( 1, $current ),
			'total_pages'  => $total_pages,
			'has_prev'     => $current > 1,
			'has_next'     => $current < $total_pages,
			'prev_page'    => max( 1, $current - 1 ),
			'next_page'    => min( $total_pages, $current + 1 ),
		);
	}
}
