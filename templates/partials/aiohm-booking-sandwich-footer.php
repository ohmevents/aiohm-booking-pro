<?php
/**
 * Sandwich Footer Component
 *
 * Displays the footer section of the unified booking form with:
 * - Brand color background
 * - Checkout button that shows embedded checkout form
 * - Dynamic styling that matches the header
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load shared form settings based on context (same logic as header).
if ( isset( $is_events_context ) && $is_events_context ) {
	$form_settings = get_option( 'aiohm_booking_tickets_form_settings', array() );
} else {
	$form_settings = get_option( 'aiohm_booking_form_settings', array() );
}

// Get unified brand color from main settings (shared across all contexts)
$main_settings       = get_option( 'aiohm_booking_settings', array() );
$unified_brand_color = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? null;

// Extract footer data with fallbacks - use unified color if available
$brand_color = $unified_brand_color ?? $form_settings['form_primary_color'] ?? '#457d59';
$text_color  = $form_settings['form_text_color'] ?? '#ffffff';

// Enqueue shortcodes CSS if not already loaded
if ( ! wp_style_is( 'aiohm-booking-shortcodes', 'enqueued' ) ) {
	wp_enqueue_style(
		'aiohm-booking-shortcodes',
		AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-shortcodes.css',
		array(),
		AIOHM_BOOKING_VERSION
	);
}

// Add inline CSS for dynamic colors and step transitions
$dynamic_css = "
:root {
	--aiohm-brand-color: {$brand_color};
	--aiohm-text-color: {$text_color};
}

/* Step transition animations */
.aiohm-booking-sandwich-body {
	transition: opacity 0.3s ease-in-out;
}

.aiohm-booking-sandwich-body.transitioning-to-checkout {
	opacity: 0.5;
}

.aiohm-booking-sandwich-body.checkout-step {
	background: #f8f9fa;
	border-radius: 8px;
	padding: 20px;
	margin-top: 20px;
}

	/* Hide old pricing elements during checkout step */
	.aiohm-booking-sandwich-body.checkout-step .aiohm-pricing-summary-card,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-selected-accommodations,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-selected-events,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-pricing-breakdown,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-accommodation-selection,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-event-summary-item,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-booking-dates-display,
	.aiohm-booking-sandwich-body.checkout-step .aiohm-booking-layer-pricing {
		display: none !important;
	}

/* Hide old accommodation selection UI during checkout */
.aiohm-booking-sandwich-body.checkout-step .aiohm-booking-dates-display,
.aiohm-booking-sandwich-body.checkout-step .aiohm-accommodations-list {
	display: none !important;
}

/* Spinner for loading states */
.aiohm-spinner {
	display: inline-block;
	width: 16px;
	height: 16px;
	border: 2px solid #f3f3f3;
	border-top: 2px solid var(--aiohm-brand-color);
	border-radius: 50%;
	animation: aiohm-spin 1s linear infinite;
}

@keyframes aiohm-spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

/* Success message styling */
.aiohm-booking-success {
	text-align: center;
	padding: 40px 20px;
}

.aiohm-success-icon {
	font-size: 48px;
	color: #28a745;
	margin-bottom: 20px;
}

.aiohm-booking-success h3 {
	color: #28a745;
	margin-bottom: 10px;
}

/* Developer mode indicator */
.aiohm-developer-mode-badge {
	position: absolute;
	top: 10px;
	right: 10px;
	background: #ff6b35;
	color: white;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: bold;
}
";
wp_add_inline_style( 'aiohm-booking-shortcodes', $dynamic_css );

// Determine button text based on context.
$button_text = __( 'Continue to Checkout', 'aiohm-booking-pro' );

// Check if we're in preview mode.
$is_preview = isset( $GLOBALS['aiohm_booking_preview_mode'] ) && $GLOBALS['aiohm_booking_preview_mode'];
?>

