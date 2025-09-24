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
  
	// Test connection button - Use the AJAX handler from Stripe module
	$('#test-stripe-connection').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation(); // Prevent other handlers from firing
		
		var button = $(this);
		var originalText = button.text();
		button.prop('disabled', true).text(aiohm_stripe_settings.testing_text);
		
		$.post(aiohm_stripe_settings.ajax_url, {
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
					.text(originalText);
			}, 3000);
		});
	});

	// Handle Stripe settings save button
	$(document).on('click', '#aiohm-stripe-settings button[name="save_stripe_settings"], #aiohm-stripe-settings .aiohm-btn--save', function(e) {
		e.preventDefault();

		var $button = $(this);
		var $container = $button.closest('#aiohm-stripe-settings');
		var originalText = $button.text();

		if ($button.prop('disabled')) {
			return;
		}

		$button.prop('disabled', true).text('Saving...');

		var ajaxData = {
			action: 'aiohm_booking_save_stripe_settings',
			nonce: aiohm_stripe_settings.nonce
		};

		$container.find('input').each(function() {
			var $input = $(this);
			var name = $input.attr('name');
			var value = $input.val();
			
			if (name && name !== '') {
				ajaxData[name] = value;
			}
		});

		$.ajax({
			url: aiohm_stripe_settings.ajax_url,
			type: 'POST',
			data: ajaxData,
			success: function(response) {
				$button.prop('disabled', false).text(originalText);

				if (response.success) {
					if (typeof AIOHM_Booking_Settings_Admin !== 'undefined' && AIOHM_Booking_Settings_Admin.showNotice) {
						AIOHM_Booking_Settings_Admin.showNotice(response.data.message || 'Stripe settings saved successfully!', 'success');
					} else {
						var $notice = $('<div class="notice notice-success" style="margin: 15px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;"><p><strong>✅ ' + (response.data.message || 'Stripe settings saved successfully!') + '</strong></p></div>');
						$button.closest('.aiohm-card').prepend($notice);
						setTimeout(function() { $notice.fadeOut(); }, 10000);
					}
				} else {
					var errorMsg = response.data ? (response.data.message || JSON.stringify(response.data)) : 'Failed to save Stripe settings';
					if (typeof AIOHM_Booking_Settings_Admin !== 'undefined' && AIOHM_Booking_Settings_Admin.showNotice) {
						AIOHM_Booking_Settings_Admin.showNotice('Error: ' + errorMsg, 'error');
					} else {
						var $notice = $('<div class="notice notice-error" style="margin: 15px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;"><p><strong>❌ Error: ' + errorMsg + '</strong></p></div>');
						$button.closest('.aiohm-card').prepend($notice);
						setTimeout(function() { $notice.fadeOut(); }, 15000);
					}
				}
			},
			error: function(xhr, status, error) {
				$button.prop('disabled', false).text(originalText);
				if (typeof AIOHM_Booking_Settings_Admin !== 'undefined' && AIOHM_Booking_Settings_Admin.showNotice) {
					AIOHM_Booking_Settings_Admin.showNotice('Network error while saving Stripe settings', 'error');
				} else {
					alert('❌ Network error while saving Stripe settings');
				}
			}
		});
	});
});

/* </fs_premium_only> */