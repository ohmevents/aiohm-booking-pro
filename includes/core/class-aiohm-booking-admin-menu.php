<?php
/**
 * Admin Menu Management System.
 *
 * Handles WordPress admin menu creation, submenu management, and navigation
 * structure for the AIOHM Booking plugin. This class is responsible for
 * creating all admin menu items based on module settings and user permissions.
 *
 * @package AIOHM_Booking
 *
 * @since 1.2.6
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
 * Admin Menu Management Class.
 *
 * Creates and manages the WordPress admin menu structure for the booking system,
 * including dynamic menu item creation based on enabled modules and user settings.
 *
 * @since 1.0.0
 */
class AIOHM_BOOKING_Admin_Menu {

	/**
	 * Module registry instance.
	 *
	 * @since 1.0.0
	 * @var AIOHM_BOOKING_Module_Registry
	 */
	private $module_registry;

	/**
	 * Plugin settings.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * Initializes the menu system and sets up WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @param AIOHM_BOOKING_Module_Registry $module_registry The module registry instance.
	 */
	public function __construct( $module_registry = null ) {
		$this->module_registry = $module_registry ? $module_registry : AIOHM_BOOKING_Module_Registry::instance();
	}

	/**
	 * Initialize the admin menu system.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ), 10 );
	}

	/**
	 * Create admin menu and submenus.
	 *
	 * Builds the complete admin menu structure for AIOHM Booking,
	 * including conditional menu items based on enabled modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function create_admin_menu() {
		// Get fresh settings for menu creation.
		$this->settings = AIOHM_BOOKING_Settings::get_all();

		// Create main menu page.
		$menu_icon = $this->get_menu_icon();
		add_menu_page(
			'AIOHM Booking',
			'AIOHM Booking',
			'manage_options',
			'aiohm-booking-pro',
			array( 'AIOHM_BOOKING_Admin', 'dash' ),
			$menu_icon,
			27
		);

		// Add core menu items.
		$this->add_core_menu_items();

		// Add conditional menu items based on enabled modules.
		$this->add_conditional_menu_items();

		// Add developer-only menu items.
		$this->add_developer_menu_items();
	}

	/**
	 * Add core menu items that are always visible.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_core_menu_items() {
		// Dashboard (default page after install).
		add_submenu_page(
			'aiohm-booking-pro',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'aiohm-booking-pro',
			array( 'AIOHM_BOOKING_Admin', 'dash' )
		);

		// Settings (always show for plugin configuration).
		add_submenu_page(
			'aiohm-booking-pro',
			'Settings',
			'Settings',
			'manage_options',
			'aiohm-booking-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Add conditional menu items based on enabled modules.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_conditional_menu_items() {
		$enabled_modules = $this->get_enabled_modules();

		// Event Tickets.
		if ( $enabled_modules['tickets'] ) {
			add_submenu_page(
				'aiohm-booking-pro',
				'Event Tickets',
				'Event Tickets',
				'manage_options',
				'aiohm-booking-tickets',
				array( $this, 'tickets_module' )
			);
		}

		// Accommodation.
		if ( $enabled_modules['accommodations'] ) {
			add_submenu_page(
				'aiohm-booking-pro',
				'Accommodation',
				'Accommodation',
				'manage_options',
				'aiohm-booking-accommodations',
				array( $this, 'accommodations_module' )
			);
		}

		// Calendar (show only if calendar is enabled AND accommodations is enabled, since calendar depends on accommodations).
		if ( $enabled_modules['calendar'] && $enabled_modules['accommodations'] ) {
			add_submenu_page(
				'aiohm-booking-pro',
				'Calendar',
				'Calendar',
				'manage_options',
				'aiohm-booking-calendar',
				array( $this, 'calendar' )
			);
		}

		// Notifications.
		if ( $enabled_modules['notifications'] ) {
			add_submenu_page(
				'aiohm-booking-pro',
				'Notifications',
				'Notifications',
				'manage_options',
				'aiohm-booking-notifications',
				array( $this, 'notification_module' )
			);
		}

		// Orders.
		if ( $enabled_modules['orders'] ) {
			add_submenu_page(
				'aiohm-booking-pro',
				'Orders',
				'Orders',
				'manage_options',
				'aiohm-booking-orders',
				array( $this, 'orders' )
			);
		}
	}

	/**
	 * Add developer-only menu items.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function add_developer_menu_items() {
		$css_manager_enabled = ! empty( $this->settings['enable_css_manager'] );

		if ( $css_manager_enabled && $this->is_developer() ) {
			add_submenu_page(
				'aiohm-booking-pro',
				'CSS Design System',
				'🎨 Design System',
				'manage_options',
				'aiohm-booking-css-manager',
				array( $this, 'css_manager_page' )
			);
		}
	}

	/**
	 * Get enabled modules based on current settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of enabled module flags
	 */
	private function get_enabled_modules() {
		// Handle both boolean and string values for backward compatibility.
		return array(
			'accommodations' => $this->is_module_enabled( 'enable_accommodations' ),
			'notifications'  => $this->is_module_enabled( 'enable_notifications' ) && $this->is_notifications_module_available(),
			'orders'         => $this->is_module_enabled( 'enable_orders' ),
			'calendar'       => $this->is_module_enabled( 'enable_calendar' ),
			'tickets'        => $this->is_module_enabled( 'enable_tickets' ),
			'ai_analytics'   => $this->is_module_enabled( 'enable_ai_analytics' ),
		);
	}

