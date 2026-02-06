<?php
/**
 * Fired during plugin deactivation.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core;

/**
 * Deactivator Class
 */
class Deactivator {
	
	/**
	 * Plugin deactivation logic
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Clear any scheduled cron jobs
		self::clear_scheduled_tasks();
		
		// Clear transients/cache
		self::clear_cache();
		
		// Log deactivation
		update_option( 'synpat_platform_deactivated', time() );
	}
	
	/**
	 * Clear scheduled tasks
	 */
	private static function clear_scheduled_tasks() {
		// Clear any scheduled cron events
		$timestamp = wp_next_scheduled( 'synpat_daily_maintenance' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'synpat_daily_maintenance' );
		}
	}
	
	/**
	 * Clear all plugin-related transients
	 */
	private static function clear_cache() {
		global $wpdb;
		
		// Delete transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_synpat_%' 
			OR option_name LIKE '_transient_timeout_synpat_%'"
		);
	}
}
