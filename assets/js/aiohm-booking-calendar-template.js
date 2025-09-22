/**
 * AIOHM Booking Calendar Template Scripts
 * Handles calendar-specific JavaScript functionality
 *
 * @package AIOHM_Booking_PRO
 * @version 1.2.3
 */

(function($) {
    'use strict';

    /**
     * Calendar Template Functionality
     */
    var AIOHM_Booking_Calendar = {

        init: function() {
            this.loadSavedColors();
            this.bindEvents();
        },

        /**
         * Load saved calendar colors from server
         */
        loadSavedColors: function() {
            // Load saved calendar colors - data is passed from PHP via wp_localize_script
            if (typeof aiohm_booking_calendar !== 'undefined' && aiohm_booking_calendar.saved_colors) {
                const savedColors = aiohm_booking_calendar.saved_colors;
                if (window.AIOHMAdvancedCalendar && window.AIOHMAdvancedCalendar.loadSavedColors) {
                    window.AIOHMAdvancedCalendar.loadSavedColors(savedColors);
                }
            }
        },

        /**
         * Bind calendar events
         */
        bindEvents: function() {
            // Calendar navigation functionality
            $('.calendar-prev, .calendar-next').on('click', function(e) {
                e.preventDefault();
                var direction = $(this).hasClass('calendar-prev') ? 'prev' : 'next';
                // This would typically make an AJAX call to load the calendar for the new period
            });

            // Calendar view selector
            $('.calendar-view-selector .button').on('click', function(e) {
                e.preventDefault();
                $('.calendar-view-selector .button').removeClass('button-primary').addClass('button-secondary');
                $(this).removeClass('button-secondary').addClass('button-primary');
                var view = $(this).data('view');
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AIOHM_Booking_Calendar.init();
    });

})(jQuery);
