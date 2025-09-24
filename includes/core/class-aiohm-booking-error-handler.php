<?php

namespace AIOHM_Booking_PRO\Core;
/**
 * Error Handling and Logging Utilities for AIOHM Booking
 * Provides centralized error handling, logging, and user feedback.
 *
 * @package AIOHM_Booking
 *
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error Handling Class.
 */
class AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler {

	/**
	 * Error log file path.
	 *
	 * @var string
	 */
	private static $log_file;

	/**
	 * Initialize error handling.
	 */
	public static function init() {
		self::$log_file = WP_CONTENT_DIR . '/aiohm-booking-errors.log';

		// Register shutdown function.
		register_shutdown_function( array( __CLASS__, 'handle_shutdown' ) );
	}

	/**
	 * Handle PHP errors.
	 *
	 * @param int    $errno The error level.
	 * @param string $errstr The error message.
	 * @param string $errfile The file where the error occurred.
	 * @param int    $errline The line number where the error occurred.
	 */
	public static function handle_php_errors( $errno, $errstr, $errfile, $errline ) {
		// Only handle errors that are included in error_reporting.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Error reporting check for debugging
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		$error_message = sprintf(
			'[%s] PHP Error: %s in %s on line %d',
			gmdate( 'Y-m-d H:i:s' ),
			$errstr,
			$errfile,
			$errline
		);

		self::log_error(
			$error_message,
			'php_error',
			array(
				'errno'   => $errno,
				'errfile' => $errfile,
				'errline' => $errline,
			)
		);

		// Don't execute PHP's internal error handler.
		return true;
	}

	/**
	 * Handle uncaught exceptions.
	 *
	 * @param Exception $exception The uncaught exception.
	 */
	public static function handle_uncaught_exceptions( $exception ) {
		$error_message = sprintf(
			'[%s] Uncaught Exception: %s in %s on line %d',
			gmdate( 'Y-m-d H:i:s' ),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine()
		);

		self::log_error(
			$error_message,
			'uncaught_exception',
			array(
				'exception' => get_class( $exception ),
				'trace'     => $exception->getTraceAsString(),
			)
		);

		// Show user-friendly error page.
		if ( ! headers_sent() ) {
			http_response_code( 500 );
		}

		if ( is_admin() ) {
			wp_die(
				esc_html__( 'A critical error occurred. Please check the error logs for details.', 'aiohm-booking-pro' ),
				esc_html__( 'Critical Error', 'aiohm-booking-pro' ),
				array( 'back_link' => true )
			);
		} else {
			wp_die(
				esc_html__( 'Something went wrong. Please try again later.', 'aiohm-booking-pro' ),
				esc_html__( 'Error', 'aiohm-booking-pro' )
			);
		}
	}

	/**
	 * Handle shutdown (fatal errors).
	 */
	public static function handle_shutdown() {
		$error = error_get_last();

		if ( $error !== null && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
			$error_message = sprintf(
				'[%s] Fatal Error: %s in %s on line %d',
				gmdate( 'Y-m-d H:i:s' ),
				$error['message'],
				$error['file'],
				$error['line']
			);

			self::log_error( $error_message, 'fatal_error', $error );
		}
	}

