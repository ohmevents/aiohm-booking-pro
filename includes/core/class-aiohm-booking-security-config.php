<?php

namespace AIOHM_Booking_PRO\Core;
/**
 * AIOHM Booking Security Configuration
 * Centralized security settings and constants.
 *
 * @package AIOHM_Booking
 *
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security Configuration Class.
 */
class AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Security_Config {

	/** Rate limiting settings */
	public const RATE_LIMIT_WEBHOOK_MAX_REQUESTS = 100;

	public const RATE_LIMIT_WEBHOOK_TIME_WINDOW = 60; // seconds.

	public const RATE_LIMIT_IPN_MAX_REQUESTS = 50;

	public const RATE_LIMIT_IPN_TIME_WINDOW = 60; // seconds.

	public const RATE_LIMIT_API_MAX_REQUESTS = 200;

	public const RATE_LIMIT_API_TIME_WINDOW = 60; // seconds.

	/** Input validation limits */
	public const MAX_BOOKING_ID = 999999999;

	public const MAX_GUESTS = 20;

	public const MIN_GUESTS = 1;

	public const MAX_EMAIL_LENGTH = 254;

	public const MAX_NAME_LENGTH = 100;

	/** Security headers */
	public const SECURITY_HEADERS = array(
		'X-Content-Type-Options'  => 'nosniff',
		'X-Frame-Options'         => 'SAMEORIGIN',
		'X-XSS-Protection'        => '1; mode=block',
		'Referrer-Policy'         => 'strict-origin-when-cross-origin',
		'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://js.stripe.com https://www.paypal.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com data:; worker-src 'self' blob:; frame-src https://js.stripe.com https://hooks.stripe.com https://www.paypal.com;",
	);

	/** Allowed HTTP methods for webhooks */
	public const ALLOWED_WEBHOOK_METHODS = array( 'POST' );

	/** IP whitelist for webhooks (optional - can be configured in settings) */
	public const STRIPE_WEBHOOK_IPS = array(
		'54.187.174.169/32', // Stripe's IP range.
		'54.187.205.235/32',
		'54.187.216.72/32',
		'54.241.31.99/32',
		'54.241.31.102/32',
		'54.241.34.103/32',
	);

	public const PAYPAL_WEBHOOK_IPS = array(
		'173.0.81.0/24', // PayPal's IP ranges.
		'173.0.82.0/24',
		'173.0.83.0/24',
		'173.0.84.0/24',
		'173.0.85.0/24',
	);

	/**
	 * Get security headers.
	 */
	public static function get_security_headers() {
		return self::SECURITY_HEADERS;
	}

