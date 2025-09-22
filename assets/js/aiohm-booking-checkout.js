/**
 * AIOHM Booking Checkout Handler
 *
 * Handles payment processing and notifications for the checkout step
 *
 * @package AIOHM_Booking
 * @since 1.2.7
 */

class AIOHMBookingCheckout {
    constructor() {
        this.container = null;
        this.init();
    }

    /**
     * Initialize the checkout handler
     */
    init() {
        this.container = document.querySelector('.aiohm-booking-sandwich-container');
        if (!this.container) return;
        this.setupEventListeners();
    }

    /**
     * Set up listeners to update invoice when data changes
     */
    setupInvoiceUpdateListeners() {
        // Listen for tab changes to regenerate invoice when user reaches tab 3
        const tabButtons = this.container.querySelectorAll('.aiohm-tab-button, [data-tab]');
        tabButtons.forEach(tab => {
            tab.addEventListener('click', () => {
                setTimeout(() => {
                    this.generateInvoicePreview();
                }, 100); // Small delay to ensure tab content is loaded
            });
        });

        // Listen for changes in form selections (events, accommodations)
        const selectionInputs = this.container.querySelectorAll('input[type="checkbox"], input[type="radio"], select');
        selectionInputs.forEach(input => {
            input.addEventListener('change', () => {
                setTimeout(() => {
                    this.generateInvoicePreview();
                }, 200); // Delay to ensure pricing updates
            });
        });

        // Listen for pricing updates (if there's a pricing calculation trigger)
        const pricingContainer = this.container.querySelector('.aiohm-pricing-container, .pricing-summary');
        if (pricingContainer) {
            // Use MutationObserver to watch for changes in pricing content
            const observer = new MutationObserver(() => {
                this.generateInvoicePreview();
            });
            observer.observe(pricingContainer, { 
                childList: true, 
                subtree: true, 
                characterData: true 
            });
        }
    }

