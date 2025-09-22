/**
 * AIOHM Booking Sandwich Navigation
 * 
 * Handles tab-based navigation with smooth animations for the sandwich booking form
 * 
 * Features:
 * - 3-step tab navigation (Selection, Details, Checkout)
 * - Smooth sandwich closing/opening animations
 * - Form validation before step transitions
 * - Responsive touch/click handling
 * - State management and persistence
 * 
 * @package AIOHM_Booking_PRO
 * @since 1.2.7
 */

class AIOHMBookingSandwichNavigation {
	
	constructor() {
		this.container = null;
		this.currentStep = 1;
		this.maxSteps = 3;
		this.minStep = 1;
		this.animationDuration = 600; // Match CSS animation duration
		this.isAnimating = false;
		this.stepValidators = {};
		
		this.init();
	}
	
	/**
	 * Initialize the navigation system
	 */
	init() {
		this.container = document.querySelector('.aiohm-booking-sandwich-container');
		if (!this.container) {
			return;
		}
		
		// Detect the actual starting step from the sandwich body
		const sandwichBody = this.container.querySelector('.aiohm-booking-sandwich-body');
		if (sandwichBody) {
			this.currentStep = parseInt(sandwichBody.dataset.currentStep) || 1;
		}
		
		// Detect available steps by looking at existing step elements
		const stepElements = this.container.querySelectorAll('.aiohm-booking-step-content');
		this.minStep = Math.min(...Array.from(stepElements).map(el => parseInt(el.dataset.step)));
		this.maxSteps = Math.max(...Array.from(stepElements).map(el => parseInt(el.dataset.step)));
		
		this.setupEventListeners();
		this.updateNavigationButtons();
		this.updateTabStates();
	}
	
	/**
	 * Set up all event listeners
	 */
	setupEventListeners() {
		// Navigation buttons
		const nextBtn = this.container.querySelector('.aiohm-booking-btn-next');
		const prevBtn = this.container.querySelector('.aiohm-booking-btn-prev');
		
		if (nextBtn) {
			nextBtn.addEventListener('click', (e) => {
				this.handleNextStep(e);
			});
		}
		
		if (prevBtn) {
			prevBtn.addEventListener('click', (e) => {
				this.handlePrevStep(e);
			});
		}
		
		// Tab clicks (allow direct navigation to completed steps)
		const tabs = this.container.querySelectorAll('.aiohm-booking-tab-item');
		tabs.forEach(tab => {
			tab.addEventListener('click', (e) => this.handleTabClick(e));
		});
		
		// Form validation events
		this.setupFormValidation();
	}
	
	/**
	 * Handle next step button click
	 */
	async handleNextStep(event) {
		event.preventDefault();
		
		if (this.isAnimating) return;
		
		// Validate current step
		if (!await this.validateCurrentStep()) {
			return;
		}
		
		if (this.currentStep < this.maxSteps) {
			await this.animateToStep(this.currentStep + 1);
		}
	}
	
	/**
	 * Handle previous step button click
	 */
	async handlePrevStep(event) {
		event.preventDefault();
		
		if (this.isAnimating) return;
		
		if (this.currentStep > this.minStep) {
			await this.animateToStep(this.currentStep - 1);
		}
	}
	
	/**
	 * Handle tab click for direct navigation
	 */
	async handleTabClick(event) {
		const tab = event.currentTarget;
		const targetStep = parseInt(tab.dataset.step);
		
		if (this.isAnimating || targetStep === this.currentStep) return;
		
		// Only allow navigation to completed steps or the next step
		if (targetStep > this.currentStep + 1) {
			this.showMessage('Please complete the current step first', 'warning');
			return;
		}
		
		// Validate current step if moving forward
		if (targetStep > this.currentStep) {
			if (!await this.validateCurrentStep()) {
				return;
			}
		}
		
		await this.animateToStep(targetStep);
	}
	