	/**
	 * Validate IP address against whitelist.
	 *
	 * @param string $ip IP address to validate.
	 * @param string $service Service name (default: 'stripe').
	 *
	 * @return bool True if IP is whitelisted, false otherwise.
	 */
	public static function validate_ip_whitelist( $ip, $service = 'stripe' ) {
		$whitelist = ( 'paypal' === $service ) ? self::PAYPAL_WEBHOOK_IPS : self::STRIPE_WEBHOOK_IPS;

		foreach ( $whitelist as $cidr ) {
			if ( self::ip_in_cidr( $ip, $cidr ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if IP is in CIDR range.
	 *
	 * @param string $ip IP address to check.
	 * @param string $cidr CIDR range to check against.
	 *
	 * @return bool True if IP is in range, false otherwise.
	 */
	private static function ip_in_cidr( $ip, $cidr ) {
		[$subnet, $mask] = explode( '/', $cidr );
		$mask            = (int) $mask; // Ensure mask is integer for bitwise operations.

		if ( ( ip2long( $ip ) & ~( ( 1 << ( 32 - $mask ) ) - 1 ) ) === ip2long( $subnet ) ) {
			return true;
		}

		return false;
	}



	/**
	 * Generate secure nonce.
	 *
	 * @param string $action Action name for the nonce.
	 *
	 * @return string Generated nonce.
	 */
	public static function generate_secure_nonce( $action = 'aiohm_booking_action' ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify secure nonce.
	 *
	 * @param string $nonce Nonce to verify.
	 * @param string $action Action name for the nonce.
	 *
	 * @return bool|int True if nonce is valid, false or 1/2 if invalid.
	 */
	public static function verify_secure_nonce( $nonce, $action = 'aiohm_booking_action' ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Log security events.
	 *
	 * @param string $event Security event type.
	 * @param array  $data Additional event data.
	 *
	 * @return void
	 */
	public static function log_security_event( $event, $data = array() ) {
		$log_data = array(
			'timestamp'  => current_time( 'mysql' ),
			'event'      => $event,
			'ip'         => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) ),
			'user_agent' => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? 'unknown' ) ),
			'data'       => $data,
		);

		// Production logging removed for security

		// Store critical security events in database for admin review
		$critical_events = array( 'rate_limit_exceeded', 'security_violation', 'suspicious_request' );
		if ( in_array( $event, $critical_events, true ) ) {
			$security_logs = get_option( 'aiohm_booking_security_logs', array() );

			// Keep only last 100 entries to prevent database bloat
			if ( count( $security_logs ) >= 100 ) {
				$security_logs = array_slice( $security_logs, -99, 99, true );
			}

			$security_logs[] = $log_data;
			update_option( 'aiohm_booking_security_logs', $security_logs, false );
		}
	}

	/**
	 * Get security logs for admin review
	 *
	 * @since 1.2.5
	 * @return array Security logs
	 */
	public static function get_security_logs() {
		return get_option( 'aiohm_booking_security_logs', array() );
	}

	/**
	 * Clear security logs
	 *
	 * @since 1.2.5
	 * @return bool Success
	 */
	public static function clear_security_logs() {
		return delete_option( 'aiohm_booking_security_logs' );
	}

	/**
	 * Check if request is from allowed origin.
	 */
	public static function validate_origin() {
		$origin  = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ?? '' ) );
		$referer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) );

		$allowed_origins = array(
			home_url(),
			site_url(),
		);

		// Check origin.
		if ( ! empty( $origin ) && ! in_array( $origin, $allowed_origins, true ) ) {
			return false;
		}

		// Check referer as fallback.
		if ( ! empty( $referer ) ) {
			$referer_host = wp_parse_url( $referer, PHP_URL_HOST );
			$site_host    = wp_parse_url( home_url(), PHP_URL_HOST );

			if ( $referer_host !== $site_host ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Validate request integrity.
	 *
	 * @param mixed $request Request data to validate.
	 *
	 * @return mixed|WP_Error Original request if valid, WP_Error on security violation.
	 */
	public static function validate_request( $request ) {
		// Check for suspicious patterns.
		$suspicious_patterns = array(
			'/<script/i',
			'/javascript:/i',
			'/on\w+\s*=/i',
			'/eval\(/i',
			'/base64_decode/i',
			'/\$\{.*\}/i', // Template literals that could be exploited.
		);

		$request_data = wp_json_encode( $request );

		foreach ( $suspicious_patterns as $pattern ) {
			if ( preg_match( $pattern, $request_data ) ) {
				if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler' ) ) {
					AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
						'Suspicious request pattern detected',
						'security_warning',
						array(
							'pattern' => $pattern,
							'ip'      => self::get_client_ip(),
						)
					);
				}

				return new WP_Error( 'security_violation', __( 'Security violation detected', 'aiohm-booking-pro' ) );
			}
		}

		return $request;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string Client IP address.
	 */
	public static function get_client_ip() {
		$ip_headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// Handle comma-separated IPs (like X-Forwarded-For).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}

				// Validate IP.
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ) );
	}

	/**
	 * Sanitize file uploads.
	 *
	 * @param array $file File upload data.
	 *
	 * @return array|WP_Error Sanitized file data or error.
	 */
	public static function sanitize_file_upload( $file ) {
		if ( ! is_array( $file ) || ! isset( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_file', __( 'Invalid file upload', 'aiohm-booking-pro' ) );
		}

		// Check file size (max 5MB).
		$max_size = 5 * 1024 * 1024; // 5MB
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds maximum allowed size', 'aiohm-booking-pro' ) );
		}

		// Check file type.
		$allowed_types = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'application/pdf',
			'text/plain',
		);

		$file_type = wp_check_filetype( $file['name'] );
		if ( ! in_array( $file_type['type'], $allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'File type not allowed', 'aiohm-booking-pro' ) );
		}

		// Check for malicious content.
		if ( function_exists( 'finfo_open' ) ) {
			$finfo     = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );

			if ( $real_mime !== $file_type['type'] ) {
				return new WP_Error( 'mime_mismatch', __( 'File type mismatch detected', 'aiohm-booking-pro' ) );
			}
		}

		return $file;
	}

	/**
	 * Initialize security features.
	 */
	public static function init_security() {
		// Add request validation filters.
		add_filter( 'aiohm_booking_validate_request', array( __CLASS__, 'validate_request' ) );

		// Rate limiting for sensitive operations.
		add_action( 'wp_ajax_aiohm_booking_submit_support_request', array( __CLASS__, 'check_rate_limit' ), 1 );
		add_action( 'wp_ajax_aiohm_booking_stripe_create_session', array( __CLASS__, 'check_rate_limit' ), 1 );
		add_action( 'wp_ajax_aiohm_booking_stripe_process_payment', array( __CLASS__, 'check_rate_limit' ), 1 );
	}

	/**
	 * Rate limiting for AJAX requests.
	 */
	public static function check_rate_limit() {
		$user_ip = self::get_client_ip();
		$action  = current_action();

		// Define rate limits (requests per minute).
		$rate_limits = array(
			'wp_ajax_aiohm_booking_submit_support_request' => 5,
			'wp_ajax_aiohm_booking_stripe_create_session'  => 10,
			'wp_ajax_aiohm_booking_stripe_process_payment' => 20,
		);

		$limit     = $rate_limits[ $action ] ?? 30; // Default limit.
		$cache_key = 'aiohm_rate_limit_' . md5( $user_ip . $action );

		// Get current count.
		$current_count = get_transient( $cache_key );
		if ( false === $current_count ) {
			$current_count = 0;
		}

		if ( $current_count >= $limit ) {
			if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler' ) ) {
				AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
					sprintf( 'Rate limit exceeded for %s from IP %s', $action, $user_ip ),
					'rate_limit'
				);
			}

			wp_send_json_error(
				array(
					'message' => __( 'Too many requests. Please try again later.', 'aiohm-booking-pro' ),
				)
			);
		}

		// Increment counter.
		set_transient( $cache_key, $current_count + 1, 60 ); // 1 minute window
	}
}