<div class="aiohm-booking-sandwich-footer" style="--aiohm-brand-color: <?php echo esc_attr( $brand_color ); ?>; --aiohm-text-color: <?php echo esc_attr( $text_color ); ?>;">
	<div class="aiohm-booking-footer-content">
		<?php if ( $is_preview ) : ?>
			<button type="button" class="aiohm-booking-checkout-btn" disabled>
				<?php echo esc_html( $button_text ); ?>
			</button>
		<?php else : ?>
			<button type="button" class="aiohm-booking-checkout-btn" id="aiohm-booking-submit">
				<?php echo esc_html( $button_text ); ?>
				<span class="aiohm-checkout-arrow">→</span>
			</button>
		<?php endif; ?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const checkoutBtn = document.getElementById('aiohm-booking-submit');
	const sandwichForm = document.getElementById('aiohm-booking-sandwich-form');
	const sandwichBody = document.querySelector('.aiohm-booking-sandwich-body');
	
	if (checkoutBtn && sandwichForm && sandwichBody) {
		checkoutBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			// Store current booking form data
			const formData = new FormData(sandwichForm);
			
			// Store in sessionStorage for persistence
			const formDataObj = {};
			for (let [key, value] of formData.entries()) {
				formDataObj[key] = value;
			}
			sessionStorage.setItem('aiohm_booking_form_data', JSON.stringify(formDataObj));
			
			// Disable button and show loading
			checkoutBtn.disabled = true;
			checkoutBtn.innerHTML = '<span class="aiohm-spinner"></span> Processing...';
			
			// Determine the correct AJAX action based on context
			const isEventsContext = <?php echo $is_events_context ? 'true' : 'false'; ?>;
			const ajaxAction = isEventsContext ? 'aiohm_booking_submit_event' : 'aiohm_booking_submit';
			
			// Submit booking to create booking ID
			fetch(window.aiohm_booking?.ajax_url || ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: ajaxAction,
					nonce: window.aiohm_booking?.nonce || '',
					form_data: new URLSearchParams(formData).toString()
				})
			})
			.then(response => response.json())
			.then(data => {
				if (data.success && data.data.booking_id) {
					// Transition to checkout step
					transitionToCheckout(data.data.booking_id);
				} else {
					showError('Error creating booking: ' + (data.data?.message || 'Unknown error'));
					resetButton();
				}
			})
			.catch(error => {
				console.error('Error submitting booking:', error);
				showError('Error submitting booking. Please try again.');
				resetButton();
			});
		});
	}
	
	function transitionToCheckout(bookingId) {
		// Add step transition class
		sandwichBody.classList.add('transitioning-to-checkout');
		
		// Hide pricing layer completely during checkout
		const pricingLayer = document.querySelector('.aiohm-booking-layer-pricing');
		if (pricingLayer) {
			pricingLayer.style.display = 'none';
		}
		
		// Hide any old pricing summary content immediately
		const pricingSummaries = document.querySelectorAll('.aiohm-pricing-summary-card, .aiohm-selected-accommodations, .aiohm-selected-events, .aiohm-pricing-breakdown, .aiohm-event-summary-item, .aiohm-booking-dates-display');
		pricingSummaries.forEach(el => {
			el.style.display = 'none';
		});
		
		// Fetch checkout form HTML
		fetch(window.aiohm_booking_frontend?.ajax_url || ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'aiohm_booking_get_checkout_html',
				booking_id: bookingId,
				nonce: window.aiohm_booking_frontend?.nonce || '',
				developer_mode: true // Show only payments in developer mode
			})
		})
		.then(response => response.json())
		.then(result => {
			if (result.success && result.data.html) {
				// Replace form content with checkout
				setTimeout(() => {
					// Clear all content first
					sandwichBody.innerHTML = '';
					
					// Add checkout content
					sandwichBody.innerHTML = result.data.html;
					sandwichBody.classList.remove('transitioning-to-checkout');
					sandwichBody.classList.add('checkout-step');
					
					// Update button to "Back to Booking Form"
					checkoutBtn.innerHTML = '← Back to Booking Form';
					checkoutBtn.disabled = false;
					checkoutBtn.onclick = function() { returnToBookingForm(); };
					
					// Scroll to top of form
					sandwichForm.scrollIntoView({ behavior: 'smooth' });
					
					// Initialize checkout functionality
					initializeCheckoutStep(bookingId);
				}, 300);
			} else {
				showError('Error loading checkout. Please refresh the page.');
				resetButton();
			}
		})
		.catch(error => {
			console.error('Error loading checkout:', error);
			showError('Error loading checkout. Please refresh the page.');
			resetButton();
		});
	}
	
	function returnToBookingForm() {
		// Remove checkout classes
		sandwichBody.classList.remove('checkout-step');
		sandwichBody.classList.add('transitioning-to-form');
		
		// Show pricing layer again
		const pricingLayer = document.querySelector('.aiohm-booking-layer-pricing');
		if (pricingLayer) {
			pricingLayer.style.display = '';
		}
		
		// Restore original form content
		setTimeout(() => {
			// This would restore original form - for now just reload page
			window.location.reload();
		}, 300);
	}	function initializeCheckoutStep(bookingId) {
		// Find and initialize payment method selection
		const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
		const completeBookingBtn = document.getElementById('aiohm-complete-booking');
		
		// Handle payment method changes
		paymentRadios.forEach(radio => {
			radio.addEventListener('change', function() {
				handlePaymentMethodChange(this.value, bookingId);
			});
		});
		
		// Handle final booking completion
		if (completeBookingBtn) {
			completeBookingBtn.addEventListener('click', function() {
				handleBookingCompletion(bookingId);
			});
		}
		
		// Initialize default payment method
		const defaultPayment = document.querySelector('input[name="payment_method"]:checked');
		if (defaultPayment) {
			handlePaymentMethodChange(defaultPayment.value, bookingId);
		}
	}
	
	function handlePaymentMethodChange(method, bookingId) {
		const paymentDetails = document.getElementById('aiohm-payment-details');
		if (!paymentDetails) return;
		
		// Show loading in payment details
		paymentDetails.innerHTML = '<p>Loading payment options...</p>';
		
		// Load payment method specific content
		fetch(window.aiohm_booking_frontend?.ajax_url || ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'aiohm_booking_load_payment_method',
				payment_method: method,
				booking_id: bookingId,
				nonce: window.aiohm_booking_frontend?.nonce || ''
			})
		})
		.then(response => response.json())
		.then(result => {
			if (result.success) {
				paymentDetails.innerHTML = result.data.html || '';
			} else {
				paymentDetails.innerHTML = '<p>Error loading payment method.</p>';
			}
		})
		.catch(error => {
			console.error('Error loading payment method:', error);
			paymentDetails.innerHTML = '<p>Error loading payment method.</p>';
		});
	}
	
	function handleBookingCompletion(bookingId) {
		const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
		if (!selectedPayment) {
			showError('Please select a payment method.');
			return;
		}
		
		const completeBtn = document.getElementById('aiohm-complete-booking');
		if (completeBtn) {
			completeBtn.disabled = true;
			completeBtn.innerHTML = '<span class="aiohm-spinner"></span> Processing Payment...';
		}
		
		// Process payment based on selected method
		if (selectedPayment.value === 'manual') {
			// Handle free invoice
			handleManualPayment(bookingId);
		} else {
			// Handle pro payment methods (Stripe, PayPal)
			handleProPayment(selectedPayment.value, bookingId);
		}
	}
	
	function handleManualPayment(bookingId) {
		// Complete booking with manual payment (free invoice)
		fetch(window.aiohm_booking_frontend?.ajax_url || ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'aiohm_booking_complete_manual_payment',
				booking_id: bookingId,
				nonce: window.aiohm_booking_frontend?.nonce || ''
			})
		})
		.then(response => response.json())
		.then(result => {
			if (result.success) {
				// Show success message and invoice details
				showSuccessMessage('Booking confirmed! Payment instructions sent to your email.');
			} else {
				showError('Error completing booking: ' + (result.data?.message || 'Unknown error'));
			}
		})
		.catch(error => {
			console.error('Error completing manual payment:', error);
			showError('Error completing booking. Please try again.');
		});
	}
	
	function handleProPayment(method, bookingId) {
		// Trigger the appropriate payment module
		const event = new CustomEvent('aiohm_booking_process_payment', {
			detail: {
				method: method,
				booking_id: bookingId
			}
		});
		document.dispatchEvent(event);
	}
	
	function showError(message) {
		alert(message); // Simple for now, can be enhanced with better UI
	}
	
	function showSuccessMessage(message) {
		// Replace form with success message
		sandwichBody.innerHTML = `
			<div class="aiohm-booking-success">
				<div class="aiohm-success-icon">✓</div>
				<h3>Booking Confirmed!</h3>
				<p>${message}</p>
				<button type="button" onclick="location.reload()" class="aiohm-btn aiohm-btn-primary">Start New Booking</button>
			</div>
		`;
	}
	
	function resetButton() {
		if (checkoutBtn) {
			checkoutBtn.disabled = false;
			checkoutBtn.innerHTML = '<?php echo esc_html( $button_text ); ?> <span class="aiohm-checkout-arrow">→</span>';
		}
	}
});
</script>
