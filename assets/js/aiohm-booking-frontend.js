/**
 * AIOHM Booking Frontend JavaScript
 * 
 * Handles frontend booking form interactions, calendar functionality,
 * and AJAX communication with the backend.
 * 
 * @package AIOHM_Booking_PRO
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * AIOHM Booking Frontend App
     */
    const AIOHMBookingFrontend = {
        
        /**
         * Helper function to get Monday-based day of week (0 = Monday, 6 = Sunday)
         */
        getMondayBasedDay: function(date) {
            const day = date.getDay();
            return day === 0 ? 6 : day - 1;
        },

        /**
         * Initialize the frontend app
         */
        init: function() {
            // Frontend-specific initialization
            this.initBookingForms();
            this.initCalendar();
            this.initCheckout();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Frontend-specific event handlers (base events are handled automatically)
            // Form submission handlers - use namespace to avoid conflicts
            $(document).on('submit.aiohm-frontend', '.aiohm-booking-form', this.handleBookingSubmit.bind(this));
            $(document).on('submit.aiohm-frontend', '.aiohm-checkout-form', this.handleCheckoutSubmit.bind(this));
            
            // Date selection handlers
            $(document).on('change.aiohm-frontend', '.aiohm-date-input', this.handleDateChange.bind(this));
            
            // Calendar date clicks
            $(document).on('click.aiohm-frontend', '.aiohm-calendar-date', this.handleCalendarDateClick.bind(this));
            
            // Quantity button handlers
            $(document).on('click.aiohm-frontend', '.qty-btn', this.handleQuantityButtonClick.bind(this));
            
            // Quantity and pricing handlers
            $(document).on('change.aiohm-frontend', '.aiohm-quantity-input', this.updatePricing.bind(this));
            $(document).on('change.aiohm-frontend', '.aiohm-accommodation-select', this.updatePricing.bind(this));
            $(document).on('change.aiohm-frontend', '.aiohm-ticket-select', this.updatePricing.bind(this));
            
            // Accommodation checkbox handlers for sandwich template
            $(document).on('change.aiohm-frontend', '.accommodation-checkbox', this.handleAccommodationSelection.bind(this));
            
            // Hidden date input change handlers to update pricing summary
            $(document).on('change.aiohm-frontend', '#checkinHidden, #checkoutHidden', this.handleDateSelectionChange.bind(this));
            
            // Only bind qty-input change if shortcode system isn't handling it
            if (typeof AIOHM_Booking_Shortcode === 'undefined') {
                $(document).on('change.aiohm-frontend', '.qty-input', this.updatePricing.bind(this));
            }
            
            // Form validation
            $(document).on('blur.aiohm-frontend', '.aiohm-required-field', this.validateField.bind(this));
        },
        
        /**
         * Initialize booking forms
         */
        initBookingForms: function() {
            $('.aiohm-booking-form').each(function() {
                const $form = $(this);
                
                // Set default values
                AIOHMBookingFrontend.setDefaultDates($form);
                AIOHMBookingFrontend.updatePricing($form);
                
                // Check for pre-selected accommodation checkboxes and dispatch event
                const $checkedAccommodations = $form.find('.accommodation-checkbox:checked');
                if ($checkedAccommodations.length > 0) {
                    // Trigger the accommodation selection handler for pre-selected items
                    AIOHMBookingFrontend.handleAccommodationSelection({ target: $checkedAccommodations.first()[0] });
                }
                
                // Initialize form animations
                $form.find('.form-input').on('focus', function() {
                    $(this).closest('.form-group').addClass('focused');
                }).on('blur', function() {
                    $(this).closest('.form-group').removeClass('focused');
                });
            });
        },
        
        /**
         * Initialize calendar functionality
         */
        initCalendar: function() {
            // Only initialize if shortcode calendar system isn't handling it
            if (typeof AIOHM_Booking_Shortcode === 'undefined') {
                $('.aiohm-booking-calendar').each(function() {
                    const $calendar = $(this);
                    const eventId = $calendar.data('event-id') || '';

                    // Load calendar data
                    AIOHMBookingFrontend.loadCalendarData($calendar, eventId);
                });
            }

            // Initialize accommodation calendar
            this.initAccommodationCalendar();
        },

        /**
         * Initialize accommodation calendar functionality
         */
        initAccommodationCalendar: function() {
            // Initialize calendar for accommodation selection
            $('.booking-calendar-container').each(function() {
                const $container = $(this);
                AIOHMBookingFrontend.setupAccommodationCalendar($container);
            });

            // Bind accommodation calendar events
            this.bindAccommodationCalendarEvents();
        },

        /**
         * Setup accommodation calendar
         */
        setupAccommodationCalendar: function($container) {
            const currentDate = new Date();
            let currentMonth = currentDate.getMonth();
            let currentYear = currentDate.getFullYear();

            // Generate initial calendar
            this.generateCalendar($container, currentMonth, currentYear);

            // Store current month/year in container
            $container.data('currentMonth', currentMonth);
            $container.data('currentYear', currentYear);
        },

        /**
         * Generate calendar grid
         */
        generateCalendar: function($container, month, year) {
            const $grid = $container.find('.aiohm-calendar-grid');
            const $monthYear = $container.find('.aiohm-calendar-month-year');

            // Update month/year display
            const monthNames = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            $monthYear.text(`${monthNames[month]} ${year}`);

            // Clear existing calendar
            $grid.find('.aiohm-calendar-date').remove();

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - this.getMondayBasedDay(firstDay));

            // Generate calendar cells
            for (let i = 0; i < 42; i++) {
                const cellDate = new Date(startDate);
                cellDate.setDate(startDate.getDate() + i);

                const $cell = $('<div></div>')
                    .addClass('aiohm-calendar-date')
                    .text(cellDate.getDate());

                // Add classes based on date status
                if (cellDate.getMonth() !== month) {
                    $cell.addClass('empty');
                } else if (cellDate < new Date().setHours(0, 0, 0, 0)) {
                    $cell.addClass('disabled');
                    // Don't add data-date attribute to disabled dates to prevent selection
                } else {
                    $cell.addClass('available');
                    $cell.attr('data-date', cellDate.toISOString().split('T')[0]);
                }

                $grid.append($cell);
            }

            // Update navigation buttons
            const $prevBtn = $container.find('.aiohm-prev-month');
            const $nextBtn = $container.find('.aiohm-next-month');

            const currentDate = new Date();
            const isCurrentMonth = month === currentDate.getMonth() && year === currentDate.getFullYear();
            $prevBtn.prop('disabled', isCurrentMonth);
        },

        /**
         * Bind accommodation calendar events
         */
        bindAccommodationCalendarEvents: function() {
            const self = this;

            // Month navigation
            $(document).on('click', '.aiohm-prev-month', function(e) {
                e.preventDefault();
                const $container = $(this).closest('.booking-calendar-container');
                let month = $container.data('currentMonth');
                let year = $container.data('currentYear');

                month--;
                if (month < 0) {
                    month = 11;
                    year--;
                }

                $container.data('currentMonth', month);
                $container.data('currentYear', year);
                self.generateCalendar($container, month, year);
            });

            $(document).on('click', '.aiohm-next-month', function(e) {
                e.preventDefault();
                const $container = $(this).closest('.booking-calendar-container');
                let month = $container.data('currentMonth');
                let year = $container.data('currentYear');

                month++;
                if (month > 11) {
                    month = 0;
                    year++;
                }

                $container.data('currentMonth', month);
                $container.data('currentYear', year);
                self.generateCalendar($container, month, year);
            });

            // Date selection
            $(document).on('click', '.aiohm-calendar-date.available', function() {
                const $cell = $(this);
                const date = $cell.attr('data-date');

                // Prevent selection of disabled or past dates
                if (!date || $cell.hasClass('disabled') || $cell.hasClass('empty')) {
                    return;
                }
                
                // Double-check that the date is not in the past
                const selectedDate = new Date(date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    return;
                }

                // Handle check-in/check-out selection
                const $container = $cell.closest('.booking-calendar-container');
                const $checkinHidden = $container.closest('.aiohm-accommodation-selection').find('#checkinHidden');
                const $checkoutHidden = $container.closest('.aiohm-accommodation-selection').find('#checkoutHidden');
                const $checkinDisplay = $container.closest('.aiohm-accommodation-selection').find('#checkinDisplay, #pricingCheckinDisplay').first();
                const $checkoutDisplay = $container.closest('.aiohm-accommodation-selection').find('#checkoutDisplay, #pricingCheckoutDisplay').first();

                let checkinDate = $checkinHidden.val();
                let checkoutDate = $checkoutHidden.val();

                if (!checkinDate || (checkinDate && checkoutDate)) {
                    // Select check-in date
                    checkinDate = date;
                    checkoutDate = '';
                    $checkinHidden.val(checkinDate);
                    $checkoutHidden.val('');
                    $checkinDisplay.text(new Date(checkinDate).toLocaleDateString());
                    $checkoutDisplay.text('Select check-out date');

                    // Update calendar UI
                    $container.find('.aiohm-calendar-date').removeClass('checkin checkout selected');
                    $cell.addClass('checkin selected');
                } else {
                    // Select check-out date
                    if (date > checkinDate) {
                        checkoutDate = date;
                        $checkoutHidden.val(checkoutDate);
                        $checkoutDisplay.text(new Date(checkoutDate).toLocaleDateString());

                        // Update calendar UI
                        $container.find('.aiohm-calendar-date').removeClass('checkout selected');
                        $cell.addClass('checkout selected');

                        // Calculate and update nights
                        const nights = Math.ceil((new Date(checkoutDate) - new Date(checkinDate)) / (1000 * 60 * 60 * 24));
                        $container.closest('.aiohm-accommodation-selection').find('#stay_duration').val(nights);
                        
                        // Update any selected accommodations with date information
                        self.updateAccommodationDates(checkinDate, checkoutDate, nights);
                    }
                }
            });

            // Quantity button handlers
            $(document).on('click', '.aiohm-qty-minus', function(e) {
                e.preventDefault();
                const $input = $(this).siblings('.aiohm-qty-input');
                const target = $(this).data('target');
                const $targetInput = target ? $('#' + target) : $input;
                const currentVal = parseInt($targetInput.val());
                if (currentVal > parseInt($targetInput.attr('min') || 1)) {
                    $targetInput.val(currentVal - 1).trigger('change');
                }
            });

            $(document).on('click', '.aiohm-qty-plus', function(e) {
                e.preventDefault();
                const $input = $(this).siblings('.aiohm-qty-input');
                const target = $(this).data('target');
                const $targetInput = target ? $('#' + target) : $input;
                const currentVal = parseInt($targetInput.val());
                const maxVal = parseInt($targetInput.attr('max') || 99);
                if (currentVal < maxVal) {
                    $targetInput.val(currentVal + 1).trigger('change');
                }
            });
        },

        /**
         * Update accommodation selections with date information
         */
        updateAccommodationDates: function(checkinDate, checkoutDate, nights) {
            // Find any selected accommodations and re-trigger the selection event with updated data
            const $selectedCheckboxes = $('.accommodation-checkbox:checked');
            if ($selectedCheckboxes.length > 0) {
                // Use the first checkbox to trigger the update
                $selectedCheckboxes.first().trigger('change');
            }
        },

        /**
         * Handle hidden date input changes from calendar selection
         */
        handleDateSelectionChange: function(e) {
            const $input = $(e.target);
            const $form = $input.closest('form');
            
            // Get current values
            const checkinDate = $form.find('#checkinHidden').val() || '';
            const checkoutDate = $form.find('#checkoutHidden').val() || '';
            
            // Update display elements
            const $checkinDisplay = $form.find('#checkinDisplay, #pricingCheckinDisplay');
            const $checkoutDisplay = $form.find('#checkoutDisplay, #pricingCheckoutDisplay');
            
            if (checkinDate) {
                const checkinFormatted = new Date(checkinDate).toLocaleDateString();
                $checkinDisplay.text(checkinFormatted);
            }
            
            if (checkoutDate) {
                const checkoutFormatted = new Date(checkoutDate).toLocaleDateString();
                $checkoutDisplay.text(checkoutFormatted);
            } else if (checkinDate) {
                $checkoutDisplay.text('Select check-out date');
            }
            
            // Update stay duration if both dates are selected
            if (checkinDate && checkoutDate) {
                const nights = Math.ceil((new Date(checkoutDate) - new Date(checkinDate)) / (1000 * 60 * 60 * 24));
                $form.find('#stay_duration').val(nights);
            }
            
            // Trigger accommodation selection update to refresh pricing summary with new dates
            const $selectedCheckboxes = $form.find('.accommodation-checkbox:checked');
            if ($selectedCheckboxes.length > 0) {
                $selectedCheckboxes.first().trigger('change');
            }
        },

        /**
         * Initialize checkout functionality
         */
        initCheckout: function() {
            $('.aiohm-checkout-form').each(function() {
                const $form = $(this);
                
                // Load order data if order ID is present
                const orderId = $form.find('[name="order_id"]').val();
                if (orderId) {
                    AIOHMBookingFrontend.loadOrderData($form, orderId);
                }
            });
        },
        
        /**
         * Handle booking form submission
         */
        handleBookingSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('.booking-btn, .submit-button');
            
            // Prevent double submission
            if ($form.data('submitting') === true) {
                return false;
            }
            
            // Validate form
            if (!this.validateBookingForm($form)) {
                return false;
            }
            
            // Mark form as submitting
            $form.data('submitting', true);
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            var $btnText = $submitBtn.find('.btn-text');
            if ($btnText.length) {
                $btnText.data('original-text', $btnText.text()).text('Processing...');
            } else {
                $submitBtn.text('Processing...');
            }
            
            // Prepare form data - PHP handler expects serialized form_data parameter
            var formData = $form.serialize();
            
            // Determine the appropriate AJAX action based on form content
            var hasAccommodations = $form.find('input[name="accommodations[]"]:checked, input[name="accommodation_id"]:checked').length > 0;
            var hasEvents = $form.find('input[name="selected_event"]:checked, input[name="selected_events[]"]:checked').length > 0;
            
            var ajaxAction;
            if (hasAccommodations && hasEvents) {
                ajaxAction = 'aiohm_booking_submit_unified';
            } else if (hasAccommodations) {
                ajaxAction = 'aiohm_booking_submit_accommodation';
            } else if (hasEvents) {
                ajaxAction = 'aiohm_booking_submit_event';
            } else {
                ajaxAction = 'aiohm_booking_submit_accommodation'; // Default fallback
            }
            
            var ajaxData = {
                action: ajaxAction,
                nonce: (typeof aiohm_booking_frontend !== 'undefined' ? aiohm_booking_frontend.nonce : aiohm_booking.nonce),
                form_data: formData
            };
            
            
            // Submit via AJAX
            $.ajax({
                url: (typeof aiohm_booking_frontend !== 'undefined' ? aiohm_booking_frontend.ajax_url : aiohm_booking.ajax_url),
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        // Show integrated checkout instead of redirecting
                        if (response.data.booking_id) {
                            // Show success message
                            AIOHMBookingFrontend.showMessage($form.closest('.aiohm-booking-shortcode-wrapper').length ? $form.closest('.aiohm-booking-shortcode-wrapper') : $form.parent(), 'success', 'Booking submitted successfully!');
                            
                            // Load and display integrated checkout
                            AIOHMBookingFrontend.showIntegratedCheckout($form.closest('.aiohm-booking-shortcode-wrapper').length ? $form.closest('.aiohm-booking-shortcode-wrapper') : $form.parent(), response.data.booking_id);
                        } else {
                            // Fallback: show success message
                            AIOHMBookingFrontend.showMessage($form.closest('.aiohm-booking-shortcode-wrapper').length ? $form.closest('.aiohm-booking-shortcode-wrapper') : $form.parent(), 'success', response.data.message);
                        }
                    } else {
                        AIOHMBookingFrontend.showMessage($form.closest('.aiohm-booking-shortcode-wrapper').length ? $form.closest('.aiohm-booking-shortcode-wrapper') : $form.parent(), 'error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    AIOHMBookingFrontend.showMessage($form.closest('.aiohm-booking-shortcode-wrapper').length ? $form.closest('.aiohm-booking-shortcode-wrapper') : $form.parent(), 'error', 'An error occurred. Please try again.');
                },
                complete: function() {
                    // Reset submitting flag
                    $form.data('submitting', false);
                    
                    $submitBtn.prop('disabled', false);
                    var $btnText = $submitBtn.find('.btn-text');
                    if ($btnText.length && $btnText.data('original-text')) {
                        $btnText.text($btnText.data('original-text'));
                    } else {
                        $submitBtn.text('Continue to Booking');
                    }
                }
            });
        },
        
        /**
         * Handle checkout form submission
         */
        handleCheckoutSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const $submitBtn = $form.find('.submit-button');
            
            // Validate checkout form
            if (!this.validateCheckoutForm($form)) {
                return false;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true).text('Processing Payment...');
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'aiohm_process_checkout');
            formData.append('nonce', aiohm_booking.nonce);
            
            // Submit via AJAX
            $.ajax({
                url: aiohm_booking.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Redirect to confirmation or payment
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            AIOHMBookingFrontend.showMessage($form, 'success', response.data.message);
                        }
                    } else {
                        AIOHMBookingFrontend.showMessage($form, 'error', response.data.message);
                    }
                },
                error: function() {
                    AIOHMBookingFrontend.showMessage($form, 'error', 'Payment processing failed. Please try again.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Complete Booking');
                }
            });
        },
        
        /**
         * Handle date input changes
         */
        handleDateChange: function(e) {
            const $input = $(e.target);
            const $form = $input.closest('form');
            
            // Update calendar if present
            const $calendar = $form.find('.aiohm-booking-calendar');
            if ($calendar.length) {
                this.highlightCalendarDate($calendar, $input.val());
            }
            
            // Update pricing
            this.updatePricing($form);
        },
        
        /**
         * Handle calendar date clicks
         */
        handleCalendarDateClick: function(e) {
            e.preventDefault();
            
            const $date = $(e.target);
            const $calendar = $date.closest('.aiohm-booking-calendar');
            const $form = $date.closest('form');
            const selectedDate = $date.data('date');
            
            if (!selectedDate || $date.hasClass('disabled') || $date.hasClass('empty')) {
                return;
            }
            
            // Double-check that the date is not in the past
            const dateObj = new Date(selectedDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (dateObj < today) {
                return;
            }
            
            // Check for private event restrictions
            if ($date.hasClass('aiohm-private-event-date')) {
                // Check if user has selected individual accommodations (not book all)
                const selectedIndividualAccommodations = $form.find('.accommodation-checkbox:checked').not('#private_all_checkbox').length;
                const isBookAllSelected = $form.find('#private_all_checkbox').is(':checked');
                
                if (selectedIndividualAccommodations > 0 && !isBookAllSelected) {
                    alert('This date has a private event. You can only book the entire property on private event dates. Please select "Book Entire Property" option or choose a different date.');
                    return;
                }
            }
            
            // Update date inputs
            $form.find('.aiohm-date-input').val(selectedDate);
            
            // Update calendar UI
            $calendar.find('.aiohm-calendar-date').removeClass('selected');
            $date.addClass('selected');
            
            // Update pricing
            this.updatePricing($form);
        },
        
        /**
         * Handle quantity button clicks (+/- buttons)
         */
        handleQuantityButtonClick: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const target = $btn.data('target');
            const $input = $('#' + target);
            
            if (!$input.length) {
                return;
            }
            
            const currentVal = parseInt($input.val()) || 1;
            const min = parseInt($input.attr('min')) || 1;
            const max = parseInt($input.attr('max')) || 99;
            
            let newVal = currentVal;
            
            if ($btn.hasClass('qty-plus')) {
                newVal = Math.min(currentVal + 1, max);
            } else if ($btn.hasClass('qty-minus')) {
                newVal = Math.max(currentVal - 1, min);
            }
            
            if (newVal !== currentVal) {
                $input.val(newVal).trigger('change');
                
                // Update checkout dates if this is duration field
                if (target === 'stay_duration') {
                    this.updateCheckoutDate($input.closest('form'));
                }
                
                // Update pricing
                this.updatePricing($input.closest('form'));
            }
        },
        
        /**
         * Update pricing calculations
         */
        updatePricing: function(target) {
            const $form = $(target).closest ? $(target).closest('form') : $(target);
            const $pricingDisplay = $form.find('.aiohm-pricing-display');
            
            if (!$pricingDisplay.length) {
                return;
            }
            
            // Collect pricing data
            const pricingData = this.collectPricingData($form);
            
            // Calculate totals
            const totals = this.calculateTotals(pricingData);
            
            // Update display
            this.updatePricingDisplay($pricingDisplay, totals);
        },

        /**
         * Handle accommodation checkbox selection for sandwich template
         */
        handleAccommodationSelection: function(e) {
            console.log('frontend.js handleAccommodationSelection called for:', e.target);
            const $checkbox = $(e.target);
            const $form = $checkbox.closest('form');
            console.log('Found form:', $form.length > 0 ? 'yes' : 'no');
            
            // Get current date selection
            const checkinDate = $form.find('#checkinHidden').val() || '';
            const checkoutDate = $form.find('#checkoutHidden').val() || '';
            const nights = parseInt($form.find('#stay_duration').val()) || 1;
            
            // Format date range display
            let dateRange = 'Check-in: Select date from calendar | Check-out: Select check-in first';
            if (checkinDate && checkoutDate) {
                const checkinFormatted = new Date(checkinDate).toLocaleDateString();
                const checkoutFormatted = new Date(checkoutDate).toLocaleDateString();
                dateRange = `${checkinFormatted} - ${checkoutFormatted}`;
            }
            
            // Collect selected accommodations data
            const selectedAccommodations = [];
            const $checkedBoxes = $form.find('.accommodation-checkbox:checked');
            console.log('Found', $checkedBoxes.length, 'checked accommodation checkboxes');
            
            $checkedBoxes.each(function() {
                const $cb = $(this);
                const accommodationId = $cb.val();
                const $accommodationCard = $cb.closest('.aiohm-accommodation-card');
                
                // Try to get data from checkbox data attributes first, then fall back to card elements
                let name = $accommodationCard.find('.aiohm-accommodation-title, .aiohm-booking-event-title').text().trim() || 'Accommodation';
                let price = parseFloat($cb.data('price')) || 0;
                
                // If no price from data attribute, try to extract from card
                if (price === 0) {
                    const priceText = $accommodationCard.find('.aiohm-price-amount').text().trim() || '0';
                    price = parseFloat(priceText.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
                }
                
                console.log('Collected accommodation:', name, 'ID:', accommodationId);
                selectedAccommodations.push({
                    id: accommodationId,
                    name: name,
                    price: price,
                    type: 'unit',
                    nights: nights,
                    dateRange: dateRange
                });
            });
            
            console.log('Dispatching event with', selectedAccommodations.length, 'accommodations:', selectedAccommodations);
            // Dispatch event for pricing summary to update
            const event = new CustomEvent('aiohm-accommodation-selected', {
                detail: {
                    selectedAccommodations: selectedAccommodations
                }
            });
            document.dispatchEvent(event);
            
            // Also update the basic pricing display
            this.updatePricing($form);
        },
        
        /**
         * Collect pricing data from form
         */
        collectPricingData: function($form) {
            const data = {
                accommodations: [],
                tickets: [],
                dates: {},
                quantities: {}
            };
            
            // Collect accommodation data
            $form.find('.aiohm-accommodation-select').each(function() {
                const $select = $(this);
                if ($select.val()) {
                    data.accommodations.push({
                        id: $select.val(),
                        price: parseFloat($select.find('option:selected').data('price') || 0),
                        quantity: parseInt($form.find('.aiohm-accommodation-quantity').val() || 1)
                    });
                }
            });
            
            // Collect ticket data
            $form.find('.aiohm-ticket-select').each(function() {
                const $select = $(this);
                if ($select.val()) {
                    data.tickets.push({
                        id: $select.val(),
                        price: parseFloat($select.find('option:selected').data('price') || 0),
                        quantity: parseInt($select.find('.aiohm-ticket-quantity').val() || 1)
                    });
                }
            });
            
            // Collect dates
            data.dates.checkin = $form.find('[name="checkin_date"]').val();
            data.dates.checkout = $form.find('[name="checkout_date"]').val();
            data.dates.event_date = $form.find('[name="event_date"]').val();
            
            return data;
        },
        
        /**
         * Calculate pricing totals
         */
        calculateTotals: function(data) {
            let subtotal = 0;
            let tax = 0;
            let fees = 0;
            
            // Calculate accommodation costs
            data.accommodations.forEach(function(item) {
                subtotal += item.price * item.quantity;
            });
            
            // Calculate ticket costs
            data.tickets.forEach(function(item) {
                subtotal += item.price * item.quantity;
            });
            
            // Calculate tax (if enabled in settings)
            // This would typically come from settings
            const taxRate = 0; // Tax calculation to be implemented
            tax = subtotal * (taxRate / 100);
            
            // Calculate fees
            // This would typically come from settings
            fees = 0; // Fee calculation to be implemented
            
            return {
                subtotal: subtotal,
                tax: tax,
                fees: fees,
                total: subtotal + tax + fees
            };
        },
        
        /**
         * Update pricing display
         */
        updatePricingDisplay: function($display, totals) {
            $display.find('.subtotal-amount').text('$' + totals.subtotal.toFixed(2));
            $display.find('.tax-amount').text('$' + totals.tax.toFixed(2));
            $display.find('.fees-amount').text('$' + totals.fees.toFixed(2));
            $display.find('.total-amount').text('$' + totals.total.toFixed(2));
        },
        
        /**
         * Validate booking form
         */
        validateBookingForm: function($form) {
            let isValid = true;
            
            // Clear previous errors
            $form.find('.field-error').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    AIOHMBookingFrontend.showFieldError($field, 'This field is required.');
                    isValid = false;
                }
            });
            
            // Validate email format
            $form.find('[type="email"]').each(function() {
                const $field = $(this);
                const email = $field.val().trim();
                
                if (email && !AIOHMBookingFrontend.isValidEmail(email)) {
                    AIOHMBookingFrontend.showFieldError($field, 'Please enter a valid email address.');
                    isValid = false;
                }
            });
            
            // Validate date ranges
            const checkinDate = $form.find('[name="checkin_date"]').val();
            const checkoutDate = $form.find('[name="checkout_date"]').val();
            
            if (checkinDate && checkoutDate && checkinDate >= checkoutDate) {
                const $checkoutField = $form.find('[name="checkout_date"]');
                AIOHMBookingFrontend.showFieldError($checkoutField, 'Checkout date must be after check-in date.');
                isValid = false;
            }
            
            return isValid;
        },
        
        /**
         * Validate checkout form with comprehensive validation
         */
        validateCheckoutForm: function($form) {
            let isValid = true;
            let errors = [];

            // Clear previous errors
            $form.find('.field-error').remove();
            $form.find('.error').removeClass('error');

            // Validate required fields
            $form.find('.aiohm-required-field').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                const fieldName = $field.attr('name') || $field.attr('id');

                if (!value) {
                    AIOHMBookingFrontend.showFieldError($field, 'This field is required.');
                    errors.push(fieldName + ' is required');
                    isValid = false;
                }
            });

            // Validate email format
            $form.find('[type="email"]').each(function() {
                const $field = $(this);
                const email = $field.val().trim();

                if (email && !AIOHMBookingFrontend.isValidEmail(email)) {
                    AIOHMBookingFrontend.showFieldError($field, 'Please enter a valid email address.');
                    errors.push('Invalid email address');
                    isValid = false;
                }
            });

            // Validate phone format
            $form.find('[type="tel"]').each(function() {
                const $field = $(this);
                const phone = $field.val().trim();

                if (phone && !AIOHMBookingFrontend.isValidPhone(phone)) {
                    AIOHMBookingFrontend.showFieldError($field, 'Please enter a valid phone number.');
                    errors.push('Invalid phone number');
                    isValid = false;
                }
            });

            // Validate date ranges
            const checkinDate = $form.find('[name="checkin_date"]').val();
            const checkoutDate = $form.find('[name="checkout_date"]').val();

            if (checkinDate && checkoutDate && checkinDate >= checkoutDate) {
                const $checkoutField = $form.find('[name="checkout_date"]');
                AIOHMBookingFrontend.showFieldError($checkoutField, 'Checkout date must be after check-in date.');
                errors.push('Invalid date range');
                isValid = false;
            }

            // Validate payment method selection
            const paymentMethod = $form.find('[name="payment_method"]:checked').val();
            if (!paymentMethod) {
                AIOHMBookingFrontend.showMessage($form, 'error', 'Please select a payment method.');
                errors.push('Payment method required');
                isValid = false;
            }

            // Log validation errors if any
            if (!isValid && typeof AIOHMBookingFrontend.logError === 'function') {
                AIOHMBookingFrontend.logError('Form validation failed', errors);
            }

            return isValid;
        },
        
        /**
         * Validate individual field
         */
        validateField: function(e) {
            const $field = $(e.target);
            const value = $field.val().trim();
            const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
            const fieldName = $field.attr('name') || $field.attr('id') || 'field';

            // Clear previous error
            $field.next('.field-error').remove();
            $field.removeClass('error');

            // Remove aria-describedby if it points to a removed error
            const describedBy = $field.attr('aria-describedby');
            if (describedBy && describedBy.includes('error-')) {
                $field.removeAttr('aria-describedby');
            }

            // Check if required
            if ($field.hasClass('aiohm-required-field') && !value) {
                this.showFieldError($field, 'This field is required.', fieldName);
                return false;
            }

            // Validate email
            if (fieldType === 'email' && value && !this.isValidEmail(value)) {
                this.showFieldError($field, 'Please enter a valid email address.', fieldName);
                return false;
            }

            // Validate phone number
            if (fieldType === 'tel' && value && !this.isValidPhone(value)) {
                this.showFieldError($field, 'Please enter a valid phone number.', fieldName);
                return false;
            }

            // Validate date fields
            if (fieldType === 'date' && value) {
                if (!this.isValidDate(value)) {
                    this.showFieldError($field, 'Please enter a valid date.', fieldName);
                    return false;
                }
                if (!this.isFutureDate(value)) {
                    this.showFieldError($field, 'Please select a future date.', fieldName);
                    return false;
                }
            }

            // Validate number fields
            if (fieldType === 'number' && value) {
                const min = $field.attr('min');
                const max = $field.attr('max');
                const numValue = parseFloat(value);

                if (min && numValue < parseFloat(min)) {
                    this.showFieldError($field, 'Value must be at least ' + min + '.', fieldName);
                    return false;
                }
                if (max && numValue > parseFloat(max)) {
                    this.showFieldError($field, 'Value must be no more than ' + max + '.', fieldName);
                    return false;
                }
            }

            return true;
        },
        
        /**
         * Show field-specific error
         */
        showFieldError: function($field, message, fieldName) {
            $field.next('.field-error').remove();
            $field.addClass('error');

            const errorId = 'error-' + (fieldName || 'field') + '-' + Date.now();
            const $error = $('<div class="field-error" id="' + errorId + '" role="alert" aria-live="polite">' + message + '</div>');

            $field.after($error);
            $field.attr('aria-describedby', ($field.attr('aria-describedby') || '') + ' ' + errorId);
            $field.attr('aria-invalid', 'true');

            // Focus the field for better accessibility
            if (!$field.is(':focus')) {
                $field.focus();
            }
        },
        
        /**
         * Show general message
         */
        showMessage: function($container, type, message) {
            const messageClass = type === 'success' ? 'aiohm-success' : 'aiohm-error';
            const messageHtml = '<div class="aiohm-message ' + messageClass + '">' + message + '</div>';
            
            // Remove existing messages
            $container.find('.aiohm-message').remove();
            
            // Add new message
            $container.prepend(messageHtml);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $container.find('.aiohm-success').fadeOut();
                }, 5000);
            }
        },
        
        /**
         * Set default dates for forms
         */
        setDefaultDates: function($form) {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            // Set check-in to today if empty
            const $checkinInput = $form.find('[name="checkin_date"]');
            if (!$checkinInput.val()) {
                $checkinInput.val(this.formatDate(today));
            }
            
            // Set checkout to tomorrow if empty
            const $checkoutInput = $form.find('[name="checkout_date"]');
            if (!$checkoutInput.val()) {
                $checkoutInput.val(this.formatDate(tomorrow));
            }
        },
        
        /**
         * Update checkout date based on check-in date and duration
         */
        updateCheckoutDate: function($form) {
            const $checkinInput = $form.find('[name="checkin_date"]');
            const $checkoutInput = $form.find('[name="checkout_date"]');
            const $durationInput = $form.find('#stay_duration');
            const $checkinDisplay = $form.find('#checkinDisplay, #pricingCheckinDisplay').first();
            const $checkoutDisplay = $form.find('#checkoutDisplay, #pricingCheckoutDisplay').first();
            
            const checkinValue = $checkinInput.val();
            const duration = parseInt($durationInput.val()) || 1;
            
            if (checkinValue) {
                const checkinDate = new Date(checkinValue);
                const checkoutDate = new Date(checkinDate);
                checkoutDate.setDate(checkoutDate.getDate() + duration);
                
                const checkoutValue = this.formatDate(checkoutDate);
                $checkoutInput.val(checkoutValue);
                
                // Update display elements if they exist
                if ($checkinDisplay.length) {
                    $checkinDisplay.text(checkinDate.toLocaleDateString());
                }
                if ($checkoutDisplay.length) {
                    $checkoutDisplay.text(checkoutDate.toLocaleDateString());
                }
            }
        },
        
        /**
         * Load calendar data via AJAX
         */
        loadCalendarData: function($calendar, eventId) {
            const data = {
                action: 'aiohm_get_calendar_availability',
                start_date: this.getCurrentMonthStart(),
                end_date: this.getCurrentMonthEnd(),
                nonce: (typeof aiohm_booking_frontend !== 'undefined' && aiohm_booking_frontend.nonce) ? 
                       aiohm_booking_frontend.nonce : 
                       (typeof aiohm_booking !== 'undefined' ? aiohm_booking.nonce : '')
            };
            
            $.ajax({
                url: (typeof aiohm_booking_frontend !== 'undefined' && aiohm_booking_frontend.ajax_url) ? 
                     aiohm_booking_frontend.ajax_url : 
                     (typeof aiohm_booking !== 'undefined' ? aiohm_booking.ajax_url : '/wp-admin/admin-ajax.php'),
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        AIOHMBookingFrontend.renderCalendar($calendar, response.data);
                    }
                },
                error: function() {
                }
            });
        },
        
        /**
         * Get current month start date
         */
        getCurrentMonthStart: function() {
            const now = new Date();
            return (now.getFullYear() + '-' + 
                   String(now.getMonth() + 1).padStart(2, '0') + '-01');
        },
        
        /**
         * Get current month end date
         */
        getCurrentMonthEnd: function() {
            const now = new Date();
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            return (lastDay.getFullYear() + '-' + 
                   String(lastDay.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(lastDay.getDate()).padStart(2, '0'));
        },
        
        /**
         * Render calendar with data
         */
        renderCalendar: function($calendar, data) {
            
            $calendar.find('.aiohm-calendar-date').each(function() {
                const $date = $(this);
                const dateStr = $date.data('date');
                
                if (data[dateStr]) {
                    const dateData = data[dateStr];
                    $date.removeClass('available booked pending blocked private special disabled free');
                    
                    // Clear any existing content
                    $date.empty();
                    
                    // Map backend status to CSS class
                    let statusClass = 'free'; // default to free
                    
                    if (dateData.available) {
                        if (dateData.status === 'free') {
                            statusClass = 'free';
                        } else if (dateData.status === 'special_pricing' || dateData.status === 'special') {
                            statusClass = 'special';
                        } else if (dateData.status === 'private') {
                            statusClass = 'private';
                        } else {
                            statusClass = dateData.status; // booked, pending, blocked
                        }
                    } else {
                        statusClass = dateData.status;
                        $date.addClass('disabled');
                    }
                    
                    $date.addClass(statusClass);
                    
                    // Add private event class if applicable
                    if (dateData.badges && dateData.badges.private) {
                        $date.addClass('aiohm-private-event-date');
                    }
                    
                    // Add day number
                    const dayNumber = new Date(dateStr).getDate();
                    $date.append('<span class="day-number">' + dayNumber + '</span>');
                    
                    // Add status indicator if not free
                    if (statusClass !== 'free') {
                        let statusText = '';
                        switch(statusClass) {
                            case 'booked': statusText = 'Booked'; break;
                            case 'pending': statusText = 'Pending'; break;
                            case 'blocked': statusText = 'Blocked'; break;
                            case 'external': statusText = 'External'; break;
                            case 'private': statusText = '✓'; break;
                            case 'special': statusText = '✓'; break;
                            default: statusText = statusClass;
                        }
                        if (statusText) {
                            $date.append('<span class="status-indicator">' + statusText + '</span>');
                        }
                    }
                    
                    // Add event flag badges if present (small colored boxes)
                    if (dateData.badges) {
                        const $badgeContainer = $('<div class="aiohm-cell-badges"></div>');
                        
                        if (dateData.badges.private) {
                            $badgeContainer.append('<span class="aiohm-badge aiohm-private-badge" title="Private Event">🏠</span>');
                        }
                        if (dateData.badges.special) {
                            $badgeContainer.append('<span class="aiohm-badge aiohm-special-badge" title="High Season">🌞</span>');
                        }
                        
                        if ($badgeContainer.children().length > 0) {
                            $date.append($badgeContainer);
                        }
                    }
                    
                    // Add price if present
                    if (dateData.price > 0) {
                        $date.append('<span class="price-indicator">$' + dateData.price + '</span>');
                    }
                    
                    // Add tooltip with status info
                    let tooltip = dateStr;
                    if (dateData.status !== 'free') {
                        tooltip += ' - ' + dateData.status.replace('_', ' ');
                    }
                    if (dateData.badges) {
                        const badges = [];
                        if (dateData.badges.private) badges.push('🏠 Private Event');
                        if (dateData.badges.special) badges.push('🌞 High Season');
                        if (badges.length) tooltip += ' | ' + badges.join(', ');
                    }
                    if (dateData.price > 0) {
                        tooltip += ' ($' + dateData.price + ')';
                    }
                    $date.attr('title', tooltip);
                } else {
                    $date.addClass('free');
                    const dayNumber = new Date(dateStr).getDate();
                    $date.html('<span class="day-number">' + dayNumber + '</span>');
                }
            });
        },
        
        /**
         * Load order data for checkout
         */
        loadOrderData: function($form, orderId) {
            const data = {
                action: 'aiohm_get_order_data',
                order_id: orderId,
                nonce: aiohm_booking.nonce
            };
            
            $.ajax({
                url: aiohm_booking.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        AIOHMBookingFrontend.populateCheckoutForm($form, response.data);
                    }
                },
                error: function() {
                }
            });
        },
        
        /**
         * Populate checkout form with order data
         */
        populateCheckoutForm: function($form, orderData) {
            // Update order summary
            $form.find('.order-summary').html(orderData.summary_html);
            
            // Update total
            $form.find('.order-total').text('$' + parseFloat(orderData.total).toFixed(2));
            
            // Pre-fill customer data if available
            if (orderData.customer) {
                Object.keys(orderData.customer).forEach(function(key) {
                    $form.find('[name="' + key + '"]').val(orderData.customer[key]);
                });
            }
        },

        /**
         * Utility: Validate email format
         */
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        /**
         * Utility: Validate phone format
         */
        isValidPhone: function(phone) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
            return phoneRegex.test(cleanPhone) && cleanPhone.length >= 7 && cleanPhone.length <= 15;
        },

        /**
         * Utility: Validate date format
         */
        isValidDate: function(dateString) {
            const date = new Date(dateString);
            return date instanceof Date && !isNaN(date);
        },

        /**
         * Utility: Check if date is in the future
         */
        isFutureDate: function(dateString) {
            const inputDate = new Date(dateString);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to start of day
            return inputDate >= today;
        },
        
        /**
         * Utility: Format date for input
         */
        formatDate: function(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        },
        
        /**
         * Utility: Check if two dates are the same day
         */
        isSameDay: function(date1, date2) {
            return date1.getFullYear() === date2.getFullYear() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getDate() === date2.getDate();
        },
        
        /**
         * Utility: Debounce function calls
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = function() {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Utility: Log errors
         */
        logError: function(message, context) {
            // Removed for production
        },

        /**
         * Initialize event selection functionality
         */
        initEventSelection: function() {
            const $eventRadios = $('.event-radio');
            const $selectedEventInfo = $('.selected-event-info');
            const $ticketQuantitySection = $('.ticket-quantity-section');
            
            if ($eventRadios.length === 0) return;

            // Handle event selection
            $eventRadios.on('change', function() {
                const $radio = $(this);
                const $card = $radio.closest('.event-card');
                
                if ($radio.is(':checked')) {
                    // Update visual selection
                    $('.event-card').removeClass('selected');
                    $card.addClass('selected');
                    
                    // Get event data
                    const eventTitle = $card.find('.event-title').text();
                    const eventDate = $radio.data('event-date');
                    const eventTime = $radio.data('event-time') || '';
                    const eventPrice = $radio.data('price');
                    
                    // Format date
                    let formattedDate = '';
                    if (eventDate) {
                        const dateObj = new Date(eventDate + 'T00:00:00');
                        formattedDate = dateObj.toLocaleDateString();
                    }
                    
                    // Update selected event info
                    $('#selectedEventDisplay').text(eventTitle);
                    $('#selectedEventDate').text(formattedDate || '-');
                    $('#selectedEventTime').text(eventTime || '-');
                    
                    // Set hidden form fields
                    $('#checkinHidden').val(eventDate);
                    $('#checkoutHidden').val(eventDate);
                    
                    // Show event info and ticket quantity
                    $selectedEventInfo.fadeIn();
                    $ticketQuantitySection.fadeIn();
                    
                    // Update max ticket quantity based on available seats
                    const availableSeats = $radio.data('available-seats') || 50;
                    $('#tickets_qty').attr('max', availableSeats);
                    
                    // Reset ticket quantity to 1
                    $('#tickets_qty').val(1);
                }
            });

            // Handle ticket quantity validation
            $('#tickets_qty').on('input', function() {
                const $input = $(this);
                const value = parseInt($input.val()) || 1;
                const maxSeats = parseInt($input.attr('max')) || 50;
                
                if (value > maxSeats) {
                    $input.val(maxSeats);
                    alert('Maximum ' + maxSeats + ' tickets available for this event.');
                }
                
                if (value < 1) {
                    $input.val(1);
                }
            });
        }
    };
    
    /**
     * Make AIOHMBookingFrontend globally available
     */
    window.AIOHMBookingFrontend = AIOHMBookingFrontend;
    
    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        // Delay initialization to allow shortcode.js to load first if present
        setTimeout(function() {
            // Only initialize if shortcode system isn't already handling calendars
            if (typeof AIOHM_Booking_Shortcode === 'undefined' ||
                !window.AIOHM_Booking_Shortcode ||
                $('.booking-calendar-container').length === 0) {
                AIOHMBookingFrontend.init();
            }
            
            // Always initialize event selection
            AIOHMBookingFrontend.initEventSelection();
        }, 100);
    });
    
})(jQuery);
