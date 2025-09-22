<?php
/**
 * Standardized AJAX Registration System
 *
 * Provides a centralized system for registering AJAX endpoints with consistent
 * security, validation, and error handling.
 *
 * @package AIOHM_Booking
 * @since 1.2.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Registration Manager Class
 *
 * Manages AJAX endpoint registration with standardized patterns.
 */
class AIOHM_BOOKING_Ajax_Registration_Manager {

	/**
	 * Registered AJAX endpoints
	 *
	 * @var array
	 */
	private static $endpoints = array();

	/**
	 * Initialize the AJAX registration system
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_admin_endpoints' ) );
		add_action( 'init', array( __CLASS__, 'register_frontend_endpoints' ) );
	}

	/**
	 * Register an AJAX endpoint
	 *
	 * @param string   $action Action name.
	 * @param callable $callback Callback function.
	 * @param array    $config Configuration options.
	 */
	public static function register_endpoint( $action, $callback, $config = array() ) {
		$config = wp_parse_args(
			$config,
			array(
				'admin'          => true,
				'frontend'       => false,
				'capability'     => 'manage_options',
				'nonce_action'   => 'aiohm_booking_ajax_nonce',
				'validate_input' => true,
				'log_errors'     => true,
			)
		);

		self::$endpoints[ $action ] = array(
			'callback' => $callback,
			'config'   => $config,
		);
	}

	/**
	 * Register admin AJAX endpoints
	 */
	public static function register_admin_endpoints() {
		foreach ( self::$endpoints as $action => $endpoint ) {
			if ( ! $endpoint['config']['admin'] ) {
				continue;
			}

			$hook = "wp_ajax_aiohm_booking_{$action}";
			add_action( $hook, array( __CLASS__, 'handle_admin_ajax' ) );
		}
	}

	/**
	 * Register frontend AJAX endpoints
	 */
	public static function register_frontend_endpoints() {
		foreach ( self::$endpoints as $action => $endpoint ) {
			if ( ! $endpoint['config']['frontend'] ) {
				continue;
			}

			$hook = "wp_ajax_nopriv_aiohm_booking_{$action}";
			add_action( $hook, array( __CLASS__, 'handle_frontend_ajax' ) );
		}
	}

	/**
	 * Handle admin AJAX requests
	 */
	public static function handle_admin_ajax() {
		$action = self::get_current_action();

		if ( ! isset( self::$endpoints[ $action ] ) ) {
			AIOHM_BOOKING_Error_Handler::log_error(
				"Unknown admin AJAX action: {$action}",
				'ajax_error',
				array( 'action' => $action )
			);
			wp_send_json_error( 'Unknown action' );
			return;
		}

		$endpoint = self::$endpoints[ $action ];
		self::process_ajax_request( $endpoint, 'admin' );
	}

	/**
	 * Handle frontend AJAX requests
	 */
	public static function handle_frontend_ajax() {
		$action = self::get_current_action();

		if ( ! isset( self::$endpoints[ $action ] ) ) {
			AIOHM_BOOKING_Error_Handler::log_error(
				"Unknown frontend AJAX action: {$action}",
				'ajax_error',
				array( 'action' => $action )
			);
			wp_send_json_error( 'Unknown action' );
			return;
		}

		$endpoint = self::$endpoints[ $action ];
		self::process_ajax_request( $endpoint, 'frontend' );
	}

	/**
	 * Process AJAX request with standardized handling
	 *
	 * @param array  $endpoint Endpoint configuration.
	 * @param string $context Request context (admin/frontend).
	 */
	private static function process_ajax_request( $endpoint, $context ) {
		try {
			$config = $endpoint['config'];
			$action = self::get_current_action();

			// Verify capability.
			if ( ! current_user_can( $config['capability'] ) ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					"Insufficient capability for AJAX action: {$action}",
					'capability_error',
					array(
						'action'              => $action,
						'required_capability' => $config['capability'],
						'user_capabilities'   => wp_get_current_user()->allcaps,
					)
				);
				wp_send_json_error( 'Insufficient permissions' );
				return;
			}

			// Verify nonce.
			if ( ! self::verify_ajax_nonce( $config['nonce_action'] ) ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					"Invalid nonce for AJAX action: {$action}",
					'nonce_error',
					array( 'action' => $action )
				);
				wp_send_json_error( 'Invalid security token' );
				return;
			}

