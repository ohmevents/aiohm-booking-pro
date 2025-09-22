/**
 * AIOHM Booking - AI Event Import
 * Handles the AI-powered event import functionality on the tickets admin page.
 *
 * @package AIOHM_Booking_PRO
 * @since   1.2.5
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if we have the necessary admin globals
        if (typeof aiohm_booking_admin !== 'undefined') {
            initAIImport();
        } else {
            // Try again after a short delay
            setTimeout(function() {
                if (typeof aiohm_booking_admin !== 'undefined') {
                    initAIImport();
                }
            }, 1000);
        }
    });

    /**
     * Initialize AI import functionality
     */
    function initAIImport() {
        // Handle AI import button clicks
        $(document).on('click', '.aiohm-ai-import-btn', function(e) {
            e.preventDefault();

            const eventIndex = $(this).data('event-index');
            showAIImportDialog(eventIndex);
        });

        // Handle import dialog form submission
        $(document).on('submit', '#aiohm-ai-import-form', function(e) {
            e.preventDefault();

            const $form = $(this);
            const eventIndex = $form.data('event-index');
            const eventUrl = $form.find('#ai-event-url').val().trim();

            if (!isValidUrl(eventUrl)) {
                showAIImportError('Please enter a valid URL');
                return;
            }

            importEventWithAI(eventIndex, eventUrl);
        });

        // Handle dialog close
        $(document).on('click', '.aiohm-ai-import-dialog-close, .aiohm-ai-import-cancel', function(e) {
            e.preventDefault();
            closeAIImportDialog();
        });

        // Close dialog when clicking outside
        $(document).on('click', '.aiohm-ai-import-dialog-overlay', function(e) {
            if (e.target === this) {
                closeAIImportDialog();
            }
        });

        // Handle escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                closeAIImportDialog();
            }
        });
    }

    /**
     * Show the AI import dialog
     *
     * @param {number} eventIndex The event index to import to
     */
    function showAIImportDialog(eventIndex) {
        // Get the AI provider name from global settings if available
        const aiProvider = (window.aiohm_booking_admin && window.aiohm_booking_admin.default_ai_provider) 
            ? getAIProviderDisplayName(window.aiohm_booking_admin.default_ai_provider)
            : 'AI';
            
        const dialogHtml = `
            <div class="aiohm-ai-import-dialog-overlay">
                <div class="aiohm-ai-import-dialog">
                    <div class="aiohm-ai-import-dialog-header">
                        <h3>🤖 Import Event with ${aiProvider}</h3>
                        <button type="button" class="aiohm-ai-import-dialog-close">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>

                    <div class="aiohm-ai-import-dialog-content">
                        <form id="aiohm-ai-import-form" data-event-index="${eventIndex}">
                            <div class="aiohm-form-group">
                                <label for="ai-event-url">Event URL</label>
                                <input type="url"
                                       id="ai-event-url"
                                       name="event_url"
                                       placeholder="https://example.com/event-page"
                                       required>
                                <p class="description">
                                    Paste any URL containing event information. AI will extract and fill the event details automatically.
                                </p>
                            </div>

                            <div class="aiohm-ai-features">
                                <h4>AI will extract:</h4>
                                <div class="aiohm-ai-features-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 10px;">
                                    <div class="aiohm-ai-features-column">
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">🎭 Event type</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">📅 Event title</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">📝 Description</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">📍 Location</div>
                                    </div>
                                    <div class="aiohm-ai-features-column">
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">📆 Event date</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">⏰ Event time</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">📅 Event end date</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">⏱️ Event end time</div>
                                    </div>
                                    <div class="aiohm-ai-features-column">
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">🪑 Available seats</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">💰 Price</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">🕒 Early bird price</div>
                                        <div class="aiohm-ai-feature-item" style="margin-bottom: 8px; font-size: 13px;">📅 Early bird date</div>
                                    </div>
                                </div>
                            </div>

                            <div id="ai-import-preview" class="aiohm-ai-import-preview" style="display: none;"></div>

                            <div class="aiohm-ai-import-actions">
                                <button type="button" class="button aiohm-ai-import-cancel">
                                    Cancel
                                </button>
                                <button type="submit" class="button button-primary aiohm-ai-import-submit">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                    Import with AI
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Remove existing dialog if any
        $('.aiohm-ai-import-dialog-overlay').remove();

        // Add dialog to body
        $('body').append(dialogHtml);

        // Focus on URL input (safer approach)
        setTimeout(function() {
            const $input = $('#ai-event-url');
            if ($input.length && $input.is(':visible')) {
                try {
                    $input.focus();
                } catch (e) {
                    // Silently handle focus errors
                }
            }
        }, 100);

        // Add URL preview functionality
        $('#ai-event-url').on('blur', function() {
            const url = $(this).val().trim();
            if (url && isValidUrl(url)) {
                previewEventWithAI(url);
            }
        });
    }

    /**
     * Close the AI import dialog
     */
    function closeAIImportDialog() {
        $('.aiohm-ai-import-dialog-overlay').fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Validate URL format
     *
     * @param {string} url The URL to validate
     * @returns {boolean} True if valid URL
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Preview event information using AI
     *
     * @param {string} eventUrl The event URL
     */
    function previewEventWithAI(eventUrl) {
        const $preview = $('#ai-import-preview');

        $preview.html(`
            <div class="aiohm-loading">
                <span class="spinner is-active"></span>
                AI is analyzing the event page...
            </div>
        `).show();

        $.ajax({
            url: aiohm_booking_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_booking_ai_extract_event_info',
                event_url: eventUrl,
                nonce: aiohm_booking_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    const info = response.data.event_info;
                    const previewHtml = `
                        <div class="aiohm-event-preview">
                            <h4>🤖 AI Extracted Event Information:</h4>
                            <div class="aiohm-event-info">
                                <p><strong>📅 Title:</strong> ${escapeHtml(info.title || 'Not detected')}</p>
                                <p><strong>🎭 Type:</strong> ${escapeHtml(info.event_type || 'Not detected')}</p>
                                <p><strong>📆 Date:</strong> ${formatEventDate(info.event_date, info.event_end_date)}</p>
                                <p><strong>⏰ Time:</strong> ${escapeHtml(info.event_time || 'Not detected')}</p>
                                <p><strong>📍 Location:</strong> ${escapeHtml(info.location || 'Not detected')}</p>
                                <p><strong>� Seats:</strong> ${escapeHtml(info.available_seats || 'Not detected')}</p>
                                <p><strong>�💰 Price:</strong> ${escapeHtml(info.price || 'Not detected')}</p>
                                ${info.early_bird_price ? `<p><strong>🕒 Early Bird:</strong> ${escapeHtml(info.early_bird_price)} (${escapeHtml(info.early_bird_date || 'No deadline')})</p>` : ''}
                                <p><strong>📝 Description:</strong> ${escapeHtml(info.description ? info.description.substring(0, 150) + '...' : 'Not detected')}</p>
                            </div>
                            <div class="aiohm-confidence-score">
                                <small>AI Confidence: ${response.data.confidence || 'Medium'}</small>
                            </div>
                        </div>
                    `;
                    $preview.html(previewHtml);
                } else {
                    const errorMessage = (response.data && response.data.message) ? response.data.message : 'AI could not extract event information';
                    $preview.html(`
                        <div class="aiohm-error">
                            <span class="dashicons dashicons-warning"></span>
                            ${escapeHtml(errorMessage)}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                $preview.html(`
                    <div class="aiohm-error">
                        <span class="dashicons dashicons-warning"></span>
                        Failed to connect to AI service. Please try again.
                    </div>
                `);
            }
        });
    }

    /**
     * Import event using AI
     *
     * @param {number} eventIndex The event index
     * @param {string} eventUrl The event URL
     */
    function importEventWithAI(eventIndex, eventUrl) {
        const $submitBtn = $('.aiohm-ai-import-submit');
        const originalText = $submitBtn.html();

        // Show loading state
        $submitBtn.prop('disabled', true).html(`
            <span class="spinner is-active"></span>
            Importing...
        `);

        // First extract the event information
        $.ajax({
            url: aiohm_booking_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_booking_ai_extract_event_info',
                event_url: eventUrl,
                nonce: aiohm_booking_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.event_info) {
                    // Fill the form with extracted data
                    fillEventForm(eventIndex, response.data.event_info);

                    // Show success message
                    showAISuccessMessage('Event information imported successfully!');

                    // Close dialog
                    closeAIImportDialog();
                } else {
                    const errorMessage = (response.data && response.data.message) ? response.data.message : 'AI could not extract event information';
                    showAIImportError(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                showAIImportError('Failed to connect to AI service. Please try again.');
            },
            complete: function() {
                // Restore button state
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    /**
     * Fill the event form with extracted data
     *
     * @param {number} eventIndex The event index
     * @param {object} eventInfo The extracted event information
     */
    function fillEventForm(eventIndex, eventInfo) {
        const $eventCard = $(`.aiohm-event-item, .aiohm-booking-event-settings`).eq(eventIndex);

        // Fill basic information
        if (eventInfo.title) {
            $eventCard.find(`input[name="events[${eventIndex}][title]"]`).val(eventInfo.title);
        }

        if (eventInfo.description) {
            $eventCard.find(`textarea[name="events[${eventIndex}][description]"]`).val(eventInfo.description);
        }

        if (eventInfo.event_date) {
            $eventCard.find(`input[name="events[${eventIndex}][event_date]"]`).val(eventInfo.event_date);
        }

        if (eventInfo.event_time) {
            $eventCard.find(`input[name="events[${eventIndex}][event_time]"]`).val(eventInfo.event_time);
        }

        if (eventInfo.event_end_date) {
            $eventCard.find(`input[name="events[${eventIndex}][event_end_date]"]`).val(eventInfo.event_end_date);
        }

        if (eventInfo.event_end_time) {
            $eventCard.find(`input[name="events[${eventIndex}][event_end_time]"]`).val(eventInfo.event_end_time);
        }

        if (eventInfo.location) {
            $eventCard.find(`input[name="events[${eventIndex}][location]"]`).val(eventInfo.location);
        }

        if (eventInfo.available_seats) {
            $eventCard.find(`input[name="events[${eventIndex}][available_seats]"]`).val(eventInfo.available_seats);
        }

        if (eventInfo.price) {
            $eventCard.find(`input[name="events[${eventIndex}][price]"]`).val(eventInfo.price);
        }

        if (eventInfo.early_bird_price) {
            $eventCard.find(`input[name="events[${eventIndex}][early_bird_price]"]`).val(eventInfo.early_bird_price);
        }

        if (eventInfo.early_bird_date) {
            $eventCard.find(`input[name="events[${eventIndex}][early_bird_date]"]`).val(eventInfo.early_bird_date);
        }

        if (eventInfo.event_type) {
            $eventCard.find(`input[name="events[${eventIndex}][event_type]"]`).val(eventInfo.event_type);
        }

        // Trigger change events to update any dependent fields
        $eventCard.find('input, textarea, select').trigger('change');
    }

    /**
     * Show AI import error
     *
     * @param {string} message The error message
     */
    function showAIImportError(message) {
        const $form = $('#aiohm-ai-import-form');
        const $existingError = $form.find('.aiohm-error');

        if ($existingError.length) {
            $existingError.text(message);
        } else {
            $form.prepend(`
                <div class="aiohm-error notice notice-error">
                    <p>${escapeHtml(message)}</p>
                </div>
            `);
        }
    }

    /**
     * Show AI success message
     *
     * @param {string} message The success message
     */
    function showAISuccessMessage(message) {
        // Remove existing notices
        $('.aiohm-ai-success').remove();

        // Add success notice
        $('body').append(`
            <div class="aiohm-ai-success notice notice-success is-dismissible">
                <p><span class="dashicons dashicons-yes"></span> ${escapeHtml(message)}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.aiohm-ai-success').fadeOut();
        }, 5000);
    }

    /**
     * Format event date for display
     *
     * @param {string} startDate The start date
     * @param {string} endDate The end date
     * @returns {string} Formatted date string
     */
    function formatEventDate(startDate, endDate) {
        if (!startDate) return 'Not detected';

        let formatted = startDate;
        if (endDate && endDate !== startDate) {
            formatted += ' - ' + endDate;
        }

        return formatted;
    }

    /**
     * Get AI provider display name
     *
     * @param {string} provider The provider code
     * @returns {string} Display name
     */
    function getAIProviderDisplayName(provider) {
        const providerNames = {
            'openai': 'OpenAI',
            'gemini': 'Google Gemini',
            'shareai': 'ShareAI',
            'ollama': 'Ollama',
            'claude': 'Claude'
        };
        
        return providerNames[provider] || 'AI';
    }

    /**
     * Escape HTML to prevent XSS
     *
     * @param {string} text The text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);
