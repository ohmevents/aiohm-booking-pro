<?php
/* <fs_premium_only> */
/**
 * Stripe Settings Template
 *
 * @package AIOHM_Booking_PRO
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Get settings from the module
$settings = isset( $settings ) ? $settings : AIOHM_BOOKING_Settings::get_all();
?>

<!-- Stripe Configuration Section -->
<div class="aiohm-booking-card aiohm-card aiohm-mb-8 aiohm-masonry-card" id="aiohm-stripe-settings" data-module="stripe">
	<div class="aiohm-masonry-drag-handle">
	<span class="dashicons dashicons-menu"></span>
	</div>
	<div class="aiohm-card-header aiohm-card__header">
		<div class="aiohm-card-header-title">
			<h3 class="aiohm-card-title aiohm-card__title">
				<span class="aiohm-card-icon">💳</span>
				Stripe Settings
			</h3>
		</div>
		<div class="aiohm-header-controls">
			<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-stripe-settings">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
			</button>
		</div>
	</div>
	<div class="aiohm-card-content aiohm-card__content">
	<p class="aiohm-p">Configure Stripe payment integration for secure credit card processing and payment handling.</p>

	<div class="aiohm-form-section">
		<h4>API Configuration</h4>

		<div class="aiohm-form-group">
			<label class="aiohm-form-label">Stripe Publishable Key</label>
			<div class="aiohm-input-wrapper aiohm-input-wrapper--password">
			<input type="password"
					name="aiohm_booking_settings[stripe_publishable_key]"
					id="stripe_publishable_key"
					value="<?php echo esc_attr( $settings['stripe_publishable_key'] ?? '' ); ?>"
					placeholder="pk_test_..."
					class="aiohm-form-input aiohm-form-input--password">
			<button type="button" class="aiohm-input-toggle" data-action="toggle-password" data-target="stripe_publishable_key">
				<span class="dashicons dashicons-visibility"></span>
			</button>
			</div>
			<small class="description">
			Get your publishable key from <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener">Stripe Dashboard</a>
			</small>
		</div>

		<div class="aiohm-form-group">
			<label class="aiohm-form-label">Stripe Secret Key</label>
			<div class="aiohm-input-wrapper aiohm-input-wrapper--password">
			<input type="password"
					name="aiohm_booking_settings[stripe_secret_key]"
					id="stripe_secret_key"
					value="<?php echo esc_attr( $settings['stripe_secret_key'] ?? '' ); ?>"
					placeholder="sk_test_..."
					class="aiohm-form-input aiohm-form-input--password">
			<button type="button" class="aiohm-input-toggle" data-action="toggle-password" data-target="stripe_secret_key">
				<span class="dashicons dashicons-visibility"></span>
			</button>
			</div>
			<small class="description">Keep this key secure and never share it publicly</small>
		</div>

		<div class="aiohm-form-group">
			<label class="aiohm-form-label">Stripe Webhook Secret</label>
			<div class="aiohm-input-wrapper aiohm-input-wrapper--password">
			<input type="password"
					name="aiohm_booking_settings[stripe_webhook_secret]"
					id="stripe_webhook_secret"
					value="<?php echo esc_attr( $settings['stripe_webhook_secret'] ?? '' ); ?>"
					placeholder="whsec_..."
					class="aiohm-form-input aiohm-form-input--password">
			<button type="button" class="aiohm-input-toggle" data-action="toggle-password" data-target="stripe_webhook_secret">
				<span class="dashicons dashicons-visibility"></span>
			</button>
			</div>
			<small class="description">Used for webhook signature verification</small>
		</div>

		<div class="aiohm-form-group">
			<label class="aiohm-form-label">Stripe Webhook URL</label>
			<div class="aiohm-input-wrapper">
			<input type="text"
					id="stripe_webhook_url"
					value="<?php echo esc_url( home_url( '/wp-json/aiohm-booking/v1/stripe/webhook' ) ); ?>"
					readonly
					class="aiohm-form-input">
			<button type="button" class="aiohm-input-copy" data-action="copy-to-clipboard" data-target="stripe_webhook_url" title="Copy to clipboard">
				<span class="dashicons dashicons-clipboard"></span>
			</button>
			</div>
			<small class="description">Copy this URL to your Stripe webhook configuration</small>
		</div>

		<div class="aiohm-form-actions">
			<?php
			$stripe_secret_key    = $settings['stripe_secret_key'] ?? '';
			$is_stripe_configured = ! empty( $stripe_secret_key );
			?>
			<button type="button"
					name="test_stripe_connection"
					id="test-stripe-connection"
					class="aiohm-btn aiohm-btn--primary"
					data-action="test-connection"
					data-provider="stripe"
					value="1"
					<?php echo ! $is_stripe_configured ? 'disabled' : ''; ?>>
				<span class="dashicons dashicons-admin-links"></span>
				<span class="btn-text">Test Connection</span>
			</button>
			<?php submit_button( 'Save Stripe Settings', 'primary', 'save_stripe_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--save' ) ); ?>
		</div>
	</div>

	</div>
</div>
<?php // End Stripe Configuration . ?>.
/* </fs_premium_only> */