/**
 * AIOHM Booking Orders Admin JavaScript
 * Handles orders management and AI insights functionality
 *
 * @package AIOHM_Booking
 * @version 1.1.2
 */

(function($) {
    'use strict';

    // Orders admin object
    window.AIOHM_Booking_Orders_Admin = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Orders-specific event handlers
            $(document).on('click', '.aiohm-booking-orders-delete-link', this.handleDeleteConfirmation.bind(this));
        },

        // Handle delete confirmation
        handleDeleteConfirmation: function(e) {
            const confirmed = confirm(aiohm_booking_orders.i18n.confirm_delete);
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            return true;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AIOHM_Booking_Orders_Admin.init();
    });

})(jQuery);