	/**
	 * Animate to a specific step with sandwich effect
	 */
	async animateToStep(targetStep) {
		if (this.isAnimating || targetStep === this.currentStep) return;
		
		this.isAnimating = true;
		const sandwichBody = this.container.querySelector('.aiohm-booking-sandwich-body');
		const tabNavigation = this.container.querySelector('.aiohm-booking-tab-navigation');
		const navigationFooter = this.container.querySelector('.aiohm-booking-navigation-footer');
		const currentStepElement = this.container.querySelector(`.aiohm-booking-step-content[data-step="${this.currentStep}"]`);
		const targetStepElement = this.container.querySelector(`.aiohm-booking-step-content[data-step="${targetStep}"]`);
		
		try {
			// Phase 1: Start closing animation - hide current content
			sandwichBody.classList.add('aiohm-booking-sandwich-closing');
			currentStepElement.classList.remove('aiohm-booking-step-active');
			currentStepElement.classList.add('aiohm-booking-step-hidden');
			
			// Wait for content to fade out
			await this.wait(this.animationDuration / 4);
			
			// Phase 2: Show loading widget and close sandwich
			this.showLoadingWidget();
			
			// Close header and footer with sandwich
			tabNavigation.classList.add('aiohm-booking-sandwich-closing');
			navigationFooter.classList.add('aiohm-booking-sandwich-closing');
			
			// Wait for sandwich to close completely
			await this.wait(this.animationDuration / 2);
			
			// Phase 3: Switch content (invisible during transition)
			this.currentStep = targetStep;
			sandwichBody.dataset.currentStep = targetStep;
			
			// Phase 4: Open sandwich and hide loading widget
			sandwichBody.classList.remove('aiohm-booking-sandwich-closing');
			sandwichBody.classList.add('aiohm-booking-sandwich-opening');
			
			tabNavigation.classList.remove('aiohm-booking-sandwich-closing');
			navigationFooter.classList.remove('aiohm-booking-sandwich-closing');
			
			tabNavigation.classList.add('aiohm-booking-sandwich-opening');
			navigationFooter.classList.add('aiohm-booking-sandwich-opening');
			
			// Wait for sandwich to open
			await this.wait(this.animationDuration / 4);
			
			// Hide loading widget
			this.hideLoadingWidget();
			
			// Phase 5: Show new content
			if (targetStepElement) {
				targetStepElement.classList.remove('aiohm-booking-step-hidden');
				targetStepElement.classList.add('aiohm-booking-step-active');
			} else {
				console.error('AIOHM: Target step element not found!');
			}
			
			// Wait for content to fade in
			await this.wait(this.animationDuration / 4);
			
			// Clean up animation classes
			sandwichBody.classList.remove('aiohm-booking-sandwich-opening');
			tabNavigation.classList.remove('aiohm-booking-sandwich-opening');
			navigationFooter.classList.remove('aiohm-booking-sandwich-opening');
			
			// Update UI states
			this.updateTabStates();
			this.updateNavigationButtons();
			this.scrollToTop();
			
			// Trigger step change event
			this.triggerStepChangeEvent(targetStep);
			
			// Create pending order and send notification when moving to checkout (step 3)
			if (targetStep === 3) {
				await this.createPendingOrderAndNotify();
			}
			
		} catch (error) {
			console.error('AIOHM Booking: Animation error:', error);
			this.hideLoadingWidget();
		} finally {
			this.isAnimating = false;
		}
	}
	
	/**
	 * Validate the current step before proceeding
	 */
	async validateCurrentStep() {
		const validator = this.stepValidators[this.currentStep];
		
		if (validator && typeof validator === 'function') {
			try {
				const isValid = await validator();
				if (!isValid) {
					this.showMessage('Please complete all required fields', 'error');
					return false;
				}
			} catch (error) {
				console.error('AIOHM Booking: Validation error:', error);
				this.showMessage('Validation failed. Please try again.', 'error');
				return false;
			}
		}
		
		// Default validation based on step
		switch (this.currentStep) {
			case 1:
				// Only validate selection if step 1 exists (events/accommodations enabled)
				const step1Element = this.container.querySelector('.aiohm-booking-step-content[data-step="1"]');
				return step1Element ? this.validateSelectionStep() : true;
			case 2:
				return this.validateContactStep();
			case 3:
				return true; // Checkout step doesn't need validation to proceed
			default:
				return true;
		}
	}
	
