<?php

namespace AIOHM_Booking_PRO\Admin;
/**
 * License Activation Helper
 *
 * @package AIOHM_Booking
 * @since 1.2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Activation Helper Class
 */
class AIOHM_BOOKING_License_Activation {

	/**
	 * Initialize license activation
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_license_page' ) );
		add_action( 'wp_ajax_aiohm_activate_license', array( __CLASS__, 'handle_license_activation' ) );
	}

	/**
	 * Add license activation page
	 */
	public static function add_license_page() {
		add_submenu_page(
			'aiohm-booking-pro',
			__( 'License Activation', 'aiohm-booking-pro' ),
			__( 'License', 'aiohm-booking-pro' ),
			'manage_options',
			'aiohm-booking-license',
			array( __CLASS__, 'render_license_page' )
		);
	}

	/**
	 * Render license activation page
	 */
	public static function render_license_page() {
		$is_premium = function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->is_premium();
		$fs         = function_exists( 'aiohm_booking_fs' ) ? aiohm_booking_fs() : null;
		?>
		<div class="wrap aiohm-booking-admin">
			<h1><?php esc_html_e( 'AIOHM Booking License', 'aiohm-booking-pro' ); ?></h1>
			
			<?php if ( $is_premium ) : ?>
				<div class="notice notice-success">
					<p><strong><?php esc_html_e( 'Pro License Active!', 'aiohm-booking-pro' ); ?></strong></p>
					<p><?php esc_html_e( 'Your AIOHM Booking Pro license is active and working.', 'aiohm-booking-pro' ); ?></p>
				</div>
				
				<div class="aiohm-license-info">
					<h2><?php esc_html_e( 'License Information', 'aiohm-booking-pro' ); ?></h2>
					<?php if ( $fs ) : ?>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Plan', 'aiohm-booking-pro' ); ?></th>
							<td><?php echo esc_html( $fs->get_plan_name() ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'License Type', 'aiohm-booking-pro' ); ?></th>
							<td><?php echo esc_html( $fs->is_paying() ? __( 'Paid', 'aiohm-booking-pro' ) : __( 'Trial', 'aiohm-booking-pro' ) ); ?></td>
						</tr>
						<?php if ( $fs->is_paying() && method_exists( $fs, 'get_subscription' ) ) : ?>
						<tr>
							<th><?php esc_html_e( 'Renewal', 'aiohm-booking-pro' ); ?></th>
							<td><?php echo esc_html( gmdate( 'F j, Y', strtotime( $fs->get_subscription()->next_payment ) ) ); ?></td>
						</tr>
						<?php endif; ?>
					</table>
					<?php endif; ?>
				</div>
				
			<?php else : ?>
				<div class="notice notice-info">
					<p><strong><?php esc_html_e( 'Free Version Active', 'aiohm-booking-pro' ); ?></strong></p>
					<p><?php esc_html_e( 'Activate your Pro license to unlock payment processing features.', 'aiohm-booking-pro' ); ?></p>
				</div>

				<div class="aiohm-license-activation">
					<h2><?php esc_html_e( 'Activate Pro License', 'aiohm-booking-pro' ); ?></h2>
					
					<form id="aiohm-license-form" method="post">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="license_key"><?php esc_html_e( 'License Key', 'aiohm-booking-pro' ); ?></label>
								</th>
								<td>
									<input type="text" id="license_key" name="license_key" value="" class="regular-text" placeholder="sk_..." />
									<p class="description">
										<?php esc_html_e( 'Enter your AIOHM Booking Pro license key to activate premium features.', 'aiohm-booking-pro' ); ?>
									</p>
								</td>
							</tr>
						</table>
						
						<?php wp_nonce_field( 'aiohm_license_activation', 'license_nonce' ); ?>
						
						<p class="submit">
							<button type="submit" class="button button-primary" id="activate-license">
								<?php esc_html_e( 'Activate License', 'aiohm-booking-pro' ); ?>
							</button>
							<span class="spinner"></span>
						</p>
					</form>
					
					<div id="license-activation-result"></div>
				</div>

				<div class="aiohm-license-help">
					<h3><?php esc_html_e( 'Need Help?', 'aiohm-booking-pro' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'License keys start with "sk_" followed by a unique identifier', 'aiohm-booking-pro' ); ?></li>
						<li><?php esc_html_e( 'You can find your license key in your Freemius account or purchase email', 'aiohm-booking-pro' ); ?></li>
						<li><?php esc_html_e( 'Contact support if you\'re having trouble activating your license', 'aiohm-booking-pro' ); ?></li>
					</ul>
					
					<?php if ( function_exists( 'aiohm_booking_fs' ) ) : ?>
					<p>
						<a href="<?php echo esc_url( aiohm_booking_fs()->get_upgrade_url() ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Purchase Pro License', 'aiohm-booking-pro' ); ?>
						</a>
					</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#aiohm-license-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $button = $('#activate-license');
				var $spinner = $('.spinner');
				var $result = $('#license-activation-result');
				var licenseKey = $('#license_key').val();
				
				if (!licenseKey) {
					$result.html('<div class="notice notice-error"><p><?php esc_html_e( 'Please enter a license key.', 'aiohm-booking-pro' ); ?></p></div>');
					return;
				}
				
				$button.prop('disabled', true);
				$spinner.addClass('is-active');
				$result.empty();
				
				// For Freemius, we need to redirect to the activation URL
				// This is a simplified approach - in practice, Freemius handles this
				var activationUrl = '<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-account' ) ); ?>';
				$result.html('<div class="notice notice-info"><p><?php esc_html_e( 'Redirecting to Freemius activation...', 'aiohm-booking-pro' ); ?></p></div>');
				
				// Store the license key for the user to copy-paste
				sessionStorage.setItem('aiohm_license_key', licenseKey);
				
				setTimeout(function() {
					window.location.href = activationUrl;
				}, 2000);
			});
		});
		</script>
		
		<style>
		.aiohm-license-activation {
			background: var(--ohm-white);
			border: 1px solid var(--ohm-gray-200);
			border-radius: var(--border-radius);
			padding: var(--spacing-6);
			margin: var(--spacing-5) 0;
		}
		
		.aiohm-license-help {
			background: var(--ohm-gray-50);
			border: 1px solid var(--ohm-gray-200);
			border-radius: var(--border-radius);
			padding: var(--spacing-5);
			margin: var(--spacing-5) 0;
		}
		
		.aiohm-license-help ul {
			list-style-type: disc;
			padding-left: var(--spacing-5);
		}
		
		.aiohm-license-help li {
			margin-bottom: var(--spacing-2);
		}
		
		.aiohm-license-info {
			background: var(--ohm-white);
			border: 1px solid var(--ohm-gray-200);
			border-radius: var(--border-radius);
			padding: var(--spacing-6);
			margin: var(--spacing-5) 0;
		}
		</style>
		<?php
	}

	/**
	 * Handle license activation AJAX
	 */
	public static function handle_license_activation() {
		// Verify nonce
		if ( ! isset( $_POST['license_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['license_nonce'] ) ), 'aiohm_license_activation' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'aiohm-booking-pro' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'aiohm-booking-pro' ) ) );
		}

		if ( ! isset( $_POST['license_key'] ) ) {
			wp_send_json_error( array( 'message' => __( 'License key is required', 'aiohm-booking-pro' ) ) );
		}

		$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );

		if ( empty( $license_key ) ) {
			wp_send_json_error( array( 'message' => __( 'License key is required', 'aiohm-booking-pro' ) ) );
		}

		// For Freemius integration, we redirect to their activation flow
		wp_send_json_success(
			array(
				'message'  => __( 'Redirecting to license activation...', 'aiohm-booking-pro' ),
				'redirect' => admin_url( 'admin.php?page=aiohm-booking-account' ),
			)
		);
	}
}