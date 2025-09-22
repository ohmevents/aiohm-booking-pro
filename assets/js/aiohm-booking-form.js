/**
 * Booking Form JavaScript
 *
 * @package AIOHM_Booking_PRO
 * @since   1.2.3
 */

// Initialize form behavior.
document.addEventListener('DOMContentLoaded', function() {
	const form = document.querySelector('.aiohm-booking-form, form[data-aiohm-booking]');
	const privateBookingCheckbox = document.querySelector('.aiohm-private-booking-checkbox');
	
	if (form) {
		// Form submission handling.
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const submitBtn = form.querySelector('.aiohm-submit-btn');
			const btnText = submitBtn.querySelector('.aiohm-btn-text');
			const btnLoading = submitBtn.querySelector('.aiohm-btn-loading');
			
			// Show loading state.
			btnText.style.display = 'none';
			btnLoading.style.display = 'inline';
			submitBtn.disabled = true;
			
			// Here you would normally submit the form via AJAX.
			// For now, we'll simulate the process.
			setTimeout(() => {
				btnText.style.display = 'inline';
				btnLoading.style.display = 'none';
				submitBtn.disabled = false;
			}, 2000);
		});
	}
	
	// Private booking option behavior.
	if (privateBookingCheckbox) {
		privateBookingCheckbox.addEventListener('change', function() {
			// Add any special behavior for private booking selection.
			const isPrivate = this.checked;
			form.classList.toggle('aiohm-private-mode', isPrivate);
		});
	}
});