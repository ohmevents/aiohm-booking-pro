<?php
/**
 * Admin Management System.
 *
 * Lightweight coordinator that orchestrates specialized admin classes.
 * Handles WordPress admin interface coordination and initialization.
 *
 * @package AIOHM_Booking
 * @since 1.2.6
 *
 * @author  OHM Events Agency <https://www.ohm.events>
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Management Class.
 *
 * Lightweight coordinator that orchestrates specialized admin classes
 * for menu management, AJAX handling, settings, and module coordination.
 *
 * @since 1.0.0
 */
class AIOHM_BOOKING_Admin {

	/**
	 * Admin menu handler instance.
	 *
	 * @since 1.0.0
	 * @var AIOHM_BOOKING_Admin_Menu|null
	 */
	private static $menu_handler = null;

	/**
	 * Admin AJAX handler instance.
	 *
	 * @since 1.0.0
	 * @var AIOHM_BOOKING_Admin_Ajax|null
	 */
	private static $ajax_handler = null;

	/**
	 * Admin settings handler instance.
	 *
	 * @since 1.0.0
	 * @var AIOHM_BOOKING_Admin_Settings|null
	 */
	private static $settings_handler = null;

	/**
	 * Admin modules handler instance.
	 *
	 * @since 1.0.0
	 * @var AIOHM_BOOKING_Admin_Modules|null
	 */
	private static $modules_handler = null;

	/**
	 * Initialize the Admin class.
	 *
	 * Sets up WordPress admin hooks and initializes specialized admin classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		// Initialize specialized admin handlers.
		self::init_handlers();

		// Core admin hooks.
		add_action( 'admin_init', array( __CLASS__, 'handle_activation_redirect' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( AIOHM_BOOKING_FILE ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Initialize specialized admin handler classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function init_handlers() {
		// Initialize menu handler (instance-based).
		if ( class_exists( 'AIOHM_BOOKING_Admin_Menu' ) ) {
			self::$menu_handler = new AIOHM_BOOKING_Admin_Menu();
			self::$menu_handler->init();
		}

		// Initialize AJAX handler (static).
		if ( class_exists( 'AIOHM_BOOKING_Admin_Ajax' ) ) {
			AIOHM_BOOKING_Admin_Ajax::init();
			// Create dummy instance for backward compatibility.
			self::$ajax_handler = new stdClass();
		}

		// Initialize settings handler (static).
		if ( class_exists( 'AIOHM_BOOKING_Admin_Settings' ) ) {
			AIOHM_BOOKING_Admin_Settings::init();
			// Create dummy instance for backward compatibility.
			self::$settings_handler = new stdClass();
		}

		// Initialize modules handler (instance-based).
		if ( class_exists( 'AIOHM_BOOKING_Admin_Modules' ) ) {
			self::$modules_handler = new AIOHM_BOOKING_Admin_Modules();
			self::$modules_handler->init();
		}
	}

	/**
	 * Get menu handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOHM_BOOKING_Admin_Menu|null
	 */
	public static function get_menu_handler() {
		return self::$menu_handler;
	}

	/**
	 * Get AJAX handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOHM_BOOKING_Admin_Ajax|null
	 */
	public static function get_ajax_handler() {
		return self::$ajax_handler;
	}

	/**
	 * Get settings handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOHM_BOOKING_Admin_Settings|null
	 */
	public static function get_settings_handler() {
		return self::$settings_handler;
	}

