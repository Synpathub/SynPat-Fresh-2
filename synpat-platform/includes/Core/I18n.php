<?php
/**
 * Define the internationalization functionality.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core;

/**
 * I18n Class
 */
class I18n {
	
	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'synpat-platform',
			false,
			dirname( SYNPAT_PLATFORM_BASENAME ) . '/languages/'
		);
	}
}
