<?php
/**
 * Date helper utilities.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Shared;

/**
 * Date_Helper Class
 */
class Date_Helper {
	
	/**
	 * Get current datetime in MySQL format
	 *
	 * @return string
	 */
	public static function now() {
		return current_time( 'mysql' );
	}
	
	/**
	 * Get current date in MySQL format
	 *
	 * @return string
	 */
	public static function today() {
		return gmdate( 'Y-m-d' );
	}
	
	/**
	 * Add days to a date
	 *
	 * @param string $date Date string
	 * @param int    $days Number of days to add
	 * @return string
	 */
	public static function add_days( $date, $days ) {
		$timestamp = strtotime( $date );
		return gmdate( 'Y-m-d', strtotime( "+{$days} days", $timestamp ) );
	}
	
	/**
	 * Subtract days from a date
	 *
	 * @param string $date Date string
	 * @param int    $days Number of days to subtract
	 * @return string
	 */
	public static function sub_days( $date, $days ) {
		$timestamp = strtotime( $date );
		return gmdate( 'Y-m-d', strtotime( "-{$days} days", $timestamp ) );
	}
	
	/**
	 * Calculate difference in days between two dates
	 *
	 * @param string $date1 First date
	 * @param string $date2 Second date
	 * @return int
	 */
	public static function diff_days( $date1, $date2 ) {
		$timestamp1 = strtotime( $date1 );
		$timestamp2 = strtotime( $date2 );
		
		return abs( floor( ( $timestamp2 - $timestamp1 ) / 86400 ) );
	}
	
	/**
	 * Check if date is in the past
	 *
	 * @param string $date Date to check
	 * @return bool
	 */
	public static function is_past( $date ) {
		return strtotime( $date ) < time();
	}
	
	/**
	 * Check if date is in the future
	 *
	 * @param string $date Date to check
	 * @return bool
	 */
	public static function is_future( $date ) {
		return strtotime( $date ) > time();
	}
	
	/**
	 * Get relative time string (e.g., "2 hours ago")
	 *
	 * @param string $datetime Datetime string
	 * @return string
	 */
	public static function time_ago( $datetime ) {
		return human_time_diff( strtotime( $datetime ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'synpat-platform' );
	}
	
	/**
	 * Parse MySQL date to timestamp
	 *
	 * @param string $date MySQL date string
	 * @return int
	 */
	public static function to_timestamp( $date ) {
		return strtotime( $date );
	}
	
	/**
	 * Format timestamp to MySQL date
	 *
	 * @param int $timestamp Unix timestamp
	 * @return string
	 */
	public static function from_timestamp( $timestamp ) {
		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}
	
	/**
	 * Get start of day
	 *
	 * @param string $date Date string (optional, defaults to today)
	 * @return string
	 */
	public static function start_of_day( $date = null ) {
		$date = $date ?? self::today();
		return gmdate( 'Y-m-d 00:00:00', strtotime( $date ) );
	}
	
	/**
	 * Get end of day
	 *
	 * @param string $date Date string (optional, defaults to today)
	 * @return string
	 */
	public static function end_of_day( $date = null ) {
		$date = $date ?? self::today();
		return gmdate( 'Y-m-d 23:59:59', strtotime( $date ) );
	}
}
