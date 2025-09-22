/**
 * Events Template JavaScript
 * Handles modal interactions and event booking functionality
 *
 * @package AIOHM_Booking_PRO
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
	// Handle quick booking modal
	$('.aiohm-book-event').on('click', function(e) {
		e.preventDefault();

		const eventId = $(this).data('event-id');
		const eventTitle = $(this).data('event-title');

		$('#modal-event-id').val(eventId);
		$('#modal-event-title').text(eventTitle);
		$('#aiohm-event-booking-modal').fadeIn();
	});

	// Close modal
	$('.aiohm-modal-close, .aiohm-modal').on('click', function(e) {
		if (e.target === this) {
			$('#aiohm-event-booking-modal').fadeOut();
		}
	});

	// Handle quick booking form submission
	$('#quick-event-booking-form').on('submit', function(e) {
		e.preventDefault();

		// This would typically submit via AJAX
		// For now, redirect to main booking form
		const eventId = $('#modal-event-id').val();
		
		// Check if booking URL is available
		let bookingUrl;
		if (typeof aiohm_booking_frontend !== 'undefined' && aiohm_booking_frontend.booking_url) {
			bookingUrl = aiohm_booking_frontend.booking_url + '?event_id=' + eventId;
		} else {
			// Fallback to current site URL with booking parameter
			bookingUrl = window.location.origin + '/booking/?event_id=' + eventId;
		}
		
		window.location.href = bookingUrl;
	});
});
