<?php
/**
 * Settings management class for AIOHM Booking.
 *
 * Handles fetching, updating, and caching of plugin settings.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings management class.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Settings {

	/**
	 * Cache for the settings to avoid multiple database calls.
	 *
	 * @var array|null
	 */
	private static $cached_settings = null;

	/**
	 * Flag to indicate if the cache should be cleared.
	 *
	 * @var bool
	 */
	private static $cache_cleared = false;

	/**
	 * Get all plugin settings.
	 *
	 * @since  2.0.0
	 *
	 * @return array Plugin settings array.
	 */
	public static function get_all() {
		if ( self::$cached_settings === null || self::$cache_cleared ) {
			$settings = get_option( 'aiohm_booking_settings', array() );

			// Validate settings to prevent memory issues.
			if ( is_array( $settings ) ) {
				// Remove any excessively large values.
				foreach ( $settings as $key => $value ) {
					if ( is_array( $value ) && count( $value ) > 1000 ) {
						unset( $settings[ $key ] ); // Remove large arrays.
					} elseif ( is_string( $value ) && strlen( $value ) > 10000 ) {
						unset( $settings[ $key ] ); // Remove very long strings.
					}
				}
			} else {
				$settings = array();
			}

			// Set default values for enable flags.
			$defaults = array(
				'enable_accommodations' => true,
				'enable_notifications'  => true,
				'enable_tickets'        => true,
				'enable_orders'         => true,
				'enable_calendar'       => true,
				'enable_ai_analytics'   => false, // Disabled in v2.0.0
				'enable_css_manager'    => true,
				'enable_ollama'         => false,
				'enable_paypal'         => false, // Disabled in v2.0.0
				'enable_stripe'         => true,  // Enabled by default in v2.0.0
				'enable_help'           => true,
			);

			$updated = false; // Initialize update flag.
			foreach ( $defaults as $key => $default_value ) {
				// Only set defaults for truly missing settings, not for explicitly set false values
				if ( ! isset( $settings[ $key ] ) ) {
					$settings[ $key ] = $default_value;
					$updated          = true;
				}
			}

			// Enable PRO modules for premium users
			if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
				if ( ! isset( $settings['enable_paypal'] ) || $settings['enable_paypal'] === false ) {
					$settings['enable_paypal'] = true;
					$updated                   = true;
				}
				if ( ! isset( $settings['enable_stripe'] ) || $settings['enable_stripe'] === false ) {
					$settings['enable_stripe'] = true;
					$updated                   = true;
				}
				if ( ! isset( $settings['enable_ai_analytics'] ) || $settings['enable_ai_analytics'] === false ) {
					$settings['enable_ai_analytics'] = true;
					$updated                          = true;
				}
				// Auto-enable AI providers for AI Analytics
				if ( ! isset( $settings['enable_ai_shareai'] ) || $settings['enable_ai_shareai'] === false ) {
					$settings['enable_ai_shareai'] = true;
					$updated                       = true;
				}
				if ( ! isset( $settings['enable_ai_gemini'] ) || $settings['enable_ai_gemini'] === false ) {
					$settings['enable_ai_gemini'] = true;
					$updated                      = true;
				}
				if ( ! isset( $settings['enable_ai_openai'] ) || $settings['enable_ai_openai'] === false ) {
					$settings['enable_ai_openai'] = true;
					$updated                      = true;
				}
				if ( ! isset( $settings['shortcode_ai_provider'] ) || empty( $settings['shortcode_ai_provider'] ) ) {
					$settings['shortcode_ai_provider'] = 'gemini'; // Default to Gemini since it's free
					$updated                           = true;
				}
			}

			// Save updated settings if defaults were applied.
			if ( $updated ) {
				update_option( 'aiohm_booking_settings', $settings );
			}

			self::$cached_settings = $settings;
			self::$cache_cleared   = false;
		}
		return self::$cached_settings;
	}

	/**
	 * Get a specific plugin setting.
	 *
	 * @since  2.0.0
	 *
	 * @param string $key The setting key.
	 * @param mixed  $default_value The default value if the key is not found.
	 *
	 * @return mixed The setting value or default.
	 */
	public static function get( $key, $default_value = '' ) {
		$settings = self::get_all();
		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Update plugin settings.
	 *
	 * @since  2.0.0
	 *
	 * @param array $settings The new settings array.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function update( $settings ) {
		self::clear_cache();

		// Validate settings before saving.
		if ( ! is_array( $settings ) ) {
			return false;
		}

		// Check if settings actually changed.
		$current_option   = get_option( 'aiohm_booking_settings', array() );
		$settings_changed = ( wp_json_encode( $current_option ) !== wp_json_encode( $settings ) );

		// Try different approaches for problematic saves.
		if ( $settings_changed ) {
			// First try: Standard update_option.
			$result = update_option( 'aiohm_booking_settings', $settings );

			// If that fails, try delete and add.
			if ( ! $result ) {
				delete_option( 'aiohm_booking_settings' );
				$result = add_option( 'aiohm_booking_settings', $settings );

				// If that also fails, try with autoload disabled.
				if ( ! $result ) {
					delete_option( 'aiohm_booking_settings' );
					$result = add_option( 'aiohm_booking_settings', $settings, '', 'no' );
				}
			}
		} else {
			$result = true; // No change needed.
		}

		self::clear_cache(); // Clear again after update.
		return $result;
	}

	/**
	 * Save settings (alias for update).
	 *
	 * @since  2.0.0
	 *
	 * @param array $settings The new settings array.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function save_settings( $settings ) {
		return self::update( $settings );
	}

	/**
	 * Update multiple settings by merging with existing ones.
	 *
	 * @since  2.0.0
	 *
	 * @param array $new_settings The new settings to merge.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function update_multiple( $new_settings ) {
		$current_settings = self::get_all();
		$updated_settings = array_merge( $current_settings, $new_settings );
		return self::update( $updated_settings );
	}

	/**
	 * Clear the settings cache.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public static function clear_cache() {
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( 'aiohm_booking_settings', 'options' );
		}
		self::$cached_settings = null;
		self::$cache_cleared   = true;
	}
}
