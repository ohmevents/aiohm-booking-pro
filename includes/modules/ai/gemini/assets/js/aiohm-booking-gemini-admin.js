/**
 * Gemini Module - Admin JavaScript
 *
 * @package AIOHM_Booking
 * @since 1.2.4
 */

(function($) {
    'use strict';

    /**
     * Gemini Admin Handler
     */
    window.AIOHMBookingGeminiAdmin = {
        
        /**
         * Initialize Gemini admin functionality
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind Gemini-specific events
         */
        bindEvents: function() {
            $(document).off('click.aiohm-gemini', '#test-gemini-connection');
            $(document).on('click.aiohm-gemini', '#test-gemini-connection', this.testConnection.bind(this));
        },

        /**
         * Test Gemini connection
         */
        testConnection: function(e) {
            e.preventDefault();

            var $button = $(e.target).closest('button');
            var originalText = $button.html();

            // Disable button and show loading state
            $button.prop('disabled', true);
            $button.html('<span class="dashicons dashicons-update spin"></span> Testing...');

            // Remove existing result
            this.removeExistingResult();

            // Get the nonce
            var nonce = (window.aiohm_gemini && window.aiohm_gemini.nonce) || 
                       (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) || '';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_test_gemini',
                    nonce: nonce
                },
                success: function(response) {
                    var message;
                    if (response.success) {
                        message = (response.data && response.data.message) || 'Gemini connection successful!';
                        this.showResult('success', message);
                    } else {
                        message = (response.data && response.data.message) || 'Gemini connection failed';
                        this.showResult('error', message);
                    }
                }.bind(this),
                error: function() {
                    this.showResult('error', 'Gemini connection test failed - network error');
                }.bind(this),
                complete: function() {
                    // Restore button
                    $button.prop('disabled', false);
                    $button.html(originalText);
                }
            });
        },

        /**
         * Show test result
         */
        showResult: function(type, message) {
            var resultClass = type === 'success' ? 'notice-success' : 'notice-error';
            var resultHtml = '<div class="aiohm-test-result notice ' + resultClass + ' inline"><p>' + message + '</p></div>';
            $('#test-gemini-connection').after(resultHtml);
        },

        /**
         * Remove existing test result
         */
        removeExistingResult: function() {
            $('.aiohm-test-result').remove();
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        if (window.AIOHMBookingGeminiAdmin) {
            window.AIOHMBookingGeminiAdmin.init();
        }
    });

})(jQuery);