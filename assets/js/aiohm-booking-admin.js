(function($) {
    'use strict';

    window.AIOHM_Booking_Admin = {
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.hideNotices();
        },

        bindEvents: function() {
            // Handle add event button
            $(document).on('click', '.aiohm-add-event-btn', function(e) {
                e.preventDefault();
                if (typeof AIOHM_Booking_Settings_Admin !== 'undefined' && AIOHM_Booking_Settings_Admin.addNewEvent) {
                    AIOHM_Booking_Settings_Admin.addNewEvent();
                }
            });

            // Toggle event details for tickets module
            $(document).on('click', '.aiohm-event-toggle-btn', function(e) {
                e.preventDefault();
                AIOHM_Booking_Admin.toggleEventDetails($(this));
            });

            // Handle individual event save buttons (only for event buttons with data-event-index)
            $(document).on('click', '.aiohm-individual-save-btn[data-event-index]', function(e) {
                e.preventDefault();
                AIOHM_Booking_Admin.handleIndividualEventSave($(this));
            });

            // Handle add teacher button
            $(document).on('click', '.aiohm-add-teacher-btn', function(e) {
                e.preventDefault();
                AIOHM_Booking_Admin.addTeacher($(this));
            });

            // Handle upload photo button
            $(document).on('click', '.aiohm-upload-photo-btn', function(e) {
                e.preventDefault();
                AIOHM_Booking_Admin.uploadTeacherPhoto($(this));
            });

            // Handle remove photo button
            $(document).on('click', '.aiohm-remove-photo-btn', function(e) {
                e.preventDefault();
                AIOHM_Booking_Admin.removeTeacherPhoto($(this));
            });

            // Handle remove teacher button
            $(document).on('click', '.aiohm-remove-teacher-btn', function(e) {
                e.preventDefault();
                AIOHM_Booking_Admin.removeTeacher($(this));
            });

            // Handle date input changes
            $(document).on('change', 'input[name*="[date]"]', function() {
                AIOHM_Booking_Admin.updateDateDependencies($(this));
            });

            // Handle capacity changes
            $(document).on('input', 'input[name*="[capacity]"]', function() {
                AIOHM_Booking_Admin.validateCapacity($(this));
            });

            // Handle pricing changes
            $(document).on('input', 'input[name*="[pricing]"]', function() {
                AIOHM_Booking_Admin.validatePricing($(this));
            });
        },

        initSortable: function() {
            if ($.fn.sortable) {
                $('.aiohm-events-container').sortable({
                    handle: '.aiohm-event-handle',
                    opacity: 0.7,
                    placeholder: 'aiohm-event-placeholder',
                    update: function() {
                        AIOHM_Booking_Admin.updateEventOrder();
                    }
                });
            }
        },

        hideNotices: function() {
            // Hide upgrade banners and notices on settings page - but preserve functional notices
            $('.aiohm-booking-admin .aiohm-booking-minimal-banner, .aiohm-booking-admin .aiohm-pro-notice, .aiohm-booking-admin .fs-modal, .aiohm-booking-admin .fs-banner').hide();
            
            // Hide Freemius notices specifically
            $('.aiohm-booking-admin .fs-notice').hide();
            
            // Hide notices containing upgrade-related text - but preserve success/error
            $('.aiohm-booking-admin .notice').each(function() {
                var $notice = $(this);
                if (!$notice.hasClass('notice-success') && !$notice.hasClass('notice-error')) {
                    var text = $notice.text().toLowerCase();
                    if (text.includes('pro') || text.includes('upgrade') || text.includes('requires') || text.includes('premium')) {
                        $notice.hide();
                    }
                }
            });
            
            // Use MutationObserver to watch for dynamically added notices (modern replacement for DOMNodeInserted)
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) { // Element node
                                var $element = $(node);
                                if ($element.hasClass('aiohm-booking-minimal-banner') || $element.hasClass('aiohm-pro-notice') || $element.hasClass('fs-notice') || $element.hasClass('fs-modal') || $element.hasClass('fs-banner')) {
                                    $element.hide();
                                }
                                // Hide dynamically added notices with upgrade text
                                if ($element.hasClass('notice') && !$element.hasClass('notice-success') && !$element.hasClass('notice-error')) {
                                    var text = $element.text().toLowerCase();
                                    if (text.includes('pro') || text.includes('upgrade') || text.includes('requires') || text.includes('premium')) {
                                        $element.hide();
                                    }
                                }
                            }
                        });
                    });
                });
                
                // Start observing
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        },

        addEvent: function() {
            const $container = $('.aiohm-events-container');
            const eventIndex = $container.children('.aiohm-event-row').length;
            const template = $('#aiohm-event-template').html();
            
            if (template) {
                const eventHtml = template.replace(/{{INDEX}}/g, eventIndex);
                $container.append(eventHtml);
                
                // Initialize new event
                this.initializeEvent(eventIndex);
            }
        },

        removeEvent: function($button) {
            const $eventRow = $button.closest('.aiohm-event-row');
            $eventRow.fadeOut(300, function() {
                $(this).remove();
                AIOHM_Booking_Admin.updateEventOrder();
            });
        },

        toggleEventDetails: function($button) {
            // Handle different card structures
            let $details;

            // Check if we're in the tickets module (card structure)
            if ($button.closest('.aiohm-booking-admin-card').length) {
                $details = $button.closest('.aiohm-booking-admin-card').find('.aiohm-card-body');
            } else {
                // Original event row structure
                $details = $button.closest('.aiohm-event-row').find('.aiohm-event-details');
            }

            const isVisible = $details.is(':visible');

            $details.slideToggle(300);

            // Update button icon and ARIA attributes
            const $icon = $button.find('.dashicons');
            if (isVisible) {
                $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                $button.attr('aria-expanded', 'false');
                $button.attr('aria-label', 'Expand event details');
            } else {
                $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                $button.attr('aria-expanded', 'true');
                $button.attr('aria-label', 'Collapse event details');
            }
        },

        initializeEvent: function(eventIndex) {
            // Initialize date picker if available
            if ($.fn.datepicker) {
                $(`input[name="events[${eventIndex}][date]"]`).datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0
                });
            }

            // Initialize time picker if available
            if ($.fn.timepicker) {
                $(`input[name="events[${eventIndex}][start_time]"], input[name="events[${eventIndex}][end_time]"]`).timepicker({
                    timeFormat: 'HH:mm',
                    interval: 15,
                    minTime: '00:00',
                    maxTime: '23:59'
                });
            }
        },

        updateEventOrder: function() {
            $('.aiohm-event-row').each(function(index) {
                $(this).find('input, select, textarea').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/events\[\d+\]/, `events[${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        updateDateDependencies: function($input) {
            const eventRow = $input.closest('.aiohm-event-row');
            const selectedDate = $input.val();
            
            if (selectedDate) {
                // Update minimum date for related events if needed
                this.validateEventDate(eventRow, selectedDate);
            }
        },

        validateEventDate: function($eventRow, date) {
            const today = new Date().toISOString().split('T')[0];
            
            if (date < today) {
                alert('Event date cannot be in the past.');
                $eventRow.find('input[name*="[date]"]').val('');
                return false;
            }
            
            return true;
        },

        validateCapacity: function($input) {
            const value = parseInt($input.val());
            const min = parseInt($input.attr('min')) || 1;
            const max = parseInt($input.attr('max')) || 1000;
            
            if (value < min) {
                $input.val(min);
            } else if (value > max) {
                $input.val(max);
            }
        },

        validatePricing: function($input) {
            const value = parseFloat($input.val());
            
            if (value < 0) {
                $input.val(0);
            }
            
            // Format to 2 decimal places
            if (!isNaN(value)) {
                $input.val(value.toFixed(2));
            }
        },

        handleIndividualEventSave: function($button) {
            const eventIndex = $button.data('event-index');
            const $eventCard = $button.closest('.aiohm-booking-event-settings');
            
            // Disable button and show loading state
            $button.prop('disabled', true);
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update spin"></span> Saving...');
            
            // Collect all form data for this event
            const eventData = {};
            $eventCard.find('input, textarea, select').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (name && name.startsWith('events[' + eventIndex + ']')) {
                    // Extract field name from events[index][field]
                    const fieldMatch = name.match(/events\[\d+\]\[([^\]]+)\](?:\[(.*)\])?/);
                    if (fieldMatch) {
                        const fieldName = fieldMatch[1];
                        const subField = fieldMatch[2];
                        
                        if (subField) {
                            // Handle nested arrays like teachers
                            if (!eventData[fieldName]) {
                                eventData[fieldName] = [];
                            }
                            if (!eventData[fieldName][subField]) {
                                eventData[fieldName][subField] = {};
                            }
                            // For now, collect simple values - teachers need special handling
                            if ($field.attr('type') !== 'hidden' || name.includes('[photo]')) {
                                // This is a simplified version - teachers need more complex handling
                            }
                        } else {
                            eventData[fieldName] = $field.val();
                        }
                    }
                }
            });
            
            // Handle teachers data specifically
            const teachers = [];
            $eventCard.find('.aiohm-teacher-item').each(function(index) {
                const $teacherItem = $(this);
                teachers.push({
                    name: $teacherItem.find('input[name*="teachers[' + index + '][name]"]').val(),
                    photo: $teacherItem.find('input[name*="teachers[' + index + '][photo]"]').val()
                });
            });
            eventData.teachers = teachers;
            
            // Prepare data for AJAX
            const ajaxData = {
                action: 'aiohm_booking_save_individual_event',
                nonce: aiohm_booking_admin.nonce,
                event_index: eventIndex,
                events: {}
            };
            ajaxData.events[eventIndex] = eventData;
            
            // Send AJAX request
            $.ajax({
                url: aiohm_booking_admin.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        // Show success state
                        $button.removeClass('saving error').addClass('success');
                        $button.html('<span class="dashicons dashicons-yes"></span> Saved!');
                        
                        // Reset after 2 seconds
                        setTimeout(function() {
                            $button.removeClass('success').html(originalText);
                        }, 2000);
                    } else {
                        // Show error state
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        $button.removeClass('saving success').addClass('error');
                        $button.html('<span class="dashicons dashicons-no"></span> Error');
                        
                        // Show error message
                        alert('Failed to save event: ' + errorMsg);
                        
                        // Reset button after 3 seconds
                        setTimeout(function() {
                            $button.removeClass('error').html(originalText);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    // Show error state
                    $button.removeClass('saving success').addClass('error');
                    $button.html('<span class="dashicons dashicons-no"></span> Error');
                    
                    // Show error message
                    alert('AJAX error: ' + error);
                    
                    // Reset button after 3 seconds
                    setTimeout(function() {
                        $button.removeClass('error').html(originalText);
                    }, 3000);
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false);
                }
            });
        },

        addTeacher: function($button) {
            const eventIndex = $button.data('event-index');
            const $eventCard = $button.closest('.aiohm-booking-event-settings');
            const $teachersContainer = $eventCard.find('.aiohm-teachers-container');
            
            // Get current teacher count
            const currentTeachers = $teachersContainer.find('.aiohm-teacher-item').length;
            const newTeacherIndex = currentTeachers;
            
            // Create new teacher HTML with proper upload functionality
            const teacherHtml = `
                <div class="aiohm-teacher-item" data-teacher-index="${newTeacherIndex}">
                    <div class="aiohm-teacher-details">
                        <div class="aiohm-columns-2">
                            <div class="aiohm-form-group">
                                <label>Name</label>
                                <input type="text" name="events[${eventIndex}][teachers][${newTeacherIndex}][name]" value="" placeholder="Enter teacher name">
                            </div>
                            <div class="aiohm-form-group">
                                <label>Photo</label>
                                <div class="aiohm-teacher-photo-upload">
                                    <div class="aiohm-photo-preview aiohm-hidden" id="teacher-photo-preview-${eventIndex}-${newTeacherIndex}">
                                        <img src="" alt="Teacher" class="aiohm-teacher-photo-img">
                                        <button type="button" class="aiohm-remove-photo-btn" data-event-index="${eventIndex}" data-teacher-index="${newTeacherIndex}">
                                            <span class="dashicons dashicons-no"></span>
                                        </button>
                                    </div>
                                    <div class="aiohm-photo-upload-controls" id="teacher-photo-upload-${eventIndex}-${newTeacherIndex}">
                                        <input type="hidden" name="events[${eventIndex}][teachers][${newTeacherIndex}][photo]" value="" id="teacher-photo-input-${eventIndex}-${newTeacherIndex}">
                                        <button type="button" class="button aiohm-upload-photo-btn" data-event-index="${eventIndex}" data-teacher-index="${newTeacherIndex}">
                                            <span class="dashicons dashicons-camera"></span>
                                            Upload Photo
                                        </button>
                                        <p class="aiohm-photo-hint">Recommended: 200x200px, JPG/PNG format</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="button button-secondary aiohm-remove-teacher-btn" data-teacher-index="${newTeacherIndex}">
                            <span class="dashicons dashicons-trash"></span>
                            Remove Teacher
                        </button>
                    </div>
                </div>
            `;
            
            // Add to container
            $teachersContainer.append(teacherHtml);
            
            // Hide the "no teachers" message if it exists
            $eventCard.find('.aiohm-no-teachers').hide();
        },

        uploadTeacherPhoto: function($button) {
            const eventIndex = $button.data('event-index');
            const teacherIndex = $button.data('teacher-index');
            
            // Create a file input element
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml';
            
            fileInput.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Create a FileReader to read the file
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const imageUrl = event.target.result;
                        
                        // Update the photo input and preview
                        const $photoInput = $(`#teacher-photo-input-${eventIndex}-${teacherIndex}`);
                        const $photoPreview = $(`#teacher-photo-preview-${eventIndex}-${teacherIndex}`);
                        const $uploadControls = $(`#teacher-photo-upload-${eventIndex}-${teacherIndex}`);
                        
                        // Set the image URL
                        $photoInput.val(imageUrl);
                        $photoPreview.find('.aiohm-teacher-photo-img').attr('src', imageUrl);
                        
                        // Show preview, hide upload controls
                        $photoPreview.removeClass('aiohm-hidden');
                        $uploadControls.addClass('aiohm-hidden');
                    };
                    reader.readAsDataURL(file);
                }
            };
            
            // Trigger file selection
            fileInput.click();
        },

        removeTeacherPhoto: function($button) {
            const eventIndex = $button.data('event-index');
            const teacherIndex = $button.data('teacher-index');
            
            // Clear the photo input and preview
            const $photoInput = $(`#teacher-photo-input-${eventIndex}-${teacherIndex}`);
            const $photoPreview = $(`#teacher-photo-preview-${eventIndex}-${teacherIndex}`);
            const $uploadControls = $(`#teacher-photo-upload-${eventIndex}-${teacherIndex}`);
            
            // Clear values
            $photoInput.val('');
            $photoPreview.find('.aiohm-teacher-photo-img').attr('src', '');
            
            // Hide preview, show upload controls
            $photoPreview.addClass('aiohm-hidden');
            $uploadControls.removeClass('aiohm-hidden');
        },

        removeTeacher: function($button) {
            const $teacherItem = $button.closest('.aiohm-teacher-item');
            const $teachersContainer = $teacherItem.closest('.aiohm-teachers-container');
            
            // Remove the teacher item
            $teacherItem.remove();
            
            // Check if there are any teachers left
            if ($teachersContainer.find('.aiohm-teacher-item').length === 0) {
                // Show the "no teachers" message
                $teachersContainer.siblings('.aiohm-no-teachers').show();
            }
        },

        // Handle individual event save
        handleIndividualEventSave: function($button) {
            const eventIndex = $button.data('event-index');
            const $eventCard = $button.closest('.aiohm-booking-event-settings');
            
            if (eventIndex === undefined) {
                alert('Error: Could not determine event index.');
                return;
            }
            
            // Show loading state
            const originalText = $button.text();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');
            
            // Serialize form data for this specific event
            const $form = $eventCard.closest('form');
            const formData = new FormData($form[0]);
            
            // Prepare data for AJAX
            const ajaxData = {
                action: 'aiohm_booking_save_individual_event',
                nonce: aiohm_booking_admin.nonce,
                event_index: eventIndex,
                events: {}
            };
            
            // Extract only the event data for this specific event
            const eventData = {};
            for (let [key, value] of formData.entries()) {
                if (key.startsWith(`events[${eventIndex}]`)) {
                    // Parse the field name to extract the nested structure  
                    const fieldMatch = key.match(/^events\[(\d+)\](.+)$/);
                    if (fieldMatch) {
                        const fieldName = fieldMatch[2];
                        
                        // Handle nested fields like [teachers][0][name]
                        if (fieldName.includes('[')) {
                            const nestedMatch = fieldName.match(/^\[(.+?)\]\[(\d+)\]\[(.+)\]$/);
                            if (nestedMatch) {
                                const [, parentField, index, subField] = nestedMatch;
                                if (!eventData[parentField]) eventData[parentField] = [];
                                if (!eventData[parentField][index]) eventData[parentField][index] = {};
                                eventData[parentField][index][subField] = value;
                            }
                        } else {
                            // Handle simple fields like [title] 
                            const simpleField = fieldName.match(/^\[(.+)\]$/);
                            if (simpleField) {
                                eventData[simpleField[1]] = value;
                            } else {
                                eventData[fieldName] = value;
                            }
                        }
                    }
                }
            }
            
            ajaxData.events[eventIndex] = eventData;
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    // Reset button
                    $button.prop('disabled', false).html(originalText);
                    
                    if (response.success) {
                        // Show success feedback
                        $button.html('<span class="dashicons dashicons-yes"></span> Saved!');
                        setTimeout(function() {
                            $button.html(originalText);
                        }, 2000);
                    } else {
                        // Show error message
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                        alert('Failed to save event: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    // Reset button
                    $button.prop('disabled', false).html(originalText);
                    
                    // Show error message
                    alert('Failed to save event: ' + error);
                }
            });
        }
    };

    // Character counter functionality
    $(document).on('input', 'textarea[maxlength], input[type="text"][maxlength]', function() {
        const $field = $(this);
        const maxLength = parseInt($field.attr('maxlength'));
        const currentLength = $field.val().length;
        const remaining = maxLength - currentLength;
        
        let $counter = $field.siblings('.character-counter');
        if ($counter.length === 0) {
            $counter = $('<div class="character-counter"></div>');
            $field.after($counter);
        }
        
        $counter.text(`${currentLength}/${maxLength} characters`);
        
        if (remaining < 20) {
            $counter.addClass('warning');
        } else {
            $counter.removeClass('warning');
        }
        
        if (remaining < 0) {
            $field.addClass('over-limit');
        } else {
            $field.removeClass('over-limit');
        }
    });

    // Field validation highlighting
    $(document).on('blur', 'input[required], textarea[required], select[required]', function() {
        const $field = $(this);
        const value = $field.val().trim();
        
        if (!value) {
            $field.addClass('error');
        } else {
            $field.removeClass('error');
        }
    });

    // Form submission validation
    $(document).on('submit', 'form', function(e) {
        const $form = $(this);
        let hasErrors = false;
        
        $form.find('input[required], textarea[required], select[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                $field.addClass('error');
                hasErrors = true;
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            $('html, body').animate({
                scrollTop: $('.error').first().offset().top - 100
            }, 500);
        }
    });

    // Numeric input validation
    $(document).on('input', 'input[type="number"]', function() {
        const $input = $(this);
        const value = $input.val();
        const min = parseFloat($input.attr('min'));
        const max = parseFloat($input.attr('max'));
        
        if (value !== '' && !isNaN(value)) {
            const numValue = parseFloat(value);
            
            if (!isNaN(min) && numValue < min) {
                $input.addClass('warning');
            } else if (!isNaN(max) && numValue > max) {
                $input.addClass('warning');
            } else {
                $input.removeClass('warning');
            }
        }
    });

    // Dropdown dependency handling
    $(document).on('change', 'select[data-dependent]', function() {
        const $select = $(this);
        const dependent = $select.data('dependent');
        const value = $select.val();
        
        if (dependent) {
            const $dependent = $(dependent);
            
            // Show/hide dependent field based on selection
            if (value && value !== '') {
                $dependent.closest('.form-row, .field-wrapper').show();
            } else {
                $dependent.closest('.form-row, .field-wrapper').hide();
            }
        }
    });

    // Auto-save functionality
    if (window.aiohm_booking_admin && window.aiohm_booking_admin.auto_save_enabled) {
        let autoSaveTimeout;
        
        $(document).on('input change', 'form input, form textarea, form select', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                // Trigger auto-save
                if (typeof AIOHM_Booking_Admin.autoSave === 'function') {
                    AIOHM_Booking_Admin.autoSave();
                }
            }, 3000); // Save after 3 seconds of inactivity
        });
    }

    // Dynamic field count validation
    $(document).on('input', 'input[data-counter]', function() {
        const $input = $(this);
        const targetSelector = $input.data('counter');
        const value = parseInt($input.val()) || 0;
        
        if (targetSelector) {
            const $counter = $(targetSelector);
            const currentCount = $counter.children().length;
            
            if (value !== currentCount) {
                if (value > currentCount) {
                    // Add fields
                    for (let i = currentCount; i < value; i++) {
                        const $field = $counter.children().first().clone();
                        $field.find('input, textarea, select').val('');
                        $counter.append($field);
                    }
                } else {
                    // Remove fields
                    $counter.children().slice(value).remove();
                }
            }
        }
    });

    // Preview iframe loading handler
    $(document).on('load', '.aiohm-preview-iframe', function() {
        $(this).siblings('.aiohm-preview-loading').hide();
    });

    // Initialize when document is ready
    $(document).ready(function() {
        try {
            AIOHM_Booking_Admin.init();
        } catch (error) {
            // Error initializing AIOHM Booking - silently fail in production
        }
    });

})(jQuery);