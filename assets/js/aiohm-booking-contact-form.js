/**
 * AIOHM Booking Contact Form Card JavaScript
 * Handles contact form validation, dynamic fields, and user interactions
 *
 * @package AIOHM_Booking_PRO
 * @version 1.2.3
 */

(function($) {
    'use strict';

    /**
     * Contact Form Card Handler
     */
    class AIOHMContactForm {
        constructor() {
            this.formCard = null;
            this.formFields = [];
            this.validationRules = {};
            this.formData = {};
            
            this.init();
        }

        /**
         * Initialize the contact form functionality
         */
        init() {
            this.bindElements();
            this.setupValidation();
            this.bindEvents();
            this.loadSavedData();
        }

        /**
         * Bind DOM elements
         */
        bindElements() {
            this.formCard = document.querySelector('.aiohm-contact-form-card');
            if (!this.formCard) return;

            this.formFields = this.formCard.querySelectorAll('input, select, textarea');
        }

        /**
         * Setup validation rules for different field types
         */
        setupValidation() {
            this.validationRules = {
                email: {
                    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                    message: 'Please enter a valid email address'
                },
                phone: {
                    pattern: /^[\+]?[0-9\s\-\(\)]{10,}$/,
                    message: 'Please enter a valid phone number'
                },
                name: {
                    minLength: 2,
                    message: 'Name must be at least 2 characters long'
                },
                age: {
                    min: 1,
                    max: 120,
                    message: 'Please enter a valid age between 1 and 120'
                }
            };
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Real-time validation on input
            this.formFields.forEach(field => {
                // Input validation
                field.addEventListener('input', (e) => this.handleFieldInput(e));
                field.addEventListener('blur', (e) => this.handleFieldBlur(e));
                field.addEventListener('focus', (e) => this.handleFieldFocus(e));
                
                // Save data on change
                field.addEventListener('change', (e) => this.saveFieldData(e));
            });

            // Form submission handling
            const form = this.formCard.closest('form');
            if (form) {
                form.addEventListener('submit', (e) => this.handleContactFormSubmit(e));
            }

            // Listen for external validation triggers
            document.addEventListener('aiohm-validate-contact-form', () => {
                this.validateAllFields();
            });

            // Listen for data clearing
            document.addEventListener('aiohm-clear-contact-form', () => {
                this.clearFormData();
            });
        }

        /**
         * Handle field input events
         */
        handleFieldInput(e) {
            const field = e.target;
            const fieldName = field.dataset.field || field.name;
            
            // Clear previous errors on input
            this.clearFieldError(field);
            
            // Real-time validation for certain fields
            if (field.type === 'email' || field.type === 'tel') {
                setTimeout(() => {
                    this.validateField(field);
                }, 500); // Debounce validation
            }
            
            // Update form data
            this.formData[fieldName] = field.value;
            
            // Trigger data update event
            this.triggerDataUpdate();
        }

        /**
         * Handle field blur events
         */
        handleFieldBlur(e) {
            const field = e.target;
            this.validateField(field);
        }

        /**
         * Handle field focus events
         */
        handleFieldFocus(e) {
            const field = e.target;
            this.clearFieldError(field);
        }

        /**
         * Save field data to local storage
         */
        saveFieldData(e) {
            const field = e.target;
            const fieldName = field.dataset.field || field.name;
            
            this.formData[fieldName] = field.value;
            
            // Save to localStorage for persistence
            localStorage.setItem('aiohm_contact_form_data', JSON.stringify(this.formData));
            
            this.triggerDataUpdate();
        }

        /**
         * Load previously saved data
         */
        loadSavedData() {
            const savedData = localStorage.getItem('aiohm_contact_form_data');
            if (!savedData) return;

            try {
                this.formData = JSON.parse(savedData);
                
                // Populate fields with saved data
                this.formFields.forEach(field => {
                    const fieldName = field.dataset.field || field.name;
                    if (this.formData[fieldName]) {
                        field.value = this.formData[fieldName];
                    }
                });
                
            } catch (error) {
                // Could not load saved contact form data - silently fail in production
            }
        }

        /**
         * Validate individual field
         */
        validateField(field) {
            const fieldName = field.dataset.field || field.name;
            const fieldValue = field.value.trim();
            const fieldContainer = field.closest('.aiohm-contact-field');
            const isRequired = field.hasAttribute('required');

            // Clear previous errors
            this.clearFieldError(field);

            // Check required fields
            if (isRequired && !fieldValue) {
                this.showFieldError(field, 'This field is required');
                return false;
            }

            // Skip validation if field is empty and not required
            if (!fieldValue && !isRequired) {
                return true;
            }

            // Type-specific validation
            let isValid = true;
            let errorMessage = '';

            switch (field.type) {
                case 'email':
                    if (this.validationRules.email && !this.validationRules.email.pattern.test(fieldValue)) {
                        isValid = false;
                        errorMessage = this.validationRules.email.message;
                    }
                    break;

                case 'tel':
                    if (this.validationRules.phone && !this.validationRules.phone.pattern.test(fieldValue)) {
                        isValid = false;
                        errorMessage = this.validationRules.phone.message;
                    }
                    break;

                case 'number':
                    const numValue = parseInt(fieldValue);
                    if (fieldName === 'age' && this.validationRules.age) {
                        if (numValue < this.validationRules.age.min || numValue > this.validationRules.age.max) {
                            isValid = false;
                            errorMessage = this.validationRules.age.message;
                        }
                    }
                    break;

                case 'text':
                    if (fieldName === 'name' && this.validationRules.name) {
                        if (fieldValue.length < this.validationRules.name.minLength) {
                            isValid = false;
                            errorMessage = this.validationRules.name.message;
                        }
                    }
                    break;
            }

            if (!isValid) {
                this.showFieldError(field, errorMessage);
                return false;
            } else {
                this.showFieldSuccess(field);
                return true;
            }
        }

        /**
         * Show field error
         */
        showFieldError(field, message) {
            const fieldContainer = field.closest('.aiohm-contact-field');
            if (!fieldContainer) return;

            // Add error class
            fieldContainer.classList.add('has-error');
            fieldContainer.classList.remove('has-success');

            // Remove existing error message
            const existingError = fieldContainer.querySelector('.aiohm-contact-error-message');
            if (existingError) {
                existingError.remove();
            }

            // Add new error message
            const errorElement = document.createElement('div');
            errorElement.className = 'aiohm-contact-error-message';
            errorElement.textContent = message;
            
            fieldContainer.appendChild(errorElement);
        }

        /**
         * Show field success
         */
        showFieldSuccess(field) {
            const fieldContainer = field.closest('.aiohm-contact-field');
            if (!fieldContainer) return;

            fieldContainer.classList.add('has-success');
            fieldContainer.classList.remove('has-error');
        }

        /**
         * Clear field error
         */
        clearFieldError(field) {
            const fieldContainer = field.closest('.aiohm-contact-field');
            if (!fieldContainer) return;

            fieldContainer.classList.remove('has-error', 'has-success');
            
            const errorElement = fieldContainer.querySelector('.aiohm-contact-error-message');
            if (errorElement) {
                errorElement.remove();
            }
        }

        /**
         * Validate all fields
         */
        validateAllFields() {
            let allValid = true;
            
            this.formFields.forEach(field => {
                if (!this.validateField(field)) {
                    allValid = false;
                }
            });
            
            return allValid;
        }

        /**
         * Handle contact form submission
         */
        handleContactFormSubmit(e) {
            if (!this.validateAllFields()) {
                e.preventDefault();
                
                // Scroll to first error
                const firstError = this.formCard.querySelector('.has-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                return false;
            }
            
            // Clear saved data on successful submission
            localStorage.removeItem('aiohm_contact_form_data');
            
            return true;
        }

        /**
         * Clear form data
         */
        clearFormData() {
            this.formFields.forEach(field => {
                field.value = '';
                this.clearFieldError(field);
            });
            
            this.formData = {};
            localStorage.removeItem('aiohm_contact_form_data');
            
            this.triggerDataUpdate();
        }

        /**
         * Get form data (public method for other components)
         */
        getFormData() {
            // Refresh form data from current field values
            this.formFields.forEach(field => {
                const fieldName = field.dataset.field || field.name;
                this.formData[fieldName] = field.value;
            });
            
            return { ...this.formData };
        }

        /**
         * Set form data (public method)
         */
        setFormData(data) {
            Object.keys(data).forEach(fieldName => {
                const field = this.formCard.querySelector(`[data-field="${fieldName}"], [name="${fieldName}"]`);
                if (field) {
                    field.value = data[fieldName];
                    this.formData[fieldName] = data[fieldName];
                }
            });
            
            this.triggerDataUpdate();
        }

        /**
         * Check if form is valid (public method)
         */
        isValid() {
            return this.validateAllFields();
        }

        /**
         * Trigger data update event
         */
        triggerDataUpdate() {
            const customEvent = new CustomEvent('aiohm-contact-data-updated', {
                detail: {
                    formData: this.getFormData(),
                    isValid: this.validateAllFields()
                }
            });
            
            document.dispatchEvent(customEvent);
        }

        /**
         * Set loading state
         */
        setLoading(loading) {
            if (loading) {
                this.formCard.classList.add('is-loading');
            } else {
                this.formCard.classList.remove('is-loading');
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Initialize contact form
        const contactForm = new AIOHMContactForm();
        
        // Make it globally accessible for other components
        window.AIOHMContactForm = contactForm;
    });

    /**
     * Handle dynamic content loading using modern MutationObserver
     */
    if (typeof window.AIOHMContactObserver === 'undefined') {
        window.AIOHMContactObserver = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (node.classList.contains('aiohm-contact-form-card') || node.querySelector('.aiohm-contact-form-card'))) {
                            shouldReinit = true;
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                setTimeout(() => {
                    new AIOHMContactForm();
                }, 100);
            }
        });
        
        // Start observing
        window.AIOHMContactObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})(jQuery);