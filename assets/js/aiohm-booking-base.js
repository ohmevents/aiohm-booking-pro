/**
 * AIOHM Booking Base JavaScript
 * Provides common functionality and patterns for all AIOHM Booking JavaScript modules
 * 
 * @package AIOHM_Booking_PRO
 * @version 1.2.5
 */

(function($) {
    'use strict';

    /**
     * Base AIOHM Booking Object
     * Provides common functionality that can be extended by specific modules
     */
    window.AIOHM_Booking_Base = {
        
        /**
         * Common configuration
         */
        config: {
            ajaxUrl: window.ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: '',
            loadingClass: 'aiohm-loading',
            errorClass: 'aiohm-error',
            successClass: 'aiohm-success',
            disabledClass: 'aiohm-disabled'
        },

        /**
         * Initialize base functionality
         * Should be called by extending objects
         */
        init: function() {
            this.setupGlobalConfig();
            this.bindGlobalEvents();
            this.initGlobalComponents();
            return this;
        },

        /**
         * Setup global configuration
         */
        setupGlobalConfig: function() {
            // Set nonce from global variables if available
            if (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) {
                this.config.nonce = window.aiohm_booking_admin.nonce;
            }
            if (window.aiohm_booking_frontend && window.aiohm_booking_frontend.nonce) {
                this.config.nonce = window.aiohm_booking_frontend.nonce;
            }
        },

        /**
         * Bind global events that are common across modules
         */
        bindGlobalEvents: function() {
            var self = this;
            
            // Global AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                if (settings.url && settings.url.indexOf('aiohm') !== -1) {
                    self.handleAjaxError(xhr, thrownError);
                }
            });

            // Global form validation
            $(document).on('submit', '.aiohm-form', function(e) {
                if (!self.validateForm($(this))) {
                    e.preventDefault();
                    return false;
                }
            });

            // Global toggle handlers
            $(document).on('click', '.aiohm-toggle', this.handleToggle.bind(this));
            $(document).on('change', '.toggle-input', this.handleToggleSwitch.bind(this));
        },

        /**
         * Initialize global components
         */
        initGlobalComponents: function() {
            this.initTooltips();
            this.initModals();
            this.initAccordions();
        },

        /**
         * Create a new module that extends this base
         * 
         * @param {string} name - Module name
         * @param {object} methods - Module-specific methods
         * @returns {object} - New module object
         */
        extend: function(name, methods) {
            var self = this;
            var module = $.extend({}, this, methods);
            
            // Store original init if exists
            var originalInit = methods.init;
            
            // Override init to call base init first
            module.init = function() {
                self.init.call(this);
                if (originalInit && typeof originalInit === 'function') {
                    originalInit.call(this);
                }
                return this;
            };

            // Store module reference
            window['AIOHM_Booking_' + name] = module;
            
            return module;
        },

        /**
         * Common event handlers
         */
        
        /**
         * Handle toggle buttons
         */
        handleToggle: function(e) {
            e.preventDefault();
            var $toggle = $(e.currentTarget);
            var target = $toggle.data('target');
            var $target = $(target);
            
            if ($target.length) {
                $target.toggle();
                $toggle.toggleClass('active');
            }
        },

        /**
         * Handle toggle switches
         */
        handleToggleSwitch: function(e) {
            var $switch = $(e.currentTarget);
            var $parent = $switch.closest('.toggle-wrapper, .aiohm-setting-row');
            
            if ($switch.is(':checked')) {
                $parent.addClass('enabled').removeClass('disabled');
            } else {
                $parent.addClass('disabled').removeClass('enabled');
            }
        },

        /**
         * Common AJAX functionality
         */
        
        /**
         * Make AJAX request with common error handling
         * 
         * @param {object} options - AJAX options
         * @returns {Promise}
         */
        makeAjaxRequest: function(options) {
            var self = this;
            var defaults = {
                url: this.config.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    nonce: this.config.nonce
                },
                beforeSend: function() {
                    if (options.button) {
                        self.setLoadingState(options.button, true);
                    }
                },
                complete: function() {
                    if (options.button) {
                        self.setLoadingState(options.button, false);
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, error);
                }
            };
            
            options = $.extend(true, defaults, options);
            return $.ajax(options);
        },

        /**
         * Handle AJAX errors consistently
         */
        handleAjaxError: function(xhr, error) {
            var message = 'An error occurred. Please try again.';
            
            if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data;
            } else if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data) {
                        message = response.data;
                    }
                } catch(e) {
                    // Use default message
                }
            }
            
            this.showNotification(message, 'error');
            
        },

        /**
         * UI Helper Methods
         */
        
        /**
         * Set loading state for buttons/elements
         */
        setLoadingState: function($element, loading) {
            if (loading) {
                $element.addClass(this.config.loadingClass)
                        .prop('disabled', true)
                        .data('original-text', $element.text());
                
                if ($element.is('button, .button')) {
                    $element.text($element.data('loading-text') || 'Loading...');
                }
            } else {
                $element.removeClass(this.config.loadingClass)
                        .prop('disabled', false);
                
                var originalText = $element.data('original-text');
                if (originalText) {
                    $element.text(originalText);
                }
            }
        },

        /**
         * Show notification messages
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            // Remove existing notifications
            $('.aiohm-notification').remove();
            
            var $notification = $('<div class="aiohm-notification aiohm-notification-' + type + '">' +
                '<p>' + this.escapeHtml(message) + '</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' +
                '</div>');
            
            // Insert after the first h1 or at the top of .wrap
            var $target = $('.wrap h1').first();
            if ($target.length) {
                $target.after($notification);
            } else {
                $('.wrap').prepend($notification);
            }
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Handle dismiss button
            $notification.on('click', '.notice-dismiss', function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Form validation
         */
        validateForm: function($form) {
            var isValid = true;
            var self = this;
            
            // Clear previous errors
            $form.find('.field-error').removeClass('field-error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required], .aiohm-required-field').each(function() {
                var $field = $(this);
                if (!self.validateField($field)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },

        /**
         * Validate individual field
         */
        validateField: function($field) {
            var value = $field.val();
            var isValid = true;
            var errorMessage = '';
            
            // Check if required field is empty
            if (($field.prop('required') || $field.hasClass('aiohm-required-field')) && !value) {
                isValid = false;
                errorMessage = 'This field is required.';
            }
            
            // Email validation
            if ($field.attr('type') === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
            }
            
            // Number validation
            if ($field.attr('type') === 'number' && value) {
                if (isNaN(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid number.';
                }
            }
            
            // Update field state
            if (!isValid) {
                $field.addClass('field-error');
                if (!$field.siblings('.error-message').length) {
                    $field.after('<div class="error-message">' + errorMessage + '</div>');
                }
            } else {
                $field.removeClass('field-error');
                $field.siblings('.error-message').remove();
            }
            
            return isValid;
        },

        /**
         * Component initialization
         */
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                var $element = $(this);
                var tooltip = $element.data('tooltip');
                
                $element.on('mouseenter', function() {
                    var $tooltip = $('<div class="aiohm-tooltip">' + tooltip + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $element.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 5,
                        left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });
                }).on('mouseleave', function() {
                    $('.aiohm-tooltip').remove();
                });
            });
        },

        /**
         * Initialize modals
         */
        initModals: function() {
            // Modal triggers
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('modal');
                var $modal = $('#' + modalId);
                if ($modal.length) {
                    $modal.addClass('active');
                    $('body').addClass('modal-open');
                }
            });
            
            // Modal close
            $(document).on('click', '.modal-close, .modal-backdrop', function() {
                $('.modal').removeClass('active');
                $('body').removeClass('modal-open');
            });
            
            // ESC key to close modal
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) {
                    $('.modal').removeClass('active');
                    $('body').removeClass('modal-open');
                }
            });
        },

        /**
         * Initialize accordions
         */
        initAccordions: function() {
            $(document).on('click', '.accordion-header', function() {
                var $header = $(this);
                var $content = $header.next('.accordion-content');
                var $accordion = $header.closest('.accordion');
                
                if ($accordion.hasClass('single-open')) {
                    // Close other accordion items
                    $accordion.find('.accordion-content').not($content).slideUp();
                    $accordion.find('.accordion-header').not($header).removeClass('active');
                }
                
                $header.toggleClass('active');
                $content.slideToggle();
            });
        },

        /**
         * Utility methods
         */
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Debounce function calls
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        if (window.AIOHM_Booking_Base) {
            window.AIOHM_Booking_Base.init();
        }
    });

})(jQuery);