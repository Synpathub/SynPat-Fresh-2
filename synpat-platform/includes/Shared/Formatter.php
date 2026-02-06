<?php
/**
 * Data formatting utilities.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Shared;

/**
 * Formatter Class
 */
class Formatter {
	
	/**
	 * Format date
	 *
	 * @param string $date   Date string
	 * @param string $format Output format (default: WordPress date format)
	 * @return string
	 */
	public static function date( $date, $format = null ) {
		if ( empty( $date ) ) {
			return '';
		}
		
		if ( null === $format ) {
			$format = get_option( 'date_format', 'Y-m-d' );
		}
		
		$timestamp = is_numeric( $date ) ? $date : strtotime( $date );
		return gmdate( $format, $timestamp );
	}
	
	/**
	 * Format datetime
	 *
	 * @param string $datetime Datetime string
	 * @param string $format   Output format
	 * @return string
	 */
	public static function datetime( $datetime, $format = null ) {
		if ( empty( $datetime ) ) {
			return '';
		}
		
		if ( null === $format ) {
			$date_format = get_option( 'date_format', 'Y-m-d' );
			$time_format = get_option( 'time_format', 'H:i:s' );
			$format = $date_format . ' ' . $time_format;
		}
		
		$timestamp = is_numeric( $datetime ) ? $datetime : strtotime( $datetime );
		return gmdate( $format, $timestamp );
	}
	
	/**
	 * Format number
	 *
	 * @param float $number   Number to format
	 * @param int   $decimals Decimal places
	 * @return string
	 */
	public static function number( $number, $decimals = 2 ) {
		return number_format( (float) $number, $decimals );
	}
	
	/**
	 * Format currency
	 *
	 * @param float  $amount   Amount to format
	 * @param string $currency Currency code
	 * @return string
	 */
	public static function currency( $amount, $currency = 'USD' ) {
		return Helpers::format_currency( $amount, $currency );
	}
	
	/**
	 * Format percentage
	 *
	 * @param float $value    Value to format
	 * @param int   $decimals Decimal places
	 * @return string
	 */
	public static function percentage( $value, $decimals = 2 ) {
		return number_format( (float) $value, $decimals ) . '%';
	}
	
	/**
	 * Format file size
	 *
	 * @param int $bytes File size in bytes
	 * @return string
	 */
	public static function file_size( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		
		for ( $i = 0; $bytes > 1024; $i++ ) {
			$bytes /= 1024;
		}
		
		return round( $bytes, 2 ) . ' ' . $units[ $i ];
	}
	
	/**
	 * Format phone number
	 *
	 * @param string $phone Phone number
	 * @return string
	 */
	public static function phone( $phone ) {
		// Remove all non-numeric characters
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		
		// Format US phone numbers
		if ( strlen( $phone ) === 10 ) {
			return sprintf( '(%s) %s-%s',
				substr( $phone, 0, 3 ),
				substr( $phone, 3, 3 ),
				substr( $phone, 6 )
			);
		}
		
		return $phone;
	}
	
	/**
	 * Truncate text with ellipsis
	 *
	 * @param string $text   Text to truncate
	 * @param int    $length Maximum length
	 * @return string
	 */
	public static function truncate( $text, $length = 100 ) {
		return Helpers::truncate( $text, $length );
	}
	
	/**
	 * Format array as comma-separated string
	 *
	 * @param array $array Array to format
	 * @return string
	 */
	public static function array_to_string( $array ) {
		if ( ! is_array( $array ) ) {
			return '';
		}
		
		return implode( ', ', $array );
	}
}
