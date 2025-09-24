<?php

namespace AIOHM_Booking_PRO\Core;
/**
 * Module Settings Manager
 *
 * Handles settings validation, sanitization, and processing for all modules.
 * Extracts complex logic from the abstract module class to reduce complexity.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Manager class for handling module settings operations
 */
class AIOHM_Booking_Module_Settings_Manager {

	/**
	 * Main entry point for sanitizing module settings
	 *
	 * @param array $input  The input settings to sanitize.
	 * @param array $fields The field definitions.
	 * @return array The sanitized settings.
	 */
	public static function sanitize_module_settings( $input, $fields ) {
		if ( ! is_array( $input ) || ! is_array( $fields ) ) {
			return array();
		}

		// Process checkbox fields first (they need special handling when unchecked).
		$prepared_input = self::process_checkbox_fields( $input, $fields );

		// Sanitize individual fields.
		$sanitized = self::sanitize_individual_fields( $prepared_input, $fields );

		// Apply default values for missing fields.
		$sanitized = self::apply_default_values( $sanitized, $fields );

		// Process hardcoded checkbox fields for backward compatibility.
		$sanitized = self::process_legacy_checkbox_fields( $input, $sanitized );

		return $sanitized;
	}

	/**
	 * Process checkbox fields to handle unchecked state
	 *
	 * @param array $input  The input settings.
	 * @param array $fields The field definitions.
	 * @return array The processed input.
	 */
	private static function process_checkbox_fields( $input, $fields ) {
		$processed = $input;

		foreach ( $fields as $field_id => $field ) {
			if ( 'checkbox' === ( $field['type'] ?? '' ) ) {
				// If checkbox is not in input, it means it's unchecked.
				if ( ! isset( $processed[ $field_id ] ) ) {
					$processed[ $field_id ] = 0;
				}
			}
		}

		return $processed;
	}

