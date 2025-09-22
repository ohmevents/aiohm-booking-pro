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
            $(document).on('click.aiohm-settings', '.aiohm-clone-event-btn', AIOHM_Booking_Settings_Admin.handleCloneEvent);
            $(document).on('click.aiohm-settings', '.aiohm-delete-event-btn', AIOHM_Booking_Settings_Admin.handleDeleteEvent);
            $(document).on('click.aiohm-settings', '.aiohm-eventon-import-btn', AIOHM_Booking_Settings_Admin.handleEventONImport);
            // Note: AI Import is handled by aiohm-booking-ai-import.js
            
            // Event card toggle handlers
            $(document).on('click.aiohm-settings', '.aiohm-event-toggle-btn', AIOHM_Booking_Settings_Admin.handleEventCardToggle);
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
            
            // Check if this is a booking settings save button - let it submit normally
            if ($button.hasClass('aiohm-booking-settings-save-btn')) {
                // Don't prevent default - let the form submit normally
                return;
            }
            
            e.preventDefault();
            
            // Check if this is a form customization save button
            if ($button.hasClass('aiohm-form-customization-save-btn')) {
                AIOHM_Booking_Settings_Admin.handleFormCustomizationSave($button);
                return;
            }
            
            var $form = $button.closest('form');
            
            // Check if this is an individual module save button
            if ($button.attr('name') && $button.attr('name').startsWith('save_')) {
                // Handle individual module saves with AJAX
                var moduleName = $button.attr('name').replace('save_', '').replace('_settings', '');
                AIOHM_Booking_Settings_Admin.saveModuleSettings($button, $form, moduleName);
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
            } else {
                console.error('No nonce available');
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
                    console.error('AJAX error:', xhr, status, error);
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
            
            // Add nonce
            var nonceField = $container.find('input[name="aiohm_form_settings_nonce"]');
            if (nonceField.length > 0) {
                formData.aiohm_form_settings_nonce = nonceField.val();
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
            var eventIndex = $button.data('event-index');
            
            if (confirm('Are you sure you want to clone this event? This will create a copy with the same settings.')) {
                $button.prop('disabled', true);
                var originalText = $button.html();
                $button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Cloning...');
                
                // Get the event card data
                var $eventCard = $button.closest('.aiohm-booking-event-settings');
                var eventData = AIOHM_Booking_Settings_Admin.collectEventData($eventCard, eventIndex);
                
                // Send AJAX request to clone event
                $.ajax({
                    url: aiohm_booking_admin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'aiohm_clone_event',
                        event_index: eventIndex,
                        event_data: eventData,
                        nonce: aiohm_booking_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Event cloned successfully! The page will reload to show the new event.');
                            location.reload();
                        } else {
                            alert('Error cloning event: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error cloning event. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $button.html(originalText);
                    }
                });
            }
        },

        handleDeleteEvent: function(e) {
            e.preventDefault();
            var $button = $(this);
            var eventIndex = $button.data('event-index');
            
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                $button.prop('disabled', true);
                var originalText = $button.html();
                $button.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Deleting...');
                
                // Send AJAX request to delete event
                $.ajax({
                    url: aiohm_booking_admin.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'aiohm_delete_event',
                        event_index: eventIndex,
                        nonce: aiohm_booking_admin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Event deleted successfully! The page will reload.');
                            location.reload();
                        } else {
                            alert('Error deleting event: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error deleting event. Please try again.');
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        $button.html(originalText);
                    }
                });
            }
        },

        handleEventONImport: function(e) {
            e.preventDefault();
            var $button = $(this);
            
            // Check if button is disabled (EventON not available)
            if ($button.prop('disabled')) {
                alert('EventON plugin is required for this functionality. Please install and activate EventON to use event import.');
                return;
            }
            
            alert('EventON import functionality will be available soon!');
        },


        collectEventData: function($eventCard, eventIndex) {
            var eventData = {};
            
            // Collect all form fields within the event card
            $eventCard.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name && value) {
                    // Extract field name from the array notation
                    var fieldMatch = name.match(/\[([^\]]+)\]$/);
                    if (fieldMatch) {
                        eventData[fieldMatch[1]] = value;
                    }
                }
            });
            
            return eventData;
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
            $eventCard.toggleClass('collapsed');
            
            // Update button icon
            var $icon = $button.find('.dashicons');
            if ($eventCard.hasClass('collapsed')) {
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                $button.attr('title', 'Expand event settings');
            } else {
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                $button.attr('title', 'Collapse event settings');
            }
            
            // Save state to localStorage
            AIOHM_Booking_Settings_Admin.saveEventCardState(eventIndex, $eventCard.hasClass('collapsed'));
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
                        $eventCard.addClass('collapsed');
                        
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
