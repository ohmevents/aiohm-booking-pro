/**
 * AIOHM Booking jQuery Migrate Suppression
 * Suppresses jQuery Migrate warnings for the plugin
 *
 * @package AIOHM_Booking
 * @version 1.2.3
 */

(function($) {
    'use strict';

    // More compatible jQuery Migrate warning suppression
    // Instead of setting to false, keep it as an array but disable logging
    if (typeof jQuery !== 'undefined' && jQuery.migrateWarnings) {
        // If it's already an array, clear it but keep it as array
        if (Array.isArray(jQuery.migrateWarnings)) {
            jQuery.migrateWarnings.length = 0;
        } else {
            // Initialize as empty array if it's not an array
            jQuery.migrateWarnings = [];
        }
        
        // Disable migrate warning display
        if (jQuery.migrateTrace !== false) {
            jQuery.migrateTrace = false;
        }
    }
    
    if (typeof window !== 'undefined' && window.jQuery && window.jQuery.migrateWarnings) {
        // Same for window.jQuery
        if (Array.isArray(window.jQuery.migrateWarnings)) {
            window.jQuery.migrateWarnings.length = 0;
        } else {
            window.jQuery.migrateWarnings = [];
        }
        
        // Disable migrate warning display
        if (window.jQuery.migrateTrace !== false) {
            window.jQuery.migrateTrace = false;
        }
    }
    
    // Override the migrate warning function to be silent
    if (typeof jQuery !== 'undefined' && typeof jQuery.migrateWarning === 'function') {
        jQuery.migrateWarning = function() {
            // Silent - do nothing
        };
    }
})(jQuery);
