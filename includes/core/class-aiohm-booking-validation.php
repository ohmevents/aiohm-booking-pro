<?php
/**
 * Validation and Sanitization Utilities for AIOHM Booking
 * Provides comprehensive validation, sanitization, and error handling.
 *
 * @package AIOHM_Booking
 *
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validation and Sanitization Class.
 */
class AIOHM_BOOKING_Validation {

	/**
	 * Error messages container.
	 *
	 * @var array
	 */
	private static $errors = array();

	/**
	 * Success messages container.
	 *
	 * @var array
	 */
	private static $messages = array();

	/**
	 * Validate booking data comprehensively.
	 *
	 * @param array  $data Booking data to validate.
	 * @param string $context Context of validation (frontend, admin, etc.).
	 */
	public static function validate_booking_data( $data, $context = 'frontend' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		self::clear_errors();

		if ( ! is_array( $data ) ) {
			self::add_error( 'invalid_data', __( 'Booking data must be an array', 'aiohm-booking-pro' ) );
			return false;
		}

		// Required fields validation.
		$required_fields = array( 'checkin_date', 'checkout_date', 'guests' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || self::is_empty( $data[ $field ] ) ) {
				// translators: %s: field name.
				self::add_error( 'missing_field', sprintf( __( 'Missing required field: %s', 'aiohm-booking-pro' ), $field ) );
			}
		}

		// Date validation.
		if ( ! empty( $data['checkin_date'] ) ) {
			if ( ! self::validate_date( $data['checkin_date'] ) ) {
				self::add_error( 'invalid_checkin_date', __( 'Invalid check-in date format', 'aiohm-booking-pro' ) );
			} elseif ( strtotime( $data['checkin_date'] ) < strtotime( 'today' ) ) {
				self::add_error( 'past_checkin_date', __( 'Check-in date cannot be in the past', 'aiohm-booking-pro' ) );
			}
		}

		if ( ! empty( $data['checkout_date'] ) ) {
			if ( ! self::validate_date( $data['checkout_date'] ) ) {
				self::add_error( 'invalid_checkout_date', __( 'Invalid check-out date format', 'aiohm-booking-pro' ) );
			}
		}

		// Date range validation.
		if ( ! empty( $data['checkin_date'] ) && ! empty( $data['checkout_date'] ) ) {
			if ( strtotime( $data['checkout_date'] ) <= strtotime( $data['checkin_date'] ) ) {
				self::add_error( 'invalid_date_range', __( 'Check-out date must be after check-in date', 'aiohm-booking-pro' ) );
			}

			// Maximum stay validation (30 days).
			$days_diff = ( strtotime( $data['checkout_date'] ) - strtotime( $data['checkin_date'] ) ) / ( 60 * 60 * 24 );
			if ( $days_diff > 30 ) {
				self::add_error( 'stay_too_long', __( 'Maximum stay duration is 30 days', 'aiohm-booking-pro' ) );
			}
		}

		// Guests validation.
		if ( isset( $data['guests'] ) ) {
			$guests = intval( $data['guests'] );
			if ( $guests < 1 || $guests > 20 ) {
				self::add_error( 'invalid_guests', __( 'Number of guests must be between 1 and 20', 'aiohm-booking-pro' ) );
			}
		}

		// Customer information validation.
		if ( ! empty( $data['customer_email'] ) ) {
			if ( ! self::validate_email( $data['customer_email'] ) ) {
				self::add_error( 'invalid_email', __( 'Invalid email address format', 'aiohm-booking-pro' ) );
			}
		}

		if ( ! empty( $data['customer_phone'] ) ) {
			if ( ! self::validate_phone( $data['customer_phone'] ) ) {
				self::add_error( 'invalid_phone', __( 'Invalid phone number format', 'aiohm-booking-pro' ) );
			}
		}

		// Name validation.
		if ( ! empty( $data['customer_first_name'] ) ) {
			if ( ! self::validate_name( $data['customer_first_name'] ) ) {
				self::add_error( 'invalid_first_name', __( 'First name contains invalid characters', 'aiohm-booking-pro' ) );
			}
		}

