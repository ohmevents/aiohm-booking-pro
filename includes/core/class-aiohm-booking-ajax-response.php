<?php
/**
 * AJAX Response Helper for AIOHM Booking
 *
 * Centralized AJAX response utilities for consistent error and success handling.
 * Standardizes wp_send_json_error and wp_send_json_success patterns across
 * the plugin to ensure consistent API responses.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 *
 * @author OHM Events Agency <https://www.ohm.events>
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Response Helper Class
 *
 * Provides standardized methods for AJAX responses with consistent
 * error codes, messages, and data structures.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Ajax_Response {

	/**
	 * Common error codes
	 */
	const ERROR_SECURITY_FAILED          = 'security_check_failed';
	const ERROR_INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';
	const ERROR_INVALID_DATA             = 'invalid_data';
	const ERROR_VALIDATION_FAILED        = 'validation_failed';
	const ERROR_NOT_FOUND                = 'not_found';
	const ERROR_SYSTEM_ERROR             = 'system_error';
	const ERROR_PAYMENT_FAILED           = 'payment_failed';
	const ERROR_BOOKING_FAILED           = 'booking_failed';

	/**
	 * Send standardized security error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $custom_message Optional custom message.
	 */
	public static function security_error( $custom_message = '' ) {
		$message = ! empty( $custom_message ) ? $custom_message : __( 'Security check failed. Please refresh the page and try again.', 'aiohm-booking-pro' );

		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => self::ERROR_SECURITY_FAILED,
				'data'    => array(
					'action_required' => 'refresh_page',
				),
			)
		);
	}

	/**
	 * Send standardized permission error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $custom_message Optional custom message.
	 */
	public static function permission_error( $custom_message = '' ) {
		$message = ! empty( $custom_message ) ? $custom_message : __( 'You do not have sufficient permissions to perform this action.', 'aiohm-booking-pro' );

		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => self::ERROR_INSUFFICIENT_PERMISSIONS,
				'data'    => array(
					'required_capability' => 'manage_options',
				),
			)
		);
	}

	/**
	 * Send standardized validation error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $message Error message.
	 * @param array  $validation_errors Optional validation error details.
	 */
	public static function validation_error( $message, $validation_errors = array() ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => self::ERROR_VALIDATION_FAILED,
				'data'    => array(
					'validation_errors' => $validation_errors,
				),
			)
		);
	}

	/**
	 * Send standardized not found error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $resource_type Type of resource not found.
	 * @param mixed  $resource_id Optional resource identifier.
	 */
	public static function not_found_error( $resource_type, $resource_id = null ) {
		$message = $resource_id
			? sprintf(
				/* translators: %1$s: resource type, %2$s: resource ID */
				__( '%1$s with ID %2$s not found.', 'aiohm-booking-pro' ),
				ucfirst( $resource_type ),
				$resource_id
			)
			: sprintf(
				/* translators: %s: resource type */
				__( '%s not found.', 'aiohm-booking-pro' ),
				ucfirst( $resource_type )
			);

		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => self::ERROR_NOT_FOUND,
				'data'    => array(
					'resource_type' => $resource_type,
					'resource_id'   => $resource_id,
				),
			)
		);
	}

	/**
	 * Send standardized system error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $message Error message.
	 * @param string $system_component Optional system component name.
	 */
	public static function system_error( $message, $system_component = '' ) {
		$formatted_message = $system_component
			? sprintf(
				/* translators: %1$s: system component, %2$s: error message */
				__( '%1$s error: %2$s', 'aiohm-booking-pro' ),
				$system_component,
				$message
			)
			: $message;

		wp_send_json_error(
			array(
				'message' => $formatted_message,
				'code'    => self::ERROR_SYSTEM_ERROR,
				'data'    => array(
					'component'   => $system_component,
					'raw_message' => $message,
				),
			)
		);
	}

	/**
	 * Send standardized payment error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $message Payment error message.
	 * @param array  $payment_data Optional payment context data.
	 */
	public static function payment_error( $message, $payment_data = array() ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => self::ERROR_PAYMENT_FAILED,
				'data'    => array_merge(
					array(
						'payment_context' => 'error',
					),
					$payment_data
				),
			)
		);
	}

	/**
	 * Send standardized booking error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $message Booking error message.
	 * @param array  $booking_data Optional booking context data.
	 */
	public static function booking_error( $message, $booking_data = array() ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => self::ERROR_BOOKING_FAILED,
				'data'    => array_merge(
					array(
						'booking_context' => 'error',
					),
					$booking_data
				),
			)
		);
	}

	/**
	 * Send standardized success response
	 *
	 * @since  2.0.0
	 *
	 * @param mixed  $data Success data.
	 * @param string $message Optional success message.
	 */
	public static function success( $data = array(), $message = '' ) {
		$response = is_array( $data ) ? $data : array( 'result' => $data );

		if ( $message ) {
			$response['message'] = $message;
		}

		wp_send_json_success( $response );
	}

	/**
	 * Send payment success response
	 *
	 * @since  2.0.0
	 *
	 * @param array  $payment_data Payment result data.
	 * @param string $message Optional success message.
	 */
	public static function payment_success( $payment_data, $message = '' ) {
		$default_message = __( 'Payment processed successfully.', 'aiohm-booking-pro' );
		$response        = array_merge(
			array(
				'payment_status' => 'success',
				'message'        => ! empty( $message ) ? $message : $default_message,
			),
			$payment_data
		);

		wp_send_json_success( $response );
	}

	/**
	 * Send booking success response
	 *
	 * @since  2.0.0
	 *
	 * @param array  $booking_data Booking result data.
	 * @param string $message Optional success message.
	 */
	public static function booking_success( $booking_data, $message = '' ) {
		$default_message = __( 'Booking completed successfully.', 'aiohm-booking-pro' );
		$response        = array_merge(
			array(
				'booking_status' => 'success',
				'message'        => ! empty( $message ) ? $message : $default_message,
			),
			$booking_data
		);

		wp_send_json_success( $response );
	}

	/**
	 * Send generic error response
	 *
	 * @since  2.0.0
	 *
	 * @param string $message Error message.
	 * @param string $code Optional error code.
	 * @param array  $data Optional additional data.
	 */
	public static function error( $message, $code = '', $data = array() ) {
		$response = array(
			'message' => $message,
			'code'    => ! empty( $code ) ? $code : self::ERROR_SYSTEM_ERROR,
		);

		if ( ! empty( $data ) ) {
			$response['data'] = $data;
		}

		wp_send_json_error( $response );
	}

	/**
	 * Handle WP_Error objects and send appropriate response
	 *
	 * @since  2.0.0
	 *
	 * @param WP_Error $error WP_Error object.
	 */
	public static function wp_error( WP_Error $error ) {
		$error_code    = $error->get_error_code();
		$error_message = $error->get_error_message();
		$error_data    = $error->get_error_data();

		// Map common WordPress error codes to our standardized codes.
		$code_mapping = array(
			'nonce_verification_failed' => self::ERROR_SECURITY_FAILED,
			'insufficient_permissions'  => self::ERROR_INSUFFICIENT_PERMISSIONS,
			'invalid_data'              => self::ERROR_INVALID_DATA,
			'validation_failed'         => self::ERROR_VALIDATION_FAILED,
			'not_found'                 => self::ERROR_NOT_FOUND,
		);

		$standardized_code = $code_mapping[ $error_code ] ?? $error_code;

		wp_send_json_error(
			array(
				'message' => $error_message,
				'code'    => $standardized_code,
				'data'    => $error_data,
			)
		);
	}

	/**
	 * Send response for multiple errors
	 *
	 * @since  2.0.0
	 *
	 * @param array  $errors Array of error messages or WP_Error objects.
	 * @param string $main_message Main error message.
	 */
	public static function multiple_errors( $errors, $main_message = '' ) {
		$error_list = array();

		foreach ( $errors as $error ) {
			if ( is_wp_error( $error ) ) {
				$error_list[] = array(
					'code'    => $error->get_error_code(),
					'message' => $error->get_error_message(),
					'data'    => $error->get_error_data(),
				);
			} else {
				$error_list[] = array(
					'message' => (string) $error,
				);
			}
		}

		$message = ! empty( $main_message ) ? $main_message : __( 'Multiple errors occurred during processing.', 'aiohm-booking-pro' );

		wp_send_json_error(
			array(
				'message' => $message,
				'code'    => 'multiple_errors',
				'data'    => array(
					'errors'      => $error_list,
					'error_count' => count( $error_list ),
				),
			)
		);
	}

	/**
	 * Check if this is an AJAX request
	 *
	 * @since  2.0.0
	 *
	 * @return bool True if AJAX request
	 */
	public static function is_ajax_request() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Ensure we're in an AJAX context, otherwise redirect
	 *
	 * @since  2.0.0
	 *
	 * @param string $redirect_url Optional redirect URL for non-AJAX requests.
	 */
	public static function require_ajax_context( $redirect_url = '' ) {
		if ( ! self::is_ajax_request() ) {
			if ( $redirect_url ) {
				wp_safe_redirect( $redirect_url );
			} else {
				wp_safe_redirect( admin_url() );
			}
			exit;
		}
	}
}
