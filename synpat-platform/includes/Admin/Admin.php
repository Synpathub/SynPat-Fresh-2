<?php
/**
 * Admin Bootstrap Class
 *
 * Handles admin area functionality including styles, scripts, menus, and dashboard widgets.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Admin;

/**
 * Admin Class
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Menu instance
	 *
	 * @var Menu
	 */
	private $menu;

	/**
	 * Dashboard instance
	 *
	 * @var Dashboard
	 */
	private $dashboard;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->menu        = new Menu();
		$this->dashboard   = new Dashboard();
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		if ( ! $this->is_synpat_admin_page() ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_synpat_admin_page() ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/admin/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Localize script with AJAX URL and nonce
		wp_localize_script(
			$this->plugin_name,
			'synpatAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'synpat_admin_nonce' ),
				'pluginUrl' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ),
			)
		);
	}

	/**
	 * Register admin menu.
	 */
	public function register_menu() {
		$this->menu->register();
	}

	/**
	 * Check if current page is a SynPat admin page.
	 *
	 * @return bool
	 */
	private function is_synpat_admin_page() {
		$screen = get_current_screen();
		
		if ( ! $screen ) {
			return false;
		}

		return strpos( $screen->id, 'synpat' ) !== false || 
		       strpos( $screen->id, 'toplevel_page_synpat' ) !== false;
	}

	/**
	 * Get menu instance.
	 *
	 * @return Menu
	 */
	public function get_menu() {
		return $this->menu;
	}

	/**
	 * Get dashboard instance.
	 *
	 * @return Dashboard
	 */
	public function get_dashboard() {
		return $this->dashboard;
	}
}
