/**
 * AIOHM Booking Calendar Admin JavaScript
 * Handles calendar-specific admin functionality
 * 
 * @package AIOHM_Booking_PRO
 * @version 1.2.3
 */

(function($) {
    'use strict';

    // Calendar admin object
    window.AIOHM_Booking_Calendar_Admin = {
        
        init: function() {
            // Calendar-specific initialization
            this.initCalendar();
        },

        bindEvents: function() {
            // Calendar-specific event handlers (base events are handled automatically)
            $(document).on('click', '.aiohm-calendar-cell', this.handleCellClick);
            $(document).on('change', '.aiohm-calendar-filter', this.handleFilterChange);
            $(document).on('click', '.aiohm-calendar-nav', this.handleNavigation);
        },

        initCalendar: function() {
            // Initialize calendar functionality
            this.loadCalendarData();
            this.setupCalendarControls();
        },

        loadCalendarData: function() {
            // Load calendar data - this will be handled by the advanced calendar
            if (window.AIOHMAdvancedCalendar) {
                // Let the advanced calendar handle the initialization
                return;
            }
            
            // Fallback basic calendar loading
            this.loadBasicCalendar();
        },

        loadBasicCalendar: function() {
            var $calendar = $('.aiohm-calendar-grid');
            if ($calendar.length === 0) return;
            
            // Basic calendar loading logic
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_get_calendar_data',
                    nonce: (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) || ''
                },
                success: function(response) {
                    if (response.success && response.data) {
                        AIOHM_Booking_Calendar_Admin.renderCalendar(response.data);
                    }
                }
            });
        },

        renderCalendar: function(data) {
            // Render calendar data
            var $calendar = $('.aiohm-calendar-grid');
            
            $.each(data, function(date, dayData) {
                var $cell = $calendar.find('[data-date="' + date + '"]');
                if ($cell.length > 0) {
                    $cell.removeClass('free booked pending blocked external');
                    $cell.addClass(dayData.status || 'free');
                    
                    if (dayData.count) {
                        $cell.find('.booking-count').text(dayData.count);
                    }
                }
            });
        },

        setupCalendarControls: function() {
            // Setup calendar navigation and controls
            var $prevBtn = $('.aiohm-calendar-prev');
            var $nextBtn = $('.aiohm-calendar-next');
            var $monthSelect = $('.aiohm-calendar-month-select');
            
            if ($prevBtn.length || $nextBtn.length || $monthSelect.length) {
                // Controls exist, they should already be handled by advanced calendar
                return;
            }
        },

        handleCellClick: function(e) {
            e.preventDefault();
            
            var $cell = $(this);
            var date = $cell.data('date');
            var unitId = $cell.data('unit-id');
            
            if (!date) return;
            
            // Let the advanced calendar handle cell clicks if available
            if (window.AIOHMAdvancedCalendar && window.AIOHMAdvancedCalendar.handleCellClick) {
                return window.AIOHMAdvancedCalendar.handleCellClick($cell);
            }
            
            // Basic cell click handling
            AIOHM_Booking_Calendar_Admin.showCellMenu($cell, date, unitId);
        },

        showCellMenu: function($cell, date, unitId) {
            // Show basic cell menu
            var menu = $('<div class="aiohm-cell-menu">').html(
                '<div class="aiohm-menu-header">' +
                '<strong>' + date + '</strong>' +
                '<button class="aiohm-close-menu">&times;</button>' +
                '</div>' +
                '<div class="aiohm-menu-actions">' +
                '<button class="aiohm-set-status" data-status="free">Set Free</button>' +
                '<button class="aiohm-set-status" data-status="booked">Set Booked</button>' +
                '<button class="aiohm-set-status" data-status="pending">Set Pending</button>' +
                '<button class="aiohm-set-status" data-status="blocked">Set Blocked</button>' +
                '</div>'
            );
            
            $('body').append(menu);
            
            // Position menu near the cell
            var offset = $cell.offset();
            menu.css({
                position: 'absolute',
                top: offset.top + $cell.height(),
                left: offset.left,
                zIndex: 10000
            }).show();
        },

        handleFilterChange: function() {
            var $filter = $(this);
            var filterType = $filter.data('filter');
            var filterValue = $filter.val();
            
            // Apply filter to calendar
            this.applyCalendarFilter(filterType, filterValue);
        },

        applyCalendarFilter: function(type, value) {
            var $calendar = $('.aiohm-calendar-grid');
            
            if (type === 'status') {
                $calendar.find('.aiohm-calendar-cell').hide();
                if (value === 'all') {
                    $calendar.find('.aiohm-calendar-cell').show();
                } else {
                    $calendar.find('.aiohm-calendar-cell.' + value).show();
                }
            }
        },

        handleNavigation: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var direction = $btn.data('direction');
            
            // Let advanced calendar handle navigation if available
            if (window.AIOHMAdvancedCalendar && window.AIOHMAdvancedCalendar.navigate) {
                return window.AIOHMAdvancedCalendar.navigate(direction);
            }
            
            // Basic navigation
            this.navigateCalendar(direction);
        },

        navigateCalendar: function(direction) {
            // Basic calendar navigation
            var currentMonth = $('.aiohm-current-month').data('month') || new Date().getMonth();
            var currentYear = $('.aiohm-current-year').data('year') || new Date().getFullYear();
            
            if (direction === 'next') {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
            } else if (direction === 'prev') {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
            }
            
            this.loadCalendarMonth(currentYear, currentMonth);
        },

        loadCalendarMonth: function(year, month) {
            // Load specific calendar month
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_get_calendar_month',
                    year: year,
                    month: month,
                    nonce: (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) || ''
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $('.aiohm-calendar-container').html(response.data.html || '');
                    }
                }
            });
        },

        // Utility methods for calendar
        utils: {
            formatDate: function(date) {
                if (typeof date === 'string') {
                    date = new Date(date);
                }
                return date.toLocaleDateString();
            },
            
            isValidDate: function(date) {
                return date instanceof Date && !isNaN(date);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on a calendar admin page
        if ($('.aiohm-calendar-container').length > 0) {
            AIOHM_Booking_Calendar_Admin.init();
        }
    });

    // Handle menu clicks
    $(document).on('click', '.aiohm-close-menu', function() {
        $(this).closest('.aiohm-cell-menu').remove();
    });
    
    $(document).on('click', '.aiohm-set-status', function() {
        var $btn = $(this);
        var status = $btn.data('status');
        var $menu = $btn.closest('.aiohm-cell-menu');
        
        // Status change implementation handled by advanced calendar module
        if (typeof AIOHM_Booking_Advanced_Calendar !== 'undefined' && 
            typeof AIOHM_Booking_Advanced_Calendar.handleStatusChange === 'function') {
            AIOHM_Booking_Advanced_Calendar.handleStatusChange($btn, status);
        }
        
        $menu.remove();
    });

})(jQuery);