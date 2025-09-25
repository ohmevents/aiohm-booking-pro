<?php
/**
 * Admin Modules Management Class
 *
 * Handles module coordination and management functionality for the admin interface.
 * Manages module availability, dependencies, coordination, and integration.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since  2.0.0
 *
 * @author OHM Events Agency <https://www.ohm.events>
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Modules Management Class.
 *
 * Provides module coordination, availability checking, dependency management,
 * and integration services for the admin interface.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Admin_Modules {

	/**
	 * Module registry instance.
	 *
	 * @var AIOHM_BOOKING_Module_Registry
	 */
	private $registry;

	/**
	 * Cached settings for performance.
	 *
	 * @var array|null
	 */
	private $cached_settings = null;

	/**
	 * Module dependencies map.
	 *
	 * @var array
	 */
	private $module_dependencies = array(
		'calendar' => array( 'accommodations', 'tickets' ),
		'orders'   => array( 'accommodations', 'tickets' ),
	);

	/**
	 * Module status cache.
	 *
	 * @var array
	 */
	private $status_cache = array();

	/**
	 * Constructor.
	 *
	 * @param AIOHM_BOOKING_Module_Registry $registry Module registry instance.
	 */
	public function __construct( AIOHM_BOOKING_Module_Registry $registry = null ) {
		$this->registry = $registry ? $registry : AIOHM_BOOKING_Module_Registry::instance();
	}

	/**
	 * Initialize the modules manager.
	 *
	 * @return void
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Clear caches when settings are updated.
		add_action( 'update_option_aiohm_booking_settings', array( $this, 'clear_caches' ) );
		add_action( 'aiohm_booking_modules_loaded', array( $this, 'clear_caches' ) );
	}

	/**
	 * Get all available modules with their status information.
	 *
	 * @return array Array of modules with status information
	 */
	public function get_modules_status() {
		if ( ! empty( $this->status_cache ) ) {
			return $this->status_cache;
		}

		$settings     = $this->get_settings();
		$modules_info = $this->registry->get_all_modules_info();
		$status       = array();

		foreach ( $modules_info as $module_id => $module_info ) {
			$status[ $module_id ] = array(
				'id'            => $module_id,
				'name'          => $module_info['name'],
				'description'   => $module_info['description'],
				'is_enabled'    => $this->is_module_enabled( $module_id ),
				'is_available'  => $this->is_module_available( $module_id ),
				'is_premium'    => $module_info['is_premium'],
				'has_settings'  => $module_info['has_settings'],
				'dependencies'  => $this->get_module_dependencies( $module_id ),
				'dependents'    => $this->get_module_dependents( $module_id ),
				'status_reason' => $this->get_module_status_reason( $module_id ),
			);
		}

		$this->status_cache = $status;
		return $status;
	}

	/**
	 * Check if a module is enabled in settings.
	 *
	 * @param string $module_id The module ID to check.
	 *
	 * @return bool True if module is enabled, false otherwise.
	 */
	public function is_module_enabled( $module_id ) {
		$settings = $this->get_settings();

		// Map module IDs to their setting keys.
		$setting_key = $this->get_module_setting_key( $module_id );

		if ( ! $setting_key ) {
			// If no setting key, assume it's always enabled (system modules).
			return true;
		}

		$enabled = $settings[ $setting_key ] ?? false;

		// Handle both boolean and string values.
		return ! empty( $enabled ) && '0' !== $enabled && false !== $enabled;
	}

	/**
	 * Check if a module is available (enabled and dependencies met).
	 *
	 * @param string $module_id The module ID to check.
	 *
	 * @return bool True if module is available, false otherwise.
	 */
	public function is_module_available( $module_id ) {
		// First check if module is enabled.
		if ( ! $this->is_module_enabled( $module_id ) ) {
			return false;
		}

		// Check if module exists in registry.
		if ( ! $this->registry->module_exists( $module_id ) ) {
			return false;
		}

		// Check dependencies.
		return $this->are_dependencies_met( $module_id );
	}

	/**
	 * Check if module dependencies are met.
	 *
	 * @param string $module_id The module ID to check dependencies for.
	 *
	 * @return bool True if dependencies are met, false otherwise.
	 */
	public function are_dependencies_met( $module_id ) {
		$dependencies = $this->get_module_dependencies( $module_id );

		if ( empty( $dependencies ) ) {
			return true;
		}

		// For OR dependencies (calendar needs accommodations OR tickets).
		if ( in_array( $module_id, array( 'calendar', 'orders' ), true ) ) {
			foreach ( $dependencies as $dependency ) {
				if ( $this->is_module_enabled( $dependency ) ) {
					return true;
				}
			}
			return false;
		}

		// For AND dependencies (all must be enabled).
		foreach ( $dependencies as $dependency ) {
			if ( ! $this->is_module_enabled( $dependency ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get module dependencies.
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return array Array of dependency module IDs.
	 */
	public function get_module_dependencies( $module_id ) {
		return $this->module_dependencies[ $module_id ] ?? array();
	}

	/**
	 * Get modules that depend on the given module.
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return array Array of dependent module IDs.
	 */
	public function get_module_dependents( $module_id ) {
		$dependents = array();

		foreach ( $this->module_dependencies as $dependent_id => $dependencies ) {
			if ( in_array( $module_id, $dependencies, true ) ) {
				$dependents[] = $dependent_id;
			}
		}

		return $dependents;
	}

	/**
	 * Get status reason for a module.
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return string Status reason message.
	 */
	public function get_module_status_reason( $module_id ) {
		if ( ! $this->registry->module_exists( $module_id ) ) {
			return __( 'Module not found', 'aiohm-booking-pro' );
		}

		if ( ! $this->is_module_enabled( $module_id ) ) {
			return __( 'Module disabled in settings', 'aiohm-booking-pro' );
		}

		if ( ! $this->are_dependencies_met( $module_id ) ) {
			$dependencies = $this->get_module_dependencies( $module_id );

			if ( in_array( $module_id, array( 'calendar', 'orders' ), true ) ) {
				$dep_names = array_map( array( $this, 'get_module_display_name' ), $dependencies );
				/* translators: %s: list of dependency names */
				return sprintf( __( 'Requires at least one of: %s', 'aiohm-booking-pro' ), implode( ', ', $dep_names ) );
			} else {
				$missing = array();
				foreach ( $dependencies as $dep ) {
					if ( ! $this->is_module_enabled( $dep ) ) {
						$missing[] = $this->get_module_display_name( $dep );
					}
				}
				/* translators: %s: list of missing dependency names */
				return sprintf( __( 'Missing dependencies: %s', 'aiohm-booking-pro' ), implode( ', ', $missing ) );
			}
		}

		return __( 'Available', 'aiohm-booking-pro' );
	}

	/**
	 * Get display name for a module.
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return string The module display name.
	 */
	public function get_module_display_name( $module_id ) {
		$display_names = array(
			'accommodations' => __( 'Accommodation', 'aiohm-booking-pro' ),
			'tickets'        => __( 'Event Tickets', 'aiohm-booking-pro' ),
			'calendar'       => __( 'Calendar', 'aiohm-booking-pro' ),
			'notifications'  => __( 'Notifications', 'aiohm-booking-pro' ),
			'orders'         => __( 'Orders', 'aiohm-booking-pro' ),
			'ai_analytics'   => __( 'AI Analytics', 'aiohm-booking-pro' ),
			'css_manager'    => __( 'CSS Design System', 'aiohm-booking-pro' ),
			'settings'       => __( 'Settings', 'aiohm-booking-pro' ),
		);

		return $display_names[ $module_id ] ?? ucfirst( str_replace( '_', ' ', $module_id ) );
	}

	/**
	 * Get setting key for a module.
	 *
	 * @param string $module_id The module ID.
	 *
	 * @return string|false The setting key or false if not found.
	 */
	private function get_module_setting_key( $module_id ) {
		$setting_keys = array(
			'accommodations' => 'enable_accommodations',
			'tickets'        => 'enable_tickets',
			'calendar'       => 'enable_calendar',
			'notifications'  => 'enable_notifications',
			'orders'         => 'enable_orders',
			'ai_analytics'   => 'enable_ai_analytics',
			'css_manager'    => 'enable_css_manager',
		);

		return $setting_keys[ $module_id ] ?? false;
	}

	/**
	 * Activate a module.
	 *
	 * @param string $module_id The module ID to activate.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function activate_module( $module_id ) {
		// Check if module exists.
		if ( ! $this->registry->module_exists( $module_id ) ) {
			return new WP_Error( 'module_not_found', __( 'Module not found', 'aiohm-booking-pro' ) );
		}

		// Get setting key.
		$setting_key = $this->get_module_setting_key( $module_id );
		if ( ! $setting_key ) {
			return new WP_Error( 'no_setting_key', __( 'Module cannot be activated (system module)', 'aiohm-booking-pro' ) );
		}

		// Update settings.
		$settings                 = $this->get_settings();
		$settings[ $setting_key ] = true;
		$result                   = update_option( 'aiohm_booking_settings', $settings );

		if ( $result ) {
			$this->clear_caches();
			do_action( 'aiohm_booking_module_activated', $module_id );
			return true;
		}

		return new WP_Error( 'activation_failed', __( 'Failed to activate module', 'aiohm-booking-pro' ) );
	}

	/**
	 * Deactivate a module.
	 *
	 * @param string $module_id The module ID to deactivate.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function deactivate_module( $module_id ) {
		// Check if module exists.
		if ( ! $this->registry->module_exists( $module_id ) ) {
			return new WP_Error( 'module_not_found', __( 'Module not found', 'aiohm-booking-pro' ) );
		}

		// Check for dependents.
		$dependents        = $this->get_module_dependents( $module_id );
		$active_dependents = array();

		foreach ( $dependents as $dependent ) {
			if ( $this->is_module_enabled( $dependent ) ) {
				$active_dependents[] = $this->get_module_display_name( $dependent );
			}
		}

		if ( ! empty( $active_dependents ) ) {
			/* translators: %s: list of dependent module names */
			$message = sprintf( __( 'Cannot deactivate: Required by %s', 'aiohm-booking-pro' ), implode( ', ', $active_dependents ) );
			return new WP_Error( 'has_dependents', $message );
		}

		// Get setting key.
		$setting_key = $this->get_module_setting_key( $module_id );
		if ( ! $setting_key ) {
			return new WP_Error( 'no_setting_key', __( 'Module cannot be deactivated (system module)', 'aiohm-booking-pro' ) );
		}

		// Update settings.
		$settings                 = $this->get_settings();
		$settings[ $setting_key ] = false;
		$result                   = update_option( 'aiohm_booking_settings', $settings );

		if ( $result ) {
			$this->clear_caches();
			do_action( 'aiohm_booking_module_deactivated', $module_id );
			return true;
		}

		return new WP_Error( 'deactivation_failed', __( 'Failed to deactivate module', 'aiohm-booking-pro' ) );
	}

	/**
	 * Get modules that should appear in admin menu.
	 *
	 * @return array Array of menu items with module information.
	 */
	public function get_admin_menu_modules() {
		$menu_modules = array();
		$settings     = $this->get_settings();

		// Define menu order and configuration.
		$menu_config = array(
			'tickets'        => array(
				'title'    => __( 'Event Tickets', 'aiohm-booking-pro' ),
				'priority' => 30,
			),
			'accommodations' => array(
				'title'    => __( 'Accommodation', 'aiohm-booking-pro' ),
				'priority' => 40,
			),
			'calendar'       => array(
				'title'    => __( 'Calendar', 'aiohm-booking-pro' ),
				'priority' => 50,
			),
			'notifications'  => array(
				'title'    => __( 'Notifications', 'aiohm-booking-pro' ),
				'priority' => 60,
			),
			'orders'         => array(
				'title'    => __( 'Orders', 'aiohm-booking-pro' ),
				'priority' => 70,
			),
			'css_manager'    => array(
				'title'    => __( 'CSS Design System', 'aiohm-booking-pro' ),
				'priority' => 120,
				'icon'     => '🎨',
				'dev_only' => true,
			),
		);

		foreach ( $menu_config as $module_id => $config ) {
			// Skip dev-only modules if not in dev mode.
			if ( ! empty( $config['dev_only'] ) && ! $this->is_developer_mode() ) {
				continue;
			}

			// Check if module is available.
			if ( ! $this->is_module_available( $module_id ) ) {
				continue;
			}

			$menu_modules[ $module_id ] = array(
				'id'       => $module_id,
				'title'    => $config['title'],
				'slug'     => 'aiohm-booking-' . str_replace( '_', '-', $module_id ),
				'priority' => $config['priority'],
				'icon'     => $config['icon'] ?? null,
			);
		}

		// Sort by priority.
		uasort(
			$menu_modules,
			function ( $a, $b ) {
				return $a['priority'] - $b['priority'];
			}
		);

		return $menu_modules;
	}

	/**
	 * Check if developer mode is active.
	 *
	 * @return bool True if developer mode is active.
	 */
	private function is_developer_mode() {
		// Check if user has manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Check if dev folder exists.
		$dev_folder = AIOHM_BOOKING_DIR . 'includes/modules/dev/';
		if ( ! is_dir( $dev_folder ) ) {
			return false;
		}

		// Allow override via filter or WP_DEBUG.
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ||
				apply_filters( 'aiohm_booking_is_developer', true );
	}

	/**
	 * Get cached settings.
	 *
	 * @return array Settings array.
	 */
	private function get_settings() {
		if ( null === $this->cached_settings ) {
			$this->cached_settings = get_option( 'aiohm_booking_settings', array() );
		}

		return $this->cached_settings;
	}

	/**
	 * Clear internal caches.
	 *
	 * @return void
	 */
	public function clear_caches() {
		$this->cached_settings = null;
		$this->status_cache    = array();
	}

	/**
	 * Get module coordination info for inter-module communication.
	 *
	 * @return array Module coordination information.
	 */
	public function get_module_coordination_info() {
		return array(
			'dependencies'          => $this->module_dependencies,
			'available_modules'     => array_keys(
				array_filter(
					$this->get_modules_status(),
					function ( $status ) {
						return $status['is_available'];
					}
				)
			),
			'enabled_modules'       => array_keys(
				array_filter(
					$this->get_modules_status(),
					function ( $status ) {
						return $status['is_enabled'];
					}
				)
			),
			'premium_modules'       => array_keys(
				array_filter(
					$this->get_modules_status(),
					function ( $status ) {
						return $status['is_premium'];
					}
				)
			),
			'modules_with_settings' => array_keys(
				array_filter(
					$this->get_modules_status(),
					function ( $status ) {
						return $status['has_settings'];
					}
				)
			),
		);
	}

	/**
	 * Render the dashboard page
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function render_dashboard_page() {
		// Delegate to the admin class method.
		if ( class_exists( 'AIOHM_BOOKING_Admin' ) && method_exists( 'AIOHM_BOOKING_Admin', 'render_dashboard_template' ) ) {
			AIOHM_BOOKING_Admin::render_dashboard_template();
		} else {
			// Fallback.
			echo '<div class="wrap"><h1>AIOHM Booking Dashboard</h1><p>Dashboard functionality is being loaded...</p></div>';
		}
	}

	/**
	 * Render the tickets page
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function render_tickets_page() {
		$module = $this->registry->get_module( 'tickets' );

		if ( $module && method_exists( $module, 'render_admin_page' ) ) {
			$module->render_admin_page();
		} else {
			echo '<div class="wrap"><h1>Event Tickets</h1><p>Event Tickets module not found or not enabled.</p></div>';
		}
	}
}
