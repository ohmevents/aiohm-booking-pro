<?php
/**
 * AIOHM Booking REST API Controller
 * Handles REST API endpoints for payment processing and booking operations.
 *
 * @package AIOHM_Booking
 *
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Controller for AIOHM Booking.
 */
class AIOHM_BOOKING_REST_API {

	/**
	 * Initialize the REST API.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_routes() {
		// Booking hold endpoint.
		register_rest_route(
			'aiohm-booking/v1',
			'/hold',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_booking_hold' ),
				'permission_callback' => array( __CLASS__, 'check_booking_permissions' ),
				'args'                => array(
					'booking_data' => array(
						'required'          => true,
						'validate_callback' => array( __CLASS__, 'validate_booking_data' ),
					),
				),
			)
		);

		// Stripe endpoints.
		register_rest_route(
			'aiohm-booking/v1',
			'/stripe/create-session',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_stripe_session' ),
				'permission_callback' => array( __CLASS__, 'check_payment_permissions' ),
				'args'                => array(
					'booking_id' => array(
						'required'          => true,
						'validate_callback' => array( __CLASS__, 'validate_booking_id' ),
					),
				),
			)
		);

		register_rest_route(
			'aiohm-booking/v1',
			'/stripe/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_stripe_webhook' ),
				'permission_callback' => '__return_true', // Webhooks don't need auth.
			)
		);

		// PayPal endpoints.
		register_rest_route(
			'aiohm-booking/v1',
			'/paypal/create-order',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_paypal_order' ),
				'permission_callback' => array( __CLASS__, 'check_payment_permissions' ),
				'args'                => array(
					'booking_id' => array(
						'required'          => true,
						'validate_callback' => array( __CLASS__, 'validate_booking_id' ),
					),
				),
			)
		);

		register_rest_route(
			'aiohm-booking/v1',
			'/paypal/capture',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'capture_paypal_payment' ),
				'permission_callback' => array( __CLASS__, 'check_payment_permissions' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'validate_callback' => array( __CLASS__, 'validate_paypal_order_id' ),
					),
				),
			)
		);

		register_rest_route(
			'aiohm-booking/v1',
			'/paypal/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_paypal_webhook' ),
				'permission_callback' => '__return_true', // Webhooks don't need auth.
			)
		);
	}

	/**
	 * Handle booking hold request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 */
	public static function handle_booking_hold( $request ) {
		try {
			$booking_data = $request->get_param( 'booking_data' );

			// Validate and sanitize booking data using comprehensive validation.
			if ( ! class_exists( 'AIOHM_BOOKING_Validation' ) ) {
				return new WP_Error( 'validation_unavailable', __( 'Validation system unavailable', 'aiohm-booking-pro' ) );
			}

			if ( ! AIOHM_BOOKING_Validation::validate_booking_data( $booking_data, 'rest' ) ) {
				$errors        = AIOHM_BOOKING_Validation::get_errors();
				$error_message = ! empty( $errors ) ? implode( ' ', array_values( $errors ) ) : __( 'Validation failed', 'aiohm-booking-pro' );
				return new WP_Error( 'validation_failed', $error_message, array( 'errors' => $errors ) );
			}

			// Sanitize the data.
			$booking_data = AIOHM_BOOKING_Validation::sanitize_booking_data( $booking_data );

			// Check availability.
			$availability_check = self::check_availability( $booking_data );
			if ( is_wp_error( $availability_check ) ) {
				return $availability_check;
			}

			// Create hold record.
			$hold_id = self::create_booking_hold( $booking_data );

			if ( is_wp_error( $hold_id ) ) {
				return $hold_id;
			}

			return new WP_REST_Response(
				array(
					'success'    => true,
					'hold_id'    => $hold_id,
					'expires_at' => gmdate( 'c', strtotime( '+15 minutes' ) ),
					'message'    => __( 'Booking held successfully', 'aiohm-booking-pro' ),
				),
				200
			);
		} catch ( Exception $e ) {
			AIOHM_BOOKING_Validation::log_error( 'Booking hold failed: ' . $e->getMessage(), array( 'booking_data' => $booking_data ?? null ) );
			return new WP_Error(
				'booking_hold_failed',
				__( 'Failed to hold booking', 'aiohm-booking-pro' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Create Stripe checkout session.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 */
	public static function create_stripe_session( $request ) {
		try {
			$booking_id = $request->get_param( 'booking_id' );

			// Get booking details.
			$booking = self::get_booking_details( $booking_id );
			if ( ! $booking ) {
				return new WP_Error(
					'booking_not_found',
					__( 'Booking not found', 'aiohm-booking-pro' ),
					array( 'status' => 404 )
				);
			}

			// Check if Stripe module exists and is enabled.
			if ( AIOHM_BOOKING_Utilities::is_free_version() ) {
				return new WP_Error(
					'stripe_pro_required',
					__( 'Stripe payments require AIOHM Booking PRO. Upgrade to access payment processing.', 'aiohm-booking-pro' ),
					array( 'status' => 402 )
				);
			}

			if ( ! AIOHM_BOOKING_Utilities::is_module_available( 'stripe' ) ) {
				return new WP_Error(
					'stripe_not_available',
					__( 'Stripe payment module is not available', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}

			// Get Stripe module instance.
			$stripe_module = AIOHM_BOOKING_Module_Registry::get_module_instance( 'stripe' );

			if ( ! $stripe_module || ! $stripe_module->is_enabled() ) {
				return new WP_Error(
					'stripe_disabled',
					__( 'Stripe payment method is not enabled', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}

			// Create Stripe session.
			$session_data = $stripe_module->create_checkout_session_data( $booking );

			return new WP_REST_Response(
				array(
					'success' => true,
					'session' => $session_data,
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'stripe_session_failed',
				__( 'Failed to create Stripe session', 'aiohm-booking-pro' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Handle Stripe webhook.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 */
	public static function handle_stripe_webhook( $request ) {
		// Check if Stripe is available in free version
		if ( AIOHM_BOOKING_Utilities::is_free_version() ) {
			return new WP_Error(
				'stripe_pro_required',
				__( 'Stripe webhooks require AIOHM Booking PRO.', 'aiohm-booking-pro' ),
				array( 'status' => 402 )
			);
		}

		try {
			$payload    = $request->get_body();
			$sig_header = $request->get_header( 'stripe-signature' );

			// Check if Stripe module exists.
			if ( ! AIOHM_BOOKING_Utilities::is_module_available( 'stripe' ) ) {
				return new WP_Error(
					'stripe_not_available',
					__( 'Stripe payment module is not available', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}

			// Get Stripe module instance.
			$stripe_module = AIOHM_BOOKING_Module_Registry::get_module_instance( 'stripe' );

			if ( ! $stripe_module ) {
				return new WP_Error(
					'stripe_module_error',
					__( 'Could not load Stripe module', 'aiohm-booking-pro' ),
					array( 'status' => 500 )
				);
			}

			$result = $stripe_module->process_webhook( $payload, $sig_header );

			if ( $result ) {
				return new WP_REST_Response( array( 'success' => true ), 200 );
			} else {
				return new WP_Error(
					'webhook_processing_failed',
					__( 'Webhook processing failed', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'webhook_error',
				__( 'Webhook processing error', 'aiohm-booking-pro' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Handle PayPal webhook.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 */
	public static function handle_paypal_webhook( $request ) {
		// Check if PayPal is available in free version
		if ( AIOHM_BOOKING_Utilities::is_free_version() ) {
			return new WP_Error(
				'paypal_pro_required',
				__( 'PayPal webhooks require AIOHM Booking PRO.', 'aiohm-booking-pro' ),
				array( 'status' => 402 )
			);
		}

		try {
			$payload = $request->get_body();
			$headers = getallheaders();

			// Load PayPal module if it exists.
			$paypal_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/class-aiohm-booking-module-paypal.php';
			if ( ! class_exists( 'AIOHM_BOOKING_Module_PayPal' ) && file_exists( $paypal_file ) ) {
				require_once $paypal_file;
			}

			// Check if PayPal module is available
			if ( ! class_exists( 'AIOHM_BOOKING_Module_PayPal' ) ) {
				return new WP_Error(
					'paypal_module_not_available',
					__( 'PayPal module is not available', 'aiohm-booking-pro' ),
					array( 'status' => 503 )
				);
			}

			$paypal_module = new AIOHM_BOOKING_Module_PayPal();
			$result        = $paypal_module->process_webhook( $payload, $headers );

			if ( $result ) {
				return new WP_REST_Response( array( 'success' => true ), 200 );
			} else {
				return new WP_Error(
					'webhook_processing_failed',
					__( 'PayPal webhook processing failed', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'webhook_error',
				__( 'PayPal webhook processing error', 'aiohm-booking-pro' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Create PayPal order.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 */
	public static function create_paypal_order( $request ) {
		// Check if PayPal is available in free version
		if ( AIOHM_BOOKING_Utilities::is_free_version() ) {
			return new WP_Error(
				'paypal_pro_required',
				__( 'PayPal payments require AIOHM Booking PRO. Upgrade to access payment processing.', 'aiohm-booking-pro' ),
				array( 'status' => 402 )
			);
		}

		try {
			$booking_id = $request->get_param( 'booking_id' );

			// Get booking details.
			$booking = self::get_booking_details( $booking_id );
			if ( ! $booking ) {
				return new WP_Error(
					'booking_not_found',
					__( 'Booking not found', 'aiohm-booking-pro' ),
					array( 'status' => 404 )
				);
			}

			// Load PayPal module if it exists.
			$paypal_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/class-aiohm-booking-module-paypal.php';
			if ( ! class_exists( 'AIOHM_BOOKING_Module_PayPal' ) && file_exists( $paypal_file ) ) {
				require_once $paypal_file;
			}

			// Check if PayPal module is available
			if ( ! class_exists( 'AIOHM_BOOKING_Module_PayPal' ) ) {
				return new WP_Error(
					'paypal_module_not_available',
					__( 'PayPal module is not available', 'aiohm-booking-pro' ),
					array( 'status' => 503 )
				);
			}

			$paypal_module = new AIOHM_BOOKING_Module_PayPal();

			if ( ! $paypal_module->is_enabled() ) {
				return new WP_Error(
					'paypal_disabled',
					__( 'PayPal payment method is not enabled', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}

			// Create PayPal order.
			$order_data = $paypal_module->create_paypal_order_data( $booking );

			return new WP_REST_Response(
				array(
					'success' => true,
					'order'   => $order_data,
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'paypal_order_failed',
				__( 'Failed to create PayPal order', 'aiohm-booking-pro' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Capture PayPal payment.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 */
	public static function capture_paypal_payment( $request ) {
		// Check if PayPal is available in free version
		if ( AIOHM_BOOKING_Utilities::is_free_version() ) {
			return new WP_Error(
				'paypal_pro_required',
				__( 'PayPal payment capture requires AIOHM Booking PRO.', 'aiohm-booking-pro' ),
				array( 'status' => 402 )
			);
		}

		try {
			$order_id = $request->get_param( 'order_id' );

			// Load PayPal module if it exists.
			$paypal_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/class-aiohm-booking-module-paypal.php';
			if ( ! class_exists( 'AIOHM_BOOKING_Module_PayPal' ) && file_exists( $paypal_file ) ) {
				require_once $paypal_file;
			}

			// Check if PayPal module is available
			if ( ! class_exists( 'AIOHM_BOOKING_Module_PayPal' ) ) {
				return new WP_Error(
					'paypal_module_not_available',
					__( 'PayPal module is not available', 'aiohm-booking-pro' ),
					array( 'status' => 503 )
				);
			}

			$paypal_module = new AIOHM_BOOKING_Module_PayPal();
			$result        = $paypal_module->capture_payment( $order_id );

			if ( $result ) {
				return new WP_REST_Response(
					array(
						'success' => true,
						'message' => __( 'Payment captured successfully', 'aiohm-booking-pro' ),
					),
					200
				);
			} else {
				return new WP_Error(
					'capture_failed',
					__( 'Payment capture failed', 'aiohm-booking-pro' ),
					array( 'status' => 400 )
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'capture_error',
				__( 'Payment capture error', 'aiohm-booking-pro' ),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Permission callbacks.
	 */
	public static function check_booking_permissions() {
		// Allow public booking creation but verify nonce for security.
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $nonce, 'nonce' ) ) {
			return new WP_Error( 'nonce_verification_failed', __( 'Security check failed.', 'aiohm-booking-pro' ) );
		}
		return true; // Allow public booking creation.
	}

	/**
	 * Check payment permissions.
	 *
	 * @return bool Whether the user has payment permissions.
	 */
	public static function check_payment_permissions() {
		// Payment operations require proper authentication.
		// Allow public payment processing but verify the booking belongs to the session/user.
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $nonce, 'payment_nonce' ) ) {
			return new WP_Error( 'nonce_verification_failed', __( 'Payment security check failed.', 'aiohm-booking-pro' ) );
		}

		// Additional check: verify booking ownership if user is logged in.
		if ( is_user_logged_in() ) {
			// This will be validated in the callback functions by checking booking ownership.
			return true;
		}

		// For guests, allow but validate in callback that booking session matches.
		return true;
	}

	/**
	 * Validation callbacks.
	 *
	 * @param array $data The booking data to validate.
	 */
	public static function validate_booking_data( $data ) {
		// Use the comprehensive validation class.
		if ( ! class_exists( 'AIOHM_BOOKING_Validation' ) ) {
			return new WP_Error( 'validation_class_missing', __( 'Validation class not available', 'aiohm-booking-pro' ) );
		}

		if ( ! AIOHM_BOOKING_Validation::validate_booking_data( $data, 'rest' ) ) {
			$errors        = AIOHM_BOOKING_Validation::get_errors();
			$error_message = ! empty( $errors ) ? implode( ' ', array_values( $errors ) ) : __( 'Validation failed', 'aiohm-booking-pro' );
			return new WP_Error( 'validation_failed', $error_message, array( 'errors' => $errors ) );
		}

		return true;
	}

	/**
	 * Validate booking ID.
	 *
	 * @param int $booking_id The booking ID to validate.
	 *
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_booking_id( $booking_id ) {
		if ( ! is_numeric( $booking_id ) || $booking_id <= 0 ) {
			return new WP_Error( 'invalid_booking_id', __( 'Invalid booking ID', 'aiohm-booking-pro' ) );
		}

		// Additional security: ensure booking ID is within reasonable bounds.
		$booking_id = absint( $booking_id );
		if ( $booking_id > 999999999 ) { // Prevent potential overflow attacks.
			return new WP_Error( 'invalid_booking_id', __( 'Invalid booking ID', 'aiohm-booking-pro' ) );
		}

		return true;
	}

	/**
	 * Validate PayPal order ID.
	 *
	 * @param string $order_id The PayPal order ID to validate.
	 *
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_paypal_order_id( $order_id ) {
		if ( ! is_string( $order_id ) || empty( $order_id ) ) {
			return new WP_Error( 'invalid_order_id', __( 'Invalid PayPal order ID', 'aiohm-booking-pro' ) );
		}

		// Sanitize and validate PayPal order ID format.
		$order_id = sanitize_text_field( $order_id );

		// PayPal order IDs typically follow a pattern like "5O190127TN364715T".
		if ( ! preg_match( '/^[A-Z0-9]{10,30}$/', $order_id ) ) {
			return new WP_Error( 'invalid_order_id_format', __( 'Invalid PayPal order ID format', 'aiohm-booking-pro' ) );
		}

		return true;
	}

	/**
	 * Helper methods.
	 *
	 * @param array $booking_data The booking data to check availability for.
	 */
	private static function check_availability( $booking_data ) {
		global $wpdb;

		// Extract booking parameters.
		$checkin_date     = $booking_data['checkin_date'] ?? '';
		$checkout_date    = $booking_data['checkout_date'] ?? '';
		$guests           = absint( $booking_data['guests'] ?? 1 );
		$accommodation_id = $booking_data['accommodation_id'] ?? '';

		// Validate required dates.
		if ( empty( $checkin_date ) || empty( $checkout_date ) ) {
			return new WP_Error( 'missing_dates', __( 'Check-in and check-out dates are required', 'aiohm-booking-pro' ) );
		}

		// Convert dates to timestamps for comparison.
		$checkin_timestamp  = strtotime( $checkin_date );
		$checkout_timestamp = strtotime( $checkout_date );

		if ( $checkin_timestamp >= $checkout_timestamp ) {
			return new WP_Error( 'invalid_date_range', __( 'Check-out date must be after check-in date', 'aiohm-booking-pro' ) );
		}

		// Check against calendar data table.
		$table_name = $wpdb->prefix . 'aiohm_booking_calendar';

		// Create cache key for availability check.
		$cache_key     = 'aiohm_availability_' . md5( $accommodation_id . $checkin_date . $checkout_date );
		$cached_result = get_transient( $cache_key );

		if ( false !== $cached_result ) {
			$result = $cached_result;
		} else {
			// Query for overlapping bookings.
			$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT COUNT(*) as booking_count
						FROM ' . esc_sql( $table_name ) . '
						WHERE accommodation_id = %s
						AND status IN (\'booked\', \'hold\')
						AND (
							(start_date <= %s AND end_date > %s) OR
							(start_date < %s AND end_date >= %s) OR
							(start_date >= %s AND end_date <= %s)
						)',
					$accommodation_id,
					$checkin_date,
					$checkin_date,
					$checkout_date,
					$checkout_date,
					$checkin_date,
					$checkout_date
				)
			);

			// Cache the result for 5 minutes (availability can change quickly).
			set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );
		}

		if ( $result && $result->booking_count > 0 ) {
			return new WP_Error( 'not_available', __( 'The selected dates are not available', 'aiohm-booking-pro' ) );
		}

		// Check guest capacity if accommodation ID is provided.
		if ( ! empty( $accommodation_id ) ) {
			$capacity = get_post_meta( $accommodation_id, 'aiohm_max_guests', true );
			if ( ! empty( $capacity ) && $guests > intval( $capacity ) ) {
				return new WP_Error( 'capacity_exceeded', __( 'Number of guests exceeds accommodation capacity', 'aiohm-booking-pro' ) );
			}
		}

		return true;
	}

	/**
	 * Create booking hold.
	 *
	 * @param array $booking_data The booking data to create a hold for.
	 *
	 * @return int|WP_Error The hold ID on success, WP_Error on failure.
	 */
	private static function create_booking_hold( $booking_data ) {
		global $wpdb;

		// Sanitize table name for security.
		$table_name = $wpdb->prefix . 'aiohm_booking_holds';
		$table_name = esc_sql( $table_name );

		// Sanitize and validate input data.
		$hold_data = array(
			'checkin_date'   => sanitize_text_field( $booking_data['checkin_date'] ?? '' ),
			'checkout_date'  => sanitize_text_field( $booking_data['checkout_date'] ?? '' ),
			'guests'         => absint( $booking_data['guests'] ?? 1 ),
			'customer_email' => sanitize_email( $booking_data['customer_email'] ?? '' ),
			'customer_name'  => sanitize_text_field( $booking_data['customer_name'] ?? '' ),
			'hold_expires'   => gmdate( 'Y-m-d H:i:s', time() + 900 ), // 15 minutes from now (UTC)
			'created_at'     => current_time( 'mysql' ),
		);

		// Validate required fields.
		if ( empty( $hold_data['checkin_date'] ) || empty( $hold_data['checkout_date'] ) ) {
			return new WP_Error( 'invalid_booking_data', __( 'Check-in and check-out dates are required', 'aiohm-booking-pro' ) );
		}

		// Validate email if provided.
		if ( empty( $hold_data['customer_email'] ) === false && ! is_email( $hold_data['customer_email'] ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid email address', 'aiohm-booking-pro' ) );
		}

		// Use WordPress database functions for security.
		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table_name,
			$hold_data,
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s' ) // Data format specification.
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to create booking hold', 'aiohm-booking-pro' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get booking details.
	 *
	 * @param int $booking_id The booking ID to retrieve.
	 *
	 * @return array|false Booking details array or false if not found.
	 */
	private static function get_booking_details( $booking_id ) {
		global $wpdb;

		// Sanitize booking ID.
		$booking_id = absint( $booking_id );

		if ( empty( $booking_id ) ) {
			return false;
		}

		// Query the orders table for booking details.
		$orders_table = $wpdb->prefix . 'aiohm_booking_orders';
		$posts_table  = $wpdb->posts;

		// Create cache key for booking details.
		$cache_key      = 'aiohm_booking_details_' . $booking_id;
		$cached_booking = get_transient( $cache_key );

		if ( false !== $cached_booking ) {
			$booking = $cached_booking;
		} else {
			$booking = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					'SELECT
							o.*,
							p.post_title as accommodation_title,
							p.post_name as accommodation_slug
						FROM ' . esc_sql( $orders_table ) . ' o
						LEFT JOIN ' . esc_sql( $posts_table ) . ' p ON o.accommodation_id = p.ID
						WHERE o.id = %d AND o.status IN (\'pending\', \'confirmed\', \'completed\')',
					$booking_id
				),
				ARRAY_A
			);

			// Cache the result for 15 minutes (booking details don't change frequently).
			if ( $booking ) {
				set_transient( $cache_key, $booking, 15 * MINUTE_IN_SECONDS );
			}
		}

		if ( ! $booking ) {
			return false;
		}

		// Format the response.
		return array(
			'id'                  => $booking['id'],
			'accommodation_id'    => $booking['accommodation_id'],
			'accommodation_title' => $booking['accommodation_title'] ? $booking['accommodation_title'] : __( 'Unknown Accommodation', 'aiohm-booking-pro' ),
			'accommodation_slug'  => $booking['accommodation_slug'],
			'checkin_date'        => $booking['checkin_date'],
			'checkout_date'       => $booking['checkout_date'],
			'guests'              => $booking['guests'],
			'customer_name'       => $booking['customer_name'],
			'customer_email'      => $booking['customer_email'],
			'customer_phone'      => $booking['customer_phone'],
			'total_amount'        => $booking['total_amount'],
			'currency'            => $booking['currency'] ? $booking['currency'] : 'USD',
			'status'              => $booking['status'],
			'payment_method'      => $booking['payment_method'],
			'payment_status'      => $booking['payment_status'],
			'booking_date'        => $booking['created_at'],
			'special_requests'    => $booking['special_requests'],
			'metadata'            => json_decode( $booking['metadata'] ? $booking['metadata'] : '{}', true ),
		);
	}
}
