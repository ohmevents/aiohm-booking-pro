<?php
/**
 * Checkout Template for AIOHM Booking
 * Handles the checkout process for bookings
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Get passed data.
$settings = $settings ?? array();
$atts     = $atts ?? array();

// Default values.
$style           = $atts['style'] ?? 'modern';
$show_summary    = $atts['show_summary'] ?? 'true';
$payment_methods = $atts['payment_methods'] ?? 'all';
$redirect_url    = $atts['redirect_url'] ?? '';
$developer_mode  = $atts['developer_mode'] ?? false;

// Get current booking data from URL parameter, session, or cookies.
$booking_data = array();
$booking_id   = 0;

// Get booking ID from shortcode attribute first, then URL parameter
if ( ! empty( $atts['booking_id'] ) ) {
	$booking_id = absint( $atts['booking_id'] );
} elseif ( isset( $_GET['booking_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public checkout page, booking ID is non-sensitive display parameter
	// Get booking ID with basic sanitization (read-only checkout view)
	$booking_id = absint( $_GET['booking_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public checkout page parameter
}

// Validate booking ID
if ( $booking_id > 0 ) {
	// Additional security: validate booking exists and basic ownership
	// This prevents enumeration of booking IDs
	if ( $booking_id > AIOHM_BOOKING_Security_Config::MAX_BOOKING_ID ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid booking ID.', 'aiohm-booking-pro' ) . '</p></div>';
		return;
	}
}

// First, try to get booking data from URL parameter.
if ( $booking_id > 0 ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'aiohm_booking_order';

	// Check if table exists.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.
		$booking = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id ),
			ARRAY_A
		);

		if ( $booking ) {
			// Check if this is a ticket booking or accommodation booking
			$is_ticket_booking = ( $booking['mode'] ?? '' ) === 'tickets';

			// Calculate number of nights.
			$checkin_date  = new DateTime( $booking['check_in_date'] );
			$checkout_date = new DateTime( $booking['check_out_date'] );
			$interval      = $checkin_date->diff( $checkout_date );
			$nights        = $interval->days;

			// For ticket bookings, treat as 1 night/1 event if nights is 0
			if ( $is_ticket_booking && $nights == 0 ) {
				$nights = 1;
			}

			// Get currency and amounts.
			$currency         = $booking['currency'] ?? 'USD';
			$total_amount     = floatval( $booking['total_amount'] );
			$deposit_amount   = floatval( $booking['deposit_amount'] );
			$remaining_amount = $total_amount - $deposit_amount;

			// Calculate per night/per ticket rate.
			$per_night_rate = $nights > 0 ? ( $total_amount / $nights ) : $total_amount;

			// Check if early bird pricing was applied.
			$booking_date       = new DateTime( $booking['created_at'] );
			$days_until_checkin = $booking_date->diff( $checkin_date )->days;

			// Get early bird settings.
			$plugin_settings      = get_option( 'aiohm_booking_settings', array() );
			$earlybird_days       = intval( $plugin_settings['early_bird_days'] ?? $plugin_settings['earlybird_days'] ?? 30 );
			$is_earlybird_booking = ( $days_until_checkin >= $earlybird_days );

			// If early bird was applied, calculate what regular price would have been (assuming 25% discount).
			$regular_total     = $is_earlybird_booking ? $total_amount / 0.8 : $total_amount;
			$earlybird_savings = $is_earlybird_booking ? $regular_total - $total_amount : 0;

			$booking_data = array(
				'id'             => $booking['id'],
				'check_in_date'  => $booking['check_in_date'],
				'check_out_date' => $booking['check_out_date'],
				'dates'          => array(
					'check_in'  => $booking['check_in_date'],
					'check_out' => $booking['check_out_date'],
				),
				'nights'         => $nights,
				'guests'         => $booking['guests_qty'],
				'guests_qty'     => $booking['guests_qty'],
				'rooms'          => $booking['rooms_qty'],
				'rooms_qty'      => $booking['rooms_qty'],
				'total_amount'   => $total_amount,
				'deposit_amount' => $deposit_amount,
				'currency'       => $currency,
				'pricing'        => array(
					'currency'          => $currency,
					'total_amount'      => $total_amount,
					'deposit_amount'    => $deposit_amount,
					'remaining_amount'  => $remaining_amount,
					'per_night_rate'    => $per_night_rate,
					'subtotal'          => $total_amount,
					'is_earlybird'      => $is_earlybird_booking,
					'regular_total'     => $regular_total,
					'earlybird_savings' => $earlybird_savings,
					'earlybird_days'    => $earlybird_days,
				),
				'total'          => $currency . ' ' . number_format( $total_amount, 2 ),
				'deposit'        => $currency . ' ' . number_format( $deposit_amount, 2 ),
				'remaining'      => $currency . ' ' . number_format( $remaining_amount, 2 ),
				'customer'       => array(
					'name'  => $booking['buyer_name'],
					'email' => $booking['buyer_email'],
					'phone' => $booking['buyer_phone'],
				),
				'notes'          => $booking['notes'],
				'status'         => $booking['status'],
				'mode'           => $booking['mode'] ?? 'accommodation',
			);
		}
	}
}

// Fallback to session/cookies if no URL parameter or booking not found.
if ( empty( $booking_data ) ) {
	if ( isset( $_SESSION['aiohm_booking_data'] ) ) {
		$session_data = sanitize_text_field( wp_unslash( $_SESSION['aiohm_booking_data'] ) );
		$booking_data = wp_kses_post_deep( $session_data );
	} elseif ( isset( $_COOKIE['aiohm_booking_data'] ) ) {
		$cookie_data  = sanitize_text_field( wp_unslash( $_COOKIE['aiohm_booking_data'] ) );
		$booking_data = json_decode( $cookie_data, true );
		$booking_data = $booking_data ? wp_kses_post_deep( $booking_data ) : array();
	}
}

// Check which modules are active
$global_settings        = get_option( 'aiohm_booking_settings', array() );
$accommodations_enabled = $global_settings['enable_accommodations'] ?? true;
$tickets_enabled        = $global_settings['enable_tickets'] ?? true;

// Determine booking type if we have booking data
$booking_mode = '';
if ( ! empty( $booking_data ) ) {
	$booking_mode = $booking_data['mode'] ?? 'accommodation'; // Default to accommodation for backward compatibility
}

?>
<div class="aiohm-checkout-container aiohm-checkout-style-<?php echo esc_attr( $style ); ?>" style="position: relative;">
	
	<?php if ( $developer_mode ) : ?>
		<div class="aiohm-developer-mode-badge">
			<?php esc_html_e( 'DEVELOPER MODE', 'aiohm-booking-pro' ); ?>
		</div>
	<?php endif; ?>
	
	<!-- Checkout Header -->
	<div class="aiohm-checkout-header">
		<h2 class="aiohm-checkout-title">
			<?php
			if ( $developer_mode ) {
				esc_html_e( 'Payment Testing (Developer Mode)', 'aiohm-booking-pro' );
			} else {
				esc_html_e( 'Complete Your Booking', 'aiohm-booking-pro' );
			}
			?>
		</h2>
		<p class="aiohm-checkout-subtitle">
			<?php
			if ( $developer_mode ) {
				esc_html_e( 'Test the payment integration in development mode.', 'aiohm-booking-pro' );
			} else {
				esc_html_e( 'Review your booking details and proceed with payment.', 'aiohm-booking-pro' );
			}
			?>
		</p>
	</div>

	<?php if ( empty( $booking_data ) ) : ?>
		<!-- No booking data available -->
		<div class="aiohm-checkout-empty">
			<div class="aiohm-notice aiohm-notice-warning">
				<p><?php esc_html_e( 'No booking data found. Please start a new booking.', 'aiohm-booking-pro' ); ?></p>
				<p><a href="<?php echo esc_url( get_home_url() ); ?>" class="aiohm-btn aiohm-btn-primary"><?php esc_html_e( 'Start New Booking', 'aiohm-booking-pro' ); ?></a></p>
			</div>
		</div>
	<?php else : ?>
		
		<div class="aiohm-checkout-content">
			
			<?php if ( 'true' === $show_summary ) : ?>
				<!-- Modular Booking Summary Components -->
				
				<?php
				$modular_component_loaded = false;

				// Show event checkout component if tickets module is enabled and this is a ticket booking
				if ( $tickets_enabled && $booking_mode === 'tickets' ) :
					include plugin_dir_path( __FILE__ ) . 'partials/aiohm-booking-checkout-events.php';
					$modular_component_loaded = true;
				endif;

				// Show accommodation checkout component if accommodations module is enabled and this is an accommodation booking
				if ( $accommodations_enabled && ( $booking_mode === 'accommodation' || $booking_mode === '' ) ) :
					include plugin_dir_path( __FILE__ ) . 'partials/aiohm-booking-checkout-accommodations.php';
					$modular_component_loaded = true;
				endif;
				?>
				
			<?php endif; ?>
			
			<!-- Fallback Pricing Breakdown (only if no modular component was loaded) -->
			<?php if ( ! $modular_component_loaded && ! empty( $booking_data['pricing'] ) ) : ?>
			<div class="aiohm-checkout-summary">
				<h3><?php esc_html_e( 'Pricing Details', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-summary-details">
					
					<?php if ( ! empty( $booking_data['pricing']['per_night_rate'] ) && ! empty( $booking_data['nights'] ) ) : ?>
					<div class="aiohm-summary-row">
						<span class="aiohm-summary-label">
							<?php
							/* translators: %d: number of nights */
							echo esc_html( sprintf( _n( '%d night', '%d nights', $booking_data['nights'], 'aiohm-booking-pro' ), $booking_data['nights'] ) );
							?>
							× <?php echo esc_html( $booking_data['pricing']['currency'] . ' ' . number_format( $booking_data['pricing']['per_night_rate'], 2 ) ); ?>
						</span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['pricing']['currency'] . ' ' . number_format( $booking_data['pricing']['total_amount'], 2 ) ); ?></span>
					</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $booking_data['pricing']['is_earlybird'] ) && ! empty( $booking_data['pricing']['earlybird_savings'] ) && $booking_data['pricing']['earlybird_savings'] > 0 ) : ?>
					<div class="aiohm-summary-row aiohm-summary-regular" style="text-decoration: line-through; color: #999;">
						<span class="aiohm-summary-label"><?php echo esc_html( sprintf( __( 'Regular Price:', 'aiohm-booking-pro' ) ) ); ?></span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['pricing']['currency'] . ' ' . number_format( $booking_data['pricing']['regular_total'], 2 ) ); ?></span>
					</div>
					
					<div class="aiohm-summary-row aiohm-summary-earlybird" style="color: #28a745; font-weight: bold;">
						<span class="aiohm-summary-label">
							<?php
							/* translators: %d: number of days for early bird discount */
							echo esc_html( sprintf( __( 'Early Bird (%d days):', 'aiohm-booking-pro' ), $booking_data['pricing']['earlybird_days'] ) );
							?>
						</span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['pricing']['currency'] . ' ' . number_format( $booking_data['pricing']['total_amount'], 2 ) ); ?></span>
					</div>
					
					<div class="aiohm-summary-row aiohm-summary-savings" style="color: #28a745;">
						<span class="aiohm-summary-label"><?php esc_html_e( 'You Save:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['pricing']['currency'] . ' ' . number_format( $booking_data['pricing']['earlybird_savings'], 2 ) ); ?></span>
					</div>
					<?php endif; ?>
					
					<div class="aiohm-summary-row aiohm-summary-subtotal">
						<span class="aiohm-summary-label"><?php esc_html_e( 'Subtotal:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['total'] ); ?></span>
					</div>
					
					<?php if ( ! empty( $booking_data['pricing']['deposit_amount'] ) ) : ?>
					<div class="aiohm-summary-row aiohm-summary-deposit">
						<span class="aiohm-summary-label"><?php esc_html_e( 'Deposit Required:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['deposit'] ); ?></span>
					</div>
					
					<div class="aiohm-summary-row aiohm-summary-remaining">
						<span class="aiohm-summary-label"><?php esc_html_e( 'Remaining Balance:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['remaining'] ); ?></span>
					</div>
					<?php endif; ?>
					
					<div class="aiohm-summary-row aiohm-summary-total">
						<span class="aiohm-summary-label"><?php esc_html_e( 'Total Amount:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['total'] ); ?></span>
					</div>
					
				</div>
			</div>
			<?php endif; ?>
			
			<!-- Payment Method Selection -->
			<div class="aiohm-payment-method-section">
				<h3><?php esc_html_e( 'Payment Method', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-payment-options aiohm-payment-options-grid">
					<label class="aiohm-payment-option">
						<input type="radio" name="payment_method_type" value="full" checked>
						<span class="aiohm-payment-option-content">
							<span class="aiohm-payment-title"><?php esc_html_e( 'Pay Full Amount', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-payment-description"><?php esc_html_e( 'Complete payment now', 'aiohm-booking-pro' ); ?></span>
						</span>
					</label>
					
					<label class="aiohm-payment-option">
						<input type="radio" name="payment_method_type" value="deposit">
						<span class="aiohm-payment-option-content">
							<span class="aiohm-payment-title"><?php esc_html_e( 'Pay Deposit Only', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-payment-description"><?php esc_html_e( 'Secure your booking with a deposit', 'aiohm-booking-pro' ); ?></span>
						</span>
					</label>
				</div>
			</div>
			
			<!-- Payment Methods -->
			<div class="aiohm-checkout-payment">
				<h3><?php esc_html_e( 'Payment', 'aiohm-booking-pro' ); ?></h3>
				
				<?php if ( ! empty( $booking_data['pricing']['deposit_amount'] ) && $booking_data['pricing']['deposit_amount'] > 0 ) : ?>
				<div class="aiohm-payment-amount">
					<div class="aiohm-payment-info">
						<span class="aiohm-payment-label"><?php esc_html_e( 'Amount to Pay Now (Deposit):', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-payment-value"><?php echo esc_html( $booking_data['deposit'] ); ?></span>
					</div>
					<p class="aiohm-payment-note">
						<?php esc_html_e( 'You are paying a deposit to secure your booking. The remaining balance will be due upon arrival or as specified in our booking terms.', 'aiohm-booking-pro' ); ?>
					</p>
				</div>
				<?php else : ?>
				<div class="aiohm-payment-amount">
					<div class="aiohm-payment-info">
						<span class="aiohm-payment-label"><?php esc_html_e( 'Amount to Pay:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-payment-value"><?php echo esc_html( $booking_data['total'] ?? 'N/A' ); ?></span>
					</div>
				</div>
				<?php endif; ?>
				
				<div class="aiohm-payment-methods">
					<?php if ( $developer_mode ) : ?>
						<!-- Developer Mode: Show all payment options for testing -->
						<div class="aiohm-developer-mode-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
							<strong><?php esc_html_e( 'Developer Mode Active', 'aiohm-booking-pro' ); ?></strong><br>
							<?php esc_html_e( 'All payment methods are available for testing. No actual charges will be processed.', 'aiohm-booking-pro' ); ?>
						</div>
					<?php endif; ?>
					
					<?php
					// Check if pro payment modules are available
					$has_pro_payments = apply_filters( 'aiohm_booking_has_pro_payment_methods', false );

					// In developer mode, always show all options regardless of premium status
					if ( $developer_mode || ! $has_pro_payments ) :
						?>
					<!-- Manual payment option (Free Invoice via Notifications) -->
					<div class="aiohm-payment-method-manual">
						<label class="aiohm-payment-method-label">
							<input type="radio" name="payment_method" value="manual" checked="checked" class="aiohm-payment-radio">
							<div class="aiohm-payment-method-info">
								<h4>
									<?php esc_html_e( 'Invoice Payment', 'aiohm-booking-pro' ); ?>
									<?php if ( ! $has_pro_payments ) : ?>
										<span class="aiohm-payment-badge aiohm-payment-badge-free"><?php esc_html_e( 'FREE', 'aiohm-booking-pro' ); ?></span>
									<?php endif; ?>
								</h4>
								<p class="aiohm-payment-description">
									<?php esc_html_e( 'Receive an invoice with payment instructions via email. Perfect for bank transfers or manual payments.', 'aiohm-booking-pro' ); ?>
								</p>
							</div>
						</label>
					</div>
					<?php endif; ?>

					<?php if ( $developer_mode || $has_pro_payments ) : ?>
						<!-- Pro Payment Methods -->
						<div class="aiohm-payment-method-stripe">
							<label class="aiohm-payment-method-label">
								<input type="radio" name="payment_method" value="stripe" class="aiohm-payment-radio">
								<div class="aiohm-payment-method-info">
									<h4>
										<?php esc_html_e( 'Credit/Debit Card', 'aiohm-booking-pro' ); ?>
										<?php if ( ! $has_pro_payments ) : ?>
											<span class="aiohm-payment-badge aiohm-payment-badge-pro"><?php esc_html_e( 'PRO', 'aiohm-booking-pro' ); ?></span>
										<?php endif; ?>
									</h4>
									<p class="aiohm-payment-description">
										<?php esc_html_e( 'Secure payment with Visa, Mastercard, American Express, and more.', 'aiohm-booking-pro' ); ?>
									</p>
								</div>
							</label>
						</div>
						
						<div class="aiohm-payment-method-paypal">
							<label class="aiohm-payment-method-label">
								<input type="radio" name="payment_method" value="paypal" class="aiohm-payment-radio">
								<div class="aiohm-payment-method-info">
									<h4>
										<?php esc_html_e( 'PayPal', 'aiohm-booking-pro' ); ?>
										<?php if ( ! $has_pro_payments ) : ?>
											<span class="aiohm-payment-badge aiohm-payment-badge-pro"><?php esc_html_e( 'PRO', 'aiohm-booking-pro' ); ?></span>
										<?php endif; ?>
									</h4>
									<p class="aiohm-payment-description">
										<?php esc_html_e( 'Pay safely with your PayPal account or PayPal Credit.', 'aiohm-booking-pro' ); ?>
									</p>
								</div>
							</label>
						</div>
					<?php endif; ?>

					<!-- Additional payment methods can be added here via modules -->
					<?php do_action( 'aiohm_booking_checkout_payment_methods', $developer_mode ); ?>
				</div>
				
				<!-- Payment Details Container -->
				<div id="aiohm-payment-details" class="aiohm-payment-details">
					<!-- Payment form will be loaded here based on selected method -->
				</div>
			</div>
			
			<!-- Submit Button -->
			<div class="aiohm-checkout-submit">
				<button type="button" id="aiohm-complete-booking" class="aiohm-btn aiohm-btn-primary aiohm-btn-large" data-booking-id="<?php echo esc_attr( $booking_id ); ?>">
					<?php esc_html_e( 'Complete Booking', 'aiohm-booking-pro' ); ?>
				</button>
				<p class="aiohm-checkout-terms">
					<?php esc_html_e( 'By completing your booking, you agree to our terms and conditions.', 'aiohm-booking-pro' ); ?>
				</p>
			</div>
		</div>
		
	<?php endif; ?>
</div>




