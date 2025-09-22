/**
 * Advanced Calendar JavaScript for AIOHM Booking
 * Handles calendar interactions, navigation, and dynamic features
 */

jQuery(document).ready(function($) {
    'use strict';

    // Calendar functionality object
    const AIOHMAdvancedCalendar = {

        // Helper function to get Monday-based day of week (0 = Monday, 6 = Sunday)
        getMondayBasedDay: function(date) {
            const day = date.getDay();
            return day === 0 ? 6 : day - 1;
        },

        // Initialize calendar features
        init: function() {
            this.bindEvents();
            this.setupCalendarNavigation();
            this.initColorPicker();
        },

        // Bind event handlers
        bindEvents: function() {

            // Calendar navigation buttons
            $(document).on('click', '.aiohm-period-prev, .aiohm-period-next', this.handlePeriodNavigation.bind(this));

            // Show button
            $(document).on('click', '.aiohm-show-button', this.handleShowButton.bind(this));

            // Filter buttons
            $(document).on('click', '#aiohm-calendar-search-btn', this.handleFilterCalendar.bind(this));
            $(document).on('click', '#aiohm-calendar-reset-btn', this.handleResetFilter.bind(this));
            $(document).on('click', '#aiohm-calendar-reset-all-days-btn', this.handleResetAllDays.bind(this));

            // Period selector change
            $(document).on('change', '#calendar-period', this.handlePeriodChange.bind(this));

            // Calendar cell interactions - simplified to single cells
            $(document).on('click', '.aiohm-date-cell', this.handleCellClick.bind(this));
            $(document).on('mouseenter', '.aiohm-date-cell', this.handleCellHover.bind(this));
            $(document).on('mouseleave', '.aiohm-date-cell', this.handleCellLeave.bind(this));

            // View selector buttons
            $(document).on('click', '.calendar-view-selector .button', this.handleViewChange.bind(this));

            // Accommodation links
            $(document).on('click', '.aiohm-accommodation-link', this.handleAccommodationClick.bind(this));

        },

        // Handle period navigation (previous/next)
        handlePeriodNavigation: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const isPrev = $button.hasClass('aiohm-period-prev');


            // Get current period type from the actual select element
            const periodType = $('#calendar-period').val() || 'week';

            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            let currentOffset = 0;

            // Get the appropriate offset parameter based on period type
            if (periodType === 'week') {
                currentOffset = parseInt(urlParams.get('week_offset')) || 0;
                currentOffset = isPrev ? currentOffset - 1 : currentOffset + 1;
                urlParams.set('week_offset', currentOffset);
            } else if (periodType === 'month') {
                currentOffset = parseInt(urlParams.get('month_offset')) || 0;
                currentOffset = isPrev ? currentOffset - 1 : currentOffset + 1;
                urlParams.set('month_offset', currentOffset);
            }

            // Set the period parameter
            urlParams.set('period', periodType);


            // Navigate to the new URL
            window.location.search = urlParams.toString();
        },

        // Handle period type change
        handlePeriodChange: function(e) {
            const $select = $(e.currentTarget);
            const newPeriod = $select.val();

            // Update URL to reflect the new period
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('period', newPeriod);

            // Reset all offset parameters when changing period type
            urlParams.delete('week_offset');
            urlParams.delete('month_offset');

            // Show/hide custom period inputs if they exist
            const $customWrapper = $('.aiohm-custom-period-wrapper');
            if (newPeriod === 'custom') {
                $customWrapper.removeClass('aiohm-booking-calendar-hide');
            } else {
                $customWrapper.addClass('aiohm-booking-calendar-hide');
                // Navigate to new period immediately
                window.location.search = urlParams.toString();
            }
        },

        // Handle Show button click
        handleShowButton: function(e) {
            e.preventDefault();

            // Get current period type
            const periodType = $('#calendar-period').val() || 'week';

            // Update URL with current period selection
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('period', periodType);


            // Navigate to reload the calendar
            window.location.search = urlParams.toString();
        },

        // Handle Filter Calendar button click
        handleFilterCalendar: function(e) {
            e.preventDefault();

            const statusFilter = $('#aiohm-calendar-status-filter').val();

            if (statusFilter) {
                // Hide all calendar cells first
                $('.aiohm-date-cell').closest('tr').hide();

                // Show only rows that have cells with the selected status
                $(`.aiohm-date-cell[data-status="${statusFilter}"]`).closest('tr').show();

                // Show notification
                this.showNotification(`Calendar filtered to show only ${statusFilter} statuses.`, 'info');
            } else {
                // Show all if no filter selected
                $('.aiohm-date-cell').closest('tr').show();
            }
        },

        // Handle Reset Filter button click
        handleResetFilter: function(e) {
            e.preventDefault();

            // Reset the status filter dropdown
            $('#aiohm-calendar-status-filter').val('');

            // Show all calendar rows
            $('.aiohm-date-cell').closest('tr').show();

            // Show notification
            this.showNotification('Calendar filter reset. Showing all accommodations.', 'info');
        },

        // Handle Reset All Days button click
        handleResetAllDays: function(e) {
            e.preventDefault();

            const self = this;

            // Show confirmation dialog
            if (!confirm('Are you sure you want to reset all calendar days? This will clear all manual status overrides, blocks, and custom settings. This action cannot be undone.')) {
                return;
            }

            // Show loading state
            const $button = $(e.currentTarget);
            const originalText = $button.text();
            $button.text('Resetting...').prop('disabled', true);

            // Make AJAX call to reset all days
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_reset_all_days',
                    nonce: aiohm_booking_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success notification
                        self.showNotification(response.data, 'success');

                        // Reload the page to show the reset calendar
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error notification
                        self.showNotification('Error: ' + response.data, 'error');
                        $button.text(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    self.showNotification('AJAX Error: ' + error, 'error');
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },

        // Handle calendar cell clicks - simplified for single cells
        handleCellClick: function(e) {
            // Check if the main calendar system is handling this click
            if ($(e.currentTarget).closest('.aiohm-booking-calendar-single-wrapper').length &&
                typeof window.AIOHMBookingCalendar !== 'undefined') {
                // Let the main calendar system handle this click
                return;
            }

            e.preventDefault();

            const $cell = $(e.currentTarget);
            const accommodationId = $cell.data('accommodation-id');
            const date = $cell.data('date');
            const status = $cell.data('status');
            const isEditable = $cell.data('editable');

            // Check if cell is editable
            if ($cell.data('editable')) {
                this.showCellEditDialog($cell, accommodationId, date);
            } else {
                this.showBookingDialog($cell, accommodationId, date);
            }
        },

        // Handle cell hover for preview
        handleCellHover: function(e) {
            const $cell = $(e.currentTarget);
            const accommodationId = $cell.data('accommodation-id');
            const date = $cell.data('date');

            // Add visual hover effect
            $cell.addClass('aiohm-cell-hover');
        },

        // Handle cell leave
        handleCellLeave: function(e) {
            const $cell = $(e.currentTarget);
            $cell.removeClass('aiohm-cell-hover');
        },

        // Handle view change (week, month)
        handleViewChange: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const view = $button.data('view');

            // Update button states
            $button.siblings().removeClass('button-primary').addClass('button-secondary');
            $button.removeClass('button-secondary').addClass('button-primary');

            // Here you would implement view change logic

            // For now, just show a message
            this.showNotification(`Calendar view changed to: ${view}`, 'info');
        },

        // Handle accommodation link clicks
        handleAccommodationClick: function(e) {
            e.preventDefault();

            const $link = $(e.currentTarget);
            const accommodationId = $link.data('accommodation-id');


            // Highlight the entire row
            $link.closest('tr').addClass('aiohm-row-selected')
                 .siblings().removeClass('aiohm-row-selected');
        },

        // Show booking dialog for non-editable cells
        showBookingDialog: function($cell, accommodationId, date, part) {
            const accommodationName = $cell.closest('tr').find('.aiohm-accommodation-link').text();

            // Create a simple dialog (you might want to use a proper modal library)
            const dialogContent = `
                <div class="aiohm-booking-dialog" style="
                    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                    background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 10000; max-width: 400px; width: 90%;
                ">
                    <h3>Book ${accommodationName}</h3>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Period:</strong> ${part === 'first' ? 'Morning' : 'Evening'}</p>
                    <div style="margin-top: 15px;">
                        <button class="button button-primary aiohm-book-now">Book Now</button>
                        <button class="button aiohm-close-dialog">Cancel</button>
                    </div>
                </div>
                <div class="aiohm-dialog-overlay" style="
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.5); z-index: 9999;
                "></div>
            `;

            $('body').append(dialogContent);

            // Bind close events
            $(document).on('click', '.aiohm-close-dialog, .aiohm-dialog-overlay', function() {
                $('.aiohm-booking-dialog, .aiohm-dialog-overlay').remove();
            });
        },

        // Show cell edit dialog for editable cells
        showCellEditDialog: function($cell, accommodationId, date, part) {
            const currentStatus = this.getCellStatus($cell);

            const dialogContent = `
                <div class="aiohm-edit-dialog" style="
                    position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
                    background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 10000; max-width: 400px; width: 90%;
                ">
                    <h3>Edit Booking Status</h3>
                    <p><strong>Date:</strong> ${date} (${part === 'first' ? 'Morning' : 'Evening'})</p>
                    <div style="margin: 15px 0;">
                        <label for="booking-status">Status:</label>
                        <select id="booking-status" style="width: 100%; margin-top: 5px;">
                            <option value="free" ${currentStatus === 'free' ? 'selected' : ''}>Free</option>
                            <option value="booked" ${currentStatus === 'booked' ? 'selected' : ''}>Booked</option>
                            <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="external" ${currentStatus === 'external' ? 'selected' : ''}>External</option>
                            <option value="blocked" ${currentStatus === 'blocked' ? 'selected' : ''}>Blocked</option>
                        </select>
                    </div>
                    <div style="margin: 15px 0;">
                        <label class="aiohm-bulk-checkbox">
                            <input type="checkbox" id="booking-apply-to-all"> Apply to all units on this day
                        </label>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="button button-primary aiohm-save-cell">Save</button>
                        <button class="button aiohm-close-edit-dialog">Cancel</button>
                    </div>
                </div>
                <div class="aiohm-edit-dialog-overlay" style="
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.5); z-index: 9999;
                "></div>
            `;

            $('body').append(dialogContent);

            // Bind events - remove any existing handlers first to prevent duplicates
            const self = this;
            $(document).off('click', '.aiohm-save-cell');
            $(document).off('click', '.aiohm-close-edit-dialog, .aiohm-edit-dialog-overlay');

            $(document).on('click', '.aiohm-save-cell', function() {
                const newStatus = $('#booking-status').val();
                const applyToAll = $('#booking-apply-to-all').is(':checked');

                self.saveCellChanges($cell, accommodationId, date, part, newStatus, applyToAll);
                $('.aiohm-edit-dialog, .aiohm-edit-dialog-overlay').remove();
                // Remove the event handlers after use
                $(document).off('click', '.aiohm-save-cell');
                $(document).off('click', '.aiohm-close-edit-dialog, .aiohm-edit-dialog-overlay');
            });

            $(document).on('click', '.aiohm-close-edit-dialog, .aiohm-edit-dialog-overlay', function() {
                $('.aiohm-edit-dialog, .aiohm-edit-dialog-overlay').remove();
                // Remove the event handlers after use
                $(document).off('click', '.aiohm-save-cell');
                $(document).off('click', '.aiohm-close-edit-dialog, .aiohm-edit-dialog-overlay');
            });
        },

        // Get current status of a cell
        getCellStatus: function($cell) {
            const classes = $cell.attr('class');
            if (classes.includes('aiohm-booked')) return 'booked';
            if (classes.includes('aiohm-pending')) return 'pending';
            if (classes.includes('aiohm-blocked')) return 'blocked';
            if (classes.includes('aiohm-external')) return 'external';
            return 'free';
        },

        // Enhanced save cell changes with better loading states
        saveCellChanges: function($cell, accommodationId, date, part, status, applyToAll) {
            // Show enhanced loading state
            const originalContent = $cell.html();
            $cell.html(`
                <div class="aiohm-cell-loading" style="display: flex; align-items: center; justify-content: center; gap: 4px; padding: 2px;">
                    <span class="aiohm-loading-spinner" style="width: 14px; height: 14px; border: 1px solid #ddd; border-top: 1px solid #457d59; border-radius: 50%; animation: spin 1s linear infinite;"></span>
                    <span style="font-size: 11px; color: #666;">Saving...</span>
                </div>
            `);
            $cell.addClass('aiohm-cell-saving');

            const self = this;

            // Real AJAX call to save data
            $.ajax({
                url: aiohm_booking_admin.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                timeout: 30000,
                data: {
                    action: 'aiohm_booking_update_cell_status',
                    accommodation_id: accommodationId,
                    date: date,
                    part: part || 'full',
                    status: status,
                    apply_to_all: applyToAll,
                    nonce: aiohm_booking_admin.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        // Update cell appearance
                        if (applyToAll) {
                            // Update all cells for this date
                            self.updateAllCellsForDate(date, status);
                            self.showNotification(`Status updated to "${status}" for all units on ${self.formatDateForDisplay(date)}`, 'success');
                        } else {
                            self.updateCellAppearance($cell, status, applyToAll);
                            self.showNotification(`Status updated to "${status}" for ${self.formatDateForDisplay(date)}`, 'success');
                        }
                    } else {
                        // Restore original content on failure
                        $cell.html(originalContent);
                        $cell.removeClass('aiohm-cell-saving');
                        const errorMsg = response.data || 'Unknown error occurred';
                        self.showNotification(`Failed to update cell: ${errorMsg}`, 'error');
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    // Restore original content on failure
                    $cell.html(originalContent);
                    $cell.removeClass('aiohm-cell-saving');

                    let errorMessage = 'Failed to update cell. ';

                    if (xhr && xhr.status) {
                        switch (xhr.status) {
                            case 403:
                                errorMessage += 'Permission denied. Please check your user capabilities.';
                                break;
                            case 404:
                                errorMessage += 'The requested resource was not found.';
                                break;
                            case 500:
                                errorMessage += 'Server error occurred. Please try again later.';
                                break;
                            case 0:
                                errorMessage += 'Network connection failed. Please check your internet connection.';
                                break;
                            default:
                                errorMessage += `HTTP ${xhr.status}: ${xhr.statusText || 'Unknown error'}`;
                        }
                    } else if (textStatus === 'timeout') {
                        errorMessage += 'Request timed out. Please try again.';
                    } else if (textStatus === 'parsererror') {
                        errorMessage += 'Invalid response from server.';
                    } else {
                        errorMessage += 'Please try again or contact support if the problem persists.';
                    }

                    self.showNotification(errorMessage, 'error');
                }
            });
        },

        // Update cell appearance based on status
        updateCellAppearance: function($cell, status, applyToAll) {

            // Remove all status classes (including the old aiohm-date-* classes)
            $cell.removeClass('aiohm-booked aiohm-pending aiohm-blocked aiohm-external');
            $cell.removeClass('aiohm-date-free aiohm-date-booked aiohm-date-pending aiohm-date-blocked aiohm-date-external');

            // Add new status classes
            if (status !== 'free') {
                $cell.addClass(`aiohm-${status}`);
                $cell.addClass(`aiohm-date-${status}`);
            } else {
                $cell.addClass('aiohm-date-free');
            }

            // Update data attributes
            $cell.data('status', status);
            $cell.attr('data-status', status);

            // Update tooltip title with badge information
            const statusLabels = {
                'free': 'Available',
                'booked': 'Booked',
                'pending': 'Pending',
                'external': 'External',
                'blocked': 'Blocked'
            };
            const statusLabel = statusLabels[status] || status;

            // Get current date from title and update tooltip
            const currentTitle = $cell.attr('title') || '';
            const datePart = currentTitle.split(' - ')[0] || currentTitle.split(' | ')[0] || '';

            // Build tooltip with badges
            const newTitle = this.buildTooltipWithBadges($cell, datePart, statusLabel);
            $cell.attr('title', newTitle);

            // Update cell content based on status
            if (status === 'free') {
                $cell.html('<span class="aiohm-available-indicator">✓</span>');
            } else {
                $cell.html(`<span class="aiohm-status-indicator">${statusLabel}</span>`);
            }

        },

        // Update all cells for a specific date (for bulk updates)
        updateAllCellsForDate: function(date, status) {
            const cells = $(`.aiohm-date-cell[data-date="${date}"]`);

            cells.each((index, cell) => {
                const $cell = $(cell);
                this.updateCellAppearance($cell, status, true);
            });
        },

        // Setup calendar navigation
        setupCalendarNavigation: function() {
            // Add keyboard navigation
            $(document).on('keydown', this.handleKeyboardNavigation.bind(this));

            // Initialize private event mini calendar
            this.initPrivateEventMiniCalendar();

            // Initialize booking mode radio toggle
            this.initBookingModeToggle();
        },

        // Initialize mini calendar for private event date selection
        initPrivateEventMiniCalendar: function() {
            if (!$('#aiohm-mini-calendar-grid').length) {
                return;
            }

            if (typeof aiohm_booking_calendar === 'undefined') {
                setTimeout(() => this.initPrivateEventMiniCalendar(), 100);
                return;
            }

            const self = this;
            let currentDate = new Date();
            let selectedDates = []; // Change to array for multi-selection
            let selectionMode = 'single'; // 'single', 'range', or 'multi'
            let rangeStart = null;

            // Load existing private events data
            const loadPrivateEvents = () => {
                // Load from server data passed via wp_localize_script
                let events = {};
                if (typeof aiohm_booking_calendar !== 'undefined' && aiohm_booking_calendar.private_events) {
                    events = aiohm_booking_calendar.private_events;
                }
                return events;
            };

            // Render the mini calendar
            const renderMiniCalendar = (year, month) => {
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const startDate = new Date(firstDay);
                const endDate = new Date(lastDay);

                // Adjust to show full weeks starting from Monday
                startDate.setDate(startDate.getDate() - this.getMondayBasedDay(firstDay));
                endDate.setDate(endDate.getDate() + (6 - this.getMondayBasedDay(endDate)));

                // Update month display
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'];
                $('#aiohm-mini-cal-month').text(`${monthNames[month]} ${year}`);

                // Build calendar HTML
                let html = '';

                // Day headers starting with Monday
                const dayHeaders = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                dayHeaders.forEach(day => {
                    html += `<div class="aiohm-mini-calendar-day-header">${day}</div>`;
                });

                // Load existing events
                const privateEvents = loadPrivateEvents();

                // Calendar days
                const today = new Date();
                const currentDay = new Date(startDate);

                while (currentDay <= endDate) {
                    const dayNum = currentDay.getDate();
                    const isCurrentMonth = currentDay.getMonth() === month;
                    const isToday = currentDay.toDateString() === today.toDateString();

                    // Create date string in local timezone
                    const year = currentDay.getFullYear();
                    const monthNum = String(currentDay.getMonth() + 1).padStart(2, '0');
                    const dayNum2 = String(currentDay.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${monthNum}-${dayNum2}`;

                    // Check selection status
                    const isSelected = selectedDates.includes(dateStr);

                    // Check existing event status
                    const eventData = privateEvents[dateStr];
                    const hasPrivateEvent = eventData && eventData.is_private_event;
                    const hasSpecialPricing = eventData && eventData.is_special_pricing && eventData.price > 0;

                    let dayClass = 'aiohm-mini-calendar-day';
                    if (!isCurrentMonth) dayClass += ' other-month';
                    if (isToday) dayClass += ' today';
                    if (isSelected) dayClass += ' selected';
                    if (hasPrivateEvent) dayClass += ' has-private-event';
                    if (hasSpecialPricing) dayClass += ' has-special-pricing';
                    if (hasPrivateEvent && hasSpecialPricing) dayClass += ' has-both';

                    // Create visual indicators for dual color display
                    let indicatorHtml = '';
                    if (hasPrivateEvent && hasSpecialPricing) {
                        indicatorHtml = `<div class="aiohm-dual-indicator">
                            <span class="aiohm-private-indicator"></span>
                            <span class="aiohm-special-indicator"></span>
                        </div>`;
                    } else if (hasPrivateEvent) {
                        indicatorHtml = `<div class="aiohm-single-indicator aiohm-private-indicator"></div>`;
                    } else if (hasSpecialPricing) {
                        indicatorHtml = `<div class="aiohm-single-indicator aiohm-special-indicator"></div>`;
                    }

                    html += `<div class="${dayClass}" data-date="${dateStr}">
                        ${indicatorHtml}
                        <span class="aiohm-day-number">${dayNum}</span>
                    </div>`;

                    currentDay.setDate(currentDay.getDate() + 1);
                }

                $('#aiohm-mini-calendar-grid').html(html);

                // Add/update selection controls
                const selectedCount = selectedDates.length;
                const controlsSelector = '.aiohm-selection-controls';

                // Remove existing controls if any
                $(controlsSelector).remove();

                // Add new controls after the calendar grid
                const controlsHtml = `
                    <div class="aiohm-selection-controls">
                        <span class="selection-count">${selectedCount} days selected</span>
                        <button type="button" class="aiohm-clear-selection-btn" ${selectedCount === 0 ? 'disabled' : ''}>Clear Selection</button>
                    </div>`;

                $('#aiohm-mini-calendar-grid').after(controlsHtml);

                // Update selected dates display
                updateSelectedDatesDisplay();
            };

            // Update selected dates display
            const updateSelectedDatesDisplay = () => {
                const display = $('#aiohm-selected-date-display');
                if (selectedDates.length === 0) {
                    display.text('Click on dates below to select (Hold Ctrl for multi-select)')
                           .removeClass('has-date');
                } else if (selectedDates.length === 1) {
                    const date = new Date(selectedDates[0] + 'T00:00:00');
                    const displayDate = date.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    display.text(displayDate).addClass('has-date');
                } else {
                    display.text(`${selectedDates.length} dates selected`).addClass('has-date');
                }

                // Update hidden input with selected dates
                $('#aiohm-special-event-date').val(selectedDates.join(','));
            };

            // Handle date selection with multi-select support
            const handleDateClick = (dateStr, ctrlKey, shiftKey) => {
                if (shiftKey && selectedDates.length > 0) {
                    // Range selection
                    const lastSelected = selectedDates[selectedDates.length - 1];
                    const startDate = new Date(Math.min(new Date(lastSelected), new Date(dateStr)));
                    const endDate = new Date(Math.max(new Date(lastSelected), new Date(dateStr)));

                    selectedDates = [];
                    const current = new Date(startDate);
                    while (current <= endDate) {
                        const year = current.getFullYear();
                        const month = String(current.getMonth() + 1).padStart(2, '0');
                        const day = String(current.getDate()).padStart(2, '0');
                        selectedDates.push(`${year}-${month}-${day}`);
                        current.setDate(current.getDate() + 1);
                    }
                } else if (ctrlKey) {
                    // Multi-select toggle
                    const index = selectedDates.indexOf(dateStr);
                    if (index > -1) {
                        selectedDates.splice(index, 1);
                    } else {
                        selectedDates.push(dateStr);
                    }
                } else {
                    // Single selection
                    selectedDates = [dateStr];
                }

                // Sort selected dates
                selectedDates.sort();

                // Re-render calendar to show selection
                renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());
            };

            // Event handlers
            $(document).on('click', '#aiohm-mini-cal-prev', function() {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());
            });

            $(document).on('click', '#aiohm-mini-cal-next', function() {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());
            });

            $(document).on('click', '.aiohm-mini-calendar-day:not(.other-month)', function(e) {
                e.preventDefault();
                const dateStr = $(this).data('date');
                handleDateClick(dateStr, e.ctrlKey || e.metaKey, e.shiftKey);
            });

            // Clear selection button
            $(document).on('click', '.aiohm-clear-selection-btn', function() {
                selectedDates = [];
                renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());
            });

            // Initial render
            renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());

            // Expose refresh function for external use
            this.refreshMiniCalendar = () => {
                renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());
            };

            // Expose clear selection function for external use
            this.clearMiniCalendarSelection = () => {
                selectedDates = [];
                renderMiniCalendar(currentDate.getFullYear(), currentDate.getMonth());
            };
        },

        // Initialize booking mode checkbox toggle
        initBookingModeToggle: function() {
            // Handle checkbox selection states
            const updateCheckboxStates = () => {
                $('.aiohm-checkbox-option').removeClass('selected');
                $('.aiohm-checkbox-option input[type="checkbox"]:checked').closest('.aiohm-checkbox-option').addClass('selected');
            };

            // Initial state
            updateCheckboxStates();

            // Handle checkbox changes
            $(document).on('change', 'input[name="aiohm-event-mode-private"], input[name="aiohm-event-mode-special"]', function() {
                updateCheckboxStates();

                const isPrivate = $('#aiohm-event-mode-private').is(':checked');
                const isSpecial = $('#aiohm-event-mode-special').is(':checked');
            });

            // Handle clicking on the label/option area
            $(document).on('click', '.aiohm-checkbox-option', function(e) {
                // Don't trigger if clicking directly on the checkbox input
                if (e.target.type !== 'checkbox') {
                    const checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
                }
            });

            // Handle Set Event button click
            $(document).on('click', '#aiohm-set-private-event-btn', this.handleSetEventClick.bind(this));

            // Handle Remove Event button clicks
            $(document).on('click', '.aiohm-remove-event-btn', this.handleRemoveEventClick.bind(this));
        },

        // Handle Set Event button click
        handleSetEventClick: function(e) {
            e.preventDefault();

            const selectedDatesString = $('#aiohm-special-event-date').val();
            const eventName = ($('#aiohm-special-event-name').val() || '').trim();
            const eventPrice = $('#aiohm-special-event-price').val() || '';
            const isPrivateEvent = $('#aiohm-private-event-toggle').is(':checked');

            // Determine special pricing
            const isSpecialPricing = eventPrice && parseFloat(eventPrice) > 0;

            // Parse selected dates (could be single date or comma-separated list)
            const selectedDatesList = selectedDatesString ? selectedDatesString.split(',').map(date => date.trim()).filter(date => date) : [];

            // Validation
            if (selectedDatesList.length === 0) {
                this.showNotification('Please select a date from the calendar.', 'error');
                return;
            }

            if (eventName.length === 0 && !isPrivateEvent && !isSpecialPricing) {
                this.showNotification('Please enter either an Event Name, enable 🏠, or set 💰 pricing.', 'error');
                return;
            }

            if (isSpecialPricing && parseFloat(eventPrice) <= 0) {
                this.showNotification('💰 price must be greater than 0.', 'error');
                return;
            }

            // Show loading state
            const $button = $('#aiohm-set-private-event-btn');
            const originalText = $button.text();
            $button.text('Setting Events...').prop('disabled', true);

            // Process each date separately
            this.setMultipleEvents(selectedDatesList, eventName, eventPrice, isPrivateEvent, isSpecialPricing, $button, originalText);
        },

        // Process multiple events sequentially
        setMultipleEvents: function(datesList, eventName, eventPrice, isPrivateEvent, isSpecialPricing, $button, originalText) {
            let successCount = 0;
            let errorCount = 0;
            const totalDates = datesList.length;
            let processedDates = 0;

            const processNextDate = (index) => {
                if (index >= datesList.length) {
                    // All dates processed, show final result
                    if (errorCount === 0) {
                        this.showNotification(`Events set successfully for ${successCount} date${successCount === 1 ? '' : 's'}!`, 'success');
                    } else if (successCount === 0) {
                        this.showNotification(`Failed to set events for all ${errorCount} date${errorCount === 1 ? '' : 's'}.`, 'error');
                    } else {
                        this.showNotification(`Events set for ${successCount} date${successCount === 1 ? '' : 's'}, failed for ${errorCount}.`, 'warning');
                    }

                    // Clear form if any success
                    if (successCount > 0) {
                        $('#aiohm-special-event-name').val('');
                        $('#aiohm-special-event-price').val('');
                        $('#aiohm-special-event-date').val('');
                        $('#aiohm-selected-date-display').text('Click on dates below to select (Hold Ctrl for multi-select)').removeClass('has-date');

                        // Clear the selectedDates array and re-render mini calendar
                        if (this.clearMiniCalendarSelection) {
                            this.clearMiniCalendarSelection();
                        }
                    } else {
                        // If no events were created successfully, just refresh mini calendar to show updated colors
                        if (this.refreshMiniCalendar) {
                            this.refreshMiniCalendar();
                        }
                    }

                    // Restore button state
                    $button.text(originalText).prop('disabled', false);
                    return;
                }

                const currentDate = datesList[index];
                processedDates++;

                // Update button text with progress
                $button.text(`Setting Events... (${processedDates}/${totalDates})`);

                // Prepare event data for single date
                const eventData = {
                    action: 'aiohm_booking_set_private_event',
                    date: currentDate,
                    event_name: eventName,
                    event_price: eventPrice,
                    is_private_event: isPrivateEvent ? 'true' : 'false',
                    is_special_pricing: isSpecialPricing ? 'true' : 'false',
                    nonce: aiohm_booking_admin.nonce || ''
                };

                // Save event via AJAX
                $.ajax({
                    url: aiohm_booking_admin.ajax_url || '/wp-admin/admin-ajax.php',
                    type: 'POST',
                    data: eventData,
                    success: (response) => {
                        if (response.success) {
                            successCount++;

                            // Update local private events data for mini calendar
                            if (response.data && response.data.event) {
                                if (!aiohm_booking_calendar.private_events) {
                                    aiohm_booking_calendar.private_events = {};
                                }
                                aiohm_booking_calendar.private_events[currentDate] = response.data.event;
                            }

                            // Update all calendar cells for this date instantly
                            this.updateCalendarCellsForDualEvent(currentDate, isPrivateEvent, isSpecialPricing, eventPrice, eventName);

                            // Update Current Special Events list
                            this.addEventToCurrentEventsList(currentDate, eventName, eventPrice, isPrivateEvent, isSpecialPricing);

                            // Refresh mini calendar to show new colors
                            if (this.refreshMiniCalendar) {
                                this.refreshMiniCalendar();
                            }
                        } else {
                            errorCount++;
                        }
                    },
                    error: (xhr, textStatus, errorThrown) => {
                        errorCount++;
                    },
                    complete: () => {
                        // Process next date
                        setTimeout(() => processNextDate(index + 1), 100); // Small delay to avoid overwhelming server
                    }
                });
            };

            // Start processing from first date
            processNextDate(0);
        },

        // Handle Remove Event button click
        handleRemoveEventClick: function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent any parent event handlers

            const $button = $(e.currentTarget);
            const eventDate = $button.data('date');

            if (!eventDate) {
                this.showNotification('Error: No date found for this event.', 'error');
                return;
            }

            // Show confirmation dialog
            const confirmed = confirm('Are you sure you want to remove this 🏠?');
            if (!confirmed) {
                return;
            }

            // Show loading state
            const originalText = $button.html();
            $button.html('...').prop('disabled', true);

            // Prepare removal data
            const removeData = {
                action: 'aiohm_booking_remove_private_event',
                date: eventDate,
                nonce: aiohm_booking_admin.nonce || ''
            };

            // Remove event via AJAX
            $.ajax({
                url: aiohm_booking_admin.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: removeData,
                success: (response) => {
                    if (response.success) {
                        this.showNotification('🏠 removed successfully!', 'success');

                        // Remove the event item from the UI
                        $button.closest('.aiohm-private-event-item').fadeOut(300, function() {
                            $(this).remove();

                            // Check if there are no more events and show empty message
                            const $eventsList = $('#aiohm-private-events-list');
                            const $remainingEvents = $eventsList.find('.aiohm-private-event-item');
                            if ($remainingEvents.length === 0) {
                                $eventsList.html('<em class="aiohm-private-events-empty">No 🏠 currently set.</em>');
                            }
                        });

                        // Remove visual indicators from calendar cells for this date
                        this.removeEventFromCalendar(eventDate);

                    } else {
                        this.showNotification('Error removing event: ' + (response.data || 'Unknown error'), 'error');
                        // Restore button state on error
                        $button.html(originalText).prop('disabled', false);
                    }
                },
                error: (xhr, textStatus, errorThrown) => {
                    this.showNotification('Error removing event. Please try again.', 'error');
                    // Restore button state on error
                    $button.html(originalText).prop('disabled', false);
                }
            });

        },

        // Remove event indicators from calendar cells
        removeEventFromCalendar: function(date) {
            // Find all calendar cells for this date
            const $dateCells = $(`.aiohm-date-cell[data-date="${date}"]`);

            if ($dateCells.length > 0) {
                // Store reference to calendar object for use in callback
                const calendar = this;

                $dateCells.each(function() {
                    const $cell = $(this);
                    const currentStatus = $cell.data('status');

                    // Only update cells that are currently "free" with private event indicators
                    if (currentStatus === 'free') {
                        // Old private event classes removed (now using badge system)

                        // Reset to standard free cell
                        $cell.html('<span class="aiohm-available-indicator">✓</span>');

                        // Reset data attributes
                        // Update tooltip with badge information
                        const currentTitle = $cell.attr('title') || '';
                        const datePart = currentTitle.split(' - ')[0] || currentTitle.split(' | ')[0] || '';
                        const newTitle = calendar.buildTooltipWithBadges($cell, datePart, 'Available');
                        $cell.attr('title', newTitle);
                    }
                });

            }
        },

        // Update all calendar cells for dual event (private + special pricing)
        updateCalendarCellsForDualEvent: function(date, isPrivateEvent, isSpecialPricing, price, name) {
            // Find all calendar cells for this date
            const $dateCells = $(`.aiohm-date-cell[data-date="${date}"]`);

            if ($dateCells.length === 0) {
                return;
            }

            // Store reference to calendar object for use in callback
            const calendar = this;

            $dateCells.each(function() {
                const $cell = $(this);
                const currentStatus = $cell.data('status');

                // Only update cells that are currently "free" (don't override manually set statuses)
                if (currentStatus === 'free') {
                    // Old private event class logic removed (now using badge system)

                    // Update data attributes
                    if (isSpecialPricing && price > 0) {
                        $cell.data('price', price);
                        $cell.attr('data-price', price);
                    }

                    // Update cell content - only show checkmark and price, no text labels
                    let statusLabels = [];
                    if (isPrivateEvent) statusLabels.push('Private Only');
                    if (isSpecialPricing) statusLabels.push('Special Pricing');
                    const statusLabel = statusLabels.join(' + ');

                    let newContent = '<span class="aiohm-available-indicator">✓</span>';

                    if (isSpecialPricing && price > 0) {
                        newContent += `<span class="aiohm-price-indicator">${parseFloat(price).toFixed(2)}</span>`;
                    }

                    $cell.html(newContent);

                    // Update tooltip with badge information
                    const currentTitle = $cell.attr('title') || '';
                    const datePart = currentTitle.split(' - ')[0] || currentTitle.split(' | ')[0] || '';
                    const newTitle = calendar.buildTooltipWithBadges($cell, datePart, statusLabel);
                    $cell.attr('title', newTitle);
                }
            });

        },

        // Add event to Current Special Events list in the UI
        addEventToCurrentEventsList: function(date, eventName, eventPrice, isPrivateEvent, isSpecialPricing) {
            const $eventsList = $('#aiohm-private-events-list');

            // Remove "no events" message if it exists
            $eventsList.find('.aiohm-private-events-empty').remove();

            // Check if this date already exists and remove it (to avoid duplicates)
            $eventsList.find(`.aiohm-private-event-item`).each(function() {
                const $item = $(this);
                const itemDate = $item.find('.aiohm-remove-event-btn').data('date');
                if (itemDate === date) {
                    $item.remove();
                }
            });

            // Format the date
            const dateObj = new Date(date + 'T00:00:00');
            const formattedDate = dateObj.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });

            // Format the price
            const price = eventPrice > 0 ? parseFloat(eventPrice).toFixed(2) : '0.00';

            // Get currency from global settings (fallback to USD)
            const currency = window.aiohm_booking_admin?.currency || 'USD';

            // Create mode labels
            const modeLabels = [];
            if (isPrivateEvent) modeLabels.push('🏠');
            if (isSpecialPricing) modeLabels.push('💰');
            const modeLabel = modeLabels.join(' + ') || '🏠';

            // Create CSS class
            let modeClass = 'private-only';
            if (isPrivateEvent && isSpecialPricing) {
                modeClass = 'dual-event';
            } else if (isSpecialPricing) {
                modeClass = 'special-pricing';
            }

            // Build badge representation for tooltip and display
            let badgeParts = [];
            let badgeHtml = '';
            if (isPrivateEvent) {
                badgeParts.push('🏠');
                badgeHtml += '<span class="aiohm-badge-inline aiohm-private-badge" title="🏠">🏠</span>';
            }
            if (isSpecialPricing) {
                badgeParts.push('💰');
                badgeHtml += '<span class="aiohm-badge-inline aiohm-special-badge" title="💰">💰</span>';
            }
            const badgeTooltip = badgeParts.length > 0 ? badgeParts.join(', ') : modeLabel;

            // Create the event item HTML with badge representation
            const eventHtml = `
                <div class="aiohm-private-event-item ${modeClass}" title="${badgeTooltip} - ${formattedDate}">
                    <button class="aiohm-remove-event-btn" data-date="${date}" title="Remove Event">×</button>
                    <div class="aiohm-event-date">${formattedDate}${badgeHtml ? ' <span class="aiohm-event-badges">' + badgeHtml + '</span>' : ''}</div>
                    <div class="aiohm-event-name-display">${eventName || '🏠'}</div>
                    <div class="aiohm-event-price-display">${price} ${currency} • ${modeLabel}</div>
                </div>
            `;

            // Add the new event to the list
            if (!$eventsList.find('.aiohm-private-events-grid').length) {
                $eventsList.html('<div class="aiohm-private-events-grid"></div>');
            }

            const $eventsGrid = $eventsList.find('.aiohm-private-events-grid');
            $eventsGrid.append(eventHtml);

            // Sort events by date
            this.sortEventsList();

        },

        // Sort events in Current Special Events list by date
        sortEventsList: function() {
            const $eventsGrid = $('#aiohm-private-events-list .aiohm-private-events-grid');
            const $events = $eventsGrid.find('.aiohm-private-event-item').get();

            $events.sort(function(a, b) {
                const dateA = new Date($(a).find('.aiohm-remove-event-btn').data('date'));
                const dateB = new Date($(b).find('.aiohm-remove-event-btn').data('date'));
                return dateA - dateB;
            });

            // Re-append sorted events
            $eventsGrid.empty();
            $events.forEach(event => $eventsGrid.append(event));
        },

        // Handle keyboard navigation
        handleKeyboardNavigation: function(e) {
            // Only handle when calendar is focused
            if (!$(e.target).closest('.aiohm-bookings-calendar-wrapper').length) return;

            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    $('.aiohm-period-prev').trigger('click');
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    $('.aiohm-period-next').trigger('click');
                    break;
            }
        },

        // Initialize tooltips
        // Build tooltip with badge information
        buildTooltipWithBadges: function($cell, datePart, statusLabel) {
            // Return empty string to disable tooltips
            return '';
        },

        // Utility method to format dates for display in messages
        formatDateForDisplay: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },

        // Show notification
        showNotification: function(message, type = 'info') {
            const className = type === 'success' ? 'notice-success' :
                             type === 'warning' ? 'notice-warning' :
                             type === 'error' ? 'notice-error' : 'notice-info';

            const $notification = $(`
                <div class="notice ${className} is-dismissible aiohm-notification" style="position: fixed; top: 32px; right: 20px; z-index: 10001; max-width: 300px;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            $('body').append($notification);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);

            // Manual dismiss
            $notification.on('click', '.notice-dismiss', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        // Color Picker System
        initColorPicker: function() {
            const self = this;
            let currentStatus = null;

            // Default colors mapping from localized script
            this.defaultColors = aiohm_booking_admin.calendar_colors || {
                'free': '#f8f9fa',
                'booked': '#dc3545',
                'pending': '#ffc107',
                'external': '#17a2b8',
                'blocked': '#6c757d',
                'special': '#007cba',
                'private': '#28a745'
            };

            // Load saved colors from localStorage or use defaults
            this.customColors = JSON.parse(localStorage.getItem('aiohm_calendar_colors') || '{}');

            // Apply any saved custom colors on load
            this.applyCustomColors();

            // Color picker trigger clicks
            $(document).on('click', '.aiohm-color-picker-trigger', function(e) {
                e.preventDefault();
                currentStatus = $(this).data('status');
                self.openColorPicker(currentStatus);
            });

            // Color picker modal events
            $(document).on('click', '.aiohm-color-picker-close, .aiohm-color-picker-overlay, #aiohm-color-picker-cancel', function(e) {
                e.preventDefault();
                self.closeColorPicker();
            });

            $(document).on('click', '#aiohm-color-picker-apply', function(e) {
                e.preventDefault();
                const newColor = $('#aiohm-color-input').val();
                self.applyNewColor(currentStatus, newColor);
            });

            $(document).on('click', '#aiohm-color-picker-reset', function(e) {
                e.preventDefault();
                self.resetToDefault(currentStatus);
            });

            // Reset all colors button
            $(document).on('click', '#aiohm-calendar-reset-all-colors-btn', function(e) {
                e.preventDefault();
                self.resetAllColorsToDefault();
            });

            $(document).on('click', '.aiohm-color-preset', function(e) {
                e.preventDefault();
                const color = $(this).data('color');
                $('#aiohm-color-input').val(color);
                $('#aiohm-color-text').val(color);
                self.updateColorPreview(color);
            });

            // Sync color input and text input
            $(document).on('input', '#aiohm-color-input', function() {
                const color = $(this).val();
                $('#aiohm-color-text').val(color);
                self.updateColorPreview(color);
            });

            $(document).on('input', '#aiohm-color-text', function() {
                let color = $(this).val();
                if (color.match(/^#[0-9A-F]{6}$/i)) {
                    $('#aiohm-color-input').val(color);
                    self.updateColorPreview(color);
                }
            });
        },

        // Open color picker modal
        openColorPicker: function(status) {
            const statusLabels = {
                'free': 'Free',
                'booked': 'Booked',
                'pending': 'Pending',
                'external': 'External',
                'blocked': 'Blocked',
                'special': 'High Season',
                'private': 'Private Event'
            };

            const currentColor = this.customColors[status] || this.defaultColors[status];

            // Set modal title and current color
            $('#aiohm-color-picker-title').text(`Choose Color for ${statusLabels[status]}`);
            $('#aiohm-color-input').val(currentColor);
            $('#aiohm-color-text').val(currentColor);
            this.updateColorPreview(currentColor);

            // Show modal
            $('#aiohm-color-picker-modal').removeClass('aiohm-hidden').fadeIn(200);
        },

        // Close color picker modal
        closeColorPicker: function() {
            $('#aiohm-color-picker-modal').fadeOut(200, function() {
                $(this).addClass('aiohm-hidden');
            });
        },

        // Update color preview in modal
        updateColorPreview: function(color) {
            $('#aiohm-current-color-swatch').css('background-color', color);
            $('#aiohm-current-color-code').text(color);
        },

        // Apply new color
        applyNewColor: function(status, color) {
            // Save to custom colors
            this.customColors[status] = color;
            localStorage.setItem('aiohm_calendar_colors', JSON.stringify(this.customColors));

            // Apply to current page
            this.applyColorToElements(status, color);

            // Send AJAX to update globally
            this.saveColorToServer(status, color);

            this.closeColorPicker();
            this.showNotification(`${status} color updated to ${color}!`, 'success');
        },

        // Reset color to default
        resetToDefault: function(status) {
            const defaultColor = this.defaultColors[status];
            delete this.customColors[status];
            localStorage.setItem('aiohm_calendar_colors', JSON.stringify(this.customColors));

            this.applyColorToElements(status, defaultColor);
            this.saveColorToServer(status, defaultColor);

            this.closeColorPicker();
            this.showNotification(`${status} color reset to default!`, 'success');
        },

        // Reset all colors to default
        resetAllColorsToDefault: function() {
            if (!confirm('Are you sure you want to reset all calendar colors to their default values?\n\nThis will restore the original color scheme for all status types.')) {
                return;
            }

            // Clear all custom colors
            this.customColors = {};
            localStorage.setItem('aiohm_calendar_colors', JSON.stringify(this.customColors));

            // Apply default colors to all statuses
            for (const [status, color] of Object.entries(this.defaultColors)) {
                this.applyColorToElements(status, color);
            }

            // Send AJAX request to reset all colors on server
            $.ajax({
                url: aiohm_booking_admin.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'aiohm_booking_reset_all_calendar_colors',
                    nonce: aiohm_booking_admin.nonce || ''
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('All calendar colors reset to default successfully!', 'success');
                    } else {
                        this.showNotification('Colors reset locally but failed to save to server.', 'warning');
                    }
                },
                error: (xhr, textStatus, errorThrown) => {
                    this.showNotification('Colors reset locally but failed to save to server.', 'warning');
                }
            });
        },

        // Apply custom colors on page load
        applyCustomColors: function() {
            // Apply brand color to cards on page load
            this.applyBrandColorToCards();

            for (const [status, color] of Object.entries(this.customColors)) {
                this.applyColorToElements(status, color);
            }
        },

        // Apply brand color to card elements
        applyBrandColorToCards: function() {
            const brandColor = (aiohm_booking_admin && aiohm_booking_admin.brand_color) || '#457d59';

            const style = document.createElement('style');
            style.id = 'aiohm-brand-card-colors';

            // Remove existing style if it exists
            const existing = document.getElementById(style.id);
            if (existing) existing.remove();

            // Card borders use fixed OHM green color via CSS variables - do not override
            style.textContent = `
                /* .aiohm-booking-card,
                .aiohm-module-card {
                    border-left-color: ${brandColor} !important;
                } */
            `;

            document.head.appendChild(style);
        },

        // Apply color to all matching elements
        applyColorToElements: function(status, color) {
            const style = document.createElement('style');
            style.id = `aiohm-custom-color-${status}`;

            // Remove existing custom style for this status
            const existing = document.getElementById(style.id);
            if (existing) existing.remove();

            // CSS selectors for this status
            const selectors = [
                `.legend-${status}`,
                `.aiohm-${status}`,
                `.aiohm-date-${status}`, // Add this for admin calendar cells
                `.aiohm-calendar-${status}`,
                `.booking-calendar-container .calendar-day.status-${status}`
            ];

            // Add badge selectors for event flags
            if (status === 'private') {
                selectors.push('.aiohm-private-badge', '.aiohm-badge-inline.aiohm-private-badge');
            } else if (status === 'special') {
                selectors.push('.aiohm-special-badge', '.aiohm-badge-inline.aiohm-special-badge');
            }

            // Special pricing/high season does NOT affect cell background colors
            // Only apply background colors for actual booking statuses (free, booked, pending, external, blocked)
            let css = '';

            // Apply dynamic card border color using brand color from accommodation settings
            const brandColor = (aiohm_booking_admin && aiohm_booking_admin.brand_color) || '#457d59';
            // Card borders use fixed OHM green color via CSS variables - do not override
            // css += `
            //     .aiohm-booking-card,
            //     .aiohm-module-card {
            //         border-left-color: ${brandColor} !important;
            //     }
            // `;

            // Apply borders to free cells using brand color
            if (status === 'free') {
                css += `
                    .legend-free,
                    .aiohm-calendar-date.free,
                    .booking-calendar-container .calendar-day.free {
                        border: 1px solid ${brandColor} !important;
                    }
                `;
            }
            if (status === 'special' || status === 'private') {
                // For special pricing and private events, only apply colors to badges and legends
                // Do NOT apply background colors to cells
                const badgeSelectors = selectors.filter(s =>
                    s.includes('badge') || s.includes('legend')
                );
                if (badgeSelectors.length > 0) {
                    css += `${badgeSelectors.join(', ')} { background: ${color} !important; }`;
                }

                // Also set CSS custom properties for badges
                if (status === 'private') {
                    css += `:root { --private-color: ${color} !important; --calendar-private: ${color} !important; }`;
                } else if (status === 'special') {
                    css += `:root { --special-color: ${color} !important; --calendar-special: ${color} !important; }`;
                }
            } else {
                // For actual booking statuses, apply background colors normally
                css += `${selectors.join(', ')} { background: ${color} !important; }`;
            }

            style.textContent = css;
            document.head.appendChild(style);
        },

        // Save color to server via AJAX
        saveColorToServer: function(status, color) {
            $.ajax({
                url: aiohm_booking_admin.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'aiohm_booking_save_calendar_color',
                    status: status,
                    color: color,
                    nonce: aiohm_booking_admin.nonce || ''
                },
                success: (response) => {
                    if (response.success) {
                    }
                },
                error: (xhr, textStatus, errorThrown) => {
                }
            });
        },

        // Load saved colors from server data
        loadSavedColors: function(savedColors) {
            Object.keys(savedColors).forEach(status => {
                const color = savedColors[status];
                this.applyColorToElements(status, color);
            });
        }
    };

    // Initialize the calendar
    AIOHMAdvancedCalendar.init();

    // Make it globally available
    window.AIOHMAdvancedCalendar = AIOHMAdvancedCalendar;
});

// Additional CSS for interactions (injected via JavaScript)
jQuery(document).ready(function($) {
    $('<style>').prop('type', 'text/css').html(`
        .aiohm-cell-hover {
            transform: scale(1.05) !important;
            z-index: 5 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2) !important;
        }

        .aiohm-row-selected {
            background: #f0f8ff !important;
        }

        .aiohm-loading-cell {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .aiohm-price-indicator {
            font-size: 10px;
            font-weight: bold;
            color: #333;
        }

        .aiohm-notification {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    `).appendTo('head');
});
