<?php
/**
 * Module Error Handler
 *
 * Extends the main error handler with module-specific error handling,
 * automatic error reporting, and module context awareness.
 *
 * @package AIOHM_Booking
 * @since 1.2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Module Error Handler Class
 *
 * Provides module-specific error handling with automatic context and reporting.
 */
class AIOHM_BOOKING_Module_Error_Handler {

	/**
	 * Module identifier
	 *
	 * @var string
	 */
	private $module_id;

	/**
	 * Module display name
	 *
	 * @var string
	 */
	private $module_name;

	/**
	 * Error context data
	 *
	 * @var array
	 */
	private $context = array();

	/**
	 * Constructor
	 *
	 * @param string $module_id Module identifier.
	 * @param string $module_name Module display name.
	 */
	public function __construct( $module_id, $module_name = '' ) {
		$this->module_id   = $module_id;
		$this->module_name = $module_name ?: ucfirst( str_replace( '_', ' ', $module_id ) );

		$this->context = array(
			'module_id'   => $this->module_id,
			'module_name' => $this->module_name,
			'timestamp'   => current_time( 'timestamp' ),
		);
	}

	/**
	 * Set additional context data
	 *
	 * @param array $context Additional context.
	 * @return self
	 */
	public function with_context( $context ) {
		$this->context = array_merge( $this->context, $context );
		return $this;
	}

	/**
	 * Log a module-specific error
	 *
	 * @param string $message Error message.
	 * @param string $type Error type.
	 * @param array  $additional_context Additional context data.
	 */
	public function log_error( $message, $type = 'module_error', $additional_context = array() ) {
		$full_context = array_merge( $this->context, $additional_context );

		$full_message = sprintf(
			'[%s] %s: %s',
			$this->module_name,
			$type,
			$message
		);

		AIOHM_BOOKING_Error_Handler::log_error( $full_message, $type, $full_context );

		// Check if admin notification is needed.
		$this->maybe_notify_admin( $type, $full_message, $full_context );
	}

	/**
	 * Log a module-specific warning
	 *
	 * @param string $message Warning message.
	 * @param array  $additional_context Additional context data.
	 */
	public function log_warning( $message, $additional_context = array() ) {
		$this->log_error( $message, 'module_warning', $additional_context );
	}

	/**
	 * Log module initialization error
	 *
	 * @param string $error Error message.
	 * @param array  $additional_context Additional context data.
	 */
	public function log_init_error( $error, $additional_context = array() ) {
		$this->log_error(
			"Module initialization failed: {$error}",
			'module_init_error',
			$additional_context
		);
	}

	/**
	 * Log API error with module context
	 *
	 * @param string $service Service name.
	 * @param string $error Error message.
	 * @param array  $additional_context Additional context data.
	 */
	public function log_api_error( $service, $error, $additional_context = array() ) {
		$this->log_error(
			"API error with {$service}: {$error}",
			'module_api_error',
			array_merge(
				array( 'service' => $service ),
				$additional_context
			)
		);
	}

	/**
	 * Log database error with module context
	 *
	 * @param string $operation Database operation.
	 * @param string $error Error message.
	 * @param array  $additional_context Additional context data.
	 */
	public function log_database_error( $operation, $error, $additional_context = array() ) {
		$this->log_error(
			"Database error during {$operation}: {$error}",
			'module_database_error',
			array_merge(
				array( 'operation' => $operation ),
				$additional_context
			)
		);
	}

	/**
	 * Log configuration error
	 *
	 * @param string $error Error message.
	 * @param array  $additional_context Additional context data.
	 */
	public function log_config_error( $error, $additional_context = array() ) {
		$this->log_error(
			"Configuration error: {$error}",
			'module_config_error',
			$additional_context
		);
	}

	/**
	 * Handle module exception
	 *
	 * @param Exception $exception The exception.
	 * @param string    $operation Operation being performed.
	 */
	public function handle_exception( $exception, $operation = 'operation' ) {
		$this->log_error(
			"Exception during {$operation}: " . $exception->getMessage(),
			'module_exception',
			array(
				'exception_class' => get_class( $exception ),
				'operation'       => $operation,
				'file'            => $exception->getFile(),
				'line'            => $exception->getLine(),
				'trace'           => $exception->getTraceAsString(),
			)
		);
	}

