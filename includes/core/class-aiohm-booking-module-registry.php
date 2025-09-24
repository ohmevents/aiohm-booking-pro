<?php
/**
 * Central registry for all AIOHM Booking modules
 * Manages module loading, initialization, and lifecycle.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since 1.2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central registry for all AIOHM Booking modules
 * Manages module loading, initialization, and lifecycle.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since 1.2.6
 */
class AIOHM_BOOKING_Module_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var AIOHM_BOOKING_Module_Registry|null
	 */
	private static $instance = null;

	/**
	 * Loaded modules.
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Module definitions.
	 *
	 * @var array
	 */
	private $module_definitions = array();

	/**
	 * Whether modules have been loaded.
	 *
	 * @var bool
	 */
	private $modules_loaded = false;

	/**
	 * Get singleton instance.
	 *
	 * @return AIOHM_BOOKING_Module_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize module registry.
	 */
	private function __construct() {
		// Clear module cache on initialization to ensure fresh discovery.
		$this->clear_module_cache();

		add_action( 'init', array( $this, 'load_modules' ), 20 ); // Load after main plugin initialization and textdomain loading.
		add_action( 'admin_init', array( $this, 'load_modules' ), 5 ); // Load early in admin area.
		add_action( 'activated_plugin', array( $this, 'clear_module_cache' ) );
		add_action( 'deactivated_plugin', array( $this, 'clear_module_cache' ) );
		add_action( 'upgrader_process_complete', array( $this, 'clear_module_cache' ) );
	}

	/**
	 * Discover available modules in the modules directory.
	 *
	 * @return array List of discovered module files
	 */
	private function discover_modules() {
		// Try to get the list of module files from cache.
		$cached_modules = get_transient( 'aiohm_booking_module_list' );
		if ( false !== $cached_modules ) {
			return $cached_modules;
		}

		$module_files = array();
		$modules_dir  = AIOHM_BOOKING_DIR . 'includes/modules/';

		if ( ! is_dir( $modules_dir ) ) {
			return $module_files;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $modules_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		$max_files  = 50; // Limit to prevent memory issues.
		$file_count = 0;

		foreach ( $iterator as $file ) {
			if ( $file_count >= $max_files ) {
				break; // Prevent processing too many files.
			}

			// Skip files in dev directories.
			$relative_path = str_replace( $modules_dir, '', $file->getPathname() );
			if ( strpos( $relative_path, '/dev/' ) !== false || strpos( $relative_path, '/dev-disabled/' ) !== false ) {
				continue;
			}

			// Check if module directory exists - skip loading if folder is missing.
			if ( ! $this->is_module_directory_available( $file->getPathname() ) ) {
				continue;
			}

			if ( $file->isFile() && preg_match( '/class-aiohm-booking-module-.*\.php$/', $file->getFilename() ) ) {

				// From the file path, derive the class name.
				// e.g., /path/to/wp-content/plugins/aiohm-booking/includes/modules/booking/class-aiohm-booking-module-accommodation.php
				// becomes AIOHM_BOOKING_Module_Accommodation.
				$module_name = str_replace( '.php', '', str_replace( 'class-aiohm-booking-module-', '', $file->getFilename() ) );

				// Handle special cases for acronyms.
				$special_cases = array(
					'openai'           => 'OpenAI',
					'gemini'           => 'Gemini',
					'shareai'          => 'ShareAI',
					'ollama'           => 'Ollama',
					'stripe'           => 'Stripe',
					'paypal'           => 'PayPal',
					'facebook'         => 'Facebook',
					'eventon'          => 'EventOn',
					'accommodation'    => 'Accommodation',
					'notifications'    => 'Notifications',
					'orders'           => 'Orders',
					'tickets'          => 'Tickets',
					'calendar'         => 'Calendar',
					'settings'         => 'Settings',
					'help'             => 'Help',
					'css_manager'      => 'CSS_Manager',
					'ai_analytics'     => 'AI_Analytics',
					'booking_settings' => 'Booking_Settings',
				);

				if ( isset( $special_cases[ $module_name ] ) ) {
					$class_name = 'AIOHM_BOOKING_Module_' . $special_cases[ $module_name ];
				} else {
					$class_name = str_replace( '-', '_', ucwords( str_replace( '_', '-', $module_name ), '-' ) );
					$class_name = 'AIOHM_BOOKING_Module_' . $class_name;
				}

				$module_files[] = array(
					'path'       => $file->getPathname(),
					'class_name' => $class_name,
				);

				++$file_count;
			}
		}

		// Store the discovered modules in a transient for 12 hours.
		set_transient( 'aiohm_booking_module_list', $module_files, 12 * HOUR_IN_SECONDS );

		return $module_files;
	}

	/**
	 * Load all available modules.
	 */
	public function load_modules() {
		if ( $this->modules_loaded ) {
			return; // Already loaded.
		}

		// First load the abstract base classes.
		include_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-module.php';
		include_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-settings-module.php';
		include_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-admin-module.php';

		// Load AI provider abstract classes for AI modules.
		include_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-ai-provider.php';
		include_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-ai-provider-module.php';

		$discovered_modules = $this->discover_modules();

		foreach ( $discovered_modules as $module_file ) {
			if ( file_exists( $module_file['path'] ) ) {
				include_once $module_file['path'];

				$class_name = $module_file['class_name'];
				if ( class_exists( $class_name ) && is_subclass_of( $class_name, 'AIOHM_BOOKING_Module_Abstract' ) && ! is_subclass_of( $class_name, 'AIOHM_Booking_Admin_Module' ) ) {
					if ( method_exists( $class_name, 'get_ui_definition' ) ) {
						try {
							$definition = $class_name::get_ui_definition();
							if ( is_array( $definition ) && ! empty( $definition ) && isset( $definition['id'] ) ) {
								$module_id                              = $definition['id'];
								$this->module_definitions[ $module_id ] = array_merge(
									$definition,
									array(
										'class' => $class_name,
										'file'  => str_replace( AIOHM_BOOKING_DIR . 'includes/', '', $module_file['path'] ),
									)
								);

								// Check if this is a premium module and user doesn't have premium access
								$is_premium_module  = isset( $definition['is_premium'] ) && $definition['is_premium'];
								$has_premium_access = function_exists( 'aiohm_booking_fs' ) && (aiohm_booking_fs()->is_paying() || aiohm_booking_fs()->is_premium());


								if ( ! $is_premium_module || $has_premium_access ) {
									$this->modules[ $module_id ] = new $class_name();
									do_action( 'aiohm_booking_module_loaded', $module_id, $this->modules[ $module_id ] );
								} else {
									// Skip loading premium module for free users
								}
							}
						} catch ( Exception $e ) {
							// Log error but continue loading other modules.
						}
					}
				}
			}
		}

		do_action( 'aiohm_booking_modules_loaded', $this->modules );

		$this->modules_loaded = true;
	}

	/**
	 * Get a specific module by ID.
	 *
	 * @param string $module_id The module ID to retrieve.
	 *
	 * @return mixed|null The module instance or null if not found.
	 */
	public function get_module( $module_id ) {
		$module = $this->modules[ $module_id ] ?? null;
		return $module;
	}

	/**
	 * Get all loaded modules.
	 *
	 * @return array Array of loaded modules.
	 */
	public function get_all_modules() {
		return $this->modules;
	}

	/**
	 * Get all module definitions.
	 *
	 * @return array Array of module definitions.
	 */
	public function get_module_definitions() {
		return $this->module_definitions;
	}

	/**
	 * Get all enabled modules.
	 *
	 * @return array Array of enabled modules.
	 */
	public function get_enabled_modules() {
		$enabled_modules = array();

		foreach ( $this->modules as $module_id => $module ) {
			if ( $this->is_module_enabled( $module_id ) ) {
				$enabled_modules[ $module_id ] = $module;
			}
		}

		return $enabled_modules;
	}

	/**
	 * Get all premium modules.
	 *
	 * @return array Array of premium modules.
	 */
	public function get_premium_modules() {
		return array_filter(
			$this->modules,
			function ( $module ) {
				return $module->is_premium();
			}
		);
	}

	/**
	 * Get all free modules.
	 *
	 * @return array Array of free modules.
	 */
	public function get_free_modules() {
		return array_filter(
			$this->modules,
			function ( $module ) {
				return ! $module->is_premium();
			}
		);
	}

	/**
	 * Get modules for settings page.
	 *
	 * @return array Array of modules ordered for settings display.
	 */
	public function get_modules_for_settings() {
		// Return modules in order they should appear in settings.
		$ordered_modules = array();
		// Note: priority sorting is not yet implemented with dynamic loading.
		// This can be added later if needed.
		foreach ( $this->module_definitions as $module_id => $config ) {
			if ( isset( $this->modules[ $module_id ] ) ) {
				$ordered_modules[ $module_id ] = $this->modules[ $module_id ];
			}
		}
		return $ordered_modules;
	}

	/**
	 * Register a module instance.
	 *
	 * @param string $module_id The module ID.
	 * @param mixed  $module_instance The module instance.
	 *
	 * @return bool True if registered successfully, false otherwise.
	 */
	public function register_module( $module_id, $module_instance ) {
		if ( $module_instance instanceof AIOHM_BOOKING_Module_Abstract ) {
			$this->modules[ $module_id ] = $module_instance;
			return true;
		}
		return false;
	}

	/**
	 * Unregister a module.
	 *
	 * @param string $module_id The module ID to unregister.
	 */
	public function unregister_module( $module_id ) {
		unset( $this->modules[ $module_id ] );
	}

	/**
	 * Check if a module exists.
	 *
	 * @param string $module_id The module ID to check.
	 *
	 * @return bool True if module exists, false otherwise.
	 */
	public function module_exists( $module_id ) {
		return isset( $this->modules[ $module_id ] );
	}

	/**
	 * Check if a module is enabled.
	 *
	 * @param string $module_id The module ID to check.
	 *
	 * @return bool True if module is enabled, false otherwise.
	 */
	public function is_module_enabled( $module_id ) {
		if ( ! $this->module_exists( $module_id ) ) {
			return false;
		}

		// Get the module instance
		$module = $this->get_module( $module_id );
		if ( ! $module ) {
			return false;
		}

		// Check if module dependencies are met
		if ( ! $module->check_dependencies() ) {
			return false;
		}

		// Check if this is a PRO module and if PRO features are available
		if ( AIOHM_BOOKING_Utilities::is_pro_module( $module_id ) && AIOHM_BOOKING_Utilities::is_free_version() ) {
			return false;
		}

		// All modules are enabled if dependencies are met.
		return true;
	}

	/**
	 * Get detailed information about a module.
	 *
	 * @param string $module_id The module ID.
	 * @param array  $settings Optional settings array.
	 *
	 * @return array|null Module information or null if not found.
	 */
	public function get_module_info( $module_id, $settings = null ) {
		if ( ! isset( $this->module_definitions[ $module_id ] ) ) {
			return null;
		}

		// Load settings if not provided.
		if ( null === $settings ) {
			$settings = get_option( 'aiohm_booking_settings', array() );
		}

		$config = $this->module_definitions[ $module_id ];
		$module = $this->modules[ $module_id ] ?? null;

		$module_info = array(
			'id'                  => $module_id,
			'class'               => $config['class'],
			'file'                => $config['file'],
			'access_level'        => $config['access_level'] ?? 'free',
			'category'            => $config['category'] ?? 'general',
			'is_premium'          => $config['is_premium'],
			'priority'            => $config['priority'] ?? 999,
			'has_settings'        => ! empty( $config['has_settings'] ),
			'has_admin_page'      => ! empty( $config['has_admin_page'] ),
			'hidden_in_settings'  => ! empty( $config['hidden_in_settings'] ),
			'visible_in_settings' => $config['visible_in_settings'] ?? true,
			'system_module'       => ! empty( $config['system_module'] ),
			'loaded'              => null !== $module,
			'enabled'             => $this->is_module_enabled( $module_id ),
			'name'                => $module ? $module->get_module_name() : ucfirst( str_replace( '_', ' ', $module_id ) ),
			'description'         => $module ? $module->get_module_description() : '',
			// Expose explicit admin page slug from module definition when present.
			'admin_page_slug'     => $config['admin_page_slug'] ?? null,
		);

		return $module_info;
	}

	/**
	 * Get information for all modules.
	 *
	 * @return array Array of module information.
	 */
	public function get_all_modules_info() {
		$settings     = get_option( 'aiohm_booking_settings', array() );
		$modules_info = array();
		$max_modules  = 50; // Limit to prevent memory issues.
		$module_count = 0;

		foreach ( $this->module_definitions as $module_id => $config ) {
			if ( $module_count >= $max_modules ) {
				break; // Prevent processing too many modules.
			}

			$modules_info[ $module_id ] = $this->get_module_info( $module_id, $settings );
			++$module_count;
		}
		return $modules_info;
	}

	/**
	 * Get module instance (compatibility method).
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return mixed|null The module instance or null if not found.
	 */
	public static function get_module_instance( $module_id ) {
		return self::instance()->get_module( $module_id );
	}

	/**
	 * Check if module directory is available for loading.
	 *
	 * This method implements conditional module loading by checking if the module's
	 * parent directory exists. If a module folder is deleted, the module won't load.
	 *
	 * @param string $module_file_path Path to the module file.
	 * @return bool True if module directory exists and should be loaded.
	 * @since 1.2.4
	 */
	private function is_module_directory_available( $module_file_path ) {
		$module_dir = dirname( $module_file_path );

		// Always allow core modules (not in subdirectories).
		$modules_base_dir = AIOHM_BOOKING_DIR . 'includes/modules/';
		$relative_path    = str_replace( $modules_base_dir, '', $module_dir );

		// If it's directly in modules/ (no subdirectory), always load.
		if ( strpos( $relative_path, '/' ) === false ) {
			return true;
		}

		// For modules in subdirectories, check if directory exists.
		return is_dir( $module_dir );
	}

	/**
	 * Check if a specific module type is available.
	 *
	 * @param string $module_type Module type (e.g., 'stripe', 'paypal').
	 * @return bool True if module is available.
	 * @since 1.2.4
	 */
	public function is_module_type_available( $module_type ) {
		$modules_dir = AIOHM_BOOKING_DIR . 'includes/modules/';

		// Check common locations for the module.
		$possible_paths = array(
			$modules_dir . 'payments/' . $module_type . '/',
			$modules_dir . $module_type . '/',
			$modules_dir . 'booking/' . $module_type . '/',
			$modules_dir . 'ai/' . $module_type . '/',
		);

		foreach ( $possible_paths as $path ) {
			if ( is_dir( $path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get list of missing optional modules.
	 *
	 * @return array List of missing module types.
	 * @since 1.2.4
	 */
	public function get_missing_optional_modules() {
		$optional_modules = array_merge( AIOHM_BOOKING_Utilities::get_pro_modules(), array( 'dev' ) );
		$missing          = array();

		foreach ( $optional_modules as $module_type ) {
			if ( ! $this->is_module_type_available( $module_type ) ) {
				$missing[] = $module_type;
			}
		}

		return $missing;
	}

	/**
	 * Clears the module cache transient.
	 *
	 * This is hooked to actions like plugin activation/deactivation to ensure
	 * the module list is refreshed.
	 *
	 * @since 1.0.0
	 */
	public function clear_module_cache() {
		delete_transient( 'aiohm_booking_module_list' );

		// Also clear utilities cache
		if ( class_exists( 'AIOHM_BOOKING_Utilities' ) ) {
			AIOHM_BOOKING_Utilities::clear_module_cache();
		}
	}
}
