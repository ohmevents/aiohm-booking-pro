/**
 * OpenAI Module - Admin JavaScript
 *
 * @package AIOHM_Booking
 * @since 1.2.4
 */

(function($) {
    'use strict';

    /**
     * OpenAI Admin Handler
     */
    window.AIOHMBookingOpenAIAdmin = {
        
        /**
         * Initialize OpenAI admin functionality
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind OpenAI-specific events
         */
        bindEvents: function() {
            $(document).off('click.aiohm-openai', '#test-openai-connection');
            $(document).on('click.aiohm-openai', '#test-openai-connection', this.testConnection.bind(this));
        },

        /**
         * Test OpenAI connection
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
            var nonce = (window.aiohm_openai && window.aiohm_openai.nonce) || 
                       (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) || '';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_test_openai',
                    nonce: nonce
                },
                success: function(response) {
                    var message;
                    if (response.success) {
                        message = (response.data && response.data.message) || 'OpenAI connection successful!';
                        this.showResult('success', message);
                    } else {
                        message = (response.data && response.data.message) || 'OpenAI connection failed';
                        this.showResult('error', message);
                    }
                }.bind(this),
                error: function() {
                    this.showResult('error', 'OpenAI connection test failed - network error');
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
            $('#test-openai-connection').after(resultHtml);
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
        if (window.AIOHMBookingOpenAIAdmin) {
            window.AIOHMBookingOpenAIAdmin.init();
        }
    });

})(jQuery);