	/**
	 * Log error to file.
	 *
	 * @param string $message The error message to log.
	 * @param string $type The error type.
	 * @param array  $context Additional context data.
	 */
	public static function log_error( $message, $type = 'general', $context = array() ) {
		$log_entry = sprintf(
			"[%s] [%s] %s\n",
			gmdate( 'Y-m-d H:i:s' ),
			strtoupper( $type ),
			$message
		);

		if ( ! empty( $context ) ) {
			$log_entry .= 'Context: ' . wp_json_encode( $context ) . "\n";
		}

		$log_entry .= str_repeat( '-', 50 ) . "\n";

		// Ensure log directory exists.
		$log_dir = dirname( self::$log_file );
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		// Write to log file.
		@file_put_contents( self::$log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Display admin notice for errors.
	 *
	 * @param string $message The error message to display.
	 * @param string $type The notice type (error, warning, success, info).
	 */
	public static function show_admin_error( $message, $type = 'error' ) {
		if ( ! is_admin() ) {
			return;
		}

		add_action(
			'admin_notices',
			function () use ( $message, $type ) {
				$class = 'notice notice-' . $type . ' is-dismissible';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
		);
	}

	/**
	 * Display frontend error message.
	 *
	 * @param string $message The error message to display.
	 * @param string $type The message type (error, warning, success, info).
	 */
	public static function show_frontend_error( $message, $type = 'error' ) {
		if ( is_admin() ) {
			return;
		}

		$class = 'aiohm-notice aiohm-notice-' . $type;
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Handle AJAX errors.
	 *
	 * @param string $message The error message.
	 * @param array  $data Additional error data.
	 */
	public static function handle_ajax_error( $message, $data = array() ) {
		$response = array(
			'success' => false,
			'message' => $message,
			'data'    => $data,
		);

		self::log_error( $message, 'ajax_error', $data );

		wp_send_json_error( $response );
	}

	/**
	 * Handle database errors.
	 *
	 * @param string      $operation The database operation that failed.
	 * @param string|null $error The database error message.
	 */
	public static function handle_database_error( $operation, $error = null ) {
		global $wpdb;

		$error_message = sprintf(
			'Database error during %s: %s',
			$operation,
			$error ? $error : $wpdb->last_error
		);

		self::log_error(
			$error_message,
			'database_error',
			array(
				'operation'  => $operation,
				'last_query' => $wpdb->last_query,
				'last_error' => $wpdb->last_error,
			)
		);

		if ( is_admin() ) {
			self::show_admin_error( __( 'Database error occurred. Please check the error logs.', 'aiohm-booking-pro' ) );
		}

		return new WP_Error( 'database_error', __( 'A database error occurred', 'aiohm-booking-pro' ) );
	}

	/**
	 * Handle API errors.
	 *
	 * @param string $service The API service name.
	 * @param string $error The error message.
	 * @param array  $context Additional context data.
	 */
	public static function handle_api_error( $service, $error, $context = array() ) {
		$error_message = sprintf( 'API error with %s: %s', $service, $error );

		self::log_error(
			$error_message,
			'api_error',
			array_merge(
				$context,
				array(
					'service' => $service,
					'error'   => $error,
				)
			)
		);

		/* translators: %s: service name */
		return new WP_Error( 'api_error', sprintf( __( 'Error communicating with %s', 'aiohm-booking-pro' ), $service ) );
	}

	/**
	 * Validate and sanitize user input with error handling.
	 *
	 * @param array $data The input data to validate.
	 * @param array $rules The validation rules.
	 */
	public static function validate_input( $data, $rules = array() ) {
		if ( ! class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation' ) ) {
			self::log_error( 'Validation class not available', 'validation_error' );
			return new WP_Error( 'validation_unavailable', __( 'Validation system unavailable', 'aiohm-booking-pro' ) );
		}

		$errors = array();

		foreach ( $rules as $field => $rule ) {
			if ( ! isset( $data[ $field ] ) ) {
				if ( isset( $rule['required'] ) && $rule['required'] ) {
					/* translators: %s: field name */
					$errors[ $field ] = sprintf( __( 'Field %s is required', 'aiohm-booking-pro' ), $field );
				}
				continue;
			}

			$value = $data[ $field ];

			// Type validation.
			if ( isset( $rule['type'] ) ) {
				switch ( $rule['type'] ) {
					case 'email':
						if ( ! AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::validate_email( $value ) ) {
							$errors[ $field ] = __( 'Invalid email address', 'aiohm-booking-pro' );
						}
						break;

					case 'date':
						if ( ! AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::validate_date( $value ) ) {
							$errors[ $field ] = __( 'Invalid date format', 'aiohm-booking-pro' );
						}
						break;

					case 'numeric':
						if ( ! is_numeric( $value ) ) {
							$errors[ $field ] = __( 'Must be a number', 'aiohm-booking-pro' );
						}
						break;
				}
			}

			// Length validation.
			if ( isset( $rule['max_length'] ) && strlen( $value ) > $rule['max_length'] ) {
				/* translators: %d: maximum length */
				$errors[ $field ] = sprintf( __( 'Must be less than %d characters', 'aiohm-booking-pro' ), $rule['max_length'] );
			}

			if ( isset( $rule['min_length'] ) && strlen( $value ) < $rule['min_length'] ) {
				/* translators: %d: minimum length */
				$errors[ $field ] = sprintf( __( 'Must be at least %d characters', 'aiohm-booking-pro' ), $rule['min_length'] );
			}
		}

		if ( ! empty( $errors ) ) {
			self::log_error( 'Input validation failed', 'validation_error', $errors );
			return new WP_Error( 'validation_failed', __( 'Input validation failed', 'aiohm-booking-pro' ), $errors );
		}

		return true;
	}

	/**
	 * Get recent errors from log.
	 *
	 * @param int $limit The maximum number of errors to return.
	 */
	public static function get_recent_errors( $limit = 50 ) {
		if ( ! file_exists( self::$log_file ) ) {
			return array();
		}

		$log_content = file_get_contents( self::$log_file );
		$lines       = array_reverse( explode( "\n", trim( $log_content ) ) );

		$errors = array();
		$count  = 0;

		foreach ( $lines as $line ) {
			if ( empty( $line ) || $count >= $limit ) {
				continue;
			}

			// Parse log entry.
			if ( preg_match( '/^\[([^\]]+)\] \[([^\]]+)\] (.+)$/', $line, $matches ) ) {
				$errors[] = array(
					'timestamp' => $matches[1],
					'type'      => $matches[2],
					'message'   => $matches[3],
				);
				++$count;
			}
		}

		return $errors;
	}

	/**
	 * Clear error log.
	 */
	public static function clear_log() {
		global $wp_filesystem;

		// Initialize WP_Filesystem if not already done.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( $wp_filesystem->exists( self::$log_file ) ) {
			$wp_filesystem->delete( self::$log_file );
		}
	}

	/**
	 * Send error notification to admin.
	 *
	 * @param string $error_message The error message to send.
	 * @param array  $context Additional context information about the error.
	 */
	public static function notify_admin_error( $error_message, $context = array() ) {
		if ( ! class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings' ) ) {
			return;
		}

		$settings    = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_settings();
		$admin_email = get_option( 'admin_email' );

		if ( empty( $admin_email ) ) {
			return;
		}

		$subject = sprintf( '[%s] AIOHM Booking Error Notification', get_bloginfo( 'name' ) );
		$message = sprintf(
			"An error occurred in AIOHM Booking:\n\n%s\n\nContext:\n%s\n\nPlease check the error logs for more details.",
			$error_message,
			wp_json_encode( $context, JSON_PRETTY_PRINT )
		);

		wp_mail( $admin_email, $subject, $message );
	}
}
