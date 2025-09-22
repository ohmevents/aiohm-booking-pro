/**
 * AIOHM Booking Event Selection Card JavaScript
 * Handles event selection functionality and UI interactions
 *
 * @package AIOHM_Booking_PRO
 * @version 1.2.3
 */

(function($) {
    'use strict';

    /**
     * Event Selection Card Handler
     */
    class AIOHMEventSelection {
        constructor() {
            this.eventCards = [];
            this.eventInputs = []; // Changed from eventRadios to handle both radio and checkbox
            this.selectedEvents = []; // Changed from selectedEvent to handle multiple selections
            this.allowMultiple = false;
            
            this.init();
        }

        /**
         * Initialize the event selection functionality
         */
        init() {
            this.bindElements();
            this.bindEvents();
            this.checkPreSelectedEvents();
            this.bindTicketQuantityControls();
        }

        /**
         * Bind DOM elements
         */
        bindElements() {
            // Try multiple possible container selectors for different contexts
            const containerSelectors = [
                '.aiohm-booking-event-selection-card', // Original selector
                '.aiohm-booking-selection-section', // Sandwich template selector
                '.aiohm-booking-event-selection' // Alternative selector
            ];
            
            let container = null;
            for (const selector of containerSelectors) {
                container = document.querySelector(selector);
                if (container) break;
            }
            
            if (!container) return;

            this.eventCards = container.querySelectorAll('.aiohm-booking-event-card');
            
            // Handle both radio buttons and checkboxes
            this.eventInputs = container.querySelectorAll('.aiohm-booking-event-radio, .aiohm-booking-event-checkbox');
            
            // Determine if multiple selections are allowed
            const firstInput = this.eventInputs[0];
            this.allowMultiple = firstInput && firstInput.type === 'checkbox';
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Event selection handling (for both radio and checkbox)
            this.eventInputs.forEach((input, index) => {
                input.addEventListener('change', (e) => this.handleEventSelection(e, index));
            });

            // Card click handling (click anywhere on card to select)
            this.eventCards.forEach((card, index) => {
                card.addEventListener('click', (e) => {
                    // Don't trigger if clicking on ticket quantity controls (they're inside label but need special handling)
                    if (e.target.closest('.aiohm-booking-ticket-quantity-controls')) {
                        return;
                    }
                    
                    // Don't handle other clicks if they're on the label - let the browser handle it naturally
                    if (e.target.tagName === 'LABEL' || e.target.closest('label')) {
                        return;
                    }
                    
                    // Don't trigger if clicking on the input directly
                    if (e.target.classList.contains('aiohm-booking-event-radio') || 
                        e.target.classList.contains('aiohm-booking-event-checkbox')) {
                        return;
                    }
                    
                    const input = card.querySelector('.aiohm-booking-event-radio, .aiohm-booking-event-checkbox');
                    if (input && !input.disabled) {
                        if (input.type === 'checkbox') {
                            input.checked = !input.checked;
                        } else {
                            input.checked = true;
                        }
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
        }

        /**
         * Check for pre-selected events and populate selectedEvents array
         */
        checkPreSelectedEvents() {
            if (!this.eventInputs.length) return;
            
            // Check for checked inputs and populate selectedEvents
            this.eventInputs.forEach((input, index) => {
                if (input.checked) {
                    const eventData = this.getEventData(input, index);
                    if (this.allowMultiple) {
                        this.selectedEvents.push(eventData);
                    } else {
                        this.selectedEvents = [eventData];
                    }
                }
            });
            
            // If we have selected events, trigger the event for the pricing summary
            if (this.selectedEvents.length > 0) {
                this.triggerEventSelected();
            }
        }

        /**
         * Bind ticket quantity controls
         */
        bindTicketQuantityControls() {
            // Try multiple possible container selectors for different contexts
            const containerSelectors = [
                '.aiohm-booking-event-selection-card', // Original selector
                '.aiohm-booking-selection-section', // Sandwich template selector
                '.aiohm-booking-event-selection' // Alternative selector
            ];
            
            let container = null;
            for (const selector of containerSelectors) {
                container = document.querySelector(selector);
                if (container) break;
            }
            
            if (!container) return;

            // Handle +/- buttons
            container.addEventListener('click', (e) => {
                if (e.target.classList.contains('aiohm-ticket-minus')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleTicketDecrease(e.target);
                } else if (e.target.classList.contains('aiohm-ticket-plus')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleTicketIncrease(e.target);
                }
            });

            // Handle direct input changes
            container.addEventListener('input', (e) => {
                if (e.target.classList.contains('aiohm-booking-ticket-quantity-input')) {
                    this.handleTicketInputChange(e.target);
                }
            });

            // Handle keypress for numeric-only input
            container.addEventListener('keypress', (e) => {
                if (e.target.classList.contains('aiohm-booking-ticket-quantity-input')) {
                    // Only allow numbers
                    if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                        e.preventDefault();
                    }
                }
            });
        }

        /**
         * Handle ticket quantity decrease
         * @param {HTMLElement} button - The minus button
         */
        handleTicketDecrease(button) {
            const eventIndex = button.dataset.eventIndex;
            const input = button.parentElement.querySelector('.aiohm-booking-ticket-quantity-input');
            
            if (input) {
                const currentValue = parseInt(input.value) || 0;
                const newValue = Math.max(0, currentValue - 1);
                input.value = newValue;
                this.updateTicketDisplay(eventIndex, newValue);
            }
        }

        /**
         * Handle ticket quantity increase
         * @param {HTMLElement} button - The plus button
         */
        handleTicketIncrease(button) {
            const eventIndex = button.dataset.eventIndex;
            const input = button.parentElement.querySelector('.aiohm-booking-ticket-quantity-input');
            
            if (input) {
                const currentValue = parseInt(input.value) || 0;
                const maxValue = parseInt(input.getAttribute('max')) || 50;
                const newValue = Math.min(maxValue, currentValue + 1);
                input.value = newValue;
                this.updateTicketDisplay(eventIndex, newValue);
            }
        }

        /**
         * Handle direct input changes
         * @param {HTMLElement} input - The quantity input
         */
        handleTicketInputChange(input) {
            const eventIndex = input.dataset.eventIndex;
            const value = parseInt(input.value) || 0;
            const maxValue = parseInt(input.getAttribute('max')) || 50;
            
            // Ensure value is within bounds
            const newValue = Math.max(0, Math.min(maxValue, value));
            if (newValue !== value) {
                input.value = newValue;
            }
            
            this.updateTicketDisplay(eventIndex, newValue);
        }

        /**
         * Update ticket display (decrease total available, keep sold unchanged)
         * @param {string} eventIndex - The event index
         * @param {number} selectedTickets - Number of tickets selected
         */
        updateTicketDisplay(eventIndex, selectedTickets) {
            const eventCard = document.querySelector(`[data-event-index="${eventIndex}"]`);
            if (!eventCard) return;

            // Get elements
            const totalElement = eventCard.querySelector('.aiohm-booking-total-tickets .aiohm-booking-ticket-number');
            const soldElement = eventCard.querySelector('.aiohm-booking-tickets-sold .aiohm-booking-ticket-number');
            
            if (totalElement && soldElement) {
                const radio = eventCard.querySelector('.aiohm-booking-event-radio');
                const availableSeats = parseInt(radio?.dataset.availableSeats) || 0;
                
                // Get original values from data attributes or initial display
                if (!totalElement.dataset.originalTotal) {
                    totalElement.dataset.originalTotal = totalElement.textContent;
                }
                if (!soldElement.dataset.originalSold) {
                    soldElement.dataset.originalSold = soldElement.textContent;
                }
                
                const originalTotal = parseInt(totalElement.dataset.originalTotal) || 0;
                const originalSold = parseInt(soldElement.dataset.originalSold) || 0;
                
                // Calculate new total (decrease by selected tickets)
                const newTotal = Math.max(0, originalTotal - selectedTickets);
                
                // Update displays
                totalElement.textContent = newTotal;
                soldElement.textContent = originalSold; // Keep sold unchanged
            }

            // Trigger custom event for form synchronization
            const customEvent = new CustomEvent('aiohm-tickets-updated', {
                detail: {
                    eventIndex: eventIndex,
                    selectedTickets: selectedTickets,
                    availableTickets: totalElement ? parseInt(totalElement.textContent) : 0,
                    eventData: this.selectedEvent
                }
            });
            
            document.dispatchEvent(customEvent);

            // Calculate total tickets across all events and trigger pricing summary update
            this.updatePricingSummary();
        }

        /**
         * Update pricing summary with total ticket quantity
         */
        updatePricingSummary() {
            // Calculate total tickets across all events
            const allTicketInputs = document.querySelectorAll('.aiohm-booking-ticket-quantity-input');
            let totalTickets = 0;
            
            allTicketInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                totalTickets += quantity;
            });

            // Trigger pricing summary update
            const pricingEvent = new CustomEvent('aiohm-ticket-quantity-changed', {
                detail: {
                    quantity: totalTickets
                }
            });
            
            document.dispatchEvent(pricingEvent);
        }

        /**
         * Handle event selection
         * @param {Event} e - The change event
         * @param {number} index - Index of the selected event
         */
        handleEventSelection(e, index) {
            const input = e.target;
            
            if (this.allowMultiple) {
                // Handle checkbox (multiple selection)
                const eventData = this.getEventData(input, index);
                
                if (input.checked) {
                    // Add to selected events
                    this.selectedEvents.push(eventData);
                    this.eventCards[index].classList.add('selected');
                } else {
                    // Remove from selected events
                    this.selectedEvents = this.selectedEvents.filter(event => event.index !== index);
                    this.eventCards[index].classList.remove('selected');
                }
            } else {
                // Handle radio (single selection)
                if (input.checked) {
                    // Remove selected class from all cards
                    this.eventCards.forEach(card => {
                        card.classList.remove('selected');
                    });
                    
                    // Add selected class to current card
                    this.eventCards[index].classList.add('selected');
                    
                    // Update selected events array (single event)
                    this.selectedEvents = [this.getEventData(input, index)];
                }
            }
            
            // Trigger custom event for other components
            this.triggerEventSelected();
            
            // Smooth scroll to show selection if it's not visible
            this.scrollToShowSelection();
        }

        /**
         * Extract event data from input element
         * @param {HTMLElement} input - The input element
         * @param {number} index - Event index
         * @returns {Object} Event data object
         */
        getEventData(input, index) {
            return {
                index: index,
                title: input.dataset.eventTitle || '',
                date: input.dataset.eventDate || '',
                time: input.dataset.eventTime || '',
                endDate: input.dataset.eventEndDate || '',
                endTime: input.dataset.eventEndTime || '',
                eventType: input.dataset.eventType || '',
                price: parseFloat(input.dataset.price) || 0,
                regularPrice: parseFloat(input.dataset.regularPrice) || 0,
                earlyPrice: parseFloat(input.dataset.earlyPrice) || 0,
                availableSeats: parseInt(input.dataset.availableSeats) || 0,
                description: input.dataset.eventDescription || '',
                earlyBirdDays: parseInt(input.getAttribute('data-event-early-bird-days')) || 0,
                depositPercentage: parseInt(input.getAttribute('data-event-deposit-percentage')) || 0
            };
        }

        /**
         * Trigger custom event when an event is selected
         */
        triggerEventSelected() {
            const customEvent = new CustomEvent('aiohm-event-selected', {
                detail: {
                    selectedEvents: this.selectedEvents,
                    allowMultiple: this.allowMultiple,
                    // Backwards compatibility
                    selectedEvent: this.selectedEvents[0] || null,
                    eventData: this.selectedEvents[0] || null
                }
            });
            
            document.dispatchEvent(customEvent);
        }

        /**
         * Scroll to show the selected event if it's not fully visible
         */
        scrollToShowSelection() {
            const selectedCard = document.querySelector('.aiohm-booking-event-card.selected');
            const scrollContainer = document.querySelector('.aiohm-booking-events-scroll-container');
            
            if (!selectedCard || !scrollContainer) return;
            
            const cardTop = selectedCard.offsetTop;
            const cardBottom = cardTop + selectedCard.offsetHeight;
            const containerTop = scrollContainer.scrollTop;
            const containerBottom = containerTop + scrollContainer.offsetHeight;
            
            // If card is not fully visible, scroll to show it
            if (cardTop < containerTop) {
                scrollContainer.scrollTop = cardTop - 10;
            } else if (cardBottom > containerBottom) {
                scrollContainer.scrollTop = cardBottom - scrollContainer.offsetHeight + 10;
            }
        }

        /**
         * Get currency symbol from the page or default to EUR
         */
        getCurrency() {
            // Try to get currency from any price element
            const priceElement = document.querySelector('.aiohm-current-price');
            if (priceElement) {
                const text = priceElement.textContent;
                const match = text.match(/^([^\d.,]+)/);
                if (match) {
                    return match[1];
                }
            }
            
            return 'RON';
        }

        /**
         * Get selected event data (public method for other components)
         */
        getSelectedEvent() {
            return this.selectedEvent;
        }

        /**
         * Clear event selection (public method)
         */
        clearSelection() {
            this.eventRadios.forEach(radio => {
                radio.checked = false;
            });
            
            this.eventCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Reset all ticket quantities
            this.resetAllTicketQuantities();
            
            // Removed: Hide selected event summary
            // if (this.selectedEventSummary) {
            //     this.selectedEventSummary.style.display = 'none';
            // }
            
            this.selectedEvent = null;
        }

        /**
         * Reset all ticket quantities to 0 and restore original totals
         */
        resetAllTicketQuantities() {
            const ticketInputs = document.querySelectorAll('.aiohm-booking-ticket-quantity-input');
            ticketInputs.forEach(input => {
                input.value = 0;
                const eventIndex = input.dataset.eventIndex;
                if (eventIndex) {
                    // Restore original totals
                    const eventCard = document.querySelector(`[data-event-index="${eventIndex}"]`);
                    if (eventCard) {
                        const totalElement = eventCard.querySelector('.aiohm-booking-total-tickets .aiohm-booking-ticket-number');
                        const soldElement = eventCard.querySelector('.aiohm-booking-tickets-sold .aiohm-booking-ticket-number');
                        
                        if (totalElement && totalElement.dataset.originalTotal) {
                            totalElement.textContent = totalElement.dataset.originalTotal;
                        }
                        if (soldElement && soldElement.dataset.originalSold) {
                            soldElement.textContent = soldElement.dataset.originalSold;
                        }
                    }
                }
            });
            
            // Update pricing summary to reflect 0 tickets
            this.updatePricingSummary();
        }

        /**
         * Select event by index (public method)
         */
        selectEventByIndex(index) {
            if (index >= 0 && index < this.eventRadios.length) {
                const radio = this.eventRadios[index];
                if (radio && !radio.disabled) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                }
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Initialize event selection
        const eventSelection = new AIOHMEventSelection();
        
        // Make it globally accessible for other components
        window.AIOHMEventSelection = eventSelection;
        
        // Auto-select first available event if there's only one
        const availableEvents = document.querySelectorAll('.aiohm-booking-event-radio:not([disabled])');
        if (availableEvents.length === 1) {
            setTimeout(() => {
                availableEvents[0].checked = true;
                availableEvents[0].dispatchEvent(new Event('change'));
            }, 100);
        }
    });

    /**
     * Handle dynamic content loading using modern MutationObserver
     */
    if (typeof window.AIOHMObserver === 'undefined') {
        window.AIOHMObserver = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (node.classList.contains('aiohm-booking-event-selection-card') || node.querySelector('.aiohm-booking-event-selection-card'))) {
                            shouldReinit = true;
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                setTimeout(() => {
                    const eventSelection = new AIOHMEventSelection();
                    window.AIOHMEventSelection = eventSelection;
                }, 100);
            }
        });
        
        // Start observing
        window.AIOHMObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})(jQuery);