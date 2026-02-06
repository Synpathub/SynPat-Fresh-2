<?php
/**
 * Abstract base class for all modules.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules;

use SynPat\Core\Loader;

/**
 * Base_Module Abstract Class
 */
abstract class Base_Module {
	
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name;
	
	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version;
	
	/**
	 * Loader instance
	 *
	 * @var Loader
	 */
	protected $loader;
	
	/**
	 * Initialize the module
	 *
	 * @param Loader $loader Loader instance
	 */
	abstract public function init( Loader $loader );
	
	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}
	
	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
	
	/**
	 * Register hooks
	 */
	protected function register_hooks() {
		// Override in child classes
	}
	
	/**
	 * Get service instance
	 *
	 * @return object Service instance
	 */
	abstract public function get_service();
}
