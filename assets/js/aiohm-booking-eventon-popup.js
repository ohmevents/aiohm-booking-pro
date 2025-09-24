/**
 * EventON Import Popup JavaScript
 * 
 * Handles the EventON import popup functionality in the admin events page.
 *
 * @package AIOHM_Booking_PRO
 * @since 1.2.5
 */

(function($) {
    'use strict';

    let currentEventIndex = null;
    let eventonEvents = [];

    $(document).ready(function() {
        initEventONPopup();
    });

    /**
     * Initialize EventON popup functionality.
     */
    function initEventONPopup() {
        // Handle EventON import button clicks
        $(document).on('click', '.aiohm-eventon-import-btn', function(e) {
            e.preventDefault();
            currentEventIndex = $(this).data('event-index');
            openEventONPopup();
        });

        // Handle popup close
        $(document).on('click', '.aiohm-popup-close, .aiohm-popup-cancel', function() {
            closeEventONPopup();
        });

        // Handle popup overlay click
        $(document).on('click', '.aiohm-popup-overlay', function(e) {
            if (e.target === this) {
                closeEventONPopup();
            }
        });

        // Handle EventON event selection
        $(document).on('click', '.aiohm-eventon-event-item', function() {
            $('.aiohm-eventon-event-item').removeClass('selected');
            $(this).addClass('selected');
            $('.aiohm-eventon-import-selected').prop('disabled', false);
        });

        // Handle import selected event
        $(document).on('click', '.aiohm-eventon-import-selected', function() {
            const selectedEvent = $('.aiohm-eventon-event-item.selected');
            if (selectedEvent.length) {
                const eventData = selectedEvent.data('event');
                importEventData(eventData);
            }
        });

        // Handle individual save buttons
        $(document).on('click', '.aiohm-individual-save-btn', function(e) {
            e.preventDefault();
            const eventIndex = $(this).data('event-index');
            saveIndividualEvent(eventIndex);
        });
    }

    /**
     * Open EventON import popup and load events.
     */
    function openEventONPopup() {
        const $popup = $('#aiohm-eventon-import-popup');
        const $loading = $popup.find('.aiohm-popup-loading');
        const $error = $popup.find('.aiohm-popup-error');
        const $eventsList = $popup.find('.aiohm-eventon-events-list');

        // Show popup and loading state
        $popup.show();
        $loading.show();
        $error.hide();
        $eventsList.empty();

        // Load EventON events via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aiohm_booking_get_eventon_events_list',
                nonce: aiohm_booking_admin.nonce
            },
            success: function(response) {
                $loading.hide();
                
                if (response.success && response.data.events) {
                    eventonEvents = response.data.events;
                    renderEventONEvents(response.data.events);
                } else {
                    showPopupError(response.data ? response.data.message : 'Failed to load EventON events.');
                }
            },
            error: function(xhr, status, error) {
                $loading.hide();
                showPopupError('Failed to load EventON events. Please try again.');
            }
        });
    }

    /**
     * Close EventON import popup.
     */
    function closeEventONPopup() {
        $('#aiohm-eventon-import-popup').hide();
        currentEventIndex = null;
        $('.aiohm-eventon-event-item').removeClass('selected');
        $('.aiohm-eventon-import-selected').prop('disabled', true);
    }

    /**
     * Show error message in popup.
     * 
     * @param {string} message Error message to display.
     */
    function showPopupError(message) {
        const $popup = $('#aiohm-eventon-import-popup');
        const $error = $popup.find('.aiohm-popup-error');
        const $eventsList = $popup.find('.aiohm-eventon-events-list');

        $error.find('.aiohm-error-message').text(message);
        $error.show();
        $eventsList.hide();
    }

    /**
     * Render EventON events in the popup.
     * 
     * @param {Array} events Array of EventON events.
     */
    function renderEventONEvents(events) {
        const $eventsList = $('#aiohm-eventon-events-list');
        
        if (!events || events.length === 0) {
            $eventsList.html('<p style="text-align: center; color: #666; padding: 20px;">No EventON events found.</p>');
            return;
        }

        let html = '';
        events.forEach(function(event) {
            html += `
                <div class="aiohm-eventon-event-item" data-event='${JSON.stringify(event).replace(/'/g, "&apos;")}'>
                    <h4 class="aiohm-eventon-event-title">${escapeHtml(event.title)}</h4>
                    <div class="aiohm-eventon-event-meta">
                        <span class="aiohm-eventon-event-date">📅 ${escapeHtml(event.date)}</span>
                        ${event.time ? `<span class="aiohm-eventon-event-time">🕐 ${escapeHtml(event.time)}</span>` : ''}
                        ${event.location ? `<span class="aiohm-eventon-event-location">📍 ${escapeHtml(event.location)}</span>` : ''}
                    </div>
                    ${event.description ? `<p class="aiohm-eventon-event-description">${escapeHtml(truncateText(event.description, 100))}</p>` : ''}
                </div>
            `;
        });

        $eventsList.html(html);
    }

    /**
     * Import selected EventON event data into the current event form.
     * 
     * @param {Object} eventData EventON event data.
     */
    function importEventData(eventData) {
        if (currentEventIndex === null) {
            alert('Error: No event card selected.');
            return;
        }

        const $eventCard = $(`.aiohm-eventon-import-btn[data-event-index="${currentEventIndex}"]`).closest('.aiohm-booking-event-settings');
        
        if ($eventCard.length === 0) {
            alert('Error: Event card not found.');
            return;
        }

        // Fill in the event form fields
        fillEventForm($eventCard, eventData);
        
        // Close popup
        closeEventONPopup();
        
        // Show success message
        showNotice('success', `Event data imported from EventON: "${eventData.title}"`);
        
        // Scroll to the event card
        $('html, body').animate({
            scrollTop: $eventCard.offset().top - 100
        }, 500);
    }

    /**
     * Fill event form with EventON data.
     * 
     * @param {jQuery} $eventCard Event card element.
     * @param {Object} eventData EventON event data.
     */
    function fillEventForm($eventCard, eventData) {
        // Event title (limit to 50 characters)
        const title = eventData.title || '';
        const truncatedTitle = title.length > 50 ? title.substring(0, 50) : title;
        $eventCard.find('input[name*="[title]"]').val(truncatedTitle);
        
        // Event description (limit to 150 characters)
        const description = eventData.description || '';
        const truncatedDescription = description.length > 150 ? description.substring(0, 150) : description;
        $eventCard.find('textarea[name*="[description]"]').val(truncatedDescription);
        
        // Event type
        $eventCard.find('input[name*="[event_type]"]').val('EventON Import');
        
        // Event dates and times
        if (eventData.start_date) {
            const startDate = eventData.start_date.split(' ')[0]; // Get date part
            $eventCard.find('input[name*="[event_date]"]').val(startDate);
        }
        
        if (eventData.end_date) {
            const endDate = eventData.end_date.split(' ')[0]; // Get date part
            $eventCard.find('input[name*="[event_end_date]"]').val(endDate);
        }
        
        if (!eventData.all_day && eventData.start_date) {
            const startTime = eventData.start_date.split(' ')[1]; // Get time part
            if (startTime) {
                $eventCard.find('input[name*="[event_time]"]').val(startTime);
            }
        }
        
        if (!eventData.all_day && eventData.end_date) {
            const endTime = eventData.end_date.split(' ')[1]; // Get time part
            if (endTime) {
                $eventCard.find('input[name*="[event_end_time]"]').val(endTime);
            }
        }
        
        // Add visual indicator that data was imported
        $eventCard.addClass('aiohm-imported-from-eventon');
        
        // Add a small badge or indicator
        const $header = $eventCard.find('.aiohm-card-header');
        if ($header.find('.aiohm-import-badge').length === 0) {
            $header.find('.aiohm-card-header-title').append('<span class="aiohm-import-badge" style="background: #4CAF50; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-left: 8px;">Imported from EventON</span>');
        }
        
        // Update character counters for the filled fields
        $eventCard.find('.aiohm-char-limited').each(function() {
            if (typeof AIOHM_Booking_Admin !== 'undefined' && AIOHM_Booking_Admin.updateCharacterCount) {
                AIOHM_Booking_Admin.updateCharacterCount.call(this);
            }
        });
    }

    /**
     * Save individual event via AJAX.
     * 
     * @param {number} eventIndex Event index to save.
     */
    function saveIndividualEvent(eventIndex) {
        const $eventCard = $(`.aiohm-individual-save-btn[data-event-index="${eventIndex}"]`).closest('.aiohm-booking-event-settings');
        const $saveBtn = $eventCard.find('.aiohm-individual-save-btn');
        
        if ($eventCard.length === 0) {
            alert('Error: Event card not found.');
            return;
        }

        // Disable save button and show loading
        $saveBtn.prop('disabled', true);
        const originalText = $saveBtn.html();
        $saveBtn.html('<span class="dashicons dashicons-update-alt" style="animation: rotation 1s infinite linear;"></span> Saving...');

        // Collect event data from the form
        const eventData = collectEventData($eventCard, eventIndex);

        // Send AJAX request to save the event
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aiohm_booking_save_individual_event',
                nonce: aiohm_booking_admin.nonce,
                event_index: eventIndex,
                event_data: eventData
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message || 'Event saved successfully!');
                    
                    // Add saved indicator
                    $eventCard.addClass('aiohm-event-saved');
                    
                    // Update save button
                    $saveBtn.html('<span class="dashicons dashicons-yes-alt"></span> Saved!');
                    
                    // Reset button after delay
                    setTimeout(function() {
                        $saveBtn.html(originalText).prop('disabled', false);
                        $eventCard.removeClass('aiohm-event-saved');
                    }, 2000);
                } else {
                    showNotice('error', response.data ? response.data.message : 'Failed to save event.');
                    $saveBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                showNotice('error', 'Failed to save event. Please try again.');
                $saveBtn.html(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Collect event data from form fields.
     * 
     * @param {jQuery} $eventCard Event card element.
     * @param {number} eventIndex Event index.
     * @returns {Object} Event data object.
     */
    function collectEventData($eventCard, eventIndex) {
        return {
            title: $eventCard.find('input[name*="[title]"]').val() || '',
            description: $eventCard.find('textarea[name*="[description]"]').val() || '',
            event_type: $eventCard.find('input[name*="[event_type]"]').val() || '',
            event_date: $eventCard.find('input[name*="[event_date]"]').val() || '',
            event_time: $eventCard.find('input[name*="[event_time]"]').val() || '',
            event_end_date: $eventCard.find('input[name*="[event_end_date]"]').val() || '',
            event_end_time: $eventCard.find('input[name*="[event_end_time]"]').val() || '',
            price: parseFloat($eventCard.find('input[name*="[price]"]').val()) || 0,
            early_bird_price: parseFloat($eventCard.find('input[name*="[early_bird_price]"]').val()) || 0,
            early_bird_date: $eventCard.find('input[name*="[early_bird_date]"]').val() || '',
            capacity: parseInt($eventCard.find('input[name*="[capacity]"]').val()) || 50
        };
    }

    /**
     * Show admin notice.
     * 
     * @param {string} type Notice type (success, error, warning, info).
     * @param {string} message Notice message.
     */
    function showNotice(type, message) {
        // Remove existing notices
        $('.aiohm-eventon-notice').remove();

        const $notice = $(`
            <div class="aiohm-eventon-notice notice notice-${type} is-dismissible" style="margin: 15px 0;">
                <p>${escapeHtml(message)}</p>
                <button type="button" class="notice-dismiss" aria-label="Dismiss this notice.">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

        // Insert notice at the top of the events container
        $('#events-container').before($notice);

        // Auto-dismiss success notices
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Handle manual dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Escape HTML to prevent XSS.
     * 
     * @param {string} text Text to escape.
     * @returns {string} Escaped text.
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Truncate text to specified length.
     * 
     * @param {string} text Text to truncate.
     * @param {number} length Maximum length.
     * @returns {string} Truncated text.
     */
    function truncateText(text, length) {
        if (!text || text.length <= length) return text;
        return text.substring(0, length) + '...';
    }

})(jQuery);

// Add CSS for rotation animation
const style = document.createElement('style');
style.textContent = `
    @keyframes rotation {
        from { transform: rotate(0deg); }
        to { transform: rotate(359deg); }
    }
    
    .aiohm-event-card.aiohm-imported-from-eventon {
        border-color: #4CAF50 !important;
        box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2) !important;
    }
    
    .aiohm-event-card.aiohm-event-saved {
        border-color: #2196F3 !important;
        box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2) !important;
    }
`;
document.head.appendChild(style);