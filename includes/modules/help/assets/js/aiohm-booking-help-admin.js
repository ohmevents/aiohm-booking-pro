/**
 * AIOHM Booking Help Admin JavaScript
 *
 * Handles help page functionality including debug info collection,
 * support requests, and feature requests.
 *
 * @package AIOHM Booking
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const AIOHMBookingHelp = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Debug functionality
            $(document).on('click', '#collect-debug-info', this.collectDebugInfo.bind(this));
            $(document).on('click', '#copy-debug-info', this.copyDebugInfo.bind(this));
            $(document).on('click', '#download-debug-info', this.downloadDebugInfo.bind(this));

            // Support request form
            $(document).on('submit', '#aiohm-support-form', this.submitSupportRequest.bind(this));

            // Feature request form  
            $(document).on('submit', '#aiohm-feature-form', this.submitFeatureRequest.bind(this));
        },

        collectDebugInfo: function() {
            const $button = $('#collect-debug-info');
            const $debugText = $('#debug-text');
            
            $button.prop('disabled', true).text('Collecting...');
            
            this.showLoading();
            
            const requestData = {
                action: 'aiohm_collect_debug_info',
                nonce: this.settings.nonce,
                include_system: $('#include-system-info').is(':checked'),
                include_plugins: $('#include-plugin-info').is(':checked'),
                include_errors: $('#include-error-logs').is(':checked')
            };
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.success) {
                        $debugText.val(response.data);
                        $('.debug-actions').show();
                    } else {
                        this.showNotice('Error collecting debug information: ' + response.data, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotice('Failed to collect debug information', 'error');
                }.bind(this),
                complete: function() {
                    $button.prop('disabled', false).text('Collect Debug Info');
                    this.hideLoading();
                }.bind(this)
            });
        },

        copyDebugInfo: function() {
            const debugText = $('#debug-text').val();
            
            if (!debugText) {
                this.showNotice('No debug information to copy', 'error');
                return;
            }
            
            navigator.clipboard.writeText(debugText).then(function() {
                this.showNotice('Debug information copied to clipboard', 'success');
            }.bind(this)).catch(function() {
                // Fallback for older browsers
                const $temp = $('<textarea>').val(debugText).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                this.showNotice('Debug information copied to clipboard', 'success');
            }.bind(this));
        },

        downloadDebugInfo: function(e) {
            e.preventDefault();

            const $textarea = $('#debug-text');
            const debugInfo = $textarea.val();

            if (!debugInfo) {
                this.showMessage(aiohm_booking_help_ajax.i18n.noDebugToDownload, 'error');
                return;
            }

            const blob = new Blob([debugInfo], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'aiohm-booking-debug-report.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.showMessage(aiohm_booking_help_ajax.i18n.debugDownloaded, 'success');
        },

        submitSupportRequest: function(e) {
            e.preventDefault();

            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            const formData = new FormData($form[0]);
            formData.append('action', 'aiohm_booking_submit_support_request');
            formData.append('nonce', aiohm_booking_help_ajax.nonce);

            $submitBtn.prop('disabled', true).text('Submitting...');

            $.ajax({
                url: aiohm_booking_help_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showMessage(
                            aiohm_booking_help_ajax.i18n.requestSubmitted.replace('%s', aiohm_booking_help_ajax.i18n.supportRequest),
                            'success'
                        );
                        $form[0].reset();
                    } else {
                        this.showMessage(
                            response.data.message || aiohm_booking_help_ajax.i18n.requestError + (response.data.message || aiohm_booking_help_ajax.i18n.unknownError),
                            'error'
                        );
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage(aiohm_booking_help_ajax.i18n.requestServerError, 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        submitFeatureRequest: function(e) {
            e.preventDefault();

            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            const formData = new FormData($form[0]);
            formData.append('action', 'aiohm_booking_submit_feature_request');
            formData.append('nonce', aiohm_booking_help_ajax.nonce);

            $submitBtn.prop('disabled', true).text('Submitting...');

            $.ajax({
                url: aiohm_booking_help_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showMessage(
                            aiohm_booking_help_ajax.i18n.requestSubmitted.replace('%s', aiohm_booking_help_ajax.i18n.featureRequest),
                            'success'
                        );
                        $form[0].reset();
                    } else {
                        this.showMessage(
                            response.data.message || aiohm_booking_help_ajax.i18n.requestError + (response.data.message || aiohm_booking_help_ajax.i18n.unknownError),
                            'error'
                        );
                    }
                },
                error: (xhr, status, error) => {
                    this.showMessage(aiohm_booking_help_ajax.i18n.requestServerError, 'error');
                },
                complete: () => {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        showMessage: function(message, type) {
            const $messages = $('#support-messages');
            const $message = $('<div class="notice notice-' + (type === 'success' ? 'success' : 'error') + ' is-dismissible"><p>' + message + '</p></div>');

            $messages.html($message);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $message.fadeOut(() => {
                    $message.remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AIOHMBookingHelp.init();
    });

})(jQuery);
