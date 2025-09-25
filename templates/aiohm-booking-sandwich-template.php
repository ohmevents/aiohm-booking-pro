<?php
/**
 * AIOHM Booking Sandwich Template - Tab-Based Navigation
 *
 * Modern 3-step tab-based booking form with smooth animations:
 * - Step 1: Event/Accommodation Selection
 * - Step 2: Contact Information + Pricing Summary
 * - Step 3: Checkout with Payment Options
 *
 * Features:
 * - Unified .aiohm-booking-* CSS class pattern
 * - Smooth sandwich closing/opening animations
 * - Responsive tab navigation
 * - Context-aware content display
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Context configuration for tab-based booking
$shortcode_type      = $shortcode_type ?? 'full'; // 'full', 'events', 'accommodations'
$is_events_context   = ( $shortcode_type === 'events' );
$show_accommodations = ( $shortcode_type === 'full' || $shortcode_type === 'accommodations' );
$show_events         = ( $shortcode_type === 'full' || $shortcode_type === 'events' );

// Get settings and determine enabled modules
$main_settings = get_option( 'aiohm_booking_settings', array() );
$form_settings = $is_events_context
	? get_option( 'aiohm_booking_tickets_form_settings', array() )
	: get_option( 'aiohm_booking_form_settings', array() );

// Brand color configuration
$unified_brand_color = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? null;
$brand_color         = $unified_brand_color ?? $form_settings['form_primary_color'] ?? '#457d59';

// Module availability
$tickets_enabled        = ! empty( $main_settings['enable_tickets'] );
$accommodations_enabled = ! empty( $main_settings['enable_accommodations'] );

// Final display logic based on shortcode type and module status
switch ( $shortcode_type ) {
	case 'events':
		$show_events         = $tickets_enabled;
		$show_accommodations = false;
		break;
	case 'accommodations':
		$show_events         = false;
		$show_accommodations = $accommodations_enabled;
		break;
	case 'full':
	default:
		$show_events         = $tickets_enabled;
		$show_accommodations = $accommodations_enabled;
		break;
}

// Determine starting step based on available modules
$starting_step = ( $show_events || $show_accommodations ) ? 1 : 2;

// Make context available globally for included templates
global $aiohm_booking_events_context;
$aiohm_booking_events_context = $is_events_context;
?>

<!-- AIOHM Booking Tab-Based Sandwich Form -->
<div class="aiohm-booking-sandwich-container" 
	data-shortcode-type="<?php echo esc_attr( $shortcode_type ); ?>"
	data-brand-color="<?php echo esc_attr( $brand_color ); ?>"
	style="--aiohm-brand-color: <?php echo esc_attr( $brand_color ); ?>">

	<!-- Tab Navigation Header -->
	<div class="aiohm-booking-tab-navigation">
		<?php
		$tab_counter = 1;
		?>
		
		<!-- Step 1: Selection (conditionally shown based on enabled modules) -->
		<?php if ( $show_events || $show_accommodations ) : ?>
		<div class="aiohm-booking-tab-item aiohm-booking-tab-active" data-step="1" data-tab="selection">
			<span class="aiohm-booking-tab-number"><?php echo esc_html( $tab_counter++ ); ?></span>
		</div>
		<?php endif; ?>
		
		<div class="aiohm-booking-tab-item <?php echo ( $starting_step === 2 ) ? 'aiohm-booking-tab-active' : ''; ?>" data-step="2" data-tab="details">
			<span class="aiohm-booking-tab-number"><?php echo esc_html( $tab_counter++ ); ?></span>
		</div>
		
		<div class="aiohm-booking-tab-item" data-step="3" data-tab="checkout">
			<span class="aiohm-booking-tab-number"><?php echo esc_html( $tab_counter ); ?></span>
		</div>
	</div>

	<!-- Main Form Container -->
	<form id="aiohm-booking-sandwich-form" class="aiohm-booking-sandwich-form" method="post"
	<?php if ( isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) : ?>
		onsubmit="event.preventDefault(); alert('Form submission is disabled in preview mode.'); return false;"
	<?php else : ?>
		onsubmit="return false;" 
	<?php endif; ?>
	>
	
		<!-- Sandwich Body with Animation Container -->
		<div class="aiohm-booking-sandwich-body" data-current-step="<?php echo esc_attr( $starting_step ); ?>">
			
			<!-- Step 1: Selection (Events and/or Accommodations) - Only shown if modules are enabled -->
			<?php if ( $show_events || $show_accommodations ) : ?>
			<div class="aiohm-booking-step-content aiohm-booking-step-active" data-step="1">
				<div class="aiohm-booking-step-inner">
					
					<?php if ( $show_events ) : ?>
					<div class="aiohm-booking-selection-section aiohm-booking-event-selection">
						<?php include AIOHM_BOOKING_DIR . 'templates/partials/aiohm-booking-event-selection.php'; ?>
					</div>
					<?php endif; ?>
					
					<?php if ( $show_accommodations ) : ?>
					<div class="aiohm-booking-selection-section aiohm-booking-accommodation-selection">
						<?php include AIOHM_BOOKING_DIR . 'templates/partials/aiohm-booking-accommodation-selection.php'; ?>
					</div>
					<?php endif; ?>
					
				</div>
			</div>
			<?php endif; ?>
			
			<!-- Step 2: Contact Information + Pricing Summary -->
			<div class="aiohm-booking-step-content <?php echo ( $starting_step === 2 ) ? 'aiohm-booking-step-active' : 'aiohm-booking-step-hidden'; ?>" data-step="2">
				<div class="aiohm-booking-step-inner">
					
					<div class="aiohm-booking-pricing-section">
						<h3 class="aiohm-booking-section-title">
							<?php esc_html_e( 'Booking Summary', 'aiohm-booking-pro' ); ?>
						</h3>
						<?php require AIOHM_BOOKING_DIR . 'templates/partials/aiohm-booking-pricing-summary.php'; ?>
					</div>
					
					<div class="aiohm-booking-contact-section">
						<h3 class="aiohm-booking-section-title">
							<?php esc_html_e( 'Contact Information', 'aiohm-booking-pro' ); ?>
						</h3>
						<?php
						$contact_form_vars = array(
							'is_events_context' => $is_events_context,
							'form_settings'     => $form_settings,
							'opts'              => $main_settings,
						);
						extract( $contact_form_vars );
						require AIOHM_BOOKING_DIR . 'templates/partials/aiohm-booking-contact-form.php';
						?>
					</div>
					
				</div>
			</div>
			
			<!-- Step 3: Checkout -->
			<div class="aiohm-booking-step-content aiohm-booking-step-hidden" data-step="3">
				<div class="aiohm-booking-step-inner">
					
					<div class="aiohm-booking-checkout-section">
						<h3 class="aiohm-booking-section-title">
							<?php esc_html_e( 'Complete Your Booking', 'aiohm-booking-pro' ); ?>
						</h3>
						
						<?php
						// Check if user has premium access
						$is_premium_user = false;
						if ( function_exists( 'aiohm_booking_fs' ) ) {
							$is_premium_user = aiohm_booking_fs()->can_use_premium_code__premium_only();
						}

						?>
						
						<?php if ( $is_premium_user ) : ?>
							<!-- Premium User: Payment Options -->
							<div class="aiohm-booking-payment-options">
								<!-- Payment Method Selection -->
								<div class="aiohm-payment-method-section">
									<div class="aiohm-payment-options aiohm-payment-options-grid">
										<label class="aiohm-payment-option">
											<span class="aiohm-payment-amount-label">
												<span class="aiohm-payment-amount-text"><?php esc_html_e( 'Total to Pay:', 'aiohm-booking-pro' ); ?></span>
												<span class="aiohm-payment-amount-value" id="aiohm-full-payment-amount"><?php echo esc_html( $currency ?? 'RON' ); ?> 0.00</span>
											</span>
											<input type="radio" name="payment_method_type" value="full" checked>
											<span class="aiohm-payment-option-content">
												<span class="aiohm-payment-title"><?php esc_html_e( 'Pay Full Amount', 'aiohm-booking-pro' ); ?></span>
												<span class="aiohm-payment-description"><?php esc_html_e( 'Complete payment now', 'aiohm-booking-pro' ); ?></span>
											</span>
										</label>
										
										<label class="aiohm-payment-option">
											<span class="aiohm-payment-amount-label">
												<span class="aiohm-payment-amount-text"><?php esc_html_e( 'Total Deposit:', 'aiohm-booking-pro' ); ?></span>
												<span class="aiohm-payment-amount-value" id="aiohm-deposit-payment-amount"><?php echo esc_html( $currency ?? 'RON' ); ?> 0.00</span>
											</span>
											<input type="radio" name="payment_method_type" value="deposit">
											<span class="aiohm-payment-option-content">
												<span class="aiohm-payment-title"><?php esc_html_e( 'Pay Deposit Only', 'aiohm-booking-pro' ); ?></span>
												<span class="aiohm-payment-description"><?php esc_html_e( 'Secure your booking with a deposit', 'aiohm-booking-pro' ); ?></span>
											</span>
										</label>
									</div>
								</div>
								
								<div class="aiohm-booking-payment-methods">
									<h4><?php esc_html_e( 'Choose Payment Method', 'aiohm-booking-pro' ); ?></h4>
									
									<div class="aiohm-booking-payment-buttons">
										<button type="button" class="aiohm-booking-btn aiohm-booking-btn-payment aiohm-booking-btn-stripe" id="aiohm-stripe-payment">
											<span class="aiohm-booking-btn-icon">💳</span>
											<span class="aiohm-booking-btn-text"><?php esc_html_e( 'Pay with Stripe', 'aiohm-booking-pro' ); ?></span>
										</button>
									</div>
								</div>
							</div>
							
						<?php else : ?>
							<!-- Free User: Notification/Invoice -->
							<div class="aiohm-booking-free-checkout">
								<div class="aiohm-booking-free-notice">
									<div class="aiohm-booking-notice-icon">📧</div>
									<h4><?php esc_html_e( 'Booking Confirmation', 'aiohm-booking-pro' ); ?></h4>
									<p><?php esc_html_e( 'Your booking request has been received. You will receive a confirmation email with payment instructions.', 'aiohm-booking-pro' ); ?></p>
								</div>
								
								<div class="aiohm-booking-invoice-preview">
									<div class="aiohm-booking-invoice-content" id="aiohm-invoice-preview">
										<!-- Invoice content will be generated here -->
									</div>
								</div>
								
								<div class="aiohm-booking-free-actions">
									<button type="button" class="aiohm-booking-btn aiohm-booking-btn-primary" id="aiohm-send-notification">
										<span class="aiohm-booking-btn-text"><?php esc_html_e( 'Send Invoice', 'aiohm-booking-pro' ); ?></span>
									</button>
									
									<div class="aiohm-booking-upgrade-prompt">
										<p><?php esc_html_e( 'Upgrade to Pro for instant payment processing with Stripe', 'aiohm-booking-pro' ); ?></p>
										<?php
										$upgrade_url = aiohm_booking_fs()->is_paying()
											? aiohm_booking_fs()->_get_admin_page_url('account')
											: aiohm_booking_fs()->get_upgrade_url();
										?>
										<a href="<?php echo esc_url( $upgrade_url ); ?>" class="aiohm-booking-btn aiohm-booking-btn-secondary">
											<?php echo aiohm_booking_fs()->is_paying() ? esc_html__( 'Manage License', 'aiohm-booking-pro' ) : esc_html__( 'Upgrade to Pro', 'aiohm-booking-pro' ); ?>
										</a>
									</div>
								</div>
							</div>
						<?php endif; ?>
						
						<!-- Processing Status -->
						<div class="aiohm-booking-processing-status" id="aiohm-processing-status" style="display: none;">
							<div class="aiohm-booking-status-message">
								<div class="aiohm-booking-spinner"></div>
								<span><?php esc_html_e( 'Processing your booking...', 'aiohm-booking-pro' ); ?></span>
							</div>
						</div>
						
					</div>
					
				</div>
			</div>
			
		</div>
		
	</form>
	
	<!-- Navigation Footer -->
	<div class="aiohm-booking-navigation-footer">
		<div class="aiohm-booking-navigation-buttons">
			<button type="button" class="aiohm-booking-btn aiohm-booking-btn-prev aiohm-booking-btn-disabled" disabled style="display: none;">
				<span class="aiohm-booking-btn-icon">‹</span>
				<span class="aiohm-booking-btn-text"><?php esc_html_e( 'Previous', 'aiohm-booking-pro' ); ?></span>
			</button>
			
			<button type="button" class="aiohm-booking-btn aiohm-booking-btn-next">
				<span class="aiohm-booking-btn-text"><?php esc_html_e( 'Continue', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-booking-btn-icon">›</span>
			</button>
		</div>
	</div>
	
</div>