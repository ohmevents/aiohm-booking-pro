<?php
/**
 * Calendar Selection Partial Template
 *
 * Displays accommodation calendar for date selection.
 * Used by accommodations shortcode and mixed mode shortcode.
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get accommodation data and settings.
$global_settings    = get_option( 'aiohm_booking_settings', array() );
$template_helper    = AIOHM_BOOKING_Template_Helper::instance();
$accommodation_data = $template_helper->get_accommodation_data();
$pricing_data       = $template_helper->get_pricing_data();

// Get accommodation labels.
$accommodation_type = $global_settings['accommodation_type'] ?? 'room';
$singular           = aiohm_booking_get_accommodation_singular_name( $accommodation_type );
$plural             = aiohm_booking_get_accommodation_plural_name( $accommodation_type );
?>

<div class="aiohm-calendar-selection-section">
	<h3 class="aiohm-section-title">
		<?php
		/* translators: %s: accommodation type plural (e.g., Rooms, Cabins) */
		printf( esc_html__( 'Select Your %s', 'aiohm-booking-pro' ), esc_html( $plural ) );
		?>
	</h3>
	<p class="aiohm-section-description">
		<?php esc_html_e( 'Choose your dates and accommodation preferences', 'aiohm-booking-pro' ); ?>
	</p>
	
	<!-- Date Selection -->
	<div class="aiohm-date-selection">
		<div class="aiohm-form-row">
			<div class="aiohm-form-group aiohm-form-group--half">
				<label for="checkin_date" class="aiohm-form-label">
					<?php esc_html_e( 'Check-in Date', 'aiohm-booking-pro' ); ?> <span class="required">*</span>
				</label>
				<input type="date" id="checkin_date" name="checkin_date" class="aiohm-form-input aiohm-date-input" required>
			</div>
			
			<div class="aiohm-form-group aiohm-form-group--half">
				<label for="checkout_date" class="aiohm-form-label">
					<?php esc_html_e( 'Check-out Date', 'aiohm-booking-pro' ); ?> <span class="required">*</span>
				</label>
				<input type="date" id="checkout_date" name="checkout_date" class="aiohm-form-input aiohm-date-input" required>
			</div>
		</div>
		
		<div class="aiohm-nights-display" style="display: none;">
			<span class="aiohm-nights-text"><?php esc_html_e( 'Total nights:', 'aiohm-booking-pro' ); ?></span>
			<span class="aiohm-nights-count">0</span>
		</div>
	</div>
	
	<!-- Select Your Accommodations -->
	<?php if ( ! empty( $accommodation_data ) ) : ?>
	<div class="aiohm-accommodations-grid">
		<?php foreach ( $accommodation_data as $accommodation ) : ?>
			<div class="aiohm-accommodation-card" data-accommodation-id="<?php echo esc_attr( $accommodation['id'] ); ?>">
				<input type="checkbox" name="selected_accommodations[]" value="<?php echo esc_attr( $accommodation['id'] ); ?>" 
						id="accommodation_<?php echo esc_attr( $accommodation['id'] ); ?>" class="aiohm-accommodation-checkbox">
				
				<label for="accommodation_<?php echo esc_attr( $accommodation['id'] ); ?>" class="aiohm-accommodation-label">
					<?php if ( ! empty( $accommodation['image'] ) ) : ?>
						<div class="aiohm-accommodation-image">
							<img src="<?php echo esc_url( $accommodation['image'] ); ?>" 
								alt="<?php echo esc_attr( $accommodation['name'] ); ?>"
								loading="lazy">
						</div>
					<?php endif; ?>
					
					<div class="aiohm-accommodation-content">
						<h4 class="aiohm-accommodation-name"><?php echo esc_html( $accommodation['name'] ); ?></h4>
						
						<?php if ( ! empty( $accommodation['description'] ) ) : ?>
							<p class="aiohm-accommodation-description">
								<?php echo esc_html( wp_trim_words( $accommodation['description'], 15 ) ); ?>
							</p>
						<?php endif; ?>
						
						<div class="aiohm-accommodation-features">
							<?php if ( ! empty( $accommodation['capacity'] ) ) : ?>
								<span class="aiohm-feature">
									<span class="dashicons dashicons-groups"></span>
									<?php
									/* translators: %d: accommodation capacity */
									printf( esc_html__( 'Up to %d guests', 'aiohm-booking-pro' ), intval( $accommodation['capacity'] ) );
									?>
								</span>
							<?php endif; ?>
							
							<?php if ( ! empty( $accommodation['size'] ) ) : ?>
								<span class="aiohm-feature">
									<span class="dashicons dashicons-admin-home"></span>
									<?php echo esc_html( $accommodation['size'] ); ?>
								</span>
							<?php endif; ?>
						</div>
						
						<div class="aiohm-accommodation-price">
							<span class="aiohm-price-amount">
								<?php
								$currency = $global_settings['currency'] ?? 'EUR';
								$price    = floatval( $accommodation['price'] ?? $pricing_data['accommodation_price'] );
								echo esc_html( $currency . ' ' . number_format( $price, 2 ) );
								?>
							</span>
							<span class="aiohm-price-period"><?php esc_html_e( 'per night', 'aiohm-booking-pro' ); ?></span>
						</div>
						
						<div class="aiohm-accommodation-actions">
							<span class="aiohm-select-indicator"><?php esc_html_e( 'Select', 'aiohm-booking-pro' ); ?></span>
							<span class="aiohm-selected-indicator"><?php esc_html_e( 'Selected', 'aiohm-booking-pro' ); ?></span>
						</div>
					</div>
				</label>
			</div>
		<?php endforeach; ?>
	</div>
	
	<!-- Guest Count -->
	<div class="aiohm-guest-selection">
		<h4 class="aiohm-section-subtitle"><?php esc_html_e( 'Number of Guests', 'aiohm-booking-pro' ); ?></h4>
		<div class="aiohm-guest-controls">
			<div class="aiohm-guest-control">
				<label for="adults_count" class="aiohm-guest-label"><?php esc_html_e( 'Adults', 'aiohm-booking-pro' ); ?></label>
				<div class="aiohm-quantity-controls">
					<button type="button" class="aiohm-quantity-btn aiohm-adults-minus">-</button>
					<input type="number" id="adults_count" name="adults_count" value="1" min="1" max="20" class="aiohm-quantity-input">
					<button type="button" class="aiohm-quantity-btn aiohm-adults-plus">+</button>
				</div>
			</div>
			
			<div class="aiohm-guest-control">
				<label for="children_count" class="aiohm-guest-label"><?php esc_html_e( 'Children', 'aiohm-booking-pro' ); ?></label>
				<div class="aiohm-quantity-controls">
					<button type="button" class="aiohm-quantity-btn aiohm-children-minus">-</button>
					<input type="number" id="children_count" name="children_count" value="0" min="0" max="10" class="aiohm-quantity-input">
					<button type="button" class="aiohm-quantity-btn aiohm-children-plus">+</button>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Booking Summary -->
	<div class="aiohm-booking-summary" style="display: none;">
		<h4 class="aiohm-section-subtitle"><?php esc_html_e( 'Booking Summary', 'aiohm-booking-pro' ); ?></h4>
		<div class="aiohm-summary-content">
			<div class="aiohm-summary-dates">
				<span class="aiohm-summary-label"><?php esc_html_e( 'Dates:', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-summary-value aiohm-summary-dates-value">-</span>
			</div>
			<div class="aiohm-summary-accommodations">
				<span class="aiohm-summary-label"><?php echo esc_html( $plural ); ?>:</span>
				<span class="aiohm-summary-value aiohm-summary-accommodations-value">-</span>
			</div>
			<div class="aiohm-summary-guests">
				<span class="aiohm-summary-label"><?php esc_html_e( 'Guests:', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-summary-value aiohm-summary-guests-value">-</span>
			</div>
			<div class="aiohm-summary-total">
				<span class="aiohm-summary-label"><?php esc_html_e( 'Total:', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-summary-value aiohm-summary-total-value">€0.00</span>
			</div>
		</div>
	</div>
	
	<?php else : ?>
	<div class="aiohm-no-accommodations">
		<div class="aiohm-message aiohm-message--info">
			<h3>
			<?php
			/* translators: %s: accommodation type plural name */
			printf( esc_html__( 'No %s Available', 'aiohm-booking-pro' ), esc_html( $plural ) );
			?>
			</h3>
			<p><?php esc_html_e( 'There are currently no accommodations available for booking. Please check back later.', 'aiohm-booking-pro' ); ?></p>
		</div>
	</div>
	<?php endif; ?>
</div>

<?php
// Enqueue calendar selection script
wp_enqueue_script(
	'aiohm-booking-calendar-selection',
	AIOHM_BOOKING_URL . 'assets/js/calendar-selection.js',
	array(),
	AIOHM_BOOKING_VERSION,
	true
);

// Localize script with settings
wp_localize_script(
	'aiohm-booking-calendar-selection',
	'aiohm_calendar_selection',
	array(
		'currency'        => $global_settings['currency'] ?? 'EUR',
		'price_per_night' => floatval( $pricing_data['accommodation_price'] ?? 0 ),
	)
);
?>