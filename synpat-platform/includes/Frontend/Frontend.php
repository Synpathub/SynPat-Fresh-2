<?php
/**
 * Frontend Bootstrap Class
 *
 * Main frontend bootstrap class that handles enqueuing of frontend assets
 * and localization of scripts for AJAX functionality.
 *
 * @package SynPat
 * @subpackage Frontend
 * @since 1.0.0
 */

namespace SynPat\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class
 *
 * Handles all frontend-related functionality including asset enqueuing
 * and script localization.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Plugin version.
	 * @param string $plugin_url Plugin URL.
	 */
	public function __construct( $version = '1.0.0', $plugin_url = '' ) {
		$this->version    = $version;
		$this->plugin_url = $plugin_url;
	}

	/**
	 * Initialize frontend functionality
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue frontend styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'synpat-frontend',
			$this->plugin_url . 'assets/css/frontend.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueue frontend scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'synpat-frontend',
			$this->plugin_url . 'assets/js/frontend/frontend.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Localize script with AJAX URL and nonce.
		wp_localize_script(
			'synpat-frontend',
			'synpatFrontend',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'synpat_frontend_nonce' ),
				'restUrl'   => esc_url_raw( rest_url() ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Get plugin version
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin version.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get plugin URL
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin URL.
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}
}
