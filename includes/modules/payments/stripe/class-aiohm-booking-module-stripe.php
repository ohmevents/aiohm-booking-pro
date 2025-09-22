<?php
/**
 * Stripe Payment Module - Professional payment processing with Stripe
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* <fs_premium_only> */

use Stripe\StripeClient;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Account;
use Stripe\Refund;
use Stripe\Customer;

/**
 * Stripe Payment Module
 *
 * Handles Stripe payment processing for AIOHM Booking system.
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */
class AIOHM_BOOKING_Module_Stripe extends AIOHM_BOOKING_Payment_Module_Abstract {

	/**
	 * Check if user can access premium features
	 *
	 * @return bool
	 */
	private function can_use_premium() {

		if ( ! function_exists( 'aiohm_booking_fs' ) ) {
			return false;
		}

		$fs = aiohm_booking_fs();

		// Manual override: if user is paying but not detected as premium, allow access
		if ( $fs->is_paying() ) {
			return true;
		}

		return $fs->can_use_premium_code__premium_only();
	}

	/**
	 * Module ID.
	 *
	 * @var string
	 */
	protected $module_id = 'stripe';

	/**
	 * Get UI definition for the module.
	 *
	 * @return array Module UI definition.
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'stripe',
			'name'                => __( 'Stripe', 'aiohm-booking-pro' ),
			'description'         => __( 'Professional payment processing with Stripe - accept credit cards, digital wallets, and international payments.', 'aiohm-booking-pro' ),
			'icon'                => '💳',
			'category'            => 'payment',
			'access_level'        => 'premium',
			'is_premium'          => true,
			'priority'            => 20,
			'has_settings'        => true,
			'has_admin_page'      => false,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Initialize module hooks.
	 */
	public function init_hooks() {
		add_action( 'wp_ajax_aiohm_booking_test_stripe', array( $this, 'test_stripe_connection' ) );

		if ( ! $this->is_enabled() || ! $this->can_use_premium() ) {
			return;
		}

		add_action( 'aiohm_booking_process_payment_stripe', array( $this, 'process_payment' ) );
		add_action( 'wp_ajax_aiohm_booking_stripe_create_session', array( $this, 'create_checkout_session' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_stripe_create_session', array( $this, 'create_checkout_session' ) );
		add_action( 'wp_ajax_aiohm_booking_stripe_process_payment', array( $this, 'process_card_payment' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_stripe_process_payment', array( $this, 'process_card_payment' ) );

		add_action( 'wp_ajax_aiohm_booking_stripe_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_stripe_webhook', array( $this, 'handle_webhook' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_stripe_assets' ) );
		add_filter( 'aiohm_booking_payment_methods', array( $this, 'register_payment_method' ) );
	}

	/**
	 * Enqueue admin assets.
	 */
	protected function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( strpos( $screen->id, 'stripe' ) === false && strpos( $screen->id, 'aiohm-booking-settings' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'aiohm-booking-stripe-admin',
			AIOHM_BOOKING_URL . 'includes/modules/payments/stripe/assets/css/aiohm-booking-stripe-admin.css',
			array( 'aiohm-booking-admin' ),
			AIOHM_BOOKING_VERSION
		);

		wp_enqueue_script(
			'aiohm-booking-stripe-admin',
			AIOHM_BOOKING_URL . 'includes/modules/payments/stripe/assets/js/aiohm-booking-stripe-admin.js',
			array( 'jquery', 'aiohm-booking-admin' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		wp_localize_script(
			'aiohm-booking-stripe-admin',
			'aiohm_booking_stripe_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
			)
		);

		// Enqueue settings-specific JavaScript for test connection functionality
		wp_enqueue_script(
			'aiohm-booking-stripe-settings',
			AIOHM_BOOKING_URL . 'includes/modules/payments/stripe/assets/js/stripe-settings.js',
			array( 'jquery', 'aiohm-booking-stripe-admin' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		wp_localize_script(
			'aiohm-booking-stripe-settings',
			'aiohm_stripe_settings',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
				'test_connection_text' => __( 'Test Connection', 'aiohm-booking-pro' ),
				'testing_text'         => __( 'Testing...', 'aiohm-booking-pro' ),
				'success_text'         => __( '✓ Success', 'aiohm-booking-pro' ),
				'failed_text'          => __( '✗ Failed', 'aiohm-booking-pro' ),
			)
		);
	}

	/**
	 * Get module name.
	 *
	 * @return string Module name.
	 */


	/**
	 * Get settings fields configuration.
	 *
	 * @return array Settings fields.
	 */
	public function get_settings_fields() {
		return array(
			'stripe_test_mode'            => array(
				'type'        => 'checkbox',
				'label'       => __( 'Test Mode', 'aiohm-booking-pro' ),
				'description' => __( 'Enable test mode for development and testing', 'aiohm-booking-pro' ),
				'default'     => 1,
			),
			'stripe_publishable_key'      => array(
				'type'              => 'text',
				'label'             => __( 'Stripe Publishable Key', 'aiohm-booking-pro' ),
				'description'       => __( 'Your Stripe publishable key', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_secret_key'           => array(
				'type'              => 'password',
				'label'             => __( 'Stripe Secret Key', 'aiohm-booking-pro' ),
				'description'       => __( 'Your Stripe secret key', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_publishable_key_live' => array(
				'type'              => 'text',
				'label'             => __( 'Live Publishable Key', 'aiohm-booking-pro' ),
				'description'       => __( 'Your Stripe live publishable key (pk_live_...)', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_secret_key_live'      => array(
				'type'              => 'password',
				'label'             => __( 'Live Secret Key', 'aiohm-booking-pro' ),
				'description'       => __( 'Your Stripe live secret key (sk_live_...)', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_publishable_key_test' => array(
				'type'              => 'text',
				'label'             => __( 'Test Publishable Key', 'aiohm-booking-pro' ),
				'description'       => __( 'Your Stripe test publishable key (pk_test_...)', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_secret_key_test'      => array(
				'type'              => 'password',
				'label'             => __( 'Test Secret Key', 'aiohm-booking-pro' ),
				'description'       => __( 'Your Stripe test secret key (sk_test_...)', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_webhook_secret'       => array(
				'type'              => 'password',
				'label'             => __( 'Webhook Endpoint Secret', 'aiohm-booking-pro' ),
				'description'       => __( 'Webhook signing secret for secure webhook verification', 'aiohm-booking-pro' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stripe_payment_methods'      => array(
				'type'        => 'multiselect',
				'label'       => __( 'Enabled Payment Methods', 'aiohm-booking-pro' ),
				'description' => __( 'Select which payment methods to enable', 'aiohm-booking-pro' ),
				'options'     => array(
					'card'       => __( 'Credit/Debit Cards', 'aiohm-booking-pro' ),
					'apple_pay'  => __( 'Apple Pay', 'aiohm-booking-pro' ),
					'google_pay' => __( 'Google Pay', 'aiohm-booking-pro' ),
					'link'       => __( 'Link', 'aiohm-booking-pro' ),
				),
				'default'     => array( 'card' ),
			),
			'stripe_capture_method'       => array(
				'type'        => 'select',
				'label'       => __( 'Capture Method', 'aiohm-booking-pro' ),
				'description' => __( 'When to capture payments', 'aiohm-booking-pro' ),
				'options'     => array(
					'automatic' => __( 'Automatic - Capture immediately', 'aiohm-booking-pro' ),
					'manual'    => __( 'Manual - Authorize now, capture later', 'aiohm-booking-pro' ),
				),
				'default'     => 'automatic',
			),
		);
	}

	/**
	 * Get default settings for the module.
	 *
	 * @return array Default settings.
	 */
	protected function get_default_settings() {
		return array(
			'stripe_test_mode'            => 1,
			'stripe_publishable_key'      => '',
			'stripe_secret_key'           => '',
			'stripe_publishable_key_live' => '',
			'stripe_secret_key_live'      => '',
			'stripe_publishable_key_test' => '',
			'stripe_secret_key_test'      => '',
			'stripe_webhook_secret'       => '',
			'stripe_payment_methods'      => array( 'card' ),
			'stripe_capture_method'       => 'automatic',
		);
	}

	/**
	 * Get module settings
	 *
	 * @return array Module settings
	 */
	public function get_settings() {
		// Clear cache to ensure we get the latest settings.
		AIOHM_BOOKING_Settings::clear_cache();
		return AIOHM_BOOKING_Settings::get_all();
	}

	/**
	 * Render settings template.
	 *
	 * @since 1.0.0
	 */
	public function render_settings() {
		$settings = $this->get_settings();
		include AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/templates/stripe-settings.php';
	}

	/**
	 * Check if the module is enabled.
	 *
	 * @return bool True if enabled.
	 */
	protected function check_if_enabled() {
		$settings   = AIOHM_BOOKING_Settings::get_all();
		$enable_key = 'enable_' . $this->module_id;

		// If the setting exists (either '1' or '0'), respect it.
		if ( isset( $settings[ $enable_key ] ) ) {
			return ! empty( $settings[ $enable_key ] );
		}

		// Default to disabled for premium module.
		return false;
	}

	/**
	 * Process payment.
	 *
	 * @param array $order_data Order data.
	 */
	public function process_payment( $order_data ) {
		// This entire code block will be removed from the free version.
		if ( ! $this->can_use_premium() ) {
			return new WP_Error( 'premium_required', __( 'Stripe payments require AIOHM Booking Pro', 'aiohm-booking-pro' ) );
		}

		try {
			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					return new WP_Error( 'stripe_sdk_missing', __( 'Stripe PHP SDK not found. Please install the Stripe SDK by running: composer require stripe/stripe-php', 'aiohm-booking-pro' ) );
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			// Get secret key - try MVP compatibility first
			$secret_key = '';
			if ( ! empty( $settings['stripe_secret_key'] ) ) {
				// MVP format - single key
				$secret_key = $settings['stripe_secret_key'];
			} elseif ( $test_mode && ! empty( $settings['stripe_secret_key_test'] ) ) {
				// Modular format - test key
				$secret_key = $settings['stripe_secret_key_test'];
			} elseif ( ! $test_mode && ! empty( $settings['stripe_secret_key_live'] ) ) {
				// Modular format - live key
				$secret_key = $settings['stripe_secret_key_live'];
			}

			if ( empty( $secret_key ) ) {
				return new WP_Error( 'stripe_config_error', __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			}

			Stripe::setApiKey( $secret_key );

			// Create checkout session for hosted checkout page
			$checkout_session_data = array(
				'payment_method_types' => $settings['stripe_payment_methods'] ?? array( 'card' ),
				'line_items'           => array(
					array(
						'price_data' => array(
							'currency'     => strtolower( $order_data['currency'] ?? 'usd' ),
							'product_data' => array(
								'name'        => 'Booking Payment',
								/* translators: %s: order ID number */
								'description' => sprintf( __( 'Booking payment for order #%s', 'aiohm-booking-pro' ), $order_data['order_id'] ?? '' ),
							),
							'unit_amount'  => intval( $order_data['amount'] * 100 ), // Convert to cents
						),
						'quantity'   => 1,
					),
				),
				'mode'                 => 'payment',
				'success_url'          => home_url( '/?aiohm_booking_action=success&session_id={CHECKOUT_SESSION_ID}' ),
				'cancel_url'           => home_url( '/?aiohm_booking_action=cancelled' ),
				'metadata'             => array(
					'order_id'       => $order_data['order_id'] ?? '',
					'customer_email' => $order_data['customer_email'] ?? '',
				),
				'customer_email'       => $order_data['customer_email'] ?? '',
			);

			$checkout_session = Session::create( $checkout_session_data );

			return array(
				'success'      => true,
				'checkout_url' => $checkout_session->url,
				'session_id'   => $checkout_session->id,
			);

		} catch ( ApiErrorException $e ) {
			return new WP_Error( 'stripe_api_error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', __( 'Payment processing failed', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Process card payment.
	 */
	public function process_card_payment() {
		try {
			// Verify security using centralized helper.
			if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'payment_nonce' ) ) {
				return; // Error response already sent by helper
			}

			// Sanitize payment data using centralized helper.
			$payment_fields = AIOHM_BOOKING_Security_Helper::sanitize_post_fields(
				array(
					'booking_id'        => 'int',
					'payment_method_id' => 'text',
					'amount'            => 'float',
					'currency'          => array( 'text', 'usd' ),
				)
			);

			$booking_id        = $payment_fields['booking_id'];
			$payment_method_id = $payment_fields['payment_method_id'];
			$amount            = $payment_fields['amount'];
			$currency          = $payment_fields['currency'];

			// Validate payment data using comprehensive validation.
			$payment_data = array(
				'booking_id'        => $booking_id,
				'payment_method_id' => $payment_method_id,
				'amount'            => $amount,
				'currency'          => $currency,
			);

			if ( ! class_exists( 'AIOHM_BOOKING_Validation' ) ) {
				wp_send_json_error( array( 'message' => __( 'Validation system unavailable', 'aiohm-booking-pro' ) ) );
				return;
			}

			if ( ! AIOHM_BOOKING_Validation::validate_payment_data( $payment_data ) ) {
				$errors        = AIOHM_BOOKING_Validation::get_errors();
				$error_message = ! empty( $errors ) ? implode( ' ', array_values( $errors ) ) : __( 'Payment validation failed', 'aiohm-booking-pro' );
				wp_send_json_error( array( 'message' => $error_message ) );
				return;
			}

			// Get booking details with security.
			global $wpdb;

			// Sanitize table name.
			$order_table = $wpdb->prefix . 'aiohm_booking_order';
			$order_table = esc_sql( $order_table );

			// Use prepared statement for security.
			$booking = $wpdb->get_row(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for Stripe payment record insertion
				$wpdb->prepare(
					'SELECT * FROM %i WHERE id = %d',
					$order_table,
					$booking_id
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

			if ( ! $booking ) {
				wp_send_json_error( array( 'message' => __( 'Booking not found', 'aiohm-booking-pro' ) ) );
				return;
			}

			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					wp_send_json_error( array( 'message' => __( 'Stripe PHP SDK not found', 'aiohm-booking-pro' ) ) );
					return;
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			// Check if using test/live mode fields or simple fields.
			if ( isset( $settings['stripe_secret_key_test'] ) || isset( $settings['stripe_secret_key_live'] ) ) {
				// Use test/live mode fields.
				$secret_key = $test_mode
					? ( $settings['stripe_secret_key_test'] ?? '' )
					: ( $settings['stripe_secret_key_live'] ?? '' );
			} else {
				// Fall back to simple field names for MVP compatibility.
				$secret_key = $settings['stripe_secret_key'] ?? '';
			}

			if ( empty( $secret_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) ) );
				return;
			}

			Stripe::setApiKey( $secret_key );

			// Create payment intent.
			$payment_intent = PaymentIntent::create(
				array(
					'amount'              => intval( $amount * 100 ),
					'currency'            => $currency,
					'payment_method'      => $payment_method_id,
					'confirmation_method' => 'manual',
					'confirm'             => true,
					'return_url'          => home_url( '/booking-success' ),
					'metadata'            => array(
						'booking_id'     => $booking_id,
						'order_id'       => $booking->id,
						'customer_email' => $booking->customer_email ?? '',
					),
					/* translators: %s: order ID */
					'description'         => sprintf( __( 'Booking payment for order #%s', 'aiohm-booking-pro' ), $booking->id ),
				)
			);

			if ( 'requires_action' === $payment_intent->status ||
				'requires_source_action' === $payment_intent->status ) {
				// 3D Secure authentication required.
				wp_send_json_success(
					array(
						'requires_action'              => true,
						'payment_intent_client_secret' => $payment_intent->client_secret,
					)
				);
			} elseif ( 'succeeded' === $payment_intent->status ) {
				// Payment succeeded immediately.
				$this->update_booking_payment_status( $booking_id, $payment_intent->id, 'completed' );
				wp_send_json_success(
					array(
						'payment_intent_id' => $payment_intent->id,
						'redirect_url'      => home_url( '/booking-success' ),
					)
				);
			} else {
				wp_send_json_error( array( 'message' => __( 'Payment processing failed', 'aiohm-booking-pro' ) ) );
			}
		} catch ( ApiErrorException $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Payment processing error', 'aiohm-booking-pro' ) ) );
		}
	}

	/**
	 * Update booking payment status.
	 *
	 * @param int    $booking_id     Booking ID.
	 * @param string $transaction_id Transaction ID.
	 * @param string $status         Payment status.
	 * @return int|bool Number of rows updated or false on error.
	 */
	private function update_booking_payment_status( $booking_id, $transaction_id, $status ) {
		global $wpdb;
		$order_table = $wpdb->prefix . 'aiohm_booking_order';

		$result = $wpdb->update(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for payment status update
			$order_table,
			array(
				'payment_status' => $status,
				'payment_method' => 'stripe',
				'transaction_id' => $transaction_id,
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		if ( false !== $result ) {
			do_action( 'aiohm_booking_payment_completed', $booking_id, 'stripe', null );
		}

		return $result;
	}

	/**
	 * Create checkout session.
	 */
	public function create_checkout_session() {
		try {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

			if ( empty( $nonce ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'aiohm-booking-pro' ) ) );
				return;
			}

			$nonce_valid      = false;
			$possible_actions = array(
				'aiohm_booking_checkout',
				'aiohm_booking_frontend_nonce',
				'wp_rest',
				'aiohm_booking_nonce',
				'aiohm-booking-nonce',
			);

			foreach ( $possible_actions as $action ) {
				if ( wp_verify_nonce( $nonce, $action ) ) {
					$nonce_valid = true;
					break;
				}
			}

			if ( ! $nonce_valid ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page and try again.', 'aiohm-booking-pro' ) ) );
				return;
			}

			$booking_id = absint( $_POST['booking_id'] ?? 0 );
			if ( ! $booking_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid booking ID', 'aiohm-booking-pro' ) ) );
				return;
			}

			global $wpdb;
			$order_table = $wpdb->prefix . 'aiohm_booking_order';
			$booking     = $wpdb->get_row(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for payment record lookup
				$wpdb->prepare(
					'SELECT * FROM %i WHERE id = %d',
					$order_table,
					$booking_id
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

			if ( ! $booking ) {
				wp_send_json_error( array( 'message' => __( 'Booking not found', 'aiohm-booking-pro' ) ) );
				return;
			}

			if ( empty( $booking->total_amount ) || $booking->total_amount <= 0 ) {
				wp_send_json_error( array( 'message' => __( 'Invalid booking amount', 'aiohm-booking-pro' ) ) );
				return;
			}

			if ( empty( $booking->buyer_email ) ) {
				wp_send_json_error( array( 'message' => __( 'Customer email is required', 'aiohm-booking-pro' ) ) );
				return;
			}

			$session_data = $this->create_checkout_session_data( $booking );

			wp_send_json_success( $session_data );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => __( 'Payment processing error. Please try again.', 'aiohm-booking-pro' ) ) );
		}
	}

	public function create_checkout_session_data( $booking ) {
		if ( ! is_object( $booking ) ) {
			throw new Exception( esc_html__( 'Invalid booking data provided', 'aiohm-booking-pro' ) );
		}

		if ( empty( $booking->id ) ) {
			throw new Exception( esc_html__( 'Booking ID is required', 'aiohm-booking-pro' ) );
		}

		if ( empty( $booking->total_amount ) || $booking->total_amount <= 0 ) {
			throw new Exception( esc_html__( 'Invalid booking amount', 'aiohm-booking-pro' ) );
		}

		if ( empty( $booking->buyer_email ) ) {
			throw new Exception( esc_html__( 'Customer email is required', 'aiohm-booking-pro' ) );
		}

		$settings  = $this->get_settings();
		$test_mode = ! empty( $settings['stripe_test_mode'] );

		$secret_key = '';
		if ( ! empty( $settings['stripe_secret_key'] ) ) {
			$secret_key = $settings['stripe_secret_key'];
		} elseif ( $test_mode && ! empty( $settings['stripe_secret_key_test'] ) ) {
			$secret_key = $settings['stripe_secret_key_test'];
		} elseif ( ! $test_mode && ! empty( $settings['stripe_secret_key_live'] ) ) {
			$secret_key = $settings['stripe_secret_key_live'];
		}

		if ( empty( $secret_key ) ) {
			throw new Exception( esc_html__( 'Stripe secret key not configured. Please check your Stripe settings.', 'aiohm-booking-pro' ) );
		}

		$publishable_key = '';
		if ( ! empty( $settings['stripe_publishable_key'] ) ) {
			$publishable_key = $settings['stripe_publishable_key'];
		} elseif ( $test_mode && ! empty( $settings['stripe_publishable_key_test'] ) ) {
			$publishable_key = $settings['stripe_publishable_key_test'];
		} elseif ( ! $test_mode && ! empty( $settings['stripe_publishable_key_live'] ) ) {
			$publishable_key = $settings['stripe_publishable_key_live'];
		}
		$line_items = array(
			array(
				'price_data' => array(
					'currency'     => strtolower( $booking->currency ?? 'usd' ),
					'product_data' => array(
						/* translators: %s: booking ID number */
						'name' => sprintf( __( 'Booking #%s', 'aiohm-booking-pro' ), $booking->id ),
					),
					'unit_amount'  => intval( $booking->total_amount * 100 ),
				),
				'quantity'   => 1,
			),
		);

		$checkout_session_args = array(
			'payment_method_types' => array( 'card' ),
			'line_items'           => $line_items,
			'mode'                 => 'payment',
			'success_url'          => home_url( '/booking-success?session_id={CHECKOUT_SESSION_ID}' ),
			'cancel_url'           => home_url( '/booking-cancelled' ),
			'client_reference_id'  => $booking->id,
			'customer_email'       => $booking->buyer_email,
			'metadata'             => array(
				'booking_id' => $booking->id,
				'order_id'   => $booking->id,
			),
		);

		$response = wp_remote_post(
			'https://api.stripe.com/v1/checkout/sessions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret_key,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => http_build_query( $checkout_session_args ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Stripe API Error: ' . esc_html( $response->get_error_message() ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['error'] ) ) {
			throw new Exception( 'Stripe Error: ' . esc_html( $body['error']['message'] ) );
		}

		if ( empty( $body['url'] ) ) {
			throw new Exception( 'Could not create Stripe checkout session.' );
		}

		return array(
			'session_id'      => $body['id'],
			'url'             => $body['url'],
			'publishable_key' => $publishable_key,
		);
	}

	/**
	 * Handle webhook.
	 */
	public function handle_webhook() {
		try {
			// Security: Only allow POST requests for webhooks.
			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				http_response_code( 405 );
				echo wp_json_encode( array( 'error' => 'Method not allowed' ) );
				wp_die();
			}

			// Rate limiting: Prevent abuse.
			$this->check_rate_limit();

			$payload    = file_get_contents( 'php://input' );
			$sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ) : '';

			// Basic validation: ensure payload is not empty.
			if ( empty( $payload ) ) {
				http_response_code( 400 );
				echo wp_json_encode( array( 'error' => 'Empty payload' ) );
				wp_die();
			}

			$result = $this->process_webhook( $payload, $sig_header );

			if ( $result ) {
				http_response_code( 200 );
				echo wp_json_encode( array( 'success' => true ) );
			} else {
				http_response_code( 400 );
				echo wp_json_encode( array( 'error' => 'Webhook processing failed' ) );
			}
		} catch ( Exception $e ) {

			http_response_code( 500 );
			echo wp_json_encode( array( 'error' => 'Internal server error' ) );
		}

		wp_die();
	}

	/**
	 * Get the payment gateway identifier
	 *
	 * @return string
	 */
	protected function get_gateway_id() {
		return 'stripe';
	}

	/**
	 * Get active credentials for Stripe
	 *
	 * @return array
	 */
	protected function get_active_credentials() {
		$settings  = $this->get_settings();
		$test_mode = ! empty( $settings['stripe_test_mode'] );

		// Check if using test/live mode fields or simple fields.
		if ( isset( $settings['stripe_secret_key_test'] ) || isset( $settings['stripe_secret_key_live'] ) ) {
			// Use test/live mode fields.
			$secret_key = $test_mode
				? ( $settings['stripe_secret_key_test'] ?? '' )
				: ( $settings['stripe_secret_key_live'] ?? '' );
			$public_key = $test_mode
				? ( $settings['stripe_public_key_test'] ?? '' )
				: ( $settings['stripe_public_key_live'] ?? '' );
		} else {
			// Fall back to simple field names for MVP compatibility.
			$secret_key = $settings['stripe_secret_key'] ?? '';
			$public_key = $settings['stripe_public_key'] ?? '';
		}

		return array(
			'secret_key' => $secret_key,
			'public_key' => $public_key,
			'test_mode'  => $test_mode,
		);
	}

	public function process_webhook( $payload, $sig_header ) {
		try {
			$settings       = $this->get_settings();
			$webhook_secret = $settings['stripe_webhook_secret'] ?? '';

			if ( empty( $webhook_secret ) ) {
				$event = json_decode( $payload );
			} elseif ( ! class_exists( 'StripeClient' ) ) {
					$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					$event = json_decode( $payload );
				} else {
					require_once $stripe_init_file;
					$event = Webhook::constructEvent( $payload, $sig_header, $webhook_secret );
				}
			} else {
				$event = Webhook::constructEvent( $payload, $sig_header, $webhook_secret );
			}

			if ( ! $event ) {
				return false;
			}

			switch ( $event->type ) {
				case 'checkout.session.completed':
					$session = $event->data->object;

					$order_id          = absint( $session->client_reference_id ?? 0 );
					$payment_intent_id = sanitize_text_field( $session->payment_intent ?? '' );

					if ( $order_id > 0 ) {
						global $wpdb;
						$table = $wpdb->prefix . 'aiohm_booking_order';

						$order = $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for webhook processing with custom table
							$wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table ) . ' WHERE id = %d AND payment_status = %s', $order_id, 'pending' )
						);

						if ( $order ) {
							$wpdb->update(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for payment record update
								$table,
								array(
									'payment_status' => 'completed',
									'payment_method' => 'stripe',
									'transaction_id' => $payment_intent_id,
									'updated_at'     => current_time( 'mysql' ),
								),
								array( 'id' => $order_id )
							); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

							do_action( 'aiohm_booking_payment_completed', $order_id, 'stripe', $session );
						}
					}
					return true;

				case 'payment_intent.succeeded':
					return true;

				case 'payment_intent.payment_failed':
					return true;

				default:
					return true;
			}
		} catch ( Exception $e ) {
			return false;
		}
	}


	/**
	 * Enqueue Stripe assets.
	 */
	public function enqueue_stripe_assets() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! is_page() && ! is_single() ) {
			return;
		}

		$settings  = $this->get_settings();
		$test_mode = ! empty( $settings['stripe_test_mode'] );

		$publishable_key = '';
		if ( ! empty( $settings['stripe_publishable_key'] ) ) {
			$publishable_key = $settings['stripe_publishable_key'];
		} elseif ( $test_mode && ! empty( $settings['stripe_publishable_key_test'] ) ) {
			$publishable_key = $settings['stripe_publishable_key_test'];
		} elseif ( ! $test_mode && ! empty( $settings['stripe_publishable_key_live'] ) ) {
			$publishable_key = $settings['stripe_publishable_key_live'];
		}

		if ( empty( $publishable_key ) ) {
			return;
		}

		wp_enqueue_script(
			'stripe-js',
			'https://js.stripe.com/v3/',
			array(),
			'3.0',
			true
		);

		wp_enqueue_script(
			'aiohm-booking-stripe-frontend',
			AIOHM_BOOKING_URL . 'includes/modules/payments/stripe/assets/js/aiohm-booking-stripe-frontend.js',
			array( 'jquery', 'stripe-js' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Also enqueue checkout-specific script
		wp_enqueue_script(
			'aiohm-booking-stripe-checkout',
			AIOHM_BOOKING_URL . 'includes/modules/payments/stripe/assets/js/aiohm-booking-stripe-checkout.js',
			array( 'jquery', 'aiohm-booking-stripe-frontend' ),
			AIOHM_BOOKING_VERSION,
			true
		);
		wp_localize_script(
			'aiohm-booking-stripe-frontend',
			'aiohm_booking_stripe',
			array(
				'publishable_key' => $publishable_key,
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'aiohm_booking_payment_nonce' ),
				'messages'        => array(
					'processing' => __( 'Processing payment...', 'aiohm-booking-pro' ),
					'error'      => __( 'Payment failed. Please try again.', 'aiohm-booking-pro' ),
					'success'    => __( 'Payment successful!', 'aiohm-booking-pro' ),
				),
			)
		);
	}

	public function test_stripe_connection() {
		try {
			if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'admin_nonce', 'manage_options' ) ) {
				return;
			}

			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					wp_send_json_error( array( 'message' => __( 'Stripe PHP SDK not found', 'aiohm-booking-pro' ) ) );
					return;
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			// Check if using test/live mode fields or simple fields.
			if ( isset( $settings['stripe_secret_key_test'] ) || isset( $settings['stripe_secret_key_live'] ) ) {
				// Use test/live mode fields.
				$secret_key = $test_mode
					? ( $settings['stripe_secret_key_test'] ?? '' )
					: ( $settings['stripe_secret_key_live'] ?? '' );
			} else {
				// Fall back to simple field names for MVP compatibility.
				$secret_key = $settings['stripe_secret_key'] ?? '';
			}

			if ( empty( $secret_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) ) );
				return;
			}

			// Test the connection by making a simple API call.
			Stripe::setApiKey( $secret_key );

			try {
				// Try to retrieve account information.
				$account = Account::retrieve();

				wp_send_json_success(
					array(
						'message'           => __( 'Stripe connection successful', 'aiohm-booking-pro' ),
						'account_id'        => $account->id,
						'test_mode'         => $test_mode,
						'charges_enabled'   => $account->charges_enabled,
						'details_submitted' => $account->details_submitted,
					)
				);

			} catch ( ApiErrorException $e ) {
				wp_send_json_error(
					array(
						/* translators: %s: error message */
						'message'    => sprintf( __( 'Stripe API Error: %s', 'aiohm-booking-pro' ), $e->getMessage() ),
						'error_type' => get_class( $e ),
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					/* translators: %s: error message */
					'message' => sprintf( __( 'Connection test failed: %s', 'aiohm-booking-pro' ), $e->getMessage() ),
				)
			);
		}
	}

	/**
	 * Create payment intent.
	 *
	 * @param array $order_data Order data.
	 * @return array|WP_Error Payment intent data or error.
	 */
	public function create_payment_intent( $order_data ) {
		try {
			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					return new WP_Error( 'stripe_sdk_missing', __( 'Stripe PHP SDK not found. Please install the Stripe SDK by running: composer require stripe/stripe-php', 'aiohm-booking-pro' ) );
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			$secret_key = $test_mode
				? $settings['stripe_secret_key_test']
				: $settings['stripe_secret_key_live'];

			if ( empty( $secret_key ) ) {
				return new WP_Error( 'stripe_config_error', __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			}

			Stripe::setApiKey( $secret_key );

			// Create payment intent.
			$intent_config = array(
				'amount'               => intval( $order_data['amount'] * 100 ), // Convert to cents.
				'currency'             => strtolower( $order_data['currency'] ?? 'usd' ),
				'payment_method_types' => $settings['stripe_payment_methods'] ?? array( 'card' ),
				'capture_method'       => $settings['stripe_capture_method'] ?? 'automatic',
				'metadata'             => array(
					'order_id'       => $order_data['order_id'] ?? '',
					'booking_id'     => $order_data['booking_id'] ?? '',
					'customer_email' => $order_data['customer_email'] ?? '',
				),
				/* translators: %s: order ID */
				'description'          => sprintf( __( 'Booking payment for order #%s', 'aiohm-booking-pro' ), $order_data['order_id'] ?? '' ),
			);

			// Add customer information if available.
			if ( ! empty( $order_data['customer_email'] ) ) {
				$intent_config['receipt_email'] = $order_data['customer_email'];
			}

			$payment_intent = PaymentIntent::create( $intent_config );

			return array(
				'success'           => true,
				'payment_intent_id' => $payment_intent->id,
				'client_secret'     => $payment_intent->client_secret,
				'amount'            => $payment_intent->amount,
				'currency'          => $payment_intent->currency,
				'status'            => $payment_intent->status,
			);

		} catch ( ApiErrorException $e ) {
			return new WP_Error( 'stripe_api_error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', __( 'Payment intent creation failed', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Capture payment.
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @param float  $amount            Amount to capture.
	 * @return array|WP_Error Capture data or error.
	 */
	public function capture_payment( $payment_intent_id, $amount = null ) {
		try {
			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					return new WP_Error( 'stripe_sdk_missing', __( 'Stripe PHP SDK not found. Please install the Stripe SDK by running: composer require stripe/stripe-php', 'aiohm-booking-pro' ) );
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			$secret_key = $test_mode
				? $settings['stripe_secret_key_test']
				: $settings['stripe_secret_key_live'];

			if ( empty( $secret_key ) ) {
				return new WP_Error( 'stripe_config_error', __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			}

			Stripe::setApiKey( $secret_key );

			// Retrieve the payment intent.
			$payment_intent = PaymentIntent::retrieve( $payment_intent_id );

			if ( 'requires_capture' !== $payment_intent->status ) {
				return new WP_Error( 'stripe_capture_error', __( 'Payment intent is not in a capturable state', 'aiohm-booking-pro' ) );
			}

			// Capture the payment.
			$capture_params = array();
			if ( null !== $amount ) {
				$capture_params['amount_to_capture'] = intval( $amount * 100 );
			}

			$captured_payment = $payment_intent->capture( $capture_params );

			return array(
				'success'           => true,
				'payment_intent_id' => $captured_payment->id,
				'amount_captured'   => $captured_payment->amount_captured,
				'currency'          => $captured_payment->currency,
				'status'            => $captured_payment->status,
			);

		} catch ( ApiErrorException $e ) {
			return new WP_Error( 'stripe_api_error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', __( 'Payment capture failed', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Refund payment.
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @param float  $amount            Amount to refund.
	 * @param string $reason            Refund reason.
	 * @return array|WP_Error Refund data or error.
	 */
	public function refund_payment( $payment_intent_id, $amount = null, $reason = 'requested_by_customer' ) {
		try {
			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					return new WP_Error( 'stripe_sdk_missing', __( 'Stripe PHP SDK not found. Please install the Stripe SDK by running: composer require stripe/stripe-php', 'aiohm-booking-pro' ) );
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			$secret_key = $test_mode
				? $settings['stripe_secret_key_test']
				: $settings['stripe_secret_key_live'];

			if ( empty( $secret_key ) ) {
				return new WP_Error( 'stripe_config_error', __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			}

			Stripe::setApiKey( $secret_key );

			// Retrieve the payment intent to get the charge ID.
			$payment_intent = PaymentIntent::retrieve( $payment_intent_id );

			if ( empty( $payment_intent->charges->data ) ) {
				return new WP_Error( 'stripe_refund_error', __( 'No charges found for this payment intent', 'aiohm-booking-pro' ) );
			}

			$charge_id = $payment_intent->charges->data[0]->id;

			// Create refund.
			$refund_params = array(
				'charge' => $charge_id,
				'reason' => $reason,
			);

			if ( null !== $amount ) {
				$refund_params['amount'] = intval( $amount * 100 );
			}

			$refund = Refund::create( $refund_params );

			return array(
				'success'   => true,
				'refund_id' => $refund->id,
				'amount'    => $refund->amount,
				'currency'  => $refund->currency,
				'status'    => $refund->status,
			);

		} catch ( ApiErrorException $e ) {
			return new WP_Error( 'stripe_api_error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', __( 'Refund failed', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Get payment status.
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @return array|WP_Error Payment status data or error.
	 */
	public function get_payment_status( $payment_intent_id ) {
		try {
			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					return new WP_Error( 'stripe_sdk_missing', __( 'Stripe PHP SDK not found. Please install the Stripe SDK by running: composer require stripe/stripe-php', 'aiohm-booking-pro' ) );
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			$secret_key = $test_mode
				? $settings['stripe_secret_key_test']
				: $settings['stripe_secret_key_live'];

			if ( empty( $secret_key ) ) {
				return new WP_Error( 'stripe_config_error', __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			}

			Stripe::setApiKey( $secret_key );

			// Retrieve payment intent.
			$payment_intent = PaymentIntent::retrieve( $payment_intent_id );

			return array(
				'success'         => true,
				'status'          => $payment_intent->status,
				'amount'          => $payment_intent->amount,
				'amount_captured' => $payment_intent->amount_captured,
				'amount_refunded' => $payment_intent->amount_refunded,
				'currency'        => $payment_intent->currency,
				'charges'         => count( $payment_intent->charges->data ),
			);

		} catch ( ApiErrorException $e ) {
			return new WP_Error( 'stripe_api_error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', __( 'Failed to retrieve payment status', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Create customer.
	 *
	 * @param array $customer_data Customer data.
	 * @return array|WP_Error Customer data or error.
	 */
	public function create_customer( $customer_data ) {
		try {
			// Load Stripe PHP SDK.
			if ( ! class_exists( 'StripeClient' ) ) {
				$stripe_init_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/vendor/autoload.php';
				if ( ! file_exists( $stripe_init_file ) ) {
					return new WP_Error( 'stripe_sdk_missing', __( 'Stripe PHP SDK not found. Please install the Stripe SDK by running: composer require stripe/stripe-php', 'aiohm-booking-pro' ) );
				}
				require_once $stripe_init_file;
			}

			$settings  = $this->get_settings();
			$test_mode = ! empty( $settings['stripe_test_mode'] );

			$secret_key = $test_mode
				? $settings['stripe_secret_key_test']
				: $settings['stripe_secret_key_live'];

			if ( empty( $secret_key ) ) {
				return new WP_Error( 'stripe_config_error', __( 'Stripe secret key not configured', 'aiohm-booking-pro' ) );
			}

			Stripe::setApiKey( $secret_key );

			// Create customer.
			$customer_params = array(
				'email'    => $customer_data['email'] ?? '',
				'name'     => $customer_data['name'] ?? '',
				'metadata' => array(
					'user_id' => $customer_data['user_id'] ?? '',
					'source'  => 'aiohm-booking-pro',
				),
			);

			// Add optional fields.
			if ( ! empty( $customer_data['phone'] ) ) {
				$customer_params['phone'] = $customer_data['phone'];
			}

			if ( ! empty( $customer_data['address'] ) ) {
				$customer_params['address'] = $customer_data['address'];
			}

			$customer = Customer::create( $customer_params );

			return array(
				'success'     => true,
				'customer_id' => $customer->id,
				'email'       => $customer->email,
				'name'        => $customer->name,
			);

		} catch ( ApiErrorException $e ) {
			return new WP_Error( 'stripe_api_error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'stripe_error', __( 'Customer creation failed', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * Get publishable key.
	 *
	 * @return string Publishable key.
	 */
	public function get_publishable_key() {
		$settings  = $this->get_settings();
		$test_mode = ! empty( $settings['stripe_test_mode'] );

		return $test_mode
			? $settings['stripe_publishable_key_test']
			: $settings['stripe_publishable_key_live'];
	}

	/**
	 * Check if test mode.
	 *
	 * @return bool True if test mode.
	 */
	public function is_test_mode() {
		$settings = $this->get_settings();
		return ! empty( $settings['stripe_test_mode'] );
	}

	/**
	 * Get supported currencies.
	 *
	 * @return array Supported currencies.
	 */
	public function get_supported_currencies() {
		return array(
			'usd' => __( 'US Dollar', 'aiohm-booking-pro' ),
			'eur' => __( 'Euro', 'aiohm-booking-pro' ),
			'gbp' => __( 'British Pound', 'aiohm-booking-pro' ),
			'cad' => __( 'Canadian Dollar', 'aiohm-booking-pro' ),
			'aud' => __( 'Australian Dollar', 'aiohm-booking-pro' ),
			'jpy' => __( 'Japanese Yen', 'aiohm-booking-pro' ),
			'chf' => __( 'Swiss Franc', 'aiohm-booking-pro' ),
			'sek' => __( 'Swedish Krona', 'aiohm-booking-pro' ),
			'nok' => __( 'Norwegian Krone', 'aiohm-booking-pro' ),
			'dkk' => __( 'Danish Krone', 'aiohm-booking-pro' ),
			'pln' => __( 'Polish Złoty', 'aiohm-booking-pro' ),
			'czk' => __( 'Czech Koruna', 'aiohm-booking-pro' ),
			'huf' => __( 'Hungarian Forint', 'aiohm-booking-pro' ),
		);
	}

	/**
	 * Validate credentials.
	 *
	 * @return bool True if valid.
	 */
	public function validate_credentials() {
		$settings  = $this->get_settings();
		$test_mode = ! empty( $settings['stripe_test_mode'] );

		$publishable_key = $test_mode
			? $settings['stripe_publishable_key_test']
			: $settings['stripe_publishable_key_live'];

		$secret_key = $test_mode
			? $settings['stripe_secret_key_test']
			: $settings['stripe_secret_key_live'];

		if ( empty( $publishable_key ) || empty( $secret_key ) ) {
			return false;
		}

		// Basic validation - check if keys start with correct prefixes.
		$expected_pub_prefix = $test_mode ? 'pk_test_' : 'pk_live_';
		$expected_sec_prefix = $test_mode ? 'sk_test_' : 'sk_live_';

		return strpos( $publishable_key, $expected_pub_prefix ) === 0 &&
				strpos( $secret_key, $expected_sec_prefix ) === 0;
	}

	/**
	 * Register payment method.
	 *
	 * @param array $methods Payment methods.
	 * @return array Payment methods.
	 */
	public function register_payment_method( $methods ) {
		$methods['stripe'] = array(
			'id'          => 'stripe',
			'title'       => __( 'Stripe', 'aiohm-booking-pro' ),
			'description' => __( 'Pay with credit card', 'aiohm-booking-pro' ),
			'icon'        => AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-stripe.png',
			'enabled'     => $this->is_enabled(),
		);

		return $methods;
	}

	/**
	 * Get payment form HTML for checkout.
	 *
	 * @param int $booking_id Booking ID.
	 * @return string Payment form HTML.
	 */
	public function get_payment_form_html( $booking_id ) {
		// Check if user can access premium features
		if ( ! $this->can_use_premium() ) {
			return '<div class="payment-error">' . esc_html__( 'Stripe payments require AIOHM Booking PRO.', 'aiohm-booking-pro' ) . '</div>';
		}

		// Get booking data
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';
		$booking = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		if ( ! $booking ) {
			return '<p>' . esc_html__( 'Error: Booking not found.', 'aiohm-booking-pro' ) . '</p>';
		}

		$settings  = $this->get_settings();
		$test_mode = ! empty( $settings['stripe_test_mode'] );

		// Get publishable key
		$publishable_key = '';
		if ( ! empty( $settings['stripe_publishable_key'] ) ) {
			// MVP format - single key
			$publishable_key = $settings['stripe_publishable_key'];
		} elseif ( $test_mode && ! empty( $settings['stripe_publishable_key_test'] ) ) {
			// Modular format - test key
			$publishable_key = $settings['stripe_publishable_key_test'];
		} elseif ( ! $test_mode && ! empty( $settings['stripe_publishable_key_live'] ) ) {
			// Modular format - live key
			$publishable_key = $settings['stripe_publishable_key_live'];
		}

		if ( empty( $publishable_key ) ) {
			return '<div class="payment-error">' . esc_html__( 'Stripe publishable key not configured.', 'aiohm-booking-pro' ) . '</div>';
		}

		$currency       = $booking['currency'] ?? 'USD';
		$total_amount   = floatval( $booking['total_amount'] );
		$deposit_amount = floatval( $booking['deposit_amount'] );
		$amount_to_pay  = $deposit_amount > 0 ? $deposit_amount : $total_amount;

		// Enqueue Stripe.js
		wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), '3', true );

		// Localize script with booking data
		wp_localize_script( 'aiohm-booking-checkout', 'aiohm_stripe_data', array(
			'publishable_key' => $publishable_key,
			'booking_id'      => $booking_id,
			'amount'          => $amount_to_pay,
			'currency'        => strtolower( $currency ),
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'aiohm_booking_stripe_nonce' ),
		) );

		ob_start();
		?>
		<div class="aiohm-stripe-payment-container">
			<h4><?php esc_html_e( 'Pay with Credit Card', 'aiohm-booking-pro' ); ?></h4>
			
			<div class="aiohm-payment-summary">
				<p><strong><?php esc_html_e( 'Amount to Pay:', 'aiohm-booking-pro' ); ?></strong> 
					<?php echo esc_html( $currency . ' ' . number_format( $amount_to_pay, 2 ) ); ?>
				</p>
				<?php if ( $deposit_amount > 0 ) : ?>
					<p class="aiohm-deposit-note">
						<?php esc_html_e( 'This is a deposit to secure your booking. The remaining balance will be due as specified in your booking terms.', 'aiohm-booking-pro' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div id="aiohm-stripe-payment-element">
				<!-- Stripe Elements will be inserted here -->
			</div>

			<div id="aiohm-stripe-payment-errors" class="aiohm-payment-errors" style="display: none;"></div>

			<button type="button" id="aiohm-stripe-payment-button" class="aiohm-payment-button aiohm-stripe-button">
				<?php 
				/* translators: %s: amount to pay */
				printf( esc_html__( 'Pay %s', 'aiohm-booking-pro' ), esc_html( $currency . ' ' . number_format( $amount_to_pay, 2 ) ) ); 
				?>
			</button>

			<div id="aiohm-stripe-payment-processing" class="aiohm-payment-processing" style="display: none;">
				<?php esc_html_e( 'Processing payment...', 'aiohm-booking-pro' ); ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			if (typeof aiohm_stripe_data !== 'undefined') {
				var stripe = Stripe(aiohm_stripe_data.publishable_key);
				var elements = stripe.elements();
				
				var style = {
					base: {
						fontSize: '16px',
						color: '#32325d',
						fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
						fontSmoothing: 'antialiased',
						'::placeholder': {
							color: '#aab7c4'
						}
					}
				};
				
				var card = elements.create('card', {style: style});
				card.mount('#aiohm-stripe-payment-element');
				
				var $button = $('#aiohm-stripe-payment-button');
				var $errors = $('#aiohm-stripe-payment-errors');
				var $processing = $('#aiohm-stripe-payment-processing');
				
				$button.on('click', function(e) {
					e.preventDefault();
					
					$button.prop('disabled', true);
					$errors.hide().text('');
					$processing.show();
					
					stripe.createPaymentMethod({
						type: 'card',
						card: card
					}).then(function(result) {
						if (result.error) {
							$errors.text(result.error.message).show();
							$button.prop('disabled', false);
							$processing.hide();
						} else {
							// Send payment method to server
							$.ajax({
								url: aiohm_stripe_data.ajax_url,
								type: 'POST',
								data: {
									action: 'aiohm_booking_stripe_process_payment',
									booking_id: aiohm_stripe_data.booking_id,
									payment_method_id: result.paymentMethod.id,
									amount: aiohm_stripe_data.amount,
									currency: aiohm_stripe_data.currency,
									nonce: aiohm_stripe_data.nonce
								},
								success: function(response) {
									if (response.success) {
										if (response.data.requires_action) {
											// Handle 3D Secure
											stripe.confirmCardPayment(
												response.data.payment_intent_client_secret
											).then(function(result) {
												if (result.error) {
													$errors.text(result.error.message).show();
													$button.prop('disabled', false);
													$processing.hide();
												} else {
													window.location.href = response.data.redirect_url || '<?php echo esc_url( home_url( '/booking-success' ) ); ?>';
												}
											});
										} else {
											window.location.href = response.data.redirect_url || '<?php echo esc_url( home_url( '/booking-success' ) ); ?>';
										}
									} else {
										$errors.text(response.data.message || '<?php esc_html_e( 'Payment failed', 'aiohm-booking-pro' ); ?>').show();
										$button.prop('disabled', false);
										$processing.hide();
									}
								},
								error: function() {
									$errors.text('<?php esc_html_e( 'Network error. Please try again.', 'aiohm-booking-pro' ); ?>').show();
									$button.prop('disabled', false);
									$processing.hide();
								}
							});
						}
					});
				});
			}
		});
		</script>
		<?php
		return ob_get_clean();
	}
}


/* </fs_premium_only> */
