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

<style>
.aiohm-booking-cancelled {
	text-align: center;
	padding: 60px 20px;
	max-width: 600px;
	margin: 0 auto;
}

.aiohm-booking-cancelled-icon {
	color: #f39c12;
	margin-bottom: 30px;
}

.aiohm-booking-cancelled-title {
	font-size: 2.5em;
	color: #333;
	margin-bottom: 20px;
	font-weight: 300;
}

.aiohm-booking-cancelled-message {
	font-size: 1.2em;
	color: #666;
	margin-bottom: 40px;
	line-height: 1.6;
}

.aiohm-booking-cancelled-actions {
	display: flex;
	gap: 20px;
	justify-content: center;
	flex-wrap: wrap;
}

.aiohm-booking-btn {
	display: inline-block;
	padding: 15px 30px;
	text-decoration: none;
	border-radius: 5px;
	font-weight: 500;
	transition: all 0.3s ease;
}

.aiohm-booking-btn-primary {
	background: #007cba;
	color: white;
}

.aiohm-booking-btn-primary:hover {
	background: #005a87;
}

.aiohm-booking-btn-secondary {
	background: #f7f7f7;
	color: #333;
	border: 1px solid #ddd;
}

.aiohm-booking-btn-secondary:hover {
	background: #e9e9e9;
}

@media (max-width: 768px) {
	.aiohm-booking-cancelled-actions {
		flex-direction: column;
		align-items: center;
	}

	.aiohm-booking-btn {
		width: 200px;
		text-align: center;
	}
}
</style>

<?php
get_footer();
?>