<?php
/**
 * SynPat Platform
 *
 * @package           SynPatPlatform
 * @author            SynPat
 * @copyright         2024 SynPat
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       SynPat Platform
 * Plugin URI:        https://synpat.com
 * Description:       Complete WordPress plugin for patent portfolio management, due diligence, and licensing
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            SynPat
 * Author URI:        https://synpat.com
 * Text Domain:       synpat-platform
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'SYNPAT_PLATFORM_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'SYNPAT_PLATFORM_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'SYNPAT_PLATFORM_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'SYNPAT_PLATFORM_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'SYNPAT_PLATFORM_MIN_PHP', '8.0' );

/**
 * Minimum WordPress version required.
 */
define( 'SYNPAT_PLATFORM_MIN_WP', '6.0' );

/**
 * Check PHP and WordPress version compatibility
 */
function synpat_platform_check_requirements() {
	$php_version = phpversion();
	$wp_version  = get_bloginfo( 'version' );
	
	$errors = array();
	
	if ( version_compare( $php_version, SYNPAT_PLATFORM_MIN_PHP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Current PHP version, 2: Required PHP version */
			__( 'SynPat Platform requires PHP version %2$s or higher. You are running version %1$s.', 'synpat-platform' ),
			$php_version,
			SYNPAT_PLATFORM_MIN_PHP
		);
	}
	
	if ( version_compare( $wp_version, SYNPAT_PLATFORM_MIN_WP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Current WordPress version, 2: Required WordPress version */
			__( 'SynPat Platform requires WordPress version %2$s or higher. You are running version %1$s.', 'synpat-platform' ),
			$wp_version,
			SYNPAT_PLATFORM_MIN_WP
		);
	}
	
	return $errors;
}

/**
 * The code that runs during plugin activation.
 */
function activate_synpat_platform() {
	// Check requirements
	$errors = synpat_platform_check_requirements();
	if ( ! empty( $errors ) ) {
		deactivate_plugins( SYNPAT_PLATFORM_BASENAME );
		wp_die( implode( '<br>', $errors ) );
	}
	
	require_once SYNPAT_PLATFORM_PATH . 'includes/Core/Activator.php';
	SynPat\Core\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_synpat_platform() {
	require_once SYNPAT_PLATFORM_PATH . 'includes/Core/Deactivator.php';
	SynPat\Core\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_synpat_platform' );
register_deactivation_hook( __FILE__, 'deactivate_synpat_platform' );

/**
 * Autoloader for plugin classes
 */
spl_autoload_register( function ( $class ) {
	// Only autoload classes in our namespace
	$namespace = 'SynPat\\';
	
	if ( strpos( $class, $namespace ) !== 0 ) {
		return;
	}
	
	// Remove namespace prefix
	$class = substr( $class, strlen( $namespace ) );
	
	// Convert namespace separators to directory separators
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
	
	// Build the file path
	$file = SYNPAT_PLATFORM_PATH . 'includes' . DIRECTORY_SEPARATOR . $class . '.php';
	
	// If the file exists, require it
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Begin execution of the plugin.
 */
function synpat_platform_init() {
	// Check requirements on every load
	$errors = synpat_platform_check_requirements();
	if ( ! empty( $errors ) ) {
		add_action( 'admin_notices', function() use ( $errors ) {
			echo '<div class="notice notice-error"><p>' . implode( '<br>', array_map( 'esc_html', $errors ) ) . '</p></div>';
		} );
		return;
	}
	
	require_once SYNPAT_PLATFORM_PATH . 'includes/Core/Plugin.php';
	
	// Initialize the plugin
	$plugin = SynPat\Core\Plugin::get_instance();
	$plugin->run();
}

add_action( 'plugins_loaded', 'synpat_platform_init' );

/**
 * Global accessor function for the plugin singleton
 *
 * @return SynPat\Core\Plugin
 */
function synpat() {
	return SynPat\Core\Plugin::get_instance();
}
