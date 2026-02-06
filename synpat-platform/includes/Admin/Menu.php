<?php
/**
 * Menu Registration Class
 *
 * Handles registration of admin menu and submenu pages.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Admin;

/**
 * Menu Class
 */
class Menu {

	/**
	 * Menu slug
	 *
	 * @var string
	 */
	private $menu_slug = 'synpat';

	/**
	 * Capability required to access menu
	 *
	 * @var string
	 */
	private $capability = 'manage_options';

	/**
	 * Register the admin menu.
	 */
	public function register() {
		// Main menu
		add_menu_page(
			__( 'SynPat Platform', 'synpat-platform' ),
			__( 'SynPat', 'synpat-platform' ),
			$this->capability,
			$this->menu_slug,
			array( $this, 'render_dashboard_page' ),
			$this->get_menu_icon(),
			30
		);

		// Dashboard submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Dashboard', 'synpat-platform' ),
			__( 'Dashboard', 'synpat-platform' ),
			$this->capability,
			$this->menu_slug,
			array( $this, 'render_dashboard_page' )
		);

		// Portfolios submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Portfolios', 'synpat-platform' ),
			__( 'Portfolios', 'synpat-platform' ),
			$this->capability,
			'synpat-portfolios',
			array( $this, 'render_portfolios_page' )
		);

		// Patents submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Patents', 'synpat-platform' ),
			__( 'Patents', 'synpat-platform' ),
			$this->capability,
			'synpat-patents',
			array( $this, 'render_patents_page' )
		);

		// Due Diligence submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Due Diligence', 'synpat-platform' ),
			__( 'Due Diligence', 'synpat-platform' ),
			$this->capability,
			'synpat-due-diligence',
			array( $this, 'render_due_diligence_page' )
		);

		// Licenses submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Licenses', 'synpat-platform' ),
			__( 'Licenses', 'synpat-platform' ),
			$this->capability,
			'synpat-licenses',
			array( $this, 'render_licenses_page' )
		);

		// Customers submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Customers', 'synpat-platform' ),
			__( 'Customers', 'synpat-platform' ),
			$this->capability,
			'synpat-customers',
			array( $this, 'render_customers_page' )
		);

		// Companies submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Companies', 'synpat-platform' ),
			__( 'Companies', 'synpat-platform' ),
			$this->capability,
			'synpat-companies',
			array( $this, 'render_companies_page' )
		);

		// Reports submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Reports', 'synpat-platform' ),
			__( 'Reports', 'synpat-platform' ),
			$this->capability,
			'synpat-reports',
			array( $this, 'render_reports_page' )
		);

		// Settings submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Settings', 'synpat-platform' ),
			__( 'Settings', 'synpat-platform' ),
			$this->capability,
			'synpat-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Get menu icon.
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		return 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
				<path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
				<path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
			</svg>'
		);
	}

	/**
	 * Render Dashboard page.
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$dashboard = new Dashboard();
		$dashboard->render();
	}

	/**
	 * Render Portfolios page.
	 */
	public function render_portfolios_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'portfolios' );
	}

	/**
	 * Render Patents page.
	 */
	public function render_patents_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'patents' );
	}

	/**
	 * Render Due Diligence page.
	 */
	public function render_due_diligence_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'due-diligence' );
	}

	/**
	 * Render Licenses page.
	 */
	public function render_licenses_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'licenses' );
	}

	/**
	 * Render Customers page.
	 */
	public function render_customers_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'customers' );
	}

	/**
	 * Render Companies page.
	 */
	public function render_companies_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'companies' );
	}

	/**
	 * Render Reports page.
	 */
	public function render_reports_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$this->render_page_template( 'reports' );
	}

	/**
	 * Render Settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$settings = new Settings();
		$settings->render();
	}

	/**
	 * Render page template.
	 *
	 * @param string $page Page slug.
	 */
	private function render_page_template( $page ) {
		$template_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . "templates/admin/{$page}.php";

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wrap">';
			echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
			echo '<p>' . esc_html__( 'This page is under development.', 'synpat-platform' ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * Get menu slug.
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}
}
