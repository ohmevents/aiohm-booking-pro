<?php
/**
 * AIOHM Booking Notifications Template
 * This template provides notification management within the modular system
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Note: All variables used in this template are prepared by the AIOHM_Booking_PROModulesNotificationsAIOHM_Booking_PROModulesNotificationsAIOHM_Booking_PROModulesNotificationsAIOHM_BOOKING_Module_Notifications class.

?>
<div class="wrap aiohm-booking-admin">
	<div class="aiohm-booking-admin-header">
	<div class="aiohm-booking-admin-header-content">
		<div class="aiohm-booking-admin-logo">
		<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
		</div>
		<div class="aiohm-booking-admin-header-text">
		<h1>Notification Module Management</h1>
		<p class="aiohm-booking-admin-tagline">Configure SMTP settings and manage email templates for user and admin notifications.</p>
		</div>
	</div>
	</div>

	<div class="aiohm-booking-notifications-layout">
	<!-- Left Column - SMTP Settings -->
	<div class="aiohm-booking-notifications-left">
		<form id="aiohm-notification-settings-form" method="post" action="">
		<?php wp_nonce_field( 'aiohm_save_notification_settings', 'aiohm_notification_settings_nonce' ); ?>

		<div class="aiohm-booking-notifications-card" id="smtp-configuration-section">
			<h3>SMTP Configuration</h3>
			<p>Configure your SMTP server settings to send email notifications.</p>

			<div class="aiohm-booking-notifications-smtp-settings">
			<div class="aiohm-booking-notifications-smtp-fields">
				<div class="aiohm-booking-notifications-setting-row">
				<label>Email Provider</label>
				<select name="settings[email_provider]" class="aiohm-booking-notifications-provider-select">
					<option value="wordpress" <?php selected( $email_provider, 'WordPress' ); ?>>WordPress Native</option>
					<option value="smtp" <?php selected( $email_provider, 'smtp' ); ?>>Custom SMTP</option>
				</select>
				<small>Choose your email delivery method</small>
				</div>

				<div class="aiohm-booking-notifications-setting-row">
				<label>SMTP Host</label>
				<input type="text" name="settings[smtp_host]" value="<?php echo esc_attr( $smtp_host ); ?>" placeholder="smtp.gmail.com">
				<small>Your SMTP server hostname</small>
				</div>

				<div class="aiohm-booking-notifications-setting-row aiohm-booking-notifications-row-split">
				<div class="aiohm-booking-notifications-setting-col">
					<label>Port</label>
					<input type="number" name="settings[smtp_port]" value="<?php echo esc_attr( $smtp_port ); ?>" placeholder="587">
					<small>Usually 587 (TLS) or 465 (SSL)</small>
				</div>
				<div class="aiohm-booking-notifications-setting-col">
					<label>Encryption</label>
					<select name="settings[smtp_encryption]" class="aiohm-booking-notifications-provider-select">
					<option value="tls" <?php selected( $smtp_encryption, 'tls' ); ?>>TLS</option>
					<option value="ssl" <?php selected( $smtp_encryption, 'ssl' ); ?>>SSL</option>
					<option value="none" <?php selected( $smtp_encryption, 'none' ); ?>>None</option>
					</select>
				</div>
				</div>

				<div class="aiohm-booking-notifications-setting-row">
				<label>Username</label>
				<input type="text" name="settings[smtp_username]" value="<?php echo esc_attr( $smtp_username ); ?>" placeholder="your-email@gmail.com">
				<small>Your SMTP username (usually your email)</small>
				</div>

				<div class="aiohm-booking-notifications-setting-row">
				<label>Password</label>
				<input type="password" name="settings[smtp_password]" value="" placeholder="Enter SMTP password">
				<small>Your SMTP password or app password</small>
				</div>

				<div class="aiohm-booking-notifications-setting-row aiohm-booking-notifications-row-split">
				<div class="aiohm-booking-notifications-setting-col">
					<label>From Email</label>
					<input type="email" name="settings[from_email]" value="<?php echo esc_attr( $from_email ); ?>" placeholder="noreply@yoursite.com">
					<small>Email address to send from</small>
				</div>
				<div class="aiohm-booking-notifications-setting-col">
					<label>From Name</label>
					<input type="text" name="settings[from_name]" value="<?php echo esc_attr( $from_name ); ?>" placeholder="Your Hotel Name">
					<small>Display name for emails</small>
				</div>
				</div>

				<div class="aiohm-booking-notifications-smtp-test">
				<div class="aiohm-booking-notifications-button-group">
					<button type="button" class="button-secondary aiohm-booking-notifications-test-smtp-btn">Test WordPress Mail</button>
					<button type="submit" class="button-primary aiohm-booking-notifications-save-btn" name="save_notification_settings">Save SMTP Settings</button>
				</div>
				<div class="aiohm-booking-notifications-test-result"></div>
				</div>
			</div>
			</div>
		</div>

		<!-- Email Preview Card -->
		<div class="aiohm-booking-notifications-card hidden" id="aiohm-email-preview-card">
			<div class="aiohm-booking-notifications-email-preview-header">
			<h3>📧 Email Preview</h3>
			<div class="aiohm-booking-notifications-email-preview-actions">
				<button type="button" class="button-secondary " id="refresh-preview" title="Update preview with current template content">
				🔄 Update
				</button>
				<button type="button" class="button " id="close-preview" title="Close email preview">
				✕
				</button>
			</div>
			</div>
		  
			<div class="aiohm-booking-notifications-email-preview-container">
			<!-- Email Client-like Interface -->
			<div class="aiohm-booking-notifications-email-client">
				<!-- Email Header -->
				<div class="aiohm-booking-notifications-email-header">
				<div class="aiohm-booking-notifications-email-meta">
					<div class="aiohm-booking-notifications-email-from">
					<span class="aiohm-booking-notifications-meta-label">From:</span>
					<span id="preview-from" class="aiohm-booking-notifications-meta-value">Your Hotel Name &lt;noreply@yoursite.com&gt;</span>
					</div>
					<div class="aiohm-booking-notifications-email-to">
					<span class="aiohm-booking-notifications-meta-label">To:</span>
					<span id="preview-to" class="aiohm-booking-notifications-meta-value">guest@example.com</span>
					</div>
					<div class="aiohm-booking-notifications-email-subject">
					<span class="aiohm-booking-notifications-meta-label">Subject:</span>
					<span id="preview-subject" class="aiohm-booking-notifications-meta-value">Booking Confirmation - {booking_id}</span>
					</div>
				</div>
				</div>
			  
				<!-- Email Body -->
				<div class="aiohm-booking-notifications-email-body">
				<div id="preview-content" class="aiohm-booking-notifications-email-content">
					<p>Dear {guest_name},</p>
					<p>Thank you for your booking! We're excited to welcome you to {property_name}.</p>
					<p><strong>📋 Booking Details:</strong></p>
					<ul>
					<li><strong>Booking ID:</strong> {booking_id}</li>
					<li><strong>Check-in:</strong> {check_in_date}</li>
					<li><strong>Check-out:</strong> {check_out_date}</li>
					<li><strong>Duration:</strong> {duration_nights} nights</li>
					<li><strong>Total Amount:</strong> {total_amount}</li>
					</ul>
					<p>We look forward to hosting you!</p>
					<p>Best regards,<br>
					{property_name} Team</p>
				</div>
				</div>
			  
				<!-- Email Footer Info -->
				<div class="aiohm-booking-notifications-email-footer-info">
				<small class="aiohm-booking-notifications-preview-note">
					📝 This preview shows how your email will appear to recipients. 
					Template variables (like {booking_id}) will be replaced with actual values when sent.
				</small>
				</div>
			</div>
			</div>
		</div>

		<!-- Email Template Customization Card -->
		<div class="aiohm-booking-notifications-card" id="email-template-customization-card">
			<h3>Email Template Customization</h3>
			<p>Customize the appearance and text of your email notifications to match your brand.</p>
		  
			<div class="aiohm-booking-notifications-template-customization">
			<input type="hidden" name="settings[email_preset]" id="selected-preset" value="<?php echo esc_attr( $email_preset ?? 'professional' ); ?>">

			<div class="aiohm-booking-notifications-customization-tabs">
				<button type="button" class="aiohm-booking-notifications-tab-button active" data-tab="company">Company Info</button>
				<button type="button" class="aiohm-booking-notifications-tab-button" data-tab="colors">Colors</button>
				<button type="button" class="aiohm-booking-notifications-tab-button" data-tab="text">Text & Messages</button>
			</div>

			<!-- Company Info Tab -->
			<div class="aiohm-booking-notifications-tab-content aiohm-booking-notifications-tab-company active" data-tab="company">
				<h4>Company Information for Invoices</h4>
				<div class="aiohm-booking-notifications-company-settings">
					<div class="aiohm-booking-notifications-setting-row">
						<label>Company/Business Name</label>
						<input type="text" name="settings[company_name]" value="<?php echo esc_attr( $company_name ?? get_bloginfo('name') ); ?>" placeholder="Your Business Name">
						<small>This will appear on invoices and email headers</small>
					</div>

					<div class="aiohm-booking-notifications-setting-row">
						<label>Company Logo URL</label>
						<div class="aiohm-booking-notifications-logo-upload">
							<input type="url" name="settings[company_logo]" value="<?php echo esc_attr( $company_logo ?? '' ); ?>" placeholder="https://yoursite.com/logo.png" class="aiohm-booking-notifications-logo-input">
							<button type="button" class="button-secondary aiohm-booking-notifications-upload-logo-btn">Upload Logo</button>
						</div>
						<small>Logo for invoice header (recommended size: 200x80px)</small>
					</div>

					<div class="aiohm-booking-notifications-setting-row">
						<label>Business Address</label>
						<textarea name="settings[company_address]" rows="3" placeholder="123 Business Street&#10;Your City, State 12345&#10;Country"><?php echo esc_textarea( $company_address ?? '' ); ?></textarea>
						<small>Full address for invoice billing information</small>
					</div>

					<div class="aiohm-booking-notifications-setting-row">
						<label>Contact Information</label>
						<input type="text" name="settings[company_contact]" value="<?php echo esc_attr( $company_contact ?? get_option('admin_email') ); ?>" placeholder="contact@yourbusiness.com or +1-234-567-8900">
						<small>Email or phone number for invoice contact</small>
					</div>

					<div class="aiohm-booking-notifications-setting-row">
						<label>Tax/VAT ID (Optional)</label>
						<input type="text" name="settings[company_tax_id]" value="<?php echo esc_attr( $company_tax_id ?? '' ); ?>" placeholder="VAT123456789">
						<small>Business tax or VAT identification number</small>
					</div>
				</div>
			</div>

			<!-- Colors Tab -->
			<div class="aiohm-booking-notifications-tab-content aiohm-booking-notifications-tab-colors" data-tab="colors">
				<h4>Email Color Scheme</h4>
				<div class="aiohm-booking-notifications-color-settings-grid">
				<div class="aiohm-booking-notifications-setting-row">
					<label>Primary Color (Headers)</label>
					<div class="aiohm-booking-notifications-color-input-wrapper">
					<input type="color" name="settings[email_primary_color]" value="<?php echo esc_attr( $email_primary_color ); ?>" class="aiohm-booking-notifications-color-input" id="primary-color-input">
					<span class="aiohm-booking-notifications-color-value" id="primary-color-value"><?php echo esc_attr( $email_primary_color ); ?></span>
					</div>
					<small>Color for headings and important text</small>
				</div>
				
				<div class="aiohm-booking-notifications-setting-row">
					<label>Text Color</label>
					<div class="aiohm-booking-notifications-color-input-wrapper">
					<input type="color" name="settings[email_text_color]" value="<?php echo esc_attr( $email_text_color ); ?>" class="aiohm-booking-notifications-color-input" id="text-color-input">
					<span class="aiohm-booking-notifications-color-value" id="text-color-value"><?php echo esc_attr( $email_text_color ); ?></span>
					</div>
					<small>Main text color throughout emails</small>
				</div>
				
				<div class="aiohm-booking-notifications-setting-row">
					<label>Background Color</label>
					<div class="aiohm-booking-notifications-color-input-wrapper">
					<input type="color" name="settings[email_background_color]" value="<?php echo esc_attr( $email_background_color ); ?>" class="aiohm-booking-notifications-color-input" id="background-color-input">
					<span class="aiohm-booking-notifications-color-value" id="background-color-value"><?php echo esc_attr( $email_background_color ); ?></span>
					</div>
					<small>Outer background color</small>
				</div>
				
				<div class="aiohm-booking-notifications-setting-row">
					<label>Content Background</label>
					<div class="aiohm-booking-notifications-color-input-wrapper">
					<input type="color" name="settings[email_content_bg_color]" value="<?php echo esc_attr( $email_content_bg_color ); ?>" class="aiohm-booking-notifications-color-input" id="content-bg-color-input">
					<span class="aiohm-booking-notifications-color-value" id="content-bg-color-value"><?php echo esc_attr( $email_content_bg_color ); ?></span>
					</div>
					<small>Main content area background</small>
				</div>
				
				<div class="aiohm-booking-notifications-setting-row">
					<label>Section Background</label>
					<div class="aiohm-booking-notifications-color-input-wrapper">
					<input type="color" name="settings[email_section_bg_color]" value="<?php echo esc_attr( $email_section_bg_color ); ?>" class="aiohm-booking-notifications-color-input" id="section-bg-color-input">
					<span class="aiohm-booking-notifications-color-value" id="section-bg-color-value"><?php echo esc_attr( $email_section_bg_color ); ?></span>
					</div>
					<small>Booking details section background</small>
				</div>
				</div>
			</div>

			<!-- Text Tab -->
			<div class="aiohm-booking-notifications-tab-content aiohm-booking-notifications-tab-text" data-tab="text">
				<h4>Email Text Templates</h4>
				<div class="aiohm-booking-notifications-text-settings">
				<div class="aiohm-booking-notifications-setting-row">
					<label>Email Greeting</label>
					<input type="text" name="settings[email_greeting_text]" value="<?php echo esc_attr( $email_greeting_text ); ?>" placeholder="Dear {customer_name}," class="aiohm-booking-notifications-text-input">
					<small>Standard greeting text (use {customer_name} placeholder)</small>
				</div>
				
				<div class="aiohm-booking-notifications-setting-row">
					<label>Email Closing</label>
					<input type="text" name="settings[email_closing_text]" value="<?php echo esc_attr( $email_closing_text ); ?>" placeholder="Best regards," class="aiohm-booking-notifications-text-input">
					<small>Standard closing text</small>
				</div>
				
				<div class="aiohm-booking-notifications-setting-row">
					<label>Email Footer</label>
					<input type="text" name="settings[email_footer_text]" value="<?php echo esc_attr( $email_footer_text ); ?>" placeholder="{site_name}" class="aiohm-booking-notifications-text-input">
					<small>Footer signature (use {site_name} placeholder)</small>
				</div>
				</div>
			</div>

			<div class="aiohm-booking-notifications-template-actions">
				<button type="button" class="button-secondary" id="preview-email-template">
				<span class="dashicons dashicons-visibility"></span>
				Preview Template
				</button>
				<button type="button" class="button-secondary" id="reset-email-template">
				<span class="dashicons dashicons-undo"></span>
				Reset to Default
				</button>
				<button type="submit" name="save_email_template_settings" class="button-primary">
				<span class="dashicons dashicons-saved"></span>
				Save Template Settings
				</button>
			</div>
			</div>
		</div>
		</form>

		<!-- Email Logs Card -->
		<div class="aiohm-booking-notifications-card" id="aiohm-email-logs-card">
		<h3>Email Logs</h3>
		<p>View all email activity and delivery status.</p>
		
		<div class="aiohm-booking-notifications-email-logs-container">
			<ul id="aiohm-email-logs-ul" class="aiohm-booking-notifications-email-logs-list">
			<li class="aiohm-booking-notifications-loading">Loading email logs...</li>
			</ul>
			<div class="aiohm-booking-notifications-email-logs-actions aiohm-booking-notifications-button-group">
			<button type="button" class="button-secondary" id="refresh-email-logs">
				Refresh Logs
			</button>
			<button type="button" class="button-secondary button-danger" id="clear-email-logs">Clear Logs</button>
			</div>
		</div>
		</div>
	</div>
	
	<!-- Right Column - Enhanced Email Template Manager -->
	<div class="aiohm-booking-notifications-right">
		<div class="aiohm-booking-notifications-card">
		<h3>Email Template Manager</h3>
		<p>Comprehensive email automation system for every stage of the guest journey.</p>
		
		<!-- Email Template Management -->
		<div class="aiohm-booking-notifications-email-templates-section">
			<!-- Quick Template Presets -->
			<div class="aiohm-booking-notifications-template-presets">
			<h5>Quick Template Presets</h5>
			<p>Apply professional email styles instantly with our Direct Response templates:</p>
			<div class="aiohm-booking-notifications-preset-buttons">
				<button type="button" class="button aiohm-booking-notifications-preset-btn" data-preset="professional">Professional</button>
				<button type="button" class="button aiohm-booking-notifications-preset-btn" data-preset="friendly">Friendly</button>
				<button type="button" class="button aiohm-booking-notifications-preset-btn" data-preset="luxury">Luxury</button>
				<button type="button" class="button aiohm-booking-notifications-preset-btn" data-preset="minimalist">Minimalist</button>
			</div>
			</div>

			<div class="aiohm-booking-notifications-setting-row">
			<label>Email Template to Customize</label>
			<select name="email_template_selector" id="email-template-selector" class="aiohm-booking-notifications-enhanced-select">
				<option value="">-- Select a template to customize --</option>
				<optgroup label="Core Booking Emails">
				<?php foreach ( $templates as $key => $template ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $template['name'] ); ?></option>
				<?php endforeach; ?>
				</optgroup>
			</select>
			<small>Select an email template to customize its content, timing, and recipients</small>
			</div>

			<div class="aiohm-booking-notifications-template-editor aiohm-booking-notifications-hidden" id="template-editor">
			<div class="aiohm-booking-notifications-template-settings">
				<div class="d-flex align-items-center gap-1">
				<div class="aiohm-booking-notifications-setting-row">
					<label>Template Status</label>
					<select name="template_status" class="aiohm-booking-notifications-enhanced-select">
					<option value="enabled">Enabled</option>
					<option value="disabled">Disabled</option>
					</select>
				</div>
				<div class="aiohm-booking-notifications-setting-row">
					<label>Send Timing</label>
					<select name="template_timing" class="aiohm-booking-notifications-enhanced-select">
					<option value="immediate">Immediately</option>
					<option value="1_hour">1 Hour Later</option>
					<option value="1_day">1 Day Later</option>
					<option value="3_days">3 Days Later</option>
					<option value="1_week">1 Week Later</option>
					<option value="custom">Custom Schedule</option>
					</select>
				</div>
				</div>

				<div class="aiohm-booking-notifications-setting-row aiohm-booking-notifications-custom-schedule-fields aiohm-booking-notifications-hidden">
				<div class="aiohm-booking-notifications-row-split">
					<div class="aiohm-booking-notifications-setting-col">
						<label>Custom Date</label>
						<input type="date" name="template_custom_date" class="aiohm-booking-notifications-enhanced-select">
					</div>
					<div class="aiohm-booking-notifications-setting-col">
						<label>Custom Time</label>
						<input type="time" name="template_custom_time" class="aiohm-booking-notifications-enhanced-select">
					</div>
				</div>
				<small>Set a specific date and time to send this email. Based on your site's timezone.</small>
				</div>

				<div class="aiohm-booking-notifications-setting-row">
				<label>Email Subject Line</label>
				<input type="text" name="template_subject" id="template-subject" placeholder="Use merge tags like {guest_name}, {booking_id}, {property_name}">
				<small>Available merge tags: {guest_name}, {booking_id}, {check_in_date}, {check_out_date}, {total_amount}, {property_name}</small>
				</div>

				<div class="aiohm-booking-notifications-setting-row">
				<label>Email Content</label>
				<textarea name="template_content" id="template-content" rows="10" placeholder="Dear {guest_name},&#10;&#10;Thank you for your booking...&#10;&#10;Use merge tags to personalize the email content."></textarea>
				</div>

				<div class="d-flex align-items-center gap-1">
				<div class="aiohm-booking-notifications-setting-row">
					<label>Sender Name</label>
					<input type="text" name="template_sender_name" placeholder="Your Hotel Name">
				</div>
				<div class="aiohm-booking-notifications-setting-row">
					<label>Reply-To Email</label>
					<input type="email" name="template_reply_to" placeholder="reservations@yourhotel.com">
				</div>
				</div>

				<div class="aiohm-booking-notifications-template-actions">
				<button type="button" class="button button-secondary" id="preview-template">Preview Email</button>
				<button type="button" class="button button-secondary" id="send-test-email">Send Test</button>
				<button type="button" class="button button-primary" id="save-template">Save Template</button>
				<button type="button" class="button button-secondary" id="reset-template">Reset to Default</button>
				</div>
			</div>
			</div>
		</div>
		
		<div class="aiohm-booking-notifications-template-variables">
			<h4>Available Merge Tags</h4>
			<div class="aiohm-booking-notifications-variables-grid">
			<div class="aiohm-booking-notifications-variable-item">
				<code>{guest_name}</code>
				<span>Guest's full name</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{guest_email}</code>
				<span>Guest's email address</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{booking_id}</code>
				<span>Booking reference number</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{check_in_date}</code>
				<span>Check-in date</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{check_out_date}</code>
				<span>Check-out date</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{duration_nights}</code>
				<span>Length of stay</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{total_amount}</code>
				<span>Total booking amount</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{deposit_amount}</code>
				<span>Required deposit</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{property_name}</code>
				<span>Your hotel/property name</span>
			</div>
			<div class="aiohm-booking-notifications-variable-item">
				<code>{accommodation_type}</code>
				<span>Room/accommodation details</span>
			</div>
			</div>
		</div>
		</div>
	</div>
	</div>
</div>

<?php
// The JavaScript for this template is now loaded via wp_enqueue_script.
// in the AIOHM_Booking_PROModulesNotificationsAIOHM_Booking_PROModulesNotificationsAIOHM_Booking_PROModulesNotificationsAIOHM_BOOKING_Module_Notifications class for better performance and CSP compliance.
?>
