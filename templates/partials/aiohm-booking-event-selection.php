<?php
/**
 * Event Selection Card - Modular Component
 * Displays event selection with scrollable list of available events
 * Includes start/end dates, pricing, and availability
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get events data and settings.
$events_data     = get_option( 'aiohm_booking_events_data', array() );
$global_settings = get_option( 'aiohm_booking_settings', array() );

// Check if multiple event bookings are enabled
$form_settings         = get_option( 'aiohm_booking_tickets_form_settings', array() );
$allow_multiple_events = ! empty( $form_settings['allow_group_bookings'] );

// Get events data with real-time availability (accounting for paid orders)
if ( class_exists( 'AIOHM_BOOKING_Module_Tickets' ) ) {
	$events_data = AIOHM_BOOKING_Module_Tickets::get_events_with_realtime_availability( $events_data );
}
$num_events      = intval( $global_settings['number_of_events'] ?? 5 );
$current_date    = current_time( 'Y-m-d' );
$upcoming_events = array();

// Filter for upcoming events only, respecting the configured number limit.
$event_count = 0;
foreach ( $events_data as $event ) {
	if ( ! empty( $event['event_date'] ) && $event['event_date'] >= $current_date ) {
		$upcoming_events[] = $event;
		++$event_count;
		if ( $event_count >= $num_events ) {
			break; // Stop when we reach the configured limit.
		}
	}
}

// Sort by date.
usort(
	$upcoming_events,
	function ( $a, $b ) {
		$date_comparison = strcmp( $a['event_date'], $b['event_date'] );
		return 0 !== $date_comparison ? $date_comparison : strcmp( $a['event_time'] ?? '', $b['event_time'] ?? '' );
	}
);
?>

<div class="aiohm-booking-event-selection-card">
	<div class="aiohm-booking-card-header">
		<div class="aiohm-booking-card-title-section">
			<h3 class="aiohm-booking-card-title"><?php esc_html_e( 'Event Selection', 'aiohm-booking-pro' ); ?></h3>
			<p class="aiohm-booking-card-subtitle"><?php esc_html_e( 'Select an event to purchase tickets. Choose your ticket quantity below.', 'aiohm-booking-pro' ); ?></p>
		</div>
	</div>

	<?php if ( ! empty( $upcoming_events ) ) : ?>
	
	<div class="aiohm-booking-event-selection-container">
		<div class="aiohm-booking-events-scroll-container">
			<?php foreach ( $upcoming_events as $index => $event ) : ?>
				<?php
				$price = ! empty( $event['price'] ) ? floatval( $event['price'] ) : 0;

				// Use the early bird helper for events
				$events_early_bird_settings = AIOHM_BOOKING_Early_Bird_Helper::get_events_early_bird_settings();

				// Calculate early bird price using helper
				$early_bird_price_from_event = ! empty( $event['early_bird_price'] ) ? floatval( $event['early_bird_price'] ) : 0;
				$early_bird_date             = $event['early_bird_date'] ?? '';

				// Use helper to calculate the appropriate early bird price
				$early_price = AIOHM_BOOKING_Early_Bird_Helper::calculate_events_early_bird_price(
					$price,
					$early_bird_price_from_event,
					$early_bird_date
				);

				$display_price = $early_price;

				// Dynamic date/time formatting - force short month for compact display
				$date_format = 'M j, Y'; // Short month format (Sep instead of September)
				$time_format = get_option( 'time_format', 'g:i a' );

				$event_date_formatted     = ! empty( $event['event_date'] ) ? date_i18n( $date_format, strtotime( $event['event_date'] ) ) : '';
				$event_time               = ! empty( $event['event_time'] ) ? date_i18n( $time_format, strtotime( $event['event_time'] ) ) : '';
				$event_end_date_formatted = ! empty( $event['event_end_date'] ) ? date_i18n( $date_format, strtotime( $event['event_end_date'] ) ) : '';
				$event_end_time           = ! empty( $event['event_end_time'] ) ? date_i18n( $time_format, strtotime( $event['event_end_time'] ) ) : '';

				$available_seats = isset( $event['available_seats'] ) ? intval( $event['available_seats'] ) : 50;
				$currency        = $global_settings['currency'] ?? 'EUR';

				// Use global settings as defaults for early bird days and deposit percentage
				$early_bird_days    = ! empty( $event['early_bird_days'] ) ? intval( $event['early_bird_days'] ) : intval( $global_settings['early_bird_days'] ?? 30 );
				$deposit_percentage = ! empty( $event['deposit_percentage'] ) ? intval( $event['deposit_percentage'] ) : intval( $global_settings['deposit_percentage'] ?? 50 );
				?>
				
				<div class="aiohm-booking-event-card" data-availability="<?php echo esc_attr( $available_seats > 0 ? 'available' : 'sold-out' ); ?>" data-event-index="<?php echo esc_attr( $index ); ?>">
					<label class="aiohm-booking-event-card-label">
						<input type="<?php echo esc_attr( $allow_multiple_events ? 'checkbox' : 'radio' ); ?>" 
								class="<?php echo esc_attr( $allow_multiple_events ? 'aiohm-booking-event-checkbox' : 'aiohm-booking-event-radio' ); ?>"
								name="<?php echo esc_attr( $allow_multiple_events ? 'selected_events[]' : 'selected_event' ); ?>" 
								value="<?php echo esc_attr( $index ); ?>"
								data-price="<?php echo esc_attr( $display_price ); ?>"
								data-regular-price="<?php echo esc_attr( $price ); ?>"
								data-early-price="<?php echo esc_attr( $early_price ); ?>"
								data-event-date="<?php echo esc_attr( $event['event_date'] ); ?>"
								data-event-time="<?php echo esc_attr( $event_time ); ?>"
								data-event-end-date="<?php echo esc_attr( $event['event_end_date'] ?? '' ); ?>"
								data-event-end-time="<?php echo esc_attr( $event_end_time ); ?>"
								data-available-seats="<?php echo esc_attr( $available_seats ); ?>"
								data-event-title="<?php echo esc_attr( $event['title'] ); ?>"
								data-event-type="<?php echo esc_attr( $event['event_type'] ?? '' ); ?>"
								data-event-description="<?php echo esc_attr( $event['description'] ?? '' ); ?>"
								data-event-early-bird-days="<?php echo esc_attr( $early_bird_days ); ?>"
								data-event-deposit-percentage="<?php echo esc_attr( $deposit_percentage ); ?>"
								class="<?php echo esc_attr( $allow_multiple_events ? 'aiohm-booking-event-checkbox' : 'aiohm-booking-event-radio' ); ?>"
								<?php echo esc_attr( $available_seats <= 0 ? 'disabled' : '' ); ?>>
						
						<div class="aiohm-booking-event-card-content">
							<!-- ROW 1: Header with 3 columns: Title - Date - Price -->
							<div class="aiohm-booking-event-header">
								<!-- Column 1: Title -->
								<div class="aiohm-booking-event-title-col">
									<?php if ( ! empty( $event['event_type'] ) ) : ?>
										<div class="aiohm-booking-event-type"><?php echo esc_html( $event['event_type'] ); ?></div>
									<?php endif; ?>
									<h4 class="aiohm-booking-event-title"><?php echo esc_html( $event['title'] ); ?></h4>
								</div>

								<!-- Column 2: Date -->
								<div class="aiohm-booking-event-date-col">
									<?php if ( $event_date_formatted ) : ?>
										<div class="aiohm-booking-event-start-compact">
											<strong><?php esc_html_e( 'START:', 'aiohm-booking-pro' ); ?></strong>
											<span><?php echo esc_html( $event_date_formatted ); ?></span>
											<?php if ( $event_time ) : ?>
												<span><?php echo esc_html( $event_time ); ?></span>
											<?php endif; ?>
										</div>
									<?php endif; ?>

									<?php if ( $event_end_date_formatted || $event_end_time ) : ?>
										<div class="aiohm-booking-event-end-compact">
											<strong><?php esc_html_e( 'END:', 'aiohm-booking-pro' ); ?></strong>
											<span><?php if ( $event_end_date_formatted ) : ?>
												<?php echo esc_html( $event_end_date_formatted ); ?>
											<?php else : ?>
												<?php echo esc_html( $event_date_formatted ); ?>
											<?php endif; ?></span>
											<?php if ( $event_end_time ) : ?>
												<span><?php echo esc_html( $event_end_time ); ?></span>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</div>

								<!-- Column 3: Price -->
								<div class="aiohm-booking-event-price-col">
									<?php if ( $early_price < $price ) : ?>
										<span class="aiohm-booking-current-price"><?php echo esc_html( $currency ); ?><?php echo number_format( $early_price, 2 ); ?></span>
										<span class="aiohm-booking-original-price"><?php echo esc_html( $currency ); ?><?php echo number_format( $price, 2 ); ?></span>
									<?php else : ?>
										<span class="aiohm-booking-current-price"><?php echo esc_html( $currency ); ?><?php echo number_format( $price, 2 ); ?></span>
									<?php endif; ?>

									<?php if ( $available_seats <= 0 ) : ?>
										<span class="aiohm-booking-sold-out-badge"><?php esc_html_e( 'SOLD OUT', 'aiohm-booking-pro' ); ?></span>
									<?php elseif ( $early_price < $price ) : ?>
										<span class="aiohm-booking-early-bird-badge"><?php esc_html_e( 'EARLY BIRD', 'aiohm-booking-pro' ); ?></span>
									<?php endif; ?>
									
									<!-- Selection Checkmark under price -->
									<div class="aiohm-booking-event-checkmark">✓</div>
								</div>
							</div>

							<!-- ROW 2: Description (hidden, shows on hover) -->
							<div class="aiohm-booking-event-description-row">
								<div class="aiohm-booking-event-description">
									<p>
									<?php
									if ( ! empty( $event['description'] ) ) {
										echo esc_html( wp_trim_words( $event['description'], 25, '...' ) );
									} else {
										echo esc_html__( 'No description available for this event.', 'aiohm-booking-pro' );
									}
									?>
									</p>
								</div>
							</div>


							<!-- ROW 3: Tickets info with 4 columns (hidden, shows on hover) -->
							<div class="aiohm-booking-event-tickets-row">
								<?php
								// Get tickets info (calculated by real-time availability function)
								$total_capacity    = isset( $event['total_capacity'] ) ? intval( $event['total_capacity'] ) : $available_seats;
								$tickets_sold      = isset( $event['tickets_sold'] ) ? intval( $event['tickets_sold'] ) : 0;
								$tickets_remaining = $available_seats;

								// Get teacher information
								$teachers = $event['teachers'] ?? array();
								if ( empty( $teachers ) && ( ! empty( $event['teacher_name'] ) || ! empty( $event['teacher_photo'] ) ) ) {
									// Migrate old single teacher data to new format
									$teachers[] = array(
										'name'  => $event['teacher_name'] ?? '',
										'photo' => $event['teacher_photo'] ?? '',
									);
								}
								?>
								<div class="aiohm-booking-tickets-stats">
									<!-- Column 1: Teacher Information -->
									<?php if ( ! empty( $teachers ) ) : ?>
									<div class="aiohm-booking-teacher-info">
										<?php foreach ( $teachers as $teacher ) : ?>
											<?php if ( ! empty( $teacher['name'] ) || ! empty( $teacher['photo'] ) ) : ?>
											<div class="aiohm-booking-event-teacher-item">
												<?php if ( ! empty( $teacher['photo'] ) ) : ?>
													<div class="aiohm-booking-teacher-photo">
														<img src="<?php echo esc_url( $teacher['photo'] ); ?>"
															alt="<?php echo esc_attr( $teacher['name'] ?? __( 'Teacher', 'aiohm-booking-pro' ) ); ?>"
															class="aiohm-teacher-photo-small">
													</div>
												<?php endif; ?>
												<?php if ( ! empty( $teacher['name'] ) ) : ?>
													<div class="aiohm-booking-teacher-details">
														<strong><?php esc_html_e( 'Teacher', 'aiohm-booking-pro' ); ?></strong>
														<span><?php echo esc_html( $teacher['name'] ); ?></span>
													</div>
												<?php endif; ?>
											</div>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php else : ?>
									<div class="aiohm-booking-teacher-info">
										<!-- Empty column for consistent layout -->
									</div>
									<?php endif; ?>

									<!-- Column 2: Remaining Tickets -->
									<div class="aiohm-booking-total-tickets">
										<strong><?php esc_html_e( 'REMAINING TICKETS', 'aiohm-booking-pro' ); ?></strong>
										<span class="aiohm-booking-ticket-number"><?php echo esc_html( $tickets_remaining ); ?></span>
									</div>

									<!-- Column 3: Tickets Sold -->
									<div class="aiohm-booking-tickets-sold">
										<strong><?php esc_html_e( 'TICKETS SOLD', 'aiohm-booking-pro' ); ?></strong>
										<span class="aiohm-booking-ticket-number"><?php echo esc_html( $tickets_sold ); ?></span>
									</div>

									<!-- Column 4: Buy Tickets -->
									<div class="aiohm-booking-ticket-selector">
										<strong><?php esc_html_e( 'BUY TICKETS', 'aiohm-booking-pro' ); ?></strong>
										<div class="aiohm-booking-ticket-quantity-controls">
											<button type="button" class="aiohm-booking-ticket-btn aiohm-ticket-minus" data-event-index="<?php echo esc_attr( $index ); ?>">-</button>
											<input type="text" inputmode="numeric" pattern="[0-9]*" 
													name="event_tickets[<?php echo esc_attr( $index ); ?>]" 
													value="0" 
													min="0" 
													max="<?php echo esc_attr( $available_seats ); ?>" 
													class="aiohm-booking-ticket-quantity-input" 
													data-event-index="<?php echo esc_attr( $index ); ?>"
													data-available="<?php echo esc_attr( $available_seats ); ?>">
											<button type="button" class="aiohm-booking-ticket-btn aiohm-ticket-plus" data-event-index="<?php echo esc_attr( $index ); ?>">+</button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</label>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	
	<?php else : ?>
		<div class="aiohm-no-events">
			<div class="aiohm-no-events-content">
				<svg class="aiohm-no-events-icon" width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
					<path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z"/>
				</svg>
				<h3><?php esc_html_e( 'No Events Available', 'aiohm-booking-pro' ); ?></h3>
				<p><?php esc_html_e( 'There are currently no upcoming events available for booking.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
	<?php endif; ?>
</div>

