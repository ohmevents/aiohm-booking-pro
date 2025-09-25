<?php
/**
 * Security Helper for AIOHM Booking
 *
 * Centralized security utilities for nonce verification, sanitization,
 * and common security patterns used throughout the plugin.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 *
 * @author OHM Events Agency
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Helper Class
 *
 * Provides centralized security methods for nonce verification,
 * data sanitization, and permission checking.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Security_Helper {

	/**
	 * Verify AJAX nonce and return sanitized value
	 *
	 * Combines nonce verification with sanitization in a single method
	 * to reduce code duplication across AJAX handlers.
	 *
	 * @since  2.0.0
	 *
	 * @param string $action Nonce action name.
	 * @param string $nonce_key POST key containing the nonce. Default 'nonce'.
	 * @param bool   $send_json_error Whether to send JSON error response on failure.
	 * @return bool True if nonce is valid, false otherwise
	 */
	public static function verify_ajax_nonce( $action, $nonce_key = 'nonce', $send_json_error = true ) {
		$nonce = self::sanitize_post_field( $nonce_key );

		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_' . $action ) ) {
			if ( $send_json_error ) {
				wp_send_json_error(
					array(
						'message' => __( 'Security check failed. Please refresh the page and try again.', 'aiohm-booking-pro' ),
						'code'    => 'security_check_failed',
					)
				);
			}
			return false;
		}

		return true;
	}

	/**
	 * Verify nonce from any source (POST, GET, etc.)
	 *
	 * @since  2.0.0
	 *
	 * @param string $nonce Nonce value to verify.
	 * @param string $action Nonce action name.
	 * @return bool True if nonce is valid
	 */
	public static function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, 'aiohm_booking_' . $action );
	}

	/**
	 * Create nonce for plugin actions
	 *
	 * @since  2.0.0
	 *
	 * @param string $action Action name.
	 * @return string Nonce value
	 */
	public static function create_nonce( $action ) {
		return wp_create_nonce( 'aiohm_booking_' . $action );
	}

	/**
	 * Sanitize POST field with proper unslashing
	 *
	 * Centralizes the common pattern of sanitizing POST data
	 * with proper WordPress unslashing.
	 *
	 * @since  2.0.0
	 *
	 * @param string $key POST key to sanitize.
	 * @param mixed  $default_value Default value if key doesn't exist.
	 * @param string $sanitize_type Type of sanitization to apply.
	 * @param string $nonce_action Nonce action for verification (optional).
	 * @param string $nonce_name Nonce field name (optional).
	 * @return mixed Sanitized value
	 */
	public static function sanitize_post_field( $key, $default_value = '', $sanitize_type = 'text', $nonce_action = '', $nonce_name = '_wpnonce' ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			return $default_value;
		}

		// Verify nonce if provided.
		if ( ! empty( $nonce_action ) ) {
			if ( ! isset( $_POST[ $nonce_name ] ) ) {
				return $default_value;
			}
			$nonce_value = sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) );
			if ( ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
				return $default_value;
			}
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

		switch ( $sanitize_type ) {
			case 'email':
				return sanitize_email( $value );
			case 'url':
				return esc_url_raw( $value );
			case 'int':
				return intval( $value );
			case 'float':
				return floatval( $value );
			case 'boolean':
				return (bool) $value;
			case 'textarea':
				return sanitize_textarea_field( $value );
			case 'key':
				return sanitize_key( $value );
			case 'array':
				return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : array();
			case 'text':
			default:
				return $value; // Already sanitized above.
		}
	}

	/**
	 * Sanitize multiple POST fields at once
	 *
	 * @since  2.0.0
	 *
	 * @param array $fields Array of field configurations: key => sanitize_type or key => [type, default].
	 * @return array Sanitized data
	 */
	public static function sanitize_post_fields( array $fields ) {
		$sanitized = array();

		foreach ( $fields as $key => $config ) {
			if ( is_string( $config ) ) {
				// Simple format: field name maps to sanitize type.
				$sanitized[ $key ] = self::sanitize_post_field( $key, '', $config );
			} elseif ( is_array( $config ) ) {
				// Advanced format: field name maps to array with type and default.
				$type              = $config[0] ?? 'text';
				$default           = $config[1] ?? '';
				$sanitized[ $key ] = self::sanitize_post_field( $key, $default, $type );
			}
		}

		return $sanitized;
	}

	/**
	 * Verify user capability for plugin operations
	 *
	 * @since  2.0.0
	 *
	 * @param string $capability Required capability.
	 * @param bool   $send_json_error Whether to send JSON error on failure.
	 * @return bool True if user has capability
	 */
	public static function verify_capability( $capability = 'manage_options', $send_json_error = true ) {
		if ( ! current_user_can( $capability ) ) {
			if ( $send_json_error ) {
				wp_send_json_error(
					array(
						'message' => __( 'You do not have sufficient permissions to perform this action.', 'aiohm-booking-pro' ),
						'code'    => 'insufficient_permissions',
					)
				);
			}
			return false;
		}

		return true;
	}

	/**
	 * Complete AJAX security check (nonce + capability)
	 *
	 * Performs both nonce verification and capability check in one call
	 * for maximum security with minimal code duplication.
	 *
	 * @since  2.0.0
	 *
	 * @param string $action Nonce action name.
	 * @param string $capability Required capability. Default 'manage_options'.
	 * @param string $nonce_key POST key containing the nonce. Default 'nonce'.
	 * @return bool True if both checks pass
	 */
	public static function verify_ajax_security( $action, $capability = 'manage_options', $nonce_key = 'nonce' ) {
		// Check nonce first.
		if ( ! self::verify_ajax_nonce( $action, $nonce_key, true ) ) {
			return false;
		}

		// Check capability.
		if ( ! self::verify_capability( $capability, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize and validate booking data
	 *
	 * @since  2.0.0
	 *
	 * @param array $data Raw booking data.
	 * @return array Sanitized booking data
	 */
	public static function sanitize_booking_data( array $data ) {
		$sanitized = array();

		// Define expected fields and their sanitization types.
		$field_rules = array(
			'checkin_date'     => 'text',
			'checkout_date'    => 'text',
			'guests'           => 'int',
			'accommodations'   => 'array',
			'customer_name'    => 'text',
			'customer_email'   => 'email',
			'customer_phone'   => 'text',
			'special_requests' => 'textarea',
			'payment_method'   => 'key',
			'private_booking'  => 'boolean',
			'event_id'         => 'int',
		);

		foreach ( $field_rules as $field => $type ) {
			if ( isset( $data[ $field ] ) ) {
				switch ( $type ) {
					case 'email':
						$sanitized[ $field ] = sanitize_email( $data[ $field ] );
						break;
					case 'int':
						$sanitized[ $field ] = intval( $data[ $field ] );
						break;
					case 'boolean':
						$sanitized[ $field ] = (bool) $data[ $field ];
						break;
					case 'textarea':
						$sanitized[ $field ] = sanitize_textarea_field( $data[ $field ] );
						break;
					case 'key':
						$sanitized[ $field ] = sanitize_key( $data[ $field ] );
						break;
					case 'array':
						$sanitized[ $field ] = is_array( $data[ $field ] )
							? array_map( 'intval', $data[ $field ] )
							: array();
						break;
					case 'text':
					default:
						$sanitized[ $field ] = sanitize_text_field( $data[ $field ] );
						break;
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Check if current request is a valid AJAX request
	 *
	 * @since  2.0.0
	 *
	 * @return bool True if valid AJAX request
	 */
	public static function is_valid_ajax_request() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX && check_ajax_referer( false, false, false );
	}

	/**
	 * Validate and sanitize file upload
	 *
	 * @since  2.0.0
	 *
	 * @param array $file $_FILES array element.
	 * @param array $allowed_types Allowed file types.
	 * @param int   $max_size Maximum file size in bytes.
	 * @return array|WP_Error Sanitized file data or error
	 */
	public static function validate_file_upload( $file, $allowed_types = array(), $max_size = 2097152 ) {
		if ( empty( $file ) || ! is_array( $file ) ) {
			return new WP_Error( 'no_file', __( 'No file was uploaded.', 'aiohm-booking-pro' ) );
		}

		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error( 'upload_error', __( 'File upload failed.', 'aiohm-booking-pro' ) );
		}

		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds maximum allowed size.', 'aiohm-booking-pro' ) );
		}

		$file_type = wp_check_filetype( $file['name'] );
		if ( ! empty( $allowed_types ) && ! in_array( $file_type['type'], $allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'File type is not allowed.', 'aiohm-booking-pro' ) );
		}

		return array(
			'name'     => sanitize_file_name( $file['name'] ),
			'type'     => $file_type['type'],
			'tmp_name' => $file['tmp_name'],
			'size'     => $file['size'],
		);
	}

	/**
	 * Log security events for audit trail
	 *
	 * @since  2.0.0
	 *
	 * @param string $event Event description.
	 * @param array  $context Additional context data.
	 */
	public static function log_security_event( $event, $context = array() ) {
		return;
	}
}
