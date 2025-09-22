<?php
/**
 * Common Utility Functions for AIOHM Booking
 *
 * Centralized utility class containing singleton patterns, URL generation,
 * and other common helper methods used across the plugin.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 *
 * @author  OHM Events Agency <https://www.ohm.events>
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Common Utilities Class
 *
 * Provides reusable utility methods for singleton instances,
 * URL generation, and common helper functions.
 *
 * @since 1.2.3
 */
class AIOHM_BOOKING_Utilities {

	/**
	 * Store singleton instances
	 *
	 * @since 1.2.3
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Cache for module availability checks
	 *
	 * @since 1.2.4
	 * @var array
	 */
	private static $module_availability_cache = array();

	/**
	 * Generic singleton instance method
	 *
	 * Provides a consistent singleton pattern implementation
	 * that can be used by any class extending this functionality.
	 *
	 * @since 1.2.3
	 *
	 * @param string $class_name The class name to get instance for.
	 * @return object The singleton instance
	 */
	public static function get_instance( $class_name = null ) {
		$class_name = $class_name ? $class_name : get_called_class();

		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}

		return self::$instances[ $class_name ];
	}

	/**
	 * Generate plugin URL for assets
	 *
	 * Consistent URL generation for plugin assets with proper
	 * path normalization and WordPress compatibility.
	 *
	 * @since 1.2.3
	 *
	 * @param string $path Path relative to plugin assets directory.
	 * @param string $type Asset type (css, js, images). Optional.
	 * @return string Full URL to the asset
	 */
	public static function get_asset_url( $path, $type = '' ) {
		$path = ltrim( $path, '/' );

		// Add type prefix if specified and not already in path.
		if ( $type && ! str_starts_with( $path, $type . '/' ) ) {
			$path = $type . '/' . $path;
		}

		return AIOHM_BOOKING_URL . 'assets/' . $path;
	}

	/**
	 * Generate plugin URL for any file
	 *
	 * @since 1.2.3
	 *
	 * @param string $path Path relative to plugin root directory.
	 * @return string Full URL to the file
	 */
	public static function get_plugin_url( $path = '' ) {
		return AIOHM_BOOKING_URL . ltrim( $path, '/' );
	}

	/**
	 * Generate plugin path for any file
	 *
	 * @since 1.2.3
	 *
	 * @param string $path Path relative to plugin root directory.
	 * @return string Full filesystem path to the file
	 */
	public static function get_plugin_path( $path = '' ) {
		return AIOHM_BOOKING_DIR . ltrim( $path, '/' );
	}

	/**
	 * Check if we're on an AIOHM Booking admin page
	 *
	 * @since 1.2.3
	 *
	 * @param string $hook_suffix Optional. Current admin page hook suffix.
	 * @return bool True if on plugin admin page
	 */
	public static function is_plugin_admin_page( $hook_suffix = '' ) {
		if ( empty( $hook_suffix ) ) {
			$hook_suffix = get_current_screen()->id ?? '';
		}

		return strpos( $hook_suffix, 'aiohm-booking-pro' ) !== false;
	}

	/**
	 * Sanitize and validate array of data
	 *
	 * @since 1.2.3
	 *
	 * @param array $data Data to sanitize.
	 * @param array $rules Sanitization rules per field.
	 * @return array Sanitized data
	 */
	public static function sanitize_data_array( $data, $rules = array() ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$rule = $rules[ $key ] ?? 'text_field';

			switch ( $rule ) {
				case 'email':
					$sanitized[ $key ] = sanitize_email( $value );
					break;
				case 'url':
					$sanitized[ $key ] = esc_url_raw( $value );
					break;
				case 'int':
					$sanitized[ $key ] = intval( $value );
					break;
				case 'float':
					$sanitized[ $key ] = floatval( $value );
					break;
				case 'boolean':
					$sanitized[ $key ] = (bool) $value;
					break;
				case 'textarea':
					$sanitized[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'text_field':
				default:
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Get current user capability for plugin operations
	 *
	 * @since 1.2.3
	 *
	 * @param string $context Optional. Context for capability check.
	 * @return string Required capability
	 */
	public static function get_required_capability( $context = 'general' ) {
		$capabilities = array(
			'general'  => 'manage_options',
			'settings' => 'manage_options',
			'bookings' => 'edit_posts',
			'reports'  => 'edit_posts',
		);

		return $capabilities[ $context ] ?? 'manage_options';
	}

	/**
	 * Check if current user can perform plugin action
	 *
	 * @since 1.2.3
	 *
	 * @param string $context Optional. Context for capability check.
	 * @return bool True if user has required capability
	 */
	public static function current_user_can_access( $context = 'general' ) {
		return current_user_can( self::get_required_capability( $context ) );
	}

	/**
	 * Generate secure nonce for plugin actions
	 *
	 * @since 1.2.3
	 *
	 * @param string $action Action name.
	 * @return string Nonce value
	 */
	public static function create_nonce( $action ) {
		return wp_create_nonce( 'aiohm_booking_' . $action );
	}

	/**
	 * Verify nonce for plugin actions
	 *
	 * @since 1.2.3
	 *
	 * @param string $nonce Nonce value to verify.
	 * @param string $action Action name.
	 * @return bool True if nonce is valid
	 */
	public static function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, 'aiohm_booking_' . $action );
	}

	/**
	 * Check if a module is available and loaded
	 *
	 * @since 1.2.4
	 *
	 * @param string $module_id Module ID to check.
	 * @return bool True if module is available and loaded
	 */
	public static function is_module_available( $module_id ) {
		// Use cache to avoid repeated checks.
		if ( isset( self::$module_availability_cache[ $module_id ] ) ) {
			return self::$module_availability_cache[ $module_id ];
		}

		$registry  = AIOHM_BOOKING_Module_Registry::instance();
		$available = $registry->module_exists( $module_id );

		// Cache the result.
		self::$module_availability_cache[ $module_id ] = $available;

		return $available;
	}

	/**
	 * Get available payment methods (only loaded modules)
	 *
	 * @since 1.2.4
	 *
	 * @return array Available payment methods
	 */
	public static function get_available_payment_methods() {
		$methods = array();

		// Core payment methods that should always be available.
		$methods['offline'] = array(
			'id'          => 'offline',
			'title'       => __( 'Manual Payment', 'aiohm-booking-pro' ),
			'description' => __( 'Pay by check, bank transfer, or cash', 'aiohm-booking-pro' ),
			'enabled'     => true,
		);

		// Check for optional payment modules.
		if ( self::is_module_available( 'stripe' ) ) {
			$stripe_module = AIOHM_BOOKING_Module_Registry::get_module_instance( 'stripe' );
			if ( $stripe_module && method_exists( $stripe_module, 'register_payment_method' ) ) {
				$methods = $stripe_module->register_payment_method( $methods );
			}
		}

		if ( self::is_module_available( 'paypal' ) ) {
			$paypal_module = AIOHM_BOOKING_Module_Registry::get_module_instance( 'paypal' );
			if ( $paypal_module && method_exists( $paypal_module, 'register_payment_method' ) ) {
				$methods = $paypal_module->register_payment_method( $methods );
			}
		}

		return apply_filters( 'aiohm_booking_available_payment_methods', $methods );
	}

	/**
	 * Check if any payment gateways are available
	 *
	 * @since 1.2.4
	 *
	 * @return bool True if at least one payment gateway is available
	 */
	public static function has_payment_gateways() {
		$methods = self::get_available_payment_methods();

		// Remove offline method for this check.
		unset( $methods['offline'] );

		return ! empty( $methods );
	}

	/**
	 * Get available AI providers
	 *
	 * @since 1.2.4
	 *
	 * @return array Available AI providers
	 */
	public static function get_available_ai_providers() {
		$providers = array();

		// Check each AI provider module.
		$ai_modules = array( 'openai', 'gemini', 'ollama', 'shareai' );

		foreach ( $ai_modules as $provider ) {
			if ( self::is_module_available( $provider ) ) {
				$provider_module = AIOHM_BOOKING_Module_Registry::get_module_instance( $provider );
				if ( $provider_module && method_exists( $provider_module, 'register_ai_provider' ) ) {
					$providers = $provider_module->register_ai_provider( $providers );
				}
			}
		}

		return apply_filters( 'aiohm_booking_available_ai_providers', $providers );
	}

	/**
	 * Check if any AI providers are available
	 *
	 * @since 1.2.4
	 *
	 * @return bool True if at least one AI provider is available
	 */
	public static function has_ai_providers() {
		$providers = self::get_available_ai_providers();
		return ! empty( $providers );
	}

	/**
	 * Get missing optional modules with user-friendly names
	 *
	 * @since 1.2.4
	 *
	 * @return array Missing modules with display names
	 */
	public static function get_missing_optional_modules() {
		$registry      = AIOHM_BOOKING_Module_Registry::instance();
		$missing_types = $registry->get_missing_optional_modules();

		$module_names = array(
			'stripe'       => __( 'Stripe Payments', 'aiohm-booking-pro' ),
			'paypal'       => __( 'PayPal Payments', 'aiohm-booking-pro' ),
			'dev'          => __( 'Developer Tools', 'aiohm-booking-pro' ),
			'openai'       => __( 'OpenAI Provider', 'aiohm-booking-pro' ),
			'gemini'       => __( 'Google Gemini Provider', 'aiohm-booking-pro' ),
			'ollama'       => __( 'Ollama Provider', 'aiohm-booking-pro' ),
			'shareai'      => __( 'ShareAI Provider', 'aiohm-booking-pro' ),
			'ai_analytics' => __( 'AI Analytics', 'aiohm-booking-pro' ),
		);

		$missing = array();
		foreach ( $missing_types as $type ) {
			$missing[ $type ] = $module_names[ $type ] ?? ucfirst( $type );
		}

		return $missing;
	}

	/**
	 * Clear module availability cache
	 *
	 * @since 1.2.4
	 */
	public static function clear_module_cache() {
		self::$module_availability_cache = array();
	}

	/**
	 * Format currency amount for display
	 *
	 * @since 1.2.3
	 *
	 * @param float  $amount Amount to format.
	 * @param string $currency Currency code.
	 * @return string Formatted currency string
	 */
	public static function format_currency( $amount, $currency = 'RON' ) {
		$currency_symbols = array(
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
			'RON' => 'RON',
		);

		$symbol           = $currency_symbols[ $currency ] ?? $currency . ' ';
		$formatted_amount = number_format( (float) $amount, 2, '.', ',' );

		// Symbol placement varies by currency.
		if ( in_array( $currency, array( 'USD', 'GBP' ), true ) ) {
			return $symbol . $formatted_amount;
		}

		return $formatted_amount . ' ' . $symbol;
	}

	/**
	 * Get plugin version for cache busting
	 *
	 * @since 1.2.3
	 *
	 * @return string Plugin version
	 */
	public static function get_version() {
		return defined( 'AIOHM_BOOKING_VERSION' ) ? AIOHM_BOOKING_VERSION : '1.0.0';
	}

	/**
	 * Get list of PRO modules
	 *
	 * @since 1.2.5
	 *
	 * @return array List of PRO module names
	 */
	public static function get_pro_modules() {
		return array(
			'stripe',
			'paypal',
			'openai',
			'gemini',
			'ollama',
			'shareai',
		);
	}

	/**
	 * Check if a module is a PRO feature
	 *
	 * @since 1.2.5
	 *
	 * @param string $module_name Module name to check
	 * @return bool True if module is PRO
	 */
	public static function is_pro_module( $module_name ) {
		return in_array( $module_name, self::get_pro_modules(), true );
	}

	/**
	 * Get Go PRO message for a specific feature
	 *
	 * @since 1.2.5
	 *
	 * @param string $feature_name Feature name (e.g., 'Stripe payments', 'AI analytics')
	 * @return string HTML Go PRO message
	 */
	public static function get_go_pro_message( $feature_name = '' ) {
		$message = $feature_name ?
			/* translators: %s: Feature name */
			sprintf( __( '%s is available in AIOHM Booking PRO.', 'aiohm-booking-pro' ), $feature_name ) :
			__( 'This feature is available in AIOHM Booking PRO.', 'aiohm-booking-pro' );

		$upgrade_text    = __( 'Upgrade to PRO', 'aiohm-booking-pro' );
		$learn_more_text = __( 'Learn More', 'aiohm-booking-pro' );

		return sprintf(
			'<div class="aiohm-go-pro-message">
				<p><strong>%s</strong></p>
				<p>%s</p>
				<div class="aiohm-go-pro-buttons">
					<a href="#" class="button button-primary aiohm-go-pro-button">%s</a>
					<a href="#" class="button aiohm-learn-more-button">%s</a>
				</div>
			</div>',
			esc_html( $message ),
			esc_html__( 'Get access to payment processing, AI analytics, and premium support.', 'aiohm-booking-pro' ),
			esc_html( $upgrade_text ),
			esc_html( $learn_more_text )
		);
	}

	/**
	 * Get Go PRO notice for admin settings
	 *
	 * @since 1.2.5
	 *
	 * @param string $feature_name Feature name
	 * @return string HTML notice
	 */
	public static function get_go_pro_notice( $feature_name = '' ) {
		$message = $feature_name ?
			/* translators: %s: Feature name */
			sprintf( __( '%s requires AIOHM Booking PRO', 'aiohm-booking-pro' ), $feature_name ) :
			__( 'This feature requires AIOHM Booking PRO', 'aiohm-booking-pro' );

		return sprintf(
			'<div class="notice notice-info aiohm-pro-notice">
				<p><strong>%s</strong> - <a href="#" class="aiohm-go-pro-link">%s</a></p>
			</div>',
			esc_html( $message ),
			esc_html__( 'Upgrade Now', 'aiohm-booking-pro' )
		);
	}

	/**
	 * Check if PRO features should be disabled
	 *
	 * @since 1.2.5
	 *
	 * @return bool True if PRO features should be disabled (for free version)
	 */
	public static function is_free_version() {
		// Check if user has access to premium features
		if ( ! function_exists( 'aiohm_booking_fs' ) ) {
			return true; // No Freemius = free version
		}
		
		return ! aiohm_booking_fs()->can_use_premium_code__premium_only();
	}
}
