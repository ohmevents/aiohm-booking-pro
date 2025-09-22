<?php
/**
 * Admin Settings Management System.
 *
 * Handles all settings validation, sanitization, schema management, and
 * administrative functionality for the AIOHM Booking plugin settings.
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
 * Admin Settings Management Class.
 *
 * Provides comprehensive settings management including validation,
 * sanitization, schema management, and backup/restore capabilities.
 *
 * @since 1.0.0
 */
class AIOHM_BOOKING_Admin_Settings {

	/**
	 * Settings schema for validation.
	 *
	 * @var array
	 */
	private static $settings_schema = null;

	/**
	 * Settings backup transient key.
	 *
	 * @var string
	 */
	const BACKUP_TRANSIENT_KEY = 'aiohm_booking_settings_backup';

	/**
	 * Maximum recursion depth for array sanitization.
	 *
	 * @var int
	 */
	const MAX_RECURSION_DEPTH = 5;

	/**
	 * Initialize the Admin Settings class.
	 *
	 * Sets up WordPress hooks and filters for settings management.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'pre_update_option_aiohm_booking_settings', array( __CLASS__, 'validate_before_save' ), 10, 2 );
	}

	/**
	 * Register WordPress settings with proper sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'aiohm_booking_settings_group',
			'aiohm_booking_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::get_default_settings(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Get the complete settings schema for validation.
	 *
	 * @since 1.0.0
	 *
	 * @return array Settings schema definition
	 */
	public static function get_settings_schema() {
		if ( null === self::$settings_schema ) {
			self::$settings_schema = array(
				// Global Settings.
				'currency'                => array(
					'type'     => 'string',
					'default'  => 'EUR',
					'validate' => 'currency_code',
				),
				'currency_position'       => array(
					'type'     => 'string',
					'default'  => 'before',
					'validate' => array( 'before', 'after' ),
				),
				'decimal_separator'       => array(
					'type'     => 'string',
					'default'  => '.',
					'validate' => 'single_char',
				),
				'thousand_separator'      => array(
					'type'     => 'string',
					'default'  => ',',
					'validate' => 'single_char',
				),
				'plugin_language'         => array(
					'type'     => 'string',
					'default'  => 'en_US',
					'validate' => 'locale',
				),
				'deposit_percent'         => array(
					'type'     => 'integer',
					'default'  => 20,
					'validate' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'min_age'                 => array(
					'type'     => 'integer',
					'default'  => 18,
					'validate' => array(
						'min' => 0,
						'max' => 120,
					),
				),
				'company_name'            => array(
					'type'     => 'string',
					'default'  => '',
					'validate' => 'text_field',
				),
				'company_email'           => array(
					'type'     => 'string',
					'default'  => '',
					'validate' => 'email',
				),

				// Module Enable/Disable Settings.
				'enable_accommodations'   => array(
					'type'     => 'boolean',
					'default'  => true,
					'validate' => 'boolean',
				),
				'enable_notifications'    => array(
					'type'     => 'boolean',
					'default'  => true,
					'validate' => 'boolean',
				),
				'enable_tickets'          => array(
					'type'     => 'boolean',
					'default'  => true,
					'validate' => 'boolean',
				),
				'enable_orders'           => array(
					'type'     => 'boolean',
					'default'  => true,
					'validate' => 'boolean',
				),
				'enable_calendar'         => array(
					'type'     => 'boolean',
					'default'  => true,
					'validate' => 'boolean',
				),
				'enable_css_manager'      => array(
					'type'     => 'boolean',
					'default'  => false,
					'validate' => 'boolean',
				),
				'enable_early_bird'       => array(
					'type'     => 'boolean',
					'default'  => false,
					'validate' => 'boolean',
				),
				'early_bird_days'         => array(
					'type'     => 'integer',
					'default'  => 30,
					'validate' => 'integer',
					'min'      => 1,
					'max'      => 365,
				),
				'default_earlybird_price' => array(
					'type'     => 'number',
					'default'  => 0,
					'validate' => 'number',
					'min'      => 0,
				),

				// Module Order.
				'module_order'            => array(
					'type'     => 'array',
					'default'  => array(),
					'validate' => 'array',
				),
			);

			// Add AI-related settings only for premium users
			if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
				$ai_settings = array(
					'enable_ai_analytics'     => array(
						'type'     => 'boolean',
						'default'  => true,
						'validate' => 'boolean',
					),

					// AI Provider Settings.
					'ai_shareai_api_key'      => array(
						'type'     => 'string',
						'default'  => '',
						'validate' => 'api_key',
					),
					'ai_openai_api_key'       => array(
						'type'     => 'string',
						'default'  => '',
						'validate' => 'api_key',
					),
					'ai_gemini_api_key'       => array(
						'type'     => 'string',
						'default'  => '',
						'validate' => 'api_key',
					),
					'ai_ollama_server_url'    => array(
						'type'     => 'string',
						'default'  => 'http://localhost:11434',
						'validate' => 'url',
					),
					'default_ai_provider'     => array(
						'type'     => 'string',
						'default'  => 'shareai',
						'validate' => array( 'shareai', 'openai', 'gemini', 'ollama' ),
					),

					// AI Consent Settings.
					'ai_consent_shareai'      => array(
						'type'     => 'boolean',
						'default'  => false,
						'validate' => 'boolean',
					),
					'ai_consent_openai'       => array(
						'type'     => 'boolean',
						'default'  => false,
						'validate' => 'boolean',
					),
					'ai_consent_gemini'       => array(
						'type'     => 'boolean',
						'default'  => false,
						'validate' => 'boolean',
					),
					'ai_consent_ollama'       => array(
						'type'     => 'boolean',
						'default'  => true,
						'validate' => 'boolean',
					),
				);

				self::$settings_schema = array_merge( self::$settings_schema, $ai_settings );

				// Add payment-related settings only for premium users
				$payment_settings = array(
					'enable_stripe'           => array(
						'type'     => 'boolean',
						'default'  => true,
						'validate' => 'boolean',
					),
					'enable_paypal'           => array(
						'type'     => 'boolean',
						'default'  => false, // Disabled in v2.0.0
						'validate' => 'boolean',
					),
				);

				self::$settings_schema = array_merge( self::$settings_schema, $payment_settings );
			}
		}

		return self::$settings_schema;
	}

