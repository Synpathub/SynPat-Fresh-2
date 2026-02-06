<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package SynPatPlatform
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load plugin file for constants
require_once plugin_dir_path( __FILE__ ) . 'synpat-platform.php';

// Load dependencies
require_once SYNPAT_PLATFORM_PATH . 'includes/Core/Database/Schema.php';
require_once SYNPAT_PLATFORM_PATH . 'includes/Core/Database/Migrator.php';

use SynPat\Core\Database\Schema;
use SynPat\Core\Database\Migrator;

/**
 * Delete all plugin data
 */
function synpat_platform_uninstall() {
	global $wpdb;
	
	// Drop all custom tables
	$schema = new Schema();
	$migrator = new Migrator( $schema );
	$migrator->drop_tables();
	
	// Delete all options
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'synpat_%'" );
	
	// Delete all transients
	$wpdb->query(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE '_transient_synpat_%' 
		OR option_name LIKE '_transient_timeout_synpat_%'"
	);
	
	// Remove custom capabilities from administrator role
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->remove_cap( 'synpat_manage_all' );
		$admin->remove_cap( 'synpat_manage_portfolios' );
		$admin->remove_cap( 'synpat_view_portfolios' );
		$admin->remove_cap( 'synpat_manage_patents' );
		$admin->remove_cap( 'synpat_manage_licenses' );
		$admin->remove_cap( 'synpat_manage_customers' );
		$admin->remove_cap( 'synpat_create_due_diligence' );
		$admin->remove_cap( 'synpat_view_reports' );
		$admin->remove_cap( 'synpat_manage_settings' );
	}
	
	// Remove custom roles
	remove_role( 'synpat_admin' );
	remove_role( 'synpat_analyst' );
	remove_role( 'synpat_sales' );
	remove_role( 'synpat_customer' );
	
	// Clear any scheduled hooks
	wp_clear_scheduled_hook( 'synpat_daily_maintenance' );
}

synpat_platform_uninstall();
