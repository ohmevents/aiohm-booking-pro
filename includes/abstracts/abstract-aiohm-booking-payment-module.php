<?php

namespace AIOHM_Booking_PRO\Abstracts;
/**
 * Abstract Payment Module Base Class
 *
 * Provides common functionality for all payment modules to reduce code duplication.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for payment modules
 */
abstract class AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_Payment_Module_Abstract extends AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_Module_Abstract {

	/**
	 * Check rate limit for webhook endpoints
	 *
	 * @return void|false Returns false if rate limit exceeded
	 */
	protected static function check_rate_limit() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Server variable access for rate limiting
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		if ( empty( $ip ) ) {
			return;
		}

		$rate_limit_key = 'aiohm_booking_webhook_rate_limit_' . md5( $ip );
		$current_count  = get_transient( $rate_limit_key );

		if ( false === $current_count ) {
			set_transient( $rate_limit_key, 1, 60 ); // 1 minute window.
		} elseif ( $current_count >= 10 ) { // Max 10 requests per minute.
			http_response_code( 429 );
			exit( 'Rate limit exceeded' );
		} else {
			set_transient( $rate_limit_key, $current_count + 1, 60 );
		}
	}

	/**
	 * Update payment status in database
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $transaction_id Transaction ID.
	 * @param string $status Payment status.
	 * @param string $method Payment method.
	 * @return bool|int Update result
	 */
	protected function update_payment_status( $booking_id, $transaction_id, $status, $method ) {
		try {
			global $wpdb;

			$order_table = $wpdb->prefix . 'aiohm_booking_order';
			$order_table = esc_sql( $order_table );

			$update_data = array(
				'payment_status' => sanitize_text_field( $status ),
				'payment_method' => sanitize_text_field( $method ),
				'transaction_id' => sanitize_text_field( $transaction_id ),
				'updated_at'     => current_time( 'mysql' ),
			);

			$where_data = array(
				'id' => intval( $booking_id ),
			);

			$update_formats = array( '%s', '%s', '%s', '%s' );
			$where_formats  = array( '%d' );

			$result = $wpdb->update(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for payment transaction lookup in custom table
				$order_table,
				$update_data,
				$where_data,
				$update_formats,
				$where_formats
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

			if ( false === $result ) {
				AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::handle_database_error(
					'update_payment_status',
					$wpdb->last_error
				);
				return false;
			}

			return $result;

		} catch ( Exception $e ) {
			AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
				'Exception updating payment status: ' . $e->getMessage(),
				'exception_error',
				array(
					'gateway'    => $this->get_gateway_id(),
					'booking_id' => $booking_id,
					'exception'  => get_class( $e ),
					'trace'      => $e->getTraceAsString(),
				)
			);
			return false;
		}
	}

	/**
	 * Format amount for currency display
	 *
	 * @param float  $amount The amount to format.
	 * @param string $currency Currency code.
	 * @param int    $decimals Number of decimal places.
	 * @return string Formatted amount
	 */
	protected function format_currency_amount( $amount, $currency = 'USD', $decimals = 2 ) {
		return number_format( floatval( $amount ), $decimals, '.', '' );
	}

	/**
	 * Convert amount to cents for payment processing
	 *
	 * @param float $amount Amount in main currency units.
	 * @return int Amount in cents
	 */
	protected function amount_to_cents( $amount ) {
		return intval( round( floatval( $amount ) * 100 ) );
	}

	/**
	 * Convert amount from cents to main currency units
	 *
	 * @param int $cents Amount in cents.
	 * @return float Amount in main currency units
	 */
	protected function cents_to_amount( $cents ) {
		return floatval( $cents ) / 100;
	}

	/**
	 * Enqueue payment module admin assets
	 *
	 * @param string $module_id Module identifier.
	 * @param string $page Current page.
	 */
	protected function enqueue_payment_admin_assets( $module_id, $page = '' ) {
		if ( empty( $page ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin asset loading based on page parameter
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		}

		if ( 'aiohm-booking-settings' !== $page ) {
			return;
		}

		$css_file = "aiohm-booking-{$module_id}-admin.css";
		$js_file  = "aiohm-booking-{$module_id}-admin.js";

		// Enqueue CSS.
		if ( file_exists( AIOHM_BOOKING_DIR . "assets/css/{$css_file}" ) ) {
			wp_enqueue_style(
				"aiohm-booking-{$module_id}-admin",
				AIOHM_BOOKING_URL . "assets/css/{$css_file}",
				array(),
				AIOHM_BOOKING_VERSION
			);
		}

		// Enqueue JS.
		if ( file_exists( AIOHM_BOOKING_DIR . "assets/js/{$js_file}" ) ) {
			wp_enqueue_script(
				"aiohm-booking-{$module_id}-admin",
				AIOHM_BOOKING_URL . "assets/js/{$js_file}",
				array( 'jquery' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// Localize script.
			wp_localize_script(
				"aiohm-booking-{$module_id}-admin",
				"aiohm_booking_{$module_id}_ajax",
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( "aiohm_booking_{$module_id}_ajax" ),
				)
			);
		}
	}

	/**
	 * Validate webhook request (POST only)
	 *
	 * @return bool True if valid webhook request
	 */
	protected function validate_webhook_request() {
		try {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Server variable access for webhook validation
			$request_method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
			if ( 'POST' !== $request_method ) {
				AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
					'Invalid webhook request method: ' . $request_method,
					'webhook_error',
					array( 'gateway' => $this->get_gateway_id() )
				);
				http_response_code( 405 );
				exit( 'Method not allowed' );
			}

			// Apply rate limiting.
			$this->check_rate_limit();

			return true;

		} catch ( Exception $e ) {
			AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
				'Exception during webhook validation: ' . $e->getMessage(),
				'exception_error',
				array(
					'gateway'   => $this->get_gateway_id(),
					'exception' => get_class( $e ),
					'trace'     => $e->getTraceAsString(),
				)
			);
			http_response_code( 500 );
			exit( 'Internal server error' );
		}
	}

	/**
	 * Send webhook response
	 *
	 * @param int    $code HTTP status code.
	 * @param string $message Response message.
	 */
	protected function send_webhook_response( $code, $message ) {
		http_response_code( $code );
		echo esc_html( $message );
		exit;
	}

	// Abstract methods that must be implemented by child classes.

	/**
	 * Get the payment gateway identifier
	 *
	 * @return string
	 */
	abstract protected function get_gateway_id();

	/**
	 * Get active credentials for the payment gateway
	 *
	 * @return array
	 */
	abstract protected function get_active_credentials();

	/**
	 * Process payment with the gateway
	 *
	 * @param array $order_data Order data.
	 * @return array|WP_Error
	 */
	abstract public function process_payment( $order_data );

	/**
	 * Handle webhook from the payment gateway
	 *
	 * @return void
	 */
	abstract public function handle_webhook();

	/**
	 * Validate payment data before processing
	 *
	 * @param array $payment_data Payment data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	protected function validate_payment_data( $payment_data ) {
		if ( ! is_array( $payment_data ) ) {
			return new WP_Error( 'invalid_payment_data', __( 'Payment data must be an array', 'aiohm-booking-pro' ) );
		}

		$required_fields = array( 'amount', 'currency', 'order_id' );
		foreach ( $required_fields as $field ) {
			if ( empty( $payment_data[ $field ] ) ) {
				return new WP_Error(
					'missing_required_field',
					// translators: %s is the name of the missing required field.
					sprintf( __( 'Missing required field: %s', 'aiohm-booking-pro' ), $field )
				);
			}
		}

		if ( ! is_numeric( $payment_data['amount'] ) || $payment_data['amount'] <= 0 ) {
			return new WP_Error( 'invalid_amount', __( 'Amount must be a positive number', 'aiohm-booking-pro' ) );
		}

		return true;
	}

	/**
	 * Format payment success response
	 *
	 * @param array $payment_details Payment details.
	 * @return array Formatted success response.
	 */
	protected function format_payment_success( $payment_details ) {
		return array(
			'success'        => true,
			'transaction_id' => $payment_details['transaction_id'] ?? '',
			'amount'         => $payment_details['amount'] ?? 0,
			'currency'       => $payment_details['currency'] ?? 'USD',
			'status'         => 'completed',
			'timestamp'      => current_time( 'timestamp' ),
		);
	}

	/**
	 * Format payment error response
	 *
	 * @param string $error_message Error message.
	 * @param array  $error_details Additional error details.
	 * @return array Formatted error response.
	 */
	protected function format_payment_error( $error_message, $error_details = array() ) {
		AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Error_Handler::log_error(
			'Payment processing error: ' . $error_message,
			'payment_error',
			array_merge(
				array( 'gateway' => $this->get_gateway_id() ),
				$error_details
			)
		);

		return array(
			'success'   => false,
			'error'     => $error_message,
			'gateway'   => $this->get_gateway_id(),
			'timestamp' => current_time( 'timestamp' ),
		);
	}
}
