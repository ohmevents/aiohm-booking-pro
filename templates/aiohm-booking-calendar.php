<?php
/**
 * Calendar Template
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Prevent double rendering (skip for shortcode context).
$is_shortcode_context = defined( 'AIOHM_CALENDAR_SHORTCODE_CONTEXT' );
if ( ! $is_shortcode_context && defined( 'AIOHM_CALENDAR_RENDERED' ) ) {
	return;
}
if ( ! $is_shortcode_context ) {
	define( 'AIOHM_CALENDAR_RENDERED', true );
}

// Get calendar module instance.
$calendar_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'calendar' );

// Get settings and accommodations.
$settings              = get_option( 'aiohm_booking_settings', array() );
$unit_count            = intval( $settings['available_accommodations'] ?? 7 );
$accommodation_details = get_option( 'aiohm_booking_accommodations_details', array() );

// Create unit data for calendar (use calendar module's units if available).
if ( $calendar_module && isset( $calendar_module->unit_posts ) && ! empty( $calendar_module->unit_posts ) ) {
	$unit_posts = $calendar_module->unit_posts;
} else {
	// Fallback: get accommodation posts safely.
	$unit_posts = aiohm_booking_get_accommodation_posts( $unit_count );

	// If still no posts, create display-only objects.
	if ( empty( $unit_posts ) ) {
		$unit_posts         = array();
		$accommodation_type = aiohm_booking_get_current_accommodation_type();
		$singular_name      = aiohm_booking_get_accommodation_singular_name( $accommodation_type );

		for ( $unit_number = 1; $unit_number <= $unit_count; $unit_number++ ) {
			$unit             = new stdClass();
			$unit->ID         = $unit_number;
			$unit->post_title = $singular_name . ' ' . $unit_number;
			$unit_posts[]     = $unit;
		}
	}
}

// Handle URL parameters for period navigation with validation.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter, no state change
$period_type     = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : 'week';
$allowed_periods = array( 'week', 'month', 'year' );
if ( ! in_array( $period_type, $allowed_periods, true ) ) {
	$period_type = 'week';
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter, no state change
$week_offset = isset( $_GET['week_offset'] ) ? intval( wp_unslash( $_GET['week_offset'] ) ) : 0;
$week_offset = max( -52, min( 52, $week_offset ) ); // Limit to ±1 year

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter, no state change
$month_offset = isset( $_GET['month_offset'] ) ? intval( wp_unslash( $_GET['month_offset'] ) ) : 0;
$month_offset = max( -24, min( 24, $month_offset ) ); // Limit to ±2 years

// Get period data from calendar module instead of duplicating logic.
$period_data = array();
if ( $calendar_module && method_exists( $calendar_module, 'get_period_data_for_template' ) ) {
	$period_data  = $calendar_module->get_period_data_for_template( $period_type, $week_offset, $month_offset );
	$period_array = $period_data['period_array'] ?? array();
} else {
	// Fallback: generate date range for calendar based on period and offset.
	$period_array = array();

	if ( 'week' === $period_type ) {
		$start_date = new DateTime( 'monday this week' );
		$start_date->modify( ( $week_offset * 7 ) . ' days' );

		for ( $i = 0; $i < 7; $i++ ) {
			$date = clone $start_date;
			$date->modify( "+$i days" );
			$period_array[] = $date;
		}
	} elseif ( 'month' === $period_type ) {
		$start_date = new DateTime( 'first day of this month' );
		$start_date->modify( $month_offset . ' months' );

		$days_in_month = $start_date->format( 't' );
		for ( $i = 0; $i < $days_in_month; $i++ ) {
			$date = clone $start_date;
			$date->modify( "+$i days" );
			$period_array[] = $date;
		}
	} else {
		// Fallback to current week if invalid period.
		$start_date = new DateTime( 'monday this week' );
		for ( $i = 0; $i < 7; $i++ ) {
			$date = clone $start_date;
			$date->modify( "+$i days" );
			$period_array[] = $date;
		}
	}
}

?>
<?php if ( $is_shortcode_context ) : ?>
<div class="aiohm-booking-calendar-shortcode">
<?php else : ?>
<div class="wrap aiohm-booking-admin aiohm-mt-5">
	<!-- Admin Notices Container -->
	<div id="aiohm-admin-notices" class="aiohm-admin-notices-container"></div>

	<div class="aiohm-booking-admin-header">
		<div class="aiohm-booking-admin-header-content">
			<div class="aiohm-booking-admin-logo">
				<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
			</div>
			<div class="aiohm-booking-admin-header-text">
				<h1><?php esc_html_e( 'Calendar Management', 'aiohm-booking-pro' ); ?></h1>
				<p class="aiohm-booking-admin-tagline"><?php esc_html_e( 'Professional booking calendar with advanced unit management and real-time availability.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
	</div>
<?php endif; ?>

	<?php if ( $calendar_module && ( $calendar_module->is_enabled() || $is_shortcode_context ) ) : ?>
		<?php if ( ! $is_shortcode_context ) : ?>
	<!-- Main Calendar Card -->
	<div class="aiohm-booking-admin-card">
		<div class="aiohm-booking-calendar-card-header">
		<div class="aiohm-header-main-row">
			<div class="aiohm-filter-group">
				<label for="calendar-period"><?php esc_html_e( 'Period:', 'aiohm-booking-pro' ); ?></label>
				<?php if ( ! $is_shortcode_context ) : ?>
					<?php
					// Dynamic button titles based on period type.
					$prev_title = 'week' === $period_type ? __( 'Previous Week', 'aiohm-booking-pro' ) : __( 'Previous Month', 'aiohm-booking-pro' );
					$next_title = 'week' === $period_type ? __( 'Next Week', 'aiohm-booking-pro' ) : __( 'Next Month', 'aiohm-booking-pro' );
					?>
				<button type="button" class="button aiohm-period-prev" title="<?php echo esc_attr( $prev_title ); ?>">&lt;</button>
				<?php endif; ?>
				<select id="calendar-period" name="period">
				<option value="week" <?php selected( $period_type, 'week' ); ?>><?php esc_html_e( 'Week', 'aiohm-booking-pro' ); ?></option>
				<option value="month" <?php selected( $period_type, 'month' ); ?>><?php esc_html_e( 'Month', 'aiohm-booking-pro' ); ?></option>
				</select>
				<?php if ( ! $is_shortcode_context ) : ?>
				<button type="button" class="button aiohm-period-next" title="<?php echo esc_attr( $next_title ); ?>">&gt;</button>
				<button type="button" class="button aiohm-show-button"><?php esc_html_e( 'Show', 'aiohm-booking-pro' ); ?></button>
				<?php endif; ?>
			</div>
			<div class="aiohm-calendar-legend">
				<!-- Booking Status Colors -->
				<div class="legend-group">
					<span class="legend-group-title"><?php esc_html_e( 'Booking Status:', 'aiohm-booking-pro' ); ?></span>
					<span class="legend-item"><span class="legend-dot legend-free aiohm-color-picker-trigger" data-status="free"></span><span class="legend-text"><?php esc_html_e( 'Free', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-booked aiohm-color-picker-trigger" data-status="booked"></span><span class="legend-text"><?php esc_html_e( 'Booked', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-pending aiohm-color-picker-trigger" data-status="pending"></span><span class="legend-text"><?php esc_html_e( 'Pending', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-external aiohm-color-picker-trigger" data-status="external"></span><span class="legend-text"><?php esc_html_e( 'External', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-blocked aiohm-color-picker-trigger" data-status="blocked"></span><span class="legend-text"><?php esc_html_e( 'Blocked', 'aiohm-booking-pro' ); ?></span></span>
				</div>
				<!-- Badge Indicators -->
				<div class="legend-group">
					<span class="legend-group-title"><?php esc_html_e( 'Event Flags:', 'aiohm-booking-pro' ); ?></span>
					<span class="legend-item"><span class="legend-dot legend-private aiohm-color-picker-trigger" data-status="private"></span><span class="legend-text"><?php esc_html_e( 'Private Event', 'aiohm-booking-pro' ); ?></span></span>
					<span class="legend-item"><span class="legend-dot legend-special aiohm-color-picker-trigger" data-status="special"></span><span class="legend-text"><?php esc_html_e( 'High Season', 'aiohm-booking-pro' ); ?></span></span>
				</div>
				<p class="legend-instruction aiohm-legend-instruction"><?php esc_html_e( 'Click the color box to change color for a cell state.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
		</div>
	<?php endif; ?>

	<!-- Color Picker Modal -->
	<div id="aiohm-color-picker-modal" class="aiohm-color-picker-modal aiohm-hidden">
		<div class="aiohm-color-picker-overlay"></div>
		<div class="aiohm-color-picker-content">
		<div class="aiohm-color-picker-header">
			<h3 id="aiohm-color-picker-title">Choose Color</h3>
			<button type="button" class="aiohm-color-picker-close">&times;</button>
		</div>
		<div class="aiohm-color-picker-body">
			<div class="aiohm-color-picker-current">
			<span class="aiohm-current-color-label">Current:</span>
			<span class="aiohm-current-color-swatch" id="aiohm-current-color-swatch"></span>
			<span class="aiohm-current-color-code" id="aiohm-current-color-code">#000000</span>
			</div>
		  
			<div class="aiohm-color-picker-input">
			<label for="aiohm-color-input">Custom Color:</label>
			<input type="color" id="aiohm-color-input" value="#000000">
			<input type="text" id="aiohm-color-text" value="#000000" placeholder="#000000" maxlength="7">
			</div>
		  
			<div class="aiohm-color-picker-presets">
			<span class="aiohm-color-preset-label">Quick Colors:</span>
			<div class="aiohm-color-preset-grid">
				<span class="aiohm-color-preset aiohm-preset-light-gray" data-color="#f8f9fa" title="Light Gray"></span>
				<span class="aiohm-color-preset aiohm-preset-green" data-color="#28a745" title="Green"></span>
				<span class="aiohm-color-preset aiohm-preset-red" data-color="#dc3545" title="Red"></span>
				<span class="aiohm-color-preset aiohm-preset-yellow" data-color="#ffc107" title="Yellow"></span>
				<span class="aiohm-color-preset aiohm-preset-teal" data-color="#17a2b8" title="Teal"></span>
				<span class="aiohm-color-preset aiohm-preset-purple" data-color="#6f42c1" title="Purple"></span>
				<span class="aiohm-color-preset aiohm-preset-orange" data-color="#fd7e14" title="Orange"></span>
				<span class="aiohm-color-preset aiohm-preset-mint" data-color="#20c997" title="Mint"></span>
				<span class="aiohm-color-preset aiohm-preset-gray" data-color="#6c757d" title="Gray"></span>
				<span class="aiohm-color-preset aiohm-preset-dark" data-color="#343a40" title="Dark"></span>
			</div>
			</div>
		</div>
		<div class="aiohm-color-picker-footer">
			<button type="button" class="button" id="aiohm-color-picker-reset">Reset to Default</button>
			<div class="aiohm-color-picker-actions">
			<button type="button" class="button" id="aiohm-color-picker-cancel">Cancel</button>
			<button type="button" class="button button-primary" id="aiohm-color-picker-apply">Apply Color</button>
			</div>
		</div>
		</div>
	</div>

	<!-- Calendar admin styles moved to aiohm-booking-admin.css. -->
		<div class="aiohm-booking-calendar-single-wrapper">
		<div class="aiohm-bookings-calendar-holder">
			<table class="aiohm-bookings-single-calendar-table wp-list-table fixed">
			<thead>
				<tr>
				<th class="accommodation-column">
				<?php
					// Get accommodation type from settings to display dynamic column header.
					$global_settings    = get_option( 'aiohm_booking_settings', array() );
					$accommodation_type = $global_settings['accommodation_type'] ?? 'unit';

					$plural_name = aiohm_booking_get_accommodation_plural_name( $accommodation_type );
					echo esc_html( $plural_name );
				?>
				<div class="accommodation-column-resizer" title="Drag to resize column"></div>
				</th>
				<?php foreach ( $period_array as $date ) : ?>
					<?php
					$is_today     = current_time( 'Y-m-d' ) === $date->format( 'Y-m-d' );
					$header_class = $is_today ? 'aiohm-date-today' : '';

					// Show date headers for week and month views.
					$header_content  = '<span class="aiohm-date-main">' . esc_html( $date->format( 'j M' ) ) . '</span>';
					$header_content .= '<span class="aiohm-date-year">' . esc_html( $date->format( 'Y' ) ) . '</span>';
					$header_content .= '<span class="aiohm-date-weekday">' . esc_html( $date->format( 'D' ) ) . '</span>';
					?>
					<th class="date-column <?php echo esc_attr( $header_class ); ?>">
					<div class="aiohm-date-header">
						<?php echo wp_kses_post( $header_content ); ?>
					</div>
					</th>
				<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $unit_posts ) ) : ?>
					<?php foreach ( $unit_posts as $unit_post ) : ?>
					<tr unit-id="<?php echo esc_attr( $unit_post->ID ); ?>">
					<td class="accommodation-column">
						<a href="#" class="aiohm-accommodation-link" data-accommodation-id="<?php echo esc_attr( $unit_post->ID ); ?>">
						<?php echo esc_html( $unit_post->post_title ); ?>
						</a>
					</td>
						<?php foreach ( $period_array as $date ) : ?>
							<?php
							// Load daily logic.
							$date_string = $date->format( 'Y-m-d' );
							$is_today    = current_time( 'Y-m-d' ) === $date_string;

							// Load saved cell statuses and private events.
							$saved_cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );
							$private_events      = get_option( 'aiohm_booking_private_events', array() );
							$cell_key            = $unit_post->ID . '_' . $date_string . '_full';


							// Initialize with defaults.
							$day_status         = 'free';
							$day_price          = 0;
							$has_private_event  = false;
							$private_event_data = null;

								// Check for global private events first (applies to all accommodations).
							if ( isset( $private_events[ $date_string ] ) ) {
								$has_private_event  = true;
								$private_event_data = $private_events[ $date_string ];
								// Private events show as special visual indicators but remain "free".
								$day_status = 'free';

								// Handle both old and new data structures.
								$is_special_pricing = isset( $private_event_data['is_special_pricing'] )
								? $private_event_data['is_special_pricing']
								: ( 'special' === $private_event_data['mode'] );

								if ( $is_special_pricing && $private_event_data['price'] > 0 ) {
										$day_price = $private_event_data['price'];
								}
							}

								// Admin calendar: Show exact saved status without applying booking rules.
							if ( isset( $saved_cell_statuses[ $cell_key ] ) ) {
								// Cell-specific status overrides private events.
								$day_status = $saved_cell_statuses[ $cell_key ]['status'];

								// Handle corrupted array status entries.
								if ( is_array( $day_status ) ) {
									$day_status = 'free'; // Default corrupted entries to free.
								}

								$day_price = $saved_cell_statuses[ $cell_key ]['price'] ?? $day_price;
							} else {
								// Check if demo data is disabled (Production: disabled by default).
								$demo_disabled = get_option( 'aiohm_booking_calendar_disable_demo', true );

								if ( $demo_disabled ) {
									// Show real data only - default to free unless actual bookings exist.
									$day_status = 'free';
									$day_price  = 0;
								} else {
									// Generate random sample data for demonstration (only actual booking statuses).
									$sample_statuses = array( 'free', 'booked', 'pending', 'external', 'blocked' );
									$day_status      = $sample_statuses[ array_rand( $sample_statuses ) ];
									$day_price       = 0; // No price for demo data.
								}
							}

								// Ensure day_price is set.
							if ( ! isset( $day_price ) ) {
								$day_price = 0;
							}

								// Ensure day_status is always a valid string.
							if ( ! is_string( $day_status ) || empty( $day_status ) ) {
								$day_status = 'free';
							}

								// Cell status is determined ONLY by actual booking status (free, booked, pending, external, blocked).
								// Special pricing/high season does NOT affect cell background color.
								$month_class = 'free' !== $day_status ? " aiohm-date-{$day_status}" : ' aiohm-date-free';

								// Determine badge flags (independent of booking status).
								$has_private_badge         = false;
								$has_special_pricing_badge = false;
								$badge_parts               = array(); // Initialize badge_parts array.

							if ( $has_private_event ) {
								// Handle both old and new data structures.
								$is_private_event = isset( $private_event_data['is_private_event'] )
								? ! empty( $private_event_data['is_private_event'] )
								: ( 'private' === $private_event_data['mode'] || 'both' === $private_event_data['mode'] );

								$is_special_pricing = isset( $private_event_data['is_special_pricing'] )
								? ! empty( $private_event_data['is_special_pricing'] )
								: ( 'special' === $private_event_data['mode'] || 'both' === $private_event_data['mode'] );

								$has_private_badge         = $is_private_event;
								$has_special_pricing_badge = $is_special_pricing && ( ! empty( $private_event_data['price'] ) && $private_event_data['price'] > 0 );
							}

								// Add CSS classes for badges.
							if ( $has_private_badge ) {
								$month_class .= ' aiohm-has-private-badge';
							}
							if ( $has_special_pricing_badge ) {
								$badge_parts[] = '⭐ High Season';
							}

								// Add private event visual indicators (LEGACY - keeping for backward compatibility).
							if ( $has_private_event && 'free' === $day_status ) {
								// Handle both old and new data structures.
								$is_private_event   = isset( $private_event_data['is_private_event'] )
								? $private_event_data['is_private_event']
								: ( 'private' === $private_event_data['mode'] );
								$is_special_pricing = isset( $private_event_data['is_special_pricing'] )
								? $private_event_data['is_special_pricing']
								: ( 'special' === $private_event_data['mode'] );

							}

								// Apply today class if needed.
							if ( $is_today ) {
								$month_class .= ' aiohm-date-today';
							}

								// Set status label for display.
								$status_labels = array(
									'free'     => 'Free',
									'booked'   => 'Booked',
									'pending'  => 'Pending',
									'external' => 'External',
									'blocked'  => 'Blocked',
									'special'  => 'Special Pricing',
									'private'  => 'Private Only',
								);
								$status_label  = $status_labels[ $day_status ] ?? 'Free';

								// Override label for private events.
								if ( $has_private_event && 'free' === $day_status ) {
									// Handle both old and new data structures.
									$is_private_event   = isset( $private_event_data['is_private_event'] )
									? $private_event_data['is_private_event']
									: ( 'private' === $private_event_data['mode'] );
									$is_special_pricing = isset( $private_event_data['is_special_pricing'] )
									? $private_event_data['is_special_pricing']
									: ( 'special' === $private_event_data['mode'] );

									$labels = array();
									if ( $is_private_event ) {
										$labels[] = 'Private Only';
									}
									if ( $is_special_pricing ) {
										$labels[] = 'Special Pricing';
									}
									$status_label = implode( ' + ', $labels );
								}

								// Add editable attributes for admin users.
								$edit_attributes = '';
								if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) || current_user_can( 'publish_posts' ) || is_admin() ) {
									$edit_attributes = ' data-editable="true"';
									$month_class    .= ' aiohm-editable-cell';
								}
								?>
						<td class="aiohm-date-cell<?php echo esc_attr( $month_class ); ?>"
						data-accommodation-id="<?php echo esc_attr( $unit_post->ID ); ?>"
						data-date="<?php echo esc_attr( $date_string ); ?>"
						data-status="<?php echo esc_attr( $day_status ); ?>"
						data-price="<?php echo esc_attr( $day_price ); ?>"
						title="
							<?php
							$tooltip_parts   = array();
							$tooltip_parts[] = $date->format( 'M j, Y' ) . ' - ' . $status_label;

							$tooltip_badge_parts = array(); // Initialize tooltip badge parts.
							if ( $has_private_badge ) {
								$tooltip_badge_parts[] = '🏠 Private Event';
							}
							if ( $has_special_pricing_badge ) {
								$tooltip_badge_parts[] = '⭐ High Season';
							}

							if ( ! empty( $tooltip_badge_parts ) ) {
								$tooltip_parts[] = implode( ', ', $tooltip_badge_parts );
							}

							echo esc_attr( implode( ' | ', $tooltip_parts ) );
							?>
						"<?php echo esc_attr( $edit_attributes ); ?>>
							<?php if ( 'free' !== $day_status ) : ?>
							<span class="aiohm-status-indicator"><?php echo esc_html( $status_label ); ?></span>
								<?php if ( $day_price > 0 ) : ?>
							<span class="aiohm-price-indicator"><?php echo esc_html( number_format( $day_price, 2 ) ); ?></span>
							<?php endif; ?>
						<?php elseif ( $has_private_event ) : ?>
							<!-- Private event: Show as available with only vertical bars (no text) -->
							<span class="aiohm-available-indicator">✓</span>
							<?php if ( $day_price > 0 ) : ?>
							<span class="aiohm-price-indicator"><?php echo esc_html( number_format( $day_price, 2 ) ); ?></span>
							<?php endif; ?>
						<?php else : ?>
							<span class="aiohm-available-indicator">✓</span>
						<?php endif; ?>
						
						<!-- Badge overlay indicators -->
							<?php if ( $has_private_badge || $has_special_pricing_badge ) : ?>
							<div class="aiohm-cell-indicators">
								<?php if ( $has_private_badge ) : ?>
								<span class="aiohm-indicator aiohm-private-indicator" title="<?php esc_attr_e( 'Private Event', 'aiohm-booking-pro' ); ?>"></span>
							<?php endif; ?>
								<?php if ( $has_special_pricing_badge ) : ?>
								<span class="aiohm-indicator aiohm-special-indicator" title="<?php esc_attr_e( 'High Season', 'aiohm-booking-pro' ); ?>"></span>
							<?php endif; ?>
							</div>
						<?php endif; ?>
						</td>
					<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
				<?php else : ?>
				<tr>
					<td class="accommodation-column"><?php esc_html_e( 'No accommodations configured', 'aiohm-booking-pro' ); ?></td>
					<td class="aiohm-no-accommodations-found" colspan="<?php echo esc_attr( count( $period_array ) ); ?>">
					<?php esc_html_e( 'No accommodations configured.', 'aiohm-booking-pro' ); ?>
					<br><a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'Configure Accommodations', 'aiohm-booking-pro' ); ?>
					</a>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
			</table>
		</div>
		</div>

		<!-- Footer Controls - Part of the same card -->
		<div class="aiohm-booking-calendar-footer-wrapper">
		<div class="aiohm-bookings-calendar-controls">
			<div class="aiohm-filter-group">
			<label for="aiohm-calendar-status-filter"><?php esc_html_e( 'Filter by Status:', 'aiohm-booking-pro' ); ?></label>
			<select id="aiohm-calendar-status-filter" class="aiohm-status-filter">
				<option value=""><?php esc_html_e( 'Show All', 'aiohm-booking-pro' ); ?></option>
				<option value="free"><?php esc_html_e( 'Free/Available', 'aiohm-booking-pro' ); ?></option>
				<option value="booked"><?php esc_html_e( 'Booked', 'aiohm-booking-pro' ); ?></option>
				<option value="pending"><?php esc_html_e( 'Pending', 'aiohm-booking-pro' ); ?></option>
				<option value="external"><?php esc_html_e( 'External', 'aiohm-booking-pro' ); ?></option>
				<option value="blocked"><?php esc_html_e( 'Blocked', 'aiohm-booking-pro' ); ?></option>
			</select>
			<button type="button" id="aiohm-calendar-search-btn" class="button aiohm-search-button">
				<?php esc_html_e( 'Filter Calendar', 'aiohm-booking-pro' ); ?>
			</button>
			<button type="button" id="aiohm-calendar-reset-btn" class="button aiohm-reset-button">
				<?php esc_html_e( 'Show All', 'aiohm-booking-pro' ); ?>
			</button>
			</div>
			<div class="aiohm-button-group">
			<button type="button" id="aiohm-calendar-reset-all-days-btn" class="button aiohm-reset-all-days-button danger">
				<?php esc_html_e( 'Reset All Days', 'aiohm-booking-pro' ); ?>
			</button>
			<button type="button" id="aiohm-calendar-reset-all-colors-btn" class="button aiohm-reset-all-colors-button">
				<?php esc_html_e( 'Reset All Colors', 'aiohm-booking-pro' ); ?>
			</button>
			</div>
		</div>
		</div>
	</div>

	<!-- Private Event Management Card. -->
	<div class="aiohm-booking-admin-card">
		<div class="aiohm-booking-calendar-card-header">
		<div class="aiohm-header-content">
			<h3><?php esc_html_e( 'Private Event Management', 'aiohm-booking-pro' ); ?></h3>
			<p class="aiohm-header-subtitle">
			<?php esc_html_e( 'Block entire property for private events. When a day is set as private event, only full property bookings are allowed.', 'aiohm-booking-pro' ); ?>
			</p>
		</div>
		</div>
	  
		<div class="aiohm-special-events-layout">
		<!-- Event Special Settings -->
		<div class="aiohm-special-events-settings">
			<h4><?php esc_html_e( 'Event Special Settings', 'aiohm-booking-pro' ); ?></h4>
			<p class="aiohm-settings-description">
			<?php
			/* translators: %s: plural accommodation name (e.g. units, houses, etc.) */
			echo esc_html( sprintf( __( 'Create special events with custom names, private event toggling (locks all %s), and/or high season pricing. Configure as needed for your event requirements.', 'aiohm-booking-pro' ), strtolower( $plural_name ) ) );
			?>
			</p>
		  
			<div class="aiohm-special-event-form">
			<div class="aiohm-setting-row">
				<label for="aiohm-special-event-date"><?php esc_html_e( 'Event Date:', 'aiohm-booking-pro' ); ?></label>
				<div class="aiohm-private-event-calendar-container">
				<input type="hidden" id="aiohm-special-event-date" class="aiohm-date-input">
				<div class="aiohm-selected-date-display" id="aiohm-selected-date-display">
					<?php esc_html_e( 'Click on a date below to select', 'aiohm-booking-pro' ); ?>
				</div>
				<div class="aiohm-mini-calendar-wrapper">
					<div class="aiohm-mini-calendar-header">
					<button type="button" class="aiohm-mini-cal-prev" id="aiohm-mini-cal-prev">‹</button>
					<span class="aiohm-mini-cal-month" id="aiohm-mini-cal-month"></span>
					<button type="button" class="aiohm-mini-cal-next" id="aiohm-mini-cal-next">›</button>
					</div>
					<div class="aiohm-mini-calendar-grid" id="aiohm-mini-calendar-grid">
					<!-- Calendar will be populated by JavaScript -->
					</div>
				</div>
				</div>
			</div>
			
			<div class="aiohm-setting-row aiohm-inline-fields aiohm-three-columns">
				<div class="aiohm-field-group">
				<label for="aiohm-special-event-name">
					<span class="aiohm-field-icon aiohm-private-icon"></span>
					<?php esc_html_e( 'Event Name:', 'aiohm-booking-pro' ); ?>
					<small class="aiohm-field-note"><?php esc_html_e( '(optional)', 'aiohm-booking-pro' ); ?></small>
				</label>
				<input type="text" id="aiohm-special-event-name" class="aiohm-event-name" placeholder="<?php esc_attr_e( 'e.g. Wedding Reception', 'aiohm-booking-pro' ); ?>" maxlength="50">
				<small class="aiohm-help-text"><?php esc_html_e( 'Custom event name for reference', 'aiohm-booking-pro' ); ?></small>
				</div>
				<div class="aiohm-field-group">
				<label for="aiohm-private-event-toggle">
					<span class="aiohm-field-icon aiohm-lock-icon"></span>
					<?php esc_html_e( 'Private Event:', 'aiohm-booking-pro' ); ?><span class="legend-dot legend-private label-color-dot"></span>
					<small class="aiohm-field-note"><?php esc_html_e( '(optional)', 'aiohm-booking-pro' ); ?></small>
				</label>
				<label class="aiohm-toggle-switch">
					<input type="checkbox" id="aiohm-private-event-toggle">
					<span class="aiohm-toggle-slider"></span>
				</label>
				<small class="aiohm-help-text">
				<?php
				/* translators: %s: plural accommodation name (e.g. units, houses, etc.) */
				echo esc_html( sprintf( __( 'Lock all %s for booking', 'aiohm-booking-pro' ), $plural_name ) );
				?>
				</small>
				</div>
				<div class="aiohm-field-group">
				<label for="aiohm-special-event-price">
					<span class="aiohm-field-icon aiohm-price-icon"></span>
					<?php esc_html_e( 'High Season:', 'aiohm-booking-pro' ); ?><span class="legend-dot legend-special label-color-dot"></span>
					<small class="aiohm-field-note"><?php esc_html_e( '(optional)', 'aiohm-booking-pro' ); ?></small>
				</label>
				<div class="aiohm-price-input-wrapper">
					<input type="number" id="aiohm-special-event-price" class="aiohm-price-input" placeholder="0.00" step="0.01" min="0">
					<span class="aiohm-currency-display">
					<?php
					$global_settings = get_option( 'aiohm_booking_settings', array() );
					$currency        = $global_settings['currency'] ?? 'USD';
					// Display currency symbols with titles.
					$currency_data    = array(
						'USD' => array(
							'symbol' => '$',
							'title'  => 'US Dollar',
						),
						'EUR' => array(
							'symbol' => '€',
							'title'  => 'Euro',
						),
						'GBP' => array(
							'symbol' => '£',
							'title'  => 'British Pound',
						),
						'RON' => array(
							'symbol' => 'RON',
							'title'  => 'Romanian Leu',
						),
					);
					$current_currency = $currency_data[ $currency ] ?? array(
						'symbol' => $currency,
						'title'  => $currency,
					);
					echo '<span title="' . esc_attr( $current_currency['title'] ) . '">' . esc_html( $current_currency['symbol'] ) . '</span>';
					?>
					</span>
				</div>
				<small class="aiohm-help-text"><?php esc_html_e( 'High season pricing when filled', 'aiohm-booking-pro' ); ?></small>
				</div>
			</div>
			
			<div class="aiohm-setting-row">
				<button type="button" id="aiohm-set-private-event-btn" class="button button-primary full-width">
				<?php esc_html_e( 'Create Event Special', 'aiohm-booking-pro' ); ?>
				</button>
			</div>
			</div>
		</div>
		
		<!-- Right Column: Current Special Events. -->
		<div class="aiohm-special-events-display">
			<h4><?php esc_html_e( 'Current Special Events', 'aiohm-booking-pro' ); ?></h4>
		  
			<div class="aiohm-private-events-status">
			<div id="aiohm-private-events-list" class="aiohm-private-events-list">
				<?php
				if ( empty( $private_events ) ) {
					echo '<em class="aiohm-private-events-empty">' . esc_html__( 'No private events currently set.', 'aiohm-booking-pro' ) . '</em>';
				} else {
					// Filter out past events - only show current and future events
					$current_date           = current_time( 'Y-m-d' );
					$current_private_events = array_filter(
						$private_events,
						function ( $date ) use ( $current_date ) {
							return $date >= $current_date;
						},
						ARRAY_FILTER_USE_KEY
					);

					if ( empty( $current_private_events ) ) {
						echo '<em class="aiohm-private-events-empty">' . esc_html__( 'No current special events set.', 'aiohm-booking-pro' ) . '</em>';
					} else {
						// Sort events by date.
						ksort( $current_private_events );

						echo '<div class="aiohm-private-events-grid">';

						foreach ( $current_private_events as $date => $event ) {
							$date_obj       = new DateTime( $date );
							$formatted_date = $date_obj->format( 'M j, Y' );
							$price          = ! empty( $event['price'] ) ? number_format_i18n( floatval( $event['price'] ), 2 ) : '0.00';
							$currency       = get_option( 'aiohm_booking_settings', array() )['currency'] ?? 'USD';
							$event_name     = ! empty( $event['name'] ) ? esc_html( $event['name'] ) : esc_html__( 'Private Event', 'aiohm-booking-pro' );

							// Handle both old and new data structures.
							$is_private_event   = isset( $event['is_private_event'] )
							? $event['is_private_event']
							: ( isset( $event['mode'] ) && 'private' === $event['mode'] );
							$is_special_pricing = isset( $event['is_special_pricing'] )
							? $event['is_special_pricing']
							: ( isset( $event['mode'] ) && 'special' === $event['mode'] );

							// Create mode labels.
							$mode_labels = array();
							if ( $is_private_event ) {
								$mode_labels[] = 'Private Only';
							}
							if ( $is_special_pricing ) {
								$mode_labels[] = 'Special Pricing';
							}
							$mode_label = implode( ' + ', $mode_labels );
							if ( empty( $mode_label ) ) {
								$mode_label = 'Private Event';
							}

							// Create CSS class.
							if ( $is_private_event && $is_special_pricing ) {
								$mode_class = 'dual-event';
							} elseif ( $is_special_pricing ) {
								$mode_class = 'special-pricing';
							} else {
								$mode_class = 'private-only';
							}

							// Build badge representation for event item tooltip.
							$badge_parts = array();
							if ( $is_private_event ) {
								$badge_parts[] = '🏠 Private Event';
							}
							if ( $is_special_pricing ) {
								$badge_parts[] = '🌞 High Season';
							}
							$badge_tooltip = ! empty( $badge_parts ) ? implode( ', ', $badge_parts ) : $mode_label;

							echo '<div class="aiohm-private-event-item ' . esc_attr( $mode_class ) . '" title="' . esc_attr( $badge_tooltip . ' - ' . $formatted_date ) . '">';
							echo '<button class="aiohm-remove-event-btn" data-date="' . esc_attr( $date ) . '" title="' . esc_attr__( 'Remove Event', 'aiohm-booking-pro' ) . '">×</button>';

							// Add badge display inline with content.
							echo '<div class="aiohm-event-date">' . esc_html( $formatted_date );
							if ( ! empty( $badge_parts ) ) {
								echo ' <span class="aiohm-event-badges">';
								if ( $is_private_event ) {
									echo '<span class="aiohm-badge-inline aiohm-private-badge" title="' . esc_attr__( 'Private Event', 'aiohm-booking-pro' ) . '">🏠</span>';
								}
								if ( $is_special_pricing ) {
									echo '<span class="aiohm-badge-inline aiohm-special-badge" title="' . esc_attr__( 'High Season', 'aiohm-booking-pro' ) . '">🌞</span>';
								}
								echo '</span>';
							}
							echo '</div>';

							echo '<div class="aiohm-event-name-display">' . esc_html( $event_name ) . '</div>';
							echo '<div class="aiohm-event-price-display">' . esc_html( $price ) . ' ' . esc_html( $currency ) . ' • ' . esc_html( $mode_label ) . '</div>';
							echo '</div>';
						}

						echo '</div>';
					}
				}
				?>
			</div>
			</div>
		</div>
		</div>
	</div>

	<!-- AI Calendar Insights Card (PRO Feature). -->
		<?php
		// Check if AI Analytics module is enabled and there's a default AI provider set.
		$ai_analytics_enabled       = ! empty( $settings['enable_ai_analytics'] );
		$default_ai_provider        = $settings['shortcode_ai_provider'] ?? '';
		$calendar_analytics_enabled = ! empty( $settings['enable_calendar_analytics'] );

		// Show AI section only if ALL conditions are met:.
		// 1. AI Analytics module is enabled.
		// 2. AI Analytics for Calendar page is enabled.
		// 3. Default provider is set.
		// 4. That specific provider is enabled.
		$show_ai_section = $ai_analytics_enabled &&
		$calendar_analytics_enabled &&
		! empty( $default_ai_provider ) &&
		! empty( $settings[ 'enable_ai_' . $default_ai_provider ] );
		?>

		<?php if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) : ?>
			<?php if ( $show_ai_section ) : ?>
		<div class="aiohm-booking-admin-card">
				<?php
				// Get AI Analytics module instance and render the insights section
				$ai_analytics_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'ai-analytics' );
				if ( $ai_analytics_module && method_exists( $ai_analytics_module, 'render_ai_calendar_insights_section' ) ) {
					$ai_analytics_module->render_ai_calendar_insights_section();
				}
				?>
		</div>
		<?php endif; ?>
		<?php endif; ?>

	<?php else : ?>
		<?php if ( ! $is_shortcode_context ) : ?>
	<div class="aiohm-booking-admin-card">
		<div class="notice notice-info">
		<p><?php esc_html_e( 'Calendar module is not enabled. Please enable it in the Settings to view the booking calendar.', 'aiohm-booking-pro' ); ?></p>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Settings', 'aiohm-booking-pro' ); ?></a></p>
		</div>
	</div>
	<?php endif; ?>
	<?php endif; ?>

</div>

<!-- Booking Details Popup. -->
<div id="aiohm-bookings-calendar-popup" class="aiohm-popup aiohm-hide">
	<div class="aiohm-popup-backdrop"></div>
	<div class="aiohm-popup-body">
	<div class="aiohm-header">
		<h2 class="aiohm-title aiohm-inline"><?php esc_html_e( 'Booking Details', 'aiohm-booking-pro' ); ?></h2>
		<button class="aiohm-close-popup-button dashicons dashicons-no-alt"></button>
	</div>
	<div class="aiohm-content"></div>
	<div class="aiohm-footer">
		<a href="#" class="button button-primary aiohm-edit-button"><?php esc_html_e( 'View Order', 'aiohm-booking-pro' ); ?></a>
	</div>
	</div>
</div>
<!-- Calendar styles consolidated in aiohm-booking-shortcodes.css. -->

<?php
// Hook for AI Analytics module to add content to calendar page.
do_action( 'aiohm_booking_calendar_page_bottom' );
?>