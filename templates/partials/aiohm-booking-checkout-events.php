<?php
/**
 * Event Checkout Component - Modular
 * Displays event booking summary and details for checkout
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Extract passed data
$booking_data = $booking_data ?? array();
$show_summary = $show_summary ?? true;

// Only show if this is a ticket booking
if ( empty( $booking_data ) || ( $booking_data['mode'] ?? '' ) !== 'tickets' ) {
	return;
}

// Extract event-specific data
$event_title     = '';
$event_date      = '';
$event_time      = '';
$ticket_quantity = intval( $booking_data['guests_qty'] ?? 1 );
$currency        = $booking_data['currency'] ?? 'EUR';

// Parse event details from notes if available
if ( ! empty( $booking_data['notes'] ) ) {
	$notes_lines = explode( "\n", $booking_data['notes'] );
	foreach ( $notes_lines as $line ) {
		$line = trim( $line );
		if ( strpos( $line, 'Event:' ) === 0 ) {
			// Extract event title from "Event: 0, Event Name" format
			$event_part = trim( str_replace( 'Event:', '', $line ) );
			if ( strpos( $event_part, ',' ) !== false ) {
				list( $event_index, $event_title ) = explode( ',', $event_part, 2 );
				$event_title                       = trim( $event_title );
			} else {
				$event_title = trim( $event_part );
			}
		} elseif ( strpos( $line, 'Date:' ) === 0 ) {
			$date_part = trim( str_replace( 'Date:', '', $line ) );
			// Split date and time
			$date_parts = explode( ' ', $date_part );
			$event_date = $date_parts[0] ?? '';
			$event_time = isset( $date_parts[1] ) ? implode( ' ', array_slice( $date_parts, 1 ) ) : '';
		} elseif ( strpos( $line, 'Tickets:' ) === 0 ) {
			$ticket_quantity = intval( trim( str_replace( 'Tickets:', '', $line ) ) );
		}
	}
}

// Fallback to database fields if notes parsing failed
if ( empty( $event_title ) ) {
	$event_title = 'Event Booking';
}
if ( empty( $event_date ) ) {
	$event_date = $booking_data['check_in_date'] ?? '';
}

// Format date for display
$event_date_formatted = '';
if ( ! empty( $event_date ) ) {
	$event_date_formatted = date_i18n( 'M j, Y', strtotime( $event_date ) );
}
?>

<div class="aiohm-checkout-event-summary">
	<!-- Pricing Breakdown Card -->
	<div class="aiohm-checkout-pricing-card aiohm-booking-card">
		<div class="aiohm-booking-shortcode-card-header">
			<h4><?php echo esc_html( $event_title ); ?></h4>
		</div>
		<div class="aiohm-checkout-pricing-details">
			<div class="aiohm-pricing-breakdown">
				<div class="aiohm-pricing-row">
					<span class="aiohm-pricing-label">
						<span class="dashicons dashicons-tickets-alt"></span>
						<?php esc_html_e( 'Tickets:', 'aiohm-booking-pro' ); ?>
					</span>
					<span class="aiohm-pricing-value"><?php echo esc_html( $ticket_quantity ); ?></span>
				</div>
				
				<?php if ( $ticket_quantity > 1 ) : ?>
					<div class="aiohm-pricing-row">
						<span class="aiohm-pricing-label"><?php esc_html_e( 'Price per ticket:', 'aiohm-booking-pro' ); ?></span>
						<span class="aiohm-pricing-value"><?php echo esc_html( $currency ); ?><?php echo number_format( floatval( $booking_data['total_amount'] ) / $ticket_quantity, 2 ); ?></span>
					</div>
				<?php endif; ?>
				
				<div class="aiohm-pricing-row aiohm-pricing-total">
					<span class="aiohm-pricing-label">
						<strong><?php esc_html_e( 'Total Amount:', 'aiohm-booking-pro' ); ?></strong>
					</span>
					<span class="aiohm-pricing-value aiohm-pricing-total-value">
						<strong><?php echo esc_html( $currency ); ?><?php echo number_format( floatval( $booking_data['total_amount'] ), 2 ); ?></strong>
					</span>
				</div>
				
				<?php if ( ! empty( $booking_data['deposit_amount'] ) && floatval( $booking_data['deposit_amount'] ) > 0 ) : ?>
					<div class="aiohm-pricing-row aiohm-pricing-deposit">
						<span class="aiohm-pricing-label">
							<span class="dashicons dashicons-money-alt"></span>
							<?php esc_html_e( 'Deposit Required:', 'aiohm-booking-pro' ); ?>
						</span>
						<span class="aiohm-pricing-value"><?php echo esc_html( $currency ); ?><?php echo number_format( floatval( $booking_data['deposit_amount'] ), 2 ); ?></span>
					</div>
					
					<div class="aiohm-pricing-row aiohm-pricing-balance">
						<span class="aiohm-pricing-label">
							<span class="dashicons dashicons-calculator"></span>
							<?php esc_html_e( 'Remaining Balance:', 'aiohm-booking-pro' ); ?>
						</span>
						<span class="aiohm-pricing-value"><?php echo esc_html( $currency ); ?><?php echo number_format( floatval( $booking_data['total_amount'] ) - floatval( $booking_data['deposit_amount'] ), 2 ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>