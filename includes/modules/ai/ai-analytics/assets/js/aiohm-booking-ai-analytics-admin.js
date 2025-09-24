/**
 * AIOHM Booking AI Analytics Admin JavaScript
 * Handles AI analytics and insights functionality
 * 
 * @package AIOHM_Booking
 * @version 1.1.2
 */

(function($) {
    'use strict';

    // AI Analytics admin object
    window.AIOHM_Booking_AI_Analytics = {
        
        init: function() {
            this.bindEvents();
            this.bindAIQueryEvents();
            this.bindOrdersAIQueryEvents();
            this.initAnalytics();
        },

        bindEvents: function() {
            // AI Analytics event handlers
            $(document).on('click', '.aiohm-refresh-analytics', this.refreshAnalytics);
            $(document).on('change', '.aiohm-analytics-period', this.handlePeriodChange);
            $(document).on('click', '.aiohm-export-analytics', this.exportAnalytics);
        },

        initAnalytics: function() {
            // Initialize analytics dashboard
            this.loadAnalyticsData();
            this.setupCharts();
        },

        loadAnalyticsData: function() {
            var $container = $('.aiohm-analytics-container');
            if ($container.length === 0) return;
            
            var period = $('.aiohm-analytics-period').val() || '30';
            
            $container.addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiohm_get_analytics_data',
                    period: period,
                    nonce: (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) || ''
                },
                success: function(response) {
                    if (response.success && response.data) {
                        AIOHM_Booking_AI_Analytics.renderAnalytics(response.data);
                    } else {
                        AIOHM_Booking_AI_Analytics.showError('Failed to load analytics data');
                    }
                },
                error: function() {
                    AIOHM_Booking_AI_Analytics.showError('Error loading analytics data');
                },
                complete: function() {
                    $container.removeClass('loading');
                }
            });
        },

        renderAnalytics: function(data) {
            // Render analytics data
            if (data.stats) {
                this.renderStats(data.stats);
            }
            
            if (data.charts) {
                this.renderCharts(data.charts);
            }
            
            if (data.insights) {
                this.renderInsights(data.insights);
            }
        },

        renderStats: function(stats) {
            // Render statistics cards
            var $statsContainer = $('.aiohm-analytics-stats');
            
            $.each(stats, function(key, value) {
                var $stat = $statsContainer.find('[data-stat="' + key + '"]');
                if ($stat.length > 0) {
                    $stat.find('.stat-value').text(value.formatted || value);
                    $stat.find('.stat-trend').text(value.trend || '');
                    
                    if (value.trend_direction) {
                        $stat.addClass('trend-' + value.trend_direction);
                    }
                }
            });
        },

        renderCharts: function(charts) {
            // Render charts if chart library is available
            $.each(charts, function(chartId, chartData) {
                var $chart = $('#' + chartId);
                if ($chart.length > 0) {
                    AIOHM_Booking_AI_Analytics.createChart($chart, chartData);
                }
            });
        },

        createChart: function($container, data) {
            // Basic chart creation - placeholder for actual chart library
            var chartHtml = '<div class="aiohm-simple-chart">';
            
            if (data.type === 'line') {
                chartHtml += '<div class="chart-title">' + (data.title || '') + '</div>';
                chartHtml += '<div class="chart-data">';
                
                $.each(data.data || [], function(index, point) {
                    var height = Math.min(100, (point.value / data.max) * 100);
                    chartHtml += '<div class="chart-bar" style="height: ' + height + '%">';
                    chartHtml += '<span class="bar-label">' + point.label + '</span>';
                    chartHtml += '<span class="bar-value">' + point.value + '</span>';
                    chartHtml += '</div>';
                });
                
                chartHtml += '</div>';
            } else {
                chartHtml += '<p>Chart data: ' + JSON.stringify(data) + '</p>';
            }
            
            chartHtml += '</div>';
            
            $container.html(chartHtml);
        },

        renderInsights: function(insights) {
            // Render AI insights
            var $insightsContainer = $('.aiohm-analytics-insights');
            
            if (insights.length > 0) {
                var insightsHtml = '<div class="insights-list">';
                
                $.each(insights, function(index, insight) {
                    insightsHtml += '<div class="insight-item ' + (insight.type || '') + '">';
                    insightsHtml += '<div class="insight-icon">' + (insight.icon || '💡') + '</div>';
                    insightsHtml += '<div class="insight-content">';
                    insightsHtml += '<h4>' + insight.title + '</h4>';
                    insightsHtml += '<p>' + insight.description + '</p>';
                    if (insight.action) {
                        insightsHtml += '<a href="' + insight.action.url + '" class="insight-action">' + insight.action.text + '</a>';
                    }
                    insightsHtml += '</div>';
                    insightsHtml += '</div>';
                });
                
                insightsHtml += '</div>';
                $insightsContainer.html(insightsHtml);
            } else {
                $insightsContainer.html('<p>No insights available for this period.</p>');
            }
        },

        setupCharts: function() {
            // Setup chart containers
            $('.aiohm-chart-container').each(function() {
                var $chart = $(this);
                var chartType = $chart.data('chart-type') || 'line';
                var chartData = $chart.data('chart-data') || {};
                
                if (Object.keys(chartData).length > 0) {
                    AIOHM_Booking_AI_Analytics.createChart($chart, {
                        type: chartType,
                        data: chartData
                    });
                }
            });
        },

        refreshAnalytics: function(e) {
            e.preventDefault();
            AIOHM_Booking_AI_Analytics.loadAnalyticsData();
        },

        handlePeriodChange: function() {
            var $select = $(this);
            var newPeriod = $select.val();
            
            // Reload analytics with new period
            AIOHM_Booking_AI_Analytics.loadAnalyticsData();
        },

        exportAnalytics: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var format = $btn.data('format') || 'csv';
            var period = $('.aiohm-analytics-period').val() || '30';
            
            // Create download URL
            var url = ajaxurl + '?action=aiohm_export_analytics&format=' + format + '&period=' + period;
            if (window.aiohm_booking_admin && window.aiohm_booking_admin.nonce) {
                url += '&nonce=' + window.aiohm_booking_admin.nonce;
            }
            
            // Trigger download
            window.location.href = url;
        },

        showError: function(message) {
            var $error = $('<div class="aiohm-analytics-error">' + message + '</div>');
            $('.aiohm-analytics-container').prepend($error);
            
            setTimeout(function() {
                $error.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Utility methods
        utils: {
            formatNumber: function(num) {
                if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + 'M';
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + 'K';
                }
                return num.toString();
            },
            
            formatPercentage: function(value, total) {
                if (total === 0) return '0%';
                return ((value / total) * 100).toFixed(1) + '%';
            }
        },

        // AI Query functionality for calendar page
        bindAIQueryEvents: function() {
            $(document).on('click', '#submit-ai-table-query', this.handleAIQuery.bind(this));
            $(document).on('click', '.aiohm-example-btn', this.handleExampleQuery.bind(this));
            $(document).on('click', '#copy-ai-response', this.copyAIResponse.bind(this));
            $(document).on('click', '#clear-ai-response', this.clearAIResponse.bind(this));
        },

        handleAIQuery: function(e) {
            e.preventDefault();
            const query = $('#ai-table-query-input').val().trim();

            if (!query) {
                this.showNotification('Please enter a question first.', 'warning');
                return;
            }

            // Show loading state
            $('#ai-query-loading').removeClass('aiohm-hidden').show();
            $('#submit-ai-table-query').prop('disabled', true).text('Analyzing...');
            $('#ai-table-response-area').hide();

            // AJAX call to the backend
            $.ajax({
                url: aiohm_ai_analytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_ai_query',
                    query: query,
                    context: 'calendar',
                    nonce: aiohm_ai_analytics.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showAIResponse(query, response.data.response);
                    } else {
                        this.showAIResponse(query, 'Error: ' + (response.data || 'Could not process query.'));
                    }
                },
                error: (xhr, status, error) => {
                    this.showAIResponse(query, 'Error: Could not connect to the server. ' + error);
                },
                complete: () => {
                    $('#submit-ai-table-query').prop('disabled', false).text('Ask');
                    $('#ai-query-loading').addClass('aiohm-hidden').hide();
                }
            });
        },

        handleExampleQuery: function(e) {
            e.preventDefault();
            const query = $(e.currentTarget).data('query');
            $('#ai-table-query-input').val(query);
        },

        showAIResponse: function(query, response) {
            $('#ai-query-loading').addClass('aiohm-hidden').hide();
            $('#ai-response-text').html(`<strong>Question:</strong> ${query}<br><br><strong>Answer:</strong> ${response}`);
            $('#ai-table-response-area').removeClass('aiohm-hidden').show();
        },

        copyAIResponse: function(e) {
            e.preventDefault();
            const responseText = $('#ai-response-text').text();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(responseText).then(() => {
                    this.showNotification('Response copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = responseText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.showNotification('Response copied to clipboard!', 'success');
            }
        },

        clearAIResponse: function(e) {
            e.preventDefault();
            $('#ai-table-response-area').hide();
            $('#ai-table-query-input').val('');
        },

        showNotification: function(message, type) {
            // Simple notification - could be enhanced
            alert(message);
        },

        // Orders AI functionality
        bindOrdersAIQueryEvents: function() {
            $(document).on('click', '.aiohm-example-btn', this.handleOrdersExampleQuery.bind(this));
            $(document).on('click', '#aiohm-orders-ai-submit', this.handleOrdersAIQuery.bind(this));
            $(document).on('click', '#aiohm-orders-copy-response', this.copyOrdersAIResponse.bind(this));
            $(document).on('click', '#aiohm-orders-clear-response', this.clearOrdersAIResponse.bind(this));
        },

        handleOrdersExampleQuery: function(e) {
            e.preventDefault();
            const query = $(e.currentTarget).data('query');
            const $textarea = $('#aiohm-orders-ai-query');
            $textarea.val(query);
            // Use setTimeout to avoid jQuery migrate warning with focus()
            setTimeout(function() {
                $textarea[0].focus();
            }, 10);
        },

        handleOrdersAIQuery: function(e) {
            e.preventDefault();
            const query = $('#aiohm-orders-ai-query').val().trim();

            if (!query) {
                this.showNotification('Please enter a question first.', 'warning');
                return;
            }

            // Show loading state
            $('#aiohm-orders-ai-loading').removeClass('aiohm-hidden').show();
            $('#aiohm-orders-ai-submit').prop('disabled', true).text('Analyzing...');
            $('#aiohm-orders-ai-results').hide();

            // AJAX call to the backend
            $.ajax({
                url: aiohm_ai_analytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiohm_booking_ai_query',
                    query: query,
                    context: 'orders',
                    nonce: aiohm_ai_analytics.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showOrdersAIResponse(query, response.data.response);
                    } else {
                        this.showOrdersAIResponse(query, 'Error: ' + (response.data || 'Could not process query.'));
                    }
                },
                error: (xhr, status, error) => {
                    this.showOrdersAIResponse(query, 'Error: Could not connect to the server. ' + error);
                },
                complete: () => {
                    $('#aiohm-orders-ai-submit').prop('disabled', false).text('Ask AI');
                    $('#aiohm-orders-ai-loading').addClass('aiohm-hidden').hide();
                }
            });
        },

        showOrdersAIResponse: function(query, response) {
            $('#aiohm-orders-ai-loading').addClass('aiohm-hidden').hide();
            $('#aiohm-orders-ai-response').html('<strong>Question:</strong> ' + query + '<br><br><strong>Answer:</strong> ' + response);
            $('#aiohm-orders-ai-results').removeClass('aiohm-hidden').show();
        },

        copyOrdersAIResponse: function(e) {
            e.preventDefault();
            const responseText = $('#aiohm-orders-ai-response').text();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(responseText).then(() => {
                    this.showNotification('Response copied to clipboard!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = responseText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.showNotification('Response copied to clipboard!', 'success');
            }
        },

        clearOrdersAIResponse: function(e) {
            e.preventDefault();
            $('#aiohm-orders-ai-results').hide();
            const $textarea = $('#aiohm-orders-ai-query');
            $textarea.val('');
            // Use setTimeout to avoid jQuery migrate warning with focus()
            setTimeout(function() {
                $textarea[0].focus();
            }, 10);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on an analytics admin page or orders page
        if ($('.aiohm-analytics-container').length > 0 || $('#aiohm-orders-ai-query').length > 0) {
            AIOHM_Booking_AI_Analytics.init();
        }
    });

})(jQuery);