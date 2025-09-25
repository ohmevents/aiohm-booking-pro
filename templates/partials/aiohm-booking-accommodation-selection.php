<?php
/**
 * Accommodation Selection Layer Template
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get selected dates from POST data (for AJAX updates) - nonce verification handled by calling AJAX handler
$selected_arrival_date = isset( $_POST['arrival_date'] ) ? sanitize_text_field( wp_unslash( $_POST['arrival_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by calling AJAX handler
$selected_departure_date = isset( $_POST['departure_date'] ) ? sanitize_text_field( wp_unslash( $_POST['departure_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by calling AJAX handler

// Calculate duration from selected dates
$calculated_duration = 1; // Default
if ( ! empty( $selected_arrival_date ) && ! empty( $selected_departure_date ) ) {
	$start = new DateTime( $selected_arrival_date );
	$end = new DateTime( $selected_departure_date );
	$interval = $start->diff( $end );
	$calculated_duration = $interval->days;
	if ( $calculated_duration < 1 ) {
		$calculated_duration = 1;
	}
}

// Get accommodations data and settings.
$global_settings = get_option( 'aiohm_booking_settings', array() );

// Get the maximum number of accommodations from settings
$available_accommodations = intval( $global_settings['available_accommodations'] ?? 10 );

// Ensure accommodation posts are synchronized with settings
// Get current accommodation count
$current_accommodation_count = wp_count_posts( 'aiohm_accommodation' );
$current_total               = ( $current_accommodation_count->publish ?? 0 ) + ( $current_accommodation_count->draft ?? 0 );

// If there's a mismatch, we need to sync (but only if we have access to the module)
if ( $current_total != $available_accommodations && class_exists( 'AIOHM_BOOKING_Module_Accommodation' ) ) {
	// Get all accommodation posts for cleanup
	$all_accommodations = get_posts(
		array(
			'post_type'   => 'aiohm_accommodation',
			'post_status' => array( 'publish', 'draft' ),
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		)
	);

	// If we have more than needed, delete the excess
	if ( count( $all_accommodations ) > $available_accommodations ) {
		$excess_accommodations = array_slice( $all_accommodations, $available_accommodations );
		foreach ( $excess_accommodations as $excess_accommodation ) {
			wp_delete_post( $excess_accommodation->ID, true ); // Force delete
		}
	}
}

// Get accommodations from Custom Post Type, limited by settings
$accommodations_query = new WP_Query(
	array(
		'post_type'              => 'aiohm_accommodation',
		'posts_per_page'         => $available_accommodations,
		'orderby'                => 'menu_order',
		'order'                  => 'ASC',
		'post_status'            => array( 'publish', 'draft' ),
		'fields'                 => 'all',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	)
);

$accommodations = array();
if ( $accommodations_query->have_posts() ) {
	foreach ( $accommodations_query->posts as $post ) {
		$accommodations[] = array(
			'id'              => $post->ID,
			'title'           => $post->post_title,
			'description'     => $post->post_content,
			'price'           => get_post_meta( $post->ID, '_aiohm_booking_accommodation_price', true ),
			'earlybird_price' => get_post_meta( $post->ID, '_aiohm_booking_accommodation_earlybird_price', true ),
			'type'            => get_post_meta( $post->ID, '_aiohm_booking_accommodation_type', true ) ?: 'room',
		);
	}
}

if ( ! empty( $selected_arrival_date ) && ! empty( $selected_departure_date ) && class_exists( 'AIOHM_BOOKING_Accommodation_Service' ) ) {
	$filtered_accommodations = array();
	foreach ( $accommodations as $accommodation ) {
		$is_available = AIOHM_BOOKING_Accommodation_Service::is_accommodation_available_for_range( $accommodation['id'], $selected_arrival_date, $selected_departure_date );
		if ( $is_available ) {
			$filtered_accommodations[] = $accommodation;
		}
	}
	$accommodations = $filtered_accommodations;
}

$pricing       = get_option( 'aiohm_booking_pricing', array() );
$product_names = get_option( 'aiohm_booking_product_names', array() );

// Get accommodation module settings for default price
$accommodation_settings = get_option( 'aiohm_booking_accommodation_settings', array() );

// Get early bird settings from helper
$early_bird_settings      = AIOHM_BOOKING_Early_Bird_Helper::get_accommodation_early_bird_settings();
$enable_early_bird        = $early_bird_settings['enabled'];
$early_bird_days          = $early_bird_settings['days'];
$default_early_bird_price = $early_bird_settings['default_price'];

// Set defaults.
$singular = $product_names['singular_cap'] ?? 'Room';
$plural   = $product_names['plural_cap'] ?? 'Rooms';

// Get currency from general settings (not pricing settings) - same as event selection
$global_settings = get_option( 'aiohm_booking_settings', array() );
$currency        = $global_settings['currency'] ?? 'USD';

$p = array( 'currency' => $currency );
// Get base price from accommodation module settings first, then fallback to accommodation_price from pricing
$base_acc_price = floatval( $accommodation_settings['default_price'] ?? $pricing['accommodation_price'] ?? 100 );

// Get calendar colors for CSS variables.
$admin_colors    = get_option( 'aiohm_booking_calendar_colors', array() );
$default_colors  = array(
	'free'     => '#ffffff',
	'booked'   => '#e74c3c',
	'pending'  => '#f39c12',
	'external' => '#6c5ce7',
	'blocked'  => '#4b5563',
	'special'  => '#007cba',
	'private'  => '#28a745',
);
$calendar_colors = array_merge( $default_colors, $admin_colors );

// Get brand color for calendar styling
$main_settings = get_option( 'aiohm_booking_settings', array() );
$brand_color   = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? '#457d59';

// Accommodation selection CSS is now consolidated in aiohm-booking-shortcodes.css
// No need to enqueue separate CSS file

// Generate dynamic calendar colors CSS for both variables and legend
$dynamic_calendar_css = ':root { --aiohm-brand-color: ' . esc_attr( $brand_color ) . '; } ';
foreach ( $calendar_colors as $status => $color ) {
	// Generate CSS variables for calendar cells (--aiohm-calendar-*)
	$dynamic_calendar_css .= '--aiohm-calendar-' . esc_attr( $status ) . '-color: ' . esc_attr( $color ) . '; ';
	// Generate legend colors
	$dynamic_calendar_css .= '.aiohm-calendar-legend .legend-' . esc_attr( $status ) . ' { background-color: ' . esc_attr( $color ) . '; } ';
}
wp_add_inline_style( 'aiohm-booking-accommodation-selection', $dynamic_calendar_css );

// Get form settings for text and brand colors - match sandwich header logic
$form_settings = get_option( 'aiohm_booking_form_settings', array() );
$main_settings = get_option( 'aiohm_booking_settings', array() );

// Get unified brand color from main settings (shared across all contexts) - same as sandwich header
$unified_brand_color = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? null;

// Use unified color if available, otherwise fall back to form settings - same as sandwich header
$brand_color = $unified_brand_color ?? $form_settings['form_primary_color'] ?? '#457d59';
$text_color  = $form_settings['form_text_color'] ?? '#ffffff';

// Set CSS variables via wp_localize_script for JavaScript to handle
wp_localize_script(
	'aiohm-booking-shortcode',
	'aiohm_booking_colors',
	array(
		'brand_color' => $brand_color,
		'text_color'  => $text_color,
	)
);

// Pass pricing data to JavaScript
$pricing_js_data = array(
	'currency'       => $currency,
	'base_price'     => $base_acc_price,
	'accommodations' => array(),
);

// Add individual accommodation prices to JS data
foreach ( $accommodations as $index => $accommodation ) {
	$acc_price       = ! empty( $accommodation['price'] ) ? floatval( $accommodation['price'] ) : $base_acc_price;
	$acc_early_price = ! empty( $accommodation['earlybird_price'] ) ? floatval( $accommodation['earlybird_price'] ) : $default_early_bird_price;

	// If no specific early bird price is set, use the regular price
	if ( empty( $accommodation['earlybird_price'] ) ) {
		$acc_early_price = $acc_price; // Use same price as regular price
	}

	$pricing_js_data['accommodations'][] = array(
		'id'              => $accommodation['id'] ?? $index,
		'price'           => $acc_price,
		'earlybird_price' => $acc_early_price,
		'title'           => ! empty( $accommodation['title'] ) && trim( $accommodation['title'] ) !== '' ? $accommodation['title'] : $singular . ' ' . ( $index + 1 ),
	);
}

// Add early bird settings to JS data
$pricing_js_data['early_bird'] = array(
	'enabled'       => $enable_early_bird,
	'days'          => $early_bird_days,
	'default_price' => $default_early_bird_price,
);

wp_add_inline_script(
	'aiohm-booking-shortcode',
	'window.aiohm_accommodation_pricing = ' . wp_json_encode( $pricing_js_data ) . ';',
	'before'
);
?>

<div class="aiohm-accommodation-selection-card">
	<div class="aiohm-booking-shortcode-card-header">
		<div class="aiohm-booking-card-title-section">
			<h3 class="aiohm-section-title"><?php esc_html_e( 'Select Your Accommodations', 'aiohm-booking-pro' ); ?></h3>
			<p class="aiohm-section-subtitle"><?php esc_html_e( 'Select your check-in and check-out dates, then choose your accommodation below.', 'aiohm-booking-pro' ); ?></p>
		</div>
	</div>

	<!-- Date Selection Section -->
	<div class="aiohm-date-selection-section">
		<!-- Visual Calendar for Accommodation Bookings -->
		<div class="aiohm-booking-calendar-container">
			<div class="aiohm-calendar-header">
				<button type="button" class="aiohm-calendar-nav aiohm-prev-month" id="prevMonth">‹</button>
				<h4 class="aiohm-calendar-month-year" id="currentMonth"></h4>
				<button type="button" class="aiohm-calendar-nav aiohm-next-month" id="nextMonth">›</button>
			</div>

			<div class="aiohm-calendar-grid" id="calendarGrid">
				<!-- Day Headers starting with Monday -->
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Mon', 'aiohm-booking-pro' ); ?></div>
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Tue', 'aiohm-booking-pro' ); ?></div>
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Wed', 'aiohm-booking-pro' ); ?></div>
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Thu', 'aiohm-booking-pro' ); ?></div>
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Fri', 'aiohm-booking-pro' ); ?></div>
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Sat', 'aiohm-booking-pro' ); ?></div>
				<div class="aiohm-calendar-day-header"><?php esc_html_e( 'Sun', 'aiohm-booking-pro' ); ?></div>
				<!-- Calendar dates will be populated by JavaScript. -->
			</div>

			<div class="aiohm-calendar-legend">
				<!-- Booking Status Colors -->
				<div class="legend-group">
					<span class="legend-group-title"><?php esc_html_e( 'Booking Status:', 'aiohm-booking-pro' ); ?></span>
					<span class="legend-item"><span class="legend-dot legend-free" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Free', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-booked" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Booked', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-pending" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Pending', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-external" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'External', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-blocked" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Blocked', 'aiohm-booking-pro' ); ?></span></span>
				</div>
				<!-- Badge Indicators -->
				<div class="legend-group">
					<span class="legend-group-title"><?php esc_html_e( 'Event Flags:', 'aiohm-booking-pro' ); ?></span>
					<span class="legend-item"><span class="legend-dot legend-private" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Private Event', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-special" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'High Season', 'aiohm-booking-pro' ); ?></span></span>
				</div>
			</div>
		</div>

		<!-- Selected Dates Info -->
		<div class="aiohm-selected-dates-info">
			<div class="aiohm-form-row aiohm-form-row-2">
				<div class="aiohm-input-group">
					<label class="aiohm-input-label"><?php esc_html_e( 'Duration (nights)', 'aiohm-booking-pro' ); ?></label>
					<div class="aiohm-quantity-selector">
						<button type="button" class="aiohm-qty-btn aiohm-qty-minus" data-target="stay_duration">-</button>
						<input type="number" name="stay_duration" id="stay_duration" min="1" max="30" value="<?php echo esc_attr( $calculated_duration ); ?>" class="aiohm-qty-input" aria-label="Duration in nights">
						<button type="button" class="aiohm-qty-btn aiohm-qty-plus" data-target="stay_duration">+</button>
					</div>
				</div>
				<div class="aiohm-input-group">
					<label class="aiohm-input-label" for="guests_qty"><?php esc_html_e( 'Number of Guests', 'aiohm-booking-pro' ); ?></label>
					<div class="aiohm-quantity-selector">
						<button type="button" class="aiohm-qty-btn aiohm-qty-minus" data-target="guests_qty">-</button>
						<input type="number" name="guests_qty" id="guests_qty" min="1" max="20" value="1" class="aiohm-qty-input" aria-label="Number of guests">
						<button type="button" class="aiohm-qty-btn aiohm-qty-plus" data-target="guests_qty">+</button>
					</div>
				</div>
			</div>
			<div class="aiohm-checkout-display">
				<strong><?php esc_html_e( 'Check-in:', 'aiohm-booking-pro' ); ?></strong> <span id="checkinDisplay"><?php echo ! empty( $selected_arrival_date ) ? esc_html( date_i18n( 'D, M j', strtotime( $selected_arrival_date ) ) ) : esc_html__( 'Select date from calendar', 'aiohm-booking-pro' ); ?></span> |
				<strong><?php esc_html_e( 'Check-out:', 'aiohm-booking-pro' ); ?></strong> <span id="checkoutDisplay"><?php echo ! empty( $selected_departure_date ) ? esc_html( date_i18n( 'D, M j', strtotime( $selected_departure_date ) ) ) : ( ! empty( $selected_arrival_date ) ? esc_html__( 'Select check-in first', 'aiohm-booking-pro' ) : esc_html__( 'Select check-in first', 'aiohm-booking-pro' ) ); ?></span>
			</div>
		</div>

		<!-- Hidden inputs for form processing -->
		<input type="hidden" name="checkin_date" id="checkinHidden" value="<?php echo esc_attr( $selected_arrival_date ); ?>">
		<input type="hidden" name="checkout_date" id="checkoutHidden" value="<?php echo esc_attr( $selected_departure_date ); ?>">
	</div>

	<!-- Select Your Accommodations -->
	<?php if ( ! empty( $accommodations ) ) : ?>
	<div class="aiohm-booking-form-section">
		<div class="aiohm-booking-shortcode-card-header">
			<div class="aiohm-booking-card-title-section">
				<h3 class="aiohm-section-title"><?php echo esc_html( $singular ); ?> <?php esc_html_e( 'Selection', 'aiohm-booking-pro' ); ?></h3>
				<p class="aiohm-section-subtitle"><?php esc_html_e( 'Select one or more accommodations. Prices are per night.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>

		<!-- Scrollable accommodation cards container -->
		<div class="aiohm-booking-events-scroll-container">
			<?php
			foreach ( $accommodations as $index => $accommodation ) :
				// Use accommodation price if it exists (even if 0), otherwise use base price
				$price       = isset( $accommodation['price'] ) && $accommodation['price'] !== '' ? floatval( $accommodation['price'] ) : $base_acc_price;
				$early_price = isset( $accommodation['earlybird_price'] ) && $accommodation['earlybird_price'] !== '' ? floatval( $accommodation['earlybird_price'] ) : $default_early_bird_price;

				// If no specific early bird price is set, use the regular price
				if ( empty( $accommodation['earlybird_price'] ) ) {
					$early_price = $price; // Use same price as regular price
				}

				$description = ! empty( $accommodation['description'] ) ? wp_strip_all_tags( $accommodation['description'] ) : '';
				$acc_title   = ! empty( $accommodation['title'] ) && trim( $accommodation['title'] ) !== '' ? $accommodation['title'] : $singular . ' ' . ( $index + 1 );

				// Get accommodation type - use individual type or fallback to global default
				$acc_type         = ! empty( $accommodation['type'] ) ? $accommodation['type'] : strtolower( $singular );
				$acc_type_display = ucfirst( $acc_type );

				// Determine which price to display and if early bird applies
				$display_price         = $price; // Start with regular price, JavaScript will update based on dates
				$show_early_bird_badge = false;
				$early_bird_savings    = 0;

				// Calculate early bird savings for display purposes
				if ( $enable_early_bird && $early_price > 0 && $early_price < $price ) {
					$early_bird_savings = $price - $early_price;
				}
				?>
			<div class="aiohm-booking-event-card aiohm-booking-accommodation-card" data-availability="free">
				<label class="aiohm-booking-event-card-label">
					<input type="checkbox" name="accommodations[]" value="<?php echo esc_attr( $accommodation['id'] ?? $index ); ?>"
							data-price="<?php echo esc_attr( $price ); ?>"
							data-earlybird="<?php echo esc_attr( $early_price ); ?>"
							class="accommodation-checkbox">

					<div class="aiohm-booking-event-header">
						<div class="aiohm-booking-event-title-section">
							<div class="aiohm-accommodation-title-row">
								<span class="aiohm-accommodation-type-badge" data-type="<?php echo esc_attr( $acc_type ); ?>"><?php echo esc_html( $acc_type_display ); ?></span>
								<h3 class="aiohm-booking-event-title"><?php echo esc_html( $acc_title ); ?></h3>
								<?php if ( $enable_early_bird && $early_price > 0 && $early_price < $price ) : ?>
									<span class="aiohm-early-bird-badge" style="display: none;">🎯 Early Bird</span>
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $description ) ) : ?>
							<p class="aiohm-booking-event-description"><?php echo esc_html( $description ); ?></p>
							<?php endif; ?>
						</div>
						<div class="aiohm-booking-event-price-section">
							<div class="aiohm-price-container">
								<span class="aiohm-booking-event-price"><?php echo esc_html( $p['currency'] ); ?><?php echo number_format( $display_price, 2 ); ?></span>
								<?php if ( $enable_early_bird && $early_price > 0 && $early_price < $price ) : ?>
									<div class="aiohm-early-bird-price-badge" style="display: none;">
										<span class="aiohm-early-bird-icon">🎯</span>
										<span class="aiohm-early-bird-text">Early Bird</span>
										<?php if ( $early_bird_savings > 0 ) : ?>
											<span class="aiohm-early-bird-savings">Save <?php echo esc_html( $p['currency'] ); ?><?php echo number_format( $early_bird_savings, 2 ); ?></span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<div class="aiohm-special-pricing-badge" style="display: none;">Special Pricing</div>
							</div>
						</div>
					</div>
				</label>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $form_settings['allow_private_all'] ) ) : ?>
		<!-- Book Entire Property Card -->
		<div class="aiohm-booking-event-card aiohm-booking-accommodation-card aiohm-booking-book-entire-card" data-availability="free">
			<label class="aiohm-booking-event-card-label">
				<input type="checkbox" name="private_all" id="private_all_checkbox" value="1" class="accommodation-checkbox">

				<div class="aiohm-booking-event-header">
					<div class="aiohm-booking-event-title-section">
						<h3 class="aiohm-booking-event-title aiohm-booking-book-entire-title"><?php esc_html_e( 'Book Entire Property', 'aiohm-booking-pro' ); ?></h3>
						<p class="aiohm-booking-event-description"><?php esc_html_e( 'Select this option to book the entire property exclusively.', 'aiohm-booking-pro' ); ?></p>
					</div>
					<div class="aiohm-booking-event-price-section">
						<span class="aiohm-booking-exclusive-badge"><?php esc_html_e( 'EXCLUSIVE', 'aiohm-booking-pro' ); ?></span>
					</div>
				</div>
			</label>
		</div>
		<?php endif; ?>
	</div>
	<?php else : ?>
	<div class="aiohm-no-accommodations">
		<div class="aiohm-message aiohm-message--info">
			<h3><?php esc_html_e( 'No Accommodations Available', 'aiohm-booking-pro' ); ?></h3>
			<p><?php esc_html_e( 'There are currently no accommodations available for booking. Please check back later.', 'aiohm-booking-pro' ); ?></p>
		</div>
	</div>
	<?php endif; ?>
</div>