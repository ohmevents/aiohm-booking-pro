/* Shortcode Generator Functionality */
document.addEventListener('DOMContentLoaded', function() {
	// Tab switching.
	document.querySelectorAll('.generator-tab').forEach(tab => {
		tab.addEventListener('click', function() {
			const targetTab = this.dataset.tab;
			
			// Update tab buttons.
			document.querySelectorAll('.generator-tab').forEach(t => t.classList.remove('active'));
			this.classList.add('active');
			
			// Update tab content.
			document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
			document.getElementById(targetTab + '-tab').classList.add('active');
		});
	});
	
	// Shortcode generation.
	function updateShortcode() {
		const activeTab = document.querySelector('.generator-tab.active').dataset.tab;
		let shortcode = '';
		
		switch (activeTab) {
			case 'booking-form':
				shortcode = generateBookingShortcode();
				break;
			case 'accommodations':
				shortcode = generateAccommodationsShortcode();
				break;
			case 'checkout':
				shortcode = generateCheckoutShortcode();
				break;
			case 'events':
				shortcode = generateEventsShortcode();
				break;
		}
		
		document.getElementById(activeTab + '-shortcode').textContent = shortcode;
	}
	
	function generateBookingShortcode() {
		const mode = document.getElementById('booking-mode').value;
		const theme = document.getElementById('booking-theme').value;
		const showTitle = document.getElementById('booking-show-title').checked;
		
		let attrs = [];
		if (mode !== 'auto') attrs.push(`mode="${mode}"`);
		if (theme !== 'default') attrs.push(`theme="${theme}"`);
		if (!showTitle) attrs.push('show_title="false"');
		
		return `[aiohm_booking${attrs.length ? ' ' + attrs.join(' ') : ''}]`;
	}
	
	function generateAccommodationsShortcode() {
		const style = document.getElementById('accommodations-style').value;
		const buttonText = document.getElementById('accommodations-button-text').value;
		const showPrices = document.getElementById('accommodations-show-prices').checked;
		
		let attrs = [];
		if (style !== 'compact') attrs.push(`style="${style}"`);
		if (buttonText && buttonText !== 'Book Now') attrs.push(`button_text="${buttonText}"`);
		if (!showPrices) attrs.push('show_prices="false"');
		
		return `[aiohm_booking_accommodations${attrs.length ? ' ' + attrs.join(' ') : ''}]`;
	}
	
	function generateCheckoutShortcode() {
		const summary = document.getElementById('checkout-summary').checked;
		const methods = document.getElementById('checkout-methods').value;
		
		let attrs = [];
		if (!summary) attrs.push('show_summary="false"');
		if (methods !== 'all') attrs.push(`payment_methods="${methods}"`);
		
		return `[aiohm_booking_checkout${attrs.length ? ' ' + attrs.join(' ') : ''}]`;
	}
	
	function generateEventsShortcode() {
		const count = document.getElementById('events-count').value;
		const layout = document.getElementById('events-layout').value;
		const dates = document.getElementById('events-dates').checked;
		
		let attrs = [];
		if (count !== '10') attrs.push(`count="${count}"`);
		if (layout !== 'list') attrs.push(`layout="${layout}"`);
		if (!dates) attrs.push('show_dates="false"');
		
		return `[aiohm_booking_events${attrs.length ? ' ' + attrs.join(' ') : ''}]`;
	}
	
	// Listen for option changes.
	document.querySelectorAll('.shortcode-options input, .shortcode-options select').forEach(element => {
		element.addEventListener('change', updateShortcode);
	});
	
	// Initial shortcode generation.
	updateShortcode();
});