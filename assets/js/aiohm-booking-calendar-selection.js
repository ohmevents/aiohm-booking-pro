/**
 * Calendar Selection JavaScript
 *
 * @package AIOHM_Booking_PRO
 * @since   1.2.3
 */

document.addEventListener('DOMContentLoaded', function() {
	const checkinInput = document.getElementById('checkin_date');
	const checkoutInput = document.getElementById('checkout_date');
	const nightsDisplay = document.querySelector('.aiohm-nights-display');
	const nightsCount = document.querySelector('.aiohm-nights-count');
	const accommodationCheckboxes = document.querySelectorAll('.accommodation-checkbox');
	const accommodationCards = document.querySelectorAll('.aiohm-booking-accommodation-card');
	const bookingSummary = document.querySelector('.aiohm-booking-summary');
	const adultsInput = document.getElementById('adults_count');
	const childrenInput = document.getElementById('children_count');
	
	const currency = aiohm_calendar_selection.currency || 'RON';
	const pricePerNight = parseFloat(aiohm_calendar_selection.price_per_night) || 0;
	
	// Set minimum date to today.
	const today = new Date().toISOString().split('T')[0];
	checkinInput.setAttribute('min', today);
	checkoutInput.setAttribute('min', today);
	
	// Date change handlers.
	checkinInput.addEventListener('change', function() {
		const checkinDate = new Date(this.value);
		const nextDay = new Date(checkinDate);
		nextDay.setDate(nextDay.getDate() + 1);
		checkoutInput.setAttribute('min', nextDay.toISOString().split('T')[0]);
		
		if (checkoutInput.value && checkoutInput.value <= this.value) {
			checkoutInput.value = nextDay.toISOString().split('T')[0];
		}
		
		updateNights();
		updateSummary();
	});
	
	checkoutInput.addEventListener('change', function() {
		updateNights();
		updateSummary();
	});
	
	// Accommodation selection.
	accommodationCheckboxes.forEach(function(checkbox, index) {
		checkbox.addEventListener('change', function() {
			accommodationCards[index].classList.toggle('aiohm-booking-accommodation-selected', this.checked);
			updateSummary();
		});
	});
	
	// Guest count controls.
	setupQuantityControls('.aiohm-adults-minus', '.aiohm-adults-plus', adultsInput);
	setupQuantityControls('.aiohm-children-minus', '.aiohm-children-plus', childrenInput);
	
	function setupQuantityControls(minusSelector, plusSelector, input) {
		const minusBtn = document.querySelector(minusSelector);
		const plusBtn = document.querySelector(plusSelector);
		
		if (minusBtn) {
			minusBtn.addEventListener('click', function() {
				const currentValue = parseInt(input.value);
				const minValue = parseInt(input.getAttribute('min')) || 0;
				if (currentValue > minValue) {
					input.value = currentValue - 1;
					updateSummary();
				}
			});
		}
		
		if (plusBtn) {
			plusBtn.addEventListener('click', function() {
				const currentValue = parseInt(input.value);
				const maxValue = parseInt(input.getAttribute('max')) || 20;
				if (currentValue < maxValue) {
					input.value = currentValue + 1;
					updateSummary();
				}
			});
		}
		
		input.addEventListener('input', updateSummary);
	}
	
	function updateNights() {
		if (checkinInput.value && checkoutInput.value) {
			const checkin = new Date(checkinInput.value);
			const checkout = new Date(checkoutInput.value);
			const timeDiff = checkout.getTime() - checkin.getTime();
			const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
			
			if (nights > 0) {
				nightsCount.textContent = nights;
				nightsDisplay.style.display = 'block';
			} else {
				nightsDisplay.style.display = 'none';
			}
		} else {
			nightsDisplay.style.display = 'none';
		}
	}
	
	function updateSummary() {
		const hasSelection = checkinInput.value && checkoutInput.value && 
			document.querySelectorAll('.accommodation-checkbox:checked').length > 0;
		
		if (hasSelection) {
			bookingSummary.style.display = 'block';
			
			// Update dates.
			const checkin = new Date(checkinInput.value).toLocaleDateString();
			const checkout = new Date(checkoutInput.value).toLocaleDateString();
			document.querySelector('.aiohm-summary-dates-value').textContent = `${checkin} - ${checkout}`;
			
			// Update accommodations.
			const selectedAccommodations = Array.from(document.querySelectorAll('.accommodation-checkbox:checked'))
				.map(cb => cb.closest('.aiohm-booking-accommodation-card').querySelector('.aiohm-booking-accommodation-name').textContent);
			document.querySelector('.aiohm-summary-accommodations-value').textContent = selectedAccommodations.join(', ');
			
			// Update guests.
			const adults = parseInt(adultsInput.value) || 0;
			const children = parseInt(childrenInput.value) || 0;
			let guestText = adults + ' adult' + (adults !== 1 ? 's' : '');
			if (children > 0) {
				guestText += ', ' + children + ' child' + (children !== 1 ? 'ren' : '');
			}
			document.querySelector('.aiohm-summary-guests-value').textContent = guestText;
			
			// Calculate total (simplified calculation).
			const nights = parseInt(nightsCount.textContent) || 0;
			const accommodationCount = selectedAccommodations.length;
			const total = nights * accommodationCount * pricePerNight;
			document.querySelector('.aiohm-summary-total-value').textContent = currency + ' ' + total.toFixed(2);
			
		} else {
			bookingSummary.style.display = 'none';
		}
	}
});