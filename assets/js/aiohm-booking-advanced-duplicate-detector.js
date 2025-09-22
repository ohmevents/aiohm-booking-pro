/**
 * Advanced Duplicate Detection Frontend Interface
 * 
 * @package AIOHM_Booking_PRO
 * @since 1.2.3
 */

jQuery(document).ready(function($) {
    'use strict';

    // Add Advanced Duplicate Detection section to the CSS Manager page
    if ($('.aiohm-booking-admin-wrapper').length && $('#duplicate-check-section').length) {
        addAdvancedDuplicateSection();
    }

    function addAdvancedDuplicateSection() {
        // Add advanced duplicate detection controls to existing section
        const $duplicateSection = $('#duplicate-check-section');
        if ($duplicateSection.length === 0) return;

        // Create advanced controls
        const advancedControlsHtml = `
            <div class="aiohm-card" id="advanced-duplicate-controls">
                <h3>🔍 Advanced Semantic Duplicate Detection</h3>
                <p>Detect architectural duplicates, AJAX conflicts, security issues, and cross-file dependencies that traditional tools miss.</p>
                
                <div class="aiohm-form-grid">
                    <div class="form-group">
                        <label for="detection-severity">Filter by Severity:</label>
                        <select id="detection-severity" class="form-control">
                            <option value="all">All Issues</option>
                            <option value="critical">Critical Only</option>
                            <option value="critical,high">Critical & High</option>
                            <option value="high">High Only</option>
                            <option value="medium">Medium Only</option>
                            <option value="low">Low Only</option>
                            <option value="info">Info Only</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="detection-type">Filter by Type:</label>
                        <select id="detection-type" class="form-control">
                            <option value="all">All Types</option>
                            <option value="ajax">AJAX Conflicts</option>
                            <option value="security">Security Issues</option>
                            <option value="events">Event Binding Conflicts</option>
                            <option value="nonces">Nonce Mismatches</option>
                            <option value="functional">Functional Duplicates</option>
                            <option value="api">API Duplications</option>
                            <option value="dependencies">Cross-file Dependencies</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="scan-path">Scan Specific Path (optional):</label>
                        <input type="text" id="scan-path" class="form-control" placeholder="e.g., includes/modules/ai" />
                        <small class="form-text">Leave empty to scan entire plugin</small>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="button" id="run-advanced-duplicate-check" class="button button-primary">
                        <span class="dashicons dashicons-search"></span>
                        Run Advanced Detection
                    </button>
                    <button type="button" id="export-duplicate-report" class="button button-secondary" disabled>
                        <span class="dashicons dashicons-download"></span>
                        Export Report
                    </button>
                    <button type="button" id="clear-duplicate-results" class="button button-secondary" disabled>
                        <span class="dashicons dashicons-dismiss"></span>
                        Clear Results
                    </button>
                </div>
                
                <div id="advanced-duplicate-progress" class="aiohm-progress-container" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="progress-text">Analyzing codebase for semantic duplicates...</p>
                </div>
            </div>

            <div class="aiohm-card" id="advanced-duplicate-results" style="display: none;">
                <h3>📊 Analysis Results</h3>
                <div id="results-summary" class="results-summary"></div>
                <div id="results-content" class="results-content"></div>
            </div>
        `;

        $duplicateSection.after(advancedControlsHtml);
        
        // Bind events
        bindAdvancedDuplicateEvents();
    }

    function bindAdvancedDuplicateEvents() {
        let currentResults = null;

        // Run advanced duplicate check
        $('#run-advanced-duplicate-check').on('click', function() {
            const $button = $(this);
            const $progress = $('#advanced-duplicate-progress');
            const $results = $('#advanced-duplicate-results');
            
            // Disable button and show progress
            $button.prop('disabled', true);
            $progress.show();
            $results.hide();
            
            // Collect options
            const options = {
                action: 'aiohm_booking_advanced_duplicate_check',
                nonce: aiohm_booking_admin.nonce,
                severity: $('#detection-severity').val(),
                type: $('#detection-type').val(),
                scan_only: $('#scan-path').val().trim() || null,
                format: 'json'
            };

            // Make AJAX call
            $.post(aiohm_booking_admin.ajax_url, options)
                .done(function(response) {
                    if (response.success) {
                        currentResults = response.data;
                        displayResults(response.data);
                        $('#export-duplicate-report, #clear-duplicate-results').prop('disabled', false);
                    } else {
                        showError('Analysis failed: ' + (response.data?.message || 'Unknown error'));
                    }
                })
                .fail(function(xhr, status, error) {
                    showError('Request failed: ' + error);
                })
                .always(function() {
                    $button.prop('disabled', false);
                    $progress.hide();
                });
        });

        // Export report
        $('#export-duplicate-report').on('click', function() {
            if (!currentResults) return;

            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const filename = `aiohm-duplicate-report-${timestamp}.json`;
            
            const dataStr = JSON.stringify(currentResults, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = filename;
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        });

        // Clear results
        $('#clear-duplicate-results').on('click', function() {
            currentResults = null;
            $('#advanced-duplicate-results').hide();
            $('#export-duplicate-report, #clear-duplicate-results').prop('disabled', true);
        });
    }

    function displayResults(data) {
        const $results = $('#advanced-duplicate-results');
        const $summary = $('#results-summary');
        const $content = $('#results-content');
        
        // Build summary
        const stats = data.statistics;
        const summaryHtml = `
            <div class="stats-grid">
                <div class="stat-item ${stats.total_issues === 0 ? 'success' : 'warning'}">
                    <span class="stat-number">${stats.total_issues}</span>
                    <span class="stat-label">Total Issues</span>
                </div>
                <div class="stat-item critical">
                    <span class="stat-number">${stats.by_severity.critical}</span>
                    <span class="stat-label">Critical</span>
                </div>
                <div class="stat-item high">
                    <span class="stat-number">${stats.by_severity.high}</span>
                    <span class="stat-label">High</span>
                </div>
                <div class="stat-item medium">
                    <span class="stat-number">${stats.by_severity.medium}</span>
                    <span class="stat-label">Medium</span>
                </div>
                <div class="stat-item low">
                    <span class="stat-number">${stats.by_severity.low}</span>
                    <span class="stat-label">Low</span>
                </div>
                <div class="stat-item info">
                    <span class="stat-number">${stats.by_severity.info}</span>
                    <span class="stat-label">Info</span>
                </div>
            </div>
            <p class="scan-info">
                Scanned at: ${data.scan_info.timestamp} | 
                Plugin Version: ${data.scan_info.plugin_version}
            </p>
        `;
        
        $summary.html(summaryHtml);
        
        // Build detailed results
        if (data.issues.length === 0) {
            $content.html(`
                <div class="no-issues">
                    <div class="success-icon">✅</div>
                    <h4>No Issues Detected!</h4>
                    <p>Great job! No architectural duplicates or conflicts were found with the current filters.</p>
                </div>
            `);
        } else {
            let contentHtml = '<div class="issues-list">';
            
            // Group by severity
            const bySeverity = groupBy(data.issues, 'severity');
            const severityOrder = ['critical', 'high', 'medium', 'low', 'info'];
            const severityIcons = {
                critical: '🚨',
                high: '⚠️',
                medium: '🔶',
                low: '💡',
                info: 'ℹ️'
            };
            
            severityOrder.forEach(severity => {
                if (!bySeverity[severity] || bySeverity[severity].length === 0) return;
                
                const issues = bySeverity[severity];
                contentHtml += `
                    <div class="severity-group ${severity}">
                        <h4 class="severity-header">
                            ${severityIcons[severity]} ${severity.toUpperCase()} Priority (${issues.length} issues)
                        </h4>
                        <div class="issues-in-group">
                `;
                
                issues.forEach((issue, index) => {
                    const files = extractFileInfo(issue.details);
                    contentHtml += `
                        <div class="issue-item">
                            <h5 class="issue-title">${escapeHtml(issue.title)}</h5>
                            <p class="issue-description">${escapeHtml(issue.description)}</p>
                            <p class="issue-recommendation">
                                <strong>💡 Recommendation:</strong> ${escapeHtml(issue.recommendation)}
                            </p>
                            ${files.length > 0 ? `
                                <div class="issue-files">
                                    <strong>📁 Files:</strong> ${files.map(f => `<code>${escapeHtml(f)}</code>`).join(', ')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                contentHtml += `
                        </div>
                    </div>
                `;
            });
            
            contentHtml += '</div>';
            $content.html(contentHtml);
        }
        
        $results.show();
    }

    function extractFileInfo(details) {
        const files = [];
        
        if (details.file) {
            files.push(`${details.file}:${details.line || '?'}`);
        } else if (Array.isArray(details)) {
            details.forEach(detail => {
                if (detail && typeof detail === 'object' && detail.file) {
                    files.push(`${detail.file}:${detail.line || '?'}`);
                }
            });
        }
        
        return [...new Set(files)]; // Remove duplicates
    }

    function groupBy(array, key) {
        return array.reduce((groups, item) => {
            const group = item[key];
            groups[group] = groups[group] || [];
            groups[group].push(item);
            return groups;
        }, {});
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showError(message) {
        const $results = $('#advanced-duplicate-results');
        const $content = $('#results-content');
        
        $content.html(`
            <div class="error-message">
                <div class="error-icon">❌</div>
                <h4>Analysis Error</h4>
                <p>${escapeHtml(message)}</p>
            </div>
        `);
        
        $('#results-summary').html('');
        $results.show();
    }

    // Add CSS styles
    const styles = `
        <style>
        .aiohm-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .button-group {
            margin: 20px 0;
        }
        
        .button-group .button {
            margin-right: 10px;
        }
        
        .aiohm-progress-container {
            margin: 20px 0;
        }
        
        .progress-bar {
            background: #f0f0f0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            background: linear-gradient(45deg, #0073aa, #00a0d2);
            height: 100%;
            width: 0%;
            animation: progressPulse 2s ease-in-out infinite;
        }
        
        @keyframes progressPulse {
            0% { width: 10%; }
            50% { width: 90%; }
            100% { width: 10%; }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .stat-item.success { background: #d4edda; border: 1px solid #c3e6cb; }
        .stat-item.warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        .stat-item.critical { background: #f8d7da; border: 1px solid #f5c6cb; }
        .stat-item.high { background: #fff3cd; border: 1px solid #ffeaa7; }
        .stat-item.medium { background: #d1ecf1; border: 1px solid #bee5eb; }
        .stat-item.low { background: #d4edda; border: 1px solid #c3e6cb; }
        .stat-item.info { background: #e2e3e5; border: 1px solid #d6d8db; }
        
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }
        
        .severity-group {
            margin: 25px 0;
            border-left: 4px solid #ccc;
            padding-left: 20px;
        }
        
        .severity-group.critical { border-left-color: #dc3545; }
        .severity-group.high { border-left-color: #fd7e14; }
        .severity-group.medium { border-left-color: #0d6efd; }
        .severity-group.low { border-left-color: #198754; }
        .severity-group.info { border-left-color: #6c757d; }
        
        .severity-header {
            margin: 0 0 15px 0;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .issue-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .issue-title {
            margin: 0 0 10px 0;
            color: #495057;
        }
        
        .issue-description {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .issue-recommendation {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }
        
        .issue-files {
            margin-top: 10px;
            font-size: 13px;
        }
        
        .issue-files code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 8px;
            font-size: 12px;
        }
        
        .no-issues, .error-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon, .error-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .scan-info {
            color: #6c757d;
            font-size: 13px;
            text-align: center;
            margin-top: 15px;
        }
        </style>
    `;
    
    $('head').append(styles);
});