	/**
	 * Get default settings based on schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Default settings array
	 */
	public static function get_default_settings() {
		$defaults = array();
		$schema   = self::get_settings_schema();

		foreach ( $schema as $key => $config ) {
			$defaults[ $key ] = $config['default'];
		}

		return $defaults;
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * Enhanced version of the original sanitization with schema-based validation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The settings array to sanitize.
	 *
	 * @return array The sanitized settings
	 */
	public static function sanitize_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return self::get_default_settings();
		}

		// Get current settings to merge with.
		static $current_settings = null;
		if ( null === $current_settings ) {
			$current_settings = get_option( 'aiohm_booking_settings', self::get_default_settings() );
		}

		$schema    = self::get_settings_schema();
		$sanitized = array();

		foreach ( $settings as $key => $value ) {
			if ( isset( $schema[ $key ] ) ) {
				$sanitized[ $key ] = self::sanitize_field( $value, $schema[ $key ] );
			} else {
				// Handle unknown fields with generic sanitization.
				$sanitized[ $key ] = self::sanitize_unknown_field( $value );
			}
		}

		// Merge with existing settings to preserve settings not in the form.
		return array_merge( $current_settings, $sanitized );
	}

	/**
	 * Sanitize a field based on its schema configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value  The value to sanitize.
	 * @param array $config The field configuration from schema.
	 *
	 * @return mixed Sanitized value
	 */
	private static function sanitize_field( $value, $config ) {
		$type     = $config['type'] ?? 'string';
		$validate = $config['validate'] ?? null;
		$default  = $config['default'] ?? null;

		switch ( $type ) {
			case 'boolean':
				return self::sanitize_boolean( $value );

			case 'integer':
				return self::sanitize_integer( $value, $validate );

			case 'string':
				return self::sanitize_string( $value, $validate );

			case 'array':
				return self::sanitize_array_field( $value );

			default:
				return $default;
		}
	}

	/**
	 * Sanitize boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return bool Sanitized boolean value
	 */
	private static function sanitize_boolean( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
		}

		return (bool) $value;
	}

	/**
	 * Sanitize integer values with validation.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value    Value to sanitize.
	 * @param mixed $validate Validation rules.
	 *
	 * @return int Sanitized integer value
	 */
	private static function sanitize_integer( $value, $validate = null ) {
		$int_value = absint( $value );

		if ( is_array( $validate ) ) {
			if ( isset( $validate['min'] ) && $int_value < $validate['min'] ) {
				return $validate['min'];
			}
			if ( isset( $validate['max'] ) && $int_value > $validate['max'] ) {
				return $validate['max'];
			}
		}

		return $int_value;
	}

	/**
	 * Sanitize string values with validation.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value    Value to sanitize.
	 * @param mixed $validate Validation type or allowed values.
	 *
	 * @return string Sanitized string value
	 */
	private static function sanitize_string( $value, $validate = null ) {
		if ( ! is_string( $value ) ) {
			$value = (string) $value;
		}

		// Apply validation-specific sanitization.
		switch ( $validate ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'api_key':
				return self::sanitize_api_key( $value );

			case 'currency_code':
				return self::sanitize_currency_code( $value );

			case 'locale':
				return self::sanitize_locale( $value );

			case 'single_char':
				return substr( sanitize_text_field( $value ), 0, 1 );

			case 'text_field':
				return sanitize_text_field( $value );

			default:
				// Check if validate is an array of allowed values.
				if ( is_array( $validate ) ) {
					return in_array( $value, $validate, true ) ? $value : $validate[0];
				}
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize array fields.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Array value to sanitize.
	 *
	 * @return array Sanitized array
	 */
	private static function sanitize_array_field( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return self::sanitize_array_recursive( $value );
	}

	/**
	 * Recursively sanitize array settings without database calls.
	 *
	 * Enhanced version from original Admin class.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Array value to sanitize.
	 * @param int   $depth Current recursion depth.
	 *
	 * @return array Sanitized array
	 */
	private static function sanitize_array_recursive( $value, $depth = 0 ) {
		if ( ! is_array( $value ) || $depth > self::MAX_RECURSION_DEPTH ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $k => $v ) {
			$sanitized_key = sanitize_key( $k );

			if ( is_array( $v ) ) {
				$sanitized[ $sanitized_key ] = self::sanitize_array_recursive( $v, $depth + 1 );
			} elseif ( is_bool( $v ) ) {
				$sanitized[ $sanitized_key ] = (bool) $v;
			} elseif ( is_numeric( $v ) ) {
				$sanitized[ $sanitized_key ] = is_float( $v + 0 ) ? (float) $v : (int) $v;
			} else {
				$sanitized[ $sanitized_key ] = sanitize_text_field( (string) $v );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize unknown fields with generic sanitization.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return mixed Sanitized value
	 */
	private static function sanitize_unknown_field( $value ) {
		if ( is_array( $value ) ) {
			return self::sanitize_array_recursive( $value );
		} elseif ( is_bool( $value ) ) {
			return (bool) $value;
		} elseif ( is_numeric( $value ) ) {
			return is_float( $value + 0 ) ? (float) $value : (int) $value;
		} else {
			return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Sanitize API keys.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key API key to sanitize.
	 *
	 * @return string Sanitized API key
	 */
	private static function sanitize_api_key( $api_key ) {
		$api_key = trim( $api_key );

		// Remove dangerous characters but allow API key formats.
		$api_key = preg_replace( '/[<>\'"]/', '', $api_key );

		// Validate format (alphanumeric, hyphens, underscores, dots).
		if ( ! empty( $api_key ) && ! preg_match( '/^[a-zA-Z0-9\-_.]+$/', $api_key ) ) {
			return '';
		}

		// Minimum length check.
		if ( strlen( $api_key ) > 0 && strlen( $api_key ) < 10 ) {
			return '';
		}

		return $api_key;
	}

	/**
	 * Sanitize currency codes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $currency_code Currency code to validate.
	 *
	 * @return string Valid currency code
	 */
	private static function sanitize_currency_code( $currency_code ) {
		$currency_code = strtoupper( trim( $currency_code ) );

		// List of common currency codes.
		$valid_currencies = array(
			'USD',
			'EUR',
			'GBP',
			'JPY',
			'AUD',
			'CAD',
			'CHF',
			'CNY',
			'SEK',
			'NZD',
			'MXN',
			'SGD',
			'HKD',
			'NOK',
			'TRY',
			'RUB',
			'INR',
			'BRL',
			'ZAR',
			'RON',
			'THB',
			'KRW',
		);

		return in_array( $currency_code, $valid_currencies, true ) ? $currency_code : 'EUR';
	}

	/**
	 * Sanitize locale codes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale code to validate.
	 *
	 * @return string Valid locale code
	 */
	private static function sanitize_locale( $locale ) {
		$locale = trim( $locale );

		// Basic locale format validation (language_COUNTRY).
		if ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $locale ) ) {
			return $locale;
		}

		return 'en_US';
	}

	/**
	 * Validate settings before saving (pre-update hook).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $new_value New settings value.
	 * @param mixed $old_value Current settings value.
	 *
	 * @return mixed Validated settings value
	 */
	public static function validate_before_save( $new_value, $old_value ) {
		// Create backup before major changes.
		if ( is_array( $old_value ) && count( $old_value ) > 0 ) {
			self::create_settings_backup( $old_value );
		}

		// Perform additional validation.
		$validated = self::perform_advanced_validation( $new_value, $old_value );

		return $validated;
	}

	/**
	 * Perform advanced validation on settings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $new_value New settings.
	 * @param mixed $old_value Current settings.
	 *
	 * @return mixed Validated settings
	 */
	private static function perform_advanced_validation( $new_value, $old_value ) {
		if ( ! is_array( $new_value ) ) {
			return $old_value;
		}

		$schema = self::get_settings_schema();

		// Check for critical setting changes that might break functionality.
		foreach ( $new_value as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) {
				continue;
			}

			$config = $schema[ $key ];

			// Special validation for critical settings.
			if ( 'default_ai_provider' === $key ) {
				$api_key_field = 'ai_' . $value . '_api_key';
				$consent_field = 'ai_consent_' . $value;

				// Check if API key exists for the selected provider (except Ollama).
				if ( 'ollama' !== $value && empty( $new_value[ $api_key_field ] ?? '' ) ) {
					// Keep the old provider if no API key is set.
					if ( isset( $old_value[ $key ] ) ) {
						$new_value[ $key ] = $old_value[ $key ];
					}
				}

				// Check consent for the provider.
				if ( empty( $new_value[ $consent_field ] ?? false ) ) {
					// Keep the old provider if consent not given.
					if ( isset( $old_value[ $key ] ) ) {
						$new_value[ $key ] = $old_value[ $key ];
					}
				}
			}
		}

		return $new_value;
	}

	/**
	 * Create a backup of current settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Settings to backup.
	 *
	 * @return bool True on success, false on failure
	 */
	public static function create_settings_backup( $settings ) {
		$backup_data = array(
			'timestamp' => current_time( 'timestamp' ),
			'settings'  => $settings,
			'version'   => AIOHM_BOOKING_VERSION,
		);

		return set_transient( self::BACKUP_TRANSIENT_KEY, $backup_data, DAY_IN_SECONDS );
	}

	/**
	 * Restore settings from backup.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on success, false on failure
	 */
	public static function restore_settings_backup() {
		$backup = get_transient( self::BACKUP_TRANSIENT_KEY );

		if ( ! $backup || ! isset( $backup['settings'] ) ) {
			return false;
		}

		$result = update_option( 'aiohm_booking_settings', $backup['settings'] );

		if ( $result ) {
			// Clear cache after restore.
			AIOHM_BOOKING_Settings::clear_cache();
			delete_transient( self::BACKUP_TRANSIENT_KEY );
		}

		return $result;
	}

	/**
	 * Export settings for backup or migration.
	 *
	 * @since 1.0.0
	 *
	 * @return array|false Settings export data or false on failure
	 */
	public static function export_settings() {
		$settings = get_option( 'aiohm_booking_settings', array() );

		if ( empty( $settings ) ) {
			return false;
		}

		return array(
			'export_timestamp'  => current_time( 'timestamp' ),
			'plugin_version'    => AIOHM_BOOKING_VERSION,
			'wordpress_version' => get_bloginfo( 'version' ),
			'settings'          => $settings,
			'schema_version'    => '1.0.0',
		);
	}

	/**
	 * Import settings from export data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $import_data Export data to import.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public static function import_settings( $import_data ) {
		if ( ! is_array( $import_data ) || ! isset( $import_data['settings'] ) ) {
			return new WP_Error( 'invalid_import', __( 'Invalid import data format.', 'aiohm-booking-pro' ) );
		}

		// Validate import data structure.
		if ( ! is_array( $import_data['settings'] ) ) {
			return new WP_Error( 'invalid_settings', __( 'Settings data is not valid.', 'aiohm-booking-pro' ) );
		}

		// Create backup before import.
		$current_settings = get_option( 'aiohm_booking_settings', array() );
		self::create_settings_backup( $current_settings );

		// Sanitize imported settings.
		$sanitized_settings = self::sanitize_settings( $import_data['settings'] );

		// Update settings.
		$result = update_option( 'aiohm_booking_settings', $sanitized_settings );

		if ( $result ) {
			// Clear cache after import.
			AIOHM_SETTINGS::clear_cache();
			return true;
		}

		return new WP_Error( 'import_failed', __( 'Failed to save imported settings.', 'aiohm-booking-pro' ) );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $create_backup Whether to create backup before reset.
	 *
	 * @return bool True on success, false on failure
	 */
	public static function reset_to_defaults( $create_backup = true ) {
		if ( $create_backup ) {
			$current_settings = get_option( 'aiohm_booking_settings', array() );
			self::create_settings_backup( $current_settings );
		}

		$defaults = self::get_default_settings();
		$result   = update_option( 'aiohm_booking_settings', $defaults );

		if ( $result ) {
			AIOHM_BOOKING_Settings::clear_cache();
		}

		return $result;
	}

	/**
	 * Get option name for consistent naming.
	 *
	 * @since 1.0.0
	 *
	 * @return string The main settings option name
	 */
	public static function get_option_name() {
		return 'aiohm_booking_settings';
	}

	/**
	 * Check if backup exists.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if backup exists, false otherwise
	 */
	public static function has_backup() {
		$backup = get_transient( self::BACKUP_TRANSIENT_KEY );
		return ! empty( $backup ) && isset( $backup['settings'] );
	}

	/**
	 * Get backup information.
	 *
	 * @since 1.0.0
	 *
	 * @return array|false Backup info or false if no backup
	 */
	public static function get_backup_info() {
		$backup = get_transient( self::BACKUP_TRANSIENT_KEY );

		if ( ! $backup || ! isset( $backup['timestamp'] ) ) {
			return false;
		}

		return array(
			'timestamp' => $backup['timestamp'],
			'version'   => $backup['version'] ?? 'unknown',
			'age'       => current_time( 'timestamp' ) - $backup['timestamp'],
		);
	}
}
