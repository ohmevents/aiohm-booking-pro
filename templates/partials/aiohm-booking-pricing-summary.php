<?php
/**
 * Pricing Summary Card - Modular Component
 * Displays dynamic pricing calculation, totals, deposits, and early bird savings
 * Updates automatically based on selected events and accommodations
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get pricing configuration from global settings.
$global_settings = get_option( 'aiohm_booking_settings', array() );
$pricing         = get_option( 'aiohm_booking_pricing', array() );
$pricing_config  = $pricing['prices'] ?? array();
$currency        = $pricing_config['currency'] ?? $global_settings['currency'] ?? 'RON';
$deposit_percent = $pricing_config['deposit_percent'] ?? 50;
$earlybird_days  = $pricing_config['earlybird_days'] ?? 30;
?>

<div class="aiohm-pricing-summary-card" data-booking-context="<?php echo $is_events_context ? 'events' : 'accommodations'; ?>">
	<div class="aiohm-booking-shortcode-card-header">
	</div>

	<div class="aiohm-pricing-container" 
		data-currency="<?php echo esc_attr( $currency ); ?>" 
		data-deposit-percent="<?php echo esc_attr( $deposit_percent ); ?>" 
		data-earlybird-days="<?php echo esc_attr( $earlybird_days ); ?>">
		
		<!-- Selected Items Summary -->
		<div class="aiohm-selected-items-summary">
			<div class="aiohm-no-selection-message">
				<div class="aiohm-empty-state">
					<svg class="aiohm-empty-icon" width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
						<path d="M7,4V2A1,1 0 0,1 8,1H16A1,1 0 0,1 17,2V4H20A1,1 0 0,1 21,5V7A1,1 0 0,1 20,8H19V19A3,3 0 0,1 16,22H8A3,3 0 0,1 5,19V8H4A1,1 0 0,1 3,7V5A1,1 0 0,1 4,4H7M9,3V4H15V3H9M7,8V19A1,1 0 0,0 8,20H16A1,1 0 0,0 17,19V8H7Z"/>
					</svg>
					<h4><?php esc_html_e( 'No Items Selected', 'aiohm-booking-pro' ); ?></h4>
					<p><?php esc_html_e( 'Select an event or accommodation above to see pricing details.', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>
			
			<!-- Selected Events -->
			<div class="aiohm-selected-events" style="display: none;">
				<h4 class="aiohm-summary-section-title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
						<path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z"/>
					</svg>
					<?php esc_html_e( 'Selected Events', 'aiohm-booking-pro' ); ?>
				</h4>
				<div class="aiohm-event-summary-item">
					<div class="aiohm-event-summary-grid">
						<!-- Left Column: Event Type Badge and Title -->
						<div class="aiohm-event-info-column">
							<div class="aiohm-event-title-section">
								<div class="aiohm-event-type-badge" style="display: none;"></div>
								<h4 class="aiohm-event-title"></h4>
							</div>
							<div class="aiohm-event-date-time">
								<div class="aiohm-event-start-info">
									<strong><?php esc_html_e( 'START:', 'aiohm-booking-pro' ); ?></strong>
									<span class="aiohm-event-start-date"></span>
									<span class="aiohm-event-start-time"></span>
								</div>
								<div class="aiohm-event-end-info" style="display: none;">
									<strong><?php esc_html_e( 'END:', 'aiohm-booking-pro' ); ?></strong>
									<span class="aiohm-event-end-date"></span>
									<span class="aiohm-event-end-time"></span>
								</div>
							</div>
						</div>
						
						<!-- Right Column: Quantity and Pricing -->
						<div class="aiohm-event-pricing-column">
							<div class="aiohm-price-display">
								<span class="aiohm-current-price"></span>
								<span class="aiohm-original-price" style="display: none;"></span>
								<div class="aiohm-pricing-badges">
									<span class="aiohm-early-bird-badge" style="display: none;"><?php esc_html_e( 'EARLY BIRD', 'aiohm-booking-pro' ); ?></span>
									<span class="aiohm-special-pricing-badge" style="display: none;"><?php esc_html_e( 'SPECIAL PRICING', 'aiohm-booking-pro' ); ?></span>
								</div>
							</div>
							<div class="aiohm-ticket-quantity">
								<span class="aiohm-quantity-label"><?php esc_html_e( 'Tickets:', 'aiohm-booking-pro' ); ?></span>
								<span class="aiohm-quantity-value">1</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Selected Accommodations -->
			<div class="aiohm-selected-accommodations">
				<!-- Check-in/Check-out Display -->
				<div class="aiohm-booking-dates-display">
					<div class="aiohm-booking-detail-row">
						<span class="aiohm-booking-label"><?php esc_html_e( 'Check-in:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-booking-value" id="pricingCheckinDisplay"><?php esc_html_e( 'Select date from calendar', 'aiohm-booking-pro' ); ?></span>
					</div>
					<div class="aiohm-booking-detail-row">
						<span class="aiohm-booking-label"><?php esc_html_e( 'Check-out:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-booking-value" id="pricingCheckoutDisplay"><?php esc_html_e( 'Select check-in first', 'aiohm-booking-pro' ); ?></span>
					</div>
				</div>

				<div class="aiohm-accommodations-list">
					<!-- Accommodation items will be populated by JavaScript with the new two-column structure -->
				</div>
			</div>
		</div>

		<!-- Pricing Breakdown -->
		<div class="aiohm-pricing-breakdown">
			<!-- Subtotal -->
			<div class="aiohm-price-row aiohm-subtotal-row" style="display: none;">
				<span class="aiohm-price-label"><?php esc_html_e( 'Subtotal', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-price-value aiohm-subtotal-amount"><?php echo esc_html( $currency ); ?> 0.00</span>
			</div>

			<!-- Early Bird Discount -->
			<div class="aiohm-price-row aiohm-earlybird-row" style="display: none;" data-earlybird-days="<?php echo esc_attr( $earlybird_days ); ?>">
				<span class="aiohm-price-label aiohm-discount-label">
					<?php esc_html_e( 'Early Bird Discount', 'aiohm-booking-pro' ); ?>
					<small class="aiohm-earlybird-days-text">
						<?php if ( $earlybird_days > 0 ) : ?>
							<?php
							/* translators: %d: number of days for early bird discount */
							echo '(' . esc_html( sprintf( _n( '%d day', '%d days', $earlybird_days, 'aiohm-booking-pro' ), $earlybird_days ) ) . ')';
							?>
						<?php endif; ?>
					</small>
				</span>
				<span class="aiohm-price-value aiohm-discount-amount">-<?php echo esc_html( $currency ); ?> 0.00</span>
			</div>

			<!-- Special Pricing Adjustment -->
			<div class="aiohm-price-row aiohm-special-pricing-row" style="display: none;">
				<span class="aiohm-price-label aiohm-special-label">
					<?php esc_html_e( 'Special Pricing Applied', 'aiohm-booking-pro' ); ?>
					<small class="aiohm-special-pricing-note"><?php esc_html_e( '(Private events)', 'aiohm-booking-pro' ); ?></small>
				</span>
				<span class="aiohm-price-value aiohm-special-amount"><?php echo esc_html( $currency ); ?> 0.00</span>
			</div>

			<!-- Nightly Pricing Breakdown -->
			<div class="aiohm-nightly-breakdown" style="display: none;">
				<h5 class="aiohm-nightly-breakdown-title"><?php esc_html_e( 'Nightly Breakdown', 'aiohm-booking-pro' ); ?></h5>
				<div class="aiohm-nightly-breakdown-list">
					<!-- Individual night pricing will be populated by JavaScript -->
				</div>
			</div>

			<!-- Total -->
			<div class="aiohm-price-row aiohm-total-row">
				<span class="aiohm-price-label-main"><?php esc_html_e( 'Total', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-price-value-main aiohm-total-amount"><?php echo esc_html( $currency ); ?> 0.00</span>
			</div>

			<!-- Deposit Required -->
			<div class="aiohm-price-row aiohm-deposit-row" style="display: none;">
				<span class="aiohm-price-label aiohm-deposit-label">
					<?php esc_html_e( 'Deposit Required', 'aiohm-booking-pro' ); ?>
					<small>(<?php echo esc_html( $deposit_percent ); ?>%)</small>
				</span>
				<span class="aiohm-price-value aiohm-deposit-amount"><?php echo esc_html( $currency ); ?> 0.00</span>
			</div>

			<!-- Remaining Balance -->
			<div class="aiohm-price-row aiohm-balance-row" style="display: none;">
				<span class="aiohm-price-label"><?php esc_html_e( 'Remaining Balance', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-price-value aiohm-balance-amount"><?php echo esc_html( $currency ); ?> 0.00</span>
			</div>
		</div>

		<!-- Action Buttons -->
		<div class="aiohm-pricing-actions">
			<button type="submit" class="aiohm-booking-btn aiohm-booking-btn-primary" disabled>
				<span class="aiohm-btn-content">
					<svg class="aiohm-btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
						<path d="M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/>
					</svg>
					<span class="aiohm-btn-text"><?php esc_html_e( 'Complete Booking', 'aiohm-booking-pro' ); ?></span>
				</span>
				<span class="aiohm-btn-arrow">→</span>
			</button>
			
			<div class="aiohm-booking-security-badges">
				<span class="aiohm-security-features">
					<?php esc_html_e( 'Secure Booking', 'aiohm-booking-pro' ); ?> • 
					<?php esc_html_e( 'No Hidden Fees', 'aiohm-booking-pro' ); ?> • 
					<?php esc_html_e( 'Flexible Cancellation', 'aiohm-booking-pro' ); ?>
				</span>
			</div>
		</div>
	</div>
</div>