	/**
	 * Get modules handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOHM_BOOKING_Admin_Modules|null
	 */
	public static function get_modules_handler() {
		return self::$modules_handler;
	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links The existing links.
	 *
	 * @return array The modified links.
	 */
	public static function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=aiohm-booking-settings' ) . '">' . __( 'Settings', 'aiohm-booking-pro' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Handle activation redirect to dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle_activation_redirect() {
		// Check if we should redirect after activation.
		if ( get_transient( 'aiohm_booking_activation_redirect' ) ) {
			delete_transient( 'aiohm_booking_activation_redirect' );

			// Only redirect if not already on the plugin page and not doing AJAX.
			$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'aiohm-booking-pro' !== $current_page ) {
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					wp_safe_redirect( admin_url( 'admin.php?page=aiohm-booking' ) );
					exit;
				}
			}
		}
	}

	// =============================================================================
	// PAGE RENDERING METHODS - Delegate to specialized handlers.
	// =============================================================================

	/**
	 * Dashboard page - delegates to modules handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function dash() {
		if ( self::$modules_handler && method_exists( self::$modules_handler, 'render_dashboard_page' ) ) {
			self::$modules_handler->render_dashboard_page();
		} else {
			// Render the dashboard template directly.
			self::render_dashboard_template();
		}
	}

	/**
	 * Calendar page - delegates to modules handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function calendar() {
		if ( self::$modules_handler && method_exists( self::$modules_handler, 'render_calendar_page' ) ) {
			self::$modules_handler->render_calendar_page();
		} else {
			self::render_fallback_page( 'calendar', 'Calendar' );
		}
	}

	/**
	 * Orders page - delegates to modules handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function orders() {
		if ( self::$modules_handler && method_exists( self::$modules_handler, 'render_orders_page' ) ) {
			self::$modules_handler->render_orders_page();
		} else {
			self::render_fallback_page( 'orders', 'Orders' );
		}
	}

	/**
	 * Notifications page - delegates to modules handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function notification_module() {
		if ( self::$modules_handler && method_exists( self::$modules_handler, 'render_notifications_page' ) ) {
			self::$modules_handler->render_notifications_page();
		} else {
			self::render_fallback_page( 'notifications', 'Notifications' );
		}
	}

	/**
	 * Settings page - delegates to settings handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function settings_page() {
		if ( self::$settings_handler && method_exists( self::$settings_handler, 'render_settings_page' ) ) {
			self::$settings_handler->render_settings_page();
		} elseif ( file_exists( AIOHM_BOOKING_DIR . 'templates/aiohm-booking-settings.php' ) ) {
			include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-settings.php';
		} else {
			echo '<div class="wrap"><h1>Settings</h1><p>Settings template not found.</p></div>';
		}
	}



	/**
	 * Accommodations page - delegates to modules handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function accommodations_module() {
		if ( self::$modules_handler && method_exists( self::$modules_handler, 'render_accommodations_page' ) ) {
			self::$modules_handler->render_accommodations_page();
		} else {
			self::render_fallback_page( 'accommodations', 'Accommodations' );
		}
	}

	/**
	 * CSS Manager page - delegates to modules handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function css_manager_page() {
		if ( self::$modules_handler && method_exists( self::$modules_handler, 'render_css_manager_page' ) ) {
			self::$modules_handler->render_css_manager_page();
		} else {
			self::render_fallback_page( 'css_manager', 'CSS Design System' );
		}
	}

	// =============================================================================
	// LEGACY AJAX METHODS - All delegated to specialized classes.
	// =============================================================================

	/**
	 * Legacy menu method - delegates to menu handler.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0 Use AIOHM_BOOKING_Admin_Menu class instead.
	 *
	 * @return void
	 */
	public static function menu() {
		if ( self::$menu_handler && method_exists( self::$menu_handler, 'create_admin_menu' ) ) {
			self::$menu_handler->create_admin_menu();
		}
	}

	/**
	 * Legacy sanitize settings method - delegates to settings handler.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0 Use AIOHM_BOOKING_Admin_Settings class instead.
	 *
	 * @param array $settings The settings array to sanitize.
	 *
	 * @return array The sanitized settings
	 */
	public static function sanitize_settings( $settings ) {
		if ( class_exists( 'AIOHM_BOOKING_Admin_Settings' ) && method_exists( 'AIOHM_BOOKING_Admin_Settings', 'sanitize_settings' ) ) {
			return AIOHM_BOOKING_Admin_Settings::sanitize_settings( $settings );
		}
		// Basic fallback sanitization.
		return is_array( $settings ) ? array_map( 'sanitize_text_field', $settings ) : array();
	}


	// =============================================================================
	// UTILITY METHODS.
	// =============================================================================


	/**
	 * Render fallback page for modules.
	 *
	 * @since 1.2.0
	 *
	 * @param string $module_key The module key.
	 * @param string $page_title The page title.
	 *
	 * @return void
	 */
	private static function render_fallback_page( $module_key, $page_title ) {
		$registry = AIOHM_BOOKING_Module_Registry::instance();
		$module   = $registry->get_module( $module_key );
		if ( $module && method_exists( $module, 'render_admin_page' ) ) {
			$module->render_admin_page();
		} else {
			echo '<div class="wrap"><h1>' . esc_html( $page_title ) . '</h1><p>' . esc_html( $page_title . ' module not found or not enabled.' ) . '</p></div>';
		}
	}

	/**
	 * Render the dashboard template with booking statistics
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	/**
	 * Get dashboard statistics with caching.
	 *
	 * @since 1.0.0
	 *
	 * @return array Dashboard statistics.
	 */
	public static function get_dashboard_statistics() {
		global $wpdb;

		// Get dashboard statistics with caching.
		$orders_table = $wpdb->prefix . 'aiohm_booking_order';
		$cache_key    = 'aiohm_booking_dashboard_stats';
		$cached_stats = get_transient( $cache_key );

		if ( false !== $cached_stats ) {
			return $cached_stats;
		}

		// Check if orders table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_table ) ) === $orders_table; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $table_exists ) {
			// Get 30-day statistics using prepared statements.
			$thirty_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

			$total_orders_30_days = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . esc_sql( $orders_table ) . ' WHERE created_at >= %s',
					$thirty_days_ago
				)
			);

			$pending_orders = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . esc_sql( $orders_table ) . ' WHERE status = %s AND created_at >= %s',
					'pending',
					$thirty_days_ago
				)
			);

			$paid_orders = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . esc_sql( $orders_table ) . ' WHERE status = %s AND created_at >= %s',
					'paid',
					$thirty_days_ago
				)
			);

			$total_revenue = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT SUM(total_amount) FROM ' . esc_sql( $orders_table ) . ' WHERE status = %s AND created_at >= %s',
					'paid',
					$thirty_days_ago
				)
			);

			$avg_order_value = $paid_orders > 0 ? $total_revenue / $paid_orders : 0;
			$conversion_rate = $total_orders_30_days > 0 ? ( $paid_orders / $total_orders_30_days ) * 100 : 0;

			// Get all-time statistics.
			$all_time_orders = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				'SELECT COUNT(*) FROM ' . esc_sql( $orders_table )
			);
		} else {
			// Default values if table doesn't exist.
			$total_orders_30_days = 0;
			$pending_orders       = 0;
			$paid_orders          = 0;
			$total_revenue        = 0;
			$avg_order_value      = 0;
			$conversion_rate      = 0;
			$all_time_orders      = 0;
		}

		// Prepare statistics array.
		$stats = array(
			'total_orders_30_days' => (int) $total_orders_30_days,
			'pending_orders'       => (int) $pending_orders,
			'paid_orders'          => (int) $paid_orders,
			'total_revenue'        => (float) $total_revenue,
			'avg_order_value'      => (float) $avg_order_value,
			'conversion_rate'      => (float) $conversion_rate,
			'all_time_orders'      => (int) $all_time_orders,
		);

		// Cache for 5 minutes.
		set_transient( $cache_key, $stats, 5 * MINUTE_IN_SECONDS );

		return $stats;
	}

	/**
	 * Render dashboard template.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render_dashboard_template() {
		// Get dashboard statistics.
		$stats = self::get_dashboard_statistics();

		// Extract statistics for template variables.
		$total_revenue        = $stats['total_revenue'] ?? 0;
		$total_orders_30_days = $stats['total_orders_30_days'] ?? 0;
		$pending_orders       = $stats['pending_orders'] ?? 0;
		$paid_orders          = $stats['paid_orders'] ?? 0;
		$conversion_rate      = $stats['conversion_rate'] ?? 0;
		$avg_order_value      = $stats['avg_order_value'] ?? 0;
		$all_time_orders      = $stats['all_time_orders'] ?? 0;

		// Get currency setting.
		$settings = get_option( 'aiohm_booking_settings', array() );
		$currency = $settings['currency'] ?? 'USD';

		// Get AI provider setting for dashboard display.
		$default_ai_provider = $settings['shortcode_ai_provider'] ?? 'shareai';

		// Include the dashboard template.
		include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-dashboard.php';
	}
}
