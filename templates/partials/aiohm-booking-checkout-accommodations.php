<?php
/**
 * Accommodation Checkout Component - Modular
 * Displays accommodation booking summary and details for checkout
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract passed data
$booking_data = $booking_data ?? array();
$show_summary = $show_summary ?? true;

// Only show if this is an accommodation booking
$booking_mode = $booking_data['mode'] ?? 'accommodation';
if ( empty( $booking_data ) || $booking_mode !== 'accommodation' ) {
	return;
}

// Extract accommodation-specific data
$checkin_date  = $booking_data['dates']['check_in'] ?? $booking_data['check_in_date'] ?? '';
$checkout_date = $booking_data['dates']['check_out'] ?? $booking_data['check_out_date'] ?? '';
$nights        = intval( $booking_data['nights'] ?? 1 );
$guests        = intval( $booking_data['guests'] ?? $booking_data['guests_qty'] ?? 1 );
$units         = intval( $booking_data['units'] ?? $booking_data['units_qty'] ?? 1 );
$currency      = $booking_data['currency'] ?? 'EUR';

// Get deposit percentage from settings
$global_settings = get_option( 'aiohm_booking_settings', array() );
$deposit_percent = intval( $global_settings['deposit_percentage'] ?? 50 );

// Format dates for display
$checkin_formatted  = '';
$checkout_formatted = '';
if ( ! empty( $checkin_date ) ) {
	$checkin_formatted = date_i18n( 'M j, Y', strtotime( $checkin_date ) );
}
if ( ! empty( $checkout_date ) ) {
	$checkout_formatted = date_i18n( 'M j, Y', strtotime( $checkout_date ) );
}

// Calculate per night rate
$total_amount   = floatval( $booking_data['total_amount'] ?? 0 );
$per_night_rate = ( $nights > 0 && $units > 0 ) ? ( $total_amount / ( $nights * $units ) ) : $total_amount;

// Calculate deposit amount
$deposit_amount = ( $total_amount * $deposit_percent ) / 100;

// Check if early bird pricing was applied
$booking_date       = new DateTime( $booking_data['created_at'] ?? 'now' );
$checkin_date_obj   = new DateTime( $checkin_date );
$days_until_checkin = $booking_date->diff( $checkin_date_obj )->days;
$earlybird_days     = intval( $global_settings['early_bird_days'] ?? 30 );
$is_earlybird       = ( $days_until_checkin >= $earlybird_days );

// Get customer information
$customer_name  = $booking_data['customer']['name'] ?? '';
$customer_email = $booking_data['customer']['email'] ?? '';
$customer_phone = $booking_data['customer']['phone'] ?? '';
$booking_id     = $booking_data['id'] ?? '';
?>

<div class="aiohm-checkout-accommodation-summary">
	<!-- Booking Summary Table -->
	<div class="aiohm-checkout-summary-table aiohm-booking-card">
		<div class="aiohm-booking-shortcode-card-header">
			<h4><?php esc_html_e( 'Booking Summary', 'aiohm-booking-pro' ); ?></h4>
		</div>

		<table class="aiohm-summary-table">
			<tr>
				<!-- Left Column: Booking Details -->
				<td class="aiohm-summary-left">
					<div class="aiohm-summary-section">
						<h5><?php esc_html_e( 'Booking Details', 'aiohm-booking-pro' ); ?></h5>

						<?php if ( ! empty( $checkin_formatted ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Check-in:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $checkin_formatted ); ?></span>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $checkout_formatted ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Check-out:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $checkout_formatted ); ?></span>
						</div>
						<?php endif; ?>

						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Duration:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value">
								<?php
								/* translators: %d: number of nights for accommodation stay */
								echo esc_html( sprintf( _n( '%d night', '%d nights', $nights, 'aiohm-booking-pro' ), $nights ) );
								?>
							</span>
						</div>

						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Guests:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $guests ); ?></span>
						</div>

						<?php if ( $units > 1 ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Units:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $units ); ?></span>
						</div>
						<?php endif; ?>

						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Early Bird:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo $is_earlybird ? esc_html__( 'Applied', 'aiohm-booking-pro' ) : esc_html__( 'Not Applied', 'aiohm-booking-pro' ); ?></span>
						</div>

						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Deposit Percentage:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $deposit_percent ); ?>%</span>
						</div>

						<div class="aiohm-summary-row aiohm-summary-total">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Total:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $currency . ' ' . number_format( $total_amount, 2 ) ); ?></span>
						</div>
					</div>
				</td>

				<!-- Right Column: Contact Information -->
				<td class="aiohm-summary-right">
					<div class="aiohm-summary-section">
						<h5><?php esc_html_e( 'Contact Information', 'aiohm-booking-pro' ); ?></h5>

						<?php if ( ! empty( $booking_id ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Booking ID:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value">#<?php echo esc_html( $booking_id ); ?></span>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $customer_name ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Name:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $customer_name ); ?></span>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $customer_email ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Email:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $customer_email ); ?></span>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $customer_phone ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Phone:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $customer_phone ); ?></span>
						</div>
						<?php endif; ?>

						<?php if ( ! empty( $booking_data['notes'] ) ) : ?>
						<div class="aiohm-summary-row">
							<span class="aiohm-summary-label"><?php esc_html_e( 'Notes:', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-summary-value"><?php echo esc_html( $booking_data['notes'] ); ?></span>
						</div>
						<?php endif; ?>
					</div>
				</td>
			</tr>
		</table>

		<!-- Deposit Information (if applicable) -->
		<?php if ( ! empty( $booking_data['deposit_amount'] ) && floatval( $booking_data['deposit_amount'] ) > 0 ) : ?>
		<div class="aiohm-deposit-info">
			<div class="aiohm-pricing-row aiohm-pricing-deposit">
				<span class="aiohm-pricing-label">
					<span class="dashicons dashicons-money-alt"></span>
					<?php esc_html_e( 'Deposit Required:', 'aiohm-booking-pro' ); ?>
				</span>
				<span class="aiohm-pricing-value"><?php echo esc_html( $currency ); ?><?php echo number_format( floatval( $booking_data['deposit_amount'] ), 2 ); ?></span>
			</div>

			<div class="aiohm-pricing-row aiohm-pricing-balance">
				<span class="aiohm-pricing-label">
					<span class="dashicons dashicons-calculator"></span>
					<?php esc_html_e( 'Remaining Balance:', 'aiohm-booking-pro' ); ?>
				</span>
				<span class="aiohm-pricing-value"><?php echo esc_html( $currency ); ?><?php echo number_format( $total_amount - floatval( $booking_data['deposit_amount'] ), 2 ); ?></span>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>