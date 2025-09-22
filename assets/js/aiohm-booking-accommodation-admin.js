/**
 * AIOHM Booking Accommodation Admin JavaScript
 * Handles accommodation-specific admin functionality
 *
 * @package AIOHM_Booking_PRO
 * @version 1.1.2
 */

(function($) {
    'use strict';

    // Accommodation Admin object
    window.AIOHM_Booking_Accommodation_Admin = {

        init: function() {
            this.bindEvents();
            this.initSortableFields();
        },

        bindEvents: function() {
            // Field management events
            $(document).on('click', '.field-status-badge .status-badge', this.handleFieldStatusToggle);
            $(document).on('click', '.field-toggle-action .status-badge', this.handleFieldVisibilityToggle);
            $(document).on('change', '.field-visibility-input', this.handleFieldVisibilityChange);
            $(document).on('change', '.required-field-input', this.handleFieldRequiredChange);

            // Individual accommodation save events
            $(document).on('click', '.aiohm-individual-save-btn', (e) => this.handleIndividualSave(e));

            // Form submission
            $(document).on('submit', '.aiohm-booking-settings-form', this.handleSettingsFormSubmit);
        },

        initSortableFields: function() {
            // Initialize sortable functionality for field reordering
            if ($.fn.sortable && $('#sortable-fields').length) {
                $('#sortable-fields').sortable({
                    handle: '.field-drag-handle',
                    placeholder: 'aiohm-field-card-placeholder',
                    update: this.handleFieldOrderUpdate,
                    tolerance: 'pointer'
                });
            }
        },

        handleFieldStatusToggle: function(e) {
            e.preventDefault();
            var $badge = $(this);
            var fieldKey = $badge.data('field');
            var isRequired = $badge.hasClass('required');

            // Toggle between required and optional
            if (isRequired) {
                $badge.removeClass('required').addClass('optional').text('OPTIONAL');
                $badge.closest('.field-status-badge').find('.required-field-input').val('0');
            } else {
                $badge.removeClass('optional').addClass('required').text('REQUIRED');
                $badge.closest('.field-status-badge').find('.required-field-input').val('1');
            }
        },

        handleFieldVisibilityToggle: function(e) {
            e.preventDefault();
            var $badge = $(this);
            var fieldKey = $badge.data('field');
            var isAdded = $badge.hasClass('added');

            // Toggle between added and removed
            if (isAdded) {
                $badge.removeClass('added').addClass('removed').text('REMOVED');
                $badge.closest('.field-toggle-action').find('.field-visibility-input').val('0');
            } else {
                $badge.removeClass('removed').addClass('added').text('ADDED');
                $badge.closest('.field-toggle-action').find('.field-visibility-input').val('1');
            }
        },

        handleFieldVisibilityChange: function(e) {
            var $input = $(this);
            var fieldKey = $input.closest('.aiohm-field-card').data('field');
            var isVisible = $input.val() === '1';

            // Update the badge text
            var $badge = $input.closest('.field-toggle-action').find('.status-badge');
            if (isVisible) {
                $badge.removeClass('removed').addClass('added').text('ADDED');
            } else {
                $badge.removeClass('added').addClass('removed').text('REMOVED');
            }
        },

        handleFieldRequiredChange: function(e) {
            var $input = $(this);
            var fieldKey = $input.closest('.aiohm-field-card').data('field');
            var isRequired = $input.val() === '1';

            // Update the badge text
            var $badge = $input.closest('.field-status-badge').find('.status-badge');
            if (isRequired) {
                $badge.removeClass('optional').addClass('required').text('REQUIRED');
            } else {
                $badge.removeClass('required').addClass('optional').text('OPTIONAL');
            }
        },

        handleFieldOrderUpdate: function(event, ui) {
            var fieldOrder = [];
            $('#sortable-fields .aiohm-field-card').each(function() {
                fieldOrder.push($(this).data('field'));
            });

            // Update the hidden field order input
            $('#field-order-input').val(fieldOrder.join(','));

            // Show a visual indicator that the order has changed
            if (typeof AIOHM_Booking_Admin !== 'undefined' && typeof AIOHM_Booking_Admin.showNotification === 'function') {
                AIOHM_Booking_Admin.showNotification('Field order updated', 'success');
            } else if (typeof AIOHM_Booking_Base !== 'undefined' && typeof AIOHM_Booking_Base.showNotification === 'function') {
                AIOHM_Booking_Base.showNotification('Field order updated', 'success');
            } else {
                alert('Field order updated');
            }
        },

        handleIndividualSave: function(e) {
            e.preventDefault();
            var $button = $(e.target);
            var postId = $button.data('post-id');
            var $card = $button.closest('.aiohm-module-card');
            
            // Disable button and show loading state
            $button.prop('disabled', true);
            var originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update spin"></span> Saving...');
            
            // Collect accommodation data
            var accommodationData = {
                title: $card.find('input[name="aiohm_accommodations[' + postId + '][title]"]').val(),
                description: $card.find('textarea[name="aiohm_accommodations[' + postId + '][description]"]').val(),
                earlybird_price: $card.find('input[name="aiohm_accommodations[' + postId + '][earlybird_price]"]').val(),
                price: $card.find('input[name="aiohm_accommodations[' + postId + '][price]"]').val(),
                type: $card.find('select[name="aiohm_accommodations[' + postId + '][type]"]').val(),
                id: postId
            };
            
            // Send AJAX request
            $.ajax({
                url: aiohm_booking_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_save_individual_accommodation',
                    nonce: aiohm_booking_admin.nonce,
                    post_id: postId,
                    accommodation_data: accommodationData
                },
                success: function(response) {
                    if (response.success) {
                        // Use the correct notification function
                        if (typeof AIOHM_Booking_Admin !== 'undefined' && typeof AIOHM_Booking_Admin.showNotification === 'function') {
                            AIOHM_Booking_Admin.showNotification('Accommodation saved successfully!', 'success');
                        } else if (typeof AIOHM_Booking_Base !== 'undefined' && typeof AIOHM_Booking_Base.showNotification === 'function') {
                            AIOHM_Booking_Base.showNotification('Accommodation saved successfully!', 'success');
                        } else {
                            alert('Accommodation saved successfully!');
                        }
                    } else {
                        var errorMsg = response.data || 'Unknown error';
                        if (typeof AIOHM_Booking_Admin !== 'undefined' && typeof AIOHM_Booking_Admin.showNotification === 'function') {
                            AIOHM_Booking_Admin.showNotification('Failed to save accommodation: ' + errorMsg, 'error');
                        } else if (typeof AIOHM_Booking_Base !== 'undefined' && typeof AIOHM_Booking_Base.showNotification === 'function') {
                            AIOHM_Booking_Base.showNotification('Failed to save accommodation: ' + errorMsg, 'error');
                        } else {
                            alert('Failed to save accommodation: ' + errorMsg);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'AJAX error: ' + error;
                    if (typeof AIOHM_Booking_Admin !== 'undefined' && typeof AIOHM_Booking_Admin.showNotification === 'function') {
                        AIOHM_Booking_Admin.showNotification(errorMsg, 'error');
                    } else if (typeof AIOHM_Booking_Base !== 'undefined' && typeof AIOHM_Booking_Base.showNotification === 'function') {
                        AIOHM_Booking_Base.showNotification(errorMsg, 'error');
                    } else {
                        alert(errorMsg);
                    }
                },
                complete: function() {
                    // Re-enable button and restore original text
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        handleSettingsFormSubmit: function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"], input[type="submit"]');

            // Add loading state
            $submitBtn.prop('disabled', true);
            var originalText = $submitBtn.text();
            $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Saving...');

            // Re-enable after a delay (in case of client-side validation issues)
            setTimeout(function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof AIOHM_Booking_Accommodation_Admin !== 'undefined') {
            AIOHM_Booking_Accommodation_Admin.init();
        }
    });

})(jQuery);