	/**
	 * Sanitize individual field values
	 *
	 * @param array $input  The input settings.
	 * @param array $fields The field definitions.
	 * @return array The sanitized values.
	 */
	private static function sanitize_individual_fields( $input, $fields ) {
		$sanitized = array();

		foreach ( $fields as $field_id => $field ) {
			if ( isset( $input[ $field_id ] ) ) {
				$value                  = $input[ $field_id ];
				$sanitized[ $field_id ] = self::sanitize_individual_field( $value, $field );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single field value
	 *
	 * @param mixed $value The field value to sanitize.
	 * @param array $field The field definition.
	 * @return mixed The sanitized value.
	 */
	private static function sanitize_individual_field( $value, $field ) {
		// Use field renderer factory for sanitization if available.
		if ( class_exists( 'AIOHM_Booking_Field_Renderer_Factory' ) ) {
			return AIOHM_Booking_Field_Renderer_Factory::sanitize_field( $value, $field );
		}

		// Fallback to built-in sanitization methods.
		return self::sanitize_field_fallback( $value, $field );
	}

	/**
	 * Apply default values for missing fields
	 *
	 * @param array $sanitized The sanitized values.
	 * @param array $fields    The field definitions.
	 * @return array The values with defaults applied.
	 */
	private static function apply_default_values( $sanitized, $fields ) {
		$result = $sanitized;

		foreach ( $fields as $field_id => $field ) {
			if ( ! isset( $result[ $field_id ] ) ) {
				$result[ $field_id ] = self::get_field_default_value( $field );
			}
		}

		return $result;
	}

	/**
	 * Get default value for a field
	 *
	 * @param array $field The field definition.
	 * @return mixed The default value.
	 */
	private static function get_field_default_value( $field ) {
		$field_type = $field['type'] ?? 'text';

		// Return explicit default if set.
		if ( isset( $field['default'] ) ) {
			return $field['default'];
		}

		// Return type-appropriate defaults.
		switch ( $field_type ) {
			case 'checkbox':
				return 0;
			case 'number':
				return 0;
			case 'textarea':
				return '';
			default:
				return '';
		}
	}

	/**
	 * Process legacy checkbox fields for backward compatibility
	 *
	 * This handles hardcoded checkbox fields that were processed in the original method.
	 *
	 * @param array $input     The original input.
	 * @param array $sanitized The sanitized values.
	 * @return array The values with legacy checkboxes processed.
	 */
	private static function process_legacy_checkbox_fields( $input, $sanitized ) {
		$result = $sanitized;

		// Handle plugin integration checkboxes.
		$plugin_fields = array(
		);

		// Handle other common module checkboxes.
		$checkbox_fields = array(
			'enable_ai_analytics',
		);

		$all_checkbox_fields = array_merge( $plugin_fields, $checkbox_fields );

		foreach ( $all_checkbox_fields as $checkbox_field ) {
			$result[ $checkbox_field ] = isset( $input[ $checkbox_field ] ) ? 1 : 0;
		}

		return $result;
	}

	/**
	 * Validate required fields
	 *
	 * @param array $input  The input settings.
	 * @param array $fields The field definitions.
	 * @return array Array of validation errors (empty if valid).
	 */
	public static function validate_required_fields( $input, $fields ) {
		$errors = array();

		foreach ( $fields as $field_id => $field ) {
			$is_required = $field['required'] ?? false;

			if ( $is_required ) {
				$value = $input[ $field_id ] ?? '';

				if ( self::is_empty_value( $value ) ) {
					$field_label         = $field['label'] ?? ucfirst( str_replace( '_', ' ', $field_id ) );
					$errors[ $field_id ] = sprintf(
						/* translators: %s: field label */
						__( '%s is required.', 'aiohm-booking-pro' ),
						$field_label
					);
				}
			}
		}

		return $errors;
	}

	/**
	 * Check if a value is considered empty for validation purposes
	 *
	 * @param mixed $value The value to check.
	 * @return bool Whether the value is empty.
	 */
	private static function is_empty_value( $value ) {
		if ( is_string( $value ) ) {
			return '' === trim( $value );
		}

		if ( is_array( $value ) ) {
			return empty( $value );
		}

		return empty( $value );
	}

	/**
	 * Fallback sanitization for field values when field renderer factory is not available
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	private static function sanitize_field_fallback( $value, $field ) {
		$field_type = $field['type'] ?? 'text';

		// Skip custom fields as they handle their own data.
		if ( 'custom' === $field_type ) {
			return $value;
		}

		switch ( $field_type ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'number':
				return self::sanitize_number_field( $value, $field );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'checkbox':
				return self::sanitize_checkbox_value( $value );

			case 'select':
				return self::sanitize_select_value( $value, $field );

			case 'color':
				return self::sanitize_color_value( $value );

			case 'password':
				// Don't sanitize passwords, just ensure they're strings.
				return is_string( $value ) ? $value : '';

			default:
				return self::sanitize_text_value( $value, $field );
		}
	}

	/**
	 * Sanitize number field value
	 *
	 * @param mixed $value The value to sanitize.
	 * @param array $field The field configuration.
	 * @return float|int The sanitized number.
	 */
	private static function sanitize_number_field( $value, $field ) {
		if ( ! is_numeric( $value ) ) {
			return $field['default'] ?? 0;
		}

		$number = floatval( $value );

		// Apply min/max constraints if specified.
		if ( isset( $field['min'] ) && $number < $field['min'] ) {
			$number = $field['min'];
		}

		if ( isset( $field['max'] ) && $number > $field['max'] ) {
			$number = $field['max'];
		}

		// Return as integer if step is 1 or not specified.
		$step = $field['step'] ?? 1;
		if ( 1 === $step && floor( $number ) == $number ) {
			return intval( $number );
		}

		return $number;
	}

	/**
	 * Sanitize checkbox value
	 *
	 * @param mixed $value The value to sanitize.
	 * @return int The sanitized checkbox value (0 or 1).
	 */
	private static function sanitize_checkbox_value( $value ) {
		return ! empty( $value ) ? 1 : 0;
	}

	/**
	 * Sanitize select field value
	 *
	 * @param mixed $value The value to sanitize.
	 * @param array $field The field configuration.
	 * @return string The sanitized select value.
	 */
	private static function sanitize_select_value( $value, $field ) {
		$options = $field['options'] ?? array();

		// If value is not in allowed options, return default or first option.
		if ( ! array_key_exists( $value, $options ) ) {
			if ( isset( $field['default'] ) ) {
				return $field['default'];
			}

			$option_keys = array_keys( $options );
			return ! empty( $option_keys ) ? $option_keys[0] : '';
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize color field value
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string The sanitized color value.
	 */
	private static function sanitize_color_value( $value ) {
		// Use centralized color validation.
		return AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_hex_color( $value );
	}

	/**
	 * Sanitize text value with custom callback support
	 *
	 * @param mixed $value The value to sanitize.
	 * @param array $field The field configuration.
	 * @return string The sanitized text value.
	 */
	private static function sanitize_text_value( $value, $field ) {
		$sanitize_callback = $field['sanitize_callback'] ?? 'sanitize_text_field';

		if ( is_callable( $sanitize_callback ) ) {
			return call_user_func( $sanitize_callback, $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Clean input data by removing empty values and unwanted keys
	 *
	 * @param array $input  The input data to clean.
	 * @param array $fields The allowed field definitions.
	 * @return array The cleaned input data.
	 */
	public static function clean_input_data( $input, $fields ) {
		if ( ! is_array( $input ) || ! is_array( $fields ) ) {
			return array();
		}

		$cleaned      = array();
		$allowed_keys = array_keys( $fields );

		foreach ( $input as $key => $value ) {
			// Only allow known field keys.
			if ( in_array( $key, $allowed_keys, true ) ) {
				$cleaned[ $key ] = $value;
			}
		}

		return $cleaned;
	}

	/**
	 * Prepare settings for database storage
	 *
	 * @param array $settings The settings to prepare.
	 * @return array The prepared settings.
	 */
	public static function prepare_for_storage( $settings ) {
		if ( ! is_array( $settings ) ) {
			return array();
		}

		// Remove any null values.
		$prepared = array_filter(
			$settings,
			function ( $value ) {
				return null !== $value;
			}
		);

		// Convert boolean values to integers for consistency.
		foreach ( $prepared as $key => $value ) {
			if ( is_bool( $value ) ) {
				$prepared[ $key ] = $value ? 1 : 0;
			}
		}

		return $prepared;
	}
}
