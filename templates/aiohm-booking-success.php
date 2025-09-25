<?php
/**
 * Template for booking success page
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get booking ID from URL parameter with security validation.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public success page, booking ID is non-sensitive display parameter
$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

if ( ! $booking_id || $booking_id > AIOHM_BOOKING_Security_Config::MAX_BOOKING_ID ) {
	echo '<div class="aiohm-booking-error">' . esc_html__( 'Invalid booking reference.', 'aiohm-booking-pro' ) . '</div>';
	return;
}

// Get booking details.
global $wpdb;
$table_name = $wpdb->prefix . 'aiohm_booking_order';
$booking    = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Success page needs fresh booking data, caching not appropriate
	$wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id )
);

if ( ! $booking ) {
	echo '<div class="aiohm-booking-error">Booking not found.</div>';
	return;
}

// Determine booking type
$booking_mode     = $booking->mode ?? 'accommodation';
$is_event_booking = ( $booking_mode === 'tickets' );

// Parse event details from notes if this is an event booking
$event_title     = '';
$event_date      = '';
$event_time      = '';
$ticket_quantity = $booking->guests_qty ?? 1;

if ( $is_event_booking && ! empty( $booking->notes ) ) {
	$notes_lines = explode( "\n", $booking->notes );
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

// Fallback for event title
if ( $is_event_booking && empty( $event_title ) ) {
	$event_title = 'Event Booking';
}

// Format dates for display
$checkin_formatted    = '';
$checkout_formatted   = '';
$event_date_formatted = '';

if ( ! empty( $booking->check_in_date ) ) {
	$checkin_formatted = gmdate( 'F j, Y', strtotime( $booking->check_in_date ) );
}
if ( ! empty( $booking->check_out_date ) ) {
	$checkout_formatted = gmdate( 'F j, Y', strtotime( $booking->check_out_date ) );
}
if ( ! empty( $event_date ) ) {
	$event_date_formatted = gmdate( 'F j, Y', strtotime( $event_date ) );
}
?>

<div class="aiohm-booking-success-container">
	<!-- Success Header Card -->
	<div class="aiohm-checkout-card">
		<div class="aiohm-booking-shortcode-card-header">
			<div class="aiohm-card-icon">✅</div>
			<div class="aiohm-card-title-section">
				<h1 class="aiohm-card-title"><?php esc_html_e( 'Booking Confirmed!', 'aiohm-booking-pro' ); ?></h1>
				<p class="aiohm-card-subtitle">
					<?php esc_html_e( 'Thank you for your booking. Your reservation has been received.', 'aiohm-booking-pro' ); ?>
				</p>
			</div>
		</div>
	</div>

	<!-- Booking Summary Card -->
	<div class="aiohm-checkout-card">
		<div class="aiohm-booking-shortcode-card-header">
			<div class="aiohm-card-icon">📋</div>
			<div class="aiohm-card-title-section">
				<h2 class="aiohm-card-title"><?php esc_html_e( 'Booking Summary', 'aiohm-booking-pro' ); ?></h2>
				<p class="aiohm-card-subtitle">
					<?php
					if ( $is_event_booking ) {
						esc_html_e( 'Your event booking details and ticket information.', 'aiohm-booking-pro' );
					} else {
						esc_html_e( 'Your accommodation booking details and stay information.', 'aiohm-booking-pro' );
					}
					?>
				</p>
			</div>
		</div>
		
		<div class="aiohm-checkout-accommodation-details">
			<div class="aiohm-accommodation-pricing">
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Booking ID:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value">#<?php echo esc_html( $booking->id ); ?></span>
				</div>
				
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Guest Name:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $booking->buyer_name ); ?></span>
				</div>
			
			<?php if ( $is_event_booking ) : ?>
				<!-- Event Booking Fields -->
				<?php if ( ! empty( $event_title ) ) : ?>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Event:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $event_title ); ?></span>
				</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $event_date_formatted ) ) : ?>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Event Date:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $event_date_formatted ); ?></span>
				</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $event_time ) ) : ?>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Event Time:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $event_time ); ?></span>
				</div>
				<?php endif; ?>
				
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Tickets:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value">
						<?php
						/* translators: %d: number of tickets */
						echo esc_html( sprintf( _n( '%d ticket', '%d tickets', $ticket_quantity, 'aiohm-booking-pro' ), $ticket_quantity ) );
						?>
					</span>
				</div>
				
			<?php else : ?>
				<!-- Accommodation Booking Fields -->
				<?php if ( ! empty( $checkin_formatted ) ) : ?>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Check-in:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $checkin_formatted ); ?></span>
				</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $checkout_formatted ) ) : ?>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Check-out:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $checkout_formatted ); ?></span>
				</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $booking->guests_qty ) ) : ?>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Guests:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $booking->guests_qty ); ?></span>
				</div>
				<?php endif; ?>
				
				<?php if ( ! empty( $booking->units_qty ) && $booking->units_qty > 1 ) : ?>
				<div class="aiohm-rooms-row">
					<span class="aiohm-label"><?php esc_html_e( 'Units:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( $booking->units_qty ); ?></span>
				</div>
				<?php endif; ?>
			<?php endif; ?>
			
			<div class="aiohm-total-row">
				<span class="aiohm-label"><?php esc_html_e( 'Total Amount:', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-value aiohm-total-amount"><?php echo esc_html( $booking->currency . ' ' . number_format( $booking->total_amount, 2 ) ); ?></span>
			</div>
			
			<?php if ( ! empty( $booking->deposit_amount ) && $booking->deposit_amount > 0 ) : ?>
			<div class="aiohm-deposit-row">
				<span class="aiohm-label"><?php esc_html_e( 'Deposit Required:', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-value"><?php echo esc_html( $booking->currency . ' ' . number_format( $booking->deposit_amount, 2 ) ); ?></span>
			</div>
			<?php endif; ?>
			
			<div class="aiohm-guests-row">
				<span class="aiohm-label"><?php esc_html_e( 'Status:', 'aiohm-booking-pro' ); ?></span>
				<span class="aiohm-value aiohm-status-<?php echo esc_attr( $booking->status ); ?>">
					<?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->status ) ) ); ?>
				</span>
			</div>
			</div>
		</div>
	</div>

	<?php if ( 'pending' === $booking->status ) : ?>
	<!-- Payment Instructions Card -->
	<div class="aiohm-checkout-card">
		<div class="aiohm-booking-shortcode-card-header">
			<div class="aiohm-card-icon">💳</div>
			<div class="aiohm-card-title-section">
				<h3 class="aiohm-card-title"><?php esc_html_e( 'Payment Instructions', 'aiohm-booking-pro' ); ?></h3>
				<p class="aiohm-card-subtitle"><?php esc_html_e( 'Complete your payment to secure your booking.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
		<div class="aiohm-checkout-accommodation-details">
			<div class="aiohm-payment-amount">
				<div class="aiohm-notice aiohm-notice-info">
					<p><strong><?php esc_html_e( 'Payment instructions have been sent to your email address.', 'aiohm-booking-pro' ); ?></strong></p>
					<p><?php esc_html_e( 'Please check your email (including spam folder) for detailed payment instructions. Your booking will be confirmed once payment is received.', 'aiohm-booking-pro' ); ?></p>
					<p><?php esc_html_e( 'If you don\'t receive the email within a few minutes, please contact us.', 'aiohm-booking-pro' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Next Steps Card -->
	<div class="aiohm-checkout-card">
		<div class="aiohm-card-header">
			<div class="aiohm-card-icon">📋</div>
			<div class="aiohm-card-title-section">
				<h3 class="aiohm-card-title"><?php esc_html_e( 'What Happens Next?', 'aiohm-booking-pro' ); ?></h3>
				<p class="aiohm-card-subtitle"><?php esc_html_e( 'Here\'s what to expect after your booking.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
		<div class="aiohm-checkout-accommodation-details">
			<ul class="aiohm-steps-list">
				<?php if ( 'pending' === $booking->status ) : ?>
				<li><?php esc_html_e( 'Check your email for payment instructions', 'aiohm-booking-pro' ); ?></li>
				<li><?php esc_html_e( 'Complete payment within 48 hours to secure your booking', 'aiohm-booking-pro' ); ?></li>
				<li><?php esc_html_e( 'Receive booking confirmation once payment is processed', 'aiohm-booking-pro' ); ?></li>
				<?php else : ?>
				<li><?php esc_html_e( 'You will receive a confirmation email shortly', 'aiohm-booking-pro' ); ?></li>
					<?php if ( $is_event_booking ) : ?>
				<li><?php esc_html_e( 'Event details and access instructions will be sent before the event date', 'aiohm-booking-pro' ); ?></li>
				<?php else : ?>
				<li><?php esc_html_e( 'Check-in instructions will be sent before your arrival', 'aiohm-booking-pro' ); ?></li>
				<?php endif; ?>
				<?php endif; ?>
				<li><?php esc_html_e( 'Contact us if you have any questions', 'aiohm-booking-pro' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Contact Information Card -->
	<div class="aiohm-checkout-card">
		<div class="aiohm-booking-shortcode-card-header">
			<div class="aiohm-card-icon">📞</div>
			<div class="aiohm-card-title-section">
				<h3 class="aiohm-card-title"><?php esc_html_e( 'Need Help?', 'aiohm-booking-pro' ); ?></h3>
				<p class="aiohm-card-subtitle"><?php esc_html_e( 'Get in touch if you have any questions about your booking.', 'aiohm-booking-pro' ); ?></p>
			</div>
		</div>
		<div class="aiohm-checkout-accommodation-details">
			<div class="aiohm-accommodation-pricing">
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Email:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value"><?php echo esc_html( get_option( 'admin_email' ) ); ?></span>
				</div>
				<div class="aiohm-guests-row">
					<span class="aiohm-label"><?php esc_html_e( 'Booking Reference:', 'aiohm-booking-pro' ); ?></span>
					<span class="aiohm-value">#<?php echo esc_html( $booking->id ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>


