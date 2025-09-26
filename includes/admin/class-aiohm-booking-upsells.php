<?php
/**
 * Upsell and Marketing for Free Version
 *
 * @fs_free_only
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Upsells Class
 */
class AIOHM_BOOKING_Upsells {

	/**
	 * Initialize upsells
	 */
	public static function init() {
		// Only show upsells in free version
		if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
			return;
		}

		add_action( 'aiohm_booking_settings_after_payments', array( __CLASS__, 'render_payment_upsell' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_upgrade_notice' ) );
		add_filter( 'aiohm_booking_payment_methods', array( __CLASS__, 'add_locked_payment_methods' ) );
	}

	/**
	 * Render payment upsell section
	 */
	public static function render_payment_upsell() {
		?>
		<div class="aiohm-booking-upsell-section">
			<div class="aiohm-booking-upsell-header">
				<h3><?php esc_html_e( 'Unlock Payment Processing', 'aiohm-booking-pro' ); ?> <span class="pro-badge">PRO</span></h3>
				<p><?php esc_html_e( 'Accept payments for your bookings with secure payment processing through Stripe and PayPal.', 'aiohm-booking-pro' ); ?></p>
			</div>
			
			<div class="aiohm-booking-locked-payment-methods">
				<div class="payment-method-card locked">
					<div class="payment-method-icon">💳</div>
					<h4><?php esc_html_e( 'Stripe Payments', 'aiohm-booking-pro' ); ?></h4>
					<p><?php esc_html_e( 'Accept credit cards, digital wallets, and international payments with Stripe\'s secure platform.', 'aiohm-booking-pro' ); ?></p>
					<ul class="features-list">
						<li>✓ <?php esc_html_e( 'Credit/Debit Cards', 'aiohm-booking-pro' ); ?></li>
						<li>✓ <?php esc_html_e( 'Apple Pay & Google Pay', 'aiohm-booking-pro' ); ?></li>
						<li>✓ <?php esc_html_e( '3D Secure Authentication', 'aiohm-booking-pro' ); ?></li>
						<li>✓ <?php esc_html_e( 'Automatic Refunds', 'aiohm-booking-pro' ); ?></li>
					</ul>
				</div>

				<div class="payment-method-card locked">
					<div class="payment-method-icon">🛡️</div>
					<h4><?php esc_html_e( 'PayPal Payments', 'aiohm-booking-pro' ); ?></h4>
					<p><?php esc_html_e( 'Trusted PayPal payments with buyer protection and Pay in 4 installments.', 'aiohm-booking-pro' ); ?></p>
					<ul class="features-list">
						<li>✓ <?php esc_html_e( 'PayPal Wallet', 'aiohm-booking-pro' ); ?></li>
						<li>✓ <?php esc_html_e( 'Pay in 4 (Buy Now, Pay Later)', 'aiohm-booking-pro' ); ?></li>
						<li>✓ <?php esc_html_e( 'Buyer Protection', 'aiohm-booking-pro' ); ?></li>
						<li>✓ <?php esc_html_e( 'International Support', 'aiohm-booking-pro' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="aiohm-booking-upsell-cta">
				<div class="pricing-info">
					<span class="price"><?php esc_html_e( 'Starting at €7/month', 'aiohm-booking-pro' ); ?></span>
					<span class="save-text"><?php esc_html_e( 'Save with annual billing', 'aiohm-booking-pro' ); ?></span>
				</div>
				<?php if ( function_exists( 'aiohm_booking_fs' ) ) : ?>
				<a href="https://checkout.freemius.com/plugin/20270/plan/33657/" target="_blank" class="button button-primary button-large">
					<?php esc_html_e( 'Upgrade to Pro', 'aiohm-booking-pro' ); ?>
				</a>
				<?php endif; ?>
			</div>
		</div>

		<style>
		.aiohm-booking-upsell-section {
			background: linear-gradient(135deg, var(--aiohm-brand-color, #457d59) 0%, #2d5233 100%);
			color: white;
			padding: 30px;
			border-radius: 8px;
			margin: 20px 0;
			position: relative;
			overflow: hidden;
		}
		.aiohm-booking-upsell-section::before {
			content: '';
			position: absolute;
			top: -50%;
			left: -50%;
			width: 200%;
			height: 200%;
			background: repeating-linear-gradient(
				45deg,
				transparent,
				transparent 10px,
				rgba(255,255,255,0.1) 10px,
				rgba(255,255,255,0.1) 20px
			);
			animation: movePattern 20s linear infinite;
		}
		@keyframes movePattern {
			0% { transform: translate(-50%, -50%) rotate(0deg); }
			100% { transform: translate(-50%, -50%) rotate(360deg); }
		}
		.aiohm-booking-upsell-header {
			text-align: center;
			margin-bottom: 30px;
			position: relative;
			z-index: 2;
		}
		.aiohm-booking-upsell-header h3 {
			color: white;
			font-size: 1.8em;
			margin-bottom: 10px;
		}
		.aiohm-booking-upsell-header p {
			color: #cccccc;
			font-size: 16px;
			margin: 0 0 20px 0;
		}
		.pro-badge {
			background: #ff6b6b;
			color: white;
			padding: 4px 12px;
			border-radius: 15px;
			font-size: 0.7em;
			font-weight: bold;
			box-shadow: 0 2px 4px rgba(0,0,0,0.2);
		}
		.aiohm-booking-locked-payment-methods {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
			position: relative;
			z-index: 2;
		}
		.payment-method-card {
			background: rgba(255,255,255,0.15);
			backdrop-filter: blur(10px);
			border: 1px solid rgba(255,255,255,0.2);
			border-radius: 12px;
			padding: 25px;
			text-align: center;
			transition: transform 0.3s ease;
		}
		.payment-method-card:hover {
			transform: translateY(-5px);
		}
		.payment-method-icon {
			font-size: 2.5em;
			margin-bottom: 15px;
		}
		.payment-method-card h4 {
			color: white;
			margin-bottom: 10px;
			font-size: 1.2em;
		}
		.payment-method-card p {
			color: rgba(255,255,255,0.9);
			margin-bottom: 15px;
			line-height: 1.5;
		}
		.features-list {
			list-style: none;
			padding: 0;
			margin: 0;
			text-align: left;
		}
		.features-list li {
			color: rgba(255,255,255,0.9);
			margin: 8px 0;
			padding-left: 20px;
			position: relative;
		}
		.aiohm-booking-upsell-cta {
			text-align: center;
			position: relative;
			z-index: 2;
		}
		.pricing-info {
			margin-bottom: 20px;
		}
		.price {
			font-size: 1.5em;
			font-weight: bold;
			color: white;
			display: block;
		}
		.save-text {
			color: rgba(255,255,255,0.8);
			font-size: 0.9em;
		}
		.aiohm-booking-upsell-cta .button {
			padding: 12px 30px;
			font-size: 16px;
			background: #ff6b6b;
			border-color: #ff6b6b;
			box-shadow: 0 4px 15px rgba(0,0,0,0.2);
			transition: all 0.3s ease;
		}
		.aiohm-booking-upsell-cta .button:hover {
			background: #ff5252;
			border-color: #ff5252;
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(0,0,0,0.3);
		}
		</style>
		<?php
	}

	/**
	 * Show minimal upgrade banner
	 */
	public static function show_upgrade_notice() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'aiohm-booking-pro' ) === false ) {
			return;
		}

		// Don't show on dashboard - Getting Started section handles this
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This only checks the current page, does not process form data
		if ( isset( $_GET['page'] ) && 'aiohm-booking-pro' === $_GET['page'] ) {
			return;
		}

		$dismissed = get_user_meta( get_current_user_id(), 'aiohm_booking_upgrade_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}
		?>
		<div class="aiohm-booking-minimal-banner">
			<div class="aiohm-banner-content">
				<span class="aiohm-banner-text">
					<?php esc_html_e( 'Activate Online Payments and AI insights. Starting at €7/month.', 'aiohm-booking-pro' ); ?>
				</span>
				<div class="aiohm-banner-actions">
					<?php if ( function_exists( 'aiohm_booking_fs' ) ) : ?>
					<a href="https://checkout.freemius.com/plugin/20270/plan/33657/" target="_blank" class="aiohm-upgrade-btn">
						<?php esc_html_e( 'Upgrade', 'aiohm-booking-pro' ); ?>
					</a>
					<?php endif; ?>
					<button class="aiohm-dismiss-btn" onclick="aiohm_dismiss_banner()">×</button>
				</div>
			</div>
		</div>

		<style>
		.aiohm-booking-minimal-banner {
			background: linear-gradient(135deg, var(--ohm-primary) 0%, var(--ohm-secondary) 100%);
			border-left: 4px solid var(--ohm-primary-dark);
			margin: var(--spacing-5) 0 var(--spacing-3) 0;
			padding: 0;
			position: relative;
			border-radius: var(--border-radius);
			box-shadow: var(--shadow-sm);
		}

		.aiohm-banner-content {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: var(--spacing-3) var(--spacing-5);
		}

		.aiohm-banner-text {
			color: var(--ohm-white);
			font-size: var(--ohm-font-size-sm);
			font-weight: var(--ohm-font-weight-medium);
			font-family: var(--ohm-font-secondary);
			flex: 1;
			margin-right: var(--spacing-4);
		}

		.aiohm-banner-actions {
			display: flex;
			align-items: center;
			gap: var(--spacing-3);
		}

		.aiohm-upgrade-btn {
			background: rgba(255,255,255,0.2);
			border: 1px solid rgba(255,255,255,0.3);
			color: var(--ohm-white) !important;
			padding: var(--spacing-1_5) var(--spacing-4);
			border-radius: var(--border-radius-full);
			text-decoration: none !important;
			font-size: var(--ohm-font-size-xs);
			font-weight: var(--ohm-font-weight-semibold);
			font-family: var(--ohm-font-primary);
			transition: var(--transition-all);
			backdrop-filter: blur(10px);
		}

		.aiohm-upgrade-btn:hover {
			background: rgba(255,255,255,0.3);
			border-color: rgba(255,255,255,0.5);
			transform: translateY(-1px);
			box-shadow: var(--shadow-md);
		}

		.aiohm-dismiss-btn {
			background: none;
			border: none;
			color: rgba(255,255,255,0.7);
			font-size: var(--ohm-font-size-lg);
			cursor: pointer;
			padding: var(--spacing-1) var(--spacing-2);
			border-radius: 50%;
			transition: var(--transition-colors);
			line-height: 1;
		}

		.aiohm-dismiss-btn:hover {
			background: rgba(255,255,255,0.1);
			color: var(--ohm-white);
		}

		@media (max-width: 768px) {
			.aiohm-banner-content {
				flex-direction: column;
				gap: var(--spacing-3);
				text-align: center;
			}
			
			.aiohm-banner-text {
				margin-right: 0;
			}
		}
		</style>

		<script>
		function aiohm_dismiss_banner() {
			document.querySelector('.aiohm-booking-minimal-banner').style.display = 'none';
			jQuery.post(ajaxurl, {
				action: 'aiohm_booking_dismiss_upgrade_notice',
				nonce: '<?php echo esc_js( wp_create_nonce( 'aiohm_booking_dismiss_notice' ) ); ?>'
			});
		}
		</script>
		<?php
	}

	/**
	 * Add locked payment methods to show in free version
	 */
	public static function add_locked_payment_methods( $methods ) {
		$methods['stripe_locked'] = array(
			'id'          => 'stripe',
			'title'       => __( 'Stripe', 'aiohm-booking-pro' ) . ' <span class="pro-label">PRO</span>',
			'description' => __( 'Professional payment processing with Stripe - requires Pro upgrade', 'aiohm-booking-pro' ),
			'icon'        => AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-stripe.png',
			'enabled'     => false,
			'locked'      => true,
		);

		$methods['paypal_locked'] = array(
			'id'          => 'paypal',
			'title'       => __( 'PayPal', 'aiohm-booking-pro' ) . ' <span class="pro-label">PRO</span>',
			'description' => __( 'Trusted PayPal payments with buyer protection - requires Pro upgrade', 'aiohm-booking-pro' ),
			'icon'        => AIOHM_BOOKING_URL . 'includes/modules/payments/paypal/assets/images/aiohm-booking-paypal.svg',
			'enabled'     => false,
			'locked'      => true,
		);

		return $methods;
	}
}

// Handle dismiss notice AJAX
add_action(
	'wp_ajax_aiohm_booking_dismiss_upgrade_notice',
	function () {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'aiohm_booking_dismiss_notice' ) ) {
			wp_die();
		}
		update_user_meta( get_current_user_id(), 'aiohm_booking_upgrade_notice_dismissed', true );
		wp_die();
	}
);