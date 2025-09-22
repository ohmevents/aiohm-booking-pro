/**
 * AIOHM Booking Settings Template Scripts
 * Handles settings page-specific JavaScript functionality
 *
 * @package AIOHM_Booking
 * @version 1.2.3
 */

(function($) {
    'use strict';

    /**
     * Settings Template Functionality
     */
    var AIOHM_Booking_Settings_Template = {

        init: function() {
            this.initPage();
            this.bindEvents();
        },

        /**
         * Initialize settings page
         */
        initPage: function() {
            // Settings admin initializes itself from external JS file
            this.initSortableFunctionality();
        },

        /**
         * Initialize sortable functionality
         */
        initSortableFunctionality: function() {
            // Check if jQuery UI sortable is already loaded
            if (typeof $.fn.sortable !== 'undefined') {
                this.initializeAllSortables();
            } else {
                this.loadJQueryUI();
            }
        },

        /**
         * Load jQuery UI if not available
         */
        loadJQueryUI: function() {
            // Load jQuery UI immediately
            var script = document.createElement('script');
            script.src = 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js';
            script.onload = function() {
                if (typeof $.fn.sortable !== 'undefined') {
                    AIOHM_Booking_Settings_Template.initializeAllSortables();
                }
            };
            script.onerror = function(error) {
                // Try alternative approach or fallback
            };
            document.head.appendChild(script);
        },

        /**
         * Initialize all sortable elements
         */
        initializeAllSortables: function() {
            // Implementation for sortable initialization
            
            // Primary modules sortable
            if ($('#sortable-primary-grid').length > 0) {
                $('#sortable-primary-grid').sortable({
                    handle: '.aiohm-module-icon',
                    placeholder: 'aiohm-sort-placeholder',
                    update: this.handleSortUpdate,
                    connectWith: '.aiohm-sortable',
                    tolerance: 'pointer',
                    cursor: 'grabbing',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    stop: function(event, ui) {
                        // Sorting stopped
                    }
                }).disableSelection();
            }
            
            // Supporting modules sortable
            if ($('#sortable-supporting-grid').length > 0) {
                $('#sortable-supporting-grid').sortable({
                    handle: '.aiohm-module-icon',
                    placeholder: 'aiohm-sort-placeholder',
                    update: this.handleSortUpdate,
                    connectWith: '.aiohm-sortable',
                    tolerance: 'pointer',
                    cursor: 'grabbing',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    }
                }).disableSelection();
            }
            
            // AI modules sortable
            if ($('#sortable-ai-grid').length > 0) {
                $('#sortable-ai-grid').sortable({
                    handle: '.aiohm-module-icon',
                    placeholder: 'aiohm-sort-placeholder',
                    update: this.handleSortUpdate,
                    connectWith: '.aiohm-sortable',
                    tolerance: 'pointer',
                    cursor: 'grabbing',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    }
                }).disableSelection();
            }
            
            // PRO modules sortable
            if ($('#sortable-pro-module-grid').length > 0) {
                $('#sortable-pro-module-grid').sortable({
                    handle: '.aiohm-module-icon',
                    placeholder: 'aiohm-sort-placeholder',
                    update: this.handleSortUpdate,
                    connectWith: '.aiohm-sortable',
                    tolerance: 'pointer',
                    cursor: 'grabbing',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    }
                }).disableSelection();
            }
            
            // Tools modules sortable
            if ($('#sortable-tools-grid').length > 0) {
                $('#sortable-tools-grid').sortable({
                    handle: '.aiohm-module-icon',
                    placeholder: 'aiohm-sort-placeholder',
                    update: this.handleSortUpdate,
                    connectWith: '.aiohm-sortable',
                    tolerance: 'pointer',
                    cursor: 'grabbing',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    }
                }).disableSelection();
            }
        },

        /**
         * Handle sort update
         */
        handleSortUpdate: function(event, ui) {
            var $sortable = $(this);
            var order = $sortable.sortable('toArray', {attribute: 'data-id'});
            var action = $sortable.data('sort-action') || 'aiohm_save_module_order';
            
            // Update the hidden input with the new order if it exists
            if ($('#module_order_input').length) {
                $('#module_order_input').val(order.join(','));
            }
            
            // Save order via AJAX
            if (typeof ajaxurl !== 'undefined') {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: action,
                        order: order,
                        nonce: (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) || ''
                    },
                    success: function(response) {
                        // Order saved successfully
                    },
                    error: function(xhr, status, error) {
                        // Failed to save order
                    }
                });
            }
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            this.bindCopyButtons();
            this.bindPasswordVisibility();
            // Note: Test connections are handled by aiohm-booking-ai-providers-admin.js to avoid duplicates
            this.bindConfigureButtons();
            this.bindFacebookCopyButtons();
        },

        /**
         * Bind configure button events
         */
        bindConfigureButtons: function() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.aiohm-configure-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.aiohm-configure-btn');
                    const target = button.getAttribute('data-target');
                    
                    if (target) {
                        const targetElement = document.querySelector(target);
                        if (targetElement) {
                            // Scroll to target with smooth animation
                            targetElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                            
                            // Add highlight effect
                            targetElement.classList.add('aiohm-highlight-section');
                            setTimeout(function() {
                                targetElement.classList.remove('aiohm-highlight-section');
                            }, 3000);
                        }
                    }
                }
            });
        },

        /**
         * Bind Facebook copy URL buttons
         */
        bindFacebookCopyButtons: function() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.aiohm-copy-url-btn')) {
                    e.preventDefault();
                    const button = e.target.closest('.aiohm-copy-url-btn');
                    const url = button.getAttribute('data-url');
                    const originalText = button.innerHTML;
                    
                    if (url) {
                        // Copy to clipboard
                        navigator.clipboard.writeText(url).then(function() {
                            // Show success feedback
                            button.innerHTML = '✅';
                            button.style.color = '#46b450';
                            
                            // Reset after 2 seconds
                            setTimeout(function() {
                                button.innerHTML = originalText;
                                button.style.color = '';
                            }, 2000);
                        }).catch(function(err) {
                            // Show error feedback
                            button.innerHTML = '❌';
                            button.style.color = '#dc3232';
                            
                            // Reset after 2 seconds
                            setTimeout(function() {
                                button.innerHTML = originalText;
                                button.style.color = '';
                            }, 2000);
                        });
                    }
                }
            });
        },

        /**
         * Bind API test connection events
         */
        bindAPITestConnections: function() {
            // Test OpenAI connection
            const testButton = document.getElementById('test-openai-connection');
            if (testButton) {
                testButton.addEventListener('click', function() {
                    AIOHM_Booking_Settings_Template.testOpenAIConnection(this);
                });
            }

            // Test Google Gemini connection
            const testGeminiButton = document.getElementById('test-gemini-connection');
            if (testGeminiButton) {
                testGeminiButton.addEventListener('click', function() {
                    AIOHM_Booking_Settings_Template.testGeminiConnection(this);
                });
            }

            // Payment module test connections are now handled by their respective modules
            // Payment modules handle their own test connections
        },

        /**
         * Test OpenAI API connection
         */
        testOpenAIConnection: function(button) {
            const originalText = button.innerHTML;
            const apiKeyElement = document.getElementById('openai_api_key');
            const modelElement = document.querySelector('input[name="aiohm_booking_settings[openai_model]"]');

            if (!apiKeyElement || !modelElement) {
                alert('OpenAI form elements not found.');
                return;
            }

            const apiKey = apiKeyElement.value;
            const model = modelElement.value;

            if (!apiKey) {
                alert('Please enter your OpenAI API key first.');
                return;
            }

            // Show loading state
            button.innerHTML = '<span class="dashicons dashicons-update spin"></span> Testing...';
            button.disabled = true;

            // Clear any previous test results
            const existingResult = document.getElementById('openai-test-result');
            if (existingResult) {
                existingResult.remove();
            }

            // Make actual API call to OpenAI
            fetch('https://api.openai.com/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + apiKey
                },
                body: JSON.stringify({
                    model: model || 'gpt-3.5-turbo',
                    messages: [{ role: 'user', content: 'Hello, this is a test message.' }],
                    max_tokens: 10
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error?.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                // Success
                button.innerHTML = '<span class="dashicons dashicons-yes"></span> Connected!';
                button.style.backgroundColor = '#46b450';

                // Show success message
                AIOHM_Booking_Settings_Template.showTestResult('openai', 'success', 'OpenAI API connection successful! Model: ' + (data.model || model));

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 3000);
            })
            .catch(error => {
                // Error
                button.innerHTML = '<span class="dashicons dashicons-no"></span> Failed';
                button.style.backgroundColor = '#dc3232';

                // Show error message
                AIOHM_Booking_Settings_Template.showTestResult('openai', 'error', 'Connection failed: ' + error.message);

                // Reset button after 5 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 5000);
            });
        },

        /**
         * Test Google Gemini API connection
         */
        testGeminiConnection: function(button) {
            const originalText = button.innerHTML;
            const apiKeyElement = document.getElementById('gemini_api_key');
            const modelElement = document.querySelector('input[name="aiohm_booking_settings[gemini_model]"]');

            if (!apiKeyElement || !modelElement) {
                alert('Gemini form elements not found.');
                return;
            }

            const apiKey = apiKeyElement.value;
            const model = modelElement.value;

            if (!apiKey) {
                alert('Please enter your Google Gemini API key first.');
                return;
            }

            // Show loading state
            button.innerHTML = '<span class="dashicons dashicons-update spin"></span> Testing...';
            button.disabled = true;

            // Clear any previous test results
            const existingResult = document.getElementById('gemini-test-result');
            if (existingResult) {
                existingResult.remove();
            }

            // Make actual API call to Google Gemini
            fetch(`https://generativelanguage.googleapis.com/v1beta/models/${model || 'gemini-1.5-flash'}:generateContent?key=${apiKey}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contents: [{
                        parts: [{
                            text: 'Hello, this is a test message.'
                        }]
                    }],
                    generationConfig: {
                        maxOutputTokens: 10
                    }
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error?.message || `HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                // Success
                button.innerHTML = '<span class="dashicons dashicons-yes"></span> Connected!';
                button.style.backgroundColor = '#46b450';

                // Show success message
                AIOHM_Booking_Settings_Template.showTestResult('gemini', 'success', 'Google Gemini API connection successful! Model: ' + (model || 'gemini-1.5-flash'));

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 3000);
            })
            .catch(error => {
                // Error
                button.innerHTML = '<span class="dashicons dashicons-no"></span> Failed';
                button.style.backgroundColor = '#dc3232';

                // Show error message
                AIOHM_Booking_Settings_Template.showTestResult('gemini', 'error', 'Connection failed: ' + error.message);

                // Reset button after 5 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                    button.disabled = false;
                }, 5000);
            });
        },


        /**
         * Show test result for API connections
         */
        showTestResult: function(provider, type, message) {
            const buttonGroup = document.querySelector(`#test-${provider}-connection`).parentElement;

            // Remove existing result if any
            const existingResult = document.getElementById(`${provider}-test-result`);
            if (existingResult) {
                existingResult.remove();
            }

            // Create result element
            const resultDiv = document.createElement('div');
            resultDiv.id = `${provider}-test-result`;
            resultDiv.className = type === 'success' ? 'aiohm-alert aiohm-alert--success' : 'aiohm-alert aiohm-alert--error';
            resultDiv.innerHTML = '<p><strong>' + (type === 'success' ? 'Success:' : 'Error:') + '</strong> ' + message + '</p>';

            // Insert after button group
            buttonGroup.parentElement.insertBefore(resultDiv, buttonGroup.nextSibling);

            // Auto-remove after 10 seconds
            setTimeout(() => {
                if (resultDiv.parentElement) {
                    resultDiv.remove();
                }
            }, 10000);
        },

        /**
         * Bind copy button events
         */
        bindCopyButtons: function() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.aiohm-copy-btn')) {
                    const button = e.target.closest('.aiohm-copy-btn');
                    const shortcode = button.getAttribute('data-shortcode');
                    const originalIcon = button.innerHTML;

                    // Copy to clipboard
                    navigator.clipboard.writeText(shortcode).then(function() {
                        // Show success feedback
                        button.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                        button.style.color = '#46b450';

                        // Reset after 2 seconds
                        setTimeout(function() {
                            button.innerHTML = originalIcon;
                            button.style.color = '';
                        }, 2000);
                    }).catch(function(err) {
                        // Show error feedback
                        button.innerHTML = '<span class="dashicons dashicons-no"></span>';
                        button.style.color = '#dc3232';

                        // Reset after 2 seconds
                        setTimeout(function() {
                            button.innerHTML = originalIcon;
                            button.style.color = '';
                        }, 2000);
                    });
                }

                if (e.target.closest('.aiohm-input-copy')) {
                    const button = e.target.closest('.aiohm-input-copy');
                    const inputId = button.getAttribute('data-target');
                    const input = document.getElementById(inputId);
                    const originalIcon = button.innerHTML;

                    if (input) {
                        // Copy to clipboard
                        navigator.clipboard.writeText(input.value).then(function() {
                            // Show success feedback
                            button.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                            button.style.color = '#46b450';
                            button.style.backgroundColor = 'rgba(70, 180, 80, 0.1)';

                            // Reset after 2 seconds
                            setTimeout(function() {
                                button.innerHTML = originalIcon;
                                button.style.color = '';
                                button.style.backgroundColor = '';
                            }, 2000);
                        }).catch(function(err) {
                            // Show error feedback
                            button.innerHTML = '<span class="dashicons dashicons-no"></span>';
                            button.style.color = '#dc3232';
                            button.style.backgroundColor = 'rgba(220, 50, 50, 0.1)';

                            // Reset after 2 seconds
                            setTimeout(function() {
                                button.innerHTML = originalIcon;
                                button.style.color = '';
                                button.style.backgroundColor = '';
                            }, 2000);
                        });
                    }
                }
            });
        },

        /**
         * Bind password visibility toggle
         */
        bindPasswordVisibility: function() {
            // Bind click events to password toggle buttons
            document.addEventListener('click', function(e) {
                if (e.target.closest('[data-action="toggle-password"]')) {
                    e.preventDefault();
                    const button = e.target.closest('[data-action="toggle-password"]');
                    const targetId = button.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = button.querySelector('.dashicons');

                    if (input && icon) {
                        if (input.type === 'password') {
                            input.type = 'text';
                            icon.className = 'dashicons dashicons-hidden';
                        } else {
                            input.type = 'password';
                            icon.className = 'dashicons dashicons-visibility';
                        }
                    }
                }
            });

            // Legacy function for backward compatibility
            window.togglePasswordVisibility = function(inputId) {
                const input = document.getElementById(inputId);
                const button = input.nextElementSibling;
                const icon = button.querySelector('.dashicons');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'dashicons dashicons-hidden';
                } else {
                    input.type = 'password';
                    icon.className = 'dashicons dashicons-visibility';
                }
            };

            // Global copy function for template compatibility
            window.copyToClipboard = function(inputId) {
                const input = document.getElementById(inputId);
                const button = event.target.closest('.aiohm-input-copy');
                const originalIcon = button.innerHTML;

                // Copy to clipboard
                navigator.clipboard.writeText(input.value).then(function() {
                    // Show success feedback
                    button.innerHTML = '<span class="dashicons dashicons-yes"></span>';
                    button.style.color = '#46b450';
                    button.style.backgroundColor = 'rgba(70, 180, 80, 0.1)';

                    // Reset after 2 seconds
                    setTimeout(function() {
                        button.innerHTML = originalIcon;
                        button.style.color = '';
                        button.style.backgroundColor = '';
                    }, 2000);
                }).catch(function(err) {
                    // Show error feedback
                    button.innerHTML = '<span class="dashicons dashicons-no"></span>';
                    button.style.color = '#dc3232';
                    button.style.backgroundColor = 'rgba(220, 50, 50, 0.1)';

                    // Reset after 2 seconds
                    setTimeout(function() {
                        button.innerHTML = originalIcon;
                        button.style.color = '';
                        button.style.backgroundColor = '';
                    }, 2000);
                });
            };
        },

        /**
         * Bind test connection buttons
         */
        bindTestConnections: function() {
            // Test OpenAI connection
            const testButton = document.getElementById('test-openai-connection');
            if (testButton) {
                testButton.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    const apiKeyElement = document.getElementById('openai_api_key');
                    const modelElement = document.querySelector('input[name="aiohm_booking_settings[openai_model]"]');

                    if (!apiKeyElement || !modelElement) {
                        alert('OpenAI form elements not found.');
                        return;
                    }

                    const apiKey = apiKeyElement.value;
                    const model = modelElement.value;

                    if (!apiKey) {
                        alert('Please enter an OpenAI API key first.');
                        return;
                    }

                    // Update button text
                    this.innerHTML = '<span class="dashicons dashicons-update spin"></span> Testing...';
                    this.disabled = true;

                    // Make AJAX request to test connection
                    fetch(aiohm_booking_settings.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'aiohm_booking_test_openai_connection',
                            nonce: aiohm_booking_settings.nonce,
                            api_key: apiKey,
                            model: model
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Connection successful! ' + (data.data.message || ''));
                        } else {
                            alert('❌ Connection failed: ' + (data.data || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        alert('❌ Connection test failed. Please try again.');
                    })
                    .finally(() => {
                        // Reset button
                        this.innerHTML = originalText;
                        this.disabled = false;
                    });
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        AIOHM_Booking_Settings_Template.init();
    });

})(jQuery);