    /**
     * Set up event listeners for checkout buttons
     */
    setupEventListeners() {
        // Stripe payment button
        const stripeBtn = this.container.querySelector('#aiohm-stripe-payment');
        if (stripeBtn) {
            stripeBtn.addEventListener('click', (e) => this.handleStripePayment(e));
        }

        // Free user notification button
        const notificationBtn = this.container.querySelector('#aiohm-send-notification');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', (e) => this.handleSendNotification(e));
        }
    }

    /**
     * Handle Stripe payment processing
     */
    async handleStripePayment(event) {
        event.preventDefault();

        this.showProcessingStatus('Processing payment with Stripe...');

        try {
            // Get booking data from the form
            const bookingData = this.collectBookingData();

            // Send to Stripe processing endpoint
            const response = await this.sendAjaxRequest('aiohm_booking_process_stripe', {
                booking_data: bookingData,
                payment_method: 'stripe'
            });

            if (response.success) {
                // Redirect to Stripe checkout or handle success
                if (response.data.checkout_url) {
                    window.location.href = response.data.checkout_url;
                } else {
                    this.showSuccess('Payment processed successfully!');
                    this.redirectToSuccess();
                }
            } else {
                throw new Error(response.data.message || 'Payment failed');
            }

        } catch (error) {
            console.error('AIOHM Checkout: Stripe payment error:', error);
            this.showError(error.message || 'Payment processing failed');
        } finally {
            this.hideProcessingStatus();
        }
    }

    /**
     * Handle sending notification for free users
     */
    async handleSendNotification(event) {
        event.preventDefault();

        this.showProcessingStatus('Sending invoice...');

        try {
            // Get booking data from the form
            const bookingData = this.collectBookingData();

            // Send notification
            const response = await this.sendAjaxRequest('aiohm_booking_send_notification', {
                booking_data: bookingData,
                notification_type: 'booking_confirmation'
            });

            if (response.success) {
                this.showSuccess('Invoice sent successfully!');
                this.redirectToSuccess();
            } else {
                throw new Error(response.data.message || 'Failed to send invoice');
            }

        } catch (error) {
            console.error('AIOHM Checkout: Invoice error:', error);
            this.showError(error.message || 'Failed to send invoice');
        } finally {
            this.hideProcessingStatus();
        }
    }

    /**
     * Check if we're in preview mode
     */
    isPreviewMode() {
        // Check for common preview indicators
        return (
            // Check if container has preview class
            this.container.classList.contains('aiohm-booking-preview') ||
            // Check if URL contains preview parameter
            window.location.search.includes('preview=') ||
            // Check if there's a preview element in the container
            this.container.querySelector('.aiohm-booking-preview-notice') ||
            this.container.querySelector('[data-preview="true"]') ||
            // Check if we're in an admin preview context
            document.body.classList.contains('wp-admin') ||
            // Check for preview mode data attribute
            this.container.dataset.preview === 'true' ||
            // Check if the container has preview-related text
            this.container.textContent.includes('Preview Mode') ||
            this.container.textContent.includes('This is a preview')
        );
    }

    /**
     * Collect booking data from the form
     */
    collectBookingData() {
        // Use fallback method for more reliable data collection
        console.debug('AIOHM Booking: Using fallback data collection method');
        return this.collectBookingDataFallback();
    }

    /**
     * Fallback method to collect booking data without FormData
     */
    collectBookingDataFallback() {
        // Get selected events/accommodations without form dependency
        const selectedEvents = [];
        const selectedAccommodations = [];

        // Collect event selections from anywhere in the container
        const eventCheckboxes = this.container.querySelectorAll('input[name="selected_events[]"]:checked, .event-checkbox:checked');
        eventCheckboxes.forEach(checkbox => {
            selectedEvents.push(checkbox.value);
        });

        // Collect accommodation selections
        const accommodationCheckboxes = this.container.querySelectorAll('input[name="accommodations[]"]:checked, .accommodation-checkbox:checked');
        accommodationCheckboxes.forEach(checkbox => {
            selectedAccommodations.push(checkbox.value);
        });

        // Helper function to get input value safely
        const getInputValue = (selector) => {
            const input = this.container.querySelector(selector);
            return input ? input.value : '';
        };

        const bookingData = {
            events: selectedEvents,
            accommodations: selectedAccommodations,
            contact_info: {
                name: getInputValue('input[name="contact_name"], input[name="name"]'),
                email: getInputValue('input[name="contact_email"], input[name="email"]'),
                phone: getInputValue('input[name="contact_phone"], input[name="phone"]'),
                message: getInputValue('textarea[name="contact_message"], textarea[name="message"]')
            },
            dates: {
                checkin: getInputValue('input[name="checkin_date"], #checkinHidden'),
                checkout: getInputValue('input[name="checkout_date"], #checkoutHidden')
            },
            payment_method_type: getInputValue('input[name="payment_method_type"]:checked') || 'full',
            pricing: this.getPricingData()
        };

        return bookingData;
    }

    /**
     * Get pricing data from the pricing summary
     */
    getPricingData() {
        // Try to get pricing data from the pricing summary section
        const pricingContainer = this.container.querySelector('.aiohm-pricing-container');
        if (!pricingContainer) {
            return this.getDefaultPricingData();
        }

        // Get currency from data attribute
        const currency = pricingContainer.getAttribute('data-currency') || 'RON';

        // Get total amount from the pricing summary
        const totalElement = pricingContainer.querySelector('.aiohm-total-amount');
        let total = 0;
        if (totalElement) {
            const totalText = totalElement.textContent.trim();
            // Extract numeric value from currency string (e.g., "USD 150.00" -> 150.00)
            const numericMatch = totalText.match(/[\d,]+\.?\d*/);
            if (numericMatch) {
                total = parseFloat(numericMatch[0].replace(',', '')) || 0;
            }
        }

        // Get subtotal if available
        const subtotalElement = pricingContainer.querySelector('.aiohm-subtotal-amount');
        let subtotal = total; // Default to total if subtotal not found
        if (subtotalElement) {
            const subtotalText = subtotalElement.textContent.trim();
            const numericMatch = subtotalText.match(/[\d,]+\.?\d*/);
            if (numericMatch) {
                subtotal = parseFloat(numericMatch[0].replace(',', '')) || total;
            }
        }

        // Calculate tax (total - subtotal, if subtotal is less than total)
        const tax = subtotal < total ? total - subtotal : 0;

        return {
            subtotal: subtotal,
            tax: tax,
            total: total,
            currency: currency
        };
    }

    /**
     * Get default pricing data when pricing summary is not available
     */
    getDefaultPricingData() {
        return {
            subtotal: 100.00,
            tax: 0,
            total: 100.00,
            currency: 'USD'
        };
    }

    /**
     * Generate invoice preview for free users
     */
    generateInvoicePreview() {
        const invoiceContainer = this.container.querySelector('#aiohm-invoice-preview');
        if (!invoiceContainer) return;

        const bookingData = this.collectBookingData();
        const companyData = this.getCompanyData();
        const pricingData = bookingData.pricing || this.getDefaultPricingData();
        const bookingReference = this.generateBookingReference();
        const currentDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Generate professional invoice HTML
        const invoiceHtml = `
            <div class="aiohm-invoice-header">
                <div class="aiohm-invoice-header-left">
                    ${companyData.logo ? `<img src="${companyData.logo}" alt="${companyData.name}" class="aiohm-invoice-logo">` : `<div class="aiohm-invoice-company-name">${companyData.name}</div>`}
                </div>
                <div class="aiohm-invoice-header-right">
                    <div class="aiohm-invoice-number">NO. ${bookingReference}</div>
                </div>
            </div>

            <div class="aiohm-invoice-title">
                <h1>INVOICE</h1>
            </div>

            <div class="aiohm-invoice-meta">
                <div class="aiohm-invoice-date">
                    <strong>Date:</strong> ${currentDate}
                </div>
            </div>

            <div class="aiohm-invoice-parties">
                <div class="aiohm-invoice-billed-to">
                    <h4>Billed to:</h4>
                    <div class="aiohm-invoice-customer-info">
                        <div>${bookingData.contact_info.name || 'Customer Name'}</div>
                        <div>${bookingData.contact_info.email || 'customer@email.com'}</div>
                        <div>${bookingData.contact_info.phone || 'Phone Number'}</div>
                    </div>
                </div>
                <div class="aiohm-invoice-from">
                    <h4>From:</h4>
                    <div class="aiohm-invoice-company-info">
                        <div>${companyData.name}</div>
                        <div>${companyData.address}</div>
                        <div>${companyData.contact}</div>
                    </div>
                </div>
            </div>

            <div class="aiohm-invoice-table-container">
                <table class="aiohm-invoice-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.generateInvoiceItems(bookingData, pricingData)}
                    </tbody>
                    <tfoot>
                        <tr class="aiohm-invoice-total-row">
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong>${pricingData.currency} ${pricingData.total.toFixed(2)}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="aiohm-invoice-payment-info">
                <div class="aiohm-invoice-payment-method">
                    <strong>Payment method:</strong> Invoice Payment
                </div>
                <div class="aiohm-invoice-note">
                    <strong>Note:</strong> Payment details will be sent separately via email. Thank you for choosing us!
                </div>
            </div>
        `;

        invoiceContainer.innerHTML = invoiceHtml;
    }

    /**
     * Generate invoice items from booking data
     */
    generateInvoiceItems(bookingData, pricingData) {
        let items = [];
        
        // Try to get items from mini-cards summary (tab 2)
        const summaryItems = this.getItemsFromSummary();
        
        if (summaryItems.length > 0) {
            return summaryItems.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>${item.currency} ${item.price.toFixed(2)}</td>
                    <td>${item.currency} ${item.amount.toFixed(2)}</td>
                </tr>
            `).join('');
        }
        
        // Fallback: Add events with proper pricing
        if (bookingData.events && bookingData.events.length > 0) {
            bookingData.events.forEach((eventId, index) => {
                const eventName = this.getEventName(eventId) || `Event ${index + 1}`;
                const eventDetails = this.getEventDetails(eventId);
                const eventPrice = eventDetails?.price || (pricingData.total / (bookingData.events.length + bookingData.accommodations.length)) || 0;
                items.push(`
                    <tr>
                        <td>${eventName}</td>
                        <td>1</td>
                        <td>${pricingData.currency} ${eventPrice.toFixed(2)}</td>
                        <td>${pricingData.currency} ${eventPrice.toFixed(2)}</td>
                    </tr>
                `);
            });
        }
        
        // Add accommodations with proper pricing
        if (bookingData.accommodations && bookingData.accommodations.length > 0) {
            bookingData.accommodations.forEach((accomId, index) => {
                const accomName = this.getAccommodationName(accomId) || `Accommodation ${index + 1}`;
                const accomDetails = this.getAccommodationDetails(accomId);
                const accomPrice = accomDetails?.price || (pricingData.total / (bookingData.events.length + bookingData.accommodations.length)) || 0;
                items.push(`
                    <tr>
                        <td>${accomName}</td>
                        <td>1</td>
                        <td>${pricingData.currency} ${accomPrice.toFixed(2)}</td>
                        <td>${pricingData.currency} ${accomPrice.toFixed(2)}</td>
                    </tr>
                `);
            });
        }
        
        // If no items, add a generic booking item
        if (items.length === 0) {
            items.push(`
                <tr>
                    <td>Booking Service</td>
                    <td>1</td>
                    <td>${pricingData.currency} ${pricingData.total.toFixed(2)}</td>
                    <td>${pricingData.currency} ${pricingData.total.toFixed(2)}</td>
                </tr>
            `);
        }
        
        return items.join('');
    }

    /**
     * Get items from mini-cards summary (tab 2)
     */
    getItemsFromSummary() {
        const items = [];
        
        // Look for pricing summary container
        const pricingContainer = this.container.querySelector('.aiohm-pricing-summary-card, .aiohm-pricing-container, .aiohm-booking-summary, .aiohm-summary-container');
        
        if (!pricingContainer) {
            // Try alternative selectors
            const alternatives = [
                '.pricing-summary', 
                '.booking-summary', 
                '.summary', 
                '.aiohm-pricing', 
                '.mini-cards',
                '[class*="summary"]',
                '[class*="pricing"]'
            ];
            
            for (const selector of alternatives) {
                const altContainer = this.container.querySelector(selector);
                if (altContainer) {
                    return this.parseContainerItems(altContainer);
                }
            }
            
            return items;
        }

        return this.parseContainerItems(pricingContainer);
    }

    /**
     * Parse items from a container element
     */
    parseContainerItems(container) {
        const items = [];

        // Get currency from container or fallback
        const currency = container.getAttribute('data-currency') || 
                        this.container.querySelector('[data-currency]')?.getAttribute('data-currency') || 
                        'RON';

        // Look for individual item cards or rows
        const itemSelectors = [
            '.aiohm-summary-item', 
            '.aiohm-pricing-item', 
            '.aiohm-item-row', 
            '.aiohm-booking-item',
            '.aiohm-accommodation-summary-item',
            '.aiohm-event-summary-item',
            '.summary-item',
            '.pricing-item',
            '.item-row',
            '.booking-item',
            '[class*="item"]'
        ];
        
        let itemElements = [];
        for (const selector of itemSelectors) {
            const elements = container.querySelectorAll(selector);
            if (elements.length > 0) {
                // Add to the array instead of replacing
                itemElements.push(...Array.from(elements));
            }
        }
        
        if (itemElements.length > 0) {
            itemElements.forEach(itemEl => {
                const item = this.parseItemFromElement(itemEl, currency);
                if (item) {
                    items.push(item);
                }
            });
        }

        // If no individual items found, try to parse from text content
        if (items.length === 0) {
            const summaryText = container.textContent || '';
            const parsedItems = this.parseItemsFromText(summaryText, currency);
            items.push(...parsedItems);
        }

        return items;
    }

    /**
     * Parse item from DOM element
     */
    parseItemFromElement(element, currency) {
        // First try standard selectors
        let nameEl = element.querySelector('.item-name, .aiohm-item-title, h4, .title');
        let priceEl = element.querySelector('.item-price, .aiohm-price, .price');
        let quantityEl = element.querySelector('.item-quantity, .quantity');

        // If not found, try mini-card structure (accommodations/events)
        if (!nameEl && element.classList.contains('aiohm-accommodation-summary-item')) {
            // For accommodation mini-cards
            const labelEls = element.querySelectorAll('.aiohm-booking-label');
            const valueEls = element.querySelectorAll('.aiohm-booking-value');
            
            // Find "Accommodation:" label and get its value
            for (let i = 0; i < labelEls.length; i++) {
                if (labelEls[i].textContent.includes('Accommodation:')) {
                    nameEl = valueEls[i];
                    break;
                }
            }
            
            // Get total price from "Total:" row
            for (let i = 0; i < labelEls.length; i++) {
                if (labelEls[i].textContent.includes('Total:')) {
                    priceEl = valueEls[i];
                    break;
                }
            }
            
            // Get quantity from "Units:" row
            for (let i = 0; i < labelEls.length; i++) {
                if (labelEls[i].textContent.includes('Units:')) {
                    quantityEl = valueEls[i];
                    break;
                }
            }
        } else if (!nameEl && element.classList.contains('aiohm-event-summary-item')) {
            // For event mini-cards
            const labelEls = element.querySelectorAll('.aiohm-booking-label');
            const valueEls = element.querySelectorAll('.aiohm-booking-value');
            
            // Find "Event name:" label and get its value
            for (let i = 0; i < labelEls.length; i++) {
                if (labelEls[i].textContent.includes('Event name:')) {
                    nameEl = valueEls[i];
                    break;
                }
            }
            
            // Get total price from "Total:" row
            for (let i = 0; i < labelEls.length; i++) {
                if (labelEls[i].textContent.includes('Total:')) {
                    priceEl = valueEls[i];
                    break;
                }
            }
            
            // Get quantity from "Tickets nr:" row
            for (let i = 0; i < labelEls.length; i++) {
                if (labelEls[i].textContent.includes('Tickets nr:')) {
                    quantityEl = valueEls[i];
                    break;
                }
            }
        }

        if (!nameEl) return null;

        const name = nameEl.textContent.trim();
        const priceText = priceEl?.textContent.trim() || '0';
        const quantityText = quantityEl?.textContent.trim() || '1';
        const quantity = parseInt(quantityText) || 1;

        // Extract numeric price from text like "RON 150.00" or "<strong>RON 7696.00</strong>"
        const priceMatch = priceText.match(/[\d,]+\.?\d*/);
        const price = priceMatch ? parseFloat(priceMatch[0].replace(',', '')) : 0;

        return {
            name: name,
            quantity: quantity,
            price: price,
            amount: price * quantity,
            currency: currency
        };
    }

    /**
     * Parse items from text content (fallback method)
     */
    parseItemsFromText(text, currency) {
        const items = [];
        
        // Look for patterns like "Event Name: 150 RON" or "Accommodation: €120"
        const patterns = [
            /([^:]+):\s*(\d+(?:\.\d{2})?)\s*(?:RON|EUR|USD|\$|€)/gi,
            /([^-]+)-\s*(\d+(?:\.\d{2})?)/gi
        ];

        patterns.forEach(pattern => {
            let match;
            while ((match = pattern.exec(text)) !== null) {
                const name = match[1].trim();
                const price = parseFloat(match[2]);
                
                if (name && price > 0) {
                    items.push({
                        name: name,
                        quantity: 1,
                        price: price,
                        amount: price,
                        currency: currency
                    });
                }
            }
        });

        return items;
    }

    /**
     * Get company/business data for invoice header
     */
    getCompanyData() {
        return {
            name: window.aiohm_booking_settings?.company_name || 'Your Business Name',
            logo: window.aiohm_booking_settings?.company_logo || '',
            address: window.aiohm_booking_settings?.company_address || '123 Business St., Your City',
            contact: window.aiohm_booking_settings?.company_contact || 'contact@yourbusiness.com'
        };
    }

    /**
     * Get event name by ID
     */
    getEventName(eventId) {
        const eventElement = this.container.querySelector(`[data-event-id="${eventId}"]`);
        if (eventElement) {
            const nameElement = eventElement.querySelector('.event-name, .aiohm-event-title, h3, h4');
            if (nameElement) return nameElement.textContent.trim();
        }
        return `Event #${eventId}`;
    }

    /**
     * Get event details including price
     */
    getEventDetails(eventId) {
        const eventElement = this.container.querySelector(`[data-event-id="${eventId}"]`);
        if (eventElement) {
            const nameElement = eventElement.querySelector('.event-name, .aiohm-event-title, h3, h4');
            const priceElement = eventElement.querySelector('.event-price, .aiohm-price, .price');
            
            const name = nameElement?.textContent.trim() || `Event #${eventId}`;
            let price = 0;
            
            if (priceElement) {
                const priceText = priceElement.textContent.trim();
                const priceMatch = priceText.match(/[\d,]+\.?\d*/);
                price = priceMatch ? parseFloat(priceMatch[0].replace(',', '')) : 0;
            }
            
            return { name, price };
        }
        return { name: `Event #${eventId}`, price: 0 };
    }

    /**
     * Get accommodation name by ID
     */
    getAccommodationName(accomId) {
        const accomElement = this.container.querySelector(`[data-accommodation-id="${accomId}"]`);
        if (accomElement) {
            const nameElement = accomElement.querySelector('.accommodation-name, .aiohm-accommodation-title, h3, h4');
            if (nameElement) return nameElement.textContent.trim();
        }
        return `Accommodation #${accomId}`;
    }

    /**
     * Get accommodation details including price
     */
    getAccommodationDetails(accomId) {
        const accomElement = this.container.querySelector(`[data-accommodation-id="${accomId}"]`);
        if (accomElement) {
            const nameElement = accomElement.querySelector('.accommodation-name, .aiohm-accommodation-title, h3, h4');
            const priceElement = accomElement.querySelector('.accommodation-price, .aiohm-price, .price');
            
            const name = nameElement?.textContent.trim() || `Accommodation #${accomId}`;
            let price = 0;
            
            if (priceElement) {
                const priceText = priceElement.textContent.trim();
                const priceMatch = priceText.match(/[\d,]+\.?\d*/);
                price = priceMatch ? parseFloat(priceMatch[0].replace(',', '')) : 0;
            }
            
            return { name, price };
        }
        return { name: `Accommodation #${accomId}`, price: 0 };
    }

    /**
     * Generate a booking reference number
     */
    generateBookingReference() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        return `BK${timestamp}${random}`;
    }

    /**
     * Send AJAX request to WordPress
     */
    async sendAjaxRequest(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', aiohm_booking_frontend.nonce);

        // Add data to form
        Object.keys(data).forEach(key => {
            if (typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            } else {
                formData.append(key, data[key]);
            }
        });

        const response = await fetch(aiohm_booking_frontend.ajax_url, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const jsonResponse = await response.json();

        return jsonResponse;
    }

    /**
     * Show processing status
     */
    showProcessingStatus(message = 'Processing...') {
        const statusElement = this.container.querySelector('#aiohm-processing-status');
        if (statusElement) {
            const messageElement = statusElement.querySelector('span');
            if (messageElement) {
                messageElement.textContent = message;
            }
            statusElement.style.display = 'block';
        }
    }

    /**
     * Hide processing status
     */
    hideProcessingStatus() {
        const statusElement = this.container.querySelector('#aiohm-processing-status');
        if (statusElement) {
            statusElement.style.display = 'none';
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // You could implement a toast notification system here
        alert('Success: ' + message);
    }

    /**
     * Show error message
     */
    showError(message) {
        // You could implement a toast notification system here
        alert('Error: ' + message);
    }

    /**
     * Redirect to success page
     */
    redirectToSuccess() {
        // Redirect to success page or show success message
        setTimeout(() => {
            window.location.href = '/booking-success'; // Adjust URL as needed
        }, 2000);
    }
}

// Initialize checkout handler when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on frontend pages with booking containers
    if (!document.body.classList.contains('wp-admin') && 
        document.querySelector('.aiohm-booking-sandwich-container')) {
        try {
            window.AIOHMBookingCheckout = new AIOHMBookingCheckout();
        } catch (error) {
            console.warn('AIOHM Booking: Error initializing checkout:', error);
        }
    }
});
// Cache buster: 1758520435
