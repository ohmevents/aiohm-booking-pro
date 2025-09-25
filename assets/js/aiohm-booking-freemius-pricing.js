/**
 * AIOHM Booking Freemius Pricing Page JavaScript
 * Handles dynamic header injection and pricing page enhancements
 *
 * @package AIOHM_Booking
 * @since 2.0.3
 */

jQuery(document).ready(function($) {
    // Add consistent header to pricing page
    var headerHTML = '<div class="aiohm-booking-admin-header">' +
        '<div class="aiohm-booking-admin-header-content">' +
            '<div class="aiohm-booking-admin-logo">' +
                '<img src="' + aiohm_booking_vars.plugin_url + 'assets/images/aiohm-booking-OHM_logo-black.svg" alt="AIOHM" class="aiohm-booking-admin-header-logo">' +
            '</div>' +
            '<div class="aiohm-booking-admin-header-text">' +
                '<h1>Upgrade to AIOHM Booking Pro</h1>' +
                '<p class="aiohm-booking-admin-tagline">Unlock premium features with secure payments and AI analytics</p>' +
            '</div>' +
        '</div>' +
    '</div>';
    
    // Insert header at the beginning of the pricing wrapper
    function insertHeader() {
        if ($('#fs_pricing_wrapper').length > 0 && $('.aiohm-booking-admin-header').length === 0) {
            $('#fs_pricing_wrapper').prepend(headerHTML);
        } else if ($('.wrap.fs-full-size-wrapper').length > 0 && $('.aiohm-booking-admin-header').length === 0) {
            $('.wrap.fs-full-size-wrapper').prepend(headerHTML);
        }
    }
    
    // Try immediately
    insertHeader();
    
    // Also try after a delay in case elements load later
    setTimeout(insertHeader, 1000);
    
    // Watch for dynamic content changes (Freemius loads content via AJAX)
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                insertHeader();
            }
        });
    });
    
    // Start observing the document with the configured parameters
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});