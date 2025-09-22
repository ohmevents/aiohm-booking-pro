/**
 * AIOHM Booking Stripe Frontend JavaScript
 * Handles Stripe payment processing on the frontend
 *
 * @package AIOHM_Booking_PRO
 * @since   2.0.0
 */

/* <fs_premium_only> */

(function($) {
    'use strict';

    // Initialize Stripe
    let stripe = null;
    let elements = null;
    let cardElement = null;

    // Initialize when document is ready
    $(document).ready(function() {
        initializeStripe();
        bindEvents();
    });

    /**
     * Initialize Stripe Elements
     */
    function initializeStripe() {
        if (typeof aiohm_booking_stripe === 'undefined') {
            return;
        }

        // Initialize Stripe with publishable key
        stripe = Stripe(aiohm_booking_stripe.publishable_key);

        // Create elements instance
        elements = stripe.elements();

        // Create card element
        const style = {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a',
            },
        };

        cardElement = elements.create('card', {
            style: style,
            hidePostalCode: true,
        });

        // Mount card element if container exists
        const cardContainer = $('#aiohm-booking-stripe-card-element');
        if (cardContainer.length) {
            cardElement.mount('#aiohm-booking-stripe-card-element');

            // Handle card element events
            cardElement.on('change', function(event) {
                const displayError = $('#aiohm-booking-stripe-card-errors');
                if (event.error) {
                    displayError.text(event.error.message);
                } else {
                    displayError.text('');
                }
            });
        }
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Handle checkout session creation
        $(document).on('click', '.aiohm-booking-stripe-checkout-btn', function(e) {
            e.preventDefault();
            createCheckoutSession($(this));
        });

        // Handle payment intent processing
        $(document).on('click', '.aiohm-booking-stripe-pay-btn', function(e) {
            e.preventDefault();
            processPayment($(this));
        });

        // Handle form submission
        $(document).on('submit', '.aiohm-booking-payment-form', function(e) {
            const paymentMethod = $(this).find('input[name="payment_method"]:checked').val();
            if (paymentMethod === 'stripe') {
                e.preventDefault();
                processPaymentForm($(this));
            }
        });

        // Handle integrated checkout payment processing
        document.addEventListener('aiohm_booking_process_payment', function(e) {
            if (e.detail.method === 'stripe') {
                processIntegratedPayment(e.detail.booking_id);
            }
        });
    }

    /**
     * Create Stripe Checkout Session
     */
    function createCheckoutSession($button) {
        const bookingId = $button.data('booking-id');
        const $form = $button.closest('form');

        if (!bookingId) {
            showError(aiohm_booking_stripe.messages.error);
            return;
        }

        // Disable button and show loading
        $button.prop('disabled', true).text(aiohm_booking_stripe.messages.processing);

        // Prepare data
        const data = {
            action: 'aiohm_booking_stripe_create_session',
            booking_id: bookingId,
            nonce: aiohm_booking_stripe.nonce
        };

        // Add form data if available
        if ($form.length) {
            const formData = new FormData($form[0]);
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
        }

        // Make AJAX request
        $.ajax({
            url: aiohm_booking_stripe.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Redirect to Stripe Checkout
                    window.location.href = response.data.url;
                } else {
                    showError(response.data.message || aiohm_booking_stripe.messages.error);
                    $button.prop('disabled', false).text($button.data('original-text') || 'Pay Now');
                }
            },
            error: function() {
                showError(aiohm_booking_stripe.messages.error);
                $button.prop('disabled', false).text($button.data('original-text') || 'Pay Now');
            }
        });
    }

    /**
     * Process payment with card element
     */
    function processPayment($button) {
        const bookingId = $button.data('booking-id');
        const amount = $button.data('amount');
        const currency = $button.data('currency') || 'usd';

        if (!bookingId || !cardElement) {
            showError(aiohm_booking_stripe.messages.error);
            return;
        }

        // Disable button and show loading
        $button.prop('disabled', true).text(aiohm_booking_stripe.messages.processing);

        // Create payment method
        stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
        }).then(function(result) {
            if (result.error) {
                showError(result.error.message);
                $button.prop('disabled', false).text($button.data('original-text') || 'Pay Now');
                return;
            }

            // Confirm payment
            const data = {
                action: 'aiohm_booking_stripe_process_payment',
                booking_id: bookingId,
                payment_method_id: result.paymentMethod.id,
                amount: amount,
                currency: currency,
                nonce: aiohm_booking_stripe.nonce
            };

            $.ajax({
                url: aiohm_booking_stripe.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        showSuccess(aiohm_booking_stripe.messages.success);
                        // Redirect to success page
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || '/booking-success';
                        }, 2000);
                    } else {
                        showError(response.data.message || aiohm_booking_stripe.messages.error);
                        $button.prop('disabled', false).text($button.data('original-text') || 'Pay Now');
                    }
                },
                error: function() {
                    showError(aiohm_booking_stripe.messages.error);
                    $button.prop('disabled', false).text($button.data('original-text') || 'Pay Now');
                }
            });
        });
    }

    /**
     * Process payment form
     */
    function processPaymentForm($form) {
        const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
        const originalText = $submitBtn.text();

        $submitBtn.prop('disabled', true).text(aiohm_booking_stripe.messages.processing);

        // Get form data
        const formData = new FormData($form[0]);
        formData.append('action', 'aiohm_booking_process_payment');
        formData.append('nonce', aiohm_booking_stripe.nonce);

        // Make AJAX request
        $.ajax({
            url: aiohm_booking_stripe.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess(aiohm_booking_stripe.messages.success);
                    // Redirect or reload page
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        window.location.reload();
                    }
                } else {
                    showError(response.data.message || aiohm_booking_stripe.messages.error);
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showError(aiohm_booking_stripe.messages.error);
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Process payment for integrated checkout flow
     */
    function processIntegratedPayment(bookingId) {
        if (!stripe || !bookingId) {
            showError('Payment system not ready or invalid booking ID');
            return;
        }

        // Update the complete booking button
        const completeBtn = document.getElementById('aiohm-complete-booking');
        if (completeBtn) {
            completeBtn.disabled = true;
            completeBtn.innerHTML = '<span class="aiohm-spinner"></span> Processing Payment...';
        }

        // Create Stripe checkout session
        const data = {
            action: 'aiohm_booking_stripe_create_session',
            booking_id: bookingId,
            nonce: aiohm_booking_stripe.nonce,
            integrated_checkout: true
        };

        $.post(aiohm_booking_stripe.ajax_url, data)
            .done(function(response) {
                if (response.success && response.data.session_id) {
                    // Redirect to Stripe Checkout
                    stripe.redirectToCheckout({
                        sessionId: response.data.session_id
                    }).then(function(result) {
                        if (result.error) {
                            showError(result.error.message);
                            resetCompleteButton();
                        }
                    });
                } else {
                    showError(response.data?.message || 'Failed to create payment session');
                    resetCompleteButton();
                }
            })
            .fail(function() {
                showError('Payment processing failed. Please try again.');
                resetCompleteButton();
            });
    }

    /**
     * Reset the complete booking button
     */
    function resetCompleteButton() {
        const completeBtn = document.getElementById('aiohm-complete-booking');
        if (completeBtn) {
            completeBtn.disabled = false;
            completeBtn.innerHTML = 'Complete Booking';
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        // Remove existing messages
        $('.aiohm-booking-message').remove();

        // Create and show error message
        const $error = $('<div class="aiohm-booking-message aiohm-booking-error">' + message + '</div>');
        $('body').prepend($error);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $error.fadeOut(function() {
                $error.remove();
            });
        }, 5000);
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        // Remove existing messages
        $('.aiohm-booking-message').remove();

        // Create and show success message
        const $success = $('<div class="aiohm-booking-message aiohm-booking-success">' + message + '</div>');
        $('body').prepend($success);

        // Auto-hide after 3 seconds
        setTimeout(function() {
            $success.fadeOut(function() {
                $success.remove();
            });
        }, 3000);
    }

    /**
     * Handle 3D Secure authentication
     */
    function handleCardAction(clientSecret) {
        return stripe.handleCardAction(clientSecret);
    }

    /**
     * Confirm card payment
     */
    function confirmCardPayment(clientSecret, data) {
        return stripe.confirmCardPayment(clientSecret, data);
    }

    // Expose functions globally for external use
    window.AIOHMBookingStripe = {
        initializeStripe: initializeStripe,
        createCheckoutSession: createCheckoutSession,
        processPayment: processPayment,
        handleCardAction: handleCardAction,
        confirmCardPayment: confirmCardPayment,
        showError: showError,
        showSuccess: showSuccess
    };

})(jQuery);

/* </fs_premium_only> */
