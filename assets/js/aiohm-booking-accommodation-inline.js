/**
 * Accommodation Module Inline JavaScript
 * Extracted from PHP template for better performance and CSP compliance
 */

jQuery(document).ready(function($) {
    // Handle dynamic accommodation count changes
    function updateAccommodationSections(newCount) {
        // Update accommodation statistics
        updateAccommodationStatistics(newCount);
        
        // Update accommodation details section
        updateAccommodationDetailsSection(newCount);
    }
    
    function updateAccommodationTypeText(accommodationType) {
        // Define accommodation types with singular and plural forms
        var accommodationTypes = {
            'unit': {'singular': 'Unit', 'plural': 'Units'},
            'house': {'singular': 'House', 'plural': 'Houses'},
            'apartment': {'singular': 'Apartment', 'plural': 'Apartments'},
            'villa': {'singular': 'Villa', 'plural': 'Villas'},
            'bungalow': {'singular': 'Bungalow', 'plural': 'Bungalows'},
            'cabin': {'singular': 'Cabin', 'plural': 'Cabins'},
            'cottage': {'singular': 'Cottage', 'plural': 'Cottages'},
            'suite': {'singular': 'Suite', 'plural': 'Suites'},
            'studio': {'singular': 'Studio', 'plural': 'Studios'},
            'unit': {'singular': 'Unit', 'plural': 'Units'},
            'space': {'singular': 'Space', 'plural': 'Spaces'}
        };
        
        var typeInfo = accommodationTypes[accommodationType] || accommodationTypes['unit'];
        var singular = typeInfo.singular;
        var plural = typeInfo.plural;
        var singularLower = singular.toLowerCase();
        var pluralLower = plural.toLowerCase();
        
        // Update page titles and headers
        $('h3').each(function() {
            var $h3 = $(this);
            var text = $h3.text();
            text = text.replace(/Accommodations Details/gi, plural + ' Details');
            text = text.replace(/Accommodations/gi, plural);
            text = text.replace(/Accommodation/gi, singular);
            text = text.replace(/Units/gi, plural);
            text = text.replace(/Unit/gi, singular);
            $h3.text(text);
        });
        
        // Update labels and descriptions
        $('p, label, .label, span').each(function() {
            var $el = $(this);
            var text = $el.text();
            var originalText = text;
            
            // Replace accommodation type references
            text = text.replace(/accommodations/gi, pluralLower);
            text = text.replace(/accommodation/gi, singularLower);
            text = text.replace(/units/gi, pluralLower);
            text = text.replace(/unit/gi, singularLower);
            
            if (text !== originalText) {
                $el.text(text);
            }
        });
    }
    
    // Handle field visibility toggles - update preview instantly
    $('.field-visibility-toggle').on('change', function() {
        var fieldName = $(this).data('field');
        var isVisible = $(this).prop('checked');
        
        // Update hidden input
        $(this).siblings('.field-visibility-input').val(isVisible ? '1' : '0');
        
        // Update preview via AJAX
        updatePreview();
    });
    
    // Handle required field toggles - update preview instantly
    $('.required-field-toggle').on('change', function() {
        var fieldName = $(this).data('field');
        var isRequired = $(this).prop('checked');
        
        // Update hidden input
        $(this).siblings('.required-field-input').val(isRequired ? '1' : '0');
        
        // Update preview via AJAX
        updatePreview();
    });
    
    // Handle color input changes - update preview instantly
    $('input[type="color"]').on('change input', function() {
        // Update preview via AJAX when colors change
        updatePreview();
    });
    
    function updatePreview() {
        // Get nonce from global variable if available
        var nonce = window.aiohm_booking_admin && window.aiohm_booking_admin.preview_nonce || '';
        
        // Collect all form data
        var formData = new FormData();
        formData.append('action', 'aiohm_booking_update_preview');
        formData.append('nonce', nonce);
        
        // Collect all field visibility settings
        $('.field-visibility-input').each(function() {
            formData.append($(this).attr('name'), $(this).val());
        });
        
        // Collect all required field settings
        $('.required-field-input').each(function() {
            formData.append($(this).attr('name'), $(this).val());
        });
        
        // Collect all color settings to ensure brand colors are applied
        $('input[type="color"]').each(function() {
            var name = $(this).attr('name');
            if (name) {
                formData.append(name, $(this).val());
            }
        });
        
        // Send AJAX request to update preview
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data.html) {
                    $('#booking-preview-content').html(response.data.html);
                }
            },
            error: function() {
                // Silent fail for preview updates
            }
        });
    }
    
    // Initialize accommodation type updates on page load
    var initialAccommodationType = $('select[name*="accommodation_type"]').val();
    if (initialAccommodationType) {
        updateAccommodationTypeText(initialAccommodationType);
    }
    
    // Handle accommodation type changes
    $('select[name*="accommodation_type"]').on('change', function() {
        var newType = $(this).val();
        updateAccommodationTypeText(newType);
        updatePreview();
    });
    
    // Handle accommodation count changes
    $('input[name*="available_accommodations"], input[name*="available_units"]').on('change', function() {
        var newCount = parseInt($(this).val()) || 1;
        var $input = $(this);
        var originalValue = $input.data('original-value') || $input.val();
        
        // Store original value for rollback if needed
        if (!$input.data('original-value')) {
            $input.data('original-value', originalValue);
        }
        
        // Show loading state
        $input.prop('disabled', true);
        var $container = $input.closest('.aiohm-setting-row');
        $container.addClass('loading');
        
        // Make AJAX call to sync accommodations
        $.ajax({
            url: aiohm_booking_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_booking_sync_accommodations',
                accommodation_count: newCount,
                nonce: aiohm_booking_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $container.removeClass('loading').addClass('success');
                    
                    // Update sections
                    updateAccommodationSections(newCount);
                    updatePreview();
                    
                    // Show success feedback
                    setTimeout(function() {
                        $container.removeClass('success');
                    }, 2000);
                    
                    // Update stored original value
                    $input.data('original-value', newCount);
                    
                    // Schedule page reload to show new accommodations
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Revert to original value on error
                    $input.val(originalValue);
                    if (typeof AIOHM_Booking_Admin !== 'undefined' && typeof AIOHM_Booking_Admin.showNotification === 'function') {
                        AIOHM_Booking_Admin.showNotification('Error syncing accommodations: ' + (response.data || 'Unknown error'), 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                // Revert to original value on error
                $input.val(originalValue);
                if (typeof AIOHM_Booking_Admin !== 'undefined' && typeof AIOHM_Booking_Admin.showNotification === 'function') {
                    AIOHM_Booking_Admin.showNotification('Network error occurred while syncing accommodations', 'error');
                }
            },
            complete: function() {
                $input.prop('disabled', false);
                $container.removeClass('loading');
            }
        });
    });
});