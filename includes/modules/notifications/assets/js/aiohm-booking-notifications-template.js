/**
 * AIOHM Booking Notifications Template JavaScript
 * Extracted from PHP template for better performance and CSP compliance
 * 
 * @package AIOHM_Booking
 * @version 1.2.3
 */

jQuery(document).ready(function($) {
	// Get the current email provider setting from PHP, passed via wp_localize_script
	var currentEmailProvider = (typeof aiohm_booking_notifications !== 'undefined' && aiohm_booking_notifications.email_provider) ? aiohm_booking_notifications.email_provider : 'wordpress';
  
	// Function to toggle SMTP configuration based on email provider
	function toggleSmtpConfiguration() {
		const smtpFields = $('.aiohm-booking-notifications-smtp-fields');
		if (smtpFields.length === 0) return; // Safety check
		
		const smtpRows = smtpFields.find('.aiohm-booking-notifications-setting-row');
		const providerRow = smtpRows.has('select[name="settings[email_provider]"]');
		const smtpConfigRows = smtpRows.not(providerRow);
		
		if (currentEmailProvider === 'wordpress') {
			// Hide SMTP configuration fields with smooth animation, keep only provider select visible
			smtpConfigRows.slideUp(300);
			providerRow.show();
		} else if (currentEmailProvider === 'smtp') {
			// Show all SMTP fields with smooth animation
			smtpRows.slideDown(300);
			smtpRows.removeClass('smtp-disabled');
			smtpFields.find('input, select, button').prop('disabled', false);
		}
	}

	// Initialize on page load
	// Also check the actual dropdown value as a fallback
	var $providerSelect = $('select[name="settings[email_provider]"]');
	if ($providerSelect.length && $providerSelect.val()) {
		currentEmailProvider = $providerSelect.val();
	}
	
	// Initial toggle without animation for page load
	initializeSmtpConfiguration();
	
	// Initialize the toggle function for initial page load (without animation)
	function initializeSmtpConfiguration() {
		const smtpFields = $('.aiohm-booking-notifications-smtp-fields');
		const smtpRows = smtpFields.find('.aiohm-booking-notifications-setting-row');
		const providerRow = smtpRows.has('select[name="settings[email_provider]"]');
		const smtpConfigRows = smtpRows.not(providerRow);
		
		if (currentEmailProvider === 'wordpress') {
			// Hide SMTP configuration fields immediately on page load
			smtpConfigRows.hide();
			providerRow.show();
		} else {
			// Show all SMTP fields
			smtpRows.show();
		}
	}

	// Handle provider selection change
	$(document).on('change', 'select[name="settings[email_provider]"]', function() {
		currentEmailProvider = $(this).val();
		toggleSmtpConfiguration();
	});

	// Handle preset button clicks
	$(document).on('click', '.aiohm-booking-notifications-preset-btn', function() {
		const presetType = $(this).data('preset');
		
		// Update the hidden input field
		$('#selected-preset').val(presetType);
		
		// Remove active class from all buttons
		$('.aiohm-booking-notifications-preset-btn').removeClass('active');
		
		// Add active class to clicked button
		$(this).addClass('active');
		
		// Apply preset styles instantly
		applyPresetStyles(presetType);
		
		// Show success message
		showPresetMessage('Preset "' + presetType.charAt(0).toUpperCase() + presetType.slice(1) + '" applied successfully!');
	});

	// Function to apply preset styles
	function applyPresetStyles(presetType) {
		const presets = {
			professional: {
				primaryColor: '#457d58',
				textColor: '#333333',
				backgroundColor: '#f9f9f9',
				contentBgColor: '#ffffff',
				sectionBgColor: '#f5f5f5'
			},
			friendly: {
				primaryColor: '#4a90e2',
				textColor: '#333333',
				backgroundColor: '#f0f8ff',
				contentBgColor: '#ffffff',
				sectionBgColor: '#e6f3ff'
			},
			luxury: {
				primaryColor: '#8b4513',
				textColor: '#2c1810',
				backgroundColor: '#faf6f0',
				contentBgColor: '#ffffff',
				sectionBgColor: '#f5f0e8'
			},
			minimalist: {
				primaryColor: '#666666',
				textColor: '#333333',
				backgroundColor: '#ffffff',
				contentBgColor: '#ffffff',
				sectionBgColor: '#f8f8f8'
			}
		};

		const preset = presets[presetType];
		if (preset) {
			// Update color inputs
			$('input[name="settings[email_primary_color]"]').val(preset.primaryColor);
			$('input[name="settings[email_text_color]"]').val(preset.textColor);
			$('input[name="settings[email_background_color]"]').val(preset.backgroundColor);
			$('input[name="settings[email_content_bg_color]"]').val(preset.contentBgColor);
			$('input[name="settings[email_section_bg_color]"]').val(preset.sectionBgColor);
		  
			// Update preview if visible
			updateEmailPreview(preset);
		}
	}

	// Function to update email preview
	function updateEmailPreview(preset) {
		// This would update the live preview if it's visible
		// For now, just log that preview would be updated
	}

	// Function to show preset success message
	function showPresetMessage(message) {
		// Remove any existing message
		$('.preset-message').remove();
		
		// Create and show new message
		const messageDiv = $('<div class="preset-message notice notice-success is-dismissible">' + message + '</div>');
		$('.aiohm-booking-notifications-template-presets').append(messageDiv);
		
		// Auto-hide after 3 seconds
		setTimeout(function() {
			messageDiv.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}

	// Initialize preset button state on page load
	$(document).ready(function() {
		const currentPreset = $('#selected-preset').val();
		if (currentPreset) {
			$('.aiohm-booking-notifications-preset-btn[data-preset="' + currentPreset + '"]').addClass('active');
		}
	});

	// Handle tab switching
	$(document).on('click', '.aiohm-booking-notifications-tab-button', function() {
		const tabName = $(this).data('tab');
		
		// Remove active class from all tabs
		$('.aiohm-booking-notifications-tab-button').removeClass('active');
		$('.aiohm-booking-notifications-tab-content').removeClass('active');
		
		// Add active class to clicked tab
		$(this).addClass('active');
		$('.aiohm-booking-notifications-tab-content[data-tab="' + tabName + '"]').addClass('active');
	});

	// Handle Preview Template button
	$(document).on('click', '#preview-email-template', function() {
		const colors = {
			primary: $('input[name="settings[email_primary_color]"]').val(),
			text: $('input[name="settings[email_text_color]"]').val(),
			background: $('input[name="settings[email_background_color]"]').val(),
			contentBg: $('input[name="settings[email_content_bg_color]"]').val(),
			sectionBg: $('input[name="settings[email_section_bg_color]"]').val()
		};

		const textSettings = {
			greeting: $('input[name="settings[email_greeting_text]"]').val(),
			closing: $('input[name="settings[email_closing_text]"]').val(),
			footer: $('input[name="settings[email_footer_text]"]').val()
		};

		// Show email preview card
		$('#aiohm-email-preview-card').removeClass('hidden');
		
		// Update preview with current settings
		updateEmailPreviewWithSettings(colors, textSettings);
		
		// Scroll to preview
		$('#aiohm-email-preview-card')[0].scrollIntoView({ behavior: 'smooth' });
	});

	// Function to update email preview with current settings
	function updateEmailPreviewWithSettings(colors, textSettings) {
		// Update colors in preview
		const previewCard = $('#aiohm-email-preview-card .aiohm-booking-notifications-email-client');
		
		// Apply background colors
		previewCard.css('background-color', colors.background);
		previewCard.find('.aiohm-booking-notifications-email-body').css('background-color', colors.contentBg);
		previewCard.find('.aiohm-booking-notifications-email-header').css('background-color', colors.contentBg);
		
		// Apply text colors
		previewCard.find('*').css('color', colors.text);
		previewCard.find('h3, strong').css('color', colors.primary);
		
		// Update text content
		if (textSettings.greeting) {
			$('#preview-content').html(function(index, html) {
				return html.replace(/^[^<]*/, textSettings.greeting);
			});
		}
		
		if (textSettings.closing) {
			$('#preview-content').html(function(index, html) {
				return html.replace(/Best regards[^<]*/, textSettings.closing);
			});
		}
		
		if (textSettings.footer) {
			$('#preview-content').html(function(index, html) {
				return html.replace(/\{site_name\}/, textSettings.footer);
			});
		}
	}

	// Handle real-time color updates
	$(document).on('input change', '.aiohm-booking-notifications-color-input', function() {
		const colorType = $(this).attr('name').replace('settings[email_', '').replace('_color]', '');
		const colorValue = $(this).val();
		
		// Update the color value display
		updateColorValueDisplay(colorType, colorValue);
		
		// Update preview in real-time if visible
		if (!$('#aiohm-email-preview-card').hasClass('hidden')) {
			updateColorInPreview(colorType, colorValue);
		}
	});

	// Function to update color value display
	function updateColorValueDisplay(colorType, colorValue) {
		const valueDisplays = {
			'primary': '#primary-color-value',
			'text': '#text-color-value', 
			'background': '#background-color-value',
			'content_bg': '#content-bg-color-value',
			'section_bg': '#section-bg-color-value'
		};
		
		const displayId = valueDisplays[colorType];
		if (displayId) {
			$(displayId).text(colorValue.toUpperCase());
		}
	}

	// Function to update specific color in preview
	function updateColorInPreview(colorType, colorValue) {
		const previewCard = $('#aiohm-email-preview-card .aiohm-email-client');
		
		switch(colorType) {
			case 'primary':
				previewCard.find('h3, strong').css('color', colorValue);
				break;
			case 'text':
				previewCard.find('*').css('color', colorValue);
				break;
			case 'background':
				previewCard.css('background-color', colorValue);
				break;
			case 'content_bg':
				previewCard.find('.aiohm-email-body, .aiohm-email-header').css('background-color', colorValue);
				break;
			case 'section_bg':
				// This would apply to section backgrounds in the preview
				break;
		}
	}

	// Helper function to get current colors
	function getCurrentColors() {
		return {
			primary: $('input[name="settings[email_primary_color]"]').val(),
			text: $('input[name="settings[email_text_color]"]').val(),
			background: $('input[name="settings[email_background_color]"]').val(),
			contentBg: $('input[name="settings[email_content_bg_color]"]').val(),
			sectionBg: $('input[name="settings[email_section_bg_color]"]').val()
		};
	}

	// Handle text input changes for real-time preview
	$(document).on('input', '.aiohm-booking-notifications-text-input', function() {
		if (!$('#aiohm-email-preview-card').hasClass('hidden')) {
			const textSettings = {
				greeting: $('input[name="settings[email_greeting_text]"]').val(),
				closing: $('input[name="settings[email_closing_text]"]').val(),
				footer: $('input[name="settings[email_footer_text]"]').val()
			};
			updateEmailPreviewWithSettings(getCurrentColors(), textSettings);
		}
	});

	// Handle Reset Template button
	$(document).on('click', '#reset-email-template', function() {
		if (confirm('Are you sure you want to reset all email template settings to default values?')) {
			// Reset to default professional theme
			const defaultColors = {
				primary: '#457d58',
				text: '#333333',
				background: '#f9f9f9',
				contentBg: '#ffffff',
				sectionBg: '#f5f5f5'
			};

			const defaultText = {
				greeting: 'Dear {customer_name},',
				closing: 'Best regards,',
				footer: '{site_name}'
			};

			// Apply default colors
			$('input[name="settings[email_primary_color]"]').val(defaultColors.primary);
			$('input[name="settings[email_text_color]"]').val(defaultColors.text);
			$('input[name="settings[email_background_color]"]').val(defaultColors.background);
			$('input[name="settings[email_content_bg_color]"]').val(defaultColors.contentBg);
			$('input[name="settings[email_section_bg_color]"]').val(defaultColors.sectionBg);

			// Apply default text
			$('input[name="settings[email_greeting_text]"]').val(defaultText.greeting);
			$('input[name="settings[email_closing_text]"]').val(defaultText.closing);
			$('input[name="settings[email_footer_text]"]').val(defaultText.footer);

			// Update color value displays
			Object.keys(defaultColors).forEach(function(key) {
				updateColorValueDisplay(key, defaultColors[key]);
			});

			// Update preset selection
			$('#selected-preset').val('professional');
			$('.aiohm-booking-notifications-preset-btn').removeClass('active');
			$('.aiohm-booking-notifications-preset-btn[data-preset="professional"]').addClass('active');

			// Show success message
			showTemplateMessage('Template settings reset to default values!', 'success');
		}
	});

	// Function to show template messages
	function showTemplateMessage(message, type = 'success') {
		// Remove any existing message
		$('.template-message').remove();
		
		// Create and show new message
		const messageDiv = $('<div class="template-message notice notice-' + type + ' is-dismissible">' + message + '</div>');
		$('.aiohm-booking-notifications-template-customization').prepend(messageDiv);
		
		// Auto-hide after 3 seconds
		setTimeout(function() {
			messageDiv.fadeOut(300, function() {
				$(this).remove();
			});
		}, 3000);
	}
});
