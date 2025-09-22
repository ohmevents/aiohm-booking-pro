/**
 * Stripe Settings Page JavaScript
 *
 * @package AIOHM_Booking_PRO
 * @since   1.0.0
 */

/* <fs_premium_only> */

jQuery(document).ready(function($) {
	// Toggle test/live keys display.
	$('#stripe_test_mode').on('change', function() {
		if ($(this).is(':checked')) {
			$('.live-keys').hide();
			$('.test-keys').show();
		} else {
			$('.test-keys').hide();
			$('.live-keys').show();
		}
	});
  
	// Test connection button.
	$('#test-stripe-connection').on('click', function() {
		var button = $(this);
		button.prop('disabled', true).text(aiohm_stripe_settings.testing_text);
		
		$.post(ajaxurl, {
			action: 'aiohm_booking_test_stripe',
			nonce: aiohm_stripe_settings.nonce
		})
		.done(function(response) {
			if (response.success) {
				button.removeClass('button-secondary').addClass('button-primary')
					.text(aiohm_stripe_settings.success_text);
			} else {
				button.removeClass('button-secondary').addClass('notice-error')
					.text(aiohm_stripe_settings.failed_text);
			}
		})
		.fail(function() {
			button.removeClass('button-secondary').addClass('notice-error')
				.text(aiohm_stripe_settings.failed_text);
		})
		.always(function() {
			setTimeout(function() {
				button.prop('disabled', false)
					.removeClass('button-primary notice-error')
					.addClass('button-secondary')
					.text(aiohm_stripe_settings.test_connection_text);
			}, 3000);
		});
	});
});

/* </fs_premium_only> */