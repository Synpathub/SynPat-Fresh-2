<?php
/**
 * Database schema definitions.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core\Database;

/**
 * Schema Class
 */
class Schema {
	
	/**
	 * Get table prefix
	 *
	 * @return string
	 */
	public function get_table_prefix() {
		global $wpdb;
		return $wpdb->prefix . 'synpat_';
	}
	
	/**
	 * Get all table schemas
	 *
	 * @return array
	 */
	public function get_tables() {
		return array(
			$this->categories_table(),
			$this->companies_table(),
			$this->contacts_table(),
			$this->portfolios_table(),
			$this->patents_table(),
			$this->portfolio_patents_table(),
			$this->patent_families_table(),
			$this->due_diligence_table(),
			$this->claim_charts_table(),
			$this->customers_table(),
			$this->licenses_table(),
			$this->license_payments_table(),
			$this->documents_table(),
			$this->activity_log_table(),
			$this->invitations_table(),
			$this->saved_searches_table(),
		);
	}
	
	/**
	 * Categories table schema
	 */
	private function categories_table() {
		$table_name = $this->get_table_prefix() . 'categories';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			parent_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY parent_id (parent_id)
		) {$charset_collate};";
	}
	
	/**
	 * Companies table schema
	 */
	private function companies_table() {
		$table_name = $this->get_table_prefix() . 'companies';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			legal_name varchar(255),
			type varchar(50),
			industry varchar(100),
			website varchar(255),
			email varchar(255),
			phone varchar(50),
			address_line1 varchar(255),
			address_line2 varchar(255),
			city varchar(100),
			state varchar(100),
			country varchar(100),
			postal_code varchar(20),
			notes text,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY name (name),
			KEY type (type),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Contacts table schema
	 */
	private function contacts_table() {
		$table_name = $this->get_table_prefix() . 'contacts';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			company_id bigint(20) unsigned NOT NULL,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			title varchar(100),
			email varchar(255),
			phone varchar(50),
			mobile varchar(50),
			notes text,
			is_primary tinyint(1) DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY company_id (company_id),
			KEY email (email),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Portfolios table schema
	 */
	private function portfolios_table() {
		$table_name = $this->get_table_prefix() . 'portfolios';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text,
			category_id bigint(20) unsigned,
			company_id bigint(20) unsigned,
			status varchar(50) DEFAULT 'draft',
			visibility varchar(50) DEFAULT 'private',
			price decimal(10,2),
			currency varchar(10) DEFAULT 'USD',
			asking_price decimal(10,2),
			valuation decimal(10,2),
			tags text,
			meta_data longtext,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			published_at datetime,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY category_id (category_id),
			KEY company_id (company_id),
			KEY status (status),
			KEY visibility (visibility),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Patents table schema
	 */
	private function patents_table() {
		$table_name = $this->get_table_prefix() . 'patents';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			patent_number varchar(100) NOT NULL,
			application_number varchar(100),
			title text NOT NULL,
			abstract text,
			patent_type varchar(50),
			status varchar(50),
			country varchar(10),
			filing_date date,
			publication_date date,
			grant_date date,
			expiration_date date,
			assignee varchar(255),
			inventors text,
			claims_count int,
			independent_claims int,
			priority_date date,
			patent_family_id varchar(100),
			ipc_classification text,
			cpc_classification text,
			us_classification text,
			legal_status varchar(100),
			maintenance_fee_status varchar(100),
			forward_citations int DEFAULT 0,
			backward_citations int DEFAULT 0,
			pdf_url varchar(500),
			source varchar(50),
			source_id varchar(100),
			meta_data longtext,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY patent_number (patent_number, country),
			KEY application_number (application_number),
			KEY patent_type (patent_type),
			KEY status (status),
			KEY filing_date (filing_date),
			KEY grant_date (grant_date),
			KEY assignee (assignee),
			KEY patent_family_id (patent_family_id),
			KEY created_by (created_by),
			FULLTEXT KEY title_abstract (title, abstract)
		) {$charset_collate};";
	}
	
	/**
	 * Portfolio-Patents pivot table schema
	 */
	private function portfolio_patents_table() {
		$table_name = $this->get_table_prefix() . 'portfolio_patents';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			portfolio_id bigint(20) unsigned NOT NULL,
			patent_id bigint(20) unsigned NOT NULL,
			position int DEFAULT 0,
			notes text,
			added_by bigint(20) unsigned NOT NULL,
			added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY portfolio_patent (portfolio_id, patent_id),
			KEY portfolio_id (portfolio_id),
			KEY patent_id (patent_id),
			KEY added_by (added_by)
		) {$charset_collate};";
	}
	
	/**
	 * Patent families table schema
	 */
	private function patent_families_table() {
		$table_name = $this->get_table_prefix() . 'patent_families';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			family_id varchar(100) NOT NULL,
			patent_id bigint(20) unsigned NOT NULL,
			relationship_type varchar(50),
			priority_date date,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY family_id (family_id),
			KEY patent_id (patent_id)
		) {$charset_collate};";
	}
	
	/**
	 * Due diligence table schema
	 */
	private function due_diligence_table() {
		$table_name = $this->get_table_prefix() . 'due_diligence';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			portfolio_id bigint(20) unsigned NOT NULL,
			company_id bigint(20) unsigned,
			title varchar(255) NOT NULL,
			description text,
			status varchar(50) DEFAULT 'in_progress',
			priority varchar(50) DEFAULT 'medium',
			assigned_to bigint(20) unsigned,
			start_date date,
			due_date date,
			completed_date date,
			findings text,
			recommendations text,
			meta_data longtext,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY portfolio_id (portfolio_id),
			KEY company_id (company_id),
			KEY status (status),
			KEY assigned_to (assigned_to),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Claim charts table schema
	 */
	private function claim_charts_table() {
		$table_name = $this->get_table_prefix() . 'claim_charts';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			due_diligence_id bigint(20) unsigned NOT NULL,
			patent_id bigint(20) unsigned NOT NULL,
			claim_number varchar(50) NOT NULL,
			claim_text text,
			product_feature text,
			evidence text,
			evidence_url varchar(500),
			confidence_level varchar(50),
			notes text,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY due_diligence_id (due_diligence_id),
			KEY patent_id (patent_id),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Customers table schema
	 */
	private function customers_table() {
		$table_name = $this->get_table_prefix() . 'customers';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			company_id bigint(20) unsigned,
			customer_type varchar(50) DEFAULT 'individual',
			status varchar(50) DEFAULT 'active',
			billing_address_line1 varchar(255),
			billing_address_line2 varchar(255),
			billing_city varchar(100),
			billing_state varchar(100),
			billing_country varchar(100),
			billing_postal_code varchar(20),
			tax_id varchar(100),
			payment_terms varchar(100),
			notes text,
			meta_data longtext,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id),
			KEY company_id (company_id),
			KEY status (status),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Licenses table schema
	 */
	private function licenses_table() {
		$table_name = $this->get_table_prefix() . 'licenses';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_number varchar(100) NOT NULL,
			portfolio_id bigint(20) unsigned NOT NULL,
			customer_id bigint(20) unsigned NOT NULL,
			license_type varchar(50),
			status varchar(50) DEFAULT 'draft',
			start_date date,
			end_date date,
			territory varchar(255),
			field_of_use text,
			license_fee decimal(10,2),
			royalty_rate decimal(5,2),
			minimum_royalty decimal(10,2),
			currency varchar(10) DEFAULT 'USD',
			payment_schedule varchar(100),
			exclusivity varchar(50),
			sublicensing_allowed tinyint(1) DEFAULT 0,
			contract_url varchar(500),
			notes text,
			meta_data longtext,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			signed_at datetime,
			PRIMARY KEY  (id),
			UNIQUE KEY license_number (license_number),
			KEY portfolio_id (portfolio_id),
			KEY customer_id (customer_id),
			KEY status (status),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * License payments table schema
	 */
	private function license_payments_table() {
		$table_name = $this->get_table_prefix() . 'license_payments';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_id bigint(20) unsigned NOT NULL,
			payment_type varchar(50) NOT NULL,
			amount decimal(10,2) NOT NULL,
			currency varchar(10) DEFAULT 'USD',
			due_date date,
			paid_date date,
			status varchar(50) DEFAULT 'pending',
			payment_method varchar(50),
			transaction_id varchar(255),
			notes text,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY license_id (license_id),
			KEY status (status),
			KEY due_date (due_date),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Documents table schema
	 */
	private function documents_table() {
		$table_name = $this->get_table_prefix() . 'documents';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text,
			document_type varchar(50),
			entity_type varchar(50),
			entity_id bigint(20) unsigned,
			file_name varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_size bigint(20),
			mime_type varchar(100),
			storage_type varchar(50) DEFAULT 'local',
			external_id varchar(255),
			version varchar(20) DEFAULT '1.0',
			is_current tinyint(1) DEFAULT 1,
			uploaded_by bigint(20) unsigned NOT NULL,
			uploaded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY entity (entity_type, entity_id),
			KEY document_type (document_type),
			KEY uploaded_by (uploaded_by)
		) {$charset_collate};";
	}
	
	/**
	 * Activity log table schema
	 */
	private function activity_log_table() {
		$table_name = $this->get_table_prefix() . 'activity_log';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			action varchar(100) NOT NULL,
			entity_type varchar(50),
			entity_id bigint(20) unsigned,
			description text,
			meta_data longtext,
			ip_address varchar(45),
			user_agent varchar(255),
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action),
			KEY entity (entity_type, entity_id),
			KEY created_at (created_at)
		) {$charset_collate};";
	}
	
	/**
	 * Invitations table schema
	 */
	private function invitations_table() {
		$table_name = $this->get_table_prefix() . 'invitations';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			portfolio_id bigint(20) unsigned NOT NULL,
			email varchar(255) NOT NULL,
			token varchar(255) NOT NULL,
			status varchar(50) DEFAULT 'pending',
			expires_at datetime NOT NULL,
			accepted_at datetime,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY portfolio_id (portfolio_id),
			KEY email (email),
			KEY status (status),
			KEY created_by (created_by)
		) {$charset_collate};";
	}
	
	/**
	 * Saved searches table schema
	 */
	private function saved_searches_table() {
		$table_name = $this->get_table_prefix() . 'saved_searches';
		$charset_collate = $this->get_charset_collate();
		
		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			name varchar(255) NOT NULL,
			search_type varchar(50) NOT NULL,
			criteria longtext NOT NULL,
			is_public tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY search_type (search_type)
		) {$charset_collate};";
	}
	
	/**
	 * Get charset collate
	 *
	 * @return string
	 */
	private function get_charset_collate() {
		global $wpdb;
		return $wpdb->get_charset_collate();
	}
}
