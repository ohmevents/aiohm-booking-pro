<?php

namespace AIOHM_Booking_PRO\Core;

use AIOHM_Booking_PRO\Core\\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings as Settings;

/**
 * Admin AJAX Handler.
 *
 * Handles all AJAX requests for the WordPress admin interface,
 * providing secure endpoints for settings management and administrative operations.
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
 * Admin AJAX Handler Class.
 *
 * Manages all AJAX requests for admin functionality with proper security,
 * validation, and error handling.
 *
 * @since 1.0.0
 */
class AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Admin_Ajax {

	/**
	 * Initialize the Ajax handler.
	 *
	 * Sets up WordPress AJAX hooks for all admin endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		// Global settings management.
		add_action( 'wp_ajax_aiohm_booking_save_global_settings', array( __CLASS__, 'ajax_save_global_settings' ) );
		add_action( 'wp_ajax_aiohm_booking_save_toggle_setting', array( __CLASS__, 'ajax_save_toggle_setting' ) );

		// AI provider management.
		add_action( 'wp_ajax_aiohm_booking_save_api_key', array( __CLASS__, 'ajax_save_api_key' ) );
		add_action( 'wp_ajax_aiohm_booking_save_server_url', array( __CLASS__, 'ajax_save_server_url' ) );
		add_action( 'wp_ajax_aiohm_booking_test_server', array( __CLASS__, 'ajax_test_server' ) );
		add_action( 'wp_ajax_aiohm_booking_save_ai_consent', array( __CLASS__, 'ajax_save_ai_consent' ) );
		add_action( 'wp_ajax_aiohm_booking_set_default_provider', array( __CLASS__, 'ajax_set_default_provider' ) );

		// AI provider test connections are handled by individual provider modules
		// to prevent conflicts with their own AJAX handlers
		// add_action( 'wp_ajax_aiohm_booking_test_openai', array( __CLASS__, 'ajax_test_ai_provider' ) );
		// add_action( 'wp_ajax_aiohm_booking_test_gemini', array( __CLASS__, 'ajax_test_ai_provider' ) );
		// add_action( 'wp_ajax_aiohm_booking_test_shareai', array( __CLASS__, 'ajax_test_ai_provider' ) );
		// Note: Ollama test connection is handled by its own module class.
		// Stripe test connection is now handled by the Stripe module itself
		// add_action( 'wp_ajax_aiohm_booking_test_stripe', array( __CLASS__, 'ajax_test_stripe_connection' ) );
		add_action( 'wp_ajax_aiohm_booking_test_paypal', array( __CLASS__, 'ajax_test_paypal_connection' ) );

		// Accommodation management.
		// Note: wp_ajax_aiohm_booking_save_individual_accommodation is handled by Accommodation module

		// Module management.
		add_action( 'wp_ajax_aiohm_save_grid_module_order', array( __CLASS__, 'ajax_save_grid_module_order' ) );

		// Email and notifications.
		add_action( 'wp_ajax_aiohm_test_mautic_connection', array( __CLASS__, 'ajax_test_mautic_connection' ) );
		add_action( 'wp_ajax_aiohm_send_test_email', array( __CLASS__, 'ajax_send_test_email' ) );
	}

	/**
	 * Verify AJAX request security.
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce_action The nonce action to verify against.
	 *
	 * @return bool True if verification passes.
	 */
	private static function verify_ajax_request( $nonce_action = 'aiohm_booking_admin_nonce' ) {
		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'aiohm-booking-pro' ) );
			return false;
		}

		// Verify nonce.
		$nonce = isset( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_send_json_error( __( 'Invalid security token', 'aiohm-booking-pro' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check memory and data size limits.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if limits are acceptable.
	 */
	private static function check_resource_limits() {
		// Memory safeguards.
		$memory_limit     = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
		$memory_used      = memory_get_usage( true );
		$memory_available = $memory_limit - $memory_used;

		// Require at least 32MB available.
		if ( $memory_available < ( 32 * 1024 * 1024 ) ) {
			wp_send_json_error( __( 'Insufficient memory available. Please contact your hosting provider.', 'aiohm-booking-pro' ) );
			return false;
		}

		// Limit POST data size to prevent memory exhaustion.
		$post_size = strlen( wp_json_encode( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $post_size > ( 2 * 1024 * 1024 ) ) { // 2MB limit.
			wp_send_json_error( __( 'Settings data too large. Please reduce the amount of data.', 'aiohm-booking-pro' ) );
			return false;
		}

		return true;
	}

	/**
	 * AJAX handler for saving global settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_global_settings() {
		if ( ! self::verify_ajax_request() || ! self::check_resource_limits() ) {
			return;
		}

		// Get current settings.
		$current_settings = get_option( 'aiohm_booking_settings', array() );

		// Define allowed global settings fields.
		$global_fields = array(
			'currency',
			'currency_position',
			'decimal_separator',
			'thousand_separator',
			'plugin_language',
			'deposit_percent',
			'min_age',
			'company_name',
			'company_email',
		);

		$updated = false;

		foreach ( $global_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( 'company_email' === $field ) {
					$current_settings[ $field ] = sanitize_email( wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				} elseif ( in_array( $field, array( 'deposit_percent', 'min_age' ), true ) ) {
					$current_settings[ $field ] = absint( wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				} else {
					$current_settings[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				}
				$updated = true;
			}
		}

		if ( $updated ) {
			$result = update_option( 'aiohm_booking_settings', $current_settings );
			if ( $result ) {
				wp_send_json_success(
					array(
						'message' => __( 'Global settings saved successfully!', 'aiohm-booking-pro' ),
					)
				);
			} else {
				wp_send_json_error( __( 'Failed to save settings. Please try again.', 'aiohm-booking-pro' ) );
			}
		} else {
			wp_send_json_error( __( 'No settings data received.', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for saving API keys.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_api_key() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$api_key  = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $provider ) ) {
			wp_send_json_error( __( 'Provider not specified', 'aiohm-booking-pro' ) );
		}

		// Validate provider.
		$valid_providers = array( 'shareai', 'openai', 'gemini', 'ollama' );
		if ( ! in_array( $provider, $valid_providers, true ) ) {
			/* translators: %s: provider name */
			wp_send_json_error( sprintf( __( 'Invalid provider: %s', 'aiohm-booking-pro' ), $provider ) );
		}

		// Get current settings.
		$settings = get_option( 'aiohm_booking_settings', array() );

		// Update API key for the specific provider.
		$key_field              = 'ai_' . $provider . '_api_key';
		$settings[ $key_field ] = $api_key;

		// Update settings.
		$updated = update_option( 'aiohm_booking_settings', $settings );

		// Verify the save was successful.
		if ( $updated || get_option( 'aiohm_booking_settings' )[ $key_field ] === $api_key ) {
			$message = empty( $api_key ) ? __( 'API key cleared successfully!', 'aiohm-booking-pro' ) : __( 'API key saved successfully!', 'aiohm-booking-pro' );
			wp_send_json_success( $message );
		} else {
			wp_send_json_error( __( 'Failed to save API key', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for saving server URLs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_server_url() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$provider   = sanitize_text_field( wp_unslash( $_POST['provider'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$server_url = esc_url_raw( wp_unslash( $_POST['server_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $provider ) ) {
			wp_send_json_error( __( 'Provider not specified', 'aiohm-booking-pro' ) );
		}

		// Validate provider (only ollama uses server URL).
		if ( 'ollama' !== $provider ) {
			wp_send_json_error( __( 'Server URL is only supported for Ollama provider', 'aiohm-booking-pro' ) );
		}

		// Get current settings.
		$settings = get_option( 'aiohm_booking_settings', array() );

		// Update server URL for Ollama.
		$key_field              = 'ai_' . $provider . '_server_url';
		$settings[ $key_field ] = $server_url;

		$updated = update_option( 'aiohm_booking_settings', $settings );

		// Verify the save was successful.
		if ( $updated || get_option( 'aiohm_booking_settings' )[ $key_field ] === $server_url ) {
			$message = empty( $server_url ) ? __( 'Server URL cleared successfully!', 'aiohm-booking-pro' ) : __( 'Server URL saved successfully!', 'aiohm-booking-pro' );
			wp_send_json_success( $message );
		} else {
			wp_send_json_error( __( 'Failed to save server URL', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for testing server connections.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_test_server() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$provider   = sanitize_text_field( wp_unslash( $_POST['provider'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$server_url = esc_url_raw( wp_unslash( $_POST['server_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $provider ) || empty( $server_url ) ) {
			wp_send_json_error( __( 'Provider and server URL are required', 'aiohm-booking-pro' ) );
		}

		// Validate provider (only ollama uses server URL).
		if ( 'ollama' !== $provider ) {
			wp_send_json_error( __( 'Server testing is only supported for Ollama provider', 'aiohm-booking-pro' ) );
		}

		// Basic server URL validation.
		if ( ! filter_var( $server_url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( __( 'Invalid server URL format', 'aiohm-booking-pro' ) );
		}

		// Test server connection by making a simple request to /api/tags endpoint.
		$test_url = rtrim( $server_url, '/' ) . '/api/tags';
		$response = wp_remote_get(
			$test_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			/* translators: %s: error message */
			wp_send_json_error( sprintf( __( 'Connection failed: %s', 'aiohm-booking-pro' ), $response->get_error_message() ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 === $response_code ) {
			$data = json_decode( $response_body, true );
			if ( $data && isset( $data['models'] ) ) {
				$model_count = count( $data['models'] );
				/* translators: %d: number of models */
				wp_send_json_success( sprintf( __( 'Server connection successful! Found %d available models.', 'aiohm-booking-pro' ), $model_count ) );
			} else {
				wp_send_json_success( __( 'Server connection successful!', 'aiohm-booking-pro' ) );
			}
		} else {
			/* translators: %d: HTTP status code */
			wp_send_json_error( sprintf( __( 'Server responded with status %d. Please check your Ollama server.', 'aiohm-booking-pro' ), $response_code ) );
		}
	}

	/**
	 * AJAX handler for saving toggle settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_toggle_setting() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$setting = sanitize_text_field( wp_unslash( $_POST['setting'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$value   = intval( $_POST['value'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $setting ) ) {
			wp_send_json_error( __( 'Setting not specified', 'aiohm-booking-pro' ) );
		}

		// Get current settings.
		$settings             = get_option( 'aiohm_booking_settings', array() );
		$settings[ $setting ] = $value;

		// Handle module dependencies
		$dependent_modules = array();
		if ( $setting === 'enable_accommodations' ) {
			if ( $value == 0 ) {
				// When accommodations is disabled, also disable calendar
				$settings['enable_calendar'] = 0;
				$dependent_modules[]         = array(
					'module' => 'calendar',
					'action' => 'disabled',
				);
			} else {
				// When accommodations is re-enabled, also re-enable calendar
				$settings['enable_calendar'] = 1;
				$dependent_modules[]         = array(
					'module' => 'calendar',
					'action' => 'enabled',
				);
			}
		} elseif ( $setting === 'enable_ai_analytics' ) {
			if ( $value == 0 ) {
				// When AI Analytics is disabled, disable all AI provider modules
				$ai_modules = array( 'ollama', 'openai', 'gemini', 'shareai' );
				foreach ( $ai_modules as $ai_module ) {
					$settings[ 'enable_' . $ai_module ] = 0;
					$dependent_modules[]                = array(
						'module' => $ai_module,
						'action' => 'disabled',
					);
				}
			} else {
				// When AI Analytics is re-enabled, re-enable AI provider modules
				$ai_modules = array( 'ollama', 'openai', 'gemini', 'shareai' );
				foreach ( $ai_modules as $ai_module ) {
					$settings[ 'enable_' . $ai_module ] = 1;
					$dependent_modules[]                = array(
						'module' => $ai_module,
						'action' => 'enabled',
					);
				}
			}
		}

		$update_result = update_option( 'aiohm_booking_settings', $settings );

		// Verify the setting was actually saved (update_option returns false if value is unchanged)
		$saved_settings = get_option( 'aiohm_booking_settings', array() );
		$setting_saved  = isset( $saved_settings[ $setting ] ) && $saved_settings[ $setting ] == $value;

		if ( $update_result || $setting_saved ) {
			$response_data = array( 'message' => __( 'Setting updated successfully!', 'aiohm-booking-pro' ) );

			// Include dependent modules in response
			if ( ! empty( $dependent_modules ) ) {
				$response_data['dependent_modules'] = $dependent_modules;

				$action_text = '';
				foreach ( $dependent_modules as $dep_info ) {
					if ( $dep_info['module'] === 'calendar' ) {
						$action_text = $dep_info['action'] === 'disabled' ? 'disabled' : 'enabled';
						break;
					}
				}

				/* translators: %s: action text (enabled/disabled) */
				$response_data['message'] = sprintf( __( 'Settings updated successfully! Calendar module was also %s.', 'aiohm-booking-pro' ), $action_text );
			}

			wp_send_json_success( $response_data );
		} else {
			wp_send_json_error( __( 'Failed to update setting', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for saving AI consent.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_ai_consent() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$consent  = intval( $_POST['consent'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $provider ) ) {
			wp_send_json_error( __( 'Provider is required', 'aiohm-booking-pro' ) );
		}

		// Get current settings.
		$settings                   = get_option( 'aiohm_booking_settings', array() );
		$consent_field              = 'ai_consent_' . $provider;
		$settings[ $consent_field ] = $consent;

		if ( update_option( 'aiohm_booking_settings', $settings ) ) {
			wp_send_json_success( __( 'AI consent preference saved successfully!', 'aiohm-booking-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to save AI consent preference', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for setting default AI provider.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_set_default_provider() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $provider ) ) {
			wp_send_json_error( __( 'Provider not specified', 'aiohm-booking-pro' ) );
		}

		// Get current settings.
		$settings                        = get_option( 'aiohm_booking_settings', array() );
		$settings['default_ai_provider'] = $provider;

		if ( update_option( 'aiohm_booking_settings', $settings ) ) {
			wp_send_json_success( __( 'Default provider set successfully!', 'aiohm-booking-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to set default provider', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for saving individual accommodations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_individual_accommodation() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$accommodation_index = intval( wp_unslash( $_POST['accommodation_index'] ?? -1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		// Get accommodation data and sanitize immediately.
		$raw_accommodation_data = isset( $_POST['accommodation_data'] ) ? wp_unslash( $_POST['accommodation_data'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Data is sanitized with map_deep below
		$accommodation_data     = map_deep( $raw_accommodation_data, 'sanitize_text_field' );

		if ( $accommodation_index < 0 ) {
			wp_send_json_error( __( 'Invalid accommodation index', 'aiohm-booking-pro' ) );
		}

		// Get current accommodation details.
		$accommodation_details = get_option( 'aiohm_booking_accommodations_details', array() );

		// Determine default title if empty.
		$settings           = Settings::get_all();
		$accommodation_type = $settings['accommodation_product_name'] ?? 'room';
		$type_labels        = array(
			'room'      => 'Room',
			'house'     => 'House',
			'apartment' => 'Apartment',
			'villa'     => 'Villa',
			'bungalow'  => 'Bungalow',
			'cabin'     => 'Cabin',
			'cottage'   => 'Cottage',
			'suite'     => 'Suite',
			'studio'    => 'Studio',
			'unit'      => 'Unit',
			'space'     => 'Space',
			'venue'     => 'Venue',
		);
		$default_singular   = $type_labels[ $accommodation_type ] ?? 'Room';

		$incoming_title = trim( (string) ( $accommodation_data['title'] ?? '' ) );
		if ( '' === $incoming_title ) {
			$incoming_title = $default_singular . ' ' . ( $accommodation_index + 1 );
		}

		// Sanitize the data.
		$sanitized_data = array(
			'title'           => sanitize_text_field( $incoming_title ),
			'description'     => sanitize_textarea_field( $accommodation_data['description'] ?? '' ),
			'earlybird_price' => sanitize_text_field( $accommodation_data['earlybird_price'] ?? '' ),
			'price'           => sanitize_text_field( $accommodation_data['price'] ?? '' ),
			'type'            => sanitize_text_field( $accommodation_data['type'] ?? 'room' ),
		);

		// Update the specific accommodation.
		$accommodation_details[ $accommodation_index ] = $sanitized_data;

		// Save to database.
		$result = update_option( 'aiohm_booking_accommodations_details', $accommodation_details );

		// Check if the data was actually saved.
		$saved_data = get_option( 'aiohm_booking_accommodations_details', array() );
		$was_saved  = isset( $saved_data[ $accommodation_index ] ) &&
					$saved_data[ $accommodation_index ]['title'] === $sanitized_data['title'] &&
					$saved_data[ $accommodation_index ]['type'] === $sanitized_data['type'];

		if ( $was_saved ) {
			wp_send_json_success(
				array(
					'message'            => __( 'Accommodation saved successfully!', 'aiohm-booking-pro' ),
					'accommodation_data' => $sanitized_data,
					'debug_info'         => array(
						'index'                => $accommodation_index,
						'total_accommodations' => count( $accommodation_details ),
						'update_result'        => $result,
					),
				)
			);
		} else {
			wp_send_json_error( __( 'Database update failed - data verification failed', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for saving grid module order.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_save_grid_module_order() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$module_order = sanitize_text_field( wp_unslash( $_POST['module_order'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $module_order ) ) {
			wp_send_json_error( __( 'Module order cannot be empty', 'aiohm-booking-pro' ) );
		}

		// Convert comma-separated string to array.
		$order_array = array_map( 'sanitize_text_field', explode( ',', $module_order ) );

		// Get current settings.
		$settings = get_option( 'aiohm_booking_settings', array() );

		// Update module order.
		$settings['module_order'] = $order_array;

		// Save settings.
		if ( update_option( 'aiohm_booking_settings', $settings ) ) {
			wp_send_json_success( __( 'Module order saved successfully!', 'aiohm-booking-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to save module order', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX handler for testing Mautic connection.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_test_mautic_connection() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$base_url = sanitize_url( wp_unslash( $_POST['mautic_base_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$username = sanitize_text_field( wp_unslash( $_POST['mautic_username'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$password = sanitize_text_field( wp_unslash( $_POST['mautic_password'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $base_url ) || empty( $username ) || empty( $password ) ) {
			wp_send_json_error( __( 'All Mautic connection fields are required', 'aiohm-booking-pro' ) );
		}

		// Test connection to Mautic API.
		$api_url = rtrim( $base_url, '/' ) . '/api/contacts';

		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
				'Content-Type'  => 'application/json',
			),
			'timeout' => 10,
		);

		$response = wp_remote_get( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( __( 'Connection failed: ', 'aiohm-booking-pro' ) . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 === $status_code ) {
			wp_send_json_success( array( 'message' => __( 'Mautic connection successful!', 'aiohm-booking-pro' ) ) );
		} else {
			$body          = wp_remote_retrieve_body( $response );
			$error_message = json_decode( $body, true );
			$message       = $error_message['error']['message'] ?? __( 'Invalid credentials or API access', 'aiohm-booking-pro' );
			wp_send_json_error( $message );
		}
	}

	/**
	 * AJAX handler for sending test emails.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_send_test_email() {
		if ( ! self::verify_ajax_request() ) {
			return;
		}

		$to_email    = sanitize_email( wp_unslash( $_POST['to_email'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$subject     = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$content     = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sender_name = sanitize_text_field( wp_unslash( $_POST['sender_name'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$reply_to    = sanitize_email( wp_unslash( $_POST['reply_to'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $to_email ) || empty( $subject ) || empty( $content ) ) {
			wp_send_json_error( __( 'Email address, subject, and content are required', 'aiohm-booking-pro' ) );
		}

		// Set up email headers.
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( ! empty( $sender_name ) && ! empty( $reply_to ) ) {
			$headers[] = 'From: ' . $sender_name . ' <' . $reply_to . '>';
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		// Send the test email.
		$result = wp_mail( $to_email, $subject, $content, $headers );

		if ( $result ) {
			// Log the test email.
			self::log_email_activity( $to_email, $subject, 'sent' );
			wp_send_json_success( __( 'Test email sent successfully!', 'aiohm-booking-pro' ) );
		} else {
			// Log the failed email.
			self::log_email_activity( $to_email, $subject, 'failed' );
			wp_send_json_error( __( 'Failed to send test email', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Log email activity for debugging and tracking.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to_email The recipient email.
	 * @param string $subject  The email subject.
	 * @param string $status   The email status (sent, failed).
	 *
	 * @return void
	 */
	private static function log_email_activity( $to_email, $subject, $status ) {
		$logs = get_transient( 'aiohm_email_logs' );
		if ( ! $logs || ! is_array( $logs ) ) {
			$logs = array();
		}

		$log_entry = array(
			'time'    => current_time( 'mysql' ),
			'to'      => $to_email,
			'subject' => $subject,
			'status'  => $status,
		);

		array_push( $logs, $log_entry );

		// Keep only last 50 logs.
		if ( count( $logs ) > 50 ) {
			$logs = array_slice( $logs, -50 );
		}

		set_transient( 'aiohm_email_logs', $logs, 30 * DAY_IN_SECONDS );
	}


	/**
	 * Test OpenAI connection.
	 *
	 * @param string $api_key  The API key.
	 * @param array  $settings The provider settings.
	 */
	private static function test_openai_connection( $api_key, $settings ) {
		$model = $settings['openai_model'] ?? 'gpt-3.5-turbo';

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => $model,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => 'Hello, this is a test message.',
							),
						),
						'max_tokens' => 10,
					)
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Connection failed: ' . $response->get_error_message() );
			return;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			$body  = wp_remote_retrieve_body( $response );
			$data  = json_decode( $body, true );
			$error = $data['error']['message'] ?? 'Unknown error';
			wp_send_json_error( "HTTP $status_code: $error" );
			return;
		}

		wp_send_json_success( __( 'OpenAI connection successful!', 'aiohm-booking-pro' ) );
	}

	/**
	 * Test Gemini connection.
	 *
	 * @param string $api_key  The API key.
	 * @param array  $settings The provider settings.
	 */
	private static function test_gemini_connection( $api_key, $settings ) {
		$model = $settings['gemini_model'] ?? 'gemini-pro';

		$response = wp_remote_post(
			"https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}",
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'contents' => array(
							array(
								'parts' => array(
									array(
										'text' => 'Hello, this is a test message.',
									),
								),
							),
						),
					)
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Connection failed: ' . $response->get_error_message() );
			return;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			$body  = wp_remote_retrieve_body( $response );
			$data  = json_decode( $body, true );
			$error = $data['error']['message'] ?? 'Unknown error';
			wp_send_json_error( "HTTP $status_code: $error" );
			return;
		}

		wp_send_json_success( __( 'Gemini connection successful!', 'aiohm-booking-pro' ) );
	}


	/**
	 * Test Ollama connection.
	 *
	 * @param array $settings The provider settings.
	 */
	private static function test_ollama_connection( $settings ) {
		$server_url = $settings['ollama_base_url'] ?? '';
		$model      = $settings['ollama_model'] ?? '';

		if ( empty( $server_url ) ) {
			wp_send_json_error( __( 'Server URL not configured', 'aiohm-booking-pro' ) );
			return;
		}

		$test_url = rtrim( $server_url, '/' ) . '/api/generate';

		$response = wp_remote_post(
			$test_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'  => $model,
						'prompt' => 'Hello, this is a test message.',
						'stream' => false,
					)
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Connection failed: ' . $response->get_error_message() );
			return;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			wp_send_json_error( "HTTP $status_code: Connection failed" );
			return;
		}

		wp_send_json_success( __( 'Ollama connection successful!', 'aiohm-booking-pro' ) );
	}

	/**
	 * AJAX handler for testing Stripe connection.
	 *
	 * @deprecated This method is now handled by the Stripe module itself
	 * @since 1.0.0
	 * @return void
	 */
	/*
	public static function ajax_test_stripe_connection() {
		if ( ! self::verify_ajax_request( 'aiohm_stripe_nonce' ) ) {
			return;
		}

		$settings   = get_option( 'aiohm_booking_stripe_settings', array() );
		$secret_key = $settings['stripe_secret_key'] ?? '';

		if ( empty( $secret_key ) ) {
			wp_send_json_error( __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			return;
		}

		// Test Stripe connection by retrieving account info.
		try {
			$stripe  = new \Stripe\StripeClient( $secret_key );
			$account = $stripe->accounts->retrieve();

			wp_send_json_success( __( 'Stripe connection successful!', 'aiohm-booking-pro' ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( 'Stripe connection failed: ' . $e->getMessage() );
		}
	}
	*/

	/**
	 * AJAX handler for testing PayPal connection.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ajax_test_paypal_connection() {
		if ( ! self::verify_ajax_request( 'aiohm_paypal_nonce' ) ) {
			return;
		}

		$settings      = get_option( 'aiohm_booking_paypal_settings', array() );
		$client_id     = $settings['paypal_client_id'] ?? '';
		$client_secret = $settings['paypal_client_secret'] ?? '';
		$mode          = $settings['paypal_mode'] ?? 'sandbox';

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			wp_send_json_error( __( 'PayPal credentials not configured', 'aiohm-booking-pro' ) );
			return;
		}

		// Test PayPal connection by getting access token.
		$base_url = 'sandbox' === $mode ? 'https://api.sandbox.paypal.com' : 'https://api.paypal.com';
		$auth_url = $base_url . '/v1/oauth2/token';

		$response = wp_remote_post(
			$auth_url,
			array(
				'headers' => array(
					'Accept'          => 'application/json',
					'Accept-Language' => 'en_US',
					'Authorization'   => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
					'Content-Type'    => 'application/x-www-form-urlencoded',
				),
				'body'    => 'grant_type=client_credentials',
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Connection failed: ' . $response->get_error_message() );
			return;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			wp_send_json_error( "HTTP $status_code: Authentication failed" );
			return;
		}

		wp_send_json_success( __( 'PayPal connection successful!', 'aiohm-booking-pro' ) );
	}
}
