/**
 * AIOHM Booking Shortcode JavaScript v2.0 [FRONTEND CALENDAR FIX]
 * Frontend functionality for shortcode interactions
 * Now uses same availability data as admin calendar
 *
 * @package AIOHM_Booking_PRO
 * @since   1.0.0
 */

(function($) {
    'use strict';

    // Create global object immediately when script loads
    window.AIOHM_Booking_Shortcode = {

        // Helper function to get Monday-based day of week (0 = Monday, 6 = Sunday)
        getMondayBasedDay: function(date) {
            var day = date.getDay();
            return day === 0 ? 6 : day - 1;
        },

        init: function() {
            this.bindEvents();
            this.initializeShortcodes();
            this.applyCalendarColors();
            this.applyUserColors();
            this.checkInitialDateSelection();
        },

        bindEvents: function() {
            // Widget button clicks
            $(document).on('click.aiohm-shortcode', '.booking-widget-button', this.handleWidgetClick);

            // Event booking links
            $(document).on('click.aiohm-shortcode', '.aiohm-booking-events .event-link', this.handleEventClick);

            // Form submissions
            $(document).on('submit.aiohm-shortcode', '.aiohm-booking-form', this.handleShortcodeFormSubmit);

            // Also bind to form id as backup
            $(document).on('submit.aiohm-shortcode', '#aiohm-booking-form', this.handleShortcodeFormSubmit);

            // Direct button click handler as additional backup
            $(document).on('click.aiohm-shortcode', '.booking-btn', function(e) {
                var $form = $(this).closest('form');
                if ($form.length) {
                    e.preventDefault();
                    $form.trigger('submit');
                } else {
                }
            });

            // Date change handlers for accommodation availability filtering - use hidden inputs from accommodation selection and contact form
            $(document).on('change.aiohm-shortcode', '#checkinHidden, #checkoutHidden, #customer_arrival_date, #customer_departure_date', this.handleDateChange);
        },

        // Apply calendar colors from admin settings to frontend calendar
        applyCalendarColors: function() {
            if (typeof aiohm_booking === 'undefined' || !aiohm_booking.calendar_colors) {
                return;
            }

            const colors = aiohm_booking.calendar_colors;
            const brandColor = aiohm_booking.brand_color || '#457d59';

            // Create dynamic CSS for calendar colors
            const style = document.createElement('style');
            style.id = 'aiohm-frontend-calendar-colors';

            // Remove existing style if it exists
            const existing = document.getElementById(style.id);
            if (existing) existing.remove();

            // Build CSS for each color status
            let css = '';

            // Card borders use fixed OHM green color via CSS variables - do not override
            // css += `
            //     .aiohm-booking-card,
            //     .aiohm-module-card {
            //         border-left-color: ${brandColor} !important;
            //     }
            // `;

            Object.keys(colors).forEach(status => {
                const color = colors[status];

                // Apply background colors for actual booking statuses
                if (['free', 'booked', 'pending', 'external', 'blocked'].includes(status)) {
                    css += `
                        .aiohm-calendar-date.${status},
                        .aiohm-booking-calendar-container .calendar-day.${status},
                        .aiohm-booking-calendar-container .calendar-day.status-${status},
                        .legend-${status} {
                            background-color: ${color} !important;
                        }
                    `;

                    // Handle free status with border for visibility using brand color
                    if (status === 'free' && color === '#ffffff') {
                        css += `
                            .aiohm-calendar-date.${status},
                            .aiohm-booking-calendar-container .calendar-day.${status},
                            .aiohm-booking-calendar-container .calendar-day.status-${status},
                            .legend-${status} {
                                border: 1px solid ${brandColor} !important;
                            }
                        `;
                    }
                }

                // Apply colors to legend dots and event badges
                css += `
                    .legend-dot.legend-${status},
                    .aiohm-${status}-badge {
                        background-color: ${color} !important;
                    }
                `;
            });

            style.textContent = css;
            document.head.appendChild(style);
        },

        // Apply user-chosen colors from form settings
        applyUserColors: function() {
            if (typeof aiohm_booking_colors === 'undefined') {
                return;
            }

            const brandColor = aiohm_booking_colors.brand_color || '#457d59';
            const textColor = aiohm_booking_colors.text_color || '#ffffff';

            // Create dynamic CSS for user colors
            const style = document.createElement('style');
            style.id = 'aiohm-user-colors';

            // Remove existing style if it exists
            const existing = document.getElementById(style.id);
            if (existing) existing.remove();

            // Set CSS variables and specific overrides
            const css = `
                :root {
                    --aiohm-brand-color: ${brandColor};
                    --aiohm-text-color: ${textColor};
                }
            `;

            style.textContent = css;
            document.head.appendChild(style);
        },

        initializeShortcodes: function() {
            // Initialize any shortcodes that need setup
            $('.aiohm-booking-shortcode-wrapper').each(function() {
                var $wrapper = $(this);
                var mode = $wrapper.data('mode') || 'auto';
                var theme = $wrapper.data('theme') || 'default';

                // Add initialization logic here
                $wrapper.addClass('initialized');
            });

            // Initialize booking form calendars (skip widget calendars)
            var $calendars = $('.aiohm-booking-calendar-container').not('.widget-calendar, [data-widget-calendar]');
            $calendars.each(function() {
                AIOHM_Booking_Shortcode.initCalendar($(this));
            });

            // Initialize modern booking forms
            $('.aiohm-booking-modern').each(function() {
                AIOHM_Booking_Shortcode.initBookingForm($(this));
            });

            // Initialize sandwich booking forms
            $('.aiohm-booking-sandwich-form').each(function() {
                AIOHM_Booking_Shortcode.initBookingForm($(this));
            });
        },

        handleWidgetClick: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $widget = $button.closest('.aiohm-booking-widget');

            // Add loading state
            $button.prop('disabled', true).text('Loading...');

            // Simulate booking action - replace with actual booking logic
            setTimeout(function() {
                $button.prop('disabled', false).text($button.data('original-text') || 'Book Now');

                // Show success message or redirect to booking form
                AIOHM_Booking_Shortcode.showMessage('Redirecting to booking form...', 'success');
            }, 1000);
        },

        handleEventClick: function(e) {
            e.preventDefault();
            var $link = $(this);
            var eventId = $link.data('event-id');

            // Add loading state
            $link.addClass('loading');

            // Handle event booking - replace with actual logic
            setTimeout(function() {
                $link.removeClass('loading');
                AIOHM_Booking_Shortcode.showMessage('Redirecting to event booking...', 'success');
            }, 500);
        },

        /**
         * Handle shortcode form submission
         */
        handleShortcodeFormSubmit: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $wrapper = $form.closest('.aiohm-booking-shortcode-wrapper');

            // Check if there's a checkout page URL
            var checkoutUrl = $form.data('checkout-url');

            // Validate form
            if (!AIOHM_Booking_Shortcode.validateForm($form)) {
                return false;
            }


            // Add loading state
            AIOHM_Booking_Shortcode.showLoading($wrapper);

            // Submit form via AJAX
            var formData = $form.serialize();

            // Fix: Manually add accommodation checkboxes to form data
            var checkedAccommodations = $form.find('.accommodation-checkbox:checked, .unit-checkbox:checked');

            // Also check for "Book All" checkbox
            var bookAllCheckbox = $form.find('#private_all_checkbox:checked');
            if (bookAllCheckbox.length > 0) {
                checkedAccommodations = checkedAccommodations.add(bookAllCheckbox);
            }

            if (checkedAccommodations.length > 0) {
                var accommodationData = [];
                checkedAccommodations.each(function() {
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    accommodationData.push(encodeURIComponent(name) + '=' + encodeURIComponent(value));
                });

                // Add accommodations to form data
                if (accommodationData.length > 0) {
                    formData += (formData ? '&' : '') + accommodationData.join('&');
                }
            }


            // Determine the appropriate AJAX action based on form content
            var hasAccommodations = checkedAccommodations.length > 0;
            var hasEvents = false;
            
            // Check for event selections
            var eventRadios = $form.find('input[name="selected_event"]:checked');
            var eventCheckboxes = $form.find('input[name="selected_events[]"]:checked');
            if (eventRadios.length > 0 || eventCheckboxes.length > 0) {
                hasEvents = true;
            }
            
            // Determine the correct action
            var ajaxAction;
            if (hasAccommodations && hasEvents) {
                ajaxAction = 'aiohm_booking_submit_unified';
            } else if (hasAccommodations) {
                ajaxAction = 'aiohm_booking_submit_accommodation';
            } else if (hasEvents) {
                ajaxAction = 'aiohm_booking_submit_event';
            } else {
                AIOHM_Booking_Shortcode.showMessage('Please select either accommodations or events to book.', 'error');
                AIOHM_Booking_Shortcode.hideLoading($wrapper);
                return;
            }

            $.ajax({
                url: aiohm_booking.ajax_url,
                type: 'POST',
                data: {
                    action: ajaxAction,
                    nonce: aiohm_booking.nonce,
                    form_data: formData
                },
                success: function(response) {
                    if (response.data && response.data.message) {
                    }
                    AIOHM_Booking_Shortcode.hideLoading($wrapper);

                    if (response.success) {
                        // Check if there's a checkout page URL
                        var checkoutUrl = $form.data('checkout-url');

                        if (checkoutUrl) {
                            // Show success message briefly then redirect
                            AIOHM_Booking_Shortcode.showMessage('Booking submitted successfully! Redirecting to checkout...', 'success');

                            setTimeout(function() {
                                // Redirect to checkout page with booking ID
                                var redirectUrl = checkoutUrl;
                                if (response.data.booking_id) {
                                    // Add booking ID as URL parameter
                                    redirectUrl += (checkoutUrl.includes('?') ? '&' : '?') + 'booking_id=' + response.data.booking_id;
                                }
                                window.location.href = redirectUrl;
                            }, 1500);
                        } else {
                            // Fallback: show message and reset form
                            AIOHM_Booking_Shortcode.showMessage(response.data.message, 'success');
                            $form[0].reset();

                            // Note: Calendar refresh disabled to preserve visual effects
                            // var $calendar = $wrapper.find('.aiohm-booking-calendar-container');
                            // if ($calendar.length) {
                            //     AIOHM_Booking_Shortcode.initCalendar($calendar);
                            // }
                        }
                    } else {
                        AIOHM_Booking_Shortcode.showMessage(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    AIOHM_Booking_Shortcode.hideLoading($wrapper);
                    AIOHM_Booking_Shortcode.showMessage('An error occurred. Please try again.', 'error');
                }
            });
        },

        validateForm: function($form) {
            var isValid = true;
            var $required = $form.find('[required]');

            $required.each(function() {
                var $field = $(this);
                var value = $field.val().trim();

                if (!value) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });

            // Email validation
            var $email = $form.find('input[type="email"]');
            if ($email.length && $email.val()) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($email.val())) {
                    $email.addClass('error');
                    isValid = false;
                }
            }

            if (!isValid) {
                this.showMessage('Please fill in all required fields correctly.', 'error');
            }

            return isValid;
        },

        handleDateChange: function(e) {
            // Check dates from both accommodation selection (step 1) and contact form (step 2)
            var arrivalDate = $('#checkinHidden').val() || $('#customer_arrival_date').val();
            var departureDate = $('#checkoutHidden').val() || $('#customer_departure_date').val();

            // Only update if both dates are selected
            if (arrivalDate && departureDate) {
                // Prevent multiple simultaneous AJAX calls
                if (AIOHM_Booking_Shortcode.isUpdatingAccommodationSelection) {
                    return;
                }
                AIOHM_Booking_Shortcode.isUpdatingAccommodationSelection = true;

                // Find the accommodation selection section and update it
                var $accommodationSection = $('.aiohm-booking-accommodation-selection');
                if ($accommodationSection.length) {
                    // Add loading state
                    $accommodationSection.addClass('loading');

                    // Make AJAX request to get updated accommodation selection
                    $.ajax({
                        url: aiohm_booking.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'aiohm_booking_update_accommodation_selection',
                            nonce: aiohm_booking.nonce,
                            arrival_date: arrivalDate,
                            departure_date: departureDate
                        },
                        success: function(response) {
                            if (response.success && response.data.html) {
                                // Replace only the accommodation list section to preserve calendar state
                                var $accommodationList = $accommodationSection.find('.aiohm-booking-form-section');
                                
                                if ($accommodationList.length) {
                                    // Extract only the accommodation list part from the response HTML
                                    var $tempDiv = $('<div>').html(response.data.html);
                                    var $newAccommodationList = $tempDiv.find('.aiohm-booking-form-section');
                                    
                                    if ($newAccommodationList.length) {
                                        $accommodationList.replaceWith($newAccommodationList);
                                    } else {
                                        // Fallback: replace entire section if accommodation list not found
                                        $accommodationSection.html(response.data.html);
                                    }
                                } else {
                                    // Fallback: replace entire section if accommodation list not found
                                    $accommodationSection.html(response.data.html);
                                }

                                // Note: Calendar is NOT re-initialized to preserve visual effects like selected day blink
                            } else {
                                // AJAX response not successful or missing HTML
                            }
                        },
                        error: function(xhr, status, error) {
                            // Error updating accommodation selection
                        },
                        complete: function() {
                            $accommodationSection.removeClass('loading');
                            AIOHM_Booking_Shortcode.isUpdatingAccommodationSelection = false;
                        }
                    });
                } else {
                    // Accommodation section not found
                }
            } else {
                // Not both dates selected yet
            }
        },

        checkInitialDateSelection: function() {
            var $arrivalDate = $('#checkinHidden');
            var $departureDate = $('#checkoutHidden');
            var arrivalDate = $arrivalDate.val();
            var departureDate = $departureDate.val();

            // If both dates are already selected, trigger the update
            if (arrivalDate && departureDate) {
                this.handleDateChange();
            }
        },

        showLoading: function($container) {
            var $loading = $('<div class="aiohm-booking-loading">Processing your request...</div>');
            $container.find('.aiohm-booking-form, .aiohm-booking-checkout').append($loading);
        },

        hideLoading: function($container) {
            $container.find('.aiohm-booking-loading').remove();
        },

        showMessage: function(message, type) {
            type = type || 'info';
            var alertType = type;
            if (type === 'error') {
                alertType = 'danger';
            }

            var $message = $('<div class="aiohm-booking-message aiohm-alert aiohm-alert--' + alertType + '">' +
                           '<span>' + message + '</span>' +
                           '<button type="button" class="message-close">&times;</button>' +
                           '</div>');

            // Find the best place to show the message
            var $target = $('.aiohm-booking-shortcode-wrapper').first();
            if (!$target.length) {
                $target = $('body');
            }

            $target.prepend($message);

            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Handle manual close
            $message.find('.message-close').on('click', function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        // Calendar-specific functionality
        initCalendar: function($calendar) {
            // Initialize modern calendar with availability data
            this.setupModernCalendar($calendar);
        },

        // Modern calendar implementation
        setupModernCalendar: function($calendar) {
            // Skip widget calendars to avoid conflicts
            if ($calendar.hasClass('widget-calendar') || $calendar.data('widget-calendar')) {
                return;
            }

            var self = this;
            var currentDate = new Date();
            var currentYear = currentDate.getFullYear();
            var currentMonth = currentDate.getMonth();

            // Check if calendar is already initialized and preserve current view
            var $monthYear = $calendar.find('#currentMonth');
            if ($monthYear.length && $monthYear.data('initialized')) {
                // Parse current month/year from display
                var monthYearText = $monthYear.text();
                var monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"];
                var parts = monthYearText.split(' ');
                if (parts.length === 2) {
                    var monthName = parts[0];
                    currentYear = parseInt(parts[1]);
                    currentMonth = monthNames.indexOf(monthName);
                }
            }

            // Calendar data cache
            var calendarData = {};

            // Check if we have the required elements
            if (!$calendar.find('#calendarGrid').length || !$calendar.find('#currentMonth').length) {
                return;
            }

            function renderCalendar(year, month) {
                var $grid = $calendar.find('#calendarGrid');
                var $monthYear = $calendar.find('#currentMonth');

                var firstDay = new Date(year, month, 1);
                var lastDay = new Date(year, month + 1, 0);
                var startDate = new Date(firstDay);
                startDate.setDate(startDate.getDate() - self.getMondayBasedDay(firstDay));

                var endDate = new Date(lastDay);
                endDate.setDate(endDate.getDate() + (6 - self.getMondayBasedDay(lastDay)));

                // Update month/year display
                var monthNames = ["January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"];
                $monthYear.text(monthNames[month] + " " + year);
                $monthYear.data('initialized', true);

                // Fetch availability data
                self.fetchCalendarAvailability(
                    startDate.getFullYear() + '-' + ('0' + (startDate.getMonth() + 1)).slice(-2) + '-' + ('0' + startDate.getDate()).slice(-2),
                    endDate.getFullYear() + '-' + ('0' + (endDate.getMonth() + 1)).slice(-2) + '-' + ('0' + endDate.getDate()).slice(-2),
                    function(data) {
                        calendarData = data || {};
                        renderCalendarDays();
                    }
                );

                function renderCalendarDays() {
                    // Remove existing calendar days but keep day headers
                    $grid.find('.aiohm-calendar-date').remove();

                    var current = new Date(startDate);
                    while (current <= endDate) {
                        // Use the correct date formatting - Y-m-d format to match backend keys
                        var dateString = current.getFullYear() + '-' +
                                       ('0' + (current.getMonth() + 1)).slice(-2) + '-' +
                                       ('0' + current.getDate()).slice(-2);
                        var isCurrentMonth = current.getMonth() === month;
                        var isToday = self.isToday(current);
                        var isPastDate = self.isPastDate(current);
                        var dayData = calendarData[dateString] || { status: 'available', available: true };
                        var classes = ['aiohm-calendar-date'];
                        if (!isCurrentMonth) classes.push('other-month');
                        if (isToday) classes.push('today');
                        if (isPastDate) classes.push('disabled', 'past');
                        if (!dayData.available) classes.push('unavailable');

                        // Add status class - use the actual booking status for cell color
                        var status = dayData.status || 'available';

                        // Private events should not color the cell - they show actual booking status + badge
                        if (status === 'private') {
                            // Use the underlying booking status if available, otherwise default to free
                            status = dayData.booking_status || 'available';
                        }

                        if (status === 'available') status = 'free'; // Map to CSS class name

                        // Apply the correct CSS class that matches the CSS selectors
                        // aiohm-calendar-date is already added at line 395, don't duplicate

                        // Special pricing/high season does NOT affect cell background colors
                        if (status === 'free') {
                            classes.push('free');
                        } else if (status === 'booked') {
                            classes.push('booked');
                        } else if (status === 'pending') {
                            classes.push('pending');
                        } else if (status === 'blocked') {
                            classes.push('blocked');
                        } else if (status === 'external') {
                            classes.push('external');
                        } else if (status === 'special' || status === 'special_pricing') {
                            // Special pricing only shows badges, not background colors
                            classes.push('free'); // Keep cell as free for background color
                        } else if (status === 'private') {
                            // Private events only shows badges, not background colors
                            classes.push('free'); // Keep cell as free for background color
                        } else {
                            classes.push('free'); // Default to free
                        }

                        var dayElement = $('<div class="' + classes.join(' ') + '" data-date="' + dateString + '">' + current.getDate() + '</div>');

                        // Remove title attribute to prevent browser tooltip
                        // Store tooltip data for legend replacement
                        dayElement.attr('data-tooltip-data', JSON.stringify(dayData));
                        dayElement.attr('data-date-string', dateString);

                        // Add event indicators as colored badges (like admin calendar)
                        var hasBadges = false;
                        var badgesContainer = $('<div class="aiohm-cell-badges"></div>');

                        // Show badges based on backend badge data
                        if (dayData.badges && dayData.badges.private) {
                            var eventTitle = dayData.event_name || 'Private Event';
                            badgesContainer.append('<div class="aiohm-badge aiohm-private-badge" title="Private Event: ' + eventTitle + '">🏠</div>');
                            hasBadges = true;
                            // Add class for private event detection
                            classes.push('aiohm-private-event-date');
                        }
                        if (dayData.badges && dayData.badges.special) {
                            badgesContainer.append('<div class="aiohm-badge aiohm-special-badge" title="High Season">💰</div>');
                            hasBadges = true;
                            // Add class for special pricing detection
                            classes.push('aiohm-special-pricing-date');
                        }

                        if (hasBadges) {
                            dayElement.css('position', 'relative').append(badgesContainer);
                        }

                        $grid.append(dayElement);
                        current.setDate(current.getDate() + 1);
                    }

                    // Bind click events for date selection (allow fully booked days for checkout)
                    $grid.find('.aiohm-calendar-date:not(.unavailable):not(.other-month):not(.disabled):not(.past):not(.empty)').on('click', function() {
                        self.handleDateSelection($(this), $calendar);
                    });

                    // Bind hover events for legend-style tooltip and price tooltip
                    $grid.find('.aiohm-calendar-date').on('mouseenter', function() {
                        self.showLegendTooltip($(this), $calendar);
                        self.showPriceTooltip($(this));
                    }).on('mouseleave', function() {
                        self.hideLegendTooltip($calendar);
                        self.hidePriceTooltip($(this));
                    });
                }
            }

            // Navigation
            $calendar.find('#prevMonth').on('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentYear, currentMonth);
            });

            $calendar.find('#nextMonth').on('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentYear, currentMonth);
            });

            // Initial render
            renderCalendar(currentYear, currentMonth);
        },

        // Fetch availability data via AJAX
        fetchCalendarAvailability: function(startDate, endDate, callback) {
            var self = this;
            
            // Use localized ajax_url or fallback
            var ajaxUrl = aiohm_booking_frontend?.ajax_url || '/wp-admin/admin-ajax.php';
            var nonce = aiohm_booking_frontend?.nonce || '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aiohm_get_calendar_availability',
                    start_date: startDate,
                    end_date: endDate,
                    unit_id: 0, // Check all rooms
                    nonce: nonce,
                    cache_bust: new Date().getTime() // Prevent caching
                },
                success: function(response) {
                    if (response.success) {
                        // Cache the availability data for tooltip access
                        if (!window.AIOHM_Booking_Shortcode) {
                            window.AIOHM_Booking_Shortcode = {};
                        }
                        window.AIOHM_Booking_Shortcode.cachedAvailability = response.data;
                        
                        callback(response.data);
                    } else {
                        callback({});
                    }
                },
                error: function(xhr, status, error) {
                    callback({});
                }
            });
        },

        // Date utility functions
        formatDate: function(date) {
            return date.getFullYear() + '-' +
                   ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                   ('0' + date.getDate()).slice(-2);
        },

        isToday: function(date) {
            var today = new Date();
            return date.getDate() === today.getDate() &&
                   date.getMonth() === today.getMonth() &&
                   date.getFullYear() === today.getFullYear();
        },

        isPastDate: function(date) {
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var checkDate = new Date(date);
            checkDate.setHours(0, 0, 0, 0);
            return checkDate < today;
        },

        // Show legend replacement box that appears exactly where legend is
        showLegendTooltip: function($dayElement, $calendar) {
            var self = this;
            var tooltipData = $dayElement.attr('data-tooltip-data');
            var dateString = $dayElement.attr('data-date-string');

            if (!tooltipData || !dateString) return;

            var dayData;
            try {
                dayData = JSON.parse(tooltipData);
            } catch (e) {
                return;
            }

            // Find the legend box to replace (try multiple selectors)
            var $originalLegend = $('.aiohm-calendar-legend, .aiohm-booking-legend, .booking-status-legend, .legend, .calendar-legend, .booking-legend, [class*="legend"], [class*="status"]').first();

            if (!$originalLegend.length) {
                // Look for legend elements only within the calendar container or booking shortcode
                var $calendarContainer = $calendar.closest('.aiohm-booking-calendar-container, .aiohm-booking-shortcode, .aiohm-calendar, [class*="aiohm"], [class*="booking"]');
                if ($calendarContainer.length) {
                    $originalLegend = $calendarContainer.find('*').filter(function() {
                        var text = $(this).text().toLowerCase();
                        return (text.includes('free') || text.includes('available')) &&
                               (text.includes('booked') || text.includes('pending') || text.includes('blocked')) &&
                               $(this).children().length < 8 && // Avoid selecting large containers
                               $(this).closest('.aiohm-booking-calendar-container, .aiohm-booking-shortcode').length; // Must be within booking context
                    }).first();
                }
            }

            // Additional safety check: ensure we're not replacing critical page elements
            if ($originalLegend.length) {
                var tagName = $originalLegend.prop('tagName').toLowerCase();
                var hasCriticalClasses = $originalLegend.hasClass('elementor') ||
                                        $originalLegend.hasClass('site-main') ||
                                        $originalLegend.hasClass('content') ||
                                        $originalLegend.hasClass('page-content') ||
                                        $originalLegend.attr('id') === 'main' ||
                                        $originalLegend.attr('id') === 'content' ||
                                        $originalLegend.attr('id') === 'primary';

                if (tagName === 'body' || tagName === 'html' || hasCriticalClasses) {
                    return; // Don't replace critical page elements
                }
            }

            if (!$originalLegend.length) return; // Can't replace if we can't find the legend

            // Store original legend content and styling for restoration
            if (!$originalLegend.data('original-content')) {
                $originalLegend.data('original-content', $originalLegend.html());
                $originalLegend.data('original-class', $originalLegend.attr('class'));
            }

            // Create replacement content with exact same styling as legend
            var replacementHtml = this.generateLegendReplacementHTML(dayData, dateString);

            // Replace the legend content while keeping all styling
            $originalLegend.html(replacementHtml);
            $originalLegend.addClass('aiohm-legend-replaced');

            // Store reference for cleanup
            $calendar.data('active-tooltip', $originalLegend);
        },

        // Hide legend replacement and restore original legend
        hideLegendTooltip: function($calendar) {
            var $activeLegend = $calendar.data('active-tooltip');

            if ($activeLegend && $activeLegend.data('original-content')) {
                // Restore original legend content
                $activeLegend.html($activeLegend.data('original-content'));
                $activeLegend.removeClass('aiohm-legend-replaced');
            }

            // Clean up reference
            $calendar.removeData('active-tooltip');
        },

        // Show price tooltip on calendar cell
        showPriceTooltip: function($dayElement) {
            // Don't show on other month cells or unavailable cells
            if ($dayElement.hasClass('other-month') || $dayElement.hasClass('unavailable')) {
                return;
            }

            // Remove any existing tooltip
            $dayElement.find('.aiohm-calendar-price-tooltip').remove();

            // Get pricing data - use accommodation pricing but override base_price from aiohm_booking_data
            var pricingData = window.aiohm_accommodation_pricing;
            
            // Override base_price with correct value from aiohm_booking_data if available
            if (window.aiohm_booking_data && window.aiohm_booking_data.pricing && window.aiohm_booking_data.pricing.base_price) {
                if (pricingData) {
                    pricingData.base_price = window.aiohm_booking_data.pricing.base_price;
                    pricingData.currency = window.aiohm_booking_data.pricing.currency || pricingData.currency;
                    // Also merge early bird settings
                    if (window.aiohm_booking_data.pricing.early_bird) {
                        pricingData.early_bird = window.aiohm_booking_data.pricing.early_bird;
                    }
                } else {
                    pricingData = window.aiohm_booking_data.pricing;
                }
            }
            
            
            if (!pricingData) {
                return; // No pricing data available
            }

            // Get currency from user settings
            var currency = pricingData.currency;

            // Get the currently selected accommodation (if any)
            var selectedAccommodation = this.getSelectedAccommodation();
            var basePrice = parseFloat(pricingData.base_price) || 100; // Fallback to 100 if no price set
            var accommodationPrice = basePrice;
            
            
            // If user has selected a specific accommodation, use its price
            if (selectedAccommodation && selectedAccommodation.price) {
                accommodationPrice = parseFloat(selectedAccommodation.price);
            }

            // Get the date for early bird calculation
            var dayDate = $dayElement.attr('data-date');
            if (!dayDate) {
                return;
            }

            // Check for special event pricing first
            var hasSpecialPricing = false;
            var specialPrice = 0;
            
            // First check the data-tooltip-data attribute (most reliable)
            var tooltipData = $dayElement.attr('data-tooltip-data');
            if (tooltipData) {
                try {
                    var dayData = JSON.parse(tooltipData);
                    if (dayData && dayData.price > 0) {
                        specialPrice = parseFloat(dayData.price);
                        hasSpecialPricing = true;
                    }
                } catch (e) {
                    // Silent error handling
                }
            }
            
            // If no tooltip data, check if this day has special pricing (private event with special pricing)
            if (!hasSpecialPricing && ($dayElement.hasClass('special-pricing') || $dayElement.find('.price-badge').length > 0)) {
                // Try to get special price from data attributes or stored availability data
                specialPrice = parseFloat($dayElement.attr('data-special-price')) || 0;
                
                // If no data attribute, check if we have cached availability data for this date
                if (specialPrice === 0 && window.AIOHM_Booking_Shortcode && window.AIOHM_Booking_Shortcode.cachedAvailability) {
                    var availabilityData = window.AIOHM_Booking_Shortcode.cachedAvailability[dayDate];
                    if (availabilityData && availabilityData.price > 0) {
                        specialPrice = parseFloat(availabilityData.price);
                    }
                }
                
                if (specialPrice > 0) {
                    hasSpecialPricing = true;
                }
            }

            // Calculate pricing
            var price = accommodationPrice;
            var priceLabel = 'Per Night';
            var isEarlybird = false;
            var isSpecial = false;
            var isPrivate = false;

            // Check if this is a private event
            if ($dayElement.hasClass('aiohm-private-event-date') || $dayElement.find('.aiohm-private-badge').length > 0) {
                isPrivate = true;
            }

            // Use special pricing if available (takes priority over everything)
            if (hasSpecialPricing && specialPrice > 0) {
                price = specialPrice;
                priceLabel = 'Per Night';
                isSpecial = true;
            } else {
                // Check if early bird is enabled and calculate eligibility
                if (pricingData.early_bird && pricingData.early_bird.enabled) {
                    var today = new Date();
                    today.setHours(0, 0, 0, 0); // Reset time to compare dates only
                    
                    var checkinDate = new Date(dayDate);
                    checkinDate.setHours(0, 0, 0, 0);
                    
                    var daysUntilCheckin = Math.ceil((checkinDate - today) / (1000 * 60 * 60 * 24));
                    var earlyBirdDays = parseInt(pricingData.early_bird.days) || 30;

                    if (daysUntilCheckin >= earlyBirdDays) {
                        // Apply early bird pricing
                        if (selectedAccommodation && selectedAccommodation.earlybird_price && selectedAccommodation.earlybird_price > 0) {
                            // Use accommodation-specific early bird price
                            price = parseFloat(selectedAccommodation.earlybird_price);
                        } else if (pricingData.early_bird && pricingData.early_bird.default_price > 0) {
                            // Use global early bird default price
                            price = parseFloat(pricingData.early_bird.default_price);
                        } else {
                        // Use regular price when no early bird price is set
                        price = accommodationPrice;
                    }
                    priceLabel = 'Early Bird (' + earlyBirdDays + '+ days)';
                    isEarlybird = true;
                }
            }
            }

            // Create tooltip HTML with indicators
            var tooltipHtml = '<div class="aiohm-calendar-price-tooltip">';
            
            // Show private event indicator if applicable
            if (isPrivate) {
                tooltipHtml += '<div class="aiohm-tooltip-private-indicator">Private Event</div>';
            }
            
            // Show special pricing indicator if applicable
            if (isSpecial) {
                tooltipHtml += '<div class="aiohm-tooltip-special-indicator">High Season</div>';
            } else if (isEarlybird) {
                tooltipHtml += '<div class="aiohm-tooltip-earlybird-indicator">Early Bird</div>';
            }
            
            tooltipHtml += '<div class="aiohm-tooltip-price">' + currency + ' ' + price.toFixed(2) + '</div>' +
                '<div class="aiohm-tooltip-label">' + priceLabel + '</div>';
            
            if (price === 0) {
                tooltipHtml = '<div class="aiohm-calendar-price-tooltip">' +
                    '<div class="aiohm-tooltip-label">Price not set</div>' +
                    '</div>';
            }
            
            tooltipHtml += '</div>';

            // Add tooltip to cell
            $dayElement.append(tooltipHtml);
            
            // Fix positioning for preview mode
            var $tooltip = $dayElement.find('.aiohm-calendar-price-tooltip');
            if ($tooltip.length && $('.aiohm-direct-preview-content').length) {
                // In preview mode, use fixed positioning and calculate viewport position
                var dayRect = $dayElement[0].getBoundingClientRect();
                $tooltip.css({
                    position: 'fixed',
                    top: dayRect.top - 35 + 'px',
                    left: dayRect.left + (dayRect.width / 2) + 'px',
                    transform: 'translateX(-50%)',
                    zIndex: 999999
                });
            }
        },

        // Get currently selected accommodation data
        getSelectedAccommodation: function() {
            var selectedAccommodationId = null;
            
            // Check for both radio buttons and checkboxes (covering different UI patterns)
            var $selectedInput = $('.aiohm-accommodation-option input[type="radio"]:checked, .accommodation-checkbox:checked');
            
            if ($selectedInput.length) {
                selectedAccommodationId = $selectedInput.val() || $selectedInput.data('id');
            }

            // Find the accommodation data - use same hybrid approach as showPriceTooltip
            var pricingData = window.aiohm_accommodation_pricing;
            
            // Override base_price with correct value from aiohm_booking_data if available
            if (window.aiohm_booking_data && window.aiohm_booking_data.pricing && window.aiohm_booking_data.pricing.base_price) {
                if (pricingData) {
                    pricingData.base_price = window.aiohm_booking_data.pricing.base_price;
                    pricingData.currency = window.aiohm_booking_data.pricing.currency || pricingData.currency;
                    if (window.aiohm_booking_data.pricing.early_bird) {
                        pricingData.early_bird = window.aiohm_booking_data.pricing.early_bird;
                    }
                } else {
                    pricingData = window.aiohm_booking_data.pricing;
                }
            }
            
            if (pricingData && pricingData.accommodations && selectedAccommodationId !== null) {
                for (var i = 0; i < pricingData.accommodations.length; i++) {
                    if (pricingData.accommodations[i].id == selectedAccommodationId) {
                        return pricingData.accommodations[i];
                    }
                }
            }

            return null;
        },

        // Hide price tooltip
        hidePriceTooltip: function($dayElement) {
            $dayElement.find('.aiohm-calendar-price-tooltip').remove();
        },

        // Refresh all calendar tooltips (used when accommodation selection changes)
        refreshCalendarTooltips: function() {
            var self = this;
            $('.aiohm-calendar-date:not(.other-month):not(.unavailable)').each(function() {
                var $dayElement = $(this);
                // Remove existing tooltip and re-add with updated pricing
                self.hidePriceTooltip($dayElement);
                self.showPriceTooltip($dayElement);
            });
        },

        // Update accommodation card pricing based on selected dates
        updateAccommodationCardPricing: function(checkinDate, checkoutDate) {
            // Check if we have cached availability data
            if (!window.AIOHM_Booking_Shortcode || !window.AIOHM_Booking_Shortcode.cachedAvailability) {
                // Fetch availability data for the date range
                var self = this;
                this.fetchCalendarAvailability(checkinDate, checkoutDate, function(data) {
                    if (data && Object.keys(data).length > 0) {
                        window.AIOHM_Booking_Shortcode.cachedAvailability = data;
                        self.updateAccommodationCardPricing(checkinDate, checkoutDate);
                    }
                });
                return;
            }

            const availabilityData = window.AIOHM_Booking_Shortcode.cachedAvailability;
            const $accommodationCards = $('.aiohm-booking-accommodation-card');
            
            // Calculate total nights
            const checkin = new Date(checkinDate);
            const checkout = new Date(checkoutDate);
            const nights = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
            
            if (nights <= 0) {
                return; // Invalid date range
            }

            $accommodationCards.each(function() {
                const $card = $(this);
                const $checkbox = $card.find('.accommodation-checkbox');
                const $priceDisplay = $card.find('.aiohm-booking-event-price');
                const $specialBadge = $card.find('.aiohm-special-pricing-badge');
                
                if (!$priceDisplay.length) return;
                
                // Get original pricing
                const originalPrice = parseFloat($checkbox.attr('data-price') || 0);
                const pricingContainer = $('.aiohm-pricing-container');
                const currency = pricingContainer.data('currency');
                
                let totalSpecialPrice = 0;
                let hasSpecialPricing = false;
                let specialNights = 0;
                
                // Check each night for special pricing
                let currentDate = new Date(checkinDate);
                while (currentDate < checkout) {
                    const dateString = currentDate.toISOString().split('T')[0];
                    const dayAvailability = availabilityData[dateString];
                    
                    if (dayAvailability && dayAvailability.price > 0) {
                        totalSpecialPrice += dayAvailability.price;
                        hasSpecialPricing = true;
                        specialNights++;
                    } else {
                        totalSpecialPrice += originalPrice;
                    }
                    
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                
                if (hasSpecialPricing && specialNights > 0) {
                    // Calculate average price per night with special pricing
                    const avgPricePerNight = totalSpecialPrice / nights;
                    
                    // Update price display
                    $priceDisplay.text(currency + avgPricePerNight.toFixed(2));
                    
                    // Add or show special pricing badge under price
                    if (!$specialBadge.length) {
                        $card.find('.aiohm-booking-event-price-section .aiohm-price-container').append(
                            '<div class="aiohm-special-pricing-badge">High Season</div>'
                        );
                    } else {
                        $specialBadge.show();
                    }
                    
                    // Store the special pricing information
                    $checkbox.attr('data-special-price', avgPricePerNight.toFixed(2));
                    $checkbox.attr('data-special-nights', specialNights);
                } else {
                    // Restore original pricing
                    $priceDisplay.text(currency + originalPrice.toFixed(2));
                    
                    // Hide special pricing badge
                    if ($specialBadge.length) {
                        $specialBadge.hide();
                    }
                    
                    // Remove special pricing data
                    $checkbox.removeAttr('data-special-price');
                    $checkbox.removeAttr('data-special-nights');
                }
            });
        },

        // Generate HTML for legend replacement
        generateLegendReplacementHTML: function(dayData, dateString) {
            // Get accommodation type from WordPress localized data
            var accommodationType = window.aiohm_booking_data && window.aiohm_booking_data.accommodation_type ?
                window.aiohm_booking_data.accommodation_type : 'Unit';

            // Format date according to WordPress date format settings
            var formattedDate = this.formatDate(dateString);

            // Get unit data from dayData
            var units = dayData.units || {};
            var totalUnits = units.total || 0;
            var freeUnits = units.available || 0; // Use calculated available count from backend
            var bookedUnits = units.booked || 0;
            var pendingUnits = units.pending || 0;
            var externalUnits = units.external || 0;
            var blockedUnits = units.blocked || 0;

            // Create 3-column layout
            var html = '';

            html += '<div style="display: flex; align-items: flex-start; justify-content: space-between; font-size: 13px !important; line-height: 1.4 !important;">';

            // Column 1: Date only (2 lines)
            html += '<div style="flex: 1; margin-right: 20px; font-weight: bold; color: #666; font-size: 13px !important;">';
            html += 'Date:<br>' + formattedDate;
            html += '</div>';

            // Column 2: Event Flags (inline)
            html += '<div style="flex: 1; margin-right: 20px; font-size: 13px !important;">';
            html += '<strong style="color: #666; font-size: 13px !important;">Event Flags:</strong> ';

            var eventItems = [];
            if (dayData.is_private_event) {
                eventItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;">Private Events</span>');
            }
            if (dayData.badges && dayData.badges.special) {
                eventItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;">High Season</span>');
            }

            if (eventItems.length > 0) {
                html += eventItems.join('');
            } else {
                html += '<span style="color: #999; font-size: 13px !important;">None</span>';
            }
            html += '</div>';

            // Column 3: Booking Status with Total Units (inline)
            html += '<div style="flex: 2; font-size: 13px !important;">';
            html += '<strong style="color: #666; font-size: 13px !important;">Total ' + accommodationType + 's: ' + totalUnits + '</strong><br>';

            var statusItems = [];

            if (freeUnits > 0) {
                statusItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;"><span class="legend-color free"></span>Free: ' + freeUnits + '</span>');
            }
            if (bookedUnits > 0) {
                statusItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;"><span class="legend-color booked"></span>Booked: ' + bookedUnits + '</span>');
            }
            if (pendingUnits > 0) {
                statusItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;"><span class="legend-color pending"></span>Pending: ' + pendingUnits + '</span>');
            }
            if (externalUnits > 0) {
                statusItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;"><span class="legend-color external"></span>External: ' + externalUnits + '</span>');
            }
            if (blockedUnits > 0) {
                statusItems.push('<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;"><span class="legend-color blocked"></span>Blocked: ' + blockedUnits + '</span>');
            }

            // If all units are free, show that explicitly
            if (totalUnits > 0 && freeUnits === totalUnits) {
                statusItems = ['<span style="display: inline-block; margin-right: 10px; font-size: 13px !important;"><span class="legend-color free"></span>All Free (' + totalUnits + ')</span>'];
            }

            html += statusItems.join('');
            html += '</div>';

            html += '</div>';

            return html;
        },

        // Format date according to user's global settings
        formatDate: function(dateString) {
            // Get date format from WordPress localized data
            var dateFormat = window.aiohm_booking_data && window.aiohm_booking_data.date_format ?
                window.aiohm_booking_data.date_format : 'd/m/Y';

            // Parse the input date (expected format: YYYY-MM-DD)
            var dateParts = dateString.split('-');
            if (dateParts.length !== 3) {
                return dateString; // Return as-is if format is unexpected
            }

            var year = dateParts[0];
            var month = dateParts[1];
            var day = dateParts[2];

            // Format according to user's preference
            switch(dateFormat) {
                case 'd/m/Y':
                    return day + '/' + month + '/' + year;
                case 'm/d/Y':
                    return month + '/' + day + '/' + year;
                case 'Y-m-d':
                    return year + '-' + month + '-' + day;
                case 'd.m.Y':
                    return day + '.' + month + '.' + year;
                default:
                    // Fallback to d/m/Y format
                    return day + '/' + month + '/' + year;
            }
        },

        // Handle date selection
        handleDateSelection: function($dayElement, $calendar) {
            var self = this;
            var dateString = $dayElement.data('date');
            var $form = $calendar.closest('form');

            // Prevent selection of past dates, disabled dates, or unavailable dates
            if ($dayElement.hasClass('disabled') || $dayElement.hasClass('past') || $dayElement.hasClass('empty') || $dayElement.hasClass('unavailable')) {
                return false;
            }
            
            // Double-check that the date is not in the past
            if (dateString) {
                var selectedDate = new Date(dateString);
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    return false;
                }
            }

            // Find the form inputs to determine if we're selecting check-in or check-out
            var $checkinInput = $form.find('input[name="checkin_date"]');
            var $checkoutInput = $form.find('input[name="checkout_date"]');
            var checkinValue = $checkinInput.val();
            var checkoutValue = $checkoutInput.val();
            
            // Determine if this is a checkout selection
            var isCheckoutSelection = checkinValue && !checkoutValue;
            
            // Prevent selection of fully booked/blocked/pending/external days ONLY for check-in
            // Allow fully booked days for checkout since guests are leaving (no availability needed)
            if (!isCheckoutSelection && (
                $dayElement.hasClass('fully-booked') ||
                $dayElement.hasClass('fully-pending') ||
                $dayElement.hasClass('fully-blocked') ||
                $dayElement.hasClass('fully-external'))) {
                // Show a message to the user
                var statusText = 'unavailable';
                if ($dayElement.hasClass('fully-booked')) statusText = 'fully booked';
                else if ($dayElement.hasClass('fully-pending')) statusText = 'pending confirmation';
                else if ($dayElement.hasClass('fully-blocked')) statusText = 'blocked';
                else if ($dayElement.hasClass('fully-external')) statusText = 'unavailable (external booking)';

                alert('This date is ' + statusText + ' for check-in. Please select a different check-in date.');
                return false; // Prevent further processing
            }

            // Check for private event restrictions
            if ($dayElement.hasClass('aiohm-private-event-date') ||
                $dayElement.find('.aiohm-private-badge').length > 0) {
                // Check if user has selected individual rooms (not book all)
                var selectedIndividualUnits = $('.unit-checkbox:checked, .accommodation-checkbox:checked').not('#private_all_checkbox').length;
                var isBookAllSelected = $('#private_all_checkbox').is(':checked');

                if (selectedIndividualUnits > 0 && !isBookAllSelected) {
                    alert('This date has a private event. You can only book the entire property on private event dates. Please select "Book Entire Property" option or choose different dates.');
                    return false;
                }
            }

            // Find the form inputs - they may have different names
            var $checkinInput = $form.find('input[name="checkin_date"]');
            var $checkoutInput = $form.find('input[name="checkout_date"]');
            var $durationInput = $form.find('#stay_duration');

            // Display elements
            var $checkinDisplay = $form.find('#checkinDisplayText, #checkinDisplay, #pricingCheckinDisplay').first();
            var $checkoutDisplay = $form.find('#checkoutDisplay, #pricingCheckoutDisplay').first();

            // Check if this is the first date selection (check-in)
            if (!$checkinInput.val() || (new Date(dateString) < new Date($checkinInput.val()))) {
                // Set check-in date
                $checkinInput.val(dateString).trigger('change');
                $checkoutInput.val(''); // Clear checkout if setting new checkin

                // Update display
                if ($checkinDisplay.length) {
                    $checkinDisplay.text(this.formatDisplayDate(dateString));
                }
                if ($checkoutDisplay.length) {
                    $checkoutDisplay.text('Select check-out date');
                }

                // Update visual selection
                this.updateCalendarSelection($calendar, dateString, null);
                
                // Add class to indicate check-in is selected (enables fully booked checkout dates)
                $form.addClass('has-checkin-selected');
            } else if (!$checkoutInput.val()) {
                var checkinDate = new Date($checkinInput.val());
                var selectedDate = new Date(dateString);

                if (selectedDate <= checkinDate) {
                    // If selected date is before or same as checkin, reset to new checkin
                    $checkinInput.val(dateString).trigger('change');
                    $checkoutInput.val('');
                    if ($checkinDisplay.length) {
                        $checkinDisplay.text(this.formatDisplayDate(dateString));
                    }
                    if ($checkoutDisplay.length) {
                        $checkoutDisplay.text('Select check-out date');
                    }
                    this.updateCalendarSelection($calendar, dateString, null);
                } else {
                    // Set check-out date
                    $checkoutInput.val(dateString);
                    if ($checkoutDisplay.length) {
                        $checkoutDisplay.text(this.formatDisplayDate(dateString));
                    }

                    // Calculate and update stay duration
                    var timeDiff = selectedDate.getTime() - checkinDate.getTime();
                    var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

                    if ($durationInput.length) {
                        // Prevent circular trigger by temporarily unbinding change events
                        $durationInput.off('change.calendar-update input.calendar-update');
                        $durationInput.val(daysDiff);

                        // Rebind after a short delay
                        setTimeout(function() {
                            $durationInput.on('change.calendar-update input.calendar-update', function() {
                                AIOHM_Booking_Shortcode.updateCalendarFromDuration($form);
                                // Update pricing when duration changes
                                if (typeof updatePricingDisplay === 'function') {
                                    updatePricingDisplay();
                                }
                            });
                        }, 50);
                    }

                    // Update visual selection with duration span
                    this.updateCalendarSelection($calendar, $checkinInput.val(), dateString);
                    
                    // Remove has-checkin-selected class when checkout is selected (booking complete)
                    $form.removeClass('has-checkin-selected');

                    // Trigger availability update since dates changed
                    AIOHM_Booking_Shortcode.updateAccommodationAvailability();
                    
                    // Update special pricing for accommodations based on selected dates
                    this.updateAccommodationCardPricing($checkinInput.val(), dateString);
                    
                    if (window.AIOHMBookingPricingSummary && window.AIOHMBookingPricingSummary.updateAccommodationSpecialPricing) {
                        window.AIOHMBookingPricingSummary.updateAccommodationSpecialPricing($checkinInput.val(), dateString);
                    }

                    // Trigger accommodation selection update since both dates are now selected
                    setTimeout(function() {
                        AIOHM_Booking_Shortcode.handleDateChange();
                    }, 10);
                }
            } else {
                // Reset selection - start over with new check-in
                $checkinInput.val(dateString).trigger('change');
                $checkoutInput.val('');

                if ($checkinDisplay.length) {
                    $checkinDisplay.text(this.formatDisplayDate(dateString));
                }
                if ($checkoutDisplay.length) {
                    $checkoutDisplay.text('Select check-out date');
                }

                // Update visual selection
                this.updateCalendarSelection($calendar, dateString, null);
                
                // Add class to indicate check-in is selected (enables fully booked checkout dates)
                $form.addClass('has-checkin-selected');
            }
        },

        // Update visual calendar selection with circles only
        updateCalendarSelection: function($calendar, checkinDate, checkoutDate) {
            var $grid = $calendar.find('#calendarGrid');

            // Clear all selection classes
            $grid.find('.aiohm-calendar-date').removeClass('selected-checkin selected-checkout');

            if (checkinDate) {
                var $checkinElement = $grid.find('.aiohm-calendar-date[data-date="' + checkinDate + '"]');
                $checkinElement.addClass('selected-checkin');
            }

            if (checkoutDate) {
                var $checkoutElement = $grid.find('.aiohm-calendar-date[data-date="' + checkoutDate + '"]');
                $checkoutElement.addClass('selected-checkout');
            }
        },

        // Format date for display
        formatDisplayDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            });
        },

        // Utility functions
        formatPrice: function(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },

        formatDate: function(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(date));
        },

        // Initialize booking form functionality
        initBookingForm: function($form) {
            var self = this;

            // Calendar initialization handled by main initCalendar function

            // Initialize quantity selectors
            // First unbind any existing handlers to prevent duplicates
            $form.find('.qty-btn').off('click');
            $form.find('.qty-btn').on('click', function() {
                var $btn = $(this);
                var $input = $btn.siblings('.qty-input');
                var target = $btn.data('target');
                var current = parseInt($input.val()) || 0;
                var min = parseInt($input.attr('min')) || 0;
                var max = parseInt($input.attr('max')) || 999;

                if ($btn.hasClass('qty-plus') && current < max) {
                    $input.val(current + 1).trigger('change');
                } else if ($btn.hasClass('qty-minus') && current > min) {
                    $input.val(current - 1).trigger('change');
                }
                
                // Special handling for duration changes - update calendar immediately
                if ($input.attr('id') === 'stay_duration') {
                    setTimeout(function() {
                        self.updateCalendarFromDuration($form);
                        // Also update simple checkout display if available
                        if (typeof self.updateCheckoutDisplay === 'function') {
                            self.updateCheckoutDisplay();
                        }
                        // Trigger accommodation availability update
                        AIOHM_Booking_Shortcode.updateAccommodationAvailability();
                        
                        // Trigger change events on hidden fields to notify all systems
                        $form.find('#checkoutHidden').trigger('change');
                        
                        // Also trigger handleDateChange to check for date updates
                        setTimeout(function() {
                            self.handleDateChange();
                        }, 10);
                    }, 50);
                }
            });

            // Listen for quantity input changes to update pricing
            $form.find('.qty-input').on('change input', function() {
                updatePricingDisplay();
            });

            // Listen for duration changes to update calendar visual selection
            // First unbind any existing handlers to prevent duplicates
            $form.find('#stay_duration').off('change.calendar-update input.calendar-update');
            $form.find('#stay_duration').on('change.calendar-update input.calendar-update', function() {
                self.updateCalendarFromDuration($form);
                updatePricingDisplay(); // Update pricing when duration changes
            });

            // Listen for guest count changes to update pricing
            $form.find('#guests_qty').on('change input', function() {
                updatePricingDisplay(); // Update pricing when guest count changes
            });
        },

        // Update calendar visual selection when duration is changed
        updateCalendarFromDuration: function($form) {
            var $calendar = $form.find('.aiohm-booking-calendar-container');
            var checkinDate = $form.find('input[name="checkin_date"]').val();
            var duration = parseInt($form.find('#stay_duration').val()) || 1;

            // If no check-in date is set, automatically set check-out based on today + duration
            if (!checkinDate && $calendar.length) {
                var today = new Date();
                var checkoutDate = new Date(today);
                checkoutDate.setDate(today.getDate() + duration);
                
                var checkoutDateString = checkoutDate.getFullYear() + '-' +
                                       ('0' + (checkoutDate.getMonth() + 1)).slice(-2) + '-' +
                                       ('0' + checkoutDate.getDate()).slice(-2);
                
                // Set the check-out date in all possible fields
                $form.find('input[name="checkout_date"], #checkoutHidden').val(checkoutDateString).trigger('change');
                
                // Update check-out display
                var $checkoutDisplay = $form.find('#checkoutDisplay, #pricingCheckoutDisplay').first();
                if ($checkoutDisplay.length) {
                    $checkoutDisplay.text(this.formatDisplayDate(checkoutDateString));
                }
                return; // Exit early since we don't have check-in set
            }

            if (checkinDate && $calendar.length) {
                // Calculate checkout date based on duration
                var startDate = new Date(checkinDate);
                var endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + duration);

                var checkoutDateString = endDate.getFullYear() + '-' +
                                       ('0' + (endDate.getMonth() + 1)).slice(-2) + '-' +
                                       ('0' + endDate.getDate()).slice(-2);

                // Update checkout input
                $form.find('input[name="checkout_date"], #checkoutHidden').val(checkoutDateString).trigger('change');

                // Update display
                var $checkoutDisplay = $form.find('#checkoutDisplay, #pricingCheckoutDisplay').first();
                if ($checkoutDisplay.length) {
                    $checkoutDisplay.text(this.formatDisplayDate(checkoutDateString));
                }

                // Update visual selection
                this.updateCalendarSelection($calendar, checkinDate, checkoutDateString);

                // Trigger date change check
                setTimeout(function() {
                    AIOHM_Booking_Shortcode.handleDateChange();
                }, 10);
            }
        },

        // Simple calendar implementation
        initSimpleCalendar: function($calendar) {
            // Skip widget calendars to avoid conflicts
            if ($calendar.hasClass('widget-calendar') || $calendar.data('widget-calendar')) {
                return;
            }

            var self = this;
            var currentDate = new Date();
            var currentMonth = currentDate.getMonth();
            var currentYear = currentDate.getFullYear();
            var selectedDate = null;

            function renderCalendar(month, year) {
                var firstDay = self.getMondayBasedDay(new Date(year, month, 1));
                var daysInMonth = new Date(year, month + 1, 0).getDate();
                var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];

                // Update month/year display
                $calendar.find('.calendar-month-year').text(monthNames[month] + ' ' + year);

                // Clear and build calendar grid
                var $grid = $calendar.find('.calendar-grid');
                $grid.empty();

                // Add day headers starting with Monday
                var dayHeaders = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                dayHeaders.forEach(function(day) {
                    $grid.append('<div class="calendar-day-header">' + day + '</div>');
                });

                // Add empty cells for days before month starts
                for (var i = 0; i < firstDay; i++) {
                    $grid.append('<div class="calendar-day empty"></div>');
                }

                // Add days of the month
                for (var day = 1; day <= daysInMonth; day++) {
                    var $dayEl = $('<div class="calendar-day">' + day + '</div>');
                    var dayDate = new Date(year, month, day);

                    // Disable past dates
                    if (dayDate < new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate())) {
                        $dayEl.addClass('past');
                    } else {
                        $dayEl.addClass('available');
                        $dayEl.on('click', function() {
                            var clickedDay = $(this).text();
                            selectedDate = new Date(year, month, clickedDay);

                            // Update selected state
                            $grid.find('.calendar-day').removeClass('selected');
                            $(this).addClass('selected');

                            // Update hidden date input
                            var formattedDate = selectedDate.getFullYear() + '-' +
                                String(selectedDate.getMonth() + 1).padStart(2, '0') + '-' +
                                String(selectedDate.getDate()).padStart(2, '0');
                            $('#checkinHidden').val(formattedDate).trigger('change');

                            // Update display text
                            var displayDate = selectedDate.toLocaleDateString('en-US', {
                                weekday: 'short',
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                            $('#checkinDisplay').text(displayDate);

                            // Update checkout display
                            self.updateCheckoutDisplay();

                            // Trigger accommodation availability check after checkout is updated
                            setTimeout(function() {
                                self.handleDateChange();
                            }, 10);
                        });
                    }

                    $grid.append($dayEl);
                }
            }

            // Initialize calendar
            renderCalendar(currentMonth, currentYear);

            // Navigation buttons
            $calendar.find('#prevMonth').on('click', function() {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentMonth, currentYear);
            });

            $calendar.find('#nextMonth').on('click', function() {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentMonth, currentYear);
            });

            // Duration change handler - only for simple calendar context
            if ($calendar.find('#checkinHidden').length && $calendar.find('#checkoutDisplay').length) {
                $('#stay_duration').off('change.simple-calendar');
                $('#stay_duration').on('change.simple-calendar', function() {
                    self.updateCheckoutDisplay();
                });
            }
        },

        updateCheckoutDisplay: function() {
            var checkinDate = $('#checkinHidden').val();
            var duration = parseInt($('#stay_duration').val()) || 1;

            // If no check-in date is set, automatically set check-out based on today + duration
            if (!checkinDate) {
                var today = new Date();
                var checkoutDate = new Date(today);
                checkoutDate.setDate(today.getDate() + duration);
                
                var checkoutFormatted = checkoutDate.getFullYear() + '-' +
                    String(checkoutDate.getMonth() + 1).padStart(2, '0') + '-' +
                    String(checkoutDate.getDate()).padStart(2, '0');
                
                // Set the check-out date in both possible hidden fields
                $('#checkoutHidden, input[name="checkout_date"]').val(checkoutFormatted).trigger('change');
                
                // Update check-out display
                var formattedCheckout = checkoutDate.toLocaleDateString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
                $('#checkoutDisplay, #pricingCheckoutDisplay').text(formattedCheckout);
                return; // Exit early since we don't have check-in set
            }

            if (checkinDate) {
                var checkin = new Date(checkinDate);
                var checkout = new Date(checkin);
                checkout.setDate(checkin.getDate() + duration);

                var formattedCheckout = checkout.toLocaleDateString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                $('#checkoutDisplay, #pricingCheckoutDisplay').text(formattedCheckout);

                // Update hidden checkout field
                var checkoutFormatted = checkout.getFullYear() + '-' +
                    String(checkout.getMonth() + 1).padStart(2, '0') + '-' +
                    String(checkout.getDate()).padStart(2, '0');
                $('#checkoutHidden, input[name="checkout_date"]').val(checkoutFormatted).trigger('change');
            } else {
                $('#checkoutDisplay, #pricingCheckoutDisplay').text('Select check-in first');
                $('#checkoutHidden, input[name="checkout_date"]').val('').trigger('change');
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AIOHM_Booking_Shortcode.init();
        initNewDesignFeatures();
        
        // Force unchecked state for all event checkboxes on page load
        setTimeout(function() {
            $('.aiohm-booking-event-checkbox, .aiohm-booking-event-radio').prop('checked', false);
        }, 100);

        // Fallback: ensure pricing section is visible
        setTimeout(function() {
            $('.pricing-section').show();
        }, 100);

        // Ensure colors are applied after all elements are loaded
        setTimeout(function() {
            AIOHM_Booking_Shortcode.applyCalendarColors();
        }, 200);
        
        // Dispatch initial accommodation selection event for pricing summary
        setTimeout(function() {
            dispatchAccommodationSelectionEvent();
        }, 300);
    });

    // Handle dynamic content loading with MutationObserver
    if (window.MutationObserver) {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    $(mutation.addedNodes).each(function() {
                        var $node = $(this);
                        if ($node.hasClass && ($node.hasClass('aiohm-booking-shortcode-wrapper') ||
                            $node.find('.aiohm-booking-shortcode-wrapper').length ||
                            $node.hasClass('aiohm-booking-sandwich-form') ||
                            $node.find('.aiohm-booking-sandwich-form').length)) {
                            AIOHM_Booking_Shortcode.initializeShortcodes();
                        }
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Expose to global scope for other modules
    window.AIOHM_Shortcode = AIOHM_Booking_Shortcode;

    /**
     * NEW DESIGN FUNCTIONALITY
     * Unit selection, date handling, and pricing calculations
     */

    // Initialize new design features
    function initNewDesignFeatures() {
        // Date selection functionality
        initDateSelection();

        // Unit selection and pricing
        initUnitSelection();

        // Exclusive booking logic
        initExclusiveBooking();

        // Pricing calculations
        updatePricingDisplay();
    }

    // Date Selection Logic
    function initDateSelection() {
        const checkinInput = $('input[name="checkin_date_display"]');
        const checkoutInput = $('input[name="checkout_date_display"]');
        const checkoutError = $('#checkout-error');

        // Handle check-in date selection
        checkinInput.on('change', function() {
            const checkinDate = new Date($(this).val());

            if (checkinDate) {
                // Set minimum check-out date to day after check-in
                const minCheckout = new Date(checkinDate);
                minCheckout.setDate(minCheckout.getDate() + 1);

                checkoutInput.attr('min', minCheckout.toISOString().split('T')[0]);
                checkoutInput.prop('disabled', false);

                // Hide error message
                checkoutError.hide();

                // Update hidden inputs
                $('#checkinHidden').val($(this).val()).trigger('change');
            }
        });

        // Handle check-out date selection
        checkoutInput.on('change', function() {
            const checkoutDate = new Date($(this).val());
            const checkinDate = new Date(checkinInput.val());

            if (checkoutDate && checkinDate && checkoutDate <= checkinDate) {
                // Show error if checkout is not after checkin
                checkoutError.show();
                $(this).val('');
            } else {
                checkoutError.hide();
                // Update hidden inputs
                $('#checkoutHidden').val($(this).val()).trigger('change');

                // Recalculate pricing based on nights
                updatePricingDisplay();

                // Validate private event conflicts
                validatePrivateEventConflicts();
            }
        });

        // Also listen for changes to hidden inputs (used by calendar widget)
        $('#checkinHidden, #checkoutHidden').on('change', function() {
            updatePricingDisplay();
            // Validate private event conflicts when dates change
            validatePrivateEventConflicts();
        });
    }

    // Unit Selection Logic
    function initUnitSelection() {
        // Handle room checkbox changes (both shortcode and widget selectors)
        $('.unit-checkbox, .accommodation-checkbox').on('change', function() {
            const $roomOption = $(this).closest('.room-option, .aiohm-booking-accommodation-card');
            const isChecked = $(this).is(':checked');

            // Update visual state for browsers that don't support :has()
            if (isChecked) {
                $roomOption.addClass('room-selected');
            } else {
                $roomOption.removeClass('room-selected');
            }

            // Check for private event conflicts with current date selection
            validatePrivateEventConflicts();

            updatePricingDisplay();
            updateExclusiveBookingState();
            
            // Dispatch accommodation selection event for pricing summary
            dispatchAccommodationSelectionEvent();
            
            // Refresh price tooltips when accommodation selection changes
            refreshCalendarTooltips();
        });

        // Visual feedback for room selection
        $('.unit-checkbox-container').on('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = $(this).find('.unit-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });

        // Initialize visual states on page load
        $('.unit-checkbox:checked, .accommodation-checkbox:checked').each(function() {
            $(this).closest('.room-option, .aiohm-booking-accommodation-card').addClass('room-selected');
        });
    }

    // Exclusive Booking Logic
    function initExclusiveBooking() {
        const exclusiveCheckbox = $('#private_all_checkbox');
        const roomCheckboxes = $('.unit-checkbox, .accommodation-checkbox');

        // When exclusive booking is selected
        exclusiveCheckbox.on('change', function() {
            if ($(this).is(':checked')) {
                // Check all room checkboxes
                roomCheckboxes.prop('checked', true);
                // Disable individual room selection
                roomCheckboxes.prop('disabled', true);
                $('.room-option, .aiohm-booking-accommodation-card').addClass('disabled-selection');
            } else {
                // Enable individual room selection
                roomCheckboxes.prop('disabled', false);
                $('.room-option, .aiohm-booking-accommodation-card').removeClass('disabled-selection');
            }
            updatePricingDisplay();
        });
    }

    // Validate private event conflicts
    function validatePrivateEventConflicts() {
        const checkinDate = $('input[name="checkin_date"]').val() || $('#checkinHidden').val();
        const checkoutDate = $('input[name="checkout_date"]').val() || $('#checkoutHidden').val();

        if (!checkinDate || !checkoutDate) {
            return; // No dates selected yet
        }

        // Check if any selected dates have private events
        const dateRange = getDateRange(checkinDate, checkoutDate);
        let hasPrivateEventDates = false;
        const privateEventDates = [];

        // Check each date in the range for private events
        dateRange.forEach(function(date) {
            const dateString = formatDate(date);
            const $dayElement = $('.calendar-day[data-date="' + dateString + '"]');
            
            // Check for private events in multiple ways:
            // 1. CSS class (after calendar is rendered)
            // 2. Badge presence
            // 3. Availability data (most reliable)
            let isPrivateEvent = false;
            
            if ($dayElement.hasClass('aiohm-private-event-date') ||
                $dayElement.find('.aiohm-private-badge').length > 0) {
                isPrivateEvent = true;
            }
            
            // Also check cached availability data if available
            if (!isPrivateEvent && window.AIOHM_Booking_Shortcode && window.AIOHM_Booking_Shortcode.cachedAvailability) {
                const availabilityData = window.AIOHM_Booking_Shortcode.cachedAvailability[dateString];
                if (availabilityData && availabilityData.is_private_event) {
                    isPrivateEvent = true;
                }
            }
            
            // Also check tooltip data
            if (!isPrivateEvent) {
                const tooltipData = $dayElement.attr('data-tooltip-data');
                if (tooltipData) {
                    try {
                        const dayData = JSON.parse(tooltipData);
                        if (dayData && dayData.is_private_event) {
                            isPrivateEvent = true;
                        }
                    } catch (e) {
                        // Silent error handling
                    }
                }
            }

            if (isPrivateEvent) {
                hasPrivateEventDates = true;
                privateEventDates.push(date.toLocaleDateString());
            }
        });

        if (hasPrivateEventDates) {
            const selectedIndividualUnits = $('.unit-checkbox:checked, .accommodation-checkbox:checked').not('#private_all_checkbox').length;
            const isBookAllSelected = $('#private_all_checkbox').is(':checked');
            const totalUnits = $('.unit-checkbox, .accommodation-checkbox').not('#private_all_checkbox').length;

            // Automatically disable individual room selection for private event dates
            $('.unit-checkbox, .accommodation-checkbox').not('#private_all_checkbox').each(function() {
                $(this).prop('disabled', true);
                $(this).prop('checked', false);
                $(this).closest('.aiohm-booking-accommodation-card').addClass('private-event-disabled');
            });

            // Show message about private event restrictions
            if (!isBookAllSelected) {
                // showPrivateEventWarning(privateEventDates); // Commented out - user requested to remove this banner
                
                // Auto-check the "Book Entire Property" option if it exists
                if ($('#private_all_checkbox').length > 0) {
                    $('#private_all_checkbox').prop('checked', true);
                    $('#private_all_checkbox').trigger('change');
                }
            }
        } else {
            // Re-enable individual room selection if no private events
            $('.unit-checkbox, .accommodation-checkbox').not('#private_all_checkbox').each(function() {
                $(this).prop('disabled', false);
                $(this).closest('.aiohm-booking-accommodation-card').removeClass('private-event-disabled');
            });
            
            // Remove any existing warning
            $('.private-event-warning').remove();
        }

        return true;
    }

    // Show private event warning
    function showPrivateEventWarning(privateDates) {
        const formattedDates = privateDates.join(', ');
        const message = `Your selected dates include private event dates (${formattedDates}). ` +
                       `Individual room selection has been disabled. You must book the entire property for private event dates.`;

        // Remove existing warning
        $('.private-event-warning').remove();

        // Create warning element
        const $warning = $('<div class="private-event-warning" style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin: 15px 0; border-radius: 6px; font-size: 14px; line-height: 1.5;">' +
                          '<div style="display: flex; align-items: flex-start; gap: 10px;">' +
                          '<div>' +
                          '<strong>Private Event Restriction</strong><br>' + 
                          message + 
                          '<br><small style="color: #6c5f47; margin-top: 8px; display: block;">The "Book Entire Property" option has been automatically selected for you.</small>' +
                          '</div>' +
                          '</div>' +
                          '</div>');

        // Insert warning after accommodation selection or before submit button
        if ($('.accommodation-selection').length) {
            $('.accommodation-selection').after($warning);
        } else if ($('.aiohm-booking-accommodation-cards').length) {
            $('.aiohm-booking-accommodation-cards').before($warning);
        } else {
            $('.submit-booking-btn, button[type="submit"]').before($warning);
        }

        // Auto-scroll to warning
        $warning[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Get date range array
    function getDateRange(startDate, endDate) {
        const dates = [];
        const current = new Date(startDate);
        const end = new Date(endDate);

        while (current < end) {
            dates.push(new Date(current));
            current.setDate(current.getDate() + 1);
        }

        return dates;
    }

    // Format date helper
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Update exclusive booking state based on room selection
    function updateExclusiveBookingState() {
        const roomCheckboxes = $('.unit-checkbox, .accommodation-checkbox');
        const exclusiveCheckbox = $('#private_all_checkbox');
        const totalUnits = roomCheckboxes.length;
        const selectedUnits = roomCheckboxes.filter(':checked').length;

        // If all rooms are selected, suggest exclusive booking
        if (selectedUnits === totalUnits && totalUnits > 1) {
            if (!exclusiveCheckbox.is(':checked')) {
                // Highlight exclusive option
                $('.exclusive-booking-container').addClass('suggest-exclusive');
            }
        } else {
            $('.exclusive-booking-container').removeClass('suggest-exclusive');
        }
    }

    // Pricing Calculation and Display
    function updatePricingDisplay() {
        // Support both shortcode and widget selectors - prioritize widget selectors
        const checkinInput = $('#checkinHidden, input[name="checkin_date"]').first();
        const checkoutInput = $('#checkoutHidden, input[name="checkout_date"]').first();
        const selectedUnits = $('.accommodation-checkbox:checked, .unit-checkbox:checked');
        const exclusiveCheckbox = $('#private_all_checkbox');
        const pricingSummary = $('.aiohm-pricing-container');

        // Get settings from pricing summary data
        const currency = pricingSummary.data('currency');
        const depositPercent = pricingSummary.data('deposit-percent') || 30;
        const earlybirdDays = parseInt(pricingSummary.data('earlybird-days')) || 30;

        // Initialize date variables
        let checkinDate = null;
        let checkoutDate = null;

        let totalPrice = 0;
        let earlybirdTotal = 0;
        let regularTotal = 0;
        let nights = 1;
        let isEarlybird = false;

        // Calculate number of nights
        if (checkinInput.val() && checkoutInput.val()) {
            checkinDate = new Date(checkinInput.val());
            checkoutDate = new Date(checkoutInput.val());
            nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
            nights = Math.max(1, nights); // Minimum 1 night
        }

        // Check if booking qualifies for early bird pricing
        if (checkinInput.val()) {
            const today = new Date();
            const daysUntilCheckin = Math.ceil((checkinDate - today) / (1000 * 60 * 60 * 24));
            isEarlybird = daysUntilCheckin >= earlybirdDays;
        }

        // Handle exclusive booking (Book All) vs individual room selection
        if (exclusiveCheckbox.is(':checked')) {
            // Book All is selected - check for special pricing first
            const specialPricePerNight = getSpecialPriceForDateRange(checkinDate, checkoutDate);
            
            if (specialPricePerNight > 0) {
                // Use special pricing for the entire property
                totalPrice = specialPricePerNight * nights;
                regularTotal = totalPrice; // Special pricing replaces regular pricing
                earlybirdTotal = totalPrice; // No early bird discount on special pricing
            } else {
                // No special pricing, calculate price for ALL available accommodations
                $('.accommodation-checkbox').not('#private_all_checkbox').each(function() {
                    const regularPrice = parseFloat($(this).data('price')) || 0;
                    const earlybirdPrice = parseFloat($(this).data('earlybird')) || regularPrice;
                    const specialPrice = parseFloat($(this).data('special-price')) || 0;
                    
                    // Priority: 1. Special pricing (if available), 2. Early bird (if eligible), 3. Regular price
                    let priceToUse = regularPrice;
                    if (specialPrice > 0) {
                        // Use special pricing for special dates
                        priceToUse = specialPrice;
                    } else if (isEarlybird && earlybirdPrice > 0 && earlybirdPrice < regularPrice) {
                        // Use early bird pricing if eligible
                        priceToUse = earlybirdPrice;
                    }
                    
                    totalPrice += priceToUse * nights;
                    regularTotal += regularPrice * nights;
                    earlybirdTotal += earlybirdPrice * nights;
                });
            }
        } else {
            // Individual room selection - only calculate for checked rooms
            selectedUnits.each(function() {
                const regularPrice = parseFloat($(this).data('price')) || 0;
                const earlybirdPrice = parseFloat($(this).data('earlybird')) || regularPrice;
                const specialPrice = parseFloat($(this).data('special-price')) || 0;
                
                // Priority: 1. Special pricing (if available), 2. Early bird (if eligible), 3. Regular price
                let priceToUse = regularPrice;
                if (specialPrice > 0) {
                    // Use special pricing for special dates
                    priceToUse = specialPrice;
                } else if (isEarlybird && earlybirdPrice > 0 && earlybirdPrice < regularPrice) {
                    // Use early bird pricing if eligible
                    priceToUse = earlybirdPrice;
                }
                
                totalPrice += priceToUse * nights;
                regularTotal += regularPrice * nights;
                earlybirdTotal += earlybirdPrice * nights;
            });
        }

        // Calculate savings if early bird applies
        const savings = isEarlybird && earlybirdTotal < regularTotal ? regularTotal - earlybirdTotal : 0;

        // Update display
        const formattedTotal = formatPrice(totalPrice, currency);
        const depositAmount = totalPrice * (depositPercent / 100);
        const formattedDeposit = formatPrice(depositAmount, currency);

        $('.total-amount').text(formattedTotal);
        $('.deposit-amount').text(formattedDeposit);

        // Update accommodation card displays based on special pricing, early bird eligibility, or regular pricing
        $('.accommodation-checkbox').each(function() {
            const $checkbox = $(this);
            const $card = $checkbox.closest('.aiohm-booking-accommodation-card');
            const $priceBadge = $card.find('.aiohm-early-bird-price-badge');
            const $titleBadge = $card.find('.aiohm-early-bird-badge');
            const $specialBadge = $card.find('.aiohm-special-pricing-badge');
            const $priceDisplay = $card.find('.aiohm-booking-event-price');

            const regularPrice = parseFloat($checkbox.data('price')) || 0;
            const earlybirdPrice = parseFloat($checkbox.data('earlybird')) || regularPrice;
            const specialPrice = parseFloat($checkbox.data('special-price')) || 0;

            // Priority: 1. Special pricing (if available), 2. Early bird (if eligible), 3. Regular price
            let displayPrice = regularPrice;
            let badgeType = 'regular';
            
            if (specialPrice > 0) {
                // Use special pricing for special dates
                displayPrice = specialPrice;
                badgeType = 'special';
            } else if (isEarlybird && earlybirdPrice > 0 && earlybirdPrice < regularPrice) {
                // Use early bird pricing if eligible
                displayPrice = earlybirdPrice;
                badgeType = 'earlybird';
            }

            // Show/hide appropriate badges
            if (badgeType === 'special') {
                $specialBadge.show();
                $priceBadge.hide();
                $titleBadge.hide();
            } else if (badgeType === 'earlybird') {
                $priceBadge.show();
                $titleBadge.show();
                $specialBadge.hide();
            } else {
                $priceBadge.hide();
                $titleBadge.hide();
                $specialBadge.hide();
            }

            // Update the displayed price
            if ($priceDisplay.length > 0) {
                const currency = pricingSummary.data('currency');
                $priceDisplay.text(currency + ' ' + displayPrice.toFixed(2));
            }
        });

        // Show/hide early bird pricing row
        const hasSelections = exclusiveCheckbox.is(':checked') || selectedUnits.length > 0;
        if (isEarlybird && earlybirdTotal > 0 && hasSelections && earlybirdTotal < regularTotal) {
            $('.earlybird-row').removeClass('hidden').show();
            $('.earlybird-amount').text(formatPrice(earlybirdTotal, currency));
        } else {
            $('.earlybird-row').addClass('hidden').hide();
        }

        // Show/hide savings row
        if (savings > 0) {
            $('.saving-row').removeClass('hidden').show();
            $('.saving-amount').text(formatPrice(savings, currency));
        } else {
            $('.saving-row').addClass('hidden').hide();
        }

        // Update pricing summary display based on selections
        updatePricingSummaryDisplay(exclusiveCheckbox, selectedUnits, totalPrice, currency, nights);

        // Always show pricing section, but update calculations
        $('.pricing-section').show();
    }
    
    // Update pricing summary display sections
    function updatePricingSummaryDisplay(exclusiveCheckbox, selectedUnits, totalPrice, currency, nights) {
        const $pricingCard = $('.aiohm-pricing-summary-card');
        const $noSelection = $('.aiohm-no-selection-message');
        const $selectedAccommodations = $('.aiohm-selected-accommodations');
        const $accommodationsList = $('.aiohm-accommodations-list');
        
        // Determine if we have selections
        const hasSelections = exclusiveCheckbox.is(':checked') || selectedUnits.length > 0;
        
        if (hasSelections) {
            // Hide "No Items Selected" message
            $noSelection.hide();
            
            // Show selected accommodations section
            $selectedAccommodations.show();
            
            // Add has-selections class to pricing card
            $pricingCard.addClass('has-selections');
            
            // Populate accommodations list - DISABLED: Let pricing summary handle this
            // $accommodationsList.empty();
            
            if (false && exclusiveCheckbox.is(':checked')) { // Disabled
                // Book All is selected - show all accommodations
                $accommodationsList.append(`
                    <div class="aiohm-accommodation-summary-item">
                        <div class="aiohm-item-details">
                            <span class="aiohm-item-name">🏡 Entire Property</span>
                            <span class="aiohm-item-description">All accommodations included</span>
                        </div>
                        <div class="aiohm-item-pricing">
                            <span class="aiohm-item-price">${formatPrice(totalPrice, currency)}</span>
                        </div>
                    </div>
                `);
            } else {
                // Individual selections - DISABLED: Let pricing summary handle this
                // selectedUnits.each(function() {
                //     const $checkbox = $(this);
                //     const $card = $checkbox.closest('.aiohm-booking-event-card');
                //     const roomName = $card.find('.aiohm-booking-event-title').text() || 'Accommodation';
                //     const roomPrice = parseFloat($checkbox.data('price')) || 0;
                //     
                //     $accommodationsList.append(`
                //         <div class="aiohm-accommodation-summary-item">
                //             <div class="aiohm-item-details">
                //                 <span class="aiohm-item-name">${roomName}</span>
                //                 <span class="aiohm-item-description">Per night</span>
                //             </div>
                //             <div class="aiohm-item-pricing">
                //                 <span class="aiohm-item-price">${formatPrice(roomPrice, currency)}</span>
                //             </div>
                //         </div>
                //     `);
                // });
            }
        } else {
            // No selections - show "No Items Selected" message
            $noSelection.show();
            $selectedAccommodations.hide();
            $pricingCard.removeClass('has-selections');
        }
    }

    // Format price for display
    function formatPrice(amount, currency) {
        return currency + ' ' + amount.toFixed(2);
    }

    // Add CSS for visual feedback
    const newDesignStyles = `
    <style>
    .room-option.disabled-selection {
        opacity: 0.6;
        pointer-events: none;
    }

    .exclusive-booking-container.suggest-exclusive {
        animation: suggestPulse 2s infinite;
    }

    @keyframes suggestPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .pricing-section {
        display: block !important;
    }
    </style>
    `;

    // Inject styles
    $('head').append(newDesignStyles);

    // Calendar Header Controls Functionality
    $(document).ready(function() {

        // Handle period navigation arrows (< >)
        $(document).on('click', '.aiohm-period-prev, .aiohm-period-next', function(e) {
            e.preventDefault();

            const isPrev = $(this).hasClass('aiohm-period-prev');
            const currentUrl = new URL(window.location.href);

            // Get current period type
            const periodType = $('#calendar-period').val() || 'week';

            // Handle navigation based on period type
            if (periodType === 'week') {
                const currentWeek = parseInt(currentUrl.searchParams.get('week_offset') || '0');
                const newWeek = isPrev ? currentWeek - 1 : currentWeek + 1;
                currentUrl.searchParams.set('week_offset', newWeek);
            } else if (periodType === 'month') {
                const currentMonth = parseInt(currentUrl.searchParams.get('month_offset') || '0');
                const newMonth = isPrev ? currentMonth - 1 : currentMonth + 1;
                currentUrl.searchParams.set('month_offset', newMonth);
            }

            // Reload page with new parameters
            window.location.href = currentUrl.toString();
        });

        // Handle period dropdown change
        $(document).on('change', '#calendar-period', function() {
            const periodType = $(this).val();
            const currentUrl = new URL(window.location.href);

            // Clear existing period parameters
            currentUrl.searchParams.delete('week_offset');
            currentUrl.searchParams.delete('month_offset');
            currentUrl.searchParams.delete('custom_start');
            currentUrl.searchParams.delete('custom_end');

            // Set new period type
            currentUrl.searchParams.set('period', periodType);

            // Reload page with new period
            window.location.href = currentUrl.toString();
        });

        // Handle Show button
        $(document).on('click', '.aiohm-show-button', function(e) {
            e.preventDefault();

            const periodType = $('#calendar-period').val();
            const currentUrl = new URL(window.location.href);

            // Set period parameter and reload
            currentUrl.searchParams.set('period', periodType);
            window.location.href = currentUrl.toString();
        });

        // Initialize period selector based on URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const currentPeriod = urlParams.get('period') || 'week';
        $('#calendar-period').val(currentPeriod);

    });

    // Add calendar availability loading method
    AIOHM_Booking_Shortcode.loadCalendarAvailability = function($calendar) {
        const data = {
            action: 'aiohm_get_calendar_availability',
            start_date: this.getCurrentMonthStart(),
            end_date: this.getCurrentMonthEnd(),
            nonce: aiohm_booking_frontend.nonce
        };

        $.ajax({
            url: aiohm_booking_frontend.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    AIOHM_Booking_Shortcode.renderCalendar($calendar, response.data);
                }
            },
            error: function() {
            }
        });
    };

    // Calendar rendering method
    AIOHM_Booking_Shortcode.renderCalendar = function($calendar, data) {
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
                    } else if (dateData.status === 'special_pricing') {
                        statusClass = 'free'; // Keep cell as free, badges will show special pricing
                    } else if (dateData.status === 'private') {
                        statusClass = 'free'; // Keep cell as free, badges will show private event
                    } else {
                        statusClass = dateData.status; // booked, pending, blocked, external
                    }
                } else {
                    statusClass = dateData.status;
                    $date.addClass('disabled');
                }

                // Apply the correct CSS class that matches the CSS selectors
                // Special pricing/high season does NOT affect cell background colors
                if (statusClass === 'free') {
                    $date.addClass('free');
                } else if (statusClass === 'booked') {
                    $date.addClass('booked');
                } else if (statusClass === 'pending') {
                    $date.addClass('pending');
                } else if (statusClass === 'blocked') {
                    $date.addClass('blocked');
                } else if (statusClass === 'external') {
                    $date.addClass('external');
                } else if (statusClass === 'special' || statusClass === 'special_pricing') {
                    // Special pricing only shows badges, not background colors
                    $date.addClass('free'); // Keep cell as free for background color
                } else if (statusClass === 'private') {
                    // Private events only show badges, not background colors
                    $date.addClass('free'); // Keep cell as free for background color
                } else {
                    $date.addClass('free'); // Default to free
                }

                // Add mixed unit indicators for free days with partial bookings
                // Also add fully-occupied classes for days that should be disabled
                if (dateData.units) {
                    const units = dateData.units;
                    const totalUnits = units.total;
                    const bookedUnits = units.booked || 0;
                    const pendingUnits = units.pending || 0;
                    const blockedUnits = units.blocked || 0;
                    const externalUnits = units.external || 0;

                    if (statusClass === 'free') {
                        // Add mixed unit indicators for partially occupied days
                        if (bookedUnits > 0 && bookedUnits < totalUnits) {
                            $date.addClass('has-mixed-units has-booked-units');
                        } else if (pendingUnits > 0 && pendingUnits < totalUnits) {
                            $date.addClass('has-mixed-units has-pending-units');
                        } else if (blockedUnits > 0 && blockedUnits < totalUnits) {
                            $date.addClass('has-mixed-units has-blocked-units');
                        } else if (externalUnits > 0 && externalUnits < totalUnits) {
                            $date.addClass('has-mixed-units has-external-units');
                        }
                    } else {
                        // Add fully-occupied classes for completely unavailable days
                        if (statusClass === 'booked' && bookedUnits === totalUnits) {
                            $date.addClass('fully-booked');
                        } else if (statusClass === 'pending' && pendingUnits === totalUnits) {
                            $date.addClass('fully-pending');
                        } else if (statusClass === 'blocked' && blockedUnits === totalUnits) {
                            $date.addClass('fully-blocked');
                        } else if (statusClass === 'external' && externalUnits === totalUnits) {
                            $date.addClass('fully-external');
                        }
                    }
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
    };

    // Helper methods for date calculation
    AIOHM_Booking_Shortcode.getCurrentMonthStart = function() {
        const now = new Date();
        return (now.getFullYear() + '-' +
               String(now.getMonth() + 1).padStart(2, '0') + '-01');
    };

    AIOHM_Booking_Shortcode.getCurrentMonthEnd = function() {
        const now = new Date();
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        return (lastDay.getFullYear() + '-' +
               String(lastDay.getMonth() + 1).padStart(2, '0') + '-' +
               String(lastDay.getDate()).padStart(2, '0'));
    };

    // Store the last availability check to prevent conflicts
    AIOHM_Booking_Shortcode.lastAvailabilityCheck = null;
    AIOHM_Booking_Shortcode.isUpdatingAccommodationSelection = false;

    // Get unit statuses for multiple units in one call
    AIOHM_Booking_Shortcode.getUnitStatuses = function(unitRequests, date, dayUnits) {
        const unitIds = unitRequests.map(req => req.unitId);

        $.ajax({
            url: (window.aiohm_booking_frontend && window.aiohm_booking_frontend.ajax_url) || '/wp-admin/admin-ajax.php',
            method: 'POST',
            data: {
                action: 'aiohm_get_multiple_unit_statuses',
                unit_ids: unitIds,
                date: date,
                nonce: (window.aiohm_booking_frontend && window.aiohm_booking_frontend.nonce) || ''
            },
            success: function(response) {
                if (response.success && response.data && response.data.statuses) {
                    const statuses = response.data.statuses || {};
                    

                            // Update each card with its specific status
                            unitRequests.forEach(function(req) {
                                const $card = req.card;
                                const unitId = req.unitId;
                                let availabilityStatus = statuses[unitId] || 'free';

                                // Map status names for CSS
                                switch(availabilityStatus) {
                                    case 'special_pricing':
                                        availabilityStatus = 'special';
                                        break;
                                    // Keep other statuses as-is: free, booked, pending, blocked, external
                                }

                                // Set the attribute and store it
                                $card.attr('data-availability', availabilityStatus);
                                $card.data('availability-status', availabilityStatus);

                                // Disable/enable checkbox based on availability
                                const checkbox = $card.find('.accommodation-checkbox');
                                const isBookAllSelected = $('#private_all_checkbox').is(':checked');

                                if (isBookAllSelected) {
                                    // If "Book Entire Property" is selected, keep individual checkboxes disabled
                                    checkbox.prop('disabled', true);
                                    $card.addClass('unavailable');
                                } else if (availabilityStatus === 'free' || availabilityStatus === 'available' || availabilityStatus === 'special') {
                                    checkbox.prop('disabled', false);
                                    $card.removeClass('unavailable');
                                } else {
                                    // Only uncheck if not user-selected
                                    if (!checkbox[0] || !checkbox[0].hasAttribute('data-user-selected')) {
                                        checkbox.prop('disabled', true).prop('checked', false);
                                        $card.addClass('unavailable');
                                    } else {
                                        checkbox.prop('disabled', false);
                                    }
                                }

                                // Add a subtle animation when status changes
                                $card.addClass('status-updating');
                                setTimeout(() => {
                                    $card.removeClass('status-updating');
                                }, 300);
                            });

                    // Ensure styles persist
                    AIOHM_Booking_Shortcode.persistAvailabilityStyles();
                } else {
                    // Fallback: Distribute statuses based on day-level unit counts
                    const units = dayUnits;

                    if (units && units.total > 0) {
                        const totalUnits = units.total;
                        const bookedUnits = units.booked || 0;
                        const pendingUnits = units.pending || 0;
                        const blockedUnits = units.blocked || 0;
                        const externalUnits = units.external || 0;

                        let statusIndex = 0;
                        const statusDistribution = [];

                        // Create distribution array based on unit counts
                        for (let i = 0; i < bookedUnits; i++) statusDistribution.push('booked');
                        for (let i = 0; i < pendingUnits; i++) statusDistribution.push('pending');
                        for (let i = 0; i < blockedUnits; i++) statusDistribution.push('blocked');
                        for (let i = 0; i < externalUnits; i++) statusDistribution.push('external');

                        // Fill remaining slots with 'free'
                        while (statusDistribution.length < totalUnits) {
                            statusDistribution.push('free');
                        }

                        // Shuffle for more realistic appearance
                        for (let i = statusDistribution.length - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [statusDistribution[i], statusDistribution[j]] = [statusDistribution[j], statusDistribution[i]];
                        }

                        // Apply distributed statuses to cards
                        unitRequests.forEach(function(req, index) {
                            const $card = req.card;
                            let availabilityStatus = statusDistribution[index % statusDistribution.length] || 'free';

                            // Map status names for CSS
                            switch(availabilityStatus) {
                                case 'free':
                                    availabilityStatus = 'available';
                                    break;
                                case 'special_pricing':
                                    availabilityStatus = 'special';
                                    break;
                            }

                            // Set the attribute and store it
                            $card.attr('data-availability', availabilityStatus);
                            $card.data('availability-status', availabilityStatus);

                            // Add a subtle animation when status changes
                            $card.addClass('status-updating');
                            setTimeout(() => {
                                $card.removeClass('status-updating');
                            }, 300);
                        });
                    } else {
                        // No unit data available, set all to free
                        unitRequests.forEach(function(req) {
                            const $card = req.card;
                            if (!$card.attr('data-availability')) {
                                $card.attr('data-availability', 'free');
                                $card.data('availability-status', 'free');
                            }
                        });
                    }
                    AIOHM_Booking_Shortcode.persistAvailabilityStyles();
                }
            },
            error: function(xhr, status, error) {
                // AJAX error getting calendar availability
                
                // Do not reset to available on error - preserve existing status
                // Optionally show a subtle indicator that availability check failed
                unitRequests.forEach(function(req) {
                    const $card = req.card;
                    if (!$card.attr('data-availability')) {
                        $card.attr('data-availability', 'free');
                        $card.data('availability-status', 'free');
                    }
                });
                AIOHM_Booking_Shortcode.persistAvailabilityStyles();
            }
        });
    };

    // Update accommodation availability borders based on calendar data
    AIOHM_Booking_Shortcode.updateAccommodationAvailability = function() {
        const checkinDate = $('#checkinHidden').val() || $('input[name="checkin_date"]').val();
        if (!checkinDate) {
            // No check-in date selected, only set cards that don't have availability data
            $('.aiohm-booking-accommodation-card').each(function() {
                const $card = $(this);
                if (!$card.attr('data-availability')) {
                    $card.attr('data-availability', 'free');
                }
                // Enable all checkboxes when no date is selected
                const checkbox = $card.find('.accommodation-checkbox');
                const isBookAllSelected = $('#private_all_checkbox').is(':checked');

                if (isBookAllSelected) {
                    // If "Book Entire Property" is selected, keep individual checkboxes disabled
                    checkbox.prop('disabled', true);
                    $card.addClass('unavailable');
                } else {
                    checkbox.prop('disabled', false);
                    $card.removeClass('unavailable');
                }
            });
            AIOHM_Booking_Shortcode.persistAvailabilityStyles();
            return;
        }

        // Skip if we just checked this date
        if (AIOHM_Booking_Shortcode.lastAvailabilityCheck === checkinDate) {
            return;
        }

        AIOHM_Booking_Shortcode.lastAvailabilityCheck = checkinDate;

        // Get availability data for the selected check-in date
        $.ajax({
            url: (window.aiohm_booking_frontend && window.aiohm_booking_frontend.ajax_url) || '/wp-admin/admin-ajax.php',
            method: 'POST',
            data: {
                action: 'aiohm_get_calendar_availability',
                start_date: checkinDate,
                end_date: checkinDate,
                nonce: (window.aiohm_booking_frontend && window.aiohm_booking_frontend.nonce) || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    const availabilityData = response.data;
                    const dayData = availabilityData[checkinDate];

                    if (dayData) {
                        // Check if we have individual unit data
                        if (dayData.units && dayData.units.total > 0) {
                            // Calculate how many units are available vs booked
                            const totalUnits = dayData.units.total || 0;
                            const freeUnits = dayData.units.free || 0;
                            const bookedUnits = dayData.units.booked || 0;
                            const specialUnits = dayData.units.special || 0;
                            
                            // Check private event status
                            const isPrivateEvent = dayData.is_private_event || false;
                            const eventName = dayData.event_name || '';
                            
                            
                            // Distribute status among accommodation cards based on availability (exclude "Book Entire Property")
                            const $cards = $('.aiohm-booking-accommodation-card').not('.aiohm-booking-book-entire-card');
                            let freeCount = 0;
                            let bookedCount = 0;
                            let specialCount = 0;
                            
                            $cards.each(function(index) {
                                const $card = $(this);
                                const checkbox = $card.find('.accommodation-checkbox');
                                let availabilityStatus = 'free'; // Default to free
                                
                                // Assign status based on available counts
                                // Special events should be marked as special and require all-property booking
                                if (specialCount < specialUnits) {
                                    availabilityStatus = 'special';
                                    specialCount++;
                                } else if (bookedCount < bookedUnits) {
                                    availabilityStatus = 'booked';
                                    bookedCount++;
                                } else if (freeCount < freeUnits) {
                                    availabilityStatus = 'free';
                                    freeCount++;
                                } else {
                                    // If we have more cards than units, mark extras as unavailable
                                    availabilityStatus = 'booked';
                                }
                                
                                
                                // Set the availability status
                                $card.attr('data-availability', availabilityStatus);
                                $card.data('availability-status', availabilityStatus);
                                
                                // Enable/disable checkbox based on availability
                                const isBookAllSelected = $('#private_all_checkbox').is(':checked');
                                // Only require "Book All" for special units or when no free units available
                                const requiresBookAll = isPrivateEvent && (availabilityStatus === 'special' || freeUnits === 0);
                                
                                
                                if (isBookAllSelected) {
                                    // If "Book Entire Property" is selected, disable individual checkboxes
                                    checkbox.prop('disabled', true);
                                    $card.addClass('unavailable');
                                } else if (availabilityStatus === 'special' && isPrivateEvent && !isBookAllSelected) {
                                    // Special units during private events require book all
                                    checkbox.prop('disabled', true);
                                    $card.addClass('unavailable');
                                    $card.attr('title', 'Private event - Book entire property only');
                                } else if (availabilityStatus === 'free') {
                                    checkbox.prop('disabled', false).prop('readonly', false);
                                    $card.removeClass('unavailable').css('pointer-events', 'auto');
                                    // Force re-enable the label click
                                    const $label = $card.find('label');
                                    $label.css('pointer-events', 'auto').css('cursor', 'pointer');
                                } else {
                                    // Booked accommodations are disabled - but preserve user selections
                                    if (!checkbox[0] || !checkbox[0].hasAttribute('data-user-selected')) {
                                        checkbox.prop('disabled', true).prop('checked', false);
                                        $card.addClass('unavailable');
                                    } else {
                                        checkbox.prop('disabled', false);
                                    }
                                }
                            });
                        } else {
                            // Fallback: Use day-level status for all units
                            $('.aiohm-booking-accommodation-card').each(function(index) {
                                const $card = $(this);
                                let availabilityStatus = dayData.status || 'free';

                                // Map status names for CSS
                                switch(availabilityStatus) {
                                    case 'special_pricing':
                                        availabilityStatus = 'special';
                                        break;
                                    // Keep other statuses as-is: free, booked, pending, blocked, external
                                }

                                // Set the attribute and store it
                                $card.attr('data-availability', availabilityStatus);
                                $card.data('availability-status', availabilityStatus);

                                // Disable/enable checkbox based on availability
                                const checkbox = $card.find('.accommodation-checkbox');
                                const isBookAllSelected = $('#private_all_checkbox').is(':checked');

                                if (isBookAllSelected) {
                                    // If "Book Entire Property" is selected, keep individual checkboxes disabled
                                    checkbox.prop('disabled', true);
                                    $card.addClass('unavailable');
                                } else if (availabilityStatus === 'free' || availabilityStatus === 'available' || availabilityStatus === 'special') {
                                    checkbox.prop('disabled', false);
                                    $card.removeClass('unavailable');
                                } else {
                                    // Only uncheck if not user-selected
                                    if (!checkbox[0] || !checkbox[0].hasAttribute('data-user-selected')) {
                                        checkbox.prop('disabled', true).prop('checked', false);
                                        $card.addClass('unavailable');
                                    } else {
                                        checkbox.prop('disabled', false);
                                    }
                                }

                                // Add a subtle animation when status changes
                                $card.addClass('status-updating');
                                setTimeout(() => {
                                    $card.removeClass('status-updating');
                                }, 300);
                            });

                            // Ensure styles persist
                            AIOHM_Booking_Shortcode.persistAvailabilityStyles();
                        }

                        // Ensure styles persist
                        AIOHM_Booking_Shortcode.persistAvailabilityStyles();
                    }
                } else {
                    // Fallback: only set cards that don't have availability data
                    $('.aiohm-booking-accommodation-card').each(function() {
                        const $card = $(this);
                        if (!$card.attr('data-availability')) {
                            $card.attr('data-availability', 'free');
                        }
                    });
                    AIOHM_Booking_Shortcode.persistAvailabilityStyles();
                }
            },
            error: function(xhr, status, error) {
                // Don't reset to available on error - preserve existing status
                // Optionally show a subtle indicator that availability check failed
                $('.aiohm-booking-accommodation-card').each(function() {
                    const $card = $(this);
                    if (!$card.attr('data-availability')) {
                        $card.attr('data-availability', 'free');
                    }
                    // Enable all checkboxes on error to be safe
                    const checkbox = $card.find('.accommodation-checkbox');
                    const isBookAllSelected = $('#private_all_checkbox').is(':checked');

                    if (isBookAllSelected) {
                        // If "Book Entire Property" is selected, keep individual checkboxes disabled
                        checkbox.prop('disabled', true);
                        $card.addClass('unavailable');
                    } else {
                        checkbox.prop('disabled', false);
                        $card.removeClass('unavailable');
                    }
                });
                AIOHM_Booking_Shortcode.persistAvailabilityStyles();
            }
        });
    };

    // Function to persistently maintain availability styles
    AIOHM_Booking_Shortcode.persistAvailabilityStyles = function() {
        $('.aiohm-booking-accommodation-card').each(function() {
            const $card = $(this);
            const currentStatus = $card.attr('data-availability');
            const storedStatus = $card.data('availability-status');

            // Only restore if the attribute is missing but we have a stored status
            if (!currentStatus && storedStatus) {
                $card.attr('data-availability', storedStatus);
            }
            // Update stored status if current status exists
            else if (currentStatus && (!storedStatus || storedStatus !== currentStatus)) {
                $card.data('availability-status', currentStatus);
            }

            // Maintain checkbox disabled state based on availability
            const checkbox = $card.find('.accommodation-checkbox');
            const isBookAllSelected = $('#private_all_checkbox').is(':checked');

            if (isBookAllSelected) {
                // If "Book Entire Property" is selected, keep individual checkboxes disabled
                checkbox.prop('disabled', true);
                $card.addClass('unavailable');
            } else if (currentStatus === 'free' || currentStatus === 'available' || currentStatus === 'special') {
                checkbox.prop('disabled', false);
                $card.removeClass('unavailable');
            } else if (currentStatus) {
                // Only uncheck if not user-selected
                if (!checkbox[0] || !checkbox[0].hasAttribute('data-user-selected')) {
                    checkbox.prop('disabled', true).prop('checked', false);
                    $card.addClass('unavailable');
                } else {
                    // User has selected this - keep it enabled but warn
                    checkbox.prop('disabled', false);
                }
            }
        });
    };

    // Initialize accommodation cards as active by default
    AIOHM_Booking_Shortcode.initializeAccommodationCards = function() {
        $('.aiohm-booking-accommodation-card').each(function() {
            const $card = $(this);
            const $checkbox = $card.find('.accommodation-checkbox');
            
            // Set cards as free/available by default unless specifically marked otherwise
            if (!$card.attr('data-availability')) {
                $card.attr('data-availability', 'free');
                $card.data('availability-status', 'free');
            }
            
            // Enable checkboxes for free/available cards
            const availability = $card.attr('data-availability');
            if (availability === 'free' || availability === 'available' || availability === 'special') {
                $checkbox.prop('disabled', false);
                $card.removeClass('unavailable');
            }
        });
    };

    // Initialize accommodation availability updates
    $(document).ready(function() {
        // Initialize cards as active on page load
        AIOHM_Booking_Shortcode.initializeAccommodationCards();
        
        // Update availability when check-in date changes
        $(document).on('change', 'input[name="checkin_date"], input[name="checkout_date"], #checkinHidden', function() {
            AIOHM_Booking_Shortcode.updateAccommodationAvailability();
        });

        // Update availability when calendar date is selected
        $(document).on('click', '.aiohm-calendar-date.selectable', function() {
            setTimeout(function() {
                AIOHM_Booking_Shortcode.updateAccommodationAvailability();
            }, 100);
        });

        // Initial availability check
        setTimeout(function() {
            AIOHM_Booking_Shortcode.updateAccommodationAvailability();
        }, 500);

        // Periodic check to maintain styles against other JavaScript interference (less frequent)
        setInterval(function() {
            if ($('.aiohm-booking-accommodation-card').length > 0 && $('.aiohm-booking-accommodation-card[data-availability]').length > 0) {
                AIOHM_Booking_Shortcode.persistAvailabilityStyles();
            }
        }, 5000); // Check every 5 seconds instead of 2

        // Also re-apply styles when any accommodation checkbox changes (exclude Book Entire Property)
        $(document).on('change', '.accommodation-checkbox:not(#private_all_checkbox)', function() {
            
            // Handle individual accommodation selection when checked (excludes private_all_checkbox)
            if (this.checked && this.id !== 'private_all_checkbox') {
                // Uncheck "Book Entire Property" checkbox
                $('#private_all_checkbox').prop('checked', false);
                // Remove selection styling from Book Entire Property card
                $('.aiohm-booking-book-entire-card').removeClass('selected');
            }
            
            // Update card selected state
            var $card = $(this).closest('.aiohm-booking-event-card');
            if (this.checked) {
                $card.addClass('selected');
                // Store selection state
                this.setAttribute('data-user-selected', 'true');
            } else {
                $card.removeClass('selected');
                if (this.hasAttribute('data-user-selected')) {
                }
            }

            // Update pricing display when accommodation selection changes
            if (typeof updatePricingDisplay === 'function') {
                updatePricingDisplay();
            }
            
            // Dispatch accommodation selection event for pricing summary
            dispatchAccommodationSelectionEvent();

            setTimeout(function() {
                AIOHM_Booking_Shortcode.persistAvailabilityStyles();
            }, 50);
        });

        // Handle "Book Entire Property" exclusive selection
        $(document).on('change', '#private_all_checkbox', function() {
            const isChecked = this.checked;
            
            if (isChecked) {
                // Uncheck all individual accommodation checkboxes
                $('.accommodation-checkbox').not('#private_all_checkbox').prop('checked', false);
                // Add selection styling to ALL accommodation cards (since all are booked)
                $('.aiohm-booking-event-card.aiohm-booking-accommodation-card').addClass('selected');
                // Disable individual checkboxes (but keep them visually selected)
                $('.accommodation-checkbox').not('#private_all_checkbox').prop('disabled', true);
                // Don't add unavailable class - cards should look selected, not unavailable
                
                // Update pricing for Book All selection
                if (typeof updatePricingDisplay === 'function') {
                    updatePricingDisplay();
                }
                
                // Dispatch accommodation selection event for pricing summary
                dispatchAccommodationSelectionEvent();
            } else {
                // Remove selection styling from Book Entire Property card
                $('.aiohm-booking-book-entire-card').removeClass('selected');
                // Remove selection styling from all individual accommodation cards
                $('.aiohm-booking-event-card.aiohm-booking-accommodation-card').removeClass('selected');
                
                // Re-enable ALL accommodation checkboxes (simplify the logic)
                $('.aiohm-booking-accommodation-card').each(function() {
                    const $card = $(this);
                    const $checkbox = $card.find('.accommodation-checkbox').not('#private_all_checkbox');
                    
                    // Always enable checkboxes when unchecking "Book Entire Property"
                    $checkbox.prop('disabled', false);
                    $checkbox.prop('checked', false);
                    $card.removeClass('unavailable selected');
                    $card.attr('data-availability', 'free'); // Reset to free
                });
                
                // Dispatch accommodation selection event for pricing summary
                dispatchAccommodationSelectionEvent();
                
                // Update pricing when Book All is unchecked
                if (typeof updatePricingDisplay === 'function') {
                    updatePricingDisplay();
                }
            }
        });


        // Backup click handler for accommodation cards - force checkbox toggle
        $(document).on('click', '.aiohm-booking-accommodation-card:not(.aiohm-booking-book-entire-card)', function(e) {
            // Only handle if not clicking directly on checkbox
            if (e.target.type !== 'checkbox') {
                const $checkbox = $(this).find('.accommodation-checkbox');
                if (!$checkbox.prop('disabled')) {
                    const wasChecked = $checkbox.prop('checked');
                    $checkbox.prop('checked', !wasChecked);
                    $checkbox.trigger('change');
                    
                    // Check if it stays checked after a brief delay
                    setTimeout(() => {
                    }, 100);
                    e.preventDefault();
                }
            }
        });

        // Re-apply styles after any potential DOM manipulation
        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' &&
                    $(mutation.target).hasClass('accommodation-card') &&
                    mutation.attributeName === 'style') {
                    shouldUpdate = true;
                }
            });
            if (shouldUpdate) {
                setTimeout(function() {
                    AIOHM_Booking_Shortcode.persistAvailabilityStyles();
                }, 50);
            }
        });

        // Start observing accommodation cards
        $('.aiohm-booking-accommodation-card').each(function() {
            observer.observe(this, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        });
    });

    // Global function to refresh calendar tooltips (accessible from outside the module)
    window.refreshCalendarTooltips = function() {
        if (AIOHM_Booking_Shortcode && AIOHM_Booking_Shortcode.refreshCalendarTooltips) {
            AIOHM_Booking_Shortcode.refreshCalendarTooltips();
        }
    };

    // Function to dispatch accommodation selection event for pricing summary
    let lastDispatchTime = 0;
    let callCount = 0;
    function dispatchAccommodationSelectionEvent() {
        callCount++;
        
        // Allow immediate dispatches for accommodation selection changes
        // Removed throttling to ensure all selections are processed
        // const now = Date.now();
        // if (now - lastDispatchTime < 100) {
        //     return;
        // }
        // lastDispatchTime = now;
        
        // Remove processing check to allow multiple rapid selections
        // if (dispatchAccommodationSelectionEvent.isProcessing) {
        //     return;
        // }
        
        // dispatchAccommodationSelectionEvent.isProcessing = true;
        
        const selectedAccommodations = [];
        
        // Check if "Book Entire Property" is selected
        if ($('#private_all_checkbox').is(':checked')) {
            const checkinDate = $('#checkinHidden').val() || '';
            const checkoutDate = $('#checkoutHidden').val() || '';
            const nights = calculateNights(checkinDate, checkoutDate);
            
            // Count total number of rooms for entire property booking
            const totalUnits = $('.accommodation-checkbox').not('#private_all_checkbox').length;
            
            // Calculate price for entire property, checking for special pricing first
            let totalPrice = 0;
            const specialPricePerNight = getSpecialPriceForDateRange(checkinDate, checkoutDate);
            
            if (specialPricePerNight > 0) {
                // Use special pricing for the entire property - multiply by number of rooms
                totalPrice = specialPricePerNight * totalUnits * nights;
            } else {
                // No special pricing, sum all individual accommodation prices
                $('.accommodation-checkbox').not('#private_all_checkbox').each(function() {
                    const price = parseFloat($(this).data('price')) || 0;
                    totalPrice += price * nights;
                });
            }
            
            // Check for special pricing and private events in the date range
            const hasSpecialPricing = checkDateRangeForSpecialPricing(checkinDate, checkoutDate);
            const isPrivateEvent = checkDateRangeForPrivateEvents(checkinDate, checkoutDate);
            
            selectedAccommodations.push({
                id: 'entire_property',
                name: 'Entire Property',
                price: totalPrice / nights, // Per night price
                nights: nights,
                quantity: totalUnits, // Total number of rooms in the property
                dateRange: formatDateRange(checkinDate, checkoutDate),
                type: 'entire_property',
                hasSpecialPricing: hasSpecialPricing,
                isSpecialEvent: isPrivateEvent,
                checkin: checkinDate,
                checkout: checkoutDate
            });
        } else {
            // Collect selected individual accommodations only if entire property is not selected
            const $checkedBoxes = $('.accommodation-checkbox:checked, .unit-checkbox:checked');
            
            $checkedBoxes.each(function() {
                const $checkbox = $(this);
                const $card = $checkbox.closest('.aiohm-booking-event-card, .aiohm-booking-accommodation-card');
                const accommodationId = $checkbox.val() || $checkbox.data('id') || $card.data('id');
                const name = $card.find('.aiohm-booking-event-title, .aiohm-accommodation-title').text().trim() || 'Accommodation';
                const rawPrice = $checkbox.data('price');
                const price = parseFloat(rawPrice) || 0;
                
                // Get date range information
                const checkinDate = $('#checkinHidden').val() || '';
                const checkoutDate = $('#checkoutHidden').val() || '';
                const nights = calculateNights(checkinDate, checkoutDate);
                
                // Check for special pricing and private events in the date range
                const hasSpecialPricing = checkDateRangeForSpecialPricing(checkinDate, checkoutDate);
                const isPrivateEvent = checkDateRangeForPrivateEvents(checkinDate, checkoutDate);
                
                const accommodationObj = {
                    id: accommodationId,
                    name: name,
                    price: price,
                    nights: nights,
                    dateRange: formatDateRange(checkinDate, checkoutDate),
                    type: 'unit',
                    quantity: 1,
                    hasSpecialPricing: hasSpecialPricing,
                    isSpecialEvent: isPrivateEvent,
                    checkin: checkinDate,
                    checkout: checkoutDate
                };
                
                selectedAccommodations.push(accommodationObj);
            });
        }
        
        // Dispatch the event for pricing summary
        const event = new CustomEvent('aiohm-accommodation-selected', {
            detail: {
                selectedAccommodations: selectedAccommodations
            }
        });
        document.dispatchEvent(event);
        
        // Reset processing flag after a longer delay to allow event to complete
        setTimeout(() => {
            dispatchAccommodationSelectionEvent.isProcessing = false;
        }, 2000);
    }
    
    // Helper function to calculate number of nights
    function calculateNights(checkinDate, checkoutDate) {
        if (!checkinDate || !checkoutDate) return 1;
        
        const checkin = new Date(checkinDate);
        const checkout = new Date(checkoutDate);
        const diffTime = Math.abs(checkout - checkin);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays > 0 ? diffDays : 1;
    }
    
    // Helper function to format date range
    function formatDateRange(checkinDate, checkoutDate) {
        if (!checkinDate || !checkoutDate) return '';
        
        const checkin = new Date(checkinDate);
        const checkout = new Date(checkoutDate);
        
        const options = { month: 'short', day: 'numeric' };
        const checkinStr = checkin.toLocaleDateString('en-US', options);
        const checkoutStr = checkout.toLocaleDateString('en-US', options);
        
        return `${checkinStr} - ${checkoutStr}`;
    }
    
    // Helper function to check if date range has special pricing
    function checkDateRangeForSpecialPricing(checkinDate, checkoutDate) {
        if (!checkinDate || !checkoutDate) return false;
        
        const startDate = new Date(checkinDate);
        const endDate = new Date(checkoutDate);
        
        // Check each night in the stay (excluding checkout date)
        for (let date = new Date(startDate); date < endDate; date.setDate(date.getDate() + 1)) {
            const dateStr = date.toISOString().split('T')[0];
            const dayElement = $(`.aiohm-calendar-day[data-date="${dateStr}"]`);
            
            if (dayElement.length > 0) {
                // Check for special pricing badges
                if (dayElement.hasClass('special-pricing') || dayElement.find('.price-badge').length > 0) {
                    return true;
                }
                
                // Check tooltip data
                const tooltipData = dayElement.attr('data-tooltip-data');
                if (tooltipData) {
                    try {
                        const data = JSON.parse(tooltipData);
                        if (data.badges && data.badges.special) {
                            return true;
                        }
                    } catch (e) {
                        // Silent error handling
                    }
                }
            }
        }
        
        return false;
    }
    
    // Helper function to check if date range has private events
    function checkDateRangeForPrivateEvents(checkinDate, checkoutDate) {
        if (!checkinDate || !checkoutDate) return false;
        
        const startDate = new Date(checkinDate);
        const endDate = new Date(checkoutDate);
        
        // Check each night in the stay (excluding checkout date)
        for (let date = new Date(startDate); date < endDate; date.setDate(date.getDate() + 1)) {
            const dateStr = date.toISOString().split('T')[0];
            const dayElement = $(`.aiohm-calendar-day[data-date="${dateStr}"]`);
            
            if (dayElement.length > 0) {
                // Check for private event class or data
                if (dayElement.hasClass('private-event')) {
                    return true;
                }
                
                // Check tooltip data
                const tooltipData = dayElement.attr('data-tooltip-data');
                if (tooltipData) {
                    try {
                        const data = JSON.parse(tooltipData);
                        if (data.is_private_event) {
                            return true;
                        }
                    } catch (e) {
                        // Silent error handling
                    }
                }
            }
        }
        
        return false;
    }
    
    // Helper function to get special price for date range (for entire property booking)
    function getSpecialPriceForDateRange(checkinDate, checkoutDate) {
        if (!checkinDate || !checkoutDate) return 0;
        
        // Check if we have cached availability data
        if (!window.AIOHM_Booking_Shortcode || !window.AIOHM_Booking_Shortcode.cachedAvailability) {
            return 0;
        }
        
        const availabilityData = window.AIOHM_Booking_Shortcode.cachedAvailability;
        const startDate = new Date(checkinDate);
        const endDate = new Date(checkoutDate);
        
        let totalSpecialPrice = 0;
        let nightsWithSpecialPricing = 0;
        let consistentPrice = null;
        let allNightsHaveSpecialPricing = true;
        
        // Check each night in the stay (excluding checkout date)
        for (let date = new Date(startDate); date < endDate; date.setDate(date.getDate() + 1)) {
            const dateStr = date.toISOString().split('T')[0];
            const dayData = availabilityData[dateStr];
            
            if (dayData && dayData.price > 0) {
                // This night has special pricing
                totalSpecialPrice += dayData.price;
                nightsWithSpecialPricing++;
                
                // Check if all special prices are the same
                if (consistentPrice === null) {
                    consistentPrice = dayData.price;
                } else if (consistentPrice !== dayData.price) {
                    // Prices vary, can't use for entire property
                    return 0;
                }
            } else {
                // This night doesn't have special pricing
                allNightsHaveSpecialPricing = false;
            }
        }
        
        // Only return special price if all nights have the same special pricing
        if (allNightsHaveSpecialPricing && nightsWithSpecialPricing > 0 && consistentPrice !== null) {
            return consistentPrice;
        }
        
        return 0;
    }

})(jQuery);
