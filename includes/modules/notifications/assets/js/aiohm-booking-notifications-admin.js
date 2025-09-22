/**
 * AIOHM Booking Notifications Admin JavaScript
 * Handles email template customization and preview functionality
 *
 * @package AIOHM_Booking
 * @version 1.1.2
 */

(function($) {
    'use strict';

    // Main notifications admin object
    window.AIOHM_Booking_Notifications = {

        init: function() {
            // Notifications-specific initialization
            this.bindEvents();
            this.initEmailLogs();
        },

        bindEvents: function() {
            // Notifications-specific event handlers (base events are handled automatically)
            $(document).on('click', '.aiohm-booking-notifications-test-smtp-btn', this.testSMTPConnection);
            $(document).on('change', 'select[name="settings[email_provider]"]', this.updateTestButtonText);
            $(document).on('change', '#email-template-selector', this.loadEmailTemplate);
            $(document).on('change', 'select[name="template_timing"]', this.handleTimingChange);
            $(document).on('click', '#save-template', this.saveEmailTemplate);
            $(document).on('click', '#reset-template', this.resetEmailTemplate);
            $(document).on('click', '#preview-email-template', this.previewEmailTemplate);
            $(document).on('click', '#send-test-email', this.sendTestEmail);
            $(document).on('click', '#refresh-email-logs', this.refreshEmailLogs);
            $(document).on('click', '#clear-email-logs', this.clearEmailLogs);
            
            // Email template customization events
            $(document).on('click', '.aiohm-booking-notifications-tab-button', this.handleTabSwitch);
            $(document).on('change', '.aiohm-booking-notifications-color-input', this.handleColorChange);
            $(document).on('click', '.aiohm-booking-notifications-preset-btn', this.handlePresetClick);
            $(document).on('click', '#close-preview', this.closeEmailPreview);
            $(document).on('click', '#refresh-preview', this.refreshEmailPreview);
        },

        initComponents: function() {
            // Additional notification components (base components are initialized automatically)
            // Any additional initialization here
        },

        initEmailLogs: function() {
            // Load initial email logs if the container exists
            if ($('#aiohm-email-logs-ul').length) {
                this.loadEmailLogs();
            }
            
            // Initialize email template customization
            this.initEmailTemplateCustomization();

            // Initialize test button text
            this.updateTestButtonText.call($('select[name="settings[email_provider]"]'));
        },

        initEmailTemplateCustomization: function() {
            // Set default tab if none is active
            if (!$('.aiohm-booking-notifications-tab-button.active').length) {
                $('.aiohm-booking-notifications-tab-button:first').addClass('active');
                $('.aiohm-booking-notifications-tab-content:first').addClass('active');
            }
            
            // Initialize color value displays
            $('.aiohm-booking-notifications-color-input').each(function() {
                var $input = $(this);
                var colorValue = $input.val();
                var inputId = $input.attr('id');
                var valueId = inputId.replace('-input', '-value');
                $('#' + valueId).text(colorValue);
            });
            
            // Set default preset if none selected
            if (!$('.aiohm-booking-notifications-preset-btn.active').length) {
                $('.aiohm-booking-notifications-preset-btn:first').addClass('active');
            }
        },

        // AJAX functionality only - UI events handled by inline script
        testSMTPConnection: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $result = $('.aiohm-booking-notifications-test-result');

            // Update button text based on provider
            var provider = $('select[name="settings[email_provider]"]').val();
            var buttonText = (provider === 'wordpress' || provider === '') ? 'Testing WordPress Mail...' : 'Testing SMTP Connection...';

            $button.prop('disabled', true).text(buttonText);

            $.ajax({
                url: aiohm_booking_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_test_smtp',
                    nonce: aiohm_booking_notifications.nonce,
                    settings: AIOHM_Booking_Notifications.getSMTPSettings()
                },
                success: function(response) {
                    var provider = $('select[name="settings[email_provider]"]').val();
                    var buttonText = (provider === 'wordpress' || provider === '') ? 'Test WordPress Mail' : 'Test SMTP Connection';
                    
                    $button.prop('disabled', false).text(buttonText);

                    if (response.success) {
                        $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    var provider = $('select[name="settings[email_provider]"]').val();
                    var buttonText = (provider === 'wordpress' || provider === '') ? 'Test WordPress Mail' : 'Test SMTP Connection';
                    
                    $button.prop('disabled', false).text(buttonText);
                    $result.html('<div class="notice notice-error"><p>Connection failed. Please check your settings.</p></div>');
                }
            });
        },

        getSMTPSettings: function() {
            return {
                email_provider: $('select[name="settings[email_provider]"]').val(),
                smtp_host: $('input[name="settings[smtp_host]"]').val(),
                smtp_port: $('input[name="settings[smtp_port]"]').val(),
                smtp_encryption: $('select[name="settings[smtp_encryption]"]').val(),
                smtp_username: $('input[name="settings[smtp_username]"]').val(),
                smtp_password: $('input[name="settings[smtp_password]"]').val(),
                from_email: $('input[name="settings[from_email]"]').val(),
                from_name: $('input[name="settings[from_name]"]').val()
            };
        },

        updateTestButtonText: function() {
            var provider = $(this).val();
            var buttonText = (provider === 'wordpress' || provider === '') ? 'Test WordPress Mail' : 'Test SMTP Connection';
            $('.aiohm-booking-notifications-test-smtp-btn').text(buttonText);
        },

        formatEmailLogsHTML: function(logs) {
            if (!Array.isArray(logs) || logs.length === 0) {
                return '<li>No email logs found.</li>';
            }

            var html = '';
            logs.forEach(function(log) {
                var statusClass = log.status === 'sent' ? 'success' : 'error';
                html += '<li class="' + statusClass + '">' +
                    '<strong>' + log.type + '</strong> to ' + log.recipient + ' - ' + log.time + ' (' + log.status + ')' +
                    '</li>';
            });
            return html;
        },

        loadEmailTemplate: function(e) {
            e.preventDefault();
            var $selector = $(this);
            var templateKey = $selector.val();

            if (!templateKey) {
                $('#template-editor').addClass('hidden');
                return;
            }

            // Check if the notifications object exists
            if (typeof aiohm_booking_notifications === 'undefined') {
                AIOHM_Booking_Notifications.showNotice('Notifications object not found. Please reload the page.', 'error');
                return;
            }

            // Check if templates exist
            if (!aiohm_booking_notifications.templates) {
                AIOHM_Booking_Notifications.showNotice('Templates not loaded. Please reload the page.', 'error');
                return;
            }

            // Get template data from localized script
            var template = aiohm_booking_notifications.templates[templateKey];
            if (!template) {
                AIOHM_Booking_Notifications.showNotice('Template "' + templateKey + '" not found.', 'error');
                return;
            }

            // Check if template editor exists
            if ($('#template-editor').length === 0) {
                AIOHM_Booking_Notifications.showNotice('Template editor not found in DOM.', 'error');
                return;
            }

            // Populate the template editor with the data
            
            $('#template-subject').val(template.subject || '');
            $('#template-content').val(template.content || '');
            $('select[name="template_status"]').val(template.status || 'enabled');
            $('select[name="template_timing"]').val(template.timing || 'immediate');
            $('input[name="template_sender_name"]').val(template.sender_name || '');
            $('input[name="template_reply_to"]').val(template.reply_to || '');

            // Handle custom timing fields
            if (template.timing === 'custom') {
                $('input[name="template_custom_date"]').val(template.custom_date || '');
                $('input[name="template_custom_time"]').val(template.custom_time || '');
                $('.aiohm-booking-notifications-custom-schedule-fields').show();
            } else {
                $('.aiohm-booking-notifications-custom-schedule-fields').hide();
            }

            // Show the template editor
            $('#template-editor').removeClass('aiohm-booking-notifications-hidden').show();

            // Scroll to the editor
            if ($('#template-editor')[0]) {
                $('#template-editor')[0].scrollIntoView({ behavior: 'smooth' });
            }
        },

        handleTimingChange: function(e) {
            var timing = $(this).val();
            if (timing === 'custom') {
                $('.aiohm-booking-notifications-custom-schedule-fields').show();
            } else {
                $('.aiohm-booking-notifications-custom-schedule-fields').hide();
            }
        },

        saveEmailTemplate: function(e) {
            e.preventDefault();
            var $button = $(this);
            var templateKey = $('#email-template-selector').val();

            if (!templateKey) {
                AIOHM_Booking_Notifications.showNotice('Please select a template first.', 'error');
                return;
            }

            $button.prop('disabled', true).text('Saving...');

            $.ajax({
                url: aiohm_booking_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_save_email_template',
                    nonce: aiohm_booking_notifications.nonce,
                    template_key: templateKey,
                    subject: $('#template-subject').val(),
                    content: $('#template-content').val(),
                    status: $('select[name="template_status"]').val(),
                    timing: $('select[name="template_timing"]').val(),
                    sender_name: $('input[name="template_sender_name"]').val(),
                    reply_to: $('input[name="template_reply_to"]').val(),
                    custom_date: $('input[name="template_custom_date"]').val(),
                    custom_time: $('input[name="template_custom_time"]').val()
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Save Template');

                    if (response.success) {
                        AIOHM_Booking_Notifications.showNotice(response.data, 'success');
                    } else {
                        AIOHM_Booking_Notifications.showNotice('Failed to save template: ' + response.data, 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Save Template');
                    AIOHM_Booking_Notifications.showNotice('Error saving template.', 'error');
                }
            });
        },

        resetEmailTemplate: function(e) {
            e.preventDefault();
            var $button = $(this);
            var templateKey = $('#email-template-selector').val();

            if (!templateKey) {
                AIOHM_Booking_Notifications.showNotice('Please select a template first.', 'error');
                return;
            }

            if (!confirm('Are you sure you want to reset this template to its default values?')) {
                return;
            }

            $button.prop('disabled', true).text('Resetting...');

            $.ajax({
                url: aiohm_booking_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_reset_email_template',
                    nonce: aiohm_booking_notifications.nonce,
                    template_key: templateKey
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Reset to Default');

                    if (response.success) {
                        // Update the form with default values
                        $('#template-subject').val(response.data.subject);
                        $('#template-content').val(response.data.content);
                        AIOHM_Booking_Notifications.showNotice(response.data.message, 'success');
                    } else {
                        AIOHM_Booking_Notifications.showNotice('Failed to reset template: ' + response.data, 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Reset to Default');
                    AIOHM_Booking_Notifications.showNotice('Error resetting template.', 'error');
                }
            });
        },

        previewEmailTemplate: function(e) {
            e.preventDefault();
            var templateKey = $('#email-template-selector').val();

            if (!templateKey) {
                AIOHM_Booking_Notifications.showNotice('Please select a template first.', 'error');
                return;
            }

            // Update preview content
            $('#preview-subject').text($('#template-subject').val());
            $('#preview-content').html($('#template-content').val().replace(/\n/g, '<br>'));

            // Show the preview
            $('#aiohm-email-preview-card').removeClass('hidden');
            $('#aiohm-email-preview-card')[0].scrollIntoView({ behavior: 'smooth' });
        },

        closeEmailPreview: function(e) {
            e.preventDefault();
            $('#aiohm-email-preview-card').addClass('hidden');
        },

        refreshEmailPreview: function(e) {
            e.preventDefault();
            
            // Update preview content with current template values
            $('#preview-subject').text($('#template-subject').val());
            $('#preview-content').html($('#template-content').val().replace(/\n/g, '<br>'));
            
            AIOHM_Booking_Notifications.showNotice('Preview updated with current template content.', 'success');
        },

        sendTestEmail: function(e) {
            e.preventDefault();
            var $button = $(this);
            var templateKey = $('#email-template-selector').val();

            if (!templateKey) {
                AIOHM_Booking_Notifications.showNotice('Please select a template first.', 'error');
                return;
            }

            var testEmail = prompt('Enter test email address:');
            if (!testEmail || !testEmail.includes('@')) {
                AIOHM_Booking_Notifications.showNotice('Please enter a valid email address.', 'error');
                return;
            }

            $button.prop('disabled', true).text('Sending...');

            $.ajax({
                url: aiohm_booking_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_send_test_email',
                    nonce: aiohm_booking_notifications.nonce,
                    to_email: testEmail,
                    subject: $('#template-subject').val(),
                    content: $('#template-content').val(),
                    sender_name: $('input[name="template_sender_name"]').val(),
                    reply_to: $('input[name="template_reply_to"]').val()
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Send Test');

                    if (response.success) {
                        AIOHM_Booking_Notifications.showNotice(response.data, 'success');
                    } else {
                        AIOHM_Booking_Notifications.showNotice('Failed to send test email: ' + response.data, 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Send Test');
                    AIOHM_Booking_Notifications.showNotice('Error sending test email.', 'error');
                }
            });
        },

        refreshEmailLogs: function(e) {
            e.preventDefault();
            AIOHM_Booking_Notifications.loadEmailLogs();
        },

        clearEmailLogs: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to clear all email logs?')) {
                return;
            }

            $.ajax({
                url: aiohm_booking_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_clear_email_logs',
                    nonce: aiohm_booking_notifications.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#aiohm-email-logs-ul').html('<li>No email logs found.</li>');
                        AIOHM_Booking_Notifications.showNotice('Email logs cleared successfully.', 'success');
                    }
                }
            });
        },

        loadEmailLogs: function() {
            $('#aiohm-email-logs-ul').html('<li class="aiohm-booking-notifications-loading">Loading email logs...</li>');

            $.ajax({
                url: aiohm_booking_notifications.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_get_email_logs',
                    nonce: aiohm_booking_notifications.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var html = AIOHM_Booking_Notifications.formatEmailLogsHTML(response.data);
                        $('#aiohm-email-logs-ul').html(html || '<li>No email logs found.</li>');
                    } else {
                        $('#aiohm-email-logs-ul').html('<li>Error loading logs.</li>');
                    }
                },
                error: function() {
                    $('#aiohm-email-logs-ul').html('<li>Error loading logs.</li>');
                }
            });
        },

        showNotice: function(message, type) {
            // Remove existing notices
            $('.aiohm-admin-notice').remove();

            // Create new notice
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' aiohm-admin-notice"><p>' + message + '</p></div>');

            // Add to page
            $('.aiohm-notification-layout').prepend($notice);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Email Template Customization Functions
        handleTabSwitch: function(e) {
            e.preventDefault();
            var $button = $(this);
            var tabName = $button.data('tab');

            // Remove active class from all tabs
            $('.aiohm-booking-notifications-tab-button').removeClass('active');
            $('.aiohm-booking-notifications-tab-content').removeClass('active');

            // Add active class to clicked tab
            $button.addClass('active');
            $('.aiohm-booking-notifications-tab-content[data-tab="' + tabName + '"]').addClass('active');
        },

        handleColorChange: function(e) {
            var $input = $(this);
            var colorValue = $input.val();
            var inputId = $input.attr('id');
            var valueId = inputId.replace('-input', '-value');
            
            // Update the color value display
            $('#' + valueId).text(colorValue);
            
            // Update preview if available
            AIOHM_Booking_Notifications.updateEmailPreview();
        },

        handlePresetClick: function(e) {
            e.preventDefault();
            var $button = $(this);
            var preset = $button.data('preset');

            // Remove active class from all preset buttons
            $('.aiohm-booking-notifications-preset-btn').removeClass('active');
            
            // Add active class to clicked button
            $button.addClass('active');
            
            // Update hidden preset input
            $('#selected-preset').val(preset);
            
            // Apply preset colors
            AIOHM_Booking_Notifications.applyPreset(preset);
        },

        applyPreset: function(preset) {
            var presets = {
                professional: {
                    primary_color: '#2c3e50',
                    text_color: '#34495e',
                    background_color: '#f8f9fa',
                    content_bg_color: '#ffffff',
                    section_bg_color: '#f1f3f4'
                },
                friendly: {
                    primary_color: '#27ae60',
                    text_color: '#2c3e50',
                    background_color: '#e8f5e8',
                    content_bg_color: '#ffffff',
                    section_bg_color: '#f0f9f0'
                },
                luxury: {
                    primary_color: '#8e44ad',
                    text_color: '#2c3e50',
                    background_color: '#f8f6fc',
                    content_bg_color: '#ffffff',
                    section_bg_color: '#f5f0fa'
                },
                minimalist: {
                    primary_color: '#95a5a6',
                    text_color: '#2c3e50',
                    background_color: '#ffffff',
                    content_bg_color: '#f8f9fa',
                    section_bg_color: '#ffffff'
                }
            };

            if (presets[preset]) {
                var colors = presets[preset];
                
                $('#primary-color-input').val(colors.primary_color);
                $('#primary-color-value').text(colors.primary_color);
                
                $('#text-color-input').val(colors.text_color);
                $('#text-color-value').text(colors.text_color);
                
                $('#background-color-input').val(colors.background_color);
                $('#background-color-value').text(colors.background_color);
                
                $('#content-bg-color-input').val(colors.content_bg_color);
                $('#content-bg-color-value').text(colors.content_bg_color);
                
                $('#section-bg-color-input').val(colors.section_bg_color);
                $('#section-bg-color-value').text(colors.section_bg_color);
                
                // Update preview
                AIOHM_Booking_Notifications.updateEmailPreview();
            }
        },

        resetEmailTemplate: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to reset all email template settings to default?')) {
                return;
            }
            
            // Reset to default colors
            AIOHM_Booking_Notifications.applyPreset('professional');
            
            // Reset text fields
            $('input[name="settings[email_greeting_text]"]').val('Dear {customer_name},');
            $('input[name="settings[email_closing_text]"]').val('Best regards,');
            $('input[name="settings[email_footer_text]"]').val('{site_name}');
            
            AIOHM_Booking_Notifications.showNotice('Email template settings reset to default.', 'success');
        },

        updateEmailPreview: function() {
            // Update preview colors if preview exists
            var primaryColor = $('#primary-color-input').val();
            var textColor = $('#text-color-input').val();
            var backgroundColor = $('#background-color-input').val();
            var contentBgColor = $('#content-bg-color-input').val();
            
            // Update preview elements if they exist
            if ($('#aiohm-email-preview-card').length) {
                $('#aiohm-email-preview-card').css({
                    'background-color': backgroundColor
                });
                
                $('#aiohm-email-preview-card h3').css({
                    'color': primaryColor
                });
                
                $('#aiohm-email-preview-card p, #aiohm-email-preview-card div').css({
                    'color': textColor
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AIOHM_Booking_Notifications.init();
    });

})(jQuery);
