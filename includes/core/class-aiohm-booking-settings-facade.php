<?php

namespace AIOHM_Booking_PRO\Core;
/**
 * Unified Settings Facade
 *
 * Provides a unified interface for all settings operations across the plugin,
 * integrating global settings, module settings, and validation.
 *
 * @package AIOHM_Booking
 * @since 1.2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unified Settings Facade Class
 *
 * Centralizes all settings operations with consistent validation and storage.
 */
class \Core\AIOHM_BOOKING_Settings_Facade {

	/**
	 * Get global plugin settings
	 *
	 * @param string $key Optional specific setting key.
	 * @param mixed  $default_value Default value if key not found.
	 * @return mixed Setting value or all settings if no key specified.
	 */
	public static function get_global_setting( $key = null, $default_value = null ) {
		$settings = get_option( 'aiohm_booking_settings', array() );

		if ( null === $key ) {
			return $settings;
		}

		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Update global plugin settings
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool Success status.
	 */
	public static function update_global_setting( $key, $value ) {
		$settings         = self::get_global_setting();
		$settings[ $key ] = $value;

		return update_option( 'aiohm_booking_settings', $settings );
	}

	/**
	 * Get module-specific settings
	 *
	 * @param string $module_id Module identifier.
	 * @param string $key Optional specific setting key.
	 * @param mixed  $default_value Default value if key not found.
	 * @return mixed Setting value or all module settings if no key specified.
	 */
	public static function get_module_setting( $module_id, $key = null, $default_value = null ) {
		$option_key = "aiohm_booking_{$module_id}_settings";
		$settings   = get_option( $option_key, array() );

		if ( null === $key ) {
			return $settings;
		}

		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Update module-specific settings
	 *
	 * @param string $module_id Module identifier.
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool Success status.
	 */
	public static function update_module_setting( $module_id, $key, $value ) {
		$option_key       = "aiohm_booking_{$module_id}_settings";
		$settings         = self::get_module_setting( $module_id );
		$settings[ $key ] = $value;

		return update_option( $option_key, $settings );
	}

	/**
	 * Save module settings with validation
	 *
	 * @param string $module_id Module identifier.
	 * @param array  $settings Raw settings data.
	 * @param array  $fields Field definitions for validation.
	 * @return array|WP_Error Validated settings or error.
	 */
	public static function save_module_settings( $module_id, $settings, $fields ) {
		try {
			// Use the existing settings manager for validation and sanitization.
			if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Settings_Manager' ) ) {
				$sanitized = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Settings_Manager::sanitize_module_settings( $settings, $fields );
				$errors    = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Settings_Manager::validate_required_fields( $settings, $fields );

				if ( ! empty( $errors ) ) {
					return new WP_Error( 'validation_failed', __( 'Settings validation failed', 'aiohm-booking-pro' ), $errors );
				}
			} else {
				$sanitized = $settings;
			}

			$option_key = "aiohm_booking_{$module_id}_settings";
			$success    = update_option( $option_key, $sanitized );

			if ( ! $success ) {
				AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
					"Failed to save settings for module: {$module_id}",
					'settings_error',
					array( 'module_id' => $module_id )
				);
				return new WP_Error( 'save_failed', __( 'Failed to save settings', 'aiohm-booking-pro' ) );
			}

			return $sanitized;

		} catch ( Exception $e ) {
			AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
				'Exception saving module settings: ' . $e->getMessage(),
				'exception_error',
				array(
					'module_id' => $module_id,
					'exception' => get_class( $e ),
					'trace'     => $e->getTraceAsString(),
				)
			);
			return new WP_Error( 'exception', __( 'An error occurred while saving settings', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Get all settings for a module with defaults applied
	 *
	 * @param string $module_id Module identifier.
	 * @param array  $fields Field definitions with defaults.
	 * @return array Settings with defaults applied.
	 */
	public static function get_module_settings_with_defaults( $module_id, $fields ) {
		$settings = self::get_module_setting( $module_id );

		// Apply defaults for missing fields.
		foreach ( $fields as $field_id => $field ) {
			if ( ! isset( $settings[ $field_id ] ) ) {
				$settings[ $field_id ] = $field['default'] ?? '';
			}
		}

		return $settings;
	}

	/**
	 * Delete module settings
	 *
	 * @param string $module_id Module identifier.
	 * @return bool Success status.
	 */
	public static function delete_module_settings( $module_id ) {
		$option_key = "aiohm_booking_{$module_id}_settings";
		return delete_option( $option_key );
	}

	/**
	 * Check if a module is configured (has required settings)
	 *
	 * @param string $module_id Module identifier.
	 * @param array  $required_fields Required field keys.
	 * @return bool True if configured.
	 */
	public static function is_module_configured( $module_id, $required_fields = array() ) {
		$settings = self::get_module_setting( $module_id );

		foreach ( $required_fields as $field ) {
			if ( empty( $settings[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get settings summary for admin display
	 *
	 * @param string $module_id Module identifier.
	 * @return array Summary data.
	 */
	public static function get_settings_summary( $module_id ) {
		return array(
			'module_id'      => $module_id,
			'is_configured'  => self::is_module_configured( $module_id ),
			'last_updated'   => get_option( "_aiohm_booking_{$module_id}_last_updated" ),
			'settings_count' => count( self::get_module_setting( $module_id ) ),
		);
	}

	/**
	 * Bulk update multiple settings
	 *
	 * @param array $updates Array of updates with module_id, key, value.
	 * @return array Results of each update.
	 */
	public static function bulk_update( $updates ) {
		$results = array();

		foreach ( $updates as $update ) {
			$module_id = $update['module_id'] ?? '';
			$key       = $update['key'] ?? '';
			$value     = $update['value'] ?? '';

			if ( empty( $module_id ) || empty( $key ) ) {
				$results[] = array(
					'success' => false,
					'error'   => 'Missing module_id or key',
				);
				continue;
			}

			try {
				if ( 'global' === $module_id ) {
					$success = self::update_global_setting( $key, $value );
				} else {
					$success = self::update_module_setting( $module_id, $key, $value );
				}

				$results[] = array(
					'success'   => $success,
					'module_id' => $module_id,
					'key'       => $key,
				);

			} catch ( Exception $e ) {
				$results[] = array(
					'success'   => false,
					'error'     => $e->getMessage(),
					'module_id' => $module_id,
					'key'       => $key,
				);
			}
		}

		return $results;
	}
}
