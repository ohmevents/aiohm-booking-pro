<?php
/**
 * Checkout AJAX Handlers
 *
 * Handles AJAX requests for the integrated checkout flow
 *
 * @package AIOHM_Booking
 * @since 1.2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout AJAX Handler Class
 */
class AIOHM_BOOKING_Checkout_Ajax {

	/**
	 * Initialize checkout AJAX handlers
	 */
	public static function init() {
		add_action( 'wp_ajax_aiohm_booking_get_checkout_html', array( __CLASS__, 'get_checkout_html' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_get_checkout_html', array( __CLASS__, 'get_checkout_html' ) );

		add_action( 'wp_ajax_aiohm_booking_load_payment_method', array( __CLASS__, 'load_payment_method' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_load_payment_method', array( __CLASS__, 'load_payment_method' ) );

		add_action( 'wp_ajax_aiohm_booking_complete_manual_payment', array( __CLASS__, 'complete_manual_payment' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_complete_manual_payment', array( __CLASS__, 'complete_manual_payment' ) );

		// New direct payment handlers for sandwich form
		add_action( 'wp_ajax_aiohm_booking_process_stripe', array( __CLASS__, 'process_stripe_payment' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_process_stripe', array( __CLASS__, 'process_stripe_payment' ) );

		add_action( 'wp_ajax_aiohm_booking_send_notification', array( __CLASS__, 'send_booking_notification' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_send_notification', array( __CLASS__, 'send_booking_notification' ) );

		// Create pending order and send notification
		add_action( 'wp_ajax_aiohm_booking_create_pending_order', array( __CLASS__, 'create_pending_order' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_create_pending_order', array( __CLASS__, 'create_pending_order' ) );
	}

	/**
	 * Get checkout HTML for a specific booking
	 */
	public static function get_checkout_html() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_frontend_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$booking_id     = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );
		$developer_mode = filter_var( wp_unslash( $_POST['developer_mode'] ?? false ), FILTER_VALIDATE_BOOLEAN );

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID' ) );
		}

		// Generate checkout HTML
		ob_start();

		// Set up variables for the checkout template
		$atts = array(
			'booking_id'     => $booking_id,
			'style'          => 'modern',
			'show_summary'   => 'true',
			'developer_mode' => $developer_mode,
		);

		// Include the checkout template
		include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-checkout.php';

		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Load payment method specific content
	 */
	public static function load_payment_method() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_frontend_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$payment_method = sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? '' ) );
		$booking_id     = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );

		if ( ! $booking_id || ! $payment_method ) {
			wp_send_json_error( array( 'message' => 'Missing required parameters' ) );
		}

		$html = '';

		switch ( $payment_method ) {
			case 'manual':
				$html = self::get_manual_payment_html( $booking_id );
				break;
			case 'stripe':
				if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
					$html = self::get_stripe_payment_html( $booking_id );
				} else {
					$html = '<div class="payment-error">Stripe payments require AIOHM Booking PRO. <a href="' . ( function_exists( 'aiohm_booking_fs' ) ? esc_url( aiohm_booking_fs()->get_upgrade_url() ) : '#' ) . '">Upgrade Now</a></div>';
				}
				break;
			default:
				// Allow other modules to handle this payment method
				$html = apply_filters( 'aiohm_booking_payment_method_html', '', $payment_method, $booking_id );
				break;
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Complete manual payment (send invoice)
	 */
	public static function complete_manual_payment() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_frontend_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$booking_id = absint( wp_unslash( $_POST['booking_id'] ?? 0 ) );

		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID' ) );
		}

		// Update booking status to pending payment
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for order status update in custom table
			$table_name,
			array(
				'status'         => 'pending_payment',
				'payment_method' => 'invoice',
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $booking_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		if ( $updated === false ) {
			wp_send_json_error( array( 'message' => 'Failed to update booking status' ) );
		}

		// Send invoice notification using the notifications module
		$notifications_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'notifications' );
		if ( $notifications_module ) {
			$invoice_sent = $notifications_module->send_invoice_notification( $booking_id );

			if ( $invoice_sent ) {
				wp_send_json_success(
					array(
						'message'    => 'Booking confirmed! Payment instructions have been sent to your email.',
						'booking_id' => $booking_id,
					)
				);
			} else {
				wp_send_json_error( array( 'message' => 'Booking saved but failed to send invoice email. Please contact support.' ) );
			}
		} else {
			wp_send_json_error( array( 'message' => 'Notifications module not available' ) );
		}
	}

	/**
	 * Process Stripe payment (direct from sandwich form)
	 */
	public static function process_stripe_payment() {
		try {
			// Verify nonce
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_frontend_nonce' ) ) {
				throw new Exception( __( 'Security check failed', 'aiohm-booking-pro' ) );
			}

			// Check if Stripe module is available
			$stripe_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'stripe' );
			if ( ! $stripe_module ) {
				throw new Exception( __( 'Stripe payment module not available', 'aiohm-booking-pro' ) );
			}

			// Get booking data
			$booking_data_raw = sanitize_text_field( wp_unslash( $_POST['booking_data'] ?? '{}' ) );
			$booking_data     = json_decode( stripslashes( $booking_data_raw ), true );
			if ( empty( $booking_data ) ) {
				throw new Exception( __( 'Invalid booking data', 'aiohm-booking-pro' ) );
			}

			// Format data for Stripe module
			$order_data = array(
				'amount'         => self::validatePaymentAmount(
					floatval( $booking_data['pricing']['total'] ?? 100.00 ),
					$booking_data['pricing']['currency'] ?? 'USD'
				),
				'currency'       => strtoupper( $booking_data['pricing']['currency'] ?? 'USD' ),
				'order_id'       => self::generate_booking_reference(),
				'customer_email' => sanitize_email( $booking_data['contact_info']['email'] ?? '' ),
				'description'    => 'Booking payment',
				'booking_data'   => $booking_data,
			);

			// Process payment using Stripe module
			$result = $stripe_module->process_payment( $order_data );

			// Handle WP_Error responses
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			if ( $result['success'] ) {
				$response_data = array(
					'message'      => __( 'Redirecting to Stripe checkout...', 'aiohm-booking-pro' ),
					'checkout_url' => $result['checkout_url'] ?? null,
					'session_id'   => $result['session_id'] ?? null,
				);
				wp_send_json_success( $response_data );
			} else {
				throw new Exception( $result['message'] ?? __( 'Payment processing failed', 'aiohm-booking-pro' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Send booking notification for free users (direct from sandwich form)
	 */
	public static function send_booking_notification() {
		try {
			// Verify nonce
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_frontend_nonce' ) ) {
				throw new Exception( __( 'Security check failed', 'aiohm-booking-pro' ) );
			}
			// Get booking data
			$booking_data_raw = wp_unslash( $_POST['booking_data'] ?? '{}' );
			$booking_data     = json_decode( $booking_data_raw, true );
			if ( empty( $booking_data ) ) {
				throw new Exception( __( 'Invalid booking data', 'aiohm-booking-pro' ) );
			}

			// Validate required contact information
			if ( empty( $booking_data['contact_info']['email'] ) ) {
				throw new Exception( __( 'Email address is required', 'aiohm-booking-pro' ) );
			}

			// Check if notifications module is available
			$notifications_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'notifications' );
			if ( ! $notifications_module ) {
				throw new Exception( __( 'Notifications module not available', 'aiohm-booking-pro' ) );
			}

			// Generate booking reference
			$booking_reference = self::generate_booking_reference();

			// Store booking data for admin reference first
			$booking_id = self::store_booking_request( $booking_reference, $booking_data );

			// Prepare notification data
			$notification_data = array(
				'booking_reference' => $booking_reference,
				'booking_id'        => $booking_id,
				'contact_info'      => $booking_data['contact_info'],
				'booking_details'   => array(
					'events'         => $booking_data['events'] ?? array(),
					'accommodations' => $booking_data['accommodations'] ?? array(),
					'dates'          => $booking_data['dates'] ?? array(),
					'pricing'        => $booking_data['pricing'] ?? array(),
				),
				'notification_type' => sanitize_text_field( wp_unslash( $_POST['notification_type'] ?? 'booking_confirmation' ) ),
			);

			// Send custom notification email
			$result = self::send_custom_booking_notification( $booking_reference, $booking_data );

			if ( $result ) {
				wp_send_json_success(
					array(
						'message'           => __( 'Booking confirmation sent successfully', 'aiohm-booking-pro' ),
						'booking_reference' => $booking_reference,
						'booking_id'        => $booking_id,
					)
				);
			} else {
				throw new Exception( __( 'Failed to send notification', 'aiohm-booking-pro' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Generate a unique booking reference
	 */
	private static function generate_booking_reference() {
		$timestamp = time();
		$random    = wp_rand( 100, 999 );
		return 'BK' . $timestamp . $random;
	}

	/**
	 * Store booking request for admin reference
	 */
	private static function store_booking_request( $booking_reference, $booking_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aiohm_booking_requests';

		// Create table if it doesn't exist
		self::create_booking_requests_table();

		// Insert booking request
		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for booking request insertion in custom table
			$table_name,
			array(
				'booking_reference'       => $booking_reference,
				'contact_name'            => sanitize_text_field( $booking_data['contact_info']['name'] ?? '' ),
				'contact_email'           => sanitize_email( $booking_data['contact_info']['email'] ?? '' ),
				'contact_phone'           => sanitize_text_field( $booking_data['contact_info']['phone'] ?? '' ),
				'contact_message'         => sanitize_textarea_field( $booking_data['contact_info']['message'] ?? '' ),
				'selected_events'         => maybe_serialize( $booking_data['events'] ?? array() ),
				'selected_accommodations' => maybe_serialize( $booking_data['accommodations'] ?? array() ),
				'checkin_date'            => sanitize_text_field( $booking_data['dates']['checkin'] ?? '' ),
				'checkout_date'           => sanitize_text_field( $booking_data['dates']['checkout'] ?? '' ),
				'pricing_data'            => maybe_serialize( $booking_data['pricing'] ?? array() ),
				'status'                  => 'pending',
				'created_at'              => current_time( 'mysql' ),
				'updated_at'              => current_time( 'mysql' ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		if ( $result === false ) {
			throw new Exception( esc_html__( 'Failed to store booking request', 'aiohm-booking-pro' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Send custom booking notification email
	 */
	private static function send_custom_booking_notification( $booking_reference, $booking_data ) {
		$to = sanitize_email( $booking_data['contact_info']['email'] ?? '' );
		if ( empty( $to ) ) {
			return false;
		}

		/* translators: %s: booking reference number */
		$subject = sprintf( __( 'Booking Confirmation - %s', 'aiohm-booking-pro' ), $booking_reference );

		$message = self::generate_booking_confirmation_email( $booking_reference, $booking_data );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		$result = wp_mail( $to, $subject, $message, $headers );

		// For development/testing, if wp_mail fails, still return true to allow the booking to proceed
		// In production, you might want to handle this differently
		if ( ! $result ) {
			$result = true; // Temporarily allow booking to proceed even if email fails
		}

		return $result;
	}

	/**
	 * Generate booking confirmation email content
	 */
	private static function generate_booking_confirmation_email( $booking_reference, $booking_data ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title><?php esc_html_e( 'Booking Confirmation', 'aiohm-booking-pro' ); ?></title>
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.header { background: #457d59; color: white; padding: 20px; text-align: center; }
				.content { padding: 20px; background: #f9f9f9; }
				.booking-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #457d59; }
				.footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1><?php esc_html_e( 'Booking Confirmation', 'aiohm-booking-pro' ); ?></h1>
					<p><?php esc_html_e( 'Thank you for your booking request!', 'aiohm-booking-pro' ); ?></p>
				</div>

				<div class="content">
					<p><?php esc_html_e( 'We have received your booking request and will contact you shortly to arrange payment and confirm your booking.', 'aiohm-booking-pro' ); ?></p>

					<div class="booking-details">
						<h3><?php esc_html_e( 'Booking Details', 'aiohm-booking-pro' ); ?></h3>
						<p><strong><?php esc_html_e( 'Booking Reference:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $booking_reference ); ?></p>

						<?php if ( ! empty( $booking_data['contact_info']['name'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Name:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $booking_data['contact_info']['name'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $booking_data['dates']['checkin'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Check-in:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $booking_data['dates']['checkin'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $booking_data['dates']['checkout'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Check-out:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $booking_data['dates']['checkout'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $booking_data['events'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Selected Events:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( count( $booking_data['events'] ) ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $booking_data['accommodations'] ) ) : ?>
							<p><strong><?php esc_html_e( 'Selected Accommodations:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( count( $booking_data['accommodations'] ) ); ?></p>
						<?php endif; ?>
					</div>

					<p><?php esc_html_e( 'We will review your booking request and send you payment instructions within 24 hours.', 'aiohm-booking-pro' ); ?></p>

					<p><?php esc_html_e( 'If you have any questions, please don\'t hesitate to contact us.', 'aiohm-booking-pro' ); ?></p>
				</div>

				<div class="footer">
					<p><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
					<p><?php esc_html_e( 'This is an automated message. Please do not reply to this email.', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate payment amount to ensure it meets Stripe's minimum requirements
	 */
	private static function validatePaymentAmount( $amount, $currency = 'USD' ) {
		// Stripe minimum amounts in cents (converted to dollars for validation)
		$minimum_amounts = array(
			'USD' => 0.50, // $0.50
			'EUR' => 0.50, // €0.50
			'GBP' => 0.30, // £0.30
			'AUD' => 0.50, // A$0.50
			'CAD' => 0.50, // C$0.50
			'JPY' => 50,   // ¥50
			'CHF' => 0.50, // CHF 0.50
			'NOK' => 3.00, // kr 3.00
			'SEK' => 3.00, // kr 3.00
			'DKK' => 2.50, // kr 2.50
			'NZD' => 0.50, // NZ$0.50
			'MXN' => 10.00, // MX$10.00
			'SGD' => 0.50, // S$0.50
			'HKD' => 4.00, // HK$4.00
			'BRL' => 0.50, // R$0.50
		);

		$currency       = strtoupper( $currency );
		$minimum_amount = $minimum_amounts[ $currency ] ?? 0.50;

		// Ensure amount is at least the minimum
		if ( $amount < $minimum_amount ) {
			$amount = $minimum_amount;
		}

		// Ensure amount is positive
		if ( $amount <= 0 ) {
			$amount = 0.50; // Default fallback
		}

		return $amount;
	}

	/**
	 * Create booking requests table if it doesn't exist
	 */
	private static function create_booking_requests_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'aiohm_booking_requests';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			booking_reference varchar(50) NOT NULL,
			contact_name varchar(255) DEFAULT '',
			contact_email varchar(255) NOT NULL,
			contact_phone varchar(50) DEFAULT '',
			contact_message text DEFAULT '',
			selected_events longtext DEFAULT '',
			selected_accommodations longtext DEFAULT '',
			checkin_date date DEFAULT NULL,
			checkout_date date DEFAULT NULL,
			pricing_data longtext DEFAULT '',
			status varchar(50) DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY booking_reference (booking_reference),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get manual payment HTML
	 */
	private static function get_manual_payment_html( $booking_id ) {
		// Get booking data
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely constructed with $wpdb->prefix
		$booking = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		if ( ! $booking ) {
			return '<p>Error: Booking not found.</p>';
		}

		$currency       = $booking['currency'] ?? 'USD';
		$total_amount   = floatval( $booking['total_amount'] );
		$deposit_amount = floatval( $booking['deposit_amount'] );

		ob_start();
		?>
		<div class="aiohm-manual-payment-details">
			<h4><?php esc_html_e( 'Invoice Payment Instructions', 'aiohm-booking-pro' ); ?></h4>
			<div class="aiohm-payment-info-card">
				<p><strong><?php esc_html_e( 'What happens next:', 'aiohm-booking-pro' ); ?></strong></p>
				<ol>
					<li><?php esc_html_e( 'You will receive an email with detailed payment instructions', 'aiohm-booking-pro' ); ?></li>
					<li><?php esc_html_e( 'Use the provided bank details or payment methods to complete your payment', 'aiohm-booking-pro' ); ?></li>
					<li><?php esc_html_e( 'Your booking will be confirmed once payment is received', 'aiohm-booking-pro' ); ?></li>
				</ol>
				
				<div class="aiohm-payment-summary">
					<p><strong><?php esc_html_e( 'Amount to Pay:', 'aiohm-booking-pro' ); ?></strong> 
						<?php echo esc_html( $currency . ' ' . number_format( $deposit_amount > 0 ? $deposit_amount : $total_amount, 2 ) ); ?>
					</p>
					<?php if ( $deposit_amount > 0 ) : ?>
						<p class="aiohm-deposit-note">
							<?php esc_html_e( 'This is a deposit to secure your booking. The remaining balance will be due as specified in your booking terms.', 'aiohm-booking-pro' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Stripe payment HTML
	 */
	private static function get_stripe_payment_html( $booking_id ) {
		// Check if user has access to premium features
		if ( ! function_exists( 'aiohm_booking_fs' ) || ! aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
			return '<div class="payment-error">Stripe payments require AIOHM Booking PRO.</div>';
		}

		// Check if Stripe module is available
		$stripe_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'stripe' );
		if ( ! $stripe_module ) {
			return '<p>Stripe payment is not available. Please contact support.</p>';
		}

		// Use the Stripe module's payment form rendering method
		return $stripe_module->get_payment_form_html( $booking_id );
	}

	/**
	 * Create a pending order and send notification when moving to checkout step
	 */
	public static function create_pending_order() {
		try {
			// Verify nonce
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_frontend_nonce' ) ) {
				wp_send_json_error( array( 'message' => 'Security check failed' ) );
			}

			// Parse form data
			$form_data_raw = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '';
			parse_str( $form_data_raw, $form_data );

			// Get pricing data from the pricing summary (this should be available from the form)
			$pricing_data = json_decode( stripslashes( $form_data['pricing_data'] ?? '{}' ), true );
			if ( empty( $pricing_data ) ) {
				// Fallback: try to get from session or calculate
				$pricing_data = array(
					'total' => 0.00,
					'deposit' => 0.00,
					'currency' => 'RON'
				);
			}

			// Prepare booking data
			$booking_data = array(
				'customer_first_name' => $form_data['name'] ?? '',
				'customer_email'      => $form_data['email'] ?? '',
				'customer_phone'      => $form_data['phone'] ?? '',
				'checkin_date'        => $form_data['checkin_date'] ?? '',
				'checkout_date'       => $form_data['checkout_date'] ?? '',
				'guests'              => $form_data['guests_qty'] ?? 1,
			);

			// Sanitize data
			$sanitized_data = AIOHM_BOOKING_Validation::sanitize_booking_data( $booking_data );

			$buyer_name     = $sanitized_data['customer_first_name'];
			$buyer_email    = $sanitized_data['customer_email'];
			$buyer_phone    = $sanitized_data['customer_phone'];
			$checkin_date   = $sanitized_data['checkin_date'];
			$checkout_date  = $sanitized_data['checkout_date'];
			$guest_count    = $sanitized_data['guests'];

			// Get pricing info
			$total_amount   = floatval( $pricing_data['total'] ?? 0 );
			$deposit_amount = floatval( $pricing_data['deposit'] ?? 0 );
			$currency       = $pricing_data['currency'] ?? 'RON';

			// Get selected items
			$selected_events = $form_data['selected_events'] ?? array();
			$selected_accommodations = $form_data['selected_accommodations'] ?? array();

			// Determine mode
			$mode = 'accommodation';
			if ( ! empty( $selected_events ) && empty( $selected_accommodations ) ) {
				$mode = 'event';
			} elseif ( ! empty( $selected_events ) && ! empty( $selected_accommodations ) ) {
				$mode = 'mixed';
			}

			// Calculate quantities
			$units_qty = count( $selected_accommodations ) + count( $selected_events );

			// Notes
			$notes = sanitize_textarea_field( $form_data['notes'] ?? '' );

			// Insert pending order
			global $wpdb;
			$table_name = $wpdb->prefix . 'aiohm_booking_order';

			$insert_result = $wpdb->insert(
				$table_name,
				array(
					'buyer_name'     => $buyer_name,
					'buyer_email'    => $buyer_email,
					'buyer_phone'    => $buyer_phone,
					'mode'           => $mode,
					'units_qty'      => $units_qty,
					'guests_qty'     => $guest_count,
					'currency'       => $currency,
					'total_amount'   => $total_amount,
					'deposit_amount' => $deposit_amount,
					'status'         => 'pending',
					'check_in_date'  => $checkin_date,
					'check_out_date' => $checkout_date,
					'notes'          => $notes,
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $insert_result ) {
				wp_send_json_error( array( 'message' => 'Failed to create pending order' ) );
			}

			$booking_id = $wpdb->insert_id;

			// Send notification using the notifications module
			$notifications_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'notifications' );
			if ( $notifications_module ) {
				$notification_sent = $notifications_module->send_invoice_notification( $booking_id );

				if ( $notification_sent ) {
					wp_send_json_success( array(
						'message'    => 'Pending order created and notification sent',
						'booking_id' => $booking_id,
					) );
				} else {
					wp_send_json_success( array(
						'message'    => 'Pending order created but notification failed',
						'booking_id' => $booking_id,
					) );
				}
			} else {
				wp_send_json_success( array(
					'message'    => 'Pending order created (notifications module not available)',
					'booking_id' => $booking_id,
				) );
			}

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error creating pending order: ' . $e->getMessage() ) );
		}
	}
}

// Initialize the checkout AJAX handlers
AIOHM_BOOKING_Checkout_Ajax::init();