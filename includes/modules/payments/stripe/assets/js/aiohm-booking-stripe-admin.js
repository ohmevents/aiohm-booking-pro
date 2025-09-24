/**
 * Stripe Payment Module - Admin JavaScript
 *
 * @package AIOHM_Booking_PRO
 * @since 1.2.4
 */

/* <fs_premium_only> */

(function($) {
    'use strict';

    /**
     * Stripe Admin Handler
     */
    window.AIOHMBookingStripeAdmin = {
        
        /**
         * Test Stripe API connection
         * @param {HTMLElement} button - The test button
         */
        testConnection: function(button) {
            const originalText = button.innerHTML;
            const publishableKey = document.getElementById('stripe_publishable_key').value;
            const secretKey = document.getElementById('stripe_secret_key').value;

            if (!publishableKey || !secretKey) {
                alert('Please enter both your Stripe publishable key and secret key first.');
                return;
            }

            // Show loading state
            button.innerHTML = '<span class="dashicons dashicons-update spin"></span> Testing...';
            button.disabled = true;

            // Clear any previous test results
            const existingResult = document.getElementById('stripe-test-result');
            if (existingResult) {
                existingResult.remove();
            }

            // Make actual API call to Stripe (using balance endpoint as a simple test)
            fetch('https://api.stripe.com/v1/balance', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + secretKey,
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error?.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                // Success
                button.innerHTML = '<span class="dashicons dashicons-yes"></span> Connected!';
                button.style.backgroundColor = '#46b450';

                // Show success message
                this.showTestResult('success', 'Stripe API connection successful! Account is active.');

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 3000);
            })
            .catch(error => {
                // Error
                button.innerHTML = '<span class="dashicons dashicons-no"></span> Failed';
                button.style.backgroundColor = '#dc3232';

                // Show error message
                this.showTestResult('error', 'Connection failed: ' + error.message);

                // Reset button after 5 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 5000);
            });
        },

        /**
         * Show test result message
         * @param {string} type - Result type (success or error)
         * @param {string} message - Result message
         */
        showTestResult: function(type, message) {
            const resultHtml = `
                <div id="stripe-test-result" class="notice notice-${type} inline" style="margin: 10px 0;">
                    <p><strong>Stripe Test Result:</strong> ${message}</p>
                </div>
            `;
            
            const container = document.querySelector('#test-stripe-connection').closest('.form-table');
            if (container) {
                container.insertAdjacentHTML('afterend', resultHtml);
            }
        },

        /**
         * Initialize Stripe admin functionality
         */
        init: function() {
            // Only bind test connection if the stripe-settings.js didn't handle it
            // Check if the button exists and doesn't already have handlers
            const testStripeButton = document.getElementById('test-stripe-connection');
            if (testStripeButton && !testStripeButton.hasAttribute('data-stripe-handler-bound')) {
                // Mark as handled to prevent double binding
                testStripeButton.setAttribute('data-stripe-handler-bound', 'true');
                
                testStripeButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.testConnection(testStripeButton);
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        window.AIOHMBookingStripeAdmin.init();
    });

})(jQuery);

/* </fs_premium_only> */