	/**
	 * Validate selection step (events/accommodations)
	 */
	validateSelectionStep() {
		// Check if at least one event or accommodation is selected
		const eventRadios = this.container.querySelectorAll('.aiohm-booking-event-radio:checked, .aiohm-booking-event-checkbox:checked');
		const accommodationCheckboxes = this.container.querySelectorAll('.accommodation-checkbox:checked');
		const calendarSelections = this.container.querySelectorAll('.calendar-cell.selected');
		
		if (eventRadios.length === 0 && accommodationCheckboxes.length === 0 && calendarSelections.length === 0) {
			this.showMessage('Please select at least one event or accommodation', 'warning');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Validate contact step
	 */
	validateContactStep() {
		const requiredFields = this.container.querySelectorAll('.aiohm-booking-contact-section input[required], .aiohm-booking-contact-section select[required], .aiohm-booking-contact-section textarea[required]');
		let isValid = true;
		
		requiredFields.forEach(field => {
			if (!field.value.trim()) {
				field.classList.add('error');
				isValid = false;
			} else {
				field.classList.remove('error');
			}
		});
		
		if (!isValid) {
			this.showMessage('Please fill in all required contact information', 'error');
		}
		
		return isValid;
	}
	
	/**
	 * Update tab visual states
	 */
	updateTabStates() {
		const tabs = this.container.querySelectorAll('.aiohm-booking-tab-item');
		
		tabs.forEach((tab) => {
			const step = parseInt(tab.dataset.step);
			tab.classList.remove('aiohm-booking-tab-active', 'aiohm-booking-tab-completed');
			
			if (step === this.currentStep) {
				tab.classList.add('aiohm-booking-tab-active');
			} else if (step < this.currentStep) {
				tab.classList.add('aiohm-booking-tab-completed');
			}
		});
	}
	
	/**
	 * Update navigation button states
	 */
	updateNavigationButtons() {
		const nextBtn = this.container.querySelector('.aiohm-booking-btn-next');
		const prevBtn = this.container.querySelector('.aiohm-booking-btn-prev');
		
		// Previous button
		if (prevBtn) {
			if (this.currentStep === this.minStep) {
				prevBtn.classList.add('aiohm-booking-btn-disabled');
				prevBtn.disabled = true;
				prevBtn.style.display = 'none';
			} else {
				prevBtn.classList.remove('aiohm-booking-btn-disabled');
				prevBtn.disabled = false;
				prevBtn.style.display = '';
			}
		}
		
		// Next button
		if (nextBtn) {
			const nextText = nextBtn.querySelector('.aiohm-booking-btn-text');
			if (this.currentStep === this.maxSteps) {
				// Hide the next button on final step since Stripe handles payment flow
				nextBtn.style.display = 'none';
			} else {
				if (nextText) nextText.textContent = 'Continue';
				nextBtn.classList.remove('aiohm-booking-btn-final');
				nextBtn.style.display = '';
			}
		}
	}
	
	/**
	 * Set up form validation
	 */
	setupFormValidation() {
		// Add real-time validation for contact fields
		const contactInputs = this.container.querySelectorAll('.aiohm-booking-contact-section input, .aiohm-booking-contact-section select, .aiohm-booking-contact-section textarea');
		
		contactInputs.forEach(input => {
			input.addEventListener('blur', () => {
				if (input.required && !input.value.trim()) {
					input.classList.add('error');
				} else {
					input.classList.remove('error');
				}
			});
			
			input.addEventListener('input', () => {
				if (input.classList.contains('error') && input.value.trim()) {
					input.classList.remove('error');
				}
			});
		});
	}
	
	/**
	 * Register a custom step validator
	 */
	registerStepValidator(step, validator) {
		this.stepValidators[step] = validator;
	}
	
	/**
	 * Show message to user
	 */
	showMessage(message, type = 'info') {
		// Create or update message element
		let messageEl = this.container.querySelector('.aiohm-booking-message');
		
		if (!messageEl) {
			messageEl = document.createElement('div');
			messageEl.className = 'aiohm-booking-message';
			this.container.querySelector('.aiohm-booking-sandwich-body').appendChild(messageEl);
		}
		
		messageEl.className = `aiohm-booking-message aiohm-booking-message-${type}`;
		messageEl.textContent = message;
		messageEl.style.display = 'block';
		
		// Auto-hide after 5 seconds
		setTimeout(() => {
			if (messageEl) {
				messageEl.style.display = 'none';
			}
		}, 5000);
	}
	
	/**
	 * Scroll to top of form
	 */
	scrollToTop() {
		this.container.scrollIntoView({ 
			behavior: 'smooth', 
			block: 'start' 
		});
	}
	
	/**
	 * Trigger step change event for other components
	 */
	triggerStepChangeEvent(step) {
		const event = new CustomEvent('aiohm-booking-step-change', {
			detail: { 
				step: step,
				previousStep: this.currentStep 
			},
			bubbles: true
		});
		
		this.container.dispatchEvent(event);
	}
	
	/**
	 * Show loading widget during transition
	 */
	showLoadingWidget() {
		let loadingWidget = this.container.querySelector('.aiohm-booking-loading-widget');
		
		if (!loadingWidget) {
			loadingWidget = document.createElement('div');
			loadingWidget.className = 'aiohm-booking-loading-widget';
			loadingWidget.innerHTML = `
				<div class="aiohm-booking-loading-content">
					<div class="aiohm-booking-loading-spinner"></div>
					<div class="aiohm-booking-loading-text">Loading...</div>
				</div>
			`;
			this.container.appendChild(loadingWidget);
		}
		
		loadingWidget.style.display = 'flex';
	}
	
	/**
	 * Hide loading widget after transition
	 */
	hideLoadingWidget() {
		const loadingWidget = this.container.querySelector('.aiohm-booking-loading-widget');
		if (loadingWidget) {
			loadingWidget.style.display = 'none';
		}
	}
	
	/**
	 * Get current step
	 */
	getCurrentStep() {
		return this.currentStep;
	}
	
	/**
	 * Go to specific step programmatically
	 */
	async goToStep(step) {
		if (step >= 1 && step <= this.maxSteps) {
			await this.animateToStep(step);
		}
	}
	
	/**
	 * Create a pending order and trigger notification when moving to checkout
	 */
	async createPendingOrderAndNotify() {
		try {
			// Get the form data
			const form = this.container.querySelector('#aiohm-booking-sandwich-form');
			if (!form) {
				console.error('AIOHM Booking: Form not found for order creation');
				return;
			}
			
			// Serialize form data as expected by the PHP handler
			const formDataObj = new FormData(form);
			const formDataString = new URLSearchParams(formDataObj).toString();
			
			// Create AJAX data
			const ajaxData = new FormData();
			ajaxData.append('action', 'aiohm_booking_create_pending_order');
			ajaxData.append('nonce', window.aiohm_booking?.nonce || '');
			ajaxData.append('form_data', formDataString);
			
			// Get pricing data from the pricing summary
			let pricingData = {};
			if (window.AIOHM_Booking_Pricing_Summary && window.AIOHM_Booking_Pricing_Summary.instance) {
				pricingData = window.AIOHM_Booking_Pricing_Summary.instance.getPricingData();
			}
			
			// Add pricing data to form data
			const updatedFormDataString = formDataString + '&pricing_data=' + encodeURIComponent(JSON.stringify({
				total: pricingData.totals?.total || 0,
				deposit: (pricingData.totals?.total || 0) * (pricingData.depositPercent || 50) / 100,
				currency: pricingData.currency || 'RON'
			}));
			
			ajaxData.set('form_data', updatedFormDataString);
			
			// Submit the booking to create a pending order
			const response = await fetch(window.aiohm_booking?.ajax_url || ajaxurl, {
				method: 'POST',
				body: ajaxData
			});
			
			const result = await response.json();
			
			if (result.success) {
				console.log('AIOHM Booking: Pending order created and notification sent', result.data);
				
				// Store the booking ID for later use
				if (result.data.booking_id) {
					this.container.dataset.bookingId = result.data.booking_id;
				}
			} else {
				console.error('AIOHM Booking: Failed to create pending order', result.data);
			}
			
		} catch (error) {
			console.error('AIOHM Booking: Error creating pending order', error);
		}
	}
	
	/**
	 * Utility function to wait for specified time
	 */
	wait(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
	// Only initialize if sandwich container exists
	if (document.querySelector('.aiohm-booking-sandwich-container')) {
		window.AIOHMBookingSandwich = new AIOHMBookingSandwichNavigation();
	}
});

// Export for use by other modules
if (typeof module !== 'undefined' && module.exports) {
	module.exports = AIOHMBookingSandwichNavigation;
}