<?php
/**
 * Template Loader Class
 *
 * Handles loading of templates with theme override support.
 * Allows themes to override plugin templates by placing them in theme/synpat/ directory.
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
 * Template_Loader Class
 *
 * Implements a template hierarchy system that checks for templates in:
 * 1. Theme/child theme directory (theme/synpat/)
 * 2. Plugin templates directory (plugin/templates/)
 *
 * @since 1.0.0
 */
class Template_Loader {

	/**
	 * Plugin directory path
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Template directory name in theme
	 *
	 * @var string
	 */
	private $theme_template_dir = 'synpat';

	/**
	 * Template directory name in plugin
	 *
	 * @var string
	 */
	private $plugin_template_dir = 'templates';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_dir Plugin directory path.
	 */
	public function __construct( $plugin_dir = '' ) {
		$this->plugin_dir = $plugin_dir;
	}

	/**
	 * Get template part
	 *
	 * Loads a template part with theme override support.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Template slug (e.g., 'portfolio/list').
	 * @param string|null $name Template name/variation (e.g., 'grid').
	 * @param array  $args Optional. Additional arguments to pass to template.
	 * @return void
	 */
	public function get_template_part( $slug, $name = null, $args = array() ) {
		// Get the template file.
		$template = $this->locate_template( $slug, $name );

		// Allow filtering of the template path.
		$template = apply_filters( 'synpat_get_template_part', $template, $slug, $name, $args );

		if ( ! $template ) {
			return;
		}

		// Make args available to template.
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		// Load the template.
		do_action( 'synpat_before_template_part', $template, $slug, $name, $args );

		include $template;

		do_action( 'synpat_after_template_part', $template, $slug, $name, $args );
	}

	/**
	 * Locate template
	 *
	 * Searches for template files in the theme first, then falls back to plugin templates.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Template slug.
	 * @param string|null $name Template name/variation.
	 * @return string|false Template path if found, false otherwise.
	 */
	public function locate_template( $slug, $name = null ) {
		// Build template file names.
		$templates = array();

		if ( $name ) {
			$templates[] = "{$slug}-{$name}.php";
		}
		$templates[] = "{$slug}.php";

		// Allow filtering of template file names.
		$templates = apply_filters( 'synpat_locate_template_names', $templates, $slug, $name );

		// Look for templates.
		$located = '';

		foreach ( $templates as $template_name ) {
			// Check child theme.
			if ( file_exists( trailingslashit( get_stylesheet_directory() ) . $this->theme_template_dir . '/' . $template_name ) ) {
				$located = trailingslashit( get_stylesheet_directory() ) . $this->theme_template_dir . '/' . $template_name;
				break;
			}

			// Check parent theme.
			if ( file_exists( trailingslashit( get_template_directory() ) . $this->theme_template_dir . '/' . $template_name ) ) {
				$located = trailingslashit( get_template_directory() ) . $this->theme_template_dir . '/' . $template_name;
				break;
			}

			// Check plugin directory.
			if ( file_exists( trailingslashit( $this->plugin_dir ) . $this->plugin_template_dir . '/' . $template_name ) ) {
				$located = trailingslashit( $this->plugin_dir ) . $this->plugin_template_dir . '/' . $template_name;
				break;
			}
		}

		// Allow filtering of final template path.
		$located = apply_filters( 'synpat_locate_template', $located, $slug, $name );

		return $located;
	}

	/**
	 * Get template
	 *
	 * Loads and returns template content as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Template slug.
	 * @param string|null $name Template name/variation.
	 * @param array  $args Optional. Additional arguments to pass to template.
	 * @return string Template output.
	 */
	public function get_template( $slug, $name = null, $args = array() ) {
		ob_start();
		$this->get_template_part( $slug, $name, $args );
		return ob_get_clean();
	}

	/**
	 * Include template
	 *
	 * Directly includes a template file if it exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_path Relative path to template.
	 * @param array  $args Optional. Additional arguments to pass to template.
	 * @return bool True if template was included, false otherwise.
	 */
	public function include_template( $template_path, $args = array() ) {
		// Sanitize template path.
		$template_path = ltrim( $template_path, '/' );

		// Locate the template.
		$template = $this->locate_template_by_path( $template_path );

		if ( ! $template ) {
			return false;
		}

		// Make args available to template.
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		// Include the template.
		include $template;

		return true;
	}

	/**
	 * Locate template by path
	 *
	 * Locates a template file by its full path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_path Relative template path.
	 * @return string|false Full template path if found, false otherwise.
	 */
	private function locate_template_by_path( $template_path ) {
		// Check child theme.
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . $this->theme_template_dir . '/' . $template_path ) ) {
			return trailingslashit( get_stylesheet_directory() ) . $this->theme_template_dir . '/' . $template_path;
		}

		// Check parent theme.
		if ( file_exists( trailingslashit( get_template_directory() ) . $this->theme_template_dir . '/' . $template_path ) ) {
			return trailingslashit( get_template_directory() ) . $this->theme_template_dir . '/' . $template_path;
		}

		// Check plugin directory.
		if ( file_exists( trailingslashit( $this->plugin_dir ) . $this->plugin_template_dir . '/' . $template_path ) ) {
			return trailingslashit( $this->plugin_dir ) . $this->plugin_template_dir . '/' . $template_path;
		}

		return false;
	}

	/**
	 * Set plugin directory
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_dir Plugin directory path.
	 */
	public function set_plugin_dir( $plugin_dir ) {
		$this->plugin_dir = $plugin_dir;
	}

	/**
	 * Set theme template directory
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Theme template directory name.
	 */
	public function set_theme_template_dir( $dir ) {
		$this->theme_template_dir = $dir;
	}

	/**
	 * Get theme template directory
	 *
	 * @since 1.0.0
	 *
	 * @return string Theme template directory name.
	 */
	public function get_theme_template_dir() {
		return $this->theme_template_dir;
	}

	/**
	 * Get plugin template directory
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin template directory name.
	 */
	public function get_plugin_template_dir() {
		return $this->plugin_template_dir;
	}

	/**
	 * Check if template exists
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Template slug.
	 * @param string|null $name Template name/variation.
	 * @return bool True if template exists, false otherwise.
	 */
	public function template_exists( $slug, $name = null ) {
		$template = $this->locate_template( $slug, $name );
		return ! empty( $template );
	}
}