			// Validate input if required.
			if ( $config['validate_input'] ) {
				$input_data        = self::get_ajax_input_data();
				$validation_result = self::validate_ajax_input( $input_data );

				if ( is_wp_error( $validation_result ) ) {
					if ( $config['log_errors'] ) {
						AIOHM_BOOKING_Error_Handler::log_error(
							"AJAX input validation failed for action: {$action}",
							'validation_error',
							array(
								'action' => $action,
								'errors' => $validation_result->get_error_messages(),
							)
						);
					}
					wp_send_json_error(
						array(
							'message' => 'Input validation failed',
							'errors'  => $validation_result->get_error_messages(),
						)
					);
					return;
				}
			}

			// Execute callback.
			$result = call_user_func( $endpoint['callback'] );

			// Handle WP_Error responses.
			if ( is_wp_error( $result ) ) {
				if ( $config['log_errors'] ) {
					AIOHM_BOOKING_Error_Handler::log_error(
						"AJAX callback returned error for action: {$action}",
						'callback_error',
						array(
							'action'        => $action,
							'error_code'    => $result->get_error_code(),
							'error_message' => $result->get_error_message(),
						)
					);
				}
				wp_send_json_error( $result->get_error_message() );
				return;
			}

			// Send success response.
			wp_send_json_success( $result );

		} catch ( Exception $e ) {
			AIOHM_BOOKING_Error_Handler::log_error(
				'Exception in AJAX processing: ' . $e->getMessage(),
				'exception_error',
				array(
					'action'    => $action,
					'context'   => $context,
					'exception' => get_class( $e ),
					'trace'     => $e->getTraceAsString(),
				)
			);
			wp_send_json_error( 'An unexpected error occurred' );
		}
	}

	/**
	 * Get current AJAX action
	 *
	 * @return string
	 */
	private static function get_current_action() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just reading action parameter for routing, not processing form data
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';

		// Remove the aiohm_booking_ prefix if present.
		if ( 0 === strpos( $action, 'aiohm_booking_' ) ) {
			$action = str_replace( 'aiohm_booking_', '', $action );
		}

		return $action;
	}

	/**
	 * Verify AJAX nonce
	 *
	 * @param string $nonce_action Nonce action.
	 * @return bool
	 */
	private static function verify_ajax_nonce( $nonce_action ) {
		$nonce = isset( $_REQUEST['nonce'] ) ? wp_unslash( $_REQUEST['nonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified with wp_verify_nonce
		return ! empty( $nonce ) && wp_verify_nonce( $nonce, $nonce_action );
	}

	/**
	 * Get AJAX input data
	 *
	 * @return array
	 */
	private static function get_ajax_input_data() {
		$input = array();

		// Get data from POST.
		if ( ! empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called from verified AJAX context
			$input = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called from verified AJAX context
		}

		// Get data from request body for JSON requests.
		$request_body = file_get_contents( 'php://input' );
		if ( ! empty( $request_body ) ) {
			$json_data = json_decode( $request_body, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$input = array_merge( $input, $json_data );
			}
		}

		return $input;
	}

	/**
	 * Validate AJAX input data
	 *
	 * @param array $input Input data.
	 * @return bool|WP_Error
	 */
	private static function validate_ajax_input( $input ) {
		// Basic sanitization.
		foreach ( $input as $key => $value ) {
			if ( is_string( $value ) ) {
				$input[ $key ] = sanitize_text_field( $value );
			} elseif ( is_array( $value ) ) {
				$input[ $key ] = array_map( 'sanitize_text_field', $value );
			}
		}

		// Add more specific validation rules as needed.
		return true;
	}

	/**
	 * Get all registered endpoints (for debugging)
	 *
	 * @return array
	 */
	public static function get_registered_endpoints() {
		return self::$endpoints;
	}

	/**
	 * Check if an endpoint is registered
	 *
	 * @param string $action Action name.
	 * @return bool
	 */
	public static function is_endpoint_registered( $action ) {
		return isset( self::$endpoints[ $action ] );
	}
}
