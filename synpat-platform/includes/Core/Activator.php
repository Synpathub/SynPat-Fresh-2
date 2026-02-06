<?php
/**
 * Fired during plugin activation.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core;

use SynPat\Core\Database\Schema;
use SynPat\Core\Database\Migrator;

/**
 * Activator Class
 */
class Activator {
	
	/**
	 * Plugin activation logic
	 */
	public static function activate() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, SYNPAT_PLATFORM_MIN_PHP, '<' ) ) {
			deactivate_plugins( SYNPAT_PLATFORM_BASENAME );
			wp_die(
				sprintf(
					/* translators: 1: Current PHP version, 2: Required PHP version */
					esc_html__( 'SynPat Platform requires PHP %2$s or higher. You are running %1$s.', 'synpat-platform' ),
					PHP_VERSION,
					SYNPAT_PLATFORM_MIN_PHP
				)
			);
		}
		
		// Create database tables
		self::create_tables();
		
		// Create custom roles and capabilities
		self::create_roles();
		
		// Set default options
		self::set_default_options();
		
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Set activation timestamp
		update_option( 'synpat_platform_activated', time() );
		update_option( 'synpat_platform_version', SYNPAT_PLATFORM_VERSION );
	}
	
	/**
	 * Create database tables
	 */
	private static function create_tables() {
		$schema = new Schema();
		$migrator = new Migrator( $schema );
		$migrator->run();
	}
	
	/**
	 * Create custom roles and capabilities
	 */
	private static function create_roles() {
		// Remove roles first (in case of reactivation)
		remove_role( 'synpat_admin' );
		remove_role( 'synpat_analyst' );
		remove_role( 'synpat_sales' );
		remove_role( 'synpat_customer' );
		
		// SynPat Admin - Full access
		add_role(
			'synpat_admin',
			__( 'SynPat Administrator', 'synpat-platform' ),
			array(
				'read'                       => true,
				'synpat_manage_all'          => true,
				'synpat_manage_portfolios'   => true,
				'synpat_view_portfolios'     => true,
				'synpat_manage_patents'      => true,
				'synpat_manage_licenses'     => true,
				'synpat_manage_customers'    => true,
				'synpat_create_due_diligence' => true,
				'synpat_view_reports'        => true,
				'synpat_manage_settings'     => true,
			)
		);
		
		// SynPat Analyst - Portfolio and patent management, due diligence
		add_role(
			'synpat_analyst',
			__( 'SynPat Analyst', 'synpat-platform' ),
			array(
				'read'                       => true,
				'synpat_manage_portfolios'   => true,
				'synpat_view_portfolios'     => true,
				'synpat_manage_patents'      => true,
				'synpat_create_due_diligence' => true,
				'synpat_view_reports'        => true,
			)
		);
		
		// SynPat Sales - License and customer management
		add_role(
			'synpat_sales',
			__( 'SynPat Sales', 'synpat-platform' ),
			array(
				'read'                     => true,
				'synpat_view_portfolios'   => true,
				'synpat_manage_licenses'   => true,
				'synpat_manage_customers'  => true,
				'synpat_view_reports'      => true,
			)
		);
		
		// SynPat Customer - View portfolios and own licenses
		add_role(
			'synpat_customer',
			__( 'SynPat Customer', 'synpat-platform' ),
			array(
				'read'                   => true,
				'synpat_view_portfolios' => true,
			)
		);
		
		// Add capabilities to administrator role
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'synpat_manage_all' );
			$admin->add_cap( 'synpat_manage_portfolios' );
			$admin->add_cap( 'synpat_view_portfolios' );
			$admin->add_cap( 'synpat_manage_patents' );
			$admin->add_cap( 'synpat_manage_licenses' );
			$admin->add_cap( 'synpat_manage_customers' );
			$admin->add_cap( 'synpat_create_due_diligence' );
			$admin->add_cap( 'synpat_view_reports' );
			$admin->add_cap( 'synpat_manage_settings' );
		}
	}
	
	/**
	 * Set default plugin options
	 */
	private static function set_default_options() {
		$defaults = array(
			'synpat_date_format'        => 'Y-m-d',
			'synpat_currency'           => 'USD',
			'synpat_items_per_page'     => 20,
			'synpat_enable_notifications' => true,
			'synpat_pdf_header_logo'    => '',
			'synpat_pdf_footer_text'    => '',
		);
		
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
