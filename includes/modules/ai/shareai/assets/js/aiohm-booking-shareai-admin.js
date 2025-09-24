/**
 * ShareAI Module - Admin JavaScript
 *
 * @package AIOHM_Booking
 * @since 1.2.4
 */

(function($) {
    'use strict';

    /**
     * ShareAI Admin Handler
     */
    window.AIOHMBookingShareAIAdmin = {
        
        /**
         * Initialize ShareAI admin functionality
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind ShareAI-specific events
         */
        bindEvents: function() {
            $(document).off('click.aiohm-shareai', '#test-shareai-connection');
            $(document).on('click.aiohm-shareai', '#test-shareai-connection', this.testConnection.bind(this));
        },

        /**
         * Test ShareAI connection
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

            // Get the nonce from the button's data attribute
            var nonce = $button.data('nonce') || window.aiohm_shareai.nonce || '';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_test_shareai',
                    nonce: nonce
                },
                success: function(response) {
                    var message;
                    if (response.success) {
                        message = (response.data && response.data.message) || 'ShareAI connection successful!';
                        this.showResult('success', message);
                    } else {
                        message = (response.data && response.data.message) || 'ShareAI connection failed';
                        this.showResult('error', message);
                    }
                }.bind(this),
                error: function() {
                    this.showResult('error', 'ShareAI connection test failed - network error');
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
            $('#test-shareai-connection').after(resultHtml);
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
        if (window.AIOHMBookingShareAIAdmin) {
            window.AIOHMBookingShareAIAdmin.init();
        }
    });

})(jQuery);