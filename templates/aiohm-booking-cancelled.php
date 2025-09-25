<?php
/**
 * Booking Cancelled Template
 * Displayed when user cancels Stripe checkout
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="aiohm-booking-container">
	<div class="aiohm-booking-wrapper">
		<div class="aiohm-booking-cancelled">
			<div class="aiohm-booking-cancelled-icon">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
					<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M11,6V8H13V6H11M11,10V18H13V10H11Z"/>
				</svg>
			</div>

			<h1 class="aiohm-booking-cancelled-title">
				<?php esc_html_e( 'Payment Cancelled', 'aiohm-booking-pro' ); ?>
			</h1>

			<p class="aiohm-booking-cancelled-message">
				<?php esc_html_e( 'Your payment has been cancelled. No charges have been made to your account.', 'aiohm-booking-pro' ); ?>
			</p>

			<div class="aiohm-booking-cancelled-actions">
				<a href="<?php echo esc_url( home_url( '/booking' ) ); ?>" class="aiohm-booking-btn aiohm-booking-btn-primary">
					<?php esc_html_e( 'Try Again', 'aiohm-booking-pro' ); ?>
				</a>

				<a href="<?php echo esc_url( home_url() ); ?>" class="aiohm-booking-btn aiohm-booking-btn-secondary">
					<?php esc_html_e( 'Return to Home', 'aiohm-booking-pro' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>



<?php
get_footer();
?>