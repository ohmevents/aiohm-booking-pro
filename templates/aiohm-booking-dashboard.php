<?php
/**
 * Dashboard Template for AIOHM Booking
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Handle force sync action
if ( isset( $_GET['force_sync'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aiohm_force_sync' ) ) {
	if ( function_exists( 'aiohm_booking_fs' ) ) {
		$fs = aiohm_booking_fs();
		// Force sync with Freemius
		$fs->_sync_license();
		$fs->_sync_plan();

		// Clear any caches
		if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
			AIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
		}

		// Show success message
		echo '<div class="notice notice-success is-dismissible"><p><strong>License sync completed!</strong> Please refresh the page.</p></div>';
	}
}

// Check if user has premium access (define early to avoid undefined variable warnings)
$is_premium = false;
if ( function_exists( 'aiohm_booking_fs' ) ) {
	$fs         = aiohm_booking_fs();
	$is_premium = $fs->is_premium() || $fs->is_paying();
}

// Handle license activation action
if ( isset( $_GET['activate_license'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aiohm_activate_license' ) ) {
	if ( function_exists( 'aiohm_booking_fs' ) ) {
		$fs = aiohm_booking_fs();

		// Since you already have the license, try to manually force premium status
		// This is a workaround for Freemius detection issues
		if ( ! $fs->is_premium() && $fs->is_paying() ) {
			// Clear caches and force refresh
			if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
				AIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
			}

			// Force sync
			$fs->_sync_license();
			$fs->_sync_plan();

			echo '<div class="notice notice-info is-dismissible">
				<p><strong>License sync attempted.</strong> 
				Since you already have a paid license but it\'s not detected as premium, please:</p>
				<ol>
					<li>Go to <a href="' . esc_url( admin_url( 'admin.php?page=aiohm-booking-account' ) ) . '">Freemius Account</a></li>
					<li>Check if your license is properly activated there</li>
					<li>If needed, deactivate and reactivate your license</li>
					<li>Or contact Freemius support if the issue persists</li>
				</ol>
			</div>';
		} else {
			echo '<div class="notice notice-success is-dismissible"><p><strong>License already active!</strong></p></div>';
		}
	}
}

// Ensure all variables are defined with safe defaults
$total_revenue        = $total_revenue ?? 0;
$total_orders_30_days = $total_orders_30_days ?? 0;
$pending_orders       = $pending_orders ?? 0;
$paid_orders          = $paid_orders ?? 0;
$conversion_rate      = $conversion_rate ?? 0;
$avg_order_value      = $avg_order_value ?? 0;
$all_time_orders      = $all_time_orders ?? 0;
$currency             = $currency ?? 'USD';

// Helper function for safe number formatting
$safe_number_format = function ( $number, $decimals = 0 ) {
	$number = is_numeric( $number ) ? (float) $number : 0;
	return number_format( $number, $decimals );
};
?>

<div class="wrap aiohm-booking-admin">
	<div class="aiohm-booking-admin-header">
		<div class="aiohm-booking-admin-header-content">
			<div class="aiohm-booking-admin-logo">
				<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
			</div>
			<div class="aiohm-booking-admin-header-text">
				<h1><?php esc_html_e( 'AIOHM Booking Dashboard', 'aiohm-booking-pro' ); ?></h1>
				<p class="aiohm-booking-admin-tagline"><?php esc_html_e( 'Welcome to your modular booking management system.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Statistics Cards -->
	<div class="aiohm-stats-container">
		<!-- First Row - Primary Stats (3 per row) -->
		<div class="aiohm-stats-grid aiohm-stats-row">
			<div class="aiohm-stat-card">
				<div class="aiohm-stat-icon">
					<span class="dashicons dashicons-money-alt"></span>
				</div>
				<div class="aiohm-stat-content">
					<h3><?php echo esc_html( $safe_number_format( $total_revenue, 2 ) ); ?> <?php echo esc_html( $currency ); ?></h3>
					<p><?php esc_html_e( 'Revenue (30 days)', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>

			<div class="aiohm-stat-card">
				<div class="aiohm-stat-icon">
					<span class="dashicons dashicons-clipboard"></span>
				</div>
				<div class="aiohm-stat-content">
					<h3><?php echo esc_html( $total_orders_30_days ); ?></h3>
					<p><?php esc_html_e( 'Orders (30 days)', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>

			<div class="aiohm-stat-card">
				<div class="aiohm-stat-icon">
					<span class="dashicons dashicons-clock"></span>
				</div>
				<div class="aiohm-stat-content">
					<h3><?php echo esc_html( $pending_orders ); ?></h3>
					<p><?php esc_html_e( 'Pending Orders', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Second Row - Secondary Stats (3 per row) -->
		<div class="aiohm-stats-grid aiohm-stats-row">
			<div class="aiohm-stat-card">
				<div class="aiohm-stat-icon">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<div class="aiohm-stat-content">
					<h3><?php echo esc_html( $paid_orders ); ?></h3>
					<p><?php esc_html_e( 'Paid Orders', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>

			<div class="aiohm-stat-card">
				<div class="aiohm-stat-icon">
					<span class="dashicons dashicons-chart-line"></span>
				</div>
				<div class="aiohm-stat-content">
					<h3><?php echo esc_html( $safe_number_format( $conversion_rate, 1 ) ); ?>%</h3>
					<p><?php esc_html_e( 'Conversion Rate', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>

			<div class="aiohm-stat-card">
				<div class="aiohm-stat-icon">
					<span class="dashicons dashicons-groups"></span>
				</div>
				<div class="aiohm-stat-content">
					<h3><?php echo esc_html( $safe_number_format( $avg_order_value, 2 ) ); ?> <?php echo esc_html( $currency ); ?></h3>
					<p><?php esc_html_e( 'Avg Order Value', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<!-- License Notice for Free Users -->
	<?php if ( ! $is_premium ) : ?>
	<div class="aiohm-dashboard-section aiohm-license-banner" style="background: linear-gradient(135deg, var(--aiohm-brand-color, #457d59) 0%, #2d5233 100%); color: white; padding: 30px; margin: 20px 0; border-radius: 8px; text-align: center;">
		<h2 style="margin: 0 0 10px 0; font-size: 28px;">🚀 <?php esc_html_e( 'Unlock Pro Features', 'aiohm-booking-pro' ); ?></h2>
		<p style="margin: 0 0 20px 0; font-size: 16px; color: #cccccc;"><?php esc_html_e( 'Get Stripe payments, advanced features, and premium support with a Pro license.', 'aiohm-booking-pro' ); ?></p>
		<div style="display: flex; gap: 15px; justify-content: center; align-items: center; flex-wrap: wrap;">
			<?php if ( function_exists( 'aiohm_booking_fs' ) ) : ?>
			<a href="https://checkout.freemius.com/plugin/20270/plan/33657/" target="_blank" class="aiohm-license-button" style="background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
				<?php esc_html_e( 'Buy Pro License Now', 'aiohm-booking-pro' ); ?>
			</a>
			<?php endif; ?>
			<a href="https://freemius.com/wordpress/20270/" target="_blank" style="color: white; text-decoration: underline; font-size: 14px;">
				<?php esc_html_e( 'View Pricing & Features', 'aiohm-booking-pro' ); ?>
			</a>
		</div>
		<div style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
			<?php esc_html_e( 'Secure payment processing • Priority support • Advanced integrations', 'aiohm-booking-pro' ); ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Getting Started Section (always visible) -->
	<?php
	// Always show getting started section, but customize content based on pro status
	// $is_premium is already defined at the top of the file
	?>
	<div class="aiohm-dashboard-section aiohm-getting-started">
		<h2><?php esc_html_e( 'Getting Started with AIOHM Booking', 'aiohm-booking-pro' ); ?></h2>
		<p class="aiohm-getting-started-intro"><?php esc_html_e( 'Follow these steps to set up your booking system:', 'aiohm-booking-pro' ); ?></p>
		
		<div class="aiohm-setup-steps">
			<div class="aiohm-setup-step">
				<div class="aiohm-step-number">1</div>
				<div class="aiohm-step-content">
					<h3><?php esc_html_e( 'Configure Settings', 'aiohm-booking-pro' ); ?></h3>
					<p><?php esc_html_e( 'Set up your booking preferences, currency, and basic configuration.', 'aiohm-booking-pro' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ); ?>" class="aiohm-step-button">
						<?php esc_html_e( 'Go to Settings', 'aiohm-booking-pro' ); ?>
					</a>
				</div>
			</div>

			<div class="aiohm-setup-step">
				<div class="aiohm-step-number">2</div>
				<div class="aiohm-step-content">
					<h3><?php esc_html_e( 'Set Up Calendar', 'aiohm-booking-pro' ); ?></h3>
					<p><?php esc_html_e( 'Configure your event calendar and availability settings.', 'aiohm-booking-pro' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-calendar' ) ); ?>" class="aiohm-step-button">
						<?php esc_html_e( 'Calendar Settings', 'aiohm-booking-pro' ); ?>
					</a>
				</div>
			</div>

			<div class="aiohm-setup-step">
				<div class="aiohm-step-number">3</div>
				<div class="aiohm-step-content">
					<h3><?php esc_html_e( 'Create Booking Forms', 'aiohm-booking-pro' ); ?></h3>
					<p><?php esc_html_e( 'Add booking forms to your pages using shortcodes.', 'aiohm-booking-pro' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-shortcodes' ) ); ?>" class="aiohm-step-button">
						<?php esc_html_e( 'View Shortcodes', 'aiohm-booking-pro' ); ?>
					</a>
				</div>
			</div>

			<?php if ( ! $is_premium ) : ?>
			<div class="aiohm-setup-step aiohm-step-premium">
				<div class="aiohm-step-number">4</div>
				<div class="aiohm-step-content">
					<h3><?php esc_html_e( 'Accept Payments', 'aiohm-booking-pro' ); ?> <span class="pro-badge">PRO</span></h3>
					<p><?php esc_html_e( 'Enable Stripe to accept secure online payments.', 'aiohm-booking-pro' ); ?></p>
					<?php if ( function_exists( 'aiohm_booking_fs' ) ) : ?>
					<?php
					$upgrade_url = aiohm_booking_fs()->is_paying()
						? aiohm_booking_fs()->_get_admin_page_url('account')
						: 'https://checkout.freemius.com/plugin/20270/plan/33657/';
					$button_text = aiohm_booking_fs()->is_paying()
						? esc_html__( 'Manage License', 'aiohm-booking-pro' )
						: esc_html__( 'Buy Pro License', 'aiohm-booking-pro' );
					?>
					<div class="aiohm-license-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
						<h4 style="margin: 0 0 10px 0; color: #856404;">🔑 <?php esc_html_e( 'Pro License Required', 'aiohm-booking-pro' ); ?></h4>
						<p style="margin: 0 0 15px 0; color: #856404;"><?php esc_html_e( 'To unlock Stripe payments and premium features, you need a Pro license.', 'aiohm-booking-pro' ); ?></p>
						<div style="display: flex; gap: 10px; align-items: center;">
							<a href="<?php echo esc_url( $upgrade_url ); ?>" class="aiohm-step-button aiohm-step-button-premium" style="background: #007cba; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold;">
								<?php echo esc_html( $button_text ); ?>
							</a>
							<a href="https://freemius.com/wordpress/20270/" target="_blank" style="color: #007cba; text-decoration: none; font-size: 14px;">
								<?php esc_html_e( 'Learn More →', 'aiohm-booking-pro' ); ?>
							</a>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php else : ?>
			<div class="aiohm-setup-step">
				<div class="aiohm-step-number">4</div>
				<div class="aiohm-step-content">
					<h3><?php esc_html_e( 'Configure Payments', 'aiohm-booking-pro' ); ?></h3>
					<p><?php esc_html_e( 'Set up Stripe to accept secure online payments.', 'aiohm-booking-pro' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings&tab=payments' ) ); ?>" class="aiohm-step-button">
						<?php esc_html_e( 'Payment Settings', 'aiohm-booking-pro' ); ?>
					</a>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="aiohm-dashboard-section">
		<h2><?php esc_html_e( 'Quick Actions', 'aiohm-booking-pro' ); ?></h2>
		<div class="aiohm-quick-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-tickets' ) ); ?>" class="aiohm-action-button">
				<span class="dashicons dashicons-tickets-alt"></span>
				<?php esc_html_e( 'Event Tickets', 'aiohm-booking-pro' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-accommodations' ) ); ?>" class="aiohm-action-button">
				<span class="dashicons dashicons-building"></span>
				<?php esc_html_e( 'Accommodations', 'aiohm-booking-pro' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-calendar' ) ); ?>" class="aiohm-action-button">
				<span class="dashicons dashicons-calendar-alt"></span>
				<?php esc_html_e( 'Calendar', 'aiohm-booking-pro' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-orders' ) ); ?>" class="aiohm-action-button">
				<span class="dashicons dashicons-cart"></span>
				<?php esc_html_e( 'Orders', 'aiohm-booking-pro' ); ?>
			</a>
		</div>
	</div>

	<!-- AI Content Section - Rendered by AI Analytics module -->
	<?php
	// Render AI sections through the AI Analytics module
	if ( class_exists( 'AIOHM_BOOKING_Module_AI_Analytics' ) ) {
		// Prepare statistics array for the AI module
		$dashboard_stats = array(
			'total_revenue'        => $total_revenue,
			'total_orders_30_days' => $total_orders_30_days,
			'all_time_orders'      => $all_time_orders,
			'avg_order_value'      => $avg_order_value,
		);

		// Call the AI Analytics module to render dashboard sections
		AIOHM_BOOKING_Module_AI_Analytics::render_dashboard_sections(
			$dashboard_stats,
			$default_ai_provider,
			$currency,
			$safe_number_format
		);
	}
	?>
</div>
