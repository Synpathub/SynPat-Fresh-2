<?php
/**
 * Database migrator for creating and updating tables.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core\Database;

/**
 * Migrator Class
 */
class Migrator {
	
	/**
	 * Schema instance
	 *
	 * @var Schema
	 */
	private $schema;
	
	/**
	 * Constructor
	 *
	 * @param Schema $schema Schema instance
	 */
	public function __construct( Schema $schema ) {
		$this->schema = $schema;
	}
	
	/**
	 * Run migrations
	 */
	public function run() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$tables = $this->schema->get_tables();
		
		foreach ( $tables as $table_sql ) {
			dbDelta( $table_sql );
		}
		
		// Update database version
		update_option( 'synpat_db_version', SYNPAT_PLATFORM_VERSION );
	}
	
	/**
	 * Check if tables exist
	 *
	 * @return bool
	 */
	public function tables_exist() {
		global $wpdb;
		
		$prefix = $this->schema->get_table_prefix();
		$table_name = $prefix . 'portfolios';
		
		$result = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$wpdb->esc_like( $table_name )
		) );
		
		return $result === $table_name;
	}
	
	/**
	 * Drop all plugin tables
	 */
	public function drop_tables() {
		global $wpdb;
		
		$prefix = $this->schema->get_table_prefix();
		
		$tables = array(
			'saved_searches',
			'invitations',
			'activity_log',
			'documents',
			'license_payments',
			'licenses',
			'customers',
			'claim_charts',
			'due_diligence',
			'patent_families',
			'portfolio_patents',
			'patents',
			'portfolios',
			'contacts',
			'companies',
			'categories',
		);
		
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" );
		}
	}
}
