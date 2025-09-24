/**
 * AIOHM Booking - Facebook Event Import
 * Handles the Facebook event import functionality on the tickets admin page.
 * 
 * @package AIOHM_Booking_PRO
 * @since   1.2.3
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initFacebookImport();
    });

    /**
     * Initialize Facebook import functionality
     */
    function initFacebookImport() {
        // Handle Facebook import button clicks
        $(document).on('click', '.aiohm-facebook-import-btn', function(e) {
            e.preventDefault();
            
            const eventIndex = $(this).data('event-index');
            showImportDialog(eventIndex);
        });

        // Handle import dialog form submission
        $(document).on('submit', '#aiohm-facebook-import-form', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const eventIndex = $form.data('event-index');
            const facebookUrl = $form.find('#facebook-event-url').val().trim();
            
            if (!isValidFacebookUrl(facebookUrl)) {
                showImportError(aiohm_facebook_import.i18n.invalid_url);
                return;
            }
            
            importFacebookEvent(eventIndex, facebookUrl);
        });

        // Handle dialog close
        $(document).on('click', '.aiohm-ai-import-dialog-close, .aiohm-ai-import-cancel', function(e) {
            e.preventDefault();
            closeImportDialog();
        });

        // Close dialog when clicking outside
        $(document).on('click', '.aiohm-ai-import-dialog-overlay', function(e) {
            if (e.target === this) {
                closeImportDialog();
            }
        });

        // Handle escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                closeImportDialog();
            }
        });
    }

    /**
     * Show the Facebook import dialog
     * 
     * @param {number} eventIndex The event index to import to
     */
    function showImportDialog(eventIndex) {
        const dialogHtml = `
            <div class="aiohm-ai-import-dialog-overlay">
                <div class="aiohm-ai-import-dialog">
                    <div class="aiohm-ai-import-dialog-header">
                        <h3>${aiohm_facebook_import.i18n.enter_url}</h3>
                        <button type="button" class="aiohm-ai-import-dialog-close">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                    
                    <div class="aiohm-ai-import-dialog-content">
                        <form id="aiohm-facebook-import-form" data-event-index="${eventIndex}">
                            <div class="aiohm-form-group">
                                <label for="facebook-event-url">Facebook Event URL</label>
                                <input type="url" 
                                       id="facebook-event-url" 
                                       name="facebook_url" 
                                       placeholder="${aiohm_facebook_import.i18n.example_url}"
                                       required>
                                <p class="description">
                                    Paste the URL of the Facebook event you want to import.
                                </p>
                            </div>
                            
                            <div id="ai-import-preview" class="aiohm-ai-import-preview" style="display: none;"></div>
                            
                            <div class="aiohm-ai-import-actions">
                                <button type="button" class="button aiohm-ai-import-cancel">
                                    ${aiohm_facebook_import.i18n.cancel_button}
                                </button>
                                <button type="submit" class="button button-primary aiohm-ai-import-submit">
                                    ${aiohm_facebook_import.i18n.import_button}
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
            const $input = $('#facebook-event-url');
            if ($input.length && $input.is(':visible') && !$input.is(':disabled')) {
                try {
                    $input[0].focus(); // Use native focus instead of jQuery focus
                } catch (e) {
                    // Silently handle focus errors
                }
            }
        }, 200); // Increased timeout to ensure DOM is ready

        // Add URL preview functionality
        $('#facebook-event-url').on('blur', function() {
            const url = $(this).val().trim();
            if (url && isValidFacebookUrl(url)) {
                previewFacebookEvent(url);
            }
        });
    }

    /**
     * Close the import dialog
     */
    function closeImportDialog() {
        $('.aiohm-ai-import-dialog-overlay').fadeOut(200, function() {
            $(this).remove();
        });
    }

    /**
     * Validate Facebook URL format
     * 
     * @param {string} url The URL to validate
     * @returns {boolean} True if valid Facebook event URL
     */
    function isValidFacebookUrl(url) {
        const patterns = [
            /^https?:\/\/(www\.|m\.)?facebook\.com\/events\/\d+/,
            /^https?:\/\/fb\.me\/e\/\d+/,
            /^https?:\/\/(www\.|m\.)?facebook\.com\/events\/[^\/]+\/\d+/
        ];
        
        return patterns.some(pattern => pattern.test(url));
    }

    /**
     * Preview Facebook event information
     * 
     * @param {string} facebookUrl The Facebook event URL
     */
    function previewFacebookEvent(facebookUrl) {
        const $preview = $('#ai-import-preview');
        
        $preview.html(`
            <div class="aiohm-loading">
                <span class="spinner is-active"></span>
                ${aiohm_facebook_import.i18n.loading}
            </div>
        `).show();

        $.ajax({
            url: aiohm_facebook_import.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_booking_get_facebook_event_info',
                facebook_url: facebookUrl,
                nonce: aiohm_facebook_import.nonce
            },
            success: function(response) {
                if (response.success) {
                    const info = response.data.event_info;
                    const previewHtml = `
                        <div class="aiohm-event-preview">
                            <h4>Event Preview:</h4>
                            <div class="aiohm-event-info">
                                <p><strong>Name:</strong> ${escapeHtml(info.name || 'N/A')}</p>
                                <p><strong>Date:</strong> ${formatEventDate(info.start_time)}</p>
                                <p><strong>Description:</strong> ${escapeHtml(info.description || 'N/A')}</p>
                            </div>
                        </div>
                    `;
                    $preview.html(previewHtml);
                } else {
                    $preview.html(`
                        <div class="aiohm-error">
                            <span class="dashicons dashicons-warning"></span>
                            Could not preview event: ${escapeHtml(response.data.message)}
                        </div>
                    `);
                }
            },
            error: function() {
                $preview.html(`
                    <div class="aiohm-error">
                        <span class="dashicons dashicons-warning"></span>
                        Failed to load event preview.
                    </div>
                `);
            }
        });
    }

    /**
     * Import Facebook event data
     * 
     * @param {number} eventIndex The event index to populate
     * @param {string} facebookUrl The Facebook event URL
     */
    function importFacebookEvent(eventIndex, facebookUrl) {
        const $submitBtn = $('.aiohm-ai-import-submit');
        const originalText = $submitBtn.text();
        
        // Show loading state
        $submitBtn.prop('disabled', true)
                  .html('<span class="spinner is-active"></span>' + aiohm_facebook_import.i18n.importing);

        $.ajax({
            url: aiohm_facebook_import.ajax_url,
            type: 'POST',
            data: {
                action: 'aiohm_booking_import_facebook_event',
                facebook_url: facebookUrl,
                event_index: eventIndex,
                nonce: aiohm_facebook_import.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateEventForm(eventIndex, response.data.event_data);
                    showImportSuccess(aiohm_facebook_import.i18n.import_success);
                    closeImportDialog();
                } else {
                    showImportError(response.data.message || aiohm_facebook_import.i18n.import_failed);
                }
            },
            error: function() {
                showImportError(aiohm_facebook_import.i18n.import_failed);
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Populate the event form with imported data
     * 
     * @param {number} eventIndex The event index
     * @param {Object} eventData The imported event data
     */
    function populateEventForm(eventIndex, eventData) {
        const prefix = `events[${eventIndex}]`;
        
        // Populate form fields
        $(`input[name="${prefix}[title]"]`).val(eventData.title || '');
        $(`textarea[name="${prefix}[description]"]`).val(eventData.description || '');
        $(`input[name="${prefix}[event_date]"]`).val(eventData.event_date || '');
        $(`input[name="${prefix}[event_time]"]`).val(eventData.event_time || '');
        $(`input[name="${prefix}[available_seats]"]`).val(eventData.available_seats || 50);
        $(`input[name="${prefix}[price]"]`).val(eventData.price || 25);
        $(`input[name="${prefix}[early_bird_price]"]`).val(eventData.early_bird_price || '');
        $(`input[name="${prefix}[early_bird_date]"]`).val(eventData.early_bird_date || '');

        // Trigger change events for any dependent functionality
        $(`input[name="${prefix}[title]"]`).trigger('change');

        // Add visual feedback
        const $eventBox = $(`.aiohm-event-item, .aiohm-booking-event-settings`).eq(eventIndex);
        $eventBox.addClass('aiohm-imported-highlight');
        setTimeout(function() {
            $eventBox.removeClass('aiohm-imported-highlight');
        }, 2000);
    }

    /**
     * Show import success message
     * 
     * @param {string} message Success message
     */
    function showImportSuccess(message) {
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
     * Show import error message
     * 
     * @param {string} message Error message
     */
    function showImportError(message) {
        const $form = $('#aiohm-facebook-import-form');
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
     * Escape HTML entities
     * 
     * @param {string} text Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Format event date for display
     * 
     * @param {string} dateString ISO date string
     * @returns {string} Formatted date
     */
    function formatEventDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch (e) {
            return dateString;
        }
    }

})(jQuery);