/**
 * Stripe Payment Module - Checkout JavaScript
 *
 * @package AIOHM_Booking_PRO
 * @since 1.2.4
 */

/* <fs_premium_only> */

(function() {
    'use strict';

    /**
     * Stripe Payment Handler
     */
    window.AIOHMBookingStripeCheckout = {
        
        /**
         * Process Stripe payment
         * @param {string} bookingId - The booking ID
         * @param {HTMLElement} button - The payment button
         */
        processPayment: function(bookingId, button) {
            if (!bookingId) {
                console.error('Stripe checkout: Missing booking ID');
                return;
            }

            const originalText = button.textContent;
            button.textContent = (aiohm_booking_frontend?.i18n?.processing) || 'Processing...';
            button.disabled = true;

            // Create Stripe checkout session
            fetch((aiohm_booking_frontend?.ajax_url) || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'aiohm_booking_stripe_create_session',
                    booking_id: bookingId,
                    nonce: (aiohm_booking_frontend?.nonce) || ''
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to Stripe Checkout
                    window.location.href = data.data.url;
                } else {
                    this.handleError(data.data.message || 'Payment failed. Please try again.', button, originalText);
                }
            })
            .catch(error => {
                console.error('Stripe payment error:', error);
                this.handleError('Payment failed. Please try again.', button, originalText);
            });
        },

        /**
         * Handle payment errors
         * @param {string} message - Error message
         * @param {HTMLElement} button - Payment button
         * @param {string} originalText - Original button text
         */
        handleError: function(message, button, originalText) {
            alert(message);
            button.textContent = originalText || (aiohm_booking_frontend?.i18n?.complete_booking) || 'Complete Booking';
            button.disabled = false;
        },

        /**
         * Initialize Stripe checkout
         */
        init: function() {
            // This method can be used for additional initialization if needed
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            window.AIOHMBookingStripeCheckout.init();
        });
    } else {
        window.AIOHMBookingStripeCheckout.init();
    }

})();

/* </fs_premium_only> */