	/**
	 * Check if a module is enabled in settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_key The setting key to check.
	 *
	 * @return bool True if module is enabled
	 */
	private function is_module_enabled( $setting_key ) {
		return isset( $this->settings[ $setting_key ] ) &&
				! empty( $this->settings[ $setting_key ] ) &&
				'0' !== $this->settings[ $setting_key ] &&
				false !== $this->settings[ $setting_key ];
	}

	/**
	 * Check if notifications module is available (file exists).
	 *
	 * @since 1.2.5
	 *
	 * @return bool True if notifications module file exists
	 */
	private function is_notifications_module_available() {
		$notifications_file = AIOHM_BOOKING_DIR . 'includes/modules/notifications/class-aiohm-booking-module-notifications.php';
		return file_exists( $notifications_file );
	}

	/**
	 * Get menu icon for admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return string Menu icon data URI or dashicon class
	 */
	private function get_menu_icon() {
		// Detect admin color scheme for dynamic theming.
		$admin_color   = get_user_option( 'admin_color' );
		$is_dark_theme = in_array( $admin_color, array( 'midnight', 'blue', 'coffee', 'ectoplasm', 'ocean' ), true );

		// Use the OHM logo SVG files.
		$logo_path = $is_dark_theme
			? AIOHM_BOOKING_DIR . 'assets/images/aiohm-booking-OHM_logo-white.svg'
			: AIOHM_BOOKING_DIR . 'assets/images/aiohm-booking-OHM_logo-black.svg';

		if ( file_exists( $logo_path ) && is_readable( $logo_path ) ) {
			$svg_content = file_get_contents( $logo_path );
			if ( false !== $svg_content && ! empty( $svg_content ) ) {
				return 'data:image/svg+xml;base64,' . base64_encode( $svg_content );
			}
		}

		// Fallback to dashicon.
		return 'dashicons-calendar-alt';
	}

	/**
	 * Check if current user is a developer.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if user is a developer
	 */
	private function is_developer() {
		// Check if user has manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Check if dev folder exists (most important check for production readiness).
		$dev_folder = AIOHM_BOOKING_DIR . 'includes/modules/dev/';
		if ( ! is_dir( $dev_folder ) ) {
			return false;
		}

		// Allow override via filter or WP_DEBUG.
		return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ||
				apply_filters( 'aiohm_booking_is_developer', true );
	}

	/**
	 * Render the Settings admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings_page() {
		// Get the settings module from the registry.
		$module = $this->module_registry->get_module( 'settings' );

		if ( $module && method_exists( $module, 'render_settings_page' ) ) {
			$module->render_settings_page();
		} elseif ( $module && method_exists( $module, 'render_admin_page' ) ) {
			$module->render_admin_page();
		} else {
			// Fallback: include the settings template directly.
			include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-settings.php';
		}
	}


	/**
	 * Calendar page handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function calendar() {
		// Load and render the calendar module.
		if ( class_exists( 'AIOHM_BOOKING_Module_Calendar' ) ) {
			$calendar_module = new AIOHM_BOOKING_Module_Calendar();
			if ( method_exists( $calendar_module, 'render_calendar_page' ) ) {
				$calendar_module->render_calendar_page();
			} elseif ( method_exists( $calendar_module, 'render_admin_page' ) ) {
				$calendar_module->render_admin_page();
			} else {
				echo '<div class="wrap"><h1>Calendar</h1><p>Calendar module loaded but render method not available.</p></div>';
			}
		} else {
			echo '<div class="wrap"><h1>Calendar</h1><p>Calendar module not found or not loaded.</p></div>';
		}
	}

	/**
	 * Orders page handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function orders() {
		$module = $this->module_registry->get_module( 'orders' );

		if ( $module && method_exists( $module, 'render_admin_page' ) ) {
			$module->render_admin_page();
		} else {
			echo '<div class="wrap"><h1>Orders</h1><p>Orders module not found or not enabled.</p></div>';
		}
	}

	/**
	 * Notifications page handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function notification_module() {
		$module = $this->module_registry->get_module( 'notifications' );

		if ( $module && method_exists( $module, 'render_admin_page' ) ) {
			$module->render_admin_page();
		} else {
			echo '<div class="wrap"><h1>Notifications</h1><p>Notifications module not found or not enabled.</p></div>';
		}
	}

	/**
	 * Event Tickets page handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function tickets_module() {
		$module = $this->module_registry->get_module( 'tickets' );

		if ( $module && method_exists( $module, 'render_admin_page' ) ) {
			$module->render_admin_page();
		} else {
			echo '<div class="wrap"><h1>Event Tickets</h1><p>Event Tickets module not found or not enabled.</p></div>';
		}
	}

	/**
	 * Accommodations page handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function accommodations_module() {
		if ( class_exists( 'AIOHM_BOOKING_Module_Accommodation' ) ) {
			$accommodation_module = new AIOHM_BOOKING_Module_Accommodation();
			$accommodation_module->render_admin_page();
		} else {
			echo '<div class="wrap"><h1>Accommodation</h1><p>Accommodation module not found or not enabled.</p></div>';
		}
	}


	/**
	 * CSS Manager page handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function css_manager_page() {
		// Get the CSS Manager module instance and render its page.
		$css_manager = $this->module_registry->get_module( 'css_manager' );

		if ( $css_manager && method_exists( $css_manager, 'render_css_manager_page' ) ) {
			$css_manager->render_css_manager_page();
		} else {
			echo '<div class="wrap"><h1>CSS Design System</h1><p>CSS Manager module not available.</p></div>';
		}
	}
}
