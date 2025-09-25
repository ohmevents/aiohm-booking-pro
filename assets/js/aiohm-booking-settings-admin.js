/**
 * AIOHM Booking Settings Admin JavaScript
 * Handles settings page interactions and toggles
 *
 * @package AIOHM_Booking_PRO
 * @version 1.2.3
 */

(function($) {
    'use strict';

    // Settings admin object
    window.AIOHM_Booking_Settings_Admin = {
        initialized: false,

        init: function() {
            if (this.initialized) {
                return;
            }
            
            this.initialized = true;
            this.bindEvents();
            this.initToggles();
            this.initSortableFields();
            this.initEventCardToggles();
            
            // Re-bind events after a short delay to ensure DOM is ready
            setTimeout(function() {
                AIOHM_Booking_Settings_Admin.bindEvents();
            }, 2000);
            
            // Set up mutation observer to watch for dynamically added content
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                            // Check if any added nodes contain status badges
                            var hasBadges = false;
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    if ($(node).find('.status-badge[data-field]').length > 0 || $(node).hasClass('status-badge')) {
                                        hasBadges = true;
                                    }
                                }
                            });
                            if (hasBadges) {
                                setTimeout(function() {
                                    AIOHM_Booking_Settings_Admin.bindEvents();
                                }, 100);
                            }
                        }
                    });
                });
                
                // Start observing
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
                
            }
        },

        bindEvents: function() {
            // Clear any existing events to prevent duplicates
            $(document).off('.aiohm-settings');
            
            // Settings page event handlers with namespace
            $(document).on('change.aiohm-settings', '.toggle-input', AIOHM_Booking_Settings_Admin.handleSettingsToggleSwitch);
            $(document).on('change.aiohm-settings', '.aiohm-toggle-switch input[type="checkbox"]', AIOHM_Booking_Settings_Admin.handleSettingsToggleSwitch);
            $(document).on('click.aiohm-settings', '.aiohm-module-toggle-badge', AIOHM_Booking_Settings_Admin.handleModuleToggleBadge);
            $(document).on('click.aiohm-settings', '.aiohm-configure-btn', AIOHM_Booking_Settings_Admin.handleConfigureButton);
            $(document).on('click.aiohm-settings', '.aiohm-settings-save, .aiohm-booking-settings-save-btn, .aiohm-form-customization-save-btn', AIOHM_Booking_Settings_Admin.handleSaveSettings);
            $(document).on('click.aiohm-settings', '.aiohm-settings-reset', AIOHM_Booking_Settings_Admin.handleResetSettings);
            $(document).on('click.aiohm-settings', '[data-action="scroll"]', AIOHM_Booking_Settings_Admin.handleScrollAction);
            $(document).on('click.aiohm-settings', '.aiohm-card-toggle-btn', AIOHM_Booking_Settings_Admin.handleCardToggle);
            $(document).on('click.aiohm-settings', '.aiohm-copy-btn', AIOHM_Booking_Settings_Admin.handleCopyButton);
            $(document).on('click.aiohm-settings', '.aiohm-input-copy', AIOHM_Booking_Settings_Admin.handleInputCopy);
            $(document).on('click.aiohm-settings', '.aiohm-preset-btn', AIOHM_Booking_Settings_Admin.handlePresetButton);
            $(document).on('click.aiohm-settings', '[data-action="test-connection"]', AIOHM_Booking_Settings_Admin.handleTestConnection);
            
            // Event buttons handlers
            $(document).on('click.aiohm-settings', '.aiohm-clone-event-btn', this.handleCloneEvent);
            $(document).on('click.aiohm-settings', '.aiohm-delete-event-btn', this.handleDeleteEvent);
            $(document).on('click.aiohm-settings', '.aiohm-eventon-import-btn', AIOHM_Booking_Settings_Admin.handleEventONImport);
            // Note: AI Import is handled by aiohm-booking-ai-import.js
            
            // Event card toggle handlers
            $(document).on('click.aiohm-settings', '.aiohm-event-toggle-btn', AIOHM_Booking_Settings_Admin.handleEventCardToggle);
            
            // Event type color picker handlers
            $(document).on('change.aiohm-settings', '.aiohm-event-type-color-picker', AIOHM_Booking_Settings_Admin.handleEventTypeColorChange);
            
            // Event type input change handlers
            $(document).on('input.aiohm-settings change.aiohm-settings', 'input[name*="[event_type]"]', AIOHM_Booking_Settings_Admin.handleEventTypeInputChange);
            // Bind status badge events with more specific selectors
            $(document).on('click.aiohm-settings', '.status-badge[data-field]', AIOHM_Booking_Settings_Admin.handleFieldStatusToggle);
            $(document).on('click.aiohm-settings', '.field-status-badge .status-badge', AIOHM_Booking_Settings_Admin.handleFieldStatusToggle);
            $(document).on('click.aiohm-settings', '.field-toggle-action .status-badge', AIOHM_Booking_Settings_Admin.handleFieldStatusToggle);
            
            $(document).on('change.aiohm-settings', '.field-visibility-input', AIOHM_Booking_Settings_Admin.handleFieldVisibilityChange);
            $(document).on('change.aiohm-settings', '.required-field-input', AIOHM_Booking_Settings_Admin.handleFieldRequiredChange);
            
            
            // Also bind events to specific containers that might be loaded later
            $(document).on('click.aiohm-settings', '.aiohm-form-customization-content .status-badge[data-field]', AIOHM_Booking_Settings_Admin.handleFieldStatusToggle);
            
            // Add direct click handlers as backup
            $('.status-badge[data-field]').off('click.direct').on('click.direct', function(e) {
                AIOHM_Booking_Settings_Admin.handleFieldStatusToggle.call(this, e);
            });
            
            // Bind color change events for direct preview updates
            $(document).on('change.aiohm-settings input.aiohm-settings', '.form-color-input', AIOHM_Booking_Settings_Admin.handleColorChange);
            
            // Bind preview button events
            $(document).on('click.aiohm-settings', '.aiohm-preview-modal-btn', AIOHM_Booking_Settings_Admin.openPreviewModal);
            $(document).on('click.aiohm-settings', '.aiohm-preview-tab-btn', AIOHM_Booking_Settings_Admin.openPreviewTab);
            $(document).on('click.aiohm-settings', '.aiohm-create-test-page-btn', AIOHM_Booking_Settings_Admin.createTestPage);
            
            // Bind form customization save button
            $(document).on('click.aiohm-settings', '.aiohm-form-customization-save-btn', function(e) {
                e.preventDefault();
                AIOHM_Booking_Settings_Admin.handleFormCustomizationSave($(this));
            });
        },

        initSortableFields: function() {
            // Initialize sortable functionality for field reordering
            if (typeof $.fn.sortable !== 'undefined' && $('#sortable-fields').length) {
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
            e.stopPropagation();
            
            var $badge = $(this);
            var fieldKey = $badge.data('field');
            
            
            // Check if this is the required/optional badge
            if ($badge.closest('.field-status-badge').length) {
                var isRequired = $badge.hasClass('required');
                
                // Toggle between required and optional
                if (isRequired) {
                    $badge.removeClass('required').addClass('optional').text('OPTIONAL');
                    $badge.closest('.field-status-badge').find('.required-field-input').val('0');
                } else {
                    $badge.removeClass('optional').addClass('required').text('REQUIRED');
                    $badge.closest('.field-status-badge').find('.required-field-input').val('1');
                }
            }
            // Check if this is the added/removed badge
            else if ($badge.closest('.field-toggle-action').length) {
                var isAdded = $badge.hasClass('added');
                
                // Toggle between added and removed
                if (isAdded) {
                    $badge.removeClass('added').addClass('removed').text('REMOVED');
                    $badge.closest('.field-toggle-action').find('.field-visibility-input').val('0');
                } else {
                    $badge.removeClass('removed').addClass('added').text('ADDED');
                    $badge.closest('.field-toggle-action').find('.field-visibility-input').val('1');
                }
            } else {
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
            AIOHM_Booking_Settings_Admin.showNotice('Field order updated', 'success');
        },

        initToggles: function() {
            // Initialize toggle switches
            $('.toggle-input, .aiohm-toggle-switch input[type="checkbox"]').each(function() {
                var $input = $(this);
                var $toggle = $input.closest('.aiohm-toggle-switch');
                var $text = $toggle.find('.toggle-text').add($toggle.siblings('.toggle-text'));

                if ($text.length) {
                    $text.text($input.is(':checked') ?
                        AIOHM_Booking_Settings_Admin.getToggleText(true) :
                        AIOHM_Booking_Settings_Admin.getToggleText(false));
                }
            });
        },

        /**
         * Handle settings toggle switch changes
         */
        handleSettingsToggleSwitch: function() {
            var $input = $(this);
            var $toggle = $input.closest('.aiohm-toggle-switch');
            var $text = $toggle.find('.toggle-text').add($toggle.siblings('.toggle-text'));

            if ($text.length) {
                $text.text($input.is(':checked') ?
                    AIOHM_Booking_Settings_Admin.getToggleText(true) :
                    AIOHM_Booking_Settings_Admin.getToggleText(false));
            }

            // Trigger custom event for other scripts to listen to
            $input.trigger('aiohm:toggle-changed', [$input.is(':checked')]);
            
            // Refresh preview if toggle affects form display
            if ($input.closest('.aiohm-form-customization-form').length) {
                AIOHM_Booking_Settings_Admin.refreshDirectPreview($input.closest('.aiohm-form-customization-form'));
            }
        },

        getToggleText: function(enabled) {
            return enabled ? 'Enabled' : 'Disabled';
        },

        /**
         * Convert hex color to RGB array
         */
        hexToRgb: function(hex) {
            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? [
                parseInt(result[1], 16),
                parseInt(result[2], 16),
                parseInt(result[3], 16)
            ] : [0, 0, 0];
        },

        /**
         * Refresh direct preview with current color settings
         */
        refreshDirectPreview: function($form) {
            if (!$form || !$form.length) return;
            
            // Look for preview content in the right column or direct preview content
            var $previewContent = $form.find('.aiohm-form-customization-right');
            if (!$previewContent.length) {
                $previewContent = $form.find('.aiohm-direct-preview-content');
            }
            if (!$previewContent.length) return;
            
            // Get current color values
            var primaryColor = $form.find('input[name*="[form_primary_color]"]').val() || '#457d59';
            var textColor = $form.find('input[name*="[form_text_color]"]').val() || '#333333';
            
            // Update CSS custom properties in preview content
            var $previewBooking = $previewContent.find('.aiohm-booking-modern, .aiohm-booking-form, .aiohm-booking-event-selection-card');
            if ($previewBooking.length) {
                $previewBooking.css({
                    '--ohm-primary': primaryColor,
                    '--ohm-primary-hover': primaryColor,
                    '--ohm-booking-primary': primaryColor,
                    '--ohm-booking-primary-hover': primaryColor,
                    '--aiohm-brand-color': primaryColor,
                    '--ohm-text-color': textColor,
                    '--aiohm-text-color': textColor,
                    '--ohm-primary-light': 'rgba(' + this.hexToRgb(primaryColor).join(',') + ',0.1)'
                });
                
                // Also update root CSS variables for the document
                $('body').css({
                    '--ohm-primary': primaryColor,
                    '--ohm-booking-primary': primaryColor,
                    '--aiohm-brand-color': primaryColor,
                    '--aiohm-text-color': textColor
                });

                // Update sandwich header/footer CSS variables specifically
                $('.aiohm-booking-sandwich-header, .aiohm-booking-sandwich-footer').css({
                    '--aiohm-brand-color': primaryColor,
                    '--aiohm-text-color': textColor
                });
                
                // Also update any buttons, links, and other elements that use the primary color
                $previewBooking.find('button, .button, input[type="submit"], .aiohm-submit-btn').css({
                    'background-color': primaryColor,
                    'border-color': primaryColor
                });
                
                $previewBooking.find('a').css('color', primaryColor);
                $previewBooking.find('h1, h2, h3, h4, h5, h6').css('color', textColor);
            }
        },

        /**
         * Handle color input changes
         */
        handleColorChange: function() {
            var $colorInput = $(this);
            var $form = $colorInput.closest('.aiohm-form-customization-form');
            
            // Debounce color changes to avoid too many updates
            clearTimeout(this.colorChangeTimeout);
            this.colorChangeTimeout = setTimeout(function() {
                AIOHM_Booking_Settings_Admin.refreshDirectPreview($form);
            }, 300);
        },

        /**
         * Open preview in a modal popup
         */
        openPreviewModal: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var previewUrl = $btn.data('preview-url');
            var formType = $btn.data('form-type');
            
            // Create modal if it doesn't exist
            if (!$('#aiohm-preview-modal').length) {
                $('body').append(`
                    <div id="aiohm-preview-modal" class="aiohm-modal-overlay">
                        <div class="aiohm-modal-container">
                            <div class="aiohm-modal-header">
                                <h3>Booking Form Preview</h3>
                                <button type="button" class="aiohm-modal-close">&times;</button>
                            </div>
                            <div class="aiohm-modal-content">
                                <iframe id="aiohm-preview-iframe" src="" width="100%" height="600" frameborder="0"></iframe>
                            </div>
                        </div>
                    </div>
                `);
                
                // Bind close events
                $(document).on('click', '#aiohm-preview-modal .aiohm-modal-close, #aiohm-preview-modal .aiohm-modal-overlay', function(e) {
                    if (e.target === this) {
                        AIOHM_Booking_Settings_Admin.closePreviewModal();
                    }
                });
                
                // Bind escape key
                $(document).on('keyup', function(e) {
                    if (e.keyCode === 27 && $('#aiohm-preview-modal').is(':visible')) {
                        AIOHM_Booking_Settings_Admin.closePreviewModal();
                    }
                });
            }
            
            // Update iframe src and show modal
            $('#aiohm-preview-iframe').attr('src', previewUrl);
            $('#aiohm-preview-modal').fadeIn(200);
            $('body').addClass('aiohm-modal-open');
        },

        /**
         * Close preview modal
         */
        closePreviewModal: function() {
            $('#aiohm-preview-modal').fadeOut(200);
            $('body').removeClass('aiohm-modal-open');
            // Clear iframe src to stop any ongoing requests
            $('#aiohm-preview-iframe').attr('src', 'about:blank');
        },

        /**
         * Open preview in new tab
         */
        openPreviewTab: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var previewUrl = $btn.data('preview-url');
            
            // Open in new tab/window
            window.open(previewUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        },

        createTestPage: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var formType = $btn.data('form-type');
            var originalText = $btn.html();
            
            // Disable button and show loading
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creating...');
            
            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_create_test_page',
                    form_type: formType,
                    nonce: wp.ajax.settings.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        var $message = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p><strong>' + response.data.message + '</strong></p></div>');
                        $btn.closest('.aiohm-preview-actions').after($message);
                        
                        // Auto-dismiss after 3 seconds
                        setTimeout(function() {
                            $message.fadeOut();
                        }, 3000);
                        
                        // Open the created page in new tab
                        window.open(response.data.page_url, '_blank');
                        
                        // Update button text
                        $btn.html('<span class="dashicons dashicons-external"></span> View Test Page');
                        
                        // Store page URL for future clicks
                        $btn.data('page-url', response.data.page_url);
                        
                    } else {
                        // Show error message
                        var $message = $('<div class="notice notice-error is-dismissible" style="margin: 10px 0;"><p><strong>Error:</strong> ' + (response.data || 'Failed to create test page') + '</p></div>');
                        $btn.closest('.aiohm-preview-actions').after($message);
                        
                        // Auto-dismiss after 5 seconds
                        setTimeout(function() {
                            $message.fadeOut();
                        }, 5000);
                    }
                },
                error: function() {
                    // Show error message
                    var $message = $('<div class="notice notice-error is-dismissible" style="margin: 10px 0;"><p><strong>Error:</strong> Failed to create test page. Please try again.</p></div>');
                    $btn.closest('.aiohm-preview-actions').after($message);
                    
                    // Auto-dismiss after 5 seconds
                    setTimeout(function() {
                        $message.fadeOut();
                    }, 5000);
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false);
                    
                    // If we have a stored page URL, make this a view button instead
                    if (!$btn.data('page-url')) {
                        $btn.html(originalText);
                    }
                }
            });
            
            // If we already have a page URL, just open it
            if ($btn.data('page-url')) {
                window.open($btn.data('page-url'), '_blank');
                $btn.prop('disabled', false);
                return;
            }
        },

        /**
         * Handle module toggle badge clicks (for primary modules)
         */
        handleModuleToggleBadge: function(e) {
            e.preventDefault();
            
            var $badge = $(this);
            var $moduleCard = $badge.closest('.aiohm-module-card');
            var module = $badge.data('module');
            var currentlyEnabled = $badge.data('enabled') === 1 || $badge.data('enabled') === '1';
            var newEnabled = !currentlyEnabled;

            // Prevent double clicks
            if ($badge.hasClass('saving')) {
                return;
            }
            
            $badge.addClass('saving');

            // Save the setting via AJAX
            var setting = 'enable_' + module;
            
            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_save_toggle_setting',
                    setting: setting,
                    value: newEnabled ? 1 : 0,
                    nonce: aiohm_booking_admin.nonce
                },
                success: function(response) {
                    $badge.removeClass('saving');
                    
                    if (response.success) {
                        // Update badge appearance and text
                        $badge
                            .removeClass('enabled disabled')
                            .addClass(newEnabled ? 'enabled' : 'disabled')
                            .text(newEnabled ? 'ENABLED' : 'DISABLED')
                            .data('enabled', newEnabled ? 1 : 0);

                        // Update module card appearance
                        if (newEnabled) {
                            $moduleCard.removeClass('is-inactive').addClass('is-active');
                        } else {
                            $moduleCard.removeClass('is-active').addClass('is-inactive');
                        }

                        // Handle dependent modules
                        if (response.data && response.data.dependent_modules) {
                            response.data.dependent_modules.forEach(function(depInfo) {
                                var dependentModule = depInfo.module;
                                var action = depInfo.action;
                                var $dependentCard = $('.aiohm-module-card[data-id="' + dependentModule + '"]');
                                var $dependentBadge = $dependentCard.find('.aiohm-module-status');
                                
                                if ($dependentCard.length) {
                                    if (action === 'disabled') {
                                        // Update dependent module appearance to disabled
                                        $dependentCard
                                            .removeClass('is-active')
                                            .addClass('is-inactive is-dependent');
                                        
                                        // Update dependent module badge to disabled
                                        $dependentBadge
                                            .removeClass('enabled')
                                            .addClass('disabled aiohm-dependent-badge')
                                            .text('DISABLED')
                                            .attr('title', 'Disabled because Accommodations module is disabled');
                                    } else if (action === 'enabled') {
                                        // Update dependent module appearance to enabled
                                        $dependentCard
                                            .removeClass('is-inactive is-dependent')
                                            .addClass('is-active');
                                        
                                        // Update dependent module badge to enabled
                                        $dependentBadge
                                            .removeClass('disabled aiohm-dependent-badge')
                                            .addClass('enabled')
                                            .text('ENABLED')
                                            .removeAttr('title');
                                    }
                                }
                            });
                        }

                        AIOHM_Booking_Settings_Admin.showNotice(
                            response.data.message || (module.charAt(0).toUpperCase() + module.slice(1) + ' module ' + (newEnabled ? 'enabled' : 'disabled') + ' successfully!'),
                            'success'
                        );
                    } else {
                        AIOHM_Booking_Settings_Admin.showNotice(
                            'Failed to update ' + module + ' module: ' + (response.data || 'Unknown error'),
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    $badge.removeClass('saving');
                    AIOHM_Booking_Settings_Admin.showNotice(
                        'Error updating ' + module + ' module: ' + error,
                        'error'
                    );
                }
            });
        },

        /**
         * Handle configure button clicks - force navigation for admin page modules
         */
        handleConfigureButton: function(e) {
            var $button = $(this);
            var href = $button.attr('href');
            
            // If href exists and it's a valid admin page URL, navigate to it
            if (href && href.indexOf('admin.php?page=') !== -1) {
                window.location.href = href;
                return false;
            }
        },

        handleSaveSettings: function(e) {
            var $button = $(this);
            
            // Check if this is a save button (main settings or individual module)
            if ($button.hasClass('aiohm-booking-settings-save-btn') || 
                ($button.attr('name') && $button.attr('name').startsWith('save_'))) {
                // Don't prevent default - let the form submit normally for all save buttons
                return;
            }
            
            e.preventDefault();
            
            // Check if this is a form customization save button
            if ($button.hasClass('aiohm-form-customization-save-btn')) {
                AIOHM_Booking_Settings_Admin.handleFormCustomizationSave($button);
                return;
            }

            var $form = $button.closest('form');

            $button.prop('disabled', true).text('Saving...');

            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=aiohm_save_settings&nonce=' + aiohm_booking_admin.nonce,
                success: function(response) {
                    $button.prop('disabled', false).text('Save Settings');

                    if (response.success) {
                        AIOHM_Booking_Settings_Admin.showNotice('Settings saved successfully!', 'success');
                    } else {
                        AIOHM_Booking_Settings_Admin.showNotice('Error saving settings: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Save Settings');
                    AIOHM_Booking_Settings_Admin.showNotice('Network error while saving settings.', 'error');
                }
            });
        },

        handleResetSettings: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to reset all settings to defaults?')) {
                return;
            }

            var $button = $(this);
            $button.prop('disabled', true).text('Resetting...');

            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_reset_settings',
                    nonce: aiohm_booking_admin.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Reset Settings');

                    if (response.success) {
                        location.reload();
                    } else {
                        AIOHM_Booking_Settings_Admin.showNotice('Error resetting settings: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Reset Settings');
                    AIOHM_Booking_Settings_Admin.showNotice('Network error while resetting settings.', 'error');
                }
            });
        },

        handleScrollAction: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var target = $button.data('target');
            
            
            if (target) {
                var $target = $(target);
                
                
                if ($target.length) {
                    // Smooth scroll to the target element
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 100 // 100px offset from top
                    }, 800);
                    
                    // Add highlight effect to the target card
                    $target.addClass('aiohm-highlight');
                    setTimeout(function() {
                        $target.removeClass('aiohm-highlight');
                    }, 2000);
                    
                }
            }
        },

        // Drag mode functionality removed to eliminate conflicts

        handleCardToggle: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var target = $button.data('target');
            var $targetCard = $('#' + target);
            
            if ($targetCard.length) {
                var $content = $targetCard.find('.aiohm-card__content');
                var $icon = $button.find('.dashicons');
                
                if ($content.is(':visible')) {
                    // Hide content
                    $content.slideUp(200);
                    $button.addClass('collapsed');
                    $targetCard.addClass('collapsed'); // Add collapsed class to card
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    // Show content
                    $content.slideDown(200);
                    $button.removeClass('collapsed');
                    $targetCard.removeClass('collapsed'); // Remove collapsed class from card
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                }
            }
        },

        handleCopyButton: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var shortcode = $button.data('shortcode');
            var originalIcon = $button.html();
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                // Copy to clipboard
                navigator.clipboard.writeText(shortcode).then(function() {
                    // Show success feedback
                    $button.html('<span class="dashicons dashicons-yes"></span>');
                    $button.css('color', '#46b450');
                    
                    // Reset after 2 seconds
                    setTimeout(function() {
                        $button.html(originalIcon);
                        $button.css('color', '');
                    }, 2000);
                }).catch(function(err) {
                    AIOHM_Booking_Settings_Admin.showNotice('Failed to copy to clipboard', 'error');
                });
            } else {
                AIOHM_Booking_Settings_Admin.showNotice('Clipboard not supported in this browser', 'error');
            }
        },

        handleInputCopy: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var target = $button.data('target');
            var $input = $('#' + target);
            
            if ($input.length && $input.val()) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText($input.val()).then(function() {
                        AIOHM_Booking_Settings_Admin.showNotice('Copied to clipboard!', 'success');
                    }).catch(function(err) {
                        AIOHM_Booking_Settings_Admin.showNotice('Failed to copy to clipboard', 'error');
                    });
                } else {
                    AIOHM_Booking_Settings_Admin.showNotice('Clipboard not supported in this browser', 'error');
                }
            } else {
                AIOHM_Booking_Settings_Admin.showNotice('Nothing to copy', 'error');
            }
        },

        handleTestConnection: function(e) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Prevent duplicate handlers
            
            var $button = $(this);
            var provider = $button.data('provider');
            
            // Skip Stripe connections - handled by dedicated stripe-settings.js
            if (provider === 'stripe') {
                return;
            }
            
            // Capture original text more reliably
            var $btnText = $button.find('.btn-text');
            var originalText = '';
            if ($btnText.length > 0) {
                originalText = $btnText.text().trim();
            } else {
                originalText = $button.text().trim();
            }
            
            // Prevent multiple simultaneous requests
            if ($button.hasClass('testing')) {
                return;
            }
            $button.addClass('testing');
            
            // Show loading state
            $button.prop('disabled', true);
            if ($btnText.length > 0) {
                $btnText.text('Testing...');
            } else {
                $button.text('Testing...');
            }
            
            // Get provider-specific nonce
            var nonce = '';
            if (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin[provider + '_nonce']) {
                nonce = aiohm_booking_admin[provider + '_nonce'];
            } else if (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.nonce) {
                nonce = aiohm_booking_admin.nonce;
            }
            
            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_test_' + provider,
                    nonce: nonce
                },
                success: function(response) {
                    
                    $button.removeClass('testing');
                    $button.prop('disabled', false);
                    
                    // Restore original button text
                    var $btnText = $button.find('.btn-text');
                    if ($btnText.length) {
                        $btnText.text(originalText);
                    } else {
                        $button.text(originalText);
                    }
                    
                    if (response.success) {
                        // Handle different response data formats
                        var message = 'Connection successful!';
                        if (typeof response.data === 'string') {
                            message = response.data;
                        } else if (response.data && typeof response.data === 'object') {
                            message = response.data.message || response.data || 'Connection successful!';
                        }
                        
                        AIOHM_Booking_Settings_Admin.showNotice(message, 'success');
                    } else {
                        // Handle error response data formats
                        var errorMessage = 'Test failed: Unknown error';
                        if (typeof response.data === 'string') {
                            errorMessage = 'Test failed: ' + response.data;
                        } else if (response.data && typeof response.data === 'object') {
                            errorMessage = 'Test failed: ' + (response.data.message || response.data || 'Unknown error');
                        }
                        
                        AIOHM_Booking_Settings_Admin.showNotice(errorMessage, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX error occurred
                    $button.removeClass('testing');
                    $button.prop('disabled', false);
                    if ($button.find('.btn-text').length) {
                        $button.find('.btn-text').text(originalText);
                    } else {
                        $button.text(originalText);
                    }
                    AIOHM_Booking_Settings_Admin.showNotice('Network error while testing connection', 'error');
                }
            });
        },

        handlePresetButton: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var endpoint = $button.data('endpoint');
            var target = $button.data('target');
            
            if (endpoint && target) {
                var $targetInput = $('#' + target);
                if ($targetInput.length) {
                    $targetInput.val(endpoint);
                    
                    // Visual feedback
                    $button.addClass('aiohm-preset-active');
                    setTimeout(function() {
                        $button.removeClass('aiohm-preset-active');
                    }, 200);
                    
                    // Update other preset buttons to show which one is active
                    $button.siblings('.aiohm-preset-btn').removeClass('aiohm-preset-selected');
                    $button.addClass('aiohm-preset-selected');
                }
            }
        },

        saveModuleSettings: function($button, $form, moduleName) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=aiohm_save_module_settings&module=' + moduleName + '&nonce=' + aiohm_booking_admin.nonce,
                success: function(response) {
                    $button.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        AIOHM_Booking_Settings_Admin.showNotice(response.data || 'Settings saved successfully!', 'success');
                    } else {
                        AIOHM_Booking_Settings_Admin.showNotice('Error saving settings: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(originalText);
                    AIOHM_Booking_Settings_Admin.showNotice('Network error while saving settings', 'error');
                }
            });
        },

        handleFormCustomizationSave: function($button) {
            $button.prop('disabled', true).text('Saving...');

            // Find the form customization form (the form contains the card, not vice versa)
            var $container = $button.closest('form.aiohm-form-customization-form');

            // Get the option name from the hidden field
            var optionName = $container.find('input[name="option_name"]').val() || 'aiohm_booking_form_settings';
            var formType = $container.find('input[name="form_type"]').val() || 'tickets';

            // Create the data object with the correct structure
            var formData = {
                action: 'aiohm_save_form_settings',
                form_type: formType,
                option_name: optionName
            };

            // Add nonce - try multiple possible nonce field names
            var nonceField = $container.find('input[name="aiohm_form_settings_nonce"], input[name="_wpnonce"]');
            if (nonceField.length > 0) {
                formData.aiohm_form_settings_nonce = nonceField.val();
            } else {
                // Try to find any nonce field in the entire form
                var allNonceFields = $container.find('input[type="hidden"]').filter(function() {
                    return $(this).attr('name') && $(this).attr('name').indexOf('nonce') !== -1;
                });
            }
            
            // Create the settings object with the correct key
            // formData[optionName] = {};
            
            // Collect all input, select, and textarea fields and structure them properly
            var fieldCount = 0;
            $container.find('input[name], select[name], textarea[name]').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                var type = $field.attr('type');
                
                fieldCount++;
                
                // Skip certain fields
                if (name === 'aiohm_form_settings_nonce' || name === 'action' || name === 'form_type' || name === 'option_name') {
                    return;
                }
                
                // For form fields, send them as individual POST parameters
                if (name && name.startsWith(optionName + '[')) {
                    // Skip unchecked checkboxes so hidden field values are used
                    if (type === 'checkbox' && !$field.is(':checked')) {
                        return;
                    }
                    // Extract the field name from the bracket notation
                    var fieldName = name.substring(optionName.length + 1, name.length - 1);
                    // Send as nested array: aiohm_booking_tickets_form_settings[allow_group_bookings] = value
                    if (!formData[optionName]) {
                        formData[optionName] = {};
                    }
                    formData[optionName][fieldName] = value;
                }
            });
            
            $.ajax({
                url: (typeof aiohm_booking_admin !== 'undefined' && aiohm_booking_admin.ajax_url) ? aiohm_booking_admin.ajax_url : (ajaxurl || '/wp-admin/admin-ajax.php'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function(response) {
                    // AJAX Success
                    $button.prop('disabled', false).text('Save Settings');

                    if (response.success) {
                        AIOHM_Booking_Settings_Admin.showNotice('Form customization saved successfully!', 'success');
                        // Reload the page to show updated settings
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        AIOHM_Booking_Settings_Admin.showNotice('Error saving form customization: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX Error occurred
                    $button.prop('disabled', false).text('Save Settings');
                    AIOHM_Booking_Settings_Admin.showNotice('Network error while saving form customization: ' + error, 'error');
                }
            });
        },

        showNotice: function(message, type) {
            // Remove existing notices
            $('.aiohm-admin-notice').remove();

            // Map our types to WordPress notice classes
            var noticeClass = 'notice-info'; // default
            if (type === 'success') {
                noticeClass = 'notice-success';
            } else if (type === 'error') {
                noticeClass = 'notice-error';
            } else if (type === 'warning') {
                noticeClass = 'notice-warning';
            }

            var $notice = $('<div class="notice ' + noticeClass + ' aiohm-admin-notice is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');

            // Try different selectors for inserting the notice
            var $insertAfter = $('.aiohm-settings-header, .aiohm-header, .wrap h1').first();
            if ($insertAfter.length > 0) {
                $insertAfter.after($notice);
            } else {
                // Fallback: insert at the top of the page
                $('body').prepend($notice);
            }

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);

            // Dismiss on click
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Initialize form preview synchronization
         */
        initFormPreviewSync: function() {
            // Bind events for form customization settings
            this.bindFormCustomizationEvents();
            this.bindGlobalSettingsSync();
            this.bindShortcodeSync();
        },

        /**
         * Bind events for form customization settings synchronization
         */
        bindFormCustomizationEvents: function() {
            var self = this;

            // Color changes (already exists but enhanced)
            $(document).on('input.aiohm-preview-sync', '.form-color-input', function() {
                var $input = $(this);
                var $form = $input.closest('.aiohm-form-customization-form');
                self.updatePreviewColors($form);
                self.updatePreviewStyling($form);
            });

            // URL changes
            $(document).on('input.aiohm-preview-sync', 'input[name*="[checkout_page_url]"], input[name*="[thankyou_page_url]"]', function() {
                var $input = $(this);
                var $form = $input.closest('.aiohm-form-customization-form');
                self.updatePreviewURLs($form);
            });

            // Toggle changes
            $(document).on('change.aiohm-preview-sync', 'input[name*="[allow_group_bookings]"]', function() {
                var $input = $(this);
                var $form = $input.closest('.aiohm-form-customization-form');
                self.updatePreviewGroupBookings($form);
            });

            // Field visibility changes
            $(document).on('change.aiohm-preview-sync', '.field-visibility-input, .required-field-input', function() {
                var $input = $(this);
                var $form = $input.closest('.aiohm-form-customization-form');
                self.updatePreviewFields($form);
            });
        },

        /**
         * Bind events for global settings synchronization
         */
        bindGlobalSettingsSync: function() {
            var self = this;

            // Currency changes
            $(document).on('change.aiohm-preview-sync', 'select[name*="[currency]"], input[name*="[currency_symbol]"]', function() {
                self.updatePreviewCurrency();
            });

            // Date format changes
            $(document).on('change.aiohm-preview-sync', 'select[name*="[date_format]"]', function() {
                self.updatePreviewDateFormat();
            });

            // Ticket price changes
            $(document).on('input.aiohm-preview-sync', 'input[name*="[ticket_price]"]', function() {
                self.updatePreviewPricing();
            });

            // Service fee changes
            $(document).on('input.aiohm-preview-sync', 'input[name*="[service_fee]"]', function() {
                self.updatePreviewPricing();
            });
        },

        /**
         * Bind events for shortcode parameter synchronization
         */
        bindShortcodeSync: function() {
            var self = this;

            // Shortcode preview updates
            $(document).on('input.aiohm-preview-sync', 'input[name*="[shortcode_preview]"]', function() {
                var $input = $(this);
                var $form = $input.closest('.aiohm-form-customization-form');
                self.updatePreviewShortcode($form);
            });

            // Form type changes
            $(document).on('change.aiohm-preview-sync', 'select[name*="[form_type]"]', function() {
                var $select = $(this);
                var $form = $select.closest('.aiohm-form-customization-form');
                self.updatePreviewFormType($form);
            });
        },

        /**
         * Update preview colors
         */
        updatePreviewColors: function($form) {
            if (!$form || !$form.length) return;

            var primaryColor = $form.find('input[name*="[form_primary_color]"]').val() || '#457d59';
            var textColor = $form.find('input[name*="[form_text_color]"]').val() || '#333333';

            var $preview = $form.find('.aiohm-booking-events-form-preview-container');
            if ($preview.length) {
                // Update CSS custom properties
                $preview.css({
                    '--preview-primary': primaryColor,
                    '--preview-text': textColor,
                    '--preview-primary-hover': this.adjustColor(primaryColor, -20),
                    '--preview-primary-light': this.adjustColor(primaryColor, 40),
                    '--ohm-primary': primaryColor,
                    '--ohm-booking-primary': primaryColor,
                    '--aiohm-brand-color': primaryColor,
                    '--aiohm-text-color': textColor
                });

                // Update sandwich header/footer CSS variables specifically
                $('.aiohm-booking-sandwich-header, .aiohm-booking-sandwich-footer').css({
                    '--aiohm-brand-color': primaryColor,
                    '--aiohm-text-color': textColor
                });

                // Update specific elements
                $preview.find('.aiohm-preview-submit-btn').css('background-color', primaryColor);
                $preview.find('.aiohm-preview-submit-btn:hover').css('background-color', this.adjustColor(primaryColor, -20));
                $preview.find('h5').css('border-bottom-color', primaryColor);
            }
        },

        /**
         * Update preview styling based on current settings
         */
        updatePreviewStyling: function($form) {
            if (!$form || !$form.length) return;

            var $preview = $form.find('.aiohm-booking-events-form-preview-container');
            if (!$preview.length) return;

            // Get current global settings
            var currency = $('select[name*="[currency]"]').val() || 'USD';
            var currencySymbol = $('input[name*="[currency_symbol]"]').val() || '$';
            var dateFormat = $('select[name*="[date_format]"]').val() || 'Y-m-d';

            // Update currency display in preview
            $preview.find('.currency-symbol').text(currencySymbol);
            $preview.find('.currency-code').text(currency);

            // Update date format display
            $preview.find('.date-format').text(dateFormat);

            // Update pricing information
            this.updatePreviewPricing();
        },

        /**
         * Update preview URLs
         */
        updatePreviewURLs: function($form) {
            if (!$form || !$form.length) return;

            var checkoutUrl = $form.find('input[name*="[checkout_page_url]"]').val() || '';
            var thankyouUrl = $form.find('input[name*="[thankyou_page_url]"]').val() || '';

            var $preview = $form.find('.aiohm-booking-events-form-preview-container');
            if ($preview.length) {
                // Update URL displays in preview
                $preview.find('.checkout-url-display').text(checkoutUrl || 'Not set');
                $preview.find('.thankyou-url-display').text(thankyouUrl || 'Not set');
            }
        },

        /**
         * Update preview group bookings toggle
         */
        updatePreviewGroupBookings: function($form) {
            if (!$form || !$form.length) return;

            var allowGroupBookings = $form.find('input[name*="[allow_group_bookings]"]').is(':checked');

            var $preview = $form.find('.aiohm-booking-events-form-preview-container');
            if ($preview.length) {
                if (allowGroupBookings) {
                    $preview.find('.group-booking-option').show();
                } else {
                    $preview.find('.group-booking-option').hide();
                }
            }
        },

        /**
         * Update preview fields based on visibility settings
         */
        updatePreviewFields: function($form) {
            if (!$form || !$form.length) return;

            var $preview = $form.find('.aiohm-booking-events-form-preview-container');
            if (!$preview.length) return;

            // Update core fields visibility
            $form.find('.field-visibility-input').each(function() {
                var $input = $(this);
                var fieldName = $input.data('field');
                var isVisible = $input.is(':checked');

                var $previewField = $preview.find('.aiohm-preview-field-group[data-field="' + fieldName + '"]');
                if ($previewField.length) {
                    if (isVisible) {
                        $previewField.show();
                    } else {
                        $previewField.hide();
                    }
                }
            });

            // Update required field indicators
            $form.find('.required-field-input').each(function() {
                var $input = $(this);
                var fieldName = $input.data('field');
                var isRequired = $input.is(':checked');

                var $previewField = $preview.find('.aiohm-preview-field-group[data-field="' + fieldName + '"]');
                if ($previewField.length) {
                    var $label = $previewField.find('label');
                    var labelText = $label.text().replace(/\s*\*$/, ''); // Remove existing asterisk

                    if (isRequired) {
                        $label.html(labelText + ' <span class="required-asterisk">*</span>');
                    } else {
                        $label.text(labelText);
                    }
                }
            });
        },

        /**
         * Update preview currency display
         */
        updatePreviewCurrency: function() {
            var currency = $('select[name*="[currency]"]').val() || 'USD';
            var currencySymbol = $('input[name*="[currency_symbol]"]').val() || '$';

            $('.aiohm-booking-events-form-preview-container .currency-symbol').text(currencySymbol);
            $('.aiohm-booking-events-form-preview-container .currency-code').text(currency);
        },

        /**
         * Update preview date format
         */
        updatePreviewDateFormat: function() {
            var dateFormat = $('select[name*="[date_format]"]').val() || 'Y-m-d';
            $('.aiohm-booking-events-form-preview-container .date-format').text(dateFormat);
        },

        /**
         * Update preview pricing information for events
         */
        updatePreviewPricing: function() {
            var ticketPrice = $('input[name*="[ticket_price]"]').val() || '25';
            var earlyBirdPrice = $('input[name*="[default_earlybird_price]"]').val() || '20';
            var earlyBirdDays = $('input[name*="[early_bird_days]"]').val() || '30';
            var serviceFee = $('input[name*="[service_fee]"]').val() || '2.50';
            var currencySymbol = $('input[name*="[currency_symbol]"]').val() || '$';

            $('.aiohm-booking-events-form-preview-container .default-price').text(currencySymbol + ticketPrice);
            $('.aiohm-booking-events-form-preview-container .early-bird-price').text(currencySymbol + earlyBirdPrice);
            $('.aiohm-booking-events-form-preview-container .early-bird-days').text(earlyBirdDays);
            $('.aiohm-booking-events-form-preview-container .service-fee').text(currencySymbol + serviceFee);
        },

        /**
         * Update preview validation rules
         */
        updatePreviewValidation: function() {
            var minAge = $('input[name*="[minimum_age]"]').val() || '18';
            $('.aiohm-booking-events-form-preview-container .min-age').text(minAge);
        },

        /**
         * Update preview shortcode display
         */
        updatePreviewShortcode: function($form) {
            if (!$form || !$form.length) return;

            var shortcode = $form.find('input[name*="[shortcode_preview]"]').val() || '';
            var $preview = $form.find('.aiohm-booking-events-form-preview-container');

            if ($preview.length && shortcode) {
                $preview.find('.shortcode-display').text(shortcode);
            }
        },

        /**
         * Update preview form type
         */
        updatePreviewFormType: function($form) {
            if (!$form || !$form.length) return;

            var formType = $form.find('select[name*="[form_type]"]').val() || 'events';
            var $preview = $form.find('.aiohm-booking-events-form-preview-container');

            if ($preview.length) {
                var title = 'events' === formType ? 'Event Booking Form' : 'Booking Form';
                var buttonText = 'events' === formType ? 'Book Event Tickets' : 'Book Now';

                $preview.find('h5').text(title);
                $preview.find('.aiohm-preview-submit-btn').text(buttonText);
            }
        },

        /**
         * Utility function to adjust color brightness
         */
        adjustColor: function(color, amount) {
            // Simple color adjustment - in production, use a proper color manipulation library
            var usePound = false;

            if (color[0] == "#") {
                color = color.slice(1);
                usePound = true;
            }

            var num = parseInt(color, 16);

            var r = (num >> 16) + amount;
            var g = (num >> 8 & 0x00FF) + amount;
            var b = (num & 0x0000FF) + amount;

            r = r > 255 ? 255 : r < 0 ? 0 : r;
            g = g > 255 ? 255 : g < 0 ? 0 : g;
            b = b > 255 ? 255 : b < 0 ? 0 : b;

            return (usePound ? "#" : "") + (r << 16 | g << 8 | b).toString(16);
        },

        /**
         * Refresh all preview synchronizations
         */
        refreshAllPreviews: function() {
            $('.aiohm-form-customization-form').each(function() {
                var $form = $(this);
                AIOHM_Booking_Settings_Admin.updatePreviewColors($form);
                AIOHM_Booking_Settings_Admin.updatePreviewStyling($form);
                AIOHM_Booking_Settings_Admin.updatePreviewURLs($form);
                AIOHM_Booking_Settings_Admin.updatePreviewGroupBookings($form);
                AIOHM_Booking_Settings_Admin.updatePreviewFields($form);
                AIOHM_Booking_Settings_Admin.updatePreviewShortcode($form);
                AIOHM_Booking_Settings_Admin.updatePreviewFormType($form);
            });

            this.updatePreviewCurrency();
            this.updatePreviewDateFormat();
            this.updatePreviewPricing();
            this.updatePreviewValidation();
        },

        // Event button handlers
        handleCloneEvent: function(e) {
            e.preventDefault();
            var $button = $(this);
            var eventId = $button.data('event-index'); // Use event-index for array index

            if (confirm('Are you sure you want to clone this event? This will create a copy with the same settings.')) {
                $button.prop('disabled', true);
                var originalText = $button.html();
                $button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Cloning...');

                // Send AJAX request to clone event
                $.ajax({
                    url: aiohm_booking_admin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'aiohm_clone_event',
                        event_id: eventId, // Send the event ID
                        nonce: aiohm_booking_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            AIOHM_Booking_Settings_Admin.showNotice(response.data.message, 'success');

                            // Add the new event card to the page instead of redirecting
                            if (response.data.cloned_event) {
                                AIOHM_Booking_Settings_Admin.addClonedEventCard(response.data.cloned_event);
                                AIOHM_Booking_Settings_Admin.updateEventStatistics();
                            } else {
                                // Fallback to reload if no event data returned
                                location.reload();
                            }
                        } else {
                            AIOHM_Booking_Settings_Admin.showNotice('Error cloning event: ' + (response.data.message || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        AIOHM_Booking_Settings_Admin.showNotice('Error cloning event. Please try again.', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $button.html(originalText);
                    }
                });
            }
        },

        /**
         * Handle delete event button click
         */
        handleDeleteEvent: function(e) {
            e.preventDefault();
            var $button = $(this);
            var eventIndex = $button.data('event-index');
            var $eventCard = $button.closest('.aiohm-booking-event-settings');
            var eventTitle = $eventCard.find('.aiohm-card-title').text().trim();

            if (confirm('Are you sure you want to permanently delete "' + eventTitle + '"? This action cannot be undone and will remove all event data including bookings.')) {
                $button.prop('disabled', true);
                var originalText = $button.html();
                $button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Deleting...');

                // Send AJAX request to delete event
                $.ajax({
                    url: aiohm_booking_admin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'aiohm_booking_delete_event',
                        event_index: eventIndex,
                        nonce: aiohm_booking_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            AIOHM_Booking_Settings_Admin.showNotice('Event deleted successfully!', 'success');
                            
                            // Remove the event card from the DOM with animation
                            $eventCard.fadeOut(300, function() {
                                $eventCard.remove();
                                
                                // Update event statistics
                                AIOHM_Booking_Settings_Admin.updateEventStatistics();
                                
                                // Update event indexes for remaining cards
                                AIOHM_Booking_Settings_Admin.updateEventIndexes();
                            });
                        } else {
                            AIOHM_Booking_Settings_Admin.showNotice('Error deleting event: ' + (response.data.message || 'Unknown error'), 'error');
                            $button.prop('disabled', false);
                            $button.html(originalText);
                        }
                    },
                    error: function() {
                        AIOHM_Booking_Settings_Admin.showNotice('Error deleting event. Please try again.', 'error');
                        $button.prop('disabled', false);
                        $button.html(originalText);
                    }
                });
            }
        },

        /**
         * Add a cloned event card to the page
         */
        addClonedEventCard: function(clonedEvent) {
            // Find the add event banner
            var $addBanner = $('.aiohm-add-more-banner');
            
            // Create the event card HTML
            var eventCardHtml = this.generateEventCardHtml(clonedEvent);

            // Insert the new event card right before the add banner
            if ($addBanner.length > 0) {
                $addBanner.before(eventCardHtml);
            } else {
                // Fallback: insert after the last event card
                $('.aiohm-booking-event-settings').last().after(eventCardHtml);
            }

            // Re-bind events for the new card
            setTimeout(function() {
                AIOHM_Booking_Settings_Admin.bindEvents();
            }, 100);

            // Scroll to the new event card
            var $newCard = $('.aiohm-booking-event-settings').last();
            if ($newCard.length) {
                $('html, body').animate({
                    scrollTop: $newCard.offset().top - 100
                }, 800);

                // Highlight the new card
                $newCard.addClass('aiohm-highlight');
                setTimeout(function() {
                    $newCard.removeClass('aiohm-highlight');
                }, 3000);
            }
        },

        /**
         * Generate HTML for an event card
         */
        generateEventCardHtml: function(event) {
            var eventIndex = $('.aiohm-booking-event-settings').length;
            var currency = '€'; // Default currency symbol

            return `
                <div class="aiohm-booking-event-settings aiohm-booking-admin-card aiohm-collapsed" 
                     data-event-index="${eventIndex}"
                     ${event.event_type ? `data-event-type-color="${this.generateEventTypeColor(event.event_type)}" style="--event-type-color: ${this.generateEventTypeColor(event.event_type)};"` : ''}>
                    <!-- Event Header -->
                    <div class="aiohm-card-header aiohm-event-card-header">
                        <div class="aiohm-card-header-title">
                            ${event.event_type ? `
                                <div class="aiohm-event-type-badge-container">
                                    <span class="aiohm-event-type-badge" 
                                          style="background-color: ${this.generateEventTypeColor(event.event_type)};" 
                                          data-event-type="${this.escapeHtml(event.event_type)}"
                                          data-event-index="${eventIndex}">
                                        ${this.escapeHtml(event.event_type)}
                                    </span>
                                    <input type="color" 
                                           class="aiohm-event-type-color-picker" 
                                           value="${this.generateEventTypeColor(event.event_type)}" 
                                           data-event-type="${this.escapeHtml(event.event_type)}"
                                           data-event-index="${eventIndex}"
                                           title="Change event type color">
                                </div>
                            ` : ''}
                            <h3 class="aiohm-card-title">${this.escapeHtml(event.title)}</h3>
                        </div>
                        <div class="aiohm-card-header-actions">
                            <button type="button" class="aiohm-event-toggle-btn" data-event-index="${eventIndex}" aria-label="Expand event details" aria-expanded="false">
                                <span class="dashicons dashicons-arrow-down-alt2 aiohm-toggle-icon"></span>
                            </button>
                        </div>
                    </div>

                    <div class="aiohm-card-body">
                        <!-- Event Content - 2 Column Layout -->
                        <div class="aiohm-columns-2">
                            <!-- Left Column -->
                            <div class="aiohm-column">
                                <div class="aiohm-form-group">
                                    <label>Event Type</label>
                                    <input type="text" name="events[${eventIndex}][event_type]" value="${this.escapeHtml(event.event_type || '')}" placeholder="e.g., Concert, Workshop, Conference">
                                </div>

                                <div class="aiohm-form-group">
                                    <label>Event Title <span class="aiohm-char-limit">(max 50 chars)</span></label>
                                    <input type="text" name="events[${eventIndex}][title]" value="${this.escapeHtml(event.title)}" placeholder="e.g., Summer Music Festival" maxlength="50" class="aiohm-char-limited aiohm-event-title-input">
                                    <div class="aiohm-char-counter" data-max="50">
                                        <span class="aiohm-char-current">${event.title.length}</span>/50
                                    </div>
                                </div>

                                <div class="aiohm-form-group">
                                    <label>Event Description <span class="aiohm-char-limit">(max 150 chars)</span></label>
                                    <textarea name="events[${eventIndex}][description]" rows="3" placeholder="Brief description of your event..." maxlength="150" class="aiohm-char-limited">${this.escapeHtml(event.description || '')}</textarea>
                                    <div class="aiohm-char-counter" data-max="150">
                                        <span class="aiohm-char-current">${(event.description || '').length}</span>/150
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="aiohm-column">
                                <div class="aiohm-columns-2">
                                    <div class="aiohm-form-group">
                                        <label>Event Date</label>
                                        <input type="date" name="events[${eventIndex}][event_date]" value="">
                                    </div>
                                    <div class="aiohm-form-group">
                                        <label>Event Time</label>
                                        <input type="time" name="events[${eventIndex}][event_time]" value="">
                                    </div>
                                </div>

                                <div class="aiohm-columns-2">
                                    <div class="aiohm-form-group">
                                        <label>Event End Date</label>
                                        <input type="date" name="events[${eventIndex}][event_end_date]" value="">
                                    </div>
                                    <div class="aiohm-form-group">
                                        <label>Event End Time</label>
                                        <input type="time" name="events[${eventIndex}][event_end_time]" value="">
                                    </div>
                                </div>

                                <div class="aiohm-columns-2">
                                    <div class="aiohm-form-group">
                                        <label>Available Tickets</label>
                                        <input type="number" name="events[${eventIndex}][available_seats]" value="${event.available_seats || ''}" min="1" max="10000" class="aiohm-number-input">
                                    </div>
                                    <div class="aiohm-form-group">
                                        <label>Price (<span class="currency-symbol" title="Euro">${currency}</span>)</label>
                                        <input type="number" name="events[${eventIndex}][price]" value="${event.price || ''}" step="0.01" min="0" class="aiohm-number-input">
                                    </div>
                                </div>

                                <div class="aiohm-early-bird-section">
                                    <div class="aiohm-form-group">
                                        <label>Early Bird Price (<span class="currency-symbol" title="Euro">${currency}</span>)</label>
                                        <input type="number" name="events[${eventIndex}][early_bird_price]" value="${event.early_bird_price || ''}" step="0.01" min="0" placeholder="Optional" class="aiohm-number-input">
                                    </div>
                                </div>

                                <div class="aiohm-columns-2">
                                    <div class="aiohm-form-group">
                                        <label>Early Bird Days Before Event</label>
                                        <input type="number" name="events[${eventIndex}][early_bird_days]" value="${event.early_bird_days || ''}" min="1" max="365" step="1" placeholder="e.g. 30" class="aiohm-number-input">
                                        <small>Number of days before the event when early bird pricing ends</small>
                                    </div>

                                    <div class="aiohm-form-group">
                                        <label>Deposit Percentage</label>
                                        <input type="number" name="events[${eventIndex}][deposit_percentage]" value="${event.deposit_percentage || ''}" min="0" max="100" step="1" placeholder="e.g. 0" class="aiohm-number-input">
                                        <small>Percentage of total price required as deposit</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="aiohm-booking-card-event-footer">
                        <!-- Action Buttons Group -->
                        <div class="aiohm-button-group aiohm-action-buttons">
                            <button type="button" class="button button-primary aiohm-individual-save-btn aiohm-action-btn" data-event-index="${eventIndex}" title="Save this event with all current settings">
                                <span class="dashicons dashicons-saved"></span>
                                Save Event
                            </button>

                            <button type="button" class="button aiohm-clone-event-btn aiohm-action-btn" data-event-index="${eventIndex}" title="Create a copy of this event with all settings">
                                <span class="dashicons dashicons-admin-page"></span>
                                Clone Event
                            </button>

                            <button type="button" class="button button-danger aiohm-delete-event-btn aiohm-action-btn" data-event-index="${eventIndex}" title="Permanently delete this event and all its data">
                                <span class="dashicons dashicons-trash"></span>
                                Delete Event
                            </button>
                        </div>

                        <!-- Import Buttons Group -->
                        <div class="aiohm-button-group aiohm-import-buttons">
                            <button type="button" class="aiohm-facebook-import-btn aiohm-import-btn" data-event-index="${eventIndex}" title="Import event data from Facebook Events">
                                <span class="dashicons dashicons-facebook"></span>
                                Facebook
                            </button>

                            <button type="button" class="aiohm-eventon-import-btn aiohm-import-btn" data-event-index="${eventIndex}" title="Import event data from EventON calendar">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                EventON
                            </button>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Add a new event to the page
         */
        addNewEvent: function() {
            // Send AJAX request to create new event with default data
            $.ajax({
                url: aiohm_booking_admin.ajax_url,
                method: 'POST',
                data: {
                    action: 'aiohm_add_new_event',
                    nonce: aiohm_booking_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Add the new event card with the server-provided default data
                        AIOHM_Booking_Settings_Admin.addClonedEventCard(response.data.new_event);
                        
                        // Update event statistics
                        AIOHM_Booking_Settings_Admin.updateEventStatistics();
                        
                        // Show success message
                        AIOHM_Booking_Settings_Admin.showNotice('New event added successfully with default settings!', 'success');
                        
                        // Scroll to the new event
                        var $newCard = $('.aiohm-booking-event-settings').last();
                        if ($newCard.length) {
                            $('html, body').animate({
                                scrollTop: $newCard.offset().top - 100
                            }, 500);
                        }
                    } else {
                        AIOHM_Booking_Settings_Admin.showNotice('Error adding new event: ' + (response.data.message || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    AIOHM_Booking_Settings_Admin.showNotice('Error adding new event. Please try again.', 'error');
                }
            });
        },

        /**
         * Update event statistics display
         */
        updateEventStatistics: function() {
            var $statsContainer = $('.aiohm-booking-orders-stats');
            if ($statsContainer.length) {
                var totalEvents = $('.aiohm-booking-event-settings').length;
                var totalTickets = 0;
                var upcomingEvents = 0;
                var currentDate = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
                
                // Calculate statistics from event cards
                $('.aiohm-booking-event-settings').each(function() {
                    var $card = $(this);
                    var ticketsInput = $card.find('input[name*="[available_seats]"]').val();
                    var dateInput = $card.find('input[name*="[event_date]"]').val();
                    
                    if (ticketsInput) {
                        totalTickets += parseInt(ticketsInput) || 0;
                    }
                    
                    if (dateInput && dateInput >= currentDate) {
                        upcomingEvents++;
                    }
                });
                
                var $stats = $statsContainer.find('.aiohm-booking-orders-stat');
                
                // Update statistics in order: Total Events, Upcoming Events, Total Tickets
                if ($stats.length >= 3) {
                    $stats.eq(0).find('.number').text(totalEvents);
                    $stats.eq(1).find('.number').text(upcomingEvents);
                    $stats.eq(2).find('.number').text(totalTickets);
                }
                
                // Note: Revenue, occupancy rate, and pending orders require server data
                // and will be updated via AJAX if needed in future enhancement
            }
        },

        /**
         * Update event indexes after deletion or reordering
         */
        updateEventIndexes: function() {
            $('.aiohm-booking-event-settings').each(function(index) {
                var $eventCard = $(this);
                
                // Update the data-event-index attribute
                $eventCard.attr('data-event-index', index);
                
                // Update all action buttons' data-event-index
                $eventCard.find('[data-event-index]').attr('data-event-index', index);
                
                // Update form field names to use correct index
                $eventCard.find('input, select, textarea').each(function() {
                    var $field = $(this);
                    var name = $field.attr('name');
                    if (name && name.includes('[')) {
                        // Replace the index in field names like events[0][title] with events[newIndex][title]
                        var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $field.attr('name', newName);
                    }
                });
            });
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // Generate consistent color for event type
        generateEventTypeColor: function(eventType) {
            if (!eventType || eventType.trim() === '') {
                return '#6b7280'; // ohm-gray-500
            }

            // Check if we have a stored color for this type
            var storedColors = JSON.parse(localStorage.getItem('aiohm_event_type_colors') || '{}');
            if (storedColors[eventType]) {
                return storedColors[eventType];
            }

            // Predefined colors for common event types
            var predefinedColors = {
                'breakfast': '#f59e0b',   // amber-500
                'lunch': '#10b981',       // emerald-500  
                'dinner': '#8b5cf6',      // violet-500
                'meeting': '#3b82f6',     // blue-500
                'workshop': '#06b6d4',    // cyan-500
                'conference': '#ef4444',  // red-500
                'concert': '#ec4899',     // pink-500
                'seminar': '#84cc16',     // lime-500
                'training': '#f97316',    // orange-500
                'party': '#a855f7'       // purple-500
            };

            var typeLower = eventType.toLowerCase().trim();
            if (predefinedColors[typeLower]) {
                var color = predefinedColors[typeLower];
                storedColors[eventType] = color;
                localStorage.setItem('aiohm_event_type_colors', JSON.stringify(storedColors));
                return color;
            }

            // Generate color from hash
            var hash = this.hashCode(eventType);
            var hue = Math.abs(hash) % 360;
            var saturation = 60 + (Math.abs(hash >> 8) % 20); // 60-80%
            var lightness = 45 + (Math.abs(hash >> 16) % 15); // 45-60%
            
            var color = this.hslToHex(hue, saturation, lightness);
            
            // Store the generated color
            storedColors[eventType] = color;
            localStorage.setItem('aiohm_event_type_colors', JSON.stringify(storedColors));
            
            return color;
        },

        // Simple hash function for strings
        hashCode: function(str) {
            var hash = 0;
            if (str.length === 0) return hash;
            for (var i = 0; i < str.length; i++) {
                var char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32-bit integer
            }
            return hash;
        },

        // Convert HSL to Hex
        hslToHex: function(h, s, l) {
            s /= 100;
            l /= 100;

            var c = (1 - Math.abs(2 * l - 1)) * s;
            var x = c * (1 - Math.abs((h / 60) % 2 - 1));
            var m = l - c / 2;
            var r = 0, g = 0, b = 0;

            if (0 <= h && h < 60) {
                r = c; g = x; b = 0;
            } else if (60 <= h && h < 120) {
                r = x; g = c; b = 0;
            } else if (120 <= h && h < 180) {
                r = 0; g = c; b = x;
            } else if (180 <= h && h < 240) {
                r = 0; g = x; b = c;
            } else if (240 <= h && h < 300) {
                r = x; g = 0; b = c;
            } else if (300 <= h && h < 360) {
                r = c; g = 0; b = x;
            }

            r = Math.round((r + m) * 255);
            g = Math.round((g + m) * 255);
            b = Math.round((b + m) * 255);

            return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        },

        // Update event type color
        updateEventTypeColor: function(eventType, newColor) {
            var storedColors = JSON.parse(localStorage.getItem('aiohm_event_type_colors') || '{}');
            storedColors[eventType] = newColor;
            localStorage.setItem('aiohm_event_type_colors', JSON.stringify(storedColors));
            
            // Update all badges with this event type
            $('.aiohm-event-type-badge[data-event-type="' + eventType + '"]').css('background-color', newColor);
            $('.aiohm-event-type-color-picker[data-event-type="' + eventType + '"]').val(newColor);
            
            // Update all event card borders with this event type
            $('.aiohm-booking-event-settings').each(function() {
                var $card = $(this);
                var $badge = $card.find('.aiohm-event-type-badge[data-event-type="' + eventType + '"]');
                
                if ($badge.length > 0) {
                    // Update CSS custom property and data attribute
                    $card.css('--event-type-color', newColor);
                    $card.attr('data-event-type-color', newColor);
                }
            });
        },

        handleEventONImport: function(e) {
            e.preventDefault();
            var $button = $(this);
            
            // Check if button is disabled (EventON not available)
            if ($button.prop('disabled')) {
                alert('EventON plugin is required for this functionality. Please install and activate EventON to use event import.');
                return;
            }
            
            // Show loading state
            var originalText = $button.text();
            $button.prop('disabled', true).text('Loading events...');
            
            // First, get list of available EventON events
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_get_eventon_events_list',
                    nonce: aiohm_booking_admin.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text(originalText);
                    
                    if (response.success && response.data.events && response.data.events.length > 0) {
                        AIOHM_Booking_Settings_Admin.showEventONImportModal(response.data.events);
                    } else {
                        alert('No EventON events found to import.');
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(originalText);
                    alert('Failed to load EventON events. Please try again.');
                }
            });
        },

        // Show EventON import modal with event selection
        showEventONImportModal: function(events) {
            var modalHtml = '<div id="aiohm-eventon-import-modal" class="aiohm-modal">' +
                '<div class="aiohm-modal-content">' +
                '<div class="aiohm-modal-header">' +
                '<h3>Import EventON Events</h3>' +
                '<span class="aiohm-modal-close">&times;</span>' +
                '</div>' +
                '<div class="aiohm-modal-body">' +
                '<p>Select events to import from EventON:</p>' +
                '<div class="aiohm-eventon-events-list">';
            
            // Add events to selection list
            events.forEach(function(event) {
                modalHtml += '<label class="aiohm-event-item">' +
                    '<input type="checkbox" name="eventon_events[]" value="' + event.id + '">' +
                    '<div class="aiohm-event-details">' +
                    '<strong>' + event.title + '</strong>' +
                    '<div class="aiohm-event-meta">' +
                    '<span class="date">' + event.date + '</span>' +
                    (event.time ? ' <span class="time">' + event.time + '</span>' : '') +
                    '</div>' +
                    (event.description ? '<p class="description">' + event.description.substring(0, 100) + '...</p>' : '') +
                    '</div>' +
                    '</label>';
            });
            
            modalHtml += '</div>' +
                '<div class="aiohm-import-options">' +
                '<label>' +
                'Import Status: ' +
                '<select name="import_status">' +
                '<option value="draft">Draft</option>' +
                '<option value="publish">Published</option>' +
                '</select>' +
                '</label>' +
                '<label>' +
                'Import Limit: ' +
                '<input type="number" name="import_limit" value="10" min="1" max="50">' +
                '</label>' +
                '</div>' +
                '</div>' +
                '<div class="aiohm-modal-footer">' +
                '<button type="button" class="button aiohm-modal-cancel">Cancel</button>' +
                '<button type="button" class="button button-primary aiohm-import-selected">Import Selected</button>' +
                '</div>' +
                '</div>' +
                '</div>';
            
            // Add modal to page
            $('body').append(modalHtml);
            $('#aiohm-eventon-import-modal').show();
            
            // Bind modal events
            this.bindEventONModalEvents();
        },

        // Bind EventON modal events
        bindEventONModalEvents: function() {
            var self = this;
            
            // Close modal
            $(document).on('click', '.aiohm-modal-close, .aiohm-modal-cancel', function() {
                $('#aiohm-eventon-import-modal').remove();
            });
            
            // Import selected events
            $(document).on('click', '.aiohm-import-selected', function() {
                var selectedEvents = [];
                $('input[name="eventon_events[]"]:checked').each(function() {
                    selectedEvents.push($(this).val());
                });
                
                if (selectedEvents.length === 0) {
                    alert('Please select at least one event to import.');
                    return;
                }
                
                var importStatus = $('select[name="import_status"]').val();
                var importLimit = parseInt($('input[name="import_limit"]').val());
                
                self.importEventONEvents(selectedEvents, importStatus, importLimit);
            });
        },

        // Import selected EventON events
        importEventONEvents: function(eventIds, status, limit) {
            var $button = $('.aiohm-import-selected');
            var originalText = $button.text();
            $button.prop('disabled', true).text('Importing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_import_eventon_events',
                    nonce: aiohm_booking_admin.nonce,
                    event_ids: eventIds,
                    import_status: status,
                    import_limit: limit
                },
                success: function(response) {
                    $button.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        var results = response.data;
                        var message = 'Import completed!\n\n';
                        message += 'Successfully imported: ' + results.success.length + ' events\n';
                        if (results.errors.length > 0) {
                            message += 'Errors: ' + results.errors.length + ' events\n\n';
                            message += 'Error details:\n';
                            results.errors.forEach(function(error) {
                                message += '- ' + error.message + '\n';
                            });
                        }
                        
                        alert(message);
                        $('#aiohm-eventon-import-modal').remove();
                        
                        // Refresh the page to show imported events
                        location.reload();
                    } else {
                        alert('Import failed: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text(originalText);
                    alert('Import failed. Please try again.');
                }
            });
        },

        // Initialize event card toggle functionality
        initEventCardToggles: function() {
            // Restore saved toggle states
            this.restoreEventCardStates();
            
            // Restore states after AJAX updates (like after saving)
            $(document).ajaxComplete(function(event, xhr, settings) {
                // Check if this was a settings save request
                if (settings.data && settings.data.indexOf('action=aiohm_save') !== -1) {
                    setTimeout(function() {
                        AIOHM_Booking_Settings_Admin.restoreEventCardStates();
                    }, 100);
                }
            });
        },

        // Handle event card toggle button clicks
        handleEventCardToggle: function(e) {
            e.preventDefault();
            var $button = $(this);
            var $eventCard = $button.closest('.aiohm-booking-event-settings');
            var eventIndex = $button.data('event-index') || $eventCard.data('event-index') || $eventCard.index();
            
            // Toggle collapsed state
            $eventCard.toggleClass('aiohm-collapsed');
            
            // Update button icon
            var $icon = $button.find('.dashicons');
            if ($eventCard.hasClass('aiohm-collapsed')) {
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                $button.attr('title', 'Expand event settings');
            } else {
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                $button.attr('title', 'Collapse event settings');
            }
            
            // Save state to localStorage
            AIOHM_Booking_Settings_Admin.saveEventCardState(eventIndex, $eventCard.hasClass('aiohm-collapsed'));
        },

        // Handle event type color picker changes
        handleEventTypeColorChange: function(e) {
            var $colorPicker = $(this);
            var eventType = $colorPicker.data('event-type');
            var newColor = $colorPicker.val();
            
            if (eventType && newColor) {
                // Update the color for this event type
                AIOHM_Booking_Settings_Admin.updateEventTypeColor(eventType, newColor);
                
                // Also update the badge color immediately
                $colorPicker.siblings('.aiohm-event-type-badge').css('background-color', newColor);
            }
        },

        // Handle event type input changes to update badge
        handleEventTypeInputChange: function(e) {
            var $input = $(this);
            var $eventCard = $input.closest('.aiohm-booking-event-settings');
            var $badgeContainer = $eventCard.find('.aiohm-event-type-badge-container');
            var $header = $eventCard.find('.aiohm-card-header-title');
            var eventIndex = $eventCard.data('event-index');
            var newEventType = $input.val().trim();
            
            if (newEventType) {
                // Generate color for the new event type
                var color = AIOHM_Booking_Settings_Admin.generateEventTypeColor(newEventType);
                
                // If badge container doesn't exist, create it
                if ($badgeContainer.length === 0) {
                    var badgeHtml = `
                        <div class="aiohm-event-type-badge-container">
                            <span class="aiohm-event-type-badge" 
                                  style="background-color: ${color};" 
                                  data-event-type="${AIOHM_Booking_Settings_Admin.escapeHtml(newEventType)}"
                                  data-event-index="${eventIndex}">
                                ${AIOHM_Booking_Settings_Admin.escapeHtml(newEventType)}
                            </span>
                            <input type="color" 
                                   class="aiohm-event-type-color-picker" 
                                   value="${color}" 
                                   data-event-type="${AIOHM_Booking_Settings_Admin.escapeHtml(newEventType)}"
                                   data-event-index="${eventIndex}"
                                   title="Change event type color">
                        </div>
                    `;
                    $header.prepend(badgeHtml);
                    
                    // Update card borders
                    $eventCard.css('--event-type-color', color);
                    $eventCard.attr('data-event-type-color', color);
                } else {
                    // Update existing badge
                    var $badge = $badgeContainer.find('.aiohm-event-type-badge');
                    var $colorPicker = $badgeContainer.find('.aiohm-event-type-color-picker');
                    
                    $badge.text(newEventType)
                          .css('background-color', color)
                          .attr('data-event-type', newEventType);
                    
                    $colorPicker.val(color)
                               .attr('data-event-type', newEventType);
                    
                    // Update card borders
                    $eventCard.css('--event-type-color', color);
                    $eventCard.attr('data-event-type-color', color);
                }
            } else {
                // Remove badge if event type is empty
                $badgeContainer.remove();
                
                // Remove card borders
                $eventCard.removeAttr('data-event-type-color');
                $eventCard.css('--event-type-color', '');
            }
        },

        // Save event card toggle state to localStorage
        saveEventCardState: function(eventIndex, isCollapsed) {
            var states = JSON.parse(localStorage.getItem('aiohm_event_card_states') || '{}');
            var pageKey = this.getCurrentPageKey();
            
            if (!states[pageKey]) {
                states[pageKey] = {};
            }
            
            states[pageKey][eventIndex] = isCollapsed;
            localStorage.setItem('aiohm_event_card_states', JSON.stringify(states));
        },

        // Restore event card states from localStorage
        restoreEventCardStates: function() {
            var states = JSON.parse(localStorage.getItem('aiohm_event_card_states') || '{}');
            var pageKey = this.getCurrentPageKey();
            
            if (states[pageKey]) {
                $('.aiohm-booking-event-settings').each(function(index) {
                    var $eventCard = $(this);
                    var eventIndex = $eventCard.data('event-index') || index;
                    
                    if (states[pageKey][eventIndex] === true) {
                        // Apply collapsed state
                        $eventCard.addClass('aiohm-collapsed');
                        
                        // Update toggle button icon
                        var $toggleBtn = $eventCard.find('.aiohm-event-toggle-btn');
                        var $icon = $toggleBtn.find('.dashicons');
                        $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                        $toggleBtn.attr('title', 'Expand event settings');
                    }
                });
            }
        },

        // Get a unique key for the current page/context
        getCurrentPageKey: function() {
            var path = window.location.pathname;
            var search = window.location.search;
            return path + search;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        
        // Initialize regardless of localized script availability 
        // since the scroll functionality doesn't depend on it
        AIOHM_Booking_Settings_Admin.init();
        
        // Initialize form preview synchronization
        AIOHM_Booking_Settings_Admin.initFormPreviewSync();
        
        // Refresh all previews on page load
        setTimeout(function() {
            AIOHM_Booking_Settings_Admin.refreshAllPreviews();
        }, 1000);
    });

})(jQuery);

// Global function for plugin reset confirmation (outside jQuery namespace)
function aiohm_confirm_reset_plugin_data() {
    if (confirm('⚠️ WARNING: This will permanently delete ALL plugin data including:\n\n' +
               '• All events and accommodations\n' +
               '• All booking orders and calendar data\n' +
               '• All plugin settings and configurations\n' +
               '• All email logs and statistics\n\n' +
               'This action CANNOT be undone!\n\n' +
               'Are you absolutely sure you want to proceed?')) {
        
        if (confirm('🚨 FINAL CONFIRMATION:\n\n' +
                   'You are about to PERMANENTLY DELETE all plugin data.\n' +
                   'Click OK to proceed or Cancel to abort.')) {
            aiohm_reset_plugin_data();
        }
    }
}

function aiohm_reset_plugin_data() {
    const button = document.getElementById('aiohm-reset-plugin-data');
    if (button) {
        button.disabled = true;
        button.innerHTML = '🔄 Resetting...';
    }
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'aiohm_booking_reset_plugin_data',
            nonce: jQuery('#aiohm_booking_settings_nonce').val() || ''
        },
        success: function(response) {
            if (response.success) {
                alert('✅ Plugin data has been reset successfully!\n\nThe page will now reload.');
                window.location.reload();
            } else {
                alert('❌ Error resetting plugin data: ' + (response.data.message || 'Unknown error'));
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '🗑️ Reset All Plugin Data';
                }
            }
        },
        error: function() {
            alert('❌ Network error occurred while resetting plugin data.');
            if (button) {
                button.disabled = false;
                button.innerHTML = '🗑️ Reset All Plugin Data';
            }
        }
    });
}
