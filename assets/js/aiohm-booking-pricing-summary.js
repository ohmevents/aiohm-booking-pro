/**
 * AIOHM Booking Pricing Summary Card JavaScript
 * Handles dynamic pricing calculations, totals, deposits, and payment options
 *
 * @package AIOHM_Booking_PRO
 * @version 1.2.3
 */

(function($) {
    'use strict';

    /**
     * Pricing Summary Card Handler
     */
    class AIOHMPricingSummary {
        constructor() {
            // Store instance globally for access from other components
            if (!window.AIOHM_Booking_Pricing_Summary) {
                window.AIOHM_Booking_Pricing_Summary = {};
            }
            window.AIOHM_Booking_Pricing_Summary.instance = this;
            
            this.pricingCard = null;
            this.pricingContainer = null;
            this.selectedEvents = [];
            this.selectedAccommodations = [];
            this.ticketQuantity = 1;
            this.isSubmitting = false;
            
            // Configuration
            this.currency = 'RON';
            this.depositPercent = 50;
            this.earlybirdDays = 30;
            this.checkoutUrl = '';
            
            // UI Elements
            this.elements = {};
            
            this.init();
        }

        /**
         * Initialize the pricing summary functionality
         */
        init() {
            this.bindElements();
            this.loadConfiguration();
            this.bindEvents();
            this.updateDisplay();
        }

        /**
         * Bind DOM elements
         */
        bindElements() {
            this.pricingCard = document.querySelector('.aiohm-pricing-summary-card');
            if (!this.pricingCard) return;

            this.pricingContainer = this.pricingCard.querySelector('.aiohm-pricing-container');
            
            // Bind all UI elements
            this.elements = {
                // Summary sections
                noSelectionMessage: this.pricingCard.querySelector('.aiohm-no-selection-message'),
                selectedEvents: this.pricingCard.querySelector('.aiohm-selected-events'),
                selectedAccommodations: this.pricingCard.querySelector('.aiohm-selected-accommodations'),
                eventSummaryItem: this.pricingCard.querySelector('.aiohm-booking-event-summary-item'),
                accommodationsList: this.pricingCard.querySelector('.aiohm-accommodations-list'),
                
                // Pricing rows
                earlybirdRow: this.pricingCard.querySelector('.aiohm-earlybird-row'),
                totalRow: this.pricingCard.querySelector('.aiohm-total-row'),
                depositRow: this.pricingCard.querySelector('.aiohm-deposit-row'),
                balanceRow: this.pricingCard.querySelector('.aiohm-balance-row'),
                
                // Price amounts
                discountAmount: this.pricingCard.querySelector('.aiohm-discount-amount'),
                totalAmount: this.pricingCard.querySelector('.aiohm-total-amount'),
                depositAmount: this.pricingCard.querySelector('.aiohm-deposit-amount'),
                balanceAmount: this.pricingCard.querySelector('.aiohm-balance-amount'),
                earlybirdDaysText: this.pricingCard.querySelector('.aiohm-earlybird-days-text'),
                depositPercentText: this.pricingCard.querySelector('.aiohm-deposit-label small'),
                
                // Nightly breakdown
                nightlyBreakdown: this.pricingCard.querySelector('.aiohm-nightly-breakdown'),
                nightlyBreakdownList: this.pricingCard.querySelector('.aiohm-nightly-breakdown-list'),
                
                // Event summary fields
                eventTitle: this.pricingCard.querySelector('.aiohm-event-title'),
                eventTypeBadge: this.pricingCard.querySelector('.aiohm-event-type-badge'),
                eventStartDate: this.pricingCard.querySelector('.aiohm-event-start-date'),
                eventStartTime: this.pricingCard.querySelector('.aiohm-event-start-time'),
                eventEndDate: this.pricingCard.querySelector('.aiohm-event-end-date'),
                eventEndTime: this.pricingCard.querySelector('.aiohm-event-end-time'),
                eventEndInfo: this.pricingCard.querySelector('.aiohm-event-end-info'),
                currentPrice: this.pricingCard.querySelector('.aiohm-current-price'),
                originalPrice: this.pricingCard.querySelector('.aiohm-original-price'),
                earlyBirdBadge: this.pricingCard.querySelector('.aiohm-early-bird-badge'),
                specialPricingBadge: this.pricingCard.querySelector('.aiohm-special-pricing-badge'),
                quantityValue: this.pricingCard.querySelector('.aiohm-quantity-value'),
                
                // Actions
                bookingBtn: this.pricingCard.querySelector('.aiohm-booking-btn')
            };
        }

        /**
         * Load configuration from data attributes
         */
        loadConfiguration() {
            if (!this.pricingContainer) return;
            
            this.currency = this.pricingContainer.dataset.currency || 'RON';
            this.depositPercent = parseInt(this.pricingContainer.dataset.depositPercent) || 50;
            this.earlybirdDays = parseInt(this.pricingContainer.dataset.earlybirdDays) || 30;
            this.checkoutUrl = this.pricingContainer.dataset.checkoutUrl || '';
        }

        /**
         * Check if any date in the range is marked as a private event
         * @param {string} checkinDate - Check-in date in YYYY-MM-DD format
         * @param {string} checkoutDate - Check-out date in YYYY-MM-DD format
         * @returns {boolean} - True if any night has a private event
         */
        checkIfPrivateEventInDateRange(checkinDate, checkoutDate) {
            if (!checkinDate || !checkoutDate) return false;
            
            const startDate = new Date(checkinDate);
            const endDate = new Date(checkoutDate);
            
            // Check each night in the stay (excluding checkout date)
            for (let date = new Date(startDate); date < endDate; date.setDate(date.getDate() + 1)) {
                const dateStr = date.toISOString().split('T')[0];
                
                // Check if this date has private event data
                if (window.AIOHM_Booking_Shortcode && window.AIOHM_Booking_Shortcode.cachedAvailability) {
                    const dayData = window.AIOHM_Booking_Shortcode.cachedAvailability[dateStr];
                    if (dayData && dayData.is_private_event) {
                        return true;
                    }
                }
                
                // Also check global calendar data if available
                if (typeof aiohm_booking_calendar !== 'undefined' && aiohm_booking_calendar.private_events) {
                    const privateEventData = aiohm_booking_calendar.private_events[dateStr];
                    if (privateEventData && privateEventData.is_private_event) {
                        return true;
                    }
                }
            }
            
            return false;
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Listen for event selection changes
            document.addEventListener('aiohm-event-selected', (e) => {
                this.handleEventSelection(e.detail);
            });
            
            // Listen for accommodation selection changes  
            document.addEventListener('aiohm-accommodation-selected', (e) => {
                this.handleAccommodationSelection(e.detail);
            });
            
            // Listen for ticket quantity changes
            document.addEventListener('aiohm-ticket-quantity-changed', (e) => {
                this.handleQuantityChange(e.detail);
            });
            
            // Booking button
            if (this.elements.bookingBtn) {
                this.elements.bookingBtn.addEventListener('click', (e) => {
                    this.handleBookingSubmit(e);
                });
            }
            
            // Note: Sandwich footer checkout button is now handled by the integrated 
            // checkout flow in the sandwich footer template, not here
        }

        /**
         * Handle event selection
         */
        handleEventSelection(eventData) {
            
            if (eventData.selectedEvents && eventData.selectedEvents.length > 0) {
                this.selectedEvents = eventData.selectedEvents;
                // Update early bird days based on first selected event (for backwards compatibility)
                this.earlybirdDays = eventData.selectedEvents[0].earlyBirdDays || this.earlybirdDays;
                // Update deposit percentage based on first selected event (for backwards compatibility)
                this.depositPercent = eventData.selectedEvents[0].depositPercentage || this.depositPercent;
            } else {
                this.selectedEvents = [];
            }
            
            this.updateDisplay();
        }

        /**
         * Handle accommodation selection
         */
        handleAccommodationSelection(accommodationData) {
            
            this.selectedAccommodations = accommodationData.selectedAccommodations || [];
            
            this.updateDisplay();
            
            // Apply special pricing to newly selected accommodations
            if (this.selectedAccommodations.length > 0) {
                const checkinDate = document.getElementById('checkinHidden')?.value;
                const checkoutDate = document.getElementById('checkoutHidden')?.value;
                if (checkinDate && checkoutDate) {
                    this.updateAccommodationSpecialPricing(checkinDate, checkoutDate);
                }
            }
        }

        /**
         * Handle ticket quantity change
         */
        handleQuantityChange(quantityData) {
            this.ticketQuantity = quantityData.quantity || 1;
            this.updateDisplay();
        }

        /**
         * Update the nightly pricing breakdown display
         */
        updateNightlyBreakdown() {
            if (!this.elements.nightlyBreakdown || !this.elements.nightlyBreakdownList) {
                return;
            }
            
            // Check if any accommodation has different nightly rates
            const hasDifferentRates = this.selectedAccommodations.some(acc => acc.hasDifferentNightlyRates);
            
            if (!hasDifferentRates || this.selectedAccommodations.length === 0) {
                this.elements.nightlyBreakdown.style.display = 'none';
                return;
            }
            
            // Clear existing breakdown
            this.elements.nightlyBreakdownList.innerHTML = '';
            
            // Group accommodations by nightly pricing patterns
            const nightlyGroups = {};
            
            this.selectedAccommodations.forEach(accommodation => {
                if (accommodation.nightlyPrices && accommodation.nightlyPrices.length > 0) {
                    const key = JSON.stringify(accommodation.nightlyPrices.map(n => ({ price: n.price, isSpecial: n.isSpecial })));
                    
                    if (!nightlyGroups[key]) {
                        nightlyGroups[key] = {
                            prices: accommodation.nightlyPrices,
                            count: 0,
                            accommodation: accommodation
                        };
                    }
                    
                    nightlyGroups[key].count += 1;
                }
            });
            
            // Display each unique nightly pricing pattern
            Object.values(nightlyGroups).forEach(group => {
                const { prices, count, accommodation } = group;
                
                // Create accommodation header if multiple units
                if (count > 1) {
                    const headerDiv = document.createElement('div');
                    headerDiv.className = 'aiohm-nightly-accommodation-header';
                    headerDiv.textContent = `${accommodation.title} (${count} ${count === 1 ? 'unit' : 'units'})`;
                    this.elements.nightlyBreakdownList.appendChild(headerDiv);
                }
                
                // Create nightly breakdown for this accommodation type
                prices.forEach(night => {
                    const nightDiv = document.createElement('div');
                    nightDiv.className = 'aiohm-nightly-price-row';
                    
                    const date = new Date(night.date);
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        weekday: 'short', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                    
                    nightDiv.innerHTML = `
                        <span class="aiohm-nightly-date">${formattedDate}</span>
                        <span class="aiohm-nightly-price">${this.currency} ${night.price.toFixed(2)}</span>
                        ${night.isSpecial ? '<span class="aiohm-nightly-special-badge">Special</span>' : ''}
                        ${night.isPrivateEvent ? '<span class="aiohm-nightly-private-badge">Private Event</span>' : ''}
                    `;
                    
                    this.elements.nightlyBreakdownList.appendChild(nightDiv);
                });
                
                // Add separator between accommodation types
                if (Object.keys(nightlyGroups).length > 1) {
                    const separator = document.createElement('hr');
                    separator.className = 'aiohm-nightly-separator';
                    this.elements.nightlyBreakdownList.appendChild(separator);
                }
            });
            
            this.elements.nightlyBreakdown.style.display = 'block';
        }

        /**
         * Update the entire display
         */
        updateDisplay() {
            const hasSelections = this.selectedEvents.length > 0 || this.selectedAccommodations.length > 0;
            
            // Toggle card state
            if (hasSelections) {
                this.pricingCard.classList.add('has-selections');
                this.elements.bookingBtn.disabled = false;
            } else {
                this.pricingCard.classList.remove('has-selections');
                this.elements.bookingBtn.disabled = true;
            }
            
            // Update sections
            this.updateSelectedItems();
            this.updatePricingBreakdown();
        }

        /**
         * Update selected items summary
         */
        updateSelectedItems() {
            // Update selected events to match accommodation styling - support multiple events
            if (this.selectedEvents.length > 0 && this.elements.selectedEvents) {
                this.updateEventsList();
                this.elements.selectedEvents.style.display = 'block';
            } else {
                if (this.elements.selectedEvents) {
                    this.elements.selectedEvents.style.display = 'none';
                }
            }
        }

        /**
         * Update events list to match accommodation styling
         */
        updateEventsList() {
            
            // Find the event list container (similar to accommodationsList)
            const eventListContainer = this.elements.selectedEvents.querySelector('.aiohm-booking-event-summary-content') || 
                                       this.elements.selectedEvents.querySelector('.aiohm-events-list') ||
                                       this.elements.selectedEvents;
            
            if (!eventListContainer) {
                return;
            }
            
            // Show individual mini-cards for each selected event
            const listHtml = this.selectedEvents.map((event, index) => {
                const eventIndex = event.index || index;
                
                // Get the actual ticket quantity for this specific event
                const eventQuantityInput = document.querySelector(`input[name="event_tickets[${eventIndex}]"]`);
                const eventQuantity = eventQuantityInput ? parseInt(eventQuantityInput.value) || 0 : (this.ticketQuantity || 1);
                
                const totalPrice = (event.earlyPrice || event.price) * eventQuantity;
                const pricePerTicket = event.earlyPrice || event.price || 0;
                const hasEarlyBird = event.earlyPrice && event.earlyPrice < event.price;
                
                // Get event type badge styling
                const eventType = event.eventType || '';
                const eventTypeDisplay = eventType ? eventType.charAt(0).toUpperCase() + eventType.slice(1) : '';
                
                return `
                    <div class="aiohm-event-summary-item">
                        ${eventTypeDisplay ? `
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Event type:</span>
                            <span class="aiohm-booking-value">
                                <span class="aiohm-event-type-badge">${eventTypeDisplay}</span>
                            </span>
                        </div>
                        ` : ''}
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Event name:</span>
                            <span class="aiohm-booking-value">${event.title || ''}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Event date:</span>
                            <span class="aiohm-booking-value">${event.date || ''}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Tickets nr:</span>
                            <span class="aiohm-booking-value">${eventQuantity}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Price per ticket:</span>
                            <span class="aiohm-booking-value aiohm-price-value">${this.currency} ${pricePerTicket.toFixed(2)}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Total:</span>
                            <span class="aiohm-booking-value aiohm-price-value"><strong>${this.currency} ${totalPrice.toFixed(2)}</strong></span>
                        </div>
                        ${hasEarlyBird ? `
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Early Bird Discount:</span>
                            <span class="aiohm-booking-value">
                                <span class="aiohm-early-bird-badge">Early Bird</span>
                            </span>
                        </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            eventListContainer.innerHTML = listHtml;
            
            // Update selected accommodations
            if (this.selectedAccommodations.length > 0 && this.elements.selectedAccommodations) {
                this.updateAccommodationsList();
                this.elements.selectedAccommodations.style.display = 'block';
            } else {
                if (this.elements.selectedAccommodations) {
                    this.elements.selectedAccommodations.style.display = 'none';
                }
            }
            
            // Update nightly breakdown
            this.updateNightlyBreakdown();
        }

        /**
         * Update accommodations list to match accommodation card styling
         */
        updateAccommodationsList() {
            
            if (!this.elements.accommodationsList) {
                return;
            }
            
            // Show individual mini-cards for each accommodation unit
            const listHtml = this.selectedAccommodations.map((accommodation, index) => {
                
                const totalPrice = accommodation.price * accommodation.nights || accommodation.price;
                const pricePerUnit = accommodation.price;
                const hasSpecialPricing = accommodation.hasSpecialPricing || false;
                const hasEarlyBird = accommodation.isEarlyBird || false;
                
                // Get accommodation type badge styling
                const accommodationType = accommodation.type || 'unit';
                const accommodationTypeDisplay = accommodationType === 'entire_property' ? '' : accommodationType.charAt(0).toUpperCase() + accommodationType.slice(1);
                
                return `
                    <div class="aiohm-accommodation-summary-item">
                        ${accommodationTypeDisplay ? `
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Type:</span>
                            <span class="aiohm-booking-value">
                                <span class="aiohm-accommodation-type-badge" data-type="${accommodationType}">${accommodationTypeDisplay}</span>
                            </span>
                        </div>
                        ` : ''}
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Accommodation:</span>
                            <span class="aiohm-booking-value">${accommodation.name || ''}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Check-in:</span>
                            <span class="aiohm-booking-value" id="accommodationCheckinDisplay">${this.getCheckinDisplay()}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Check-out:</span>
                            <span class="aiohm-booking-value" id="accommodationCheckoutDisplay">${this.getCheckoutDisplay()}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Duration:</span>
                            <span class="aiohm-booking-value">${accommodation.nights || 1} ${accommodation.nights === 1 ? 'night' : 'nights'}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Units:</span>
                            <span class="aiohm-booking-value">1</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Price per night:</span>
                            <span class="aiohm-booking-value aiohm-price-value">${this.currency} ${pricePerUnit.toFixed(2)}</span>
                        </div>
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Total:</span>
                            <span class="aiohm-booking-value aiohm-price-value"><strong>${this.currency} ${totalPrice.toFixed(2)}</strong></span>
                        </div>
                        ${hasSpecialPricing ? `
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">High Season:</span>
                            <span class="aiohm-booking-value">
                                <span class="aiohm-high-season-badge">High Season</span>
                            </span>
                        </div>
                        ` : ''}
                        ${accommodation.isSpecialEvent ? `
                        <div class="aiohm-booking-detail-row">
                            <span class="aiohm-booking-label">Private Events:</span>
                            <span class="aiohm-booking-value">
                                <span class="aiohm-private-events-badge">Private Events</span>
                            </span>
                        </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            this.elements.accommodationsList.innerHTML = listHtml;
            
            // Update check-in/check-out displays in accommodation table
            this.updateAccommodationDateDisplays();
        }

        /**
         * Get formatted check-in display
         */
        getCheckinDisplay() {
            const checkinElement = document.getElementById('pricingCheckinDisplay');
            return checkinElement ? checkinElement.textContent : 'Select date from calendar';
        }

        /**
         * Get formatted check-out display
         */
        getCheckoutDisplay() {
            const checkoutElement = document.getElementById('pricingCheckoutDisplay');
            return checkoutElement ? checkoutElement.textContent : 'Select check-in first';
        }

        /**
         * Update check-in/check-out displays in accommodation table
         */
        updateAccommodationDateDisplays() {
            const checkinDisplay = this.getCheckinDisplay();
            const checkoutDisplay = this.getCheckoutDisplay();
            
            const accommodationCheckinElements = document.querySelectorAll('#accommodationCheckinDisplay');
            const accommodationCheckoutElements = document.querySelectorAll('#accommodationCheckoutDisplay');
            
            accommodationCheckinElements.forEach(el => el.textContent = checkinDisplay);
            accommodationCheckoutElements.forEach(el => el.textContent = checkoutDisplay);
        }

        /**
         * Update pricing breakdown
         */
        updatePricingBreakdown() {
            const totals = this.calculateTotals();
            
            // Update early bird discount
            if (this.elements.discountAmount && totals.discount > 0) {
                this.elements.discountAmount.textContent = `-${this.currency} ${totals.discount.toFixed(2)}`;
                
                // Update early bird days text
                if (this.elements.earlybirdDaysText) {
                    if (this.earlybirdDays > 0) {
                        const daysText = this.earlybirdDays === 1 ? '1 day' : `${this.earlybirdDays} days`;
                        this.elements.earlybirdDaysText.textContent = `(${daysText})`;
                    } else {
                        this.elements.earlybirdDaysText.textContent = '';
                    }
                }
                
                this.elements.earlybirdRow.style.display = 'flex';
            } else if (this.elements.earlybirdRow) {
                this.elements.earlybirdRow.style.display = 'none';
            }
            
            // Update total
            if (this.elements.totalAmount) {
                this.elements.totalAmount.textContent = `${this.currency} ${totals.total.toFixed(2)}`;
            }
            
            // Update deposit and balance
            if (totals.total > 0) {
                const depositAmount = totals.total * (this.depositPercent / 100);
                const balanceAmount = totals.total - depositAmount;
                
                if (this.elements.depositAmount) {
                    this.elements.depositAmount.textContent = `${this.currency} ${depositAmount.toFixed(2)}`;
                    
                    // Update deposit percentage text
                    if (this.elements.depositPercentText) {
                        this.elements.depositPercentText.textContent = `(${this.depositPercent}%)`;
                    }
                    
                    this.elements.depositRow.style.display = 'flex';
                }
                
                if (this.elements.balanceAmount && balanceAmount > 0) {
                    this.elements.balanceAmount.textContent = `${this.currency} ${balanceAmount.toFixed(2)}`;
                    this.elements.balanceRow.style.display = 'flex';
                } else if (this.elements.balanceRow) {
                    this.elements.balanceRow.style.display = 'none';
                }
                
                // Update payment amounts in checkout tab
                this.updateCheckoutPaymentAmounts(totals.total, depositAmount);
            } else {
                if (this.elements.depositRow) {
                    this.elements.depositRow.style.display = 'none';
                }
                if (this.elements.balanceRow) {
                    this.elements.balanceRow.style.display = 'none';
                }
                
                // Update payment amounts in checkout tab (zero amounts)
                this.updateCheckoutPaymentAmounts(0, 0);
            }
        }

        /**
         * Update payment amounts in checkout tab
         */
        updateCheckoutPaymentAmounts(totalAmount, depositAmount) {
            // Update full payment amount
            const fullPaymentElement = document.getElementById('aiohm-full-payment-amount');
            if (fullPaymentElement) {
                fullPaymentElement.textContent = `${this.currency} ${totalAmount.toFixed(2)}`;
            }
            
            // Update deposit payment amount
            const depositPaymentElement = document.getElementById('aiohm-deposit-payment-amount');
            if (depositPaymentElement) {
                depositPaymentElement.textContent = `${this.currency} ${depositAmount.toFixed(2)}`;
            }
        }

        /**
         * Calculate all totals
         */
        calculateTotals() {
            let subtotal = 0;
            let discount = 0;
            
            // Calculate events total - use individual event ticket quantities
            this.selectedEvents.forEach(event => {
                const eventIndex = event.index || 0;
                
                // Get the actual ticket quantity for this specific event
                const eventQuantityInput = document.querySelector(`input[name="event_tickets[${eventIndex}]"]`);
                const eventQuantity = eventQuantityInput ? parseInt(eventQuantityInput.value) || 0 : (this.ticketQuantity || 1);
                
                const eventPrice = event.earlyPrice || event.price || 0;
                const eventTotal = eventPrice * eventQuantity;
                subtotal += eventTotal;
                
                // Calculate early bird discount amount for display
                if (event.earlyPrice && event.earlyPrice < event.price && event.price) {
                    const discountPerTicket = event.price - event.earlyPrice;
                    discount += discountPerTicket * eventQuantity;
                }
            });
            
            // Calculate accommodations total
            this.selectedAccommodations.forEach(accommodation => {
                const accommodationTotal = accommodation.price * (accommodation.nights || 1) * (accommodation.quantity || 1);
                subtotal += accommodationTotal;
                
                // Calculate early bird discount for accommodations
                if (accommodation.earlyPrice && accommodation.earlyPrice < accommodation.regularPrice) {
                    const discountPerNight = accommodation.regularPrice - accommodation.earlyPrice;
                    discount += discountPerNight * (accommodation.nights || 1) * (accommodation.quantity || 1);
                }
            });
            
            // Total is the subtotal (already discounted) - don't subtract discount again
            const total = Math.max(0, subtotal);
            
            return {
                subtotal: subtotal,
                discount: discount,
                total: total
            };
        }

        /**
         * Update accommodation pricing based on selected dates and special events
         */
        updateAccommodationSpecialPricing(checkinDate, checkoutDate) {
            
            // Check if we have cached availability data
            if (!window.AIOHM_Booking_Shortcode || !window.AIOHM_Booking_Shortcode.cachedAvailability) {
                return; // No availability data to check
            }

            const availabilityData = window.AIOHM_Booking_Shortcode.cachedAvailability;
            
            // Update each selected accommodation with special pricing if available
            this.selectedAccommodations.forEach(accommodation => {
                // Skip entire property bookings - they already have correct pricing calculated
                if (accommodation.type === 'entire_property') {
                    return;
                }
                
                let totalSpecialPrice = 0;
                let hasSpecialPricing = false;
                let specialNights = 0;
                let nightlyPrices = []; // Store price for each night
                
                // Check each night between checkin and checkout for special pricing
                let currentDate = new Date(checkinDate);
                const checkout = new Date(checkoutDate);
                
                while (currentDate < checkout) {
                    const dateString = currentDate.toISOString().split('T')[0];
                    const dayAvailability = availabilityData[dateString];
                    
                    let nightPrice = 0;
                    if (dayAvailability && dayAvailability.price > 0) {
                        // This date has special pricing
                        nightPrice = dayAvailability.price;
                        totalSpecialPrice += nightPrice;
                        hasSpecialPricing = true;
                        specialNights++;
                    } else {
                        // Use regular accommodation price for this night
                        nightPrice = accommodation.originalPrice || accommodation.price;
                        totalSpecialPrice += nightPrice;
                    }
                    
                    // Store the price for this night
                    nightlyPrices.push({
                        date: dateString,
                        price: nightPrice,
                        isSpecial: (dayAvailability && dayAvailability.price > 0),
                        isPrivateEvent: (dayAvailability && dayAvailability.is_private_event)
                    });
                    
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                // Store the nightly breakdown
                accommodation.nightlyPrices = nightlyPrices;
                accommodation.hasDifferentNightlyRates = nightlyPrices.some((night, index, arr) => 
                    arr.some(otherNight => otherNight.price !== night.price)
                );
                
                // CRITICAL FIX: If we have special pricing for the entire stay, use ONLY special pricing
                if (specialNights === accommodation.nights && specialNights > 0) {
                    totalSpecialPrice = 0;
                    hasSpecialPricing = true;
                    
                    // Recalculate with only special prices and update nightly prices
                    let recalcDate = new Date(checkinDate);
                    const recalcCheckout = new Date(checkoutDate);
                    let nightIndex = 0;
                    
                    while (recalcDate < recalcCheckout) {
                        const dateString = recalcDate.toISOString().split('T')[0];
                        const dayAvailability = availabilityData[dateString];
                        
                        if (dayAvailability && dayAvailability.price > 0) {
                            totalSpecialPrice += dayAvailability.price;
                            // Update the nightly price to reflect only special pricing
                            if (accommodation.nightlyPrices && accommodation.nightlyPrices[nightIndex]) {
                                accommodation.nightlyPrices[nightIndex].price = dayAvailability.price;
                            }
                        }
                        
                        recalcDate.setDate(recalcDate.getDate() + 1);
                        nightIndex++;
                    }
                }
                
                if (hasSpecialPricing && specialNights > 0) {
                    // Store original price if not already stored
                    if (!accommodation.originalPrice) {
                        accommodation.originalPrice = accommodation.price;
                    }
                    
                    // Calculate average price per night with special pricing included
                    const newPrice = totalSpecialPrice / accommodation.nights;
                    
                    accommodation.price = newPrice;
                    accommodation.specialPrice = newPrice; // Store the special price for display
                    accommodation.hasSpecialPricing = true;
                    // Only mark as special event if it's actually a private event (requires all units)
                    accommodation.isSpecialEvent = this.checkIfPrivateEventInDateRange(accommodation.checkin, accommodation.checkout);
                    accommodation.specialNights = specialNights;
                    
                } else {
                    // Restore original price if no special pricing
                    if (accommodation.originalPrice) {
                        accommodation.price = accommodation.originalPrice;
                        accommodation.specialPrice = undefined; // Clear special price
                        accommodation.hasSpecialPricing = false;
                        // Still check for private events even without special pricing
                        accommodation.isSpecialEvent = this.checkIfPrivateEventInDateRange(accommodation.checkin, accommodation.checkout);
                        accommodation.specialNights = 0;
                    }
                }
            });
            
            // Update the pricing display
            this.updatePricingBreakdown();
            
            // Update the accommodation list display with new pricing
            this.updateAccommodationsList();
            
        }

        /**
         * Handle booking submission
         */
        handleBookingSubmit(e) {
            e.preventDefault();
            
            // Prevent duplicate submissions
            if (this.isSubmitting) {
                return false;
            }
            
            if (!this.isValidForBooking()) {
                return false;
            }
            
            // Set submission flag
            this.isSubmitting = true;
            
            // Check if checkout URL is configured
            if (!this.checkoutUrl || this.checkoutUrl.trim() === '') {
                this.isSubmitting = false; // Reset flag
                alert('Checkout page URL is not configured. Please contact the site administrator.');
                return false;
            }
            
            // Show loading state
            this.setLoading(true);
            
            // Disable both pricing summary button and sandwich footer button
            if (this.elements.bookingBtn) {
                this.elements.bookingBtn.disabled = true;
                const btnTextElement = this.elements.bookingBtn.querySelector('.aiohm-btn-text');
                if (btnTextElement) {
                    btnTextElement.textContent = 'Processing...';
                } else {
                    this.elements.bookingBtn.textContent = 'Processing...';
                }
            }
            
            const sandwichBtn = document.getElementById('aiohm-booking-submit');
            if (sandwichBtn) {
                sandwichBtn.disabled = true;
                sandwichBtn.textContent = 'Processing...';
            }
            
            // Prepare form data - always use buildFormData for consistency
            const formData = this.buildFormData();
            
            // Submit via AJAX using the correct endpoint based on form content
            // Check what type of booking this is by examining the form data
            const hasEvents = formData.includes('selected_event') || formData.includes('selected_events');
            const hasAccommodations = formData.includes('accommodation') || formData.includes('accommodations');
            
            // Determine action based on actual form content, not just context
            let ajaxAction;
            if (hasEvents && !hasAccommodations) {
                // Events only
                ajaxAction = 'aiohm_booking_submit_event';
            } else if (hasAccommodations && !hasEvents) {
                // Accommodations only
                ajaxAction = 'aiohm_booking_submit_accommodation';
            } else if (hasEvents && hasAccommodations) {
                // Mixed booking - use unified handler
                ajaxAction = 'aiohm_booking_submit_unified';
            } else {
                // Fallback to accommodation handler for unknown cases
                ajaxAction = 'aiohm_booking_submit_accommodation';
            }
            
            $.ajax({
                url: window.aiohm_booking?.ajax_url || ajaxurl,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    nonce: window.aiohm_booking?.nonce,
                    form_data: formData
                },
                success: (response) => {
                    this.isSubmitting = false; // Reset flag
                    this.setLoading(false);
                    if (this.elements.bookingBtn) {
                        this.elements.bookingBtn.disabled = false;
                        const btnTextElement = this.elements.bookingBtn.querySelector('.aiohm-btn-text');
                        if (btnTextElement) {
                            btnTextElement.textContent = 'Complete Booking';
                        } else {
                            // Fallback for buttons without .aiohm-btn-text (like sandwich footer)
                            this.elements.bookingBtn.textContent = 'Continue to Checkout';
                        }
                    }
                    
                    // Also reset sandwich footer button if it was clicked
                    const sandwichBtn = document.getElementById('aiohm-booking-submit');
                    if (sandwichBtn) {
                        sandwichBtn.disabled = false;
                        sandwichBtn.textContent = 'Continue to Checkout';
                    }
                    
                    if (response.success && response.data.booking_id) {
                        // Redirect to checkout page with booking ID (same as accommodations)
                        let redirectUrl = this.checkoutUrl;
                        redirectUrl += (this.checkoutUrl.includes('?') ? '&' : '?') + 'booking_id=' + response.data.booking_id;
                        
                        window.location.href = redirectUrl;
                    } else {
                        alert('Error creating booking: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: () => {
                    this.isSubmitting = false; // Reset flag
                    this.setLoading(false);
                    if (this.elements.bookingBtn) {
                        this.elements.bookingBtn.disabled = false;
                        const btnTextElement = this.elements.bookingBtn.querySelector('.aiohm-btn-text');
                        if (btnTextElement) {
                            btnTextElement.textContent = 'Complete Booking';
                        } else {
                            // Fallback for buttons without .aiohm-btn-text (like sandwich footer)
                            this.elements.bookingBtn.textContent = 'Continue to Checkout';
                        }
                    }
                    
                    // Also reset sandwich footer button if it was clicked
                    const sandwichBtn = document.getElementById('aiohm-booking-submit');
                    if (sandwichBtn) {
                        sandwichBtn.disabled = false;
                        sandwichBtn.textContent = 'Continue to Checkout';
                    }
                    
                    alert('Network error. Please try again.');
                }
            });
        }

        /**
         * Build form data for submission (compatible with existing booking handler)
         */
        buildFormData() {
            const formData = new URLSearchParams();
            
            // Add selected events data - support multiple events
            if (this.selectedEvents.length > 0) {
                // Use multiple events format to match the event selection template
                if (this.selectedEvents.length === 1) {
                    // Single event - maintain backward compatibility
                    const event = this.selectedEvents[0];
                    formData.append('selected_event', event.index || 0);
                    formData.append('ticket_quantity', this.ticketQuantity);
                } else {
                    // Multiple events - use array format
                    this.selectedEvents.forEach(event => {
                        formData.append('selected_events[]', event.index || 0);
                    });
                    formData.append('ticket_quantity', this.ticketQuantity);
                }
                
                // Add event details for the first event (for compatibility)
                const firstEvent = this.selectedEvents[0];
                formData.append('booking_type', 'tickets');
                formData.append('event_title', firstEvent.title || '');
                formData.append('event_date', firstEvent.eventDate || firstEvent.date || '');
                formData.append('event_time', firstEvent.eventTime || firstEvent.time || '');
                formData.append('event_price', firstEvent.earlyPrice || firstEvent.price || 0);
                
                // Add individual ticket quantities for each event
                this.selectedEvents.forEach(event => {
                    const eventIndex = event.index || 0;
                    // Check if there's a specific quantity input for this event
                    const eventQuantityInput = document.querySelector(`input[name="event_tickets[${eventIndex}]"]`);
                    const eventQuantity = eventQuantityInput ? parseInt(eventQuantityInput.value) || 0 : this.ticketQuantity;
                    formData.append(`event_tickets[${eventIndex}]`, eventQuantity);
                });
            }
            
            // Add selected accommodations data  
            if (this.selectedAccommodations.length > 0) {
                this.selectedAccommodations.forEach((accommodation, index) => {
                    formData.append('selected_accommodations[]', accommodation.id || index);
                    formData.append('accommodation_nights[' + (accommodation.id || index) + ']', accommodation.nights || 1);
                });
                formData.append('booking_type', 'accommodations');
            }
            
            // Add pricing totals
            const totals = this.calculateTotals();
            formData.append('total_amount', totals.total);
            formData.append('subtotal', totals.subtotal);
            formData.append('discount_amount', totals.discount);
            formData.append('currency', this.currency);
            
            // Add customer information from contact form
            const nameField = document.querySelector('input[data-field="name"]');
            const emailField = document.querySelector('input[data-field="email"]');
            const phoneField = document.querySelector('input[name="phone"]');
            const specialRequestsField = document.querySelector('textarea[name="special_requests"]');
            
            if (nameField) formData.append('name', nameField.value);
            if (emailField) formData.append('email', emailField.value);
            if (phoneField) formData.append('phone', phoneField.value);
            if (specialRequestsField) formData.append('special_requests', specialRequestsField.value);
            
            return formData.toString();
        }

        /**
         * Check if booking is valid
         */
        isValidForBooking() {
            return this.selectedEvents.length > 0 || this.selectedAccommodations.length > 0;
        }

        /**
         * Get current pricing data (public method)
         */
        getPricingData() {
            return {
                selectedEvents: this.selectedEvents,
                selectedAccommodations: this.selectedAccommodations,
                ticketQuantity: this.ticketQuantity,
                totals: this.calculateTotals(),
                currency: this.currency,
                depositPercent: this.depositPercent
            };
        }

        /**
         * Set loading state
         */
        setLoading(loading) {
            if (loading) {
                this.pricingCard.classList.add('is-loading');
            } else {
                this.pricingCard.classList.remove('is-loading');
            }
        }

        /**
         * Clear all selections
         */
        clearSelections() {
            this.selectedEvents = [];
            this.selectedAccommodations = [];
            this.ticketQuantity = 1;
            
            this.updateDisplay();
        }

        /**
         * Override shortcode currency and total updates
         */
        overrideShortcodePricing() {
            // Override the shortcode's formatPrice function to use our currency
            const self = this;
            if (typeof window.formatPrice === 'function') {
                const originalFormatPrice = window.formatPrice;
                window.formatPrice = function(amount, currency) {
                    return `${self.currency} ${amount.toFixed(2)}`;
                };
            } else {
                // Define formatPrice if it doesn't exist yet
                window.formatPrice = function(amount, currency) {
                    return `${self.currency} ${amount.toFixed(2)}`;
                };
            }
            
            // Monitor for shortcode date updates and sync with our pricing summary
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        const target = mutation.target;
                        
                        // Check if shortcode updated check-in display elsewhere and copy to our pricing summary
                        if (target.id === 'checkinDisplay' || target.parentElement?.id === 'checkinDisplay') {
                            const checkinText = document.getElementById('checkinDisplay')?.textContent;
                            const pricingCheckinDisplay = document.getElementById('pricingCheckinDisplay');
                            if (checkinText && pricingCheckinDisplay && checkinText !== 'Select date from calendar') {
                                pricingCheckinDisplay.textContent = checkinText;
                                self.handleDateDisplayUpdate();
                            }
                        }
                        
                        // Check if shortcode updated check-out display elsewhere and copy to our pricing summary
                        if (target.id === 'checkoutDisplay' || target.parentElement?.id === 'checkoutDisplay') {
                            const checkoutText = document.getElementById('checkoutDisplay')?.textContent;
                            const pricingCheckoutDisplay = document.getElementById('pricingCheckoutDisplay');
                            if (checkoutText && pricingCheckoutDisplay && checkoutText !== 'Select check-in first') {
                                pricingCheckoutDisplay.textContent = checkoutText;
                                self.handleDateDisplayUpdate();
                            }
                        }
                    }
                });
            });
            
            // Start observing date display changes
            const checkinDisplay = document.getElementById('checkinDisplay');
            const checkoutDisplay = document.getElementById('checkoutDisplay');
            
            if (checkinDisplay) {
                observer.observe(checkinDisplay, { childList: true, characterData: true, subtree: true });
            }
            if (checkoutDisplay) {
                observer.observe(checkoutDisplay, { childList: true, characterData: true, subtree: true });
            }
            
            // Periodically check and override any .total-amount updates from shortcode
            setInterval(() => {
                const totalAmountElements = document.querySelectorAll('.total-amount');
                totalAmountElements.forEach(el => {
                    if (el !== this.elements.totalAmount && this.elements.totalAmount) {
                        // Copy our value to the shortcode's total element
                        el.textContent = this.elements.totalAmount.textContent;
                    }
                });
                
                // Also fix any hardcoded EUR prices in accommodation list
                const accommodationPrices = document.querySelectorAll('.aiohm-item-price');
                accommodationPrices.forEach(priceEl => {
                    const text = priceEl.textContent;
                    if (text.includes('EUR ') && !text.includes(self.currency)) {
                        // Extract the number and replace with our currency
                        const amount = text.replace(/[^\d.]/g, '');
                        if (amount) {
                            priceEl.textContent = `${self.currency} ${amount}`;
                        }
                    }
                });
                
                // Fix any RON currency in total amount
                const totalAmountElement = document.querySelector('.aiohm-total-amount');
                if (totalAmountElement && totalAmountElement.textContent.includes('RON')) {
                    const currentText = totalAmountElement.textContent;
                    const updatedText = currentText.replace('RON', self.currency);
                    totalAmountElement.textContent = updatedText;
                }
            }, 500);
        }

        /**
         * Handle date display updates from shortcode
         */
        handleDateDisplayUpdate() {
            // Get the current selected accommodations and refresh them with updated dates
            const selectedCheckboxes = document.querySelectorAll('.accommodation-checkbox:checked');
            if (selectedCheckboxes.length > 0) {
                // Trigger the first selected accommodation to refresh the pricing summary
                selectedCheckboxes[0].dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        /**
         * Force update pricing summary for testing
         */
        forceUpdate() {
            
            // Create fake accommodation data if checkboxes are selected
            const selectedCheckboxes = document.querySelectorAll('.accommodation-checkbox:checked');
            if (selectedCheckboxes.length > 0) {
                const fakeAccommodations = [];
                selectedCheckboxes.forEach((checkbox, index) => {
                    const price = parseFloat(checkbox.dataset.price) || 1900;
                    const name = checkbox.closest('.aiohm-accommodation-card, .aiohm-booking-event-card')?.querySelector('.aiohm-accommodation-title, .aiohm-booking-event-title')?.textContent || `Unit ${index + 1}`;
                    
                    fakeAccommodations.push({
                        id: index,
                        name: name,
                        price: price,
                        nights: 1,
                        type: 'unit',
                        dateRange: 'Test dates'
                    });
                });
                
                this.selectedAccommodations = fakeAccommodations;
                this.updateDisplay();
            }
        }

        /**
         * Sync date displays between different elements
         */
        syncDateDisplays() {
            // Check for any date displays updated by shortcode and sync them
            const checkinDisplay = document.getElementById('checkinDisplay');
            const checkoutDisplay = document.getElementById('checkoutDisplay');
            const pricingCheckinDisplay = document.getElementById('pricingCheckinDisplay');
            const pricingCheckoutDisplay = document.getElementById('pricingCheckoutDisplay');
            
            // Sync check-in display
            if (checkinDisplay && pricingCheckinDisplay) {
                const checkinText = checkinDisplay.textContent.trim();
                if (checkinText && checkinText !== 'Select date from calendar') {
                    pricingCheckinDisplay.textContent = checkinText;
                }
            }
            
            // Sync check-out display
            if (checkoutDisplay && pricingCheckoutDisplay) {
                const checkoutText = checkoutDisplay.textContent.trim();
                if (checkoutText && checkoutText !== 'Select check-in first') {
                    pricingCheckoutDisplay.textContent = checkoutText;
                }
            }
            
            // Trigger accommodation update to refresh pricing with new dates
            this.handleDateDisplayUpdate();
        }
    }

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Delay initialization to let shortcode system load first
        setTimeout(function() {
            // Initialize pricing summary
            const pricingSummary = new AIOHMPricingSummary();
            
            // Make it globally accessible for other components
            window.AIOHMPricingSummary = pricingSummary;
            window.AIOHMBookingPricingSummary = pricingSummary; // Alternative name for compatibility
            
            // Add test function to window for debugging
            window.testPricingSummary = function() {
                pricingSummary.forceUpdate();
            };
            
            // Override shortcode currency if our pricing summary is present
            if (window.aiohm_booking_data && window.aiohm_booking_data.pricing) {
                // Force our currency setting to take priority
                const pricingContainer = document.querySelector('.aiohm-pricing-container');
                if (pricingContainer) {
                    const ourCurrency = pricingContainer.dataset.currency;
                    if (ourCurrency) {
                        window.aiohm_booking_data.pricing.currency = ourCurrency;
                    }
                }
            }
            
            // Start overriding shortcode pricing updates
            pricingSummary.overrideShortcodePricing();
            
            // Hook into shortcode date selection if shortcode is available
            if (window.AIOHM_Booking_Shortcode && typeof window.AIOHM_Booking_Shortcode.handleDateSelection === 'function') {
                const originalHandleDateSelection = window.AIOHM_Booking_Shortcode.handleDateSelection;
                window.AIOHM_Booking_Shortcode.handleDateSelection = function($dayElement, $calendar) {
                    // Call original function
                    const result = originalHandleDateSelection.call(this, $dayElement, $calendar);
                    
                    // After shortcode handles date selection, update our pricing summary
                    setTimeout(() => {
                        pricingSummary.syncDateDisplays();
                    }, 100);
                    
                    return result;
                };
            }
            
            // Periodic sync of date displays as backup - DISABLED to prevent infinite loops
            // setInterval(() => {
            //     pricingSummary.syncDateDisplays();
            // }, 1000);
        }, 200); // Wait for shortcode to initialize first
        
        // Fallback handler for sandwich footer checkout button (only if not already handled)
        // This ensures the button works even if pricing summary isn't fully initialized
        const sandwichCheckoutBtn = document.getElementById('aiohm-booking-submit');
        if (sandwichCheckoutBtn && !sandwichCheckoutBtn.hasAttribute('data-aiohm-listener-attached')) {
            sandwichCheckoutBtn.addEventListener('click', function(e) {
                // Always use the pricing summary instance if available
                if (window.AIOHMPricingSummary && typeof window.AIOHMPricingSummary.handleBookingSubmit === 'function') {
                    window.AIOHMPricingSummary.handleBookingSubmit(e);
                } else {
                    // Fallback: show message that booking data is required
                    e.preventDefault();
                    alert('Please select an event or accommodation before proceeding to checkout.');
                }
            });
        }
    });

    /**
     * Handle dynamic content loading using modern MutationObserver
     */
    if (typeof window.AIOHMPricingObserver === 'undefined') {
        window.AIOHMPricingObserver = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1 && (node.classList.contains('aiohm-pricing-summary-card') || node.querySelector('.aiohm-pricing-summary-card'))) {
                            shouldReinit = true;
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                setTimeout(() => {
                    new AIOHMPricingSummary();
                }, 100);
            }
        });
        
        // Start observing
        window.AIOHMPricingObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})(jQuery);