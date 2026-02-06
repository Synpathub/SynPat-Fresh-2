<?php
/**
 * The core plugin class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core;

/**
 * Main Plugin Class (Singleton)
 */
class Plugin {
	
	/**
	 * The single instance of the class.
	 *
	 * @var Plugin
	 */
	private static $instance = null;
	
	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var Loader
	 */
	protected $loader;
	
	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;
	
	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;
	
	/**
	 * Module instances
	 *
	 * @var array
	 */
	protected $modules = array();
	
	/**
	 * Service instances
	 *
	 * @var array
	 */
	protected $services = array();
	
	/**
	 * Get the singleton instance
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		$this->plugin_name = 'synpat-platform';
		$this->version     = SYNPAT_PLATFORM_VERSION;
		
		$this->load_dependencies();
		$this->set_locale();
		$this->init_core_services();
		$this->init_modules();
		$this->define_admin_hooks();
		$this->define_frontend_hooks();
	}
	
	/**
	 * Load the required dependencies.
	 */
	private function load_dependencies() {
		$this->loader = new Loader();
	}
	
	/**
	 * Define the locale for this plugin for internationalization.
	 */
	private function set_locale() {
		$plugin_i18n = new I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}
	
	/**
	 * Initialize core services
	 */
	private function init_core_services() {
		// Initialize cache manager
		$this->services['cache'] = new Cache\Cache_Manager();
		
		// Initialize event dispatcher
		$this->services['events'] = new Events\Event_Dispatcher();
		
		// Initialize activity logger
		$this->services['logger'] = new Logger\Activity_Logger();
		
		// Initialize security
		$this->services['security'] = new Security();
	}
	
	/**
	 * Initialize all modules
	 */
	private function init_modules() {
		// Company module (foundation for others)
		$this->modules['company'] = new \SynPat\Modules\Company\Company_Module();
		
		// Patent module
		$this->modules['patent'] = new \SynPat\Modules\Patent\Patent_Module();
		
		// Portfolio module
		$this->modules['portfolio'] = new \SynPat\Modules\Portfolio\Portfolio_Module();
		
		// Due diligence module
		$this->modules['due_diligence'] = new \SynPat\Modules\DueDiligence\DueDiligence_Module();
		
		// Licensing module
		$this->modules['licensing'] = new \SynPat\Modules\Licensing\Licensing_Module();
		
		// Document module
		$this->modules['document'] = new \SynPat\Modules\Document\Document_Module();
		
		// PDF module
		$this->modules['pdf'] = new \SynPat\Modules\PDF\PDF_Module();
		
		// Search module
		$this->modules['search'] = new \SynPat\Modules\Search\Search_Module();
		
		// Analytics module
		$this->modules['analytics'] = new \SynPat\Modules\Analytics\Analytics_Module();
		
		// Initialize each module
		foreach ( $this->modules as $module ) {
			$module->init( $this->loader );
		}
	}
	
	/**
	 * Register all admin-related hooks
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		
		$admin = new \SynPat\Admin\Admin( $this->get_plugin_name(), $this->get_version() );
		
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $admin, 'register_menu' );
		
		// AJAX handler
		$ajax = new \SynPat\Admin\Ajax_Handler();
		$ajax->init( $this->loader );
	}
	
	/**
	 * Register all frontend hooks
	 */
	private function define_frontend_hooks() {
		$frontend = new \SynPat\Frontend\Frontend( $this->get_plugin_name(), $this->get_version() );
		
		$this->loader->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_scripts' );
		
		// Initialize shortcodes
		$shortcodes = new \SynPat\Frontend\Shortcodes();
		$shortcodes->init();
	}
	
	/**
	 * Run the loader to execute all hooks
	 */
	public function run() {
		$this->loader->run();
	}
	
	/**
	 * Get a module by key
	 *
	 * @param string $key Module key
	 * @return object|null
	 */
	public function get_module( $key ) {
		return isset( $this->modules[ $key ] ) ? $this->modules[ $key ] : null;
	}
	
	/**
	 * Get a service by key
	 *
	 * @param string $key Service key
	 * @return object|null
	 */
	public function get_service( $key ) {
		return isset( $this->services[ $key ] ) ? $this->services[ $key ] : null;
	}
	
	/**
	 * Magic method to provide direct access to modules
	 *
	 * @param string $name Module name
	 * @return object|null
	 */
	public function __call( $name, $arguments ) {
		// Map method names to module keys
		$module_map = array(
			'portfolio'      => 'portfolio',
			'patent'         => 'patent',
			'company'        => 'company',
			'due_diligence'  => 'due_diligence',
			'licensing'      => 'licensing',
			'document'       => 'document',
			'pdf'            => 'pdf',
			'search'         => 'search',
			'analytics'      => 'analytics',
		);
		
		if ( isset( $module_map[ $name ] ) ) {
			return $this->get_module( $module_map[ $name ] );
		}
		
		return null;
	}
	
	/**
	 * Get the plugin name
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}
	
	/**
	 * Get the plugin version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
	
	/**
	 * Get the loader
	 *
	 * @return Loader
	 */
	public function get_loader() {
		return $this->loader;
	}
	
	/**
	 * Get cache manager
	 *
	 * @return Cache\Cache_Manager
	 */
	public function cache() {
		return $this->get_service( 'cache' );
	}
	
	/**
	 * Get event dispatcher
	 *
	 * @return Events\Event_Dispatcher
	 */
	public function events() {
		return $this->get_service( 'events' );
	}
	
	/**
	 * Get activity logger
	 *
	 * @return Logger\Activity_Logger
	 */
	public function logger() {
		return $this->get_service( 'logger' );
	}
	
	/**
	 * Get security service
	 *
	 * @return Security
	 */
	public function security() {
		return $this->get_service( 'security' );
	}
}