		if ( ! empty( $data['customer_last_name'] ) ) {
			if ( ! self::validate_name( $data['customer_last_name'] ) ) {
				self::add_error( 'invalid_last_name', __( 'Last name contains invalid characters', 'aiohm-booking-pro' ) );
			}
		}

		// Special requests validation (length limit).
		if ( ! empty( $data['special_requests'] ) ) {
			if ( strlen( $data['special_requests'] ) > 1000 ) {
				self::add_error( 'special_requests_too_long', __( 'Special requests must be less than 1000 characters', 'aiohm-booking-pro' ) );
			}
		}

		return empty( self::$errors );
	}

	/**
	 * Validate payment data.
	 *
	 * @param array $data Payment data to validate.
	 */
	public static function validate_payment_data( $data ) {
		self::clear_errors();

		if ( ! is_array( $data ) ) {
			self::add_error( 'invalid_payment_data', __( 'Payment data must be an array', 'aiohm-booking-pro' ) );
			return false;
		}

		// Amount validation.
		if ( ! isset( $data['amount'] ) || ! is_numeric( $data['amount'] ) || $data['amount'] <= 0 ) {
			self::add_error( 'invalid_amount', __( 'Invalid payment amount', 'aiohm-booking-pro' ) );
		}

		// Currency validation.
		if ( ! empty( $data['currency'] ) ) {
			$valid_currencies = array( 'USD', 'EUR', 'GBP', 'CAD', 'AUD' );
			if ( ! in_array( strtoupper( $data['currency'] ), $valid_currencies, true ) ) {
				self::add_error( 'invalid_currency', __( 'Invalid currency code', 'aiohm-booking-pro' ) );
			}
		}

		// Payment method validation.
		if ( ! empty( $data['payment_method'] ) ) {
			$valid_methods = array( 'stripe', 'paypal', 'bank_transfer' );
			if ( ! in_array( $data['payment_method'], $valid_methods, true ) ) {
				self::add_error( 'invalid_payment_method', __( 'Invalid payment method', 'aiohm-booking-pro' ) );
			}
		}

		return empty( self::$errors );
	}

	/**
	 * Validate API credentials.
	 *
	 * @param string $provider The API provider name.
	 * @param array  $credentials The credentials array to validate.
	 */
	public static function validate_api_credentials( $provider, $credentials ) {
		self::clear_errors();

		if ( ! is_array( $credentials ) ) {
			self::add_error( 'invalid_credentials', __( 'Credentials must be an array', 'aiohm-booking-pro' ) );
			return false;
		}

		switch ( $provider ) {
			case 'stripe':
				if ( empty( $credentials['publishable_key'] ) ) {
					self::add_error( 'missing_publishable_key', __( 'Stripe publishable key is required', 'aiohm-booking-pro' ) );
				}
				if ( empty( $credentials['secret_key'] ) ) {
					self::add_error( 'missing_secret_key', __( 'Stripe secret key is required', 'aiohm-booking-pro' ) );
				}
				break;

			case 'paypal':
				if ( empty( $credentials['client_id'] ) ) {
					self::add_error( 'missing_client_id', __( 'PayPal client ID is required', 'aiohm-booking-pro' ) );
				}
				if ( empty( $credentials['client_secret'] ) ) {
					self::add_error( 'missing_client_secret', __( 'PayPal client secret is required', 'aiohm-booking-pro' ) );
				}
				break;

			case 'openai':
				if ( empty( $credentials['api_key'] ) ) {
					self::add_error( 'missing_api_key', __( 'OpenAI API key is required', 'aiohm-booking-pro' ) );
				}
				break;

			case 'gemini':
				if ( empty( $credentials['api_key'] ) ) {
					self::add_error( 'missing_api_key', __( 'Gemini API key is required', 'aiohm-booking-pro' ) );
				}
				break;
		}

		return empty( self::$errors );
	}

	/**
	 * Sanitize booking data.
	 *
	 * @param array $data The booking data to sanitize.
	 */
	public static function sanitize_booking_data( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'customer_email':
					$sanitized[ $key ] = sanitize_email( $value );
					break;

				case 'customer_phone':
					$sanitized[ $key ] = self::sanitize_phone( $value );
					break;

				case 'customer_first_name':
				case 'customer_last_name':
					$sanitized[ $key ] = self::sanitize_name( $value );
					break;

				case 'special_requests':
				case 'notes':
					$sanitized[ $key ] = sanitize_textarea_field( $value );
					break;

				case 'guests':
				case 'rooms_qty':
					$sanitized[ $key ] = absint( $value );
					break;

				case 'total_amount':
				case 'deposit_amount':
					$sanitized[ $key ] = floatval( $value );
					break;

				case 'checkin_date':
				case 'checkout_date':
					$sanitized[ $key ] = self::sanitize_date( $value );
					break;

				default:
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Validate date format (YYYY-MM-DD).
	 *
	 * @param string $date The date string to validate.
	 */
	public static function validate_date( $date ) {
		if ( ! is_string( $date ) ) {
			return false;
		}

		$date = trim( $date );
		if ( empty( $date ) ) {
			return false;
		}

		// Check format.
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}

		// Check if it's a valid date.
		$parts = explode( '-', $date );
		if ( ! checkdate( $parts[1], $parts[2], $parts[0] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate email with additional checks.
	 *
	 * @param string $email The email address to validate.
	 */
	public static function validate_email( $email ) {
		if ( ! is_email( $email ) ) {
			return false;
		}

		// Additional checks for common issues.
		$email = trim( $email );

		// Check for disposable email domains (basic check).
		$disposable_domains = array( '10minutemail.com', 'guerrillamail.com', 'mailinator.com' );
		$domain             = substr( strrchr( $email, '@' ), 1 );
		if ( in_array( strtolower( $domain ), $disposable_domains, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate phone number.
	 *
	 * @param string $phone The phone number to validate.
	 */
	public static function validate_phone( $phone ) {
		if ( ! is_string( $phone ) ) {
			return false;
		}

		$phone = trim( $phone );
		if ( empty( $phone ) ) {
			return false;
		}

		// Extract digits only for length check.
		$digits_only = preg_replace( '/[^\d]/', '', $phone );

		// Basic length check (7-15 digits is international standard).
		if ( strlen( $digits_only ) < 7 || strlen( $digits_only ) > 15 ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate name (allow letters, spaces, hyphens, apostrophes).
	 *
	 * @param string $name The name to validate.
	 */
	public static function validate_name( $name ) {
		if ( ! is_string( $name ) ) {
			return false;
		}

		$name = trim( $name );
		if ( empty( $name ) ) {
			return false;
		}

		// Allow letters, spaces, hyphens, apostrophes.
		if ( ! preg_match( "/^[a-zA-Z\s\-']+$/", $name ) ) {
			return false;
		}

		// Length check.
		if ( strlen( $name ) < 2 || strlen( $name ) > 50 ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize phone number.
	 *
	 * @param string $phone The phone number to sanitize.
	 */
	public static function sanitize_phone( $phone ) {
		if ( ! is_string( $phone ) ) {
			return '';
		}

		// Remove potentially harmful characters but keep basic formatting.
		return preg_replace( '/[^\d+\s\-()]/', '', trim( $phone ) );
	}

	/**
	 * Sanitize name.
	 *
	 * @param string $name The name to sanitize.
	 */
	public static function sanitize_name( $name ) {
		if ( ! is_string( $name ) ) {
			return '';
		}

		// Remove potentially harmful characters.
		return preg_replace( '/[^a-zA-Z\s\-]/', '', trim( $name ) );
	}

	/**
	 * Validate and sanitize hex color value.
	 *
	 * @param string $value The color value to validate.
	 * @return string Valid hex color value.
	 */
	public static function sanitize_hex_color( $value ) {
		// Remove any whitespace.
		$value = trim( $value );

		// If empty, return default black.
		if ( empty( $value ) ) {
			return '#000000';
		}

		// Ensure the value starts with #.
		if ( '#' !== substr( $value, 0, 1 ) ) {
			$value = '#' . $value;
		}

		// Validate hex color format (3 or 6 digits).
		if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value ) ) {
			return strtolower( $value );
		}

		// If invalid, return default black.
		return '#000000';
	}

	/**
	 * Sanitize date.
	 *
	 * @param string $date The date to sanitize.
	 */
	public static function sanitize_date( $date ) {
		if ( ! is_string( $date ) ) {
			return '';
		}

		$date = trim( $date );

		// Use centralized date validation.
		if ( self::validate_date( $date ) ) {
			return $date;
		}

		return '';
	}

	/**
	 * Check if value is empty (handles various empty states).
	 *
	 * @param mixed $value The value to check.
	 */
	public static function is_empty( $value ) {
		if ( is_null( $value ) ) {
			return true;
		}

		if ( is_string( $value ) ) {
			return trim( $value ) === '';
		}

		if ( is_array( $value ) ) {
			return empty( $value );
		}

		return empty( $value );
	}

	/**
	 * Add error message.
	 *
	 * @param string $code The error code.
	 * @param string $message The error message.
	 */
	public static function add_error( $code, $message ) {
		self::$errors[ $code ] = $message;
	}

	/**
	 * Get all errors.
	 */
	public static function get_errors() {
		return self::$errors;
	}

	/**
	 * Check if there are errors.
	 */
	public static function has_errors() {
		return ! empty( self::$errors );
	}

	/**
	 * Clear all errors.
	 */
	public static function clear_errors() {
		self::$errors = array();
	}

	/**
	 * Add success message.
	 *
	 * @param string $code The message code.
	 * @param string $message The success message.
	 */
	public static function add_message( $code, $message ) {
		self::$messages[ $code ] = $message;
	}

	/**
	 * Get all messages.
	 */
	public static function get_messages() {
		return self::$messages;
	}

	/**
	 * Clear all messages.
	 */
	public static function clear_messages() {
		self::$messages = array();
	}

	/**
	 * Log error for debugging.
	 *
	 * @param string $message The error message to log.
	 * @param array  $context Additional context information.
	 */
	public static function log_error( $message, $context = array() ) {
		// Store validation errors for admin review
		$validation_logs = get_option( 'aiohm_booking_validation_logs', array() );

		// Keep only last 50 entries to prevent database bloat
		if ( count( $validation_logs ) >= 50 ) {
			$validation_logs = array_slice( $validation_logs, -49, 49, true );
		}

		$validation_logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'message'   => $message,
			'context'   => $context,
			'ip'        => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) ),
		);

		update_option( 'aiohm_booking_validation_logs', $validation_logs, false );
	}

	/**
	 * Get validation logs for admin review
	 *
	 * @since 1.2.5
	 * @return array Validation logs
	 */
	public static function get_validation_logs() {
		return get_option( 'aiohm_booking_validation_logs', array() );
	}

	/**
	 * Clear validation logs
	 *
	 * @since 1.2.5
	 * @return bool Success
	 */
	public static function clear_validation_logs() {
		return delete_option( 'aiohm_booking_validation_logs' );
	}

	/**
	 * Handle validation errors and return appropriate response.
	 *
	 * @param string $context The context for error handling (ajax, rest, etc.).
	 */
	public static function handle_validation_errors( $context = 'ajax' ) {
		if ( empty( self::$errors ) ) {
			return;
		}

		$error_message = implode( ' ', array_values( self::$errors ) );

		switch ( $context ) {
			case 'ajax':
				wp_send_json_error(
					array(
						'message' => $error_message,
						'errors'  => self::$errors,
					)
				);
				break;

			case 'rest':
				return new WP_Error( 'validation_failed', $error_message, array( 'errors' => self::$errors ) );

			case 'admin':
				add_settings_error(
					'aiohm_booking_validation',
					'validation_failed',
					$error_message,
					'error'
				);
				break;

			default:
				wp_die( esc_html( $error_message ) );
				break;
		}
	}
}