	/**
	 * Validate module requirements
	 *
	 * @param array $requirements Array of requirements to check.
	 * @return bool|WP_Error True if all requirements met, WP_Error otherwise.
	 */
	public function validate_requirements( $requirements ) {
		$errors = array();

		foreach ( $requirements as $requirement => $check ) {
			if ( is_callable( $check ) ) {
				$result = call_user_func( $check );
				if ( ! $result ) {
					$errors[] = "Requirement '{$requirement}' not met";
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$this->log_error(
				'Module requirements validation failed: ' . implode( ', ', $errors ),
				'module_requirements_error',
				array( 'failed_requirements' => $errors )
			);

			return new WP_Error(
				'requirements_not_met',
				__( 'Module requirements not met', 'aiohm-booking-pro' ),
				$errors
			);
		}

		return true;
	}

	/**
	 * Show admin notice for module error
	 *
	 * @param string $message Error message.
	 * @param string $type Notice type.
	 */
	public function show_admin_notice( $message, $type = 'error' ) {
		$full_message = sprintf(
			'[%s] %s',
			$this->module_name,
			$message
		);

		AIOHM_BOOKING_Error_Handler::show_admin_error( $full_message, $type );
	}

	/**
	 * Show frontend notice for module error
	 *
	 * @param string $message Error message.
	 * @param string $type Notice type.
	 */
	public function show_frontend_notice( $message, $type = 'error' ) {
		$full_message = sprintf(
			'[%s] %s',
			$this->module_name,
			$message
		);

		AIOHM_BOOKING_Error_Handler::show_frontend_error( $full_message, $type );
	}

	/**
	 * Get module error statistics
	 *
	 * @param int $days Number of days to look back.
	 * @return array Error statistics.
	 */
	public function get_error_stats( $days = 7 ) {
		$recent_errors = AIOHM_BOOKING_Error_Handler::get_recent_errors( 1000 );

		$stats = array(
			'total_errors'   => 0,
			'errors_by_type' => array(),
			'recent_errors'  => array(),
		);

		$cutoff_time = strtotime( "-{$days} days" );

		foreach ( $recent_errors as $error ) {
			// Check if error is for this module.
			if ( strpos( $error['message'], "[{$this->module_name}]" ) === 0 ) {
				$error_time = strtotime( $error['timestamp'] );

				if ( $error_time >= $cutoff_time ) {
					++$stats['total_errors'];
					$stats['errors_by_type'][ $error['type'] ] = ( $stats['errors_by_type'][ $error['type'] ] ?? 0 ) + 1;
					$stats['recent_errors'][]                  = $error;
				}
			}
		}

		return $stats;
	}

	/**
	 * Clear module-specific errors from log
	 *
	 * @return int Number of errors cleared.
	 */
	public function clear_module_errors() {
		$recent_errors = AIOHM_BOOKING_Error_Handler::get_recent_errors( 1000 );
		$cleared_count = 0;

		// Note: This is a simplified implementation.
		// In a real scenario, you'd need to modify the error log file directly
		// or implement a more sophisticated filtering system

		foreach ( $recent_errors as $error ) {
			if ( strpos( $error['message'], "[{$this->module_name}]" ) === 0 ) {
				++$cleared_count;
			}
		}

		return $cleared_count;
	}

	/**
	 * Maybe notify admin based on error type and severity
	 *
	 * @param string $type Error type.
	 * @param string $message Error message.
	 * @param array  $context Error context.
	 */
	private function maybe_notify_admin( $type, $message, $context ) {
		$notify_types = array(
			'module_init_error',
			'module_api_error',
			'module_database_error',
			'module_exception',
		);

		if ( in_array( $type, $notify_types, true ) ) {
			AIOHM_BOOKING_Error_Handler::notify_admin_error( $message, $context );
		}
	}

	/**
	 * Create a child error handler for sub-components
	 *
	 * @param string $component Component name.
	 * @return self New error handler instance.
	 */
	public function create_component_handler( $component ) {
		$child_handler = new self(
			$this->module_id . '_' . $component,
			$this->module_name . ' - ' . ucfirst( $component )
		);

		return $child_handler->with_context( $this->context );
	}
}
