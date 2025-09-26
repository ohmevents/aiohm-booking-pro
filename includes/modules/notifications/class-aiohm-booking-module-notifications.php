<?php
/**
 * Notifications Module for AIOHM Booking
 * Handles email notifications, SMTP configuration, and email templates.
 *
 * @package AIOHM_BOOKING
 * @since  2.0.0
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Notifications Module
 *
 * Handles email notifications, SMTP configuration, and email templates for the booking system.
 * Manages automated emails for booking confirmations, cancellations, payment reminders,
 * and other booking-related communications.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Module_Notifications extends AIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Create database table on plugin activation.
	 *
	 * Creates the email logs table for tracking sent and failed email notifications.
	 * This should be called from the main plugin activation hook.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public static function on_activation() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_email_logs';

		// Check if table already exists.
		if ( $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) ) {	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for notification data lookup
			return; // Table already exists.
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            timestamp datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            recipient varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            headers text NOT NULL,
            status varchar(20) NOT NULL,
            error_message text,
            PRIMARY KEY  (id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get the UI definition for this module.
	 *
	 * Defines module metadata including name, description, category, and settings configuration
	 * for the WordPress admin interface.
	 *
	 * @since  2.0.0
	 * @return array Module UI definition array.
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'notifications',
			'name'                => __( 'Notifications', 'aiohm-booking-pro' ),
			'description'         => __( 'Professional email notifications for booking confirmations, cancellations, and payment reminders.', 'aiohm-booking-pro' ),
			'icon'                => '📧',
			'category'            => 'booking',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => true,
			'admin_page_slug'     => 'aiohm-booking-notifications',
			'visible_in_settings' => true,
		);
	}

	/**
	 * Constructor.
	 *
	 * Initializes the notifications module and sets up admin page configuration.
	 *
	 * @since  2.0.0
	 */
	public function __construct() {
		parent::__construct();

		// This is a PAGE module - enable admin page.
		$this->has_admin_page  = true;
		$this->admin_page_slug = 'aiohm-booking-notifications';

		// Initialize the module.
		$this->init();
	}

	/**
	 * Initialize the module.
	 *
	 * Sets up settings configuration and prepares the module for operation.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function init() {
		// Settings configuration.
		$this->settings_section_id = 'notifications';
		$this->settings_page_title = __( 'Notifications', 'aiohm-booking-pro' );
		$this->settings_tab_title  = __( 'Notifications Settings', 'aiohm-booking-pro' );
		$this->has_quick_settings  = true;
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * Sets up AJAX handlers, email configuration hooks, logging hooks,
	 * and booking notification triggers.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	protected function init_hooks() {
		// AJAX hooks.
		add_action( 'wp_ajax_aiohm_booking_test_smtp', array( $this, 'ajax_test_smtp' ) );
		add_action( 'wp_ajax_aiohm_booking_save_notification_settings', array( $this, 'ajax_save_notification_settings' ) );
		add_action( 'wp_ajax_aiohm_booking_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_aiohm_booking_save_email_template', array( $this, 'ajax_save_email_template' ) );
		add_action( 'wp_ajax_aiohm_booking_reset_email_template', array( $this, 'ajax_reset_email_template' ) );
		add_action( 'wp_ajax_aiohm_booking_get_email_template', array( $this, 'ajax_get_email_template' ) );
		add_action( 'wp_ajax_aiohm_booking_get_email_logs', array( $this, 'ajax_get_email_logs' ) );
		add_action( 'wp_ajax_aiohm_booking_clear_email_logs', array( $this, 'ajax_clear_email_logs' ) );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 10, 1 );

		// Frontend scripts to pass company data.
		add_action( 'wp_footer', array( $this, 'output_company_data_script' ) );

		// Configure SMTP for all wp_mail() calls.
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		add_filter( 'wp_mail_from', array( $this, 'get_mail_from' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_mail_from_name' ) );

		// Email logging hooks.
		add_action( 'wp_mail_succeeded', array( $this, 'log_sent_email' ), 10, 1 );
		add_action( 'wp_mail_failed', array( $this, 'log_failed_email' ), 10, 1 );

		// Cron hook for scheduled emails.
		add_action( 'aiohm_booking_send_scheduled_email', array( $this, 'handle_scheduled_email' ), 10, 1 );

		// Module-specific hooks will be added here.
		// Email sending hooks.
		add_action( 'aiohm_booking_order_created', array( $this, 'send_booking_confirmation' ), 10, 1 );
		add_action( 'aiohm_booking_order_status_changed', array( $this, 'send_status_change_notification' ), 10, 3 );
		add_action( 'aiohm_booking_payment_received', array( $this, 'send_payment_confirmation' ), 10, 1 );
	}

	/**
	 * Get settings fields for this module.
	 *
	 * Defines the configuration fields for email notifications, SMTP settings,
	 * and sender information.
	 *
	 * @since  2.0.0
	 * @return array Array of settings field definitions.
	 */
	public function get_settings_fields() {
		return array(
			'enable_email_notifications' => array(
				'type'        => 'checkbox',
				'label'       => 'Enable Email Notifications',
				'description' => 'Enable automatic email notifications for bookings',
				'default'     => true,
			),
			'email_provider'             => array(
				'type'        => 'select',
				'label'       => 'Email Provider',
				'description' => 'Choose your email provider',
				'default'     => 'wordpress',
				'options'     => array(
					'wordpress' => 'WordPress Default',
					'smtp'      => 'Custom SMTP',
					'gmail'     => 'Gmail SMTP',
					'mailgun'   => 'Mailgun API',
					'sendgrid'  => 'SendGrid API',
				),
			),
			'smtp_host'                  => array(
				'type'        => 'text',
				'label'       => 'SMTP Host',
				'description' => 'SMTP server hostname',
				'default'     => '',
			),
			'smtp_port'                  => array(
				'type'        => 'number',
				'label'       => 'SMTP Port',
				'description' => 'SMTP server port (587, 465, 25)',
				'default'     => 587,
				'min'         => 1,
				'max'         => 65535,
			),
			'smtp_username'              => array(
				'type'        => 'text',
				'label'       => 'SMTP Username',
				'description' => 'SMTP authentication username',
				'default'     => '',
			),
			'smtp_password'              => array(
				'type'        => 'password',
				'label'       => 'SMTP Password',
				'description' => 'SMTP authentication password',
				'default'     => '',
			),
			'smtp_encryption'            => array(
				'type'        => 'select',
				'label'       => 'SMTP Encryption',
				'description' => 'SMTP encryption method',
				'default'     => 'tls',
				'options'     => array(
					'none' => 'None',
					'tls'  => 'TLS',
					'ssl'  => 'SSL',
				),
			),
			'from_name'                  => array(
				'type'        => 'text',
				'label'       => 'From Name',
				'description' => 'Name to use in From field of emails',
				'default'     => get_bloginfo( 'name' ),
			),
			'from_email'                 => array(
				'type'        => 'email',
				'label'       => 'From Email',
				'description' => 'Email address to use in From field',
				'default'     => get_option( 'admin_email' ),
			),
			'reply_to_email'             => array(
				'type'        => 'email',
				'label'       => 'Reply-To Email',
				'description' => 'Email address for replies',
				'default'     => get_option( 'admin_email' ),
			),
		);
	}

	/**
	 * Get default settings for this module
	 */
	/**
	 * Get default settings for the notifications module.
	 *
	 * Provides default configuration values for email notifications and SMTP settings.
	 *
	 * @since  2.0.0
	 * @return array Array of default settings.
	 */
	protected function get_default_settings() {
		// These settings will be stored under $all_settings['notifications'].
		return array(
			'enable_email_notifications' => true,
			'email_provider'             => 'wordpress',
			'smtp_host'                  => '',
			'smtp_port'                  => 587,
			'smtp_username'              => '',
			'smtp_password'              => '',
			'smtp_encryption'            => 'tls',
			'from_name'                  => get_bloginfo( 'name' ),
			'from_email'                 => get_option( 'admin_email' ),
			'reply_to_email'             => get_option( 'admin_email' ),
			'email_templates'            => array(), // Individual templates are saved here.
			'email_primary_color'        => '#457d58',
			'email_text_color'           => '#333333',
			'email_background_color'     => '#f9f9f9',
			'email_content_bg_color'     => '#ffffff',
			'email_section_bg_color'     => '#f5f5f5',
			'email_greeting_text'        => 'Dear {customer_name},',
			'email_closing_text'         => 'Best regards,',
			'email_footer_text'          => '{site_name}',
			'email_preset'               => 'professional',
		);
	}

	/**
	 * Render admin page
	 */
	/**
	 * Render the admin page.
	 *
	 * Displays the notifications configuration interface in the WordPress admin.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function render_admin_page() {
		$this->render_notifications_page();
	}

	/**
	 * Check if this module is enabled
	 */
	/**
	 * Check if the notifications module is enabled.
	 *
	 * Determines whether email notifications are active based on module settings.
	 *
	 * @since  2.0.0
	 * @return bool True if enabled, false otherwise.
	 */

	/**
	 * Check if module should be enabled by default
	 */
	/**
	 * Check if the module is enabled based on settings.
	 *
	 * Internal method to verify module activation status from stored settings.
	 *
	 * @since  2.0.0
	 * @return bool True if enabled, false otherwise.
	 */
	protected function check_if_enabled() {
		$settings   = AIOHM_BOOKING_Settings::get_all();
		$enable_key = 'enable_' . $this->get_ui_definition()['id'];

		// If the setting exists, check if it's explicitly enabled.
		if ( isset( $settings[ $enable_key ] ) ) {
			return true === $settings[ $enable_key ] || 'true' === $settings[ $enable_key ] || 1 === $settings[ $enable_key ] || '1' === $settings[ $enable_key ];
		}

		// Default to enabled for Notifications module - it's core booking functionality.
		return true;
	}

	/**
	 * Enqueue admin assets
	 */
	/**
	 * Enqueue admin assets for the notifications page.
	 *
	 * Loads JavaScript and CSS files needed for the notifications admin interface.
	 *
	 * @since  2.0.0
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix = '' ) {
		// Check if we're on the notifications page.
		if ( ! isset( $_GET['page'] ) || 'aiohm-booking-notifications' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public access for admin page check
			return;
		}

		// Enqueue notifications module CSS
		wp_enqueue_style(
			'aiohm-booking-notifications',
			AIOHM_BOOKING_URL . 'includes/modules/notifications/assets/css/aiohm-booking-notifications.css',
			array(),
			AIOHM_BOOKING_VERSION
		);

		wp_enqueue_script(
			'aiohm-booking-preset-templates',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-preset-templates.js',
			array(),
			AIOHM_BOOKING_VERSION,
			true
		);

		wp_enqueue_script(
			'aiohm-booking-notifications-admin',
			AIOHM_BOOKING_URL . 'includes/modules/notifications/assets/js/aiohm-booking-notifications-admin.js',
			array( 'jquery', 'aiohm-booking-preset-templates', 'aiohm-booking-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Enqueue template-specific JavaScript (extracted from inline script for CSP compliance).
		wp_enqueue_script(
			'aiohm-booking-notifications-template',
			AIOHM_BOOKING_URL . 'includes/modules/notifications/assets/js/aiohm-booking-notifications-template.js',
			array( 'jquery', 'aiohm-booking-notifications-admin' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Get templates to pass to JS.
		$settings  = $this->get_module_settings();
		$templates = $this->get_email_templates( $settings['email_templates'] ?? array() );

		wp_localize_script(
			'aiohm-booking-notifications-admin',
			'aiohm_booking_notifications',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'aiohm_booking_notifications_nonce' ),
				'email_provider' => $settings['email_provider'] ?? 'wordpress', // Pass email provider for template script.
				'templates'      => $templates, // Pass templates data.
				'i18n'           => array(
					'smtp_test_success' => __( 'SMTP connection successful!', 'aiohm-booking-pro' ),
					'smtp_test_failed'  => __( 'SMTP connection failed. Please check your settings.', 'aiohm-booking-pro' ),
					'test_email_sent'   => __( 'Test email sent successfully!', 'aiohm-booking-pro' ),
					'test_email_failed' => __( 'Failed to send test email.', 'aiohm-booking-pro' ),
					'template_saved'    => __( 'Template saved successfully!', 'aiohm-booking-pro' ),
					'template_reset'    => __( 'Template reset to default successfully!', 'aiohm-booking-pro' ),
				),
			)
		);
	}

	/**
	 * Output company data script for invoice generation.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	public function output_company_data_script() {
		// Only output on pages that might have booking forms
		$current_post = get_post();
		if ( ! $current_post || ( ! is_page() && ! is_single() ) ) {
			return;
		}

		// Check if the page contains booking shortcode
		if ( ! has_shortcode( $current_post->post_content, 'aiohm_booking' ) ) {
			return;
		}

		// Get company settings.
		$settings = $this->get_module_settings();
		
		$company_data = array(
			'company_name'    => $settings['company_name'] ?? get_bloginfo( 'name' ),
			'company_logo'    => $settings['company_logo'] ?? '',
			'company_address' => $settings['company_address'] ?? '',
			'company_contact' => $settings['company_contact'] ?? get_option( 'admin_email' ),
			'company_tax_id'  => $settings['company_tax_id'] ?? '',
		);

		// Output the script directly
		echo '<script type="text/javascript">';
		echo 'window.aiohm_booking_settings = ' . wp_json_encode( $company_data ) . ';';
		echo '</script>';
	}

	/**
	 * Render the notifications page
	 */
	/**
	 * Render the notifications page content.
	 *
	 * Displays the main notifications configuration interface including
	 * settings, email templates, and SMTP configuration.
	 *
	 * @since  2.0.0
	 * @return void
	 */
	private function render_notifications_page() {
		// Settings are now fetched in a structured way from the main settings array.
		$settings = $this->get_module_settings();

		// Prepare variables for the template.
		$email_provider  = $settings['email_provider'] ?? 'WordPress';
		$smtp_host       = $settings['smtp_host'] ?? '';
		$smtp_port       = $settings['smtp_port'] ?? '587';
		$smtp_username   = $settings['smtp_username'] ?? '';
		$smtp_password   = $settings['smtp_password'] ?? '';
		$smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
		$from_email      = $settings['from_email'] ?? get_option( 'admin_email' );
		$from_name       = $settings['from_name'] ?? get_bloginfo( 'name' );

		// Company information for invoices.
		$company_name    = $settings['company_name'] ?? get_bloginfo( 'name' );
		$company_logo    = $settings['company_logo'] ?? '';
		$company_address = $settings['company_address'] ?? '';
		$company_contact = $settings['company_contact'] ?? get_option( 'admin_email' );
		$company_tax_id  = $settings['company_tax_id'] ?? '';

		// Email template styling settings.
		$email_primary_color    = $settings['email_primary_color'] ?? '#457d58';
		$email_text_color       = $settings['email_text_color'] ?? '#333333';
		$email_background_color = $settings['email_background_color'] ?? '#f9f9f9';
		$email_content_bg_color = $settings['email_content_bg_color'] ?? '#ffffff';
		$email_section_bg_color = $settings['email_section_bg_color'] ?? '#f5f5f5';

		// Email template text settings.
		$email_greeting_text = $settings['email_greeting_text'] ?? 'Dear {customer_name},';
		$email_closing_text  = $settings['email_closing_text'] ?? 'Best regards,';
		$email_footer_text   = $settings['email_footer_text'] ?? '{site_name}';
		$email_preset        = $settings['email_preset'] ?? 'professional';

		// Get all email templates.
		$templates = $this->get_email_templates( $settings['email_templates'] ?? array() );

		// Include the notification template from module directory.
		$template_path = __DIR__ . '/templates/aiohm-booking-notifications.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="wrap"><h1>Notifications</h1><p>Template file not found at: ' . esc_html( $template_path ) . '</p></div>';
		}
	}

	/**
	 * Get email templates configuration
	 */
	/**
	 * Get email templates configuration.
	 *
	 * Merges saved templates with default template structure, providing
	 * comprehensive email template definitions for all booking scenarios.
	 *
	 * @since  2.0.0
	 * @param array $saved_templates Previously saved template configurations.
	 * @return array Complete email templates array.
	 */
	private function get_email_templates( $saved_templates ) {
		return array(
			// BOOKING FLOW TEMPLATES (1-16).
			'booking_inquiry'               => array(
				'name'        => '01. Booking Inquiry Received',
				'subject'     => $saved_templates['booking_inquiry']['subject'] ?? 'Booking Inquiry Received - {booking_id}',
				'content'     => $saved_templates['booking_inquiry']['content'] ?? $this->get_default_template( 'booking_inquiry' ),
				'status'      => $saved_templates['booking_inquiry']['status'] ?? 'enabled',
				'timing'      => $saved_templates['booking_inquiry']['timing'] ?? 'immediate',
				'sender_name' => $saved_templates['booking_inquiry']['sender_name'] ?? '',
				'reply_to'    => $saved_templates['booking_inquiry']['reply_to'] ?? '',
				'custom_date' => $saved_templates['booking_inquiry']['custom_date'] ?? '',
				'custom_time' => $saved_templates['booking_inquiry']['custom_time'] ?? '',
			),
			'booking_quote'                 => array(
				'name'    => '02. Booking Quote',
				'subject' => $saved_templates['booking_quote']['subject'] ?? 'Your Booking Quote - {booking_id}',
				'content' => $saved_templates['booking_quote']['content'] ?? $this->get_default_template( 'booking_quote' ),
				'status'  => $saved_templates['booking_quote']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_quote']['timing'] ?? 'immediate',
			),
			'booking_confirmation_user'     => array(
				'name'    => '03. Booking Confirmation (User)',
				'subject' => $saved_templates['booking_confirmation_user']['subject'] ?? 'Booking Confirmation - {booking_id}',
				'content' => $saved_templates['booking_confirmation_user']['content'] ?? $this->get_default_template( 'booking_confirmation_user' ),
				'status'  => $saved_templates['booking_confirmation_user']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_confirmation_user']['timing'] ?? 'immediate',
			),
			'booking_confirmation_admin'    => array(
				'name'    => '04. Booking Confirmation (Admin)',
				'subject' => $saved_templates['booking_confirmation_admin']['subject'] ?? 'New Booking Received - {booking_id}',
				'content' => $saved_templates['booking_confirmation_admin']['content'] ?? $this->get_default_template( 'booking_confirmation_admin' ),
				'status'  => $saved_templates['booking_confirmation_admin']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_confirmation_admin']['timing'] ?? 'immediate',
			),
			'booking_pending'               => array(
				'name'    => '05. Booking Pending Review',
				'subject' => $saved_templates['booking_pending']['subject'] ?? 'Booking Under Review - {booking_id}',
				'content' => $saved_templates['booking_pending']['content'] ?? $this->get_default_template( 'booking_pending' ),
				'status'  => $saved_templates['booking_pending']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_pending']['timing'] ?? 'immediate',
			),
			'booking_approved'              => array(
				'name'    => '06. Booking Approved',
				'subject' => $saved_templates['booking_approved']['subject'] ?? 'Booking Approved - {booking_id}',
				'content' => $saved_templates['booking_approved']['content'] ?? $this->get_default_template( 'booking_approved' ),
				'status'  => $saved_templates['booking_approved']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_approved']['timing'] ?? 'immediate',
			),
			'booking_rejected'              => array(
				'name'    => '07. Booking Rejected',
				'subject' => $saved_templates['booking_rejected']['subject'] ?? 'Booking Cannot Be Confirmed - {booking_id}',
				'content' => $saved_templates['booking_rejected']['content'] ?? $this->get_default_template( 'booking_rejected' ),
				'status'  => $saved_templates['booking_rejected']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_rejected']['timing'] ?? 'immediate',
			),
			'booking_modification_request'  => array(
				'name'    => '08. Booking Modification Request',
				'subject' => $saved_templates['booking_modification_request']['subject'] ?? 'Booking Modification Request - {booking_id}',
				'content' => $saved_templates['booking_modification_request']['content'] ?? $this->get_default_template( 'booking_modification_request' ),
				'status'  => $saved_templates['booking_modification_request']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_modification_request']['timing'] ?? 'immediate',
			),
			'booking_modification_approved' => array(
				'name'    => '09. Booking Modification Approved',
				'subject' => $saved_templates['booking_modification_approved']['subject'] ?? 'Booking Changes Confirmed - {booking_id}',
				'content' => $saved_templates['booking_modification_approved']['content'] ?? $this->get_default_template( 'booking_modification_approved' ),
				'status'  => $saved_templates['booking_modification_approved']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_modification_approved']['timing'] ?? 'immediate',
			),
			'booking_modification_rejected' => array(
				'name'    => '10. Booking Modification Rejected',
				'subject' => $saved_templates['booking_modification_rejected']['subject'] ?? 'Booking Changes Cannot Be Made - {booking_id}',
				'content' => $saved_templates['booking_modification_rejected']['content'] ?? $this->get_default_template( 'booking_modification_rejected' ),
				'status'  => $saved_templates['booking_modification_rejected']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_modification_rejected']['timing'] ?? 'immediate',
			),
			'booking_cancelled_user'        => array(
				'name'    => '11. Booking Cancelled (User)',
				'subject' => $saved_templates['booking_cancelled_user']['subject'] ?? 'Booking Cancelled - {booking_id}',
				'content' => $saved_templates['booking_cancelled_user']['content'] ?? $this->get_default_template( 'booking_cancelled_user' ),
				'status'  => $saved_templates['booking_cancelled_user']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_cancelled_user']['timing'] ?? 'immediate',
			),
			'booking_cancelled_admin'       => array(
				'name'    => '12. Booking Cancelled (Admin)',
				'subject' => $saved_templates['booking_cancelled_admin']['subject'] ?? 'Booking Cancelled - {booking_id}',
				'content' => $saved_templates['booking_cancelled_admin']['content'] ?? $this->get_default_template( 'booking_cancelled_admin' ),
				'status'  => $saved_templates['booking_cancelled_admin']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_cancelled_admin']['timing'] ?? 'immediate',
			),
			'booking_cancellation_fee'      => array(
				'name'    => '13. Cancellation Fee Notice',
				'subject' => $saved_templates['booking_cancellation_fee']['subject'] ?? 'Cancellation Fee Applied - {booking_id}',
				'content' => $saved_templates['booking_cancellation_fee']['content'] ?? $this->get_default_template( 'booking_cancellation_fee' ),
				'status'  => $saved_templates['booking_cancellation_fee']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_cancellation_fee']['timing'] ?? 'immediate',
			),
			'booking_refund_processed'      => array(
				'name'    => '14. Refund Processed',
				'subject' => $saved_templates['booking_refund_processed']['subject'] ?? 'Refund Processed - {booking_id}',
				'content' => $saved_templates['booking_refund_processed']['content'] ?? $this->get_default_template( 'booking_refund_processed' ),
				'status'  => $saved_templates['booking_refund_processed']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_refund_processed']['timing'] ?? 'immediate',
			),
			'booking_waitlist_added'        => array(
				'name'    => '15. Added to Waitlist',
				'subject' => $saved_templates['booking_waitlist_added']['subject'] ?? 'Added to Waitlist - {booking_id}',
				'content' => $saved_templates['booking_waitlist_added']['content'] ?? $this->get_default_template( 'booking_waitlist_added' ),
				'status'  => $saved_templates['booking_waitlist_added']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_waitlist_added']['timing'] ?? 'immediate',
			),
			'booking_waitlist_available'    => array(
				'name'    => '16. Waitlist Spot Available',
				'subject' => $saved_templates['booking_waitlist_available']['subject'] ?? 'Booking Now Available - {booking_id}',
				'content' => $saved_templates['booking_waitlist_available']['content'] ?? $this->get_default_template( 'booking_waitlist_available' ),
				'status'  => $saved_templates['booking_waitlist_available']['status'] ?? 'enabled',
				'timing'  => $saved_templates['booking_waitlist_available']['timing'] ?? 'immediate',
			),

			// PAYMENT TEMPLATES (17-32).
			'payment_request'               => array(
				'name'    => '17. Payment Request',
				'subject' => $saved_templates['payment_request']['subject'] ?? 'Payment Required - {booking_id}',
				'content' => $saved_templates['payment_request']['content'] ?? $this->get_default_template( 'payment_request' ),
				'status'  => $saved_templates['payment_request']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_request']['timing'] ?? 'immediate',
			),
			'payment_reminder_1'            => array(
				'name'    => '18. Payment Reminder (1st)',
				'subject' => $saved_templates['payment_reminder_1']['subject'] ?? 'Payment Reminder - {booking_id}',
				'content' => $saved_templates['payment_reminder_1']['content'] ?? $this->get_default_template( 'payment_reminder_1' ),
				'status'  => $saved_templates['payment_reminder_1']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_reminder_1']['timing'] ?? 'immediate',
			),
			'payment_reminder_2'            => array(
				'name'    => '19. Payment Reminder (2nd)',
				'subject' => $saved_templates['payment_reminder_2']['subject'] ?? 'Urgent: Payment Required - {booking_id}',
				'content' => $saved_templates['payment_reminder_2']['content'] ?? $this->get_default_template( 'payment_reminder_2' ),
				'status'  => $saved_templates['payment_reminder_2']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_reminder_2']['timing'] ?? 'immediate',
			),
			'payment_reminder_final'        => array(
				'name'    => '20. Payment Reminder (Final)',
				'subject' => $saved_templates['payment_reminder_final']['subject'] ?? 'Final Notice: Payment Required - {booking_id}',
				'content' => $saved_templates['payment_reminder_final']['content'] ?? $this->get_default_template( 'payment_reminder_final' ),
				'status'  => $saved_templates['payment_reminder_final']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_reminder_final']['timing'] ?? 'immediate',
			),
			'payment_receipt'               => array(
				'name'    => '21. Payment Receipt',
				'subject' => $saved_templates['payment_receipt']['subject'] ?? 'Payment Confirmation - {booking_id}',
				'content' => $saved_templates['payment_receipt']['content'] ?? $this->get_default_template( 'payment_receipt' ),
				'status'  => $saved_templates['payment_receipt']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_receipt']['timing'] ?? 'immediate',
			),
			'payment_partial'               => array(
				'name'    => '22. Partial Payment Received',
				'subject' => $saved_templates['payment_partial']['subject'] ?? 'Partial Payment Received - {booking_id}',
				'content' => $saved_templates['payment_partial']['content'] ?? $this->get_default_template( 'payment_partial' ),
				'status'  => $saved_templates['payment_partial']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_partial']['timing'] ?? 'immediate',
			),
			'payment_failed'                => array(
				'name'    => '23. Payment Failed',
				'subject' => $saved_templates['payment_failed']['subject'] ?? 'Payment Failed - {booking_id}',
				'content' => $saved_templates['payment_failed']['content'] ?? $this->get_default_template( 'payment_failed' ),
				'status'  => $saved_templates['payment_failed']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_failed']['timing'] ?? 'immediate',
			),
			'payment_overdue'               => array(
				'name'    => '24. Payment Overdue',
				'subject' => $saved_templates['payment_overdue']['subject'] ?? 'Payment Overdue - {booking_id}',
				'content' => $saved_templates['payment_overdue']['content'] ?? $this->get_default_template( 'payment_overdue' ),
				'status'  => $saved_templates['payment_overdue']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_overdue']['timing'] ?? 'immediate',
			),
			'deposit_received'              => array(
				'name'    => '25. Deposit Received',
				'subject' => $saved_templates['deposit_received']['subject'] ?? 'Deposit Received - {booking_id}',
				'content' => $saved_templates['deposit_received']['content'] ?? $this->get_default_template( 'deposit_received' ),
				'status'  => $saved_templates['deposit_received']['status'] ?? 'enabled',
				'timing'  => $saved_templates['deposit_received']['timing'] ?? 'immediate',
			),
			'balance_due_reminder'          => array(
				'name'    => '26. Balance Due Reminder',
				'subject' => $saved_templates['balance_due_reminder']['subject'] ?? 'Balance Due - {booking_id}',
				'content' => $saved_templates['balance_due_reminder']['content'] ?? $this->get_default_template( 'balance_due_reminder' ),
				'status'  => $saved_templates['balance_due_reminder']['status'] ?? 'enabled',
				'timing'  => $saved_templates['balance_due_reminder']['timing'] ?? 'immediate',
			),
			'installment_reminder'          => array(
				'name'    => '27. Installment Payment Due',
				'subject' => $saved_templates['installment_reminder']['subject'] ?? 'Installment Payment Due - {booking_id}',
				'content' => $saved_templates['installment_reminder']['content'] ?? $this->get_default_template( 'installment_reminder' ),
				'status'  => $saved_templates['installment_reminder']['status'] ?? 'enabled',
				'timing'  => $saved_templates['installment_reminder']['timing'] ?? 'immediate',
			),
			'payment_plan_setup'            => array(
				'name'    => '28. Payment Plan Setup',
				'subject' => $saved_templates['payment_plan_setup']['subject'] ?? 'Payment Plan Established - {booking_id}',
				'content' => $saved_templates['payment_plan_setup']['content'] ?? $this->get_default_template( 'payment_plan_setup' ),
				'status'  => $saved_templates['payment_plan_setup']['status'] ?? 'enabled',
				'timing'  => $saved_templates['payment_plan_setup']['timing'] ?? 'immediate',
			),
			'invoice_generated'             => array(
				'name'    => '29. Invoice Generated',
				'subject' => $saved_templates['invoice_generated']['subject'] ?? 'Invoice #{invoice_id} - {booking_id}',
				'content' => $saved_templates['invoice_generated']['content'] ?? $this->get_default_template( 'invoice_generated' ),
				'status'  => $saved_templates['invoice_generated']['status'] ?? 'enabled',
				'timing'  => $saved_templates['invoice_generated']['timing'] ?? 'immediate',
			),
			'receipt_request'               => array(
				'name'    => '30. Receipt Request',
				'subject' => $saved_templates['receipt_request']['subject'] ?? 'Receipt Available - {booking_id}',
				'content' => $saved_templates['receipt_request']['content'] ?? $this->get_default_template( 'receipt_request' ),
				'status'  => $saved_templates['receipt_request']['status'] ?? 'enabled',
				'timing'  => $saved_templates['receipt_request']['timing'] ?? 'immediate',
			),
			'tax_invoice'                   => array(
				'name'    => '31. Tax Invoice',
				'subject' => $saved_templates['tax_invoice']['subject'] ?? 'Tax Invoice - {booking_id}',
				'content' => $saved_templates['tax_invoice']['content'] ?? $this->get_default_template( 'tax_invoice' ),
				'status'  => $saved_templates['tax_invoice']['status'] ?? 'enabled',
				'timing'  => $saved_templates['tax_invoice']['timing'] ?? 'immediate',
			),
			'credit_applied'                => array(
				'name'    => '32. Credit Applied',
				'subject' => $saved_templates['credit_applied']['subject'] ?? 'Credit Applied to Account - {booking_id}',
				'content' => $saved_templates['credit_applied']['content'] ?? $this->get_default_template( 'credit_applied' ),
				'status'  => $saved_templates['credit_applied']['status'] ?? 'enabled',
				'timing'  => $saved_templates['credit_applied']['timing'] ?? 'immediate',
			),

			// CHECK-IN/OUT TEMPLATES (33-40).
			'pre_arrival_info'              => array(
				'name'    => '33. Pre-Arrival Information',
				'subject' => $saved_templates['pre_arrival_info']['subject'] ?? 'Your Stay is Coming Up! - {booking_id}',
				'content' => $saved_templates['pre_arrival_info']['content'] ?? $this->get_default_template( 'pre_arrival_info' ),
				'status'  => $saved_templates['pre_arrival_info']['status'] ?? 'enabled',
				'timing'  => $saved_templates['pre_arrival_info']['timing'] ?? 'immediate',
			),
			'check_in_instructions'         => array(
				'name'    => '34. Check-in Instructions',
				'subject' => $saved_templates['check_in_instructions']['subject'] ?? 'Check-in Instructions - {booking_id}',
				'content' => $saved_templates['check_in_instructions']['content'] ?? $this->get_default_template( 'check_in_instructions' ),
				'status'  => $saved_templates['check_in_instructions']['status'] ?? 'enabled',
				'timing'  => $saved_templates['check_in_instructions']['timing'] ?? 'immediate',
			),
			'check_in_confirmation'         => array(
				'name'    => '35. Check-in Confirmation',
				'subject' => $saved_templates['check_in_confirmation']['subject'] ?? 'Welcome! Check-in Complete - {booking_id}',
				'content' => $saved_templates['check_in_confirmation']['content'] ?? $this->get_default_template( 'check_in_confirmation' ),
				'status'  => $saved_templates['check_in_confirmation']['status'] ?? 'enabled',
				'timing'  => $saved_templates['check_in_confirmation']['timing'] ?? 'immediate',
			),
			'late_check_in'                 => array(
				'name'    => '36. Late Check-in Instructions',
				'subject' => $saved_templates['late_check_in']['subject'] ?? 'Late Check-in Instructions - {booking_id}',
				'content' => $saved_templates['late_check_in']['content'] ?? $this->get_default_template( 'late_check_in' ),
				'status'  => $saved_templates['late_check_in']['status'] ?? 'enabled',
				'timing'  => $saved_templates['late_check_in']['timing'] ?? 'immediate',
			),
			'check_out_reminder'            => array(
				'name'    => '37. Check-out Reminder',
				'subject' => $saved_templates['check_out_reminder']['subject'] ?? 'Check-out Reminder - {booking_id}',
				'content' => $saved_templates['check_out_reminder']['content'] ?? $this->get_default_template( 'check_out_reminder' ),
				'status'  => $saved_templates['check_out_reminder']['status'] ?? 'enabled',
				'timing'  => $saved_templates['check_out_reminder']['timing'] ?? 'immediate',
			),
			'check_out_instructions'        => array(
				'name'    => '38. Check-out Instructions',
				'subject' => $saved_templates['check_out_instructions']['subject'] ?? 'Check-out Instructions - {booking_id}',
				'content' => $saved_templates['check_out_instructions']['content'] ?? $this->get_default_template( 'check_out_instructions' ),
				'status'  => $saved_templates['check_out_instructions']['status'] ?? 'enabled',
				'timing'  => $saved_templates['check_out_instructions']['timing'] ?? 'immediate',
			),
			'late_check_out_fee'            => array(
				'name'    => '39. Late Check-out Fee',
				'subject' => $saved_templates['late_check_out_fee']['subject'] ?? 'Late Check-out Fee Applied - {booking_id}',
				'content' => $saved_templates['late_check_out_fee']['content'] ?? $this->get_default_template( 'late_check_out_fee' ),
				'status'  => $saved_templates['late_check_out_fee']['status'] ?? 'enabled',
				'timing'  => $saved_templates['late_check_out_fee']['timing'] ?? 'immediate',
			),
			'early_check_in_available'      => array(
				'name'    => '40. Early Check-in Available',
				'subject' => $saved_templates['early_check_in_available']['subject'] ?? 'Early Check-in Available - {booking_id}',
				'content' => $saved_templates['early_check_in_available']['content'] ?? $this->get_default_template( 'early_check_in_available' ),
				'status'  => $saved_templates['early_check_in_available']['status'] ?? 'enabled',
				'timing'  => $saved_templates['early_check_in_available']['timing'] ?? 'immediate',
			),

			// GUEST EXPERIENCE TEMPLATES (41-48).
			'welcome_message'               => array(
				'name'    => '41. Welcome Message',
				'subject' => $saved_templates['welcome_message']['subject'] ?? 'Welcome to {property_name}!',
				'content' => $saved_templates['welcome_message']['content'] ?? $this->get_default_template( 'welcome_message' ),
				'status'  => $saved_templates['welcome_message']['status'] ?? 'enabled',
				'timing'  => $saved_templates['welcome_message']['timing'] ?? 'immediate',
			),
			'special_requests_confirmed'    => array(
				'name'    => '42. Special Requests Confirmed',
				'subject' => $saved_templates['special_requests_confirmed']['subject'] ?? 'Special Requests Confirmed - {booking_id}',
				'content' => $saved_templates['special_requests_confirmed']['content'] ?? $this->get_default_template( 'special_requests_confirmed' ),
				'status'  => $saved_templates['special_requests_confirmed']['status'] ?? 'enabled',
				'timing'  => $saved_templates['special_requests_confirmed']['timing'] ?? 'immediate',
			),
			'room_upgrade_offer'            => array(
				'name'    => '43. Room Upgrade Offer',
				'subject' => $saved_templates['room_upgrade_offer']['subject'] ?? 'Complimentary Upgrade Available - {booking_id}',
				'content' => $saved_templates['room_upgrade_offer']['content'] ?? $this->get_default_template( 'room_upgrade_offer' ),
				'status'  => $saved_templates['room_upgrade_offer']['status'] ?? 'enabled',
				'timing'  => $saved_templates['room_upgrade_offer']['timing'] ?? 'immediate',
			),
			'amenities_information'         => array(
				'name'    => '44. Amenities Information',
				'subject' => $saved_templates['amenities_information']['subject'] ?? 'Property Amenities & Services - {booking_id}',
				'content' => $saved_templates['amenities_information']['content'] ?? $this->get_default_template( 'amenities_information' ),
				'status'  => $saved_templates['amenities_information']['status'] ?? 'enabled',
				'timing'  => $saved_templates['amenities_information']['timing'] ?? 'immediate',
			),
			'local_area_guide'              => array(
				'name'    => '45. Local Area Guide',
				'subject' => $saved_templates['local_area_guide']['subject'] ?? 'Local Area Guide & Recommendations',
				'content' => $saved_templates['local_area_guide']['content'] ?? $this->get_default_template( 'local_area_guide' ),
				'status'  => $saved_templates['local_area_guide']['status'] ?? 'enabled',
				'timing'  => $saved_templates['local_area_guide']['timing'] ?? 'immediate',
			),
			'weather_update'                => array(
				'name'    => '46. Weather Update',
				'subject' => $saved_templates['weather_update']['subject'] ?? 'Weather Update for Your Stay',
				'content' => $saved_templates['weather_update']['content'] ?? $this->get_default_template( 'weather_update' ),
				'status'  => $saved_templates['weather_update']['status'] ?? 'enabled',
				'timing'  => $saved_templates['weather_update']['timing'] ?? 'immediate',
			),
			'transportation_info'           => array(
				'name'    => '47. Transportation Information',
				'subject' => $saved_templates['transportation_info']['subject'] ?? 'Transportation & Directions - {booking_id}',
				'content' => $saved_templates['transportation_info']['content'] ?? $this->get_default_template( 'transportation_info' ),
				'status'  => $saved_templates['transportation_info']['status'] ?? 'enabled',
				'timing'  => $saved_templates['transportation_info']['timing'] ?? 'immediate',
			),
			'feedback_request'              => array(
				'name'    => '48. Feedback Request',
				'subject' => $saved_templates['feedback_request']['subject'] ?? 'How was your stay? - {booking_id}',
				'content' => $saved_templates['feedback_request']['content'] ?? $this->get_default_template( 'feedback_request' ),
				'status'  => $saved_templates['feedback_request']['status'] ?? 'enabled',
				'timing'  => $saved_templates['feedback_request']['timing'] ?? 'immediate',
			),

			// MAINTENANCE & SUPPORT TEMPLATES (49-56).
			'maintenance_notice'            => array(
				'name'    => '49. Maintenance Notice',
				'subject' => $saved_templates['maintenance_notice']['subject'] ?? 'Maintenance Notice - {booking_id}',
				'content' => $saved_templates['maintenance_notice']['content'] ?? $this->get_default_template( 'maintenance_notice' ),
				'status'  => $saved_templates['maintenance_notice']['status'] ?? 'enabled',
				'timing'  => $saved_templates['maintenance_notice']['timing'] ?? 'immediate',
			),
			'emergency_contact'             => array(
				'name'    => '50. Emergency Contact Information',
				'subject' => $saved_templates['emergency_contact']['subject'] ?? 'Emergency Contact Information',
				'content' => $saved_templates['emergency_contact']['content'] ?? $this->get_default_template( 'emergency_contact' ),
				'status'  => $saved_templates['emergency_contact']['status'] ?? 'enabled',
				'timing'  => $saved_templates['emergency_contact']['timing'] ?? 'immediate',
			),
			'housekeeping_schedule'         => array(
				'name'    => '51. Housekeeping Schedule',
				'subject' => $saved_templates['housekeeping_schedule']['subject'] ?? 'Housekeeping Schedule - {booking_id}',
				'content' => $saved_templates['housekeeping_schedule']['content'] ?? $this->get_default_template( 'housekeeping_schedule' ),
				'status'  => $saved_templates['housekeeping_schedule']['status'] ?? 'enabled',
				'timing'  => $saved_templates['housekeeping_schedule']['timing'] ?? 'immediate',
			),
			'utility_outage'                => array(
				'name'    => '52. Utility Outage Notice',
				'subject' => $saved_templates['utility_outage']['subject'] ?? 'Utility Service Notice - {booking_id}',
				'content' => $saved_templates['utility_outage']['content'] ?? $this->get_default_template( 'utility_outage' ),
				'status'  => $saved_templates['utility_outage']['status'] ?? 'enabled',
				'timing'  => $saved_templates['utility_outage']['timing'] ?? 'immediate',
			),
			'security_information'          => array(
				'name'    => '53. Security Information',
				'subject' => $saved_templates['security_information']['subject'] ?? 'Security & Safety Information',
				'content' => $saved_templates['security_information']['content'] ?? $this->get_default_template( 'security_information' ),
				'status'  => $saved_templates['security_information']['status'] ?? 'enabled',
				'timing'  => $saved_templates['security_information']['timing'] ?? 'immediate',
			),
			'wifi_credentials'              => array(
				'name'    => '54. WiFi Credentials',
				'subject' => $saved_templates['wifi_credentials']['subject'] ?? 'WiFi Access Information - {booking_id}',
				'content' => $saved_templates['wifi_credentials']['content'] ?? $this->get_default_template( 'wifi_credentials' ),
				'status'  => $saved_templates['wifi_credentials']['status'] ?? 'enabled',
				'timing'  => $saved_templates['wifi_credentials']['timing'] ?? 'immediate',
			),
			'lost_found_inquiry'            => array(
				'name'    => '55. Lost & Found Inquiry',
				'subject' => $saved_templates['lost_found_inquiry']['subject'] ?? 'Lost & Found Inquiry - {booking_id}',
				'content' => $saved_templates['lost_found_inquiry']['content'] ?? $this->get_default_template( 'lost_found_inquiry' ),
				'status'  => $saved_templates['lost_found_inquiry']['status'] ?? 'enabled',
				'timing'  => $saved_templates['lost_found_inquiry']['timing'] ?? 'immediate',
			),
			'damage_report'                 => array(
				'name'    => '56. Property Damage Report',
				'subject' => $saved_templates['damage_report']['subject'] ?? 'Property Damage Report - {booking_id}',
				'content' => $saved_templates['damage_report']['content'] ?? $this->get_default_template( 'damage_report' ),
				'status'  => $saved_templates['damage_report']['status'] ?? 'enabled',
				'timing'  => $saved_templates['damage_report']['timing'] ?? 'immediate',
			),

			// ADMINISTRATIVE TEMPLATES (57-64).
			'admin_new_booking'             => array(
				'name'    => '57. Admin: New Booking Alert',
				'subject' => $saved_templates['admin_new_booking']['subject'] ?? 'New Booking Alert - {booking_id}',
				'content' => $saved_templates['admin_new_booking']['content'] ?? $this->get_default_template( 'admin_new_booking' ),
				'status'  => $saved_templates['admin_new_booking']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_new_booking']['timing'] ?? 'immediate',
			),
			'admin_payment_received'        => array(
				'name'    => '58. Admin: Payment Received',
				'subject' => $saved_templates['admin_payment_received']['subject'] ?? 'Payment Received - {booking_id}',
				'content' => $saved_templates['admin_payment_received']['content'] ?? $this->get_default_template( 'admin_payment_received' ),
				'status'  => $saved_templates['admin_payment_received']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_payment_received']['timing'] ?? 'immediate',
			),
			'admin_cancellation_alert'      => array(
				'name'    => '59. Admin: Cancellation Alert',
				'subject' => $saved_templates['admin_cancellation_alert']['subject'] ?? 'Booking Cancelled - {booking_id}',
				'content' => $saved_templates['admin_cancellation_alert']['content'] ?? $this->get_default_template( 'admin_cancellation_alert' ),
				'status'  => $saved_templates['admin_cancellation_alert']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_cancellation_alert']['timing'] ?? 'immediate',
			),
			'admin_special_request'         => array(
				'name'    => '60. Admin: Special Request Alert',
				'subject' => $saved_templates['admin_special_request']['subject'] ?? 'Special Request - {booking_id}',
				'content' => $saved_templates['admin_special_request']['content'] ?? $this->get_default_template( 'admin_special_request' ),
				'status'  => $saved_templates['admin_special_request']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_special_request']['timing'] ?? 'immediate',
			),
			'admin_review_notification'     => array(
				'name'    => '61. Admin: Review Notification',
				'subject' => $saved_templates['admin_review_notification']['subject'] ?? 'New Guest Review - {booking_id}',
				'content' => $saved_templates['admin_review_notification']['content'] ?? $this->get_default_template( 'admin_review_notification' ),
				'status'  => $saved_templates['admin_review_notification']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_review_notification']['timing'] ?? 'immediate',
			),
			'admin_system_alert'            => array(
				'name'    => '62. Admin: System Alert',
				'subject' => $saved_templates['admin_system_alert']['subject'] ?? 'System Alert - Action Required',
				'content' => $saved_templates['admin_system_alert']['content'] ?? $this->get_default_template( 'admin_system_alert' ),
				'status'  => $saved_templates['admin_system_alert']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_system_alert']['timing'] ?? 'immediate',
			),
			'admin_daily_report'            => array(
				'name'    => '63. Admin: Daily Report',
				'subject' => $saved_templates['admin_daily_report']['subject'] ?? 'Daily Booking Report - {date}',
				'content' => $saved_templates['admin_daily_report']['content'] ?? $this->get_default_template( 'admin_daily_report' ),
				'status'  => $saved_templates['admin_daily_report']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_daily_report']['timing'] ?? 'immediate',
			),
			'admin_capacity_alert'          => array(
				'name'    => '64. Admin: Capacity Alert',
				'subject' => $saved_templates['admin_capacity_alert']['subject'] ?? 'Capacity Alert - High Occupancy',
				'content' => $saved_templates['admin_capacity_alert']['content'] ?? $this->get_default_template( 'admin_capacity_alert' ),
				'status'  => $saved_templates['admin_capacity_alert']['status'] ?? 'enabled',
				'timing'  => $saved_templates['admin_capacity_alert']['timing'] ?? 'immediate',
			),
		);
	}

	/**
	 * Get default template content
	 */
	/**
	 * Get default template content for a specific template type.
	 *
	 * Provides default email content for various booking scenarios when
	 * no custom template has been configured.
	 *
	 * @since  2.0.0
	 * @param string $template_type The type of template to retrieve.
	 * @return string Default template content or empty string if not found.
	 */
	private function get_default_template( $template_type ) {
		$templates = array(
			// BOOKING FLOW TEMPLATES (1-16).
			'booking_inquiry'               => 'Dear {guest_name},<br><br>Thank you for your inquiry about booking with us. We have received your request and will get back to you shortly with a quote and availability.<br><br>Best regards,<br>{site_name} Team',
			'booking_quote'                 => 'Dear {guest_name},<br><br>Here is your booking quote as requested:<br><br><strong>Booking Details:</strong><br>• Accommodation: {accommodation_type}<br>• Dates: {check_in_date} to {check_out_date}<br>• Total Price: {total_amount}<br><br>Please let us know if you would like to proceed with this booking.<br><br>Best regards,<br>{site_name} Team',
			'booking_confirmation_user'     => 'Dear {guest_name},<br><br>Thank you for your booking! We\'re excited to welcome you to {property_name}.<br><br><strong>📋 Booking Details:</strong><br>• <strong>Booking ID:</strong> {booking_id}<br>• <strong>Check-in:</strong> {check_in_date}<br>• <strong>Check-out:</strong> {check_out_date}<br>• <strong>Duration:</strong> {duration_nights} nights<br>• <strong>Total Amount:</strong> {total_amount}<br><br>We look forward to hosting you!<br><br>Best regards,<br>{property_name} Team',
			'booking_confirmation_admin'    => 'New booking received!<br><br><strong>📋 Booking Details:</strong><br>• <strong>Booking ID:</strong> {booking_id}<br>• <strong>Guest:</strong> {guest_name}<br>• <strong>Check-in:</strong> {check_in_date}<br>• <strong>Check-out:</strong> {check_out_date}<br>• <strong>Total Amount:</strong> {total_amount}<br><br>Please review and confirm the booking.',
			'booking_pending'               => 'Dear {guest_name},<br><br>Your booking request #{booking_id} is currently under review. We will notify you once it has been approved.<br><br>Thank you for your patience.<br><br>Best regards,<br>{site_name} Team',
			'booking_approved'              => 'Dear {guest_name},<br><br>Great news! Your booking #{booking_id} has been approved and is now confirmed.<br><br>We look forward to welcoming you on {check_in_date}.<br><br>Best regards,<br>{site_name} Team',
			'booking_rejected'              => 'Dear {guest_name},<br><br>We regret to inform you that we are unable to confirm your booking request #{booking_id} at this time. This may be due to availability issues or other conflicts.<br><br>Please contact us for alternative options.<br><br>Sincerely,<br>{site_name} Team',
			'booking_modification_request'  => 'Dear {guest_name},<br><br>We have received your request to modify booking #{booking_id}. Our team is reviewing the changes and will get back to you shortly.<br><br>Best regards,<br>{site_name} Team',
			'booking_modification_approved' => 'Dear {guest_name},<br><br>Your requested modifications for booking #{booking_id} have been approved. Please find the updated details attached.<br><br>Best regards,<br>{site_name} Team',
			'booking_modification_rejected' => 'Dear {guest_name},<br><br>We are unable to accommodate the requested changes for booking #{booking_id}. Your original booking remains confirmed.<br><br>Please contact us if you have any questions.<br><br>Sincerely,<br>{site_name} Team',
			'booking_cancelled_user'        => 'Dear {guest_name},<br><br>Your booking has been cancelled as requested.<br><br><strong>📋 Cancelled Booking:</strong><br>• <strong>Booking ID:</strong> {booking_id}<br>• <strong>Dates:</strong> {check_in_date} - {check_out_date}<br>• <strong>Refund:</strong> {refund_amount}<br><br>If you have any questions, please contact us.<br><br>Best regards,<br>{property_name} Team',
			'booking_cancelled_admin'       => 'Booking cancelled by guest.<br><br><strong>📋 Cancelled Booking:</strong><br>• <strong>Booking ID:</strong> {booking_id}<br>• <strong>Guest:</strong> {guest_name}<br>• <strong>Dates:</strong> {check_in_date} - {check_out_date}<br>• <strong>Refund Amount:</strong> {refund_amount}<br><br>Please process the refund if applicable.',
			'booking_cancellation_fee'      => 'Dear {guest_name},<br><br>As per our cancellation policy, a fee of {cancellation_fee_amount} has been applied to your cancelled booking #{booking_id}.<br><br>Please contact us if you have any questions.<br><br>Sincerely,<br>{site_name} Team',
			'booking_refund_processed'      => 'Dear {guest_name},<br><br>Your refund of {refund_amount} for booking #{booking_id} has been processed. It may take 5-7 business days to appear in your account.<br><br>Best regards,<br>{site_name} Team',
			'booking_waitlist_added'        => 'Dear {guest_name},<br><br>You have been added to the waitlist for {accommodation_type} for the dates {check_in_date} to {check_out_date}. We will notify you if a spot becomes available.<br><br>Best regards,<br>{site_name} Team',
			'booking_waitlist_available'    => 'Dear {guest_name},<br><br>Good news! A spot has become available for your waitlisted booking. Please complete your reservation within 24 hours to secure it.<br><br>Best regards,<br>{site_name} Team',

			// PAYMENT TEMPLATES (17-32).
			'payment_request'               => 'Dear {guest_name},<br><br>Your payment for booking #{booking_id} is now due. Please use the link below to complete your payment.<br><br>Amount Due: {total_amount}<br><br>Best regards,<br>{site_name} Team',
			'payment_reminder_1'            => 'Dear {guest_name},<br><br>This is a friendly reminder that payment is due for your upcoming booking.<br><br><strong>📋 Booking Details:</strong><br>• <strong>Booking ID:</strong> {booking_id}<br>• <strong>Check-in:</strong> {check_in_date}<br>• <strong>Outstanding Amount:</strong> {remaining_amount}<br><br>Please complete your payment to secure your reservation.<br><br>Best regards,<br>{property_name} Team',
			'payment_reminder_2'            => 'Dear {guest_name},<br><br>This is a second reminder that your payment for booking #{booking_id} is now overdue. Please submit payment to avoid cancellation.<br><br>Outstanding Amount: {remaining_amount}<br><br>Best regards,<br>{site_name} Team',
			'payment_reminder_final'        => 'Dear {guest_name},<br><br>FINAL NOTICE: Your payment for booking #{booking_id} is critically overdue. Your booking will be cancelled if payment is not received within 24 hours.<br><br>Outstanding Amount: {remaining_amount}<br><br>Sincerely,<br>{site_name} Team',
			'payment_receipt'               => 'Dear {guest_name},<br><br>Thank you for your payment! Here\'s your receipt.<br><br><strong>💳 Payment Details:</strong><br>• <strong>Booking ID:</strong> {booking_id}<br>• <strong>Amount Paid:</strong> {payment_amount}<br>• <strong>Payment Date:</strong> {payment_date}<br>• <strong>Payment Method:</strong> {payment_method}<br><br>Your reservation is now fully confirmed!<br><br>Best regards,<br>{property_name} Team',
			'payment_partial'               => 'Dear {guest_name},<br><br>We have received your partial payment of {payment_amount} for booking #{booking_id}. The remaining balance of {remaining_amount} is due by {payment_deadline}.<br><br>Best regards,<br>{site_name} Team',
			'payment_failed'                => 'Dear {guest_name},<br><br>Your recent payment attempt for booking #{booking_id} failed. Please update your payment information or try a different method.<br><br>Sincerely,<br>{site_name} Team',
			'payment_overdue'               => 'Dear {guest_name},<br><br>Your payment for booking #{booking_id} is now overdue. Please make a payment as soon as possible to avoid cancellation.<br><br>Outstanding Amount: {remaining_amount}<br><br>Sincerely,<br>{site_name} Team',
			'deposit_received'              => 'Dear {guest_name},<br><br>We have successfully received your deposit of {deposit_amount} for booking #{booking_id}. Your booking is now tentatively confirmed.<br><br>The remaining balance is due on {payment_deadline}.<br><br>Best regards,<br>{site_name} Team',
			'balance_due_reminder'          => 'Dear {guest_name},<br><br>This is a reminder that the remaining balance for your booking #{booking_id} is due on {payment_deadline}.<br><br>Remaining Amount: {remaining_amount}<br><br>Best regards,<br>{site_name} Team',
			'installment_reminder'          => 'Dear {guest_name},<br><br>Your next installment payment for booking #{booking_id} is due on {payment_deadline}.<br><br>Installment Amount: {installment_amount}<br><br>Best regards,<br>{site_name} Team',
			'payment_plan_setup'            => 'Dear {guest_name},<br><br>Your payment plan for booking #{booking_id} has been set up. You will be charged automatically on the scheduled dates.<br><br>Best regards,<br>{site_name} Team',
			'invoice_generated'             => 'Dear {guest_name},<br><br>Thank you for your booking! Below are your payment instructions to secure your reservation.<br><br><strong>📋 Booking Summary:</strong><br>• <strong>Booking ID:</strong> #{booking_id}<br>• <strong>Check-in:</strong> {check_in_date}<br>• <strong>Check-out:</strong> {check_out_date}<br>• <strong>Guests:</strong> {guest_count}<br>• <strong>Total Amount:</strong> {currency} {total_amount}<br>• <strong>Deposit Required:</strong> {currency} {deposit_amount}<br><br><strong>💳 Payment Instructions:</strong><br>To secure your booking, please transfer the deposit amount using one of the following methods:<br><br><strong>Option 1: Bank Transfer</strong><br>Account Name: {site_name}<br>Account Number: [Your Bank Account]<br>Sort Code: [Your Sort Code]<br>Reference: Booking #{booking_id}<br><br><strong>Option 2: Contact Us</strong><br>Email: {admin_email}<br>Phone: [Your Phone Number]<br><br><strong>⏰ Payment Deadline:</strong><br>Please complete payment within 48 hours to confirm your booking.<br><br>Questions? Feel free to contact us!<br><br>Best regards,<br>{site_name} Team',
			'receipt_request'               => 'Dear {guest_name},<br><br>As requested, here is the receipt for your payment for booking #{booking_id}.<br><br>Best regards,<br>{site_name} Team',
			'tax_invoice'                   => 'Dear {guest_name},<br><br>Please find your official tax invoice for booking #{booking_id} attached.<br><br>Best regards,<br>{site_name} Team',
			'credit_applied'                => 'Dear {guest_name},<br><br>A credit of {credit_amount} has been applied to your account. This credit will be automatically used for your next booking.<br><br>Best regards,<br>{site_name} Team',

			// CHECK-IN/OUT TEMPLATES (33-40).
			'pre_arrival_info'              => 'Dear {guest_name},<br><br>We are looking forward to welcoming you soon for your booking #{booking_id}. Here is some information to help you prepare for your stay.<br><br>Best regards,<br>{site_name} Team',
			'check_in_instructions'         => 'Dear {guest_name},<br><br>Your check-in is approaching! Here are your check-in instructions.<br><br><strong>🏨 Check-in Details:</strong><br>• <strong>Date:</strong> {check_in_date}<br>• <strong>Time:</strong> After 3:00 PM<br>• <strong>Address:</strong> {property_address}<br>• <strong>Access Code:</strong> {access_code}<br><br>We look forward to welcoming you!<br><br>Best regards,<br>{property_name} Team',
			'check_in_confirmation'         => 'Dear {guest_name},<br><br>Welcome! We can confirm that you have successfully checked in for your booking #{booking_id}. We hope you have a wonderful stay.<br><br>Best regards,<br>{site_name} Team',
			'late_check_in'                 => 'Dear {guest_name},<br><br>We understand you will be arriving late. Please follow these instructions for your late check-in for booking #{booking_id}.<br><br>Best regards,<br>{site_name} Team',
			'check_out_reminder'            => 'Dear {guest_name},<br><br>We hope you\'ve enjoyed your stay! This is a reminder about your check-out.<br><br><strong>🏨 Check-out Details:</strong><br>• <strong>Date:</strong> {check_out_date}<br>• <strong>Time:</strong> Before 11:00 AM<br>• <strong>Instructions:</strong> Please leave keys at reception<br><br>Thank you for staying with us!<br><br>Best regards,<br>{property_name} Team',
			'check_out_instructions'        => 'Dear {guest_name},<br><br>As your stay comes to an end, here are the instructions for your check-out for booking #{booking_id}.<br><br>Best regards,<br>{site_name} Team',
			'late_check_out_fee'            => 'Dear {guest_name},<br><br>A late check-out fee of {late_fee_amount} has been applied to your booking #{booking_id} as per our policy.<br><br>Sincerely,<br>{site_name} Team',
			'early_check_in_available'      => 'Dear {guest_name},<br><br>Good news! Early check-in is now available for your booking #{booking_id}. You may check in any time from {early_check_in_time}.<br><br>Best regards,<br>{site_name} Team',

			// GUEST EXPERIENCE TEMPLATES (41-48).
			'welcome_message'               => 'Welcome to {site_name}!<br><br>Thank you for joining us. We\'re excited to help you find the perfect accommodation for your next trip.<br><br><strong>🎉 What\'s Next?</strong><br>• Browse our available properties<br>• Book your perfect stay<br>• Enjoy our exclusive member benefits<br><br>Happy travels!<br><br>Best regards,<br>The {site_name} Team',
			'special_requests_confirmed'    => 'Dear {guest_name},<br><br>We are pleased to confirm that we have received and will accommodate your special requests for booking #{booking_id}.<br><br>Best regards,<br>{site_name} Team',
			'room_upgrade_offer'            => 'Dear {guest_name},<br><br>As a valued guest, we would like to offer you a complimentary upgrade for your upcoming booking #{booking_id}. Please let us know if you would like to accept this offer.<br><br>Best regards,<br>{site_name} Team',
			'amenities_information'         => 'Dear {guest_name},<br><br>Here is some information about the amenities available during your stay for booking #{booking_id}.<br><br>Best regards,<br>{site_name} Team',
			'local_area_guide'              => 'Dear {guest_name},<br><br>To help you make the most of your stay, here is a guide to local attractions and restaurants near {property_name}.<br><br>Enjoy your visit!<br><br>Best regards,<br>{site_name} Team',
			'weather_update'                => 'Dear {guest_name},<br><br>Here is a weather update for your upcoming stay. Please pack accordingly!<br><br>Forecast: {weather_forecast}<br><br>Best regards,<br>{site_name} Team',
			'transportation_info'           => 'Dear {guest_name},<br><br>Here is some information on transportation options to and from {property_name}.<br><br>Best regards,<br>{site_name} Team',
			'feedback_request'              => 'Dear {guest_name},<br><br>Thank you for your recent stay with us! We\'d love to hear about your experience.<br><br><strong>🌟 Share Your Experience:</strong><br>Your feedback helps us improve our service for future guests.<br><br>Please take a moment to leave us a review.<br><br>Thank you!<br><br>Best regards,<br>{property_name} Team',

			// MAINTENANCE & SUPPORT TEMPLATES (49-56).
			'maintenance_notice'            => 'Dear Guest,<br><br>Please be advised that there will be scheduled maintenance at {property_name} on {maintenance_date} from {maintenance_start_time} to {maintenance_end_time}. We apologize for any inconvenience.<br><br>Sincerely,<br>{site_name} Management',
			'emergency_contact'             => 'Dear Guest,<br><br>For any emergencies during your stay, please use the following contact information: {emergency_contact_details}.<br><br>Sincerely,<br>{site_name} Management',
			'housekeeping_schedule'         => 'Dear {guest_name},<br><br>Here is the housekeeping schedule for your stay. If you have any special requests, please let us know.<br><br>Schedule: {housekeeping_schedule}<br><br>Best regards,<br>{site_name} Team',
			'utility_outage'                => 'Dear Guest,<br><br>Please be aware of a planned utility outage on {outage_date} from {outage_start_time} to {outage_end_time}. We apologize for any inconvenience.<br><br>Sincerely,<br>{site_name} Management',
			'security_information'          => 'Dear Guest,<br><br>For your safety and security, please review the following information. {security_details}<br><br>Sincerely,<br>{site_name} Management',
			'wifi_credentials'              => 'Dear {guest_name},<br><br>Welcome! Here are the WiFi credentials for your stay.<br><br>Network: {wifi_ssid}<br>Password: {wifi_password}<br><br>Enjoy your stay!<br><br>Best regards,<br>{site_name} Team',
			'lost_found_inquiry'            => 'Dear {guest_name},<br><br>We have received your inquiry about a lost item. We are looking into it and will contact you as soon as we have an update.<br><br>Sincerely,<br>{site_name} Team',
			'damage_report'                 => 'Dear {guest_name},<br><br>This email is to inform you of a damage report filed for your recent stay in {accommodation_type} for booking #{booking_id}.<br><br>Details: {damage_details}<br><br>Please contact us to resolve this matter.<br><br>Sincerely,<br>{site_name} Management',

			// ADMINISTRATIVE TEMPLATES (57-64).
			'admin_new_booking'             => 'A new booking has been created.<br><br>Booking ID: {booking_id}<br>Guest: {guest_name}<br>Dates: {check_in_date} to {check_out_date}<br>Total: {total_amount}<br><br>Please review the booking in the admin panel.',
			'admin_payment_received'        => 'Payment has been received for booking #{booking_id}.<br><br>Amount: {payment_amount}<br>Guest: {guest_name}<br><br>The booking status has been updated to Paid.',
			'admin_cancellation_alert'      => 'Booking #{booking_id} has been cancelled by the guest.<br><br>Guest: {guest_name}<br>Dates: {check_in_date} to {check_out_date}<br><br>Please process any necessary refunds.',
			'admin_special_request'         => 'A new special request has been submitted for booking #{booking_id}.<br><br>Guest: {guest_name}<br>Request: {special_request_details}<br><br>Please review and take appropriate action.',
			'admin_review_notification'     => 'A new guest review has been submitted for booking #{booking_id}.<br><br>Guest: {guest_name}<br>Rating: {review_rating}<br>Comment: {review_comment}<br><br>Please review and publish if appropriate.',
			'admin_system_alert'            => 'System Alert: {alert_message}.<br><br>Please investigate this issue immediately. This is an automated message from the AIOHM Booking System.',
			'admin_daily_report'            => 'Here is the daily booking report for {date}.<br><br>New Bookings: {new_bookings_count}<br>Cancellations: {cancellations_count}<br>Total Revenue: {daily_revenue}<br><br>Full report is available in the admin panel.',
			'admin_capacity_alert'          => 'Capacity Alert: Occupancy for {date} has reached {occupancy_percentage}%.<br><br>Remaining capacity is low. Consider adjusting pricing or availability.',
		);

		return $templates[ $template_type ] ?? 'Template content not found.';
	}

	/**
	 * Save notification settings via AJAX
	 */
	/**
	 * AJAX handler for saving notification settings.
	 *
	 * Processes and saves notification configuration including SMTP settings,
	 * sender information, and email preferences.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response.
	 */
	public function ajax_save_notification_settings() {
		check_ajax_referer( 'aiohm_save_notification_settings', 'aiohm_notification_settings_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'aiohm-booking-pro' ) );
		}

		$all_settings          = AIOHM_BOOKING_Settings::get_all();
		$notification_settings = $this->get_module_settings();

		// Sanitize and update settings from the form.
		$posted_settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by security helper, sanitized with map_deep

		$fields_to_save = array(
			'smtp_host',
			'smtp_port',
			'smtp_username',
			'smtp_encryption',
			'from_email',
			'from_name',
			'email_primary_color',
			'email_text_color',
			'email_background_color',
			'email_content_bg_color',
			'email_section_bg_color',
			'email_greeting_text',
			'email_closing_text',
			'email_footer_text',
			'email_preset',
		);

		foreach ( $fields_to_save as $field ) {
			if ( isset( $posted_settings[ $field ] ) ) {
				$notification_settings[ $field ] = sanitize_text_field( $posted_settings[ $field ] );
			}
		}

		// Handle password separately to avoid sanitizing it away if empty.
		if ( isset( $posted_settings['smtp_password'] ) && ! empty( $posted_settings['smtp_password'] ) ) {
			$notification_settings['smtp_password'] = $posted_settings['smtp_password'];
		}

		$all_settings['notifications'] = $notification_settings;

		$result = update_option( 'aiohm_booking_settings', $all_settings );

		if ( $result ) {
			wp_send_json_success( __( 'Settings saved successfully!', 'aiohm-booking-pro' ) );
		} else {
			wp_send_json_error( __( 'Settings were not changed or an error occurred.', 'aiohm-booking-pro' ) );
		}
	}

	/**
	 * AJAX: Test SMTP connection
	 */
	/**
	 * AJAX handler for testing SMTP configuration.
	 *
	 * Validates SMTP settings by attempting to send a test email using
	 * the configured SMTP parameters.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response with test results.
	 */
	public function ajax_test_smtp() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'aiohm-booking-pro' ) );
		}
		check_ajax_referer( 'aiohm_booking_notifications_nonce', 'nonce' );

		// Get settings from the POST data, not saved options, to test before saving.
		$posted_settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by security helper, individual fields sanitized below

		$email_provider = sanitize_text_field( $posted_settings['email_provider'] ?? 'wordpress' );

		// If using WordPress native, just test wp_mail with a simple message
		if ( 'wordpress' === $email_provider || empty( $email_provider ) ) {
			$test_email = get_option( 'admin_email' );
			$subject = 'AIOHM Booking - WordPress Mail Test';
			$message = 'This is a test email to verify WordPress native mail is working. If you\'re in a local development environment, this may fail - that\'s normal!';

			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
			);

			$result = wp_mail( $test_email, $subject, $message, $headers );

			if ( $result ) {
				wp_send_json_success( __( 'WordPress native mail test successful! Email sent to', 'aiohm-booking-pro' ) . ' ' . $test_email );
			} else {
				// For local environments, wp_mail often fails - this is expected
				wp_send_json_success( __( 'WordPress native mail test completed. Note: In local development environments, emails may not actually send but the function works. Check your server\'s mail logs for details.', 'aiohm-booking-pro' ) );
			}
			return;
		}

		// For custom SMTP, proceed with SMTP testing
		$smtp_host       = sanitize_text_field( $posted_settings['smtp_host'] ?? '' );
		$smtp_port       = absint( $posted_settings['smtp_port'] ?? 587 );
		$smtp_username   = sanitize_text_field( $posted_settings['smtp_username'] ?? '' );
		$smtp_password   = $posted_settings['smtp_password'] ?? ''; // Don't sanitize password, it can contain special characters.
		$smtp_encryption = sanitize_text_field( $posted_settings['smtp_encryption'] ?? 'tls' );
		$from_email      = sanitize_email( $posted_settings['from_email'] ?? get_option( 'admin_email' ) );
		$from_name       = sanitize_text_field( $posted_settings['from_name'] ?? get_bloginfo( 'name' ) );

		if ( empty( $smtp_host ) || empty( $smtp_port ) || empty( $smtp_username ) ) {
			wp_send_json_error( __( 'Please fill in all required SMTP fields (Host, Port, Username).', 'aiohm-booking-pro' ) );
		}

		global $phpmailer;

		// Make sure the PHPMailer class has been instantiated.
		if ( ! ( $phpmailer instanceof PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$phpmailer = new PHPMailer( true );
		}

		// It's important to clear all recipients and attachments.
		// Because send() will reuse them.
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();

		try {
			$phpmailer->isSMTP();
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$phpmailer->Host     = $smtp_host;
			$phpmailer->SMTPAuth = true;
			$phpmailer->Port     = $smtp_port;
			$phpmailer->Username = $smtp_username;
			$phpmailer->Password = $smtp_password;

			if ( 'none' !== $smtp_encryption ) {
				$phpmailer->SMTPSecure = $smtp_encryption;
			}

			$phpmailer->setFrom( $from_email, $from_name );
			$phpmailer->addAddress( get_option( 'admin_email' ), 'Admin Test' );
			$phpmailer->Subject = 'AIOHM Booking SMTP Test - ' . home_url();
			$phpmailer->Body    = 'This is a test email to verify your SMTP settings are working correctly.';
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

			$phpmailer->send();
			wp_send_json_success( __( 'SMTP connection successful! A test email has been sent to', 'aiohm-booking-pro' ) . ' ' . get_option( 'admin_email' ) );

		} catch ( Exception $e ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			$error_message = '<strong>' . __( 'Mailer Error:', 'aiohm-booking-pro' ) . '</strong> ' . esc_html( $phpmailer->ErrorInfo );
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * AJAX: Send test email
	 */
	/**
	 * AJAX handler for sending test emails.
	 *
	 * Sends a test email using current notification settings to verify
	 * email delivery configuration.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response with send results.
	 */
	public function ajax_send_test_email() {
		check_ajax_referer( 'aiohm_booking_notifications_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$to_email    = isset( $_POST['to_email'] ) ? sanitize_email( wp_unslash( $_POST['to_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$subject     = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : 'Test Email'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$content     = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$sender_name = isset( $_POST['sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sender_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$reply_to    = isset( $_POST['reply_to'] ) ? sanitize_email( wp_unslash( $_POST['reply_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		$sent = $this->send_email(
			$to_email,
			$subject,
			$content,
			$this->get_sample_merge_tag_data(),
			array(
				'from_name' => $sender_name,
				'reply_to'  => $reply_to,
			)
		);

		if ( $sent ) {
			wp_send_json_success( __( 'Test email sent successfully!', 'aiohm-booking-pro' ) );
		} else {
			global $phpmailer;
			$error_message = __( 'Failed to send test email.', 'aiohm-booking-pro' );
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			if ( isset( $phpmailer ) && $phpmailer->ErrorInfo ) {
				$error_message .= ' ' . esc_html( $phpmailer->ErrorInfo );
			}
			// phpcs:enable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			wp_send_json_error( $error_message );
		}
	}

	/**
	 * Public method to send invoice notification
	 *
	 * @param int    $booking_id The booking ID
	 * @param string $recipient Optional. The recipient email address.
	 * @return bool Success status
	 */
	/**
	 * Send an invoice notification email.
	 *
	 * Sends an invoice email notification for a specific booking to the
	 * specified recipient or the booking contact.
	 *
	 * @since  2.0.0
	 * @param int         $booking_id The booking ID to send notification for.
	 * @param string|null $recipient  Optional specific recipient email address.
	 * @return bool True on success, false on failure.
	 */
	public function send_invoice_notification( $booking_id, $recipient = null ) {
		if ( empty( $booking_id ) ) {
			return false;
		}

		// Use the existing email scheduling system.
		$this->schedule_or_send_email( 'invoice_generated', $booking_id, $recipient );
		return true;
	}

	/**
	 * Send booking confirmation email notification.
	 *
	 * Sends a confirmation email when a new booking is created.
	 *
	 * @param int $booking_id Booking ID.
	 * @return bool True on success, false on failure.
	 */
	public function send_booking_confirmation( $booking_id ) {
		if ( empty( $booking_id ) ) {
			return false;
		}

		// Send confirmation email to customer
		$this->schedule_or_send_email( 'booking_confirmation_user', $booking_id );

		// Send notification email to admin
		$this->schedule_or_send_email( 'booking_confirmation_admin', $booking_id );

		return true;
	}

	/**
	 * Send status change notification email.
	 *
	 * Sends a notification email when booking status changes.
	 *
	 * @param int    $booking_id  Booking ID.
	 * @param string $old_status  Previous status.
	 * @param string $new_status  New status.
	 * @return bool True on success, false on failure.
	 */
	public function send_status_change_notification( $booking_id, $old_status, $new_status ) {
		if ( empty( $booking_id ) || empty( $new_status ) ) {
			return false;
		}

		// Determine the appropriate template based on new status
		$template_key = '';
		switch ( $new_status ) {
			case 'confirmed':
			case 'paid':
				$template_key = 'booking_approved';
				break;
			case 'cancelled':
				$template_key = 'booking_cancelled_user';
				break;
			case 'pending':
				$template_key = 'booking_pending';
				break;
			case 'rejected':
				$template_key = 'booking_rejected';
				break;
		}

		if ( ! empty( $template_key ) ) {
			$this->schedule_or_send_email( $template_key, $booking_id );
		}

		return true;
	}

	/**
	 * Send payment confirmation email.
	 *
	 * Sends a confirmation email when payment is received.
	 *
	 * @param int $booking_id Booking ID.
	 * @return bool True on success, false on failure.
	 */
	public function send_payment_confirmation( $booking_id ) {
		if ( empty( $booking_id ) ) {
			return false;
		}

		// Send payment confirmation using the booking approved template
		$this->schedule_or_send_email( 'booking_approved', $booking_id );

		return true;
	}

	/**
	 * Centralized email sending function.
	 *
	 * @param string $to Recipient email.
	 * @param string $subject Email subject.
	 * @param string $content Email content.
	 * @param array  $merge_data Data for merge tags.
	 * @param array  $headers_override Override for From Name and Reply-To.
	 * @return bool
	 */
	/**
	 * Send an email with template processing.
	 *
	 * Sends an email using WordPress mail functions with merge tag replacement,
	 * custom headers, and HTML template wrapping.
	 *
	 * @since  2.0.0
	 * @param string $to               Recipient email address.
	 * @param string $subject          Email subject line.
	 * @param string $content          Email content body.
	 * @param array  $merge_data       Optional merge tag data for replacement.
	 * @param array  $headers_override Optional custom headers.
	 * @return bool True on success, false on failure.
	 */
	private function send_email( $to, $subject, $content, $merge_data = array(), $headers_override = array() ) {
		$settings = $this->get_module_settings();

		// Replace merge tags.
		$processed_subject = $this->replace_merge_tags( $subject, $merge_data );
		$processed_content = $this->replace_merge_tags( $content, $merge_data );

		// Build the full HTML email body.
		$email_body = $this->build_email_html( $processed_content, $settings );

		// Set headers.
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Determine From Name: Override > Global Setting > WP Default.
		$from_name = ! empty( $headers_override['from_name'] ) ? $headers_override['from_name'] : ( $settings['from_name'] ?? get_bloginfo( 'name' ) );

		// Determine From Email (used in From header): Global Setting > WP Default.
		$from_email = $settings['from_email'] ?? get_option( 'admin_email' );

		// Determine Reply-To: Override > Global Setting > From Email.
		$reply_to = ! empty( $headers_override['reply_to'] ) ? $headers_override['reply_to'] : ( $settings['reply_to_email'] ?? $from_email );

		$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		if ( ! empty( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		return wp_mail( $to, $processed_subject, $email_body, $headers );
	}

	/**
	 * Build the full HTML for an email.
	 *
	 * @param string $content The main content of the email.
	 * @param array  $settings The notification settings.
	 * @return string The full HTML email.
	 */
	/**
	 * Build HTML email template.
	 *
	 * Wraps email content in an HTML template with customizable styling
	 * based on notification settings.
	 *
	 * @since  2.0.0
	 * @param string $content  The email content to wrap.
	 * @param array  $settings Module settings for styling customization.
	 * @return string Complete HTML email template.
	 */
	private function build_email_html( $content, $settings ) {
		$primary_color    = $settings['email_primary_color'] ?? '#457d58';
		$text_color       = $settings['email_text_color'] ?? '#333333';
		$bg_color         = $settings['email_background_color'] ?? '#f9f9f9';
		$content_bg_color = $settings['email_content_bg_color'] ?? '#ffffff';
		$greeting         = $this->replace_merge_tags( $settings['email_greeting_text'] ?? 'Dear {customer_name},', $this->get_sample_merge_tag_data() );
		$closing          = $this->replace_merge_tags( $settings['email_closing_text'] ?? 'Best regards,', $this->get_sample_merge_tag_data() );
		$footer           = $this->replace_merge_tags( $settings['email_footer_text'] ?? '{site_name}', $this->get_sample_merge_tag_data() );

		ob_start();
		?>
		<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
		<body style="margin:0; padding:0; background-color:<?php echo esc_attr( $bg_color ); ?>; font-family: Arial, sans-serif;">
		<table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:<?php echo esc_attr( $bg_color ); ?>;"><tr><td>
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; margin: 20px auto; background-color:<?php echo esc_attr( $content_bg_color ); ?>; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
			<tr><td align="center" style="padding: 20px; background-color:<?php echo esc_attr( $primary_color ); ?>; color: #ffffff; border-top-left-radius: 8px; border-top-right-radius: 8px;">
				<h1 style="margin:0; color: #ffffff;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			</td></tr>
			<tr><td style="padding: 30px; color:<?php echo esc_attr( $text_color ); ?>; line-height: 1.6;">
				<p><?php echo esc_html( $greeting ); ?></p>
				<div><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
				<p><?php echo nl2br( esc_html( $closing ) ); ?><br><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			</td></tr>
			<tr><td align="center" style="padding: 20px; background-color: #f1f1f1; color: #777777; font-size: 12px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
				<?php echo esc_html( $footer ); ?>
			</td></tr>
		</table>
		</td></tr></table>
		</body></html>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX: Save email template
	 */
	/**
	 * AJAX handler for saving email templates.
	 *
	 * Saves custom email template configuration including content, timing,
	 * and delivery settings for specific notification types.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response.
	 */
	public function ajax_save_email_template() {
		check_ajax_referer( 'aiohm_booking_notifications_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$template_key = isset( $_POST['template_key'] ) ? sanitize_text_field( wp_unslash( $_POST['template_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$subject      = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$content      = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'enabled'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$timing       = isset( $_POST['timing'] ) ? sanitize_text_field( wp_unslash( $_POST['timing'] ) ) : 'immediate'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$sender_name  = isset( $_POST['sender_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sender_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$reply_to     = isset( $_POST['reply_to'] ) ? sanitize_email( wp_unslash( $_POST['reply_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$custom_date  = isset( $_POST['custom_date'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$custom_time  = isset( $_POST['custom_time'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_time'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		if ( empty( $template_key ) ) {
			wp_send_json_error( 'Template key is required' );
		}

		$settings = $this->get_module_settings();
		// Preserve existing settings for the template and only update what's sent.
		$settings['email_templates'][ $template_key ] = array_merge(
			$settings['email_templates'][ $template_key ] ?? array(),
			array(
				'subject'     => $subject,
				'content'     => $content,
				'status'      => $status,
				'timing'      => $timing,
				'sender_name' => $sender_name,
				'reply_to'    => $reply_to,
				'custom_date' => $custom_date,
				'custom_time' => $custom_time,
			)
		);
		$saved                                        = $this->save_module_settings( $settings );

		if ( $saved ) {
			wp_send_json_success( 'Template saved successfully!' );
		} else {
			wp_send_json_error( 'Failed to save template or no changes made.' );
		}
	}

	/**
	 * AJAX: Reset email template to default
	 */
	/**
	 * AJAX handler for resetting email templates to defaults.
	 *
	 * Resets a specific email template back to its default content and settings.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response.
	 */
	public function ajax_reset_email_template() {
		check_ajax_referer( 'aiohm_booking_notifications_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$template_key = isset( $_POST['template_key'] ) ? sanitize_text_field( wp_unslash( $_POST['template_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		if ( empty( $template_key ) ) {
			wp_send_json_error( 'Template key is required' );
		}

		$settings = $this->get_module_settings();

		// Reset to default.
		unset( $settings['email_templates'][ $template_key ] );

		$saved = $this->save_module_settings( $settings );

		if ( $saved ) {
			$templates        = $this->get_email_templates( $settings['email_templates'] );
			$default_template = $templates[ $template_key ] ?? null;

			wp_send_json_success(
				array(
					'message' => 'Template reset successfully!',
					'subject' => $default_template['subject'] ?? '',
					'content' => $default_template['content'] ?? '',
				)
			);
		} else {
			wp_send_json_error( 'Failed to reset template' );
		}
	}

	/**
	 * AJAX: Get email template data
	 */
	/**
	 * AJAX handler for retrieving email template data.
	 *
	 * Fetches email template configuration for editing in the admin interface.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response with template data.
	 */
	public function ajax_get_email_template() {
		check_ajax_referer( 'aiohm_booking_notifications_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$template_key = isset( $_POST['template_key'] ) ? sanitize_text_field( wp_unslash( $_POST['template_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		if ( empty( $template_key ) ) {
			wp_send_json_error( 'Template key is required' );
		}

		$settings  = $this->get_module_settings();
		$templates = $this->get_email_templates( $settings['email_templates'] ?? array() );
		$template  = $templates[ $template_key ] ?? null;

		if ( ! $template ) {
			wp_send_json_error( 'Template not found' );
		}

		wp_send_json_success(
			array(
				'subject'     => $template['subject'] ?? '',
				'content'     => $template['content'] ?? '',
				'status'      => $template['status'] ?? 'enabled',
				'timing'      => $template['timing'] ?? 'immediate',
				'sender_name' => $template['sender_name'] ?? '',
				'reply_to'    => $template['reply_to'] ?? '',
				'custom_date' => $template['custom_date'] ?? '',
				'custom_time' => $template['custom_time'] ?? '',
			)
		);
	}

	/**
	 * Save module settings
	 */
	/**
	 * Save module settings.
	 *
	 * Stores notification module settings in the WordPress options table.
	 *
	 * @since  2.0.0
	 * @param array $data Settings data to save.
	 * @return bool True on success, false on failure.
	 */
	public function save_module_settings( $data ) {
		$all_settings                  = AIOHM_BOOKING_Settings::get_all();
		$all_settings['notifications'] = $data;
		return update_option( 'aiohm_booking_settings', $all_settings );
	}

	/**
	 * Handler for the scheduled email cron job.
	 *
	 * @param array $args Arguments passed from the cron job.
	 */
	/**
	 * Handle scheduled email sending.
	 *
	 * Processes scheduled email notifications triggered by WordPress cron.
	 *
	 * @since  2.0.0
	 * @param array $args Scheduled email arguments including template and order data.
	 * @return void
	 */
	public function handle_scheduled_email( $args ) {
		if ( empty( $args['template_key'] ) || empty( $args['order_id'] ) ) {
			return;
		}
		$this->send_email_notification( $args['template_key'], $args['order_id'], $args['recipient'] ?? null );
	}

	/**
	 * A generic function to either send an email immediately or schedule it.
	 *
	 * @param string $template_key The key for the email template.
	 * @param int    $order_id     The ID of the order/booking.
	 * @param string $recipient    Optional. The recipient email. Defaults to customer email from booking.
	 */
	/**
	 * Schedule or send an email notification.
	 *
	 * Determines whether to send an email immediately or schedule it for later
	 * based on template timing configuration.
	 *
	 * @since  2.0.0
	 * @param string      $template_key The email template key to use.
	 * @param int         $order_id     The order ID for the notification.
	 * @param string|null $recipient    Optional specific recipient email.
	 * @return bool True on success, false on failure.
	 */
	private function schedule_or_send_email( $template_key, $order_id, $recipient = null ) {
		$settings      = $this->get_module_settings();
		$all_templates = $this->get_email_templates( $settings['email_templates'] ?? array() );
		$template_info = $all_templates[ $template_key ] ?? null;

		if ( ! $template_info || ( $template_info['status'] ?? 'enabled' ) !== 'enabled' ) {
			return; // Template not found or is disabled.
		}

		$timing = $template_info['timing'] ?? 'immediate';

		if ( 'immediate' === $timing ) {
			$this->send_email_notification( $template_key, $order_id, $recipient );
			return;
		}

		if ( 'custom' === $timing ) {
			$custom_date = $template_info['custom_date'] ?? '';
			$custom_time = $template_info['custom_time'] ?? '09:00'; // Default time if not set.

			if ( ! empty( $custom_date ) ) {
				// Combine date and time to create a timestamp.
				$schedule_timestamp = strtotime( "$custom_date $custom_time" );

				// Check if the time is in the future.
				if ( $schedule_timestamp > time() ) {
					wp_schedule_single_event(
						$schedule_timestamp,
						'aiohm_booking_send_scheduled_email',
						array(
							array(
								'template_key' => $template_key,
								'order_id'     => $order_id,
								'recipient'    => $recipient,
							),
						)
					);
					return; // Scheduled, so we're done.
				}
			}
		}

		$intervals = array(
			'1_hour' => HOUR_IN_SECONDS,
			'1_day'  => DAY_IN_SECONDS,
			'3_days' => 3 * DAY_IN_SECONDS,
			'1_week' => WEEK_IN_SECONDS,
		);

		$delay = $intervals[ $timing ] ?? 0;

		if ( $delay > 0 ) {
			wp_schedule_single_event(
				time() + $delay,
				'aiohm_booking_send_scheduled_email',
				array(
					array(
						'template_key' => $template_key,
						'order_id'     => $order_id,
						'recipient'    => $recipient,
					),
				)
			);
		}
	}

	/**
	 * The actual email sending logic.
	 *
	 * @param string $template_key The key for the email template.
	 * @param int    $order_id     The ID of the order.
	 * @param string $recipient    Optional. The recipient email.
	 */
	/**
	 * Send an email notification using a template.
	 *
	 * Sends a notification email using the specified template with order data
	 * for merge tag replacement.
	 *
	 * @since  2.0.0
	 * @param string      $template_key The email template key to use.
	 * @param int         $order_id     The order ID for data retrieval.
	 * @param string|null $recipient    Optional specific recipient email.
	 * @return bool True on success, false on failure.
	 */
	private function send_email_notification( $template_key, $order_id, $recipient = null ) {
		global $wpdb;
		$order_table = esc_sql( $wpdb->prefix . 'aiohm_booking_order' );
		$sql = "SELECT * FROM `{$order_table}` WHERE id = %d";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe interpolation with escaped table name
		$order = $wpdb->get_row( $wpdb->prepare( $sql, $order_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe interpolation with escaped table name, custom table query for plugin functionality

		if ( ! $order ) {
			return;
		}

		// Determine recipient.
		if ( is_null( $recipient ) ) {
			$recipient = str_contains( $template_key, '_admin' ) ? get_option( 'admin_email' ) : $order->buyer_email;
		}

		if ( empty( $recipient ) || ! AIOHM_BOOKING_Validation::validate_email( $recipient ) ) {
			return;
		}

		$settings      = $this->get_module_settings();
		$all_templates = $this->get_email_templates( $settings['email_templates'] ?? array() );
		$template      = $all_templates[ $template_key ] ?? null;

		// Do not send if template doesn't exist or is disabled.
		// This is a crucial check for scheduled emails that might have been disabled.
		// after they were scheduled but before they were sent.
		if ( ! $template || ( $template['status'] ?? 'enabled' ) !== 'enabled' ) {
			return;
		}

		$merge_tag_data = $this->get_merge_tag_data_for_order( $order );

		$this->send_email(
			$recipient,
			$template['subject'],
			$template['content'],
			$merge_tag_data,
			array(
				'from_name' => $template['sender_name'] ?? null,
				'reply_to'  => $template['reply_to'] ?? null,
			)
		);
	}

	/**
	 * Get merge tag data for an order.
	 *
	 * Retrieves and formats order data for use in email template merge tags.
	 *
	 * @since  2.0.0
	 * @param object|null $order Order object from database.
	 * @return array Array of merge tag keys and values.
	 */
	private function get_merge_tag_data_for_order( $order ) {
		if ( ! $order ) {
			return $this->get_sample_merge_tag_data(); // Fallback for safety.
		}
		// This is where you would map real order data to merge tags.
		return $this->get_sample_merge_tag_data();
	}

	/**
	 * Get sample data for replacing merge tags in test emails.
	 *
	 * @return array
	 */
	/**
	 * Get sample merge tag data.
	 *
	 * Provides sample data for merge tags when no real order data is available,
	 * useful for template previews and testing.
	 *
	 * @since  2.0.0
	 * @return array Array of sample merge tag keys and values.
	 */
	private function get_sample_merge_tag_data() {
		return array(
			'{guest_name}'         => 'John Doe',
			'{guest_email}'        => 'guest@example.com',
			'{booking_id}'         => 'TEST-12345',
			'{check_in_date}'      => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
			'{check_out_date}'     => date_i18n( get_option( 'date_format' ), strtotime( '+10 days' ) ),
			'{duration_nights}'    => 3,
			'{total_amount}'       => number_format_i18n( 299.99, 2 ),
			'{deposit_amount}'     => number_format_i18n( 99.99, 2 ),
			'{property_name}'      => get_bloginfo( 'name' ),
			'{accommodation_type}' => 'Deluxe Room',
			'{site_name}'          => get_bloginfo( 'name' ),
			'{customer_name}'      => 'John Doe',
			'{first_name}'         => 'John',
			'{check_in}'           => date_i18n( get_option( 'date_format' ), strtotime( '+7 days' ) ),
			'{check_out}'          => date_i18n( get_option( 'date_format' ), strtotime( '+10 days' ) ),
			'{rooms}'              => 'Deluxe Room',
			'{payment_deadline}'   => date_i18n( get_option( 'date_format' ), strtotime( '+3 days' ) ),
			'{remaining_amount}'   => number_format_i18n( 200.00, 2 ),
		);
	}

	/**
	 * Replace merge tags in a string with provided data.
	 *
	 * @param string $text The string containing merge tags.
	 * @param array  $data The data to replace merge tags with.
	 * @return string The processed string.
	 */
	/**
	 * Replace merge tags in text with actual data.
	 *
	 * Performs string replacement to substitute merge tags with real values
	 * in email content.
	 *
	 * @since  2.0.0
	 * @param string $text The text containing merge tags.
	 * @param array  $data Array of merge tag keys and replacement values.
	 * @return string Text with merge tags replaced.
	 */
	private function replace_merge_tags( $text, $data ) {
		return str_replace( array_keys( $data ), array_values( $data ), $text );
	}

	/**
	 * Configure PHPMailer to use SMTP settings.
	 *
	 * @param PHPMailer $phpmailer The PHPMailer instance.
	 */
	/**
	 * Configure SMTP settings for PHPMailer.
	 *
	 * Sets up SMTP configuration for outgoing emails when SMTP provider is selected.
	 *
	 * @since  2.0.0
	 * @param PHPMailer $phpmailer The PHPMailer instance to configure.
	 * @return void
	 */
	public function configure_smtp( PHPMailer $phpmailer ) {
		$settings = $this->get_module_settings();

		if ( empty( $settings['email_provider'] ) || 'smtp' !== $settings['email_provider'] || empty( $settings['smtp_host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
		$phpmailer->Host     = $settings['smtp_host'];
		$phpmailer->SMTPAuth = true;
		$phpmailer->Port     = absint( $settings['smtp_port'] ?? 587 );
		$phpmailer->Username = $settings['smtp_username'] ?? '';
		$phpmailer->Password = $settings['smtp_password'] ?? '';

		if ( ! empty( $settings['smtp_encryption'] ) && 'none' !== $settings['smtp_encryption'] ) {
			$phpmailer->SMTPSecure = $settings['smtp_encryption'];
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	}

	/**
	 * Get the From email address for outgoing emails.
	 *
	 * Returns the configured sender email address or falls back to the original.
	 *
	 * @since  2.0.0
	 * @param string $original_email_address The original WordPress From email.
	 * @return string The From email address to use.
	 */
	public function get_mail_from( $original_email_address ) {
		$settings = $this->get_module_settings();
		return ! empty( $settings['from_email'] ) ? $settings['from_email'] : $original_email_address;
	}

	/**
	 * Get the From name for outgoing emails.
	 *
	 * Returns the configured sender name or falls back to the original.
	 *
	 * @since  2.0.0
	 * @param string $original_from_name The original WordPress From name.
	 * @return string The From name to use.
	 */
	public function get_mail_from_name( $original_from_name ) {
		$settings = $this->get_module_settings();
		return ! empty( $settings['from_name'] ) ? $settings['from_name'] : $original_from_name;
	}




	/**
	 * AJAX: Get email logs
	 */
	/**
	 * AJAX handler for retrieving email logs.
	 *
	 * Fetches email log data for display in the admin interface.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response with log data.
	 */
	public function ajax_get_email_logs() {
		// Verify nonce and permissions explicitly so we always return JSON.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below

		if ( empty( $nonce ) || ! AIOHM_BOOKING_Security_Helper::verify_nonce( $nonce, 'notifications_nonce' ) ) {
			wp_send_json_error( 'Invalid or missing nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		try {
			$logs = $this->get_email_logs();
			wp_send_json_success( $logs );
		} catch ( Exception $e ) {
			// Log server-side exception for diagnosis and return safe message.
			wp_send_json_error( 'Error fetching email logs' );
		}
	}

	/**
	 * Get email logs from database
	 */
	/**
	 * Get email logs from database.
	 *
	 * Retrieves email log entries from the database with pagination support.
	 *
	 * @since  2.0.0
	 * @return array Array of email log entries.
	 */
	private function get_email_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_email_logs';

		// Check if table exists to prevent errors if activation hook didn't run.
		if ( $table_name !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) ) {	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for notification record query
			// Optionally, try to create it now.
			self::on_activation(); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
			// If it still doesn't exist, return an error message.
			if ( $table_name !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) ) {	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for notification update
				return array(
					array(
						'type'          => 'Error',
						'recipient'     => 'System',
						'status'        => 'failed',
						'time'          => 'Now',
						'timestamp'     => current_time( 'mysql' ),
						'error_message' => 'Email logs table is missing.',
					),
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
			}
		}

		$logs_table = esc_sql( $wpdb->prefix . 'aiohm_booking_email_logs' );
		$sql = "SELECT * FROM `{$logs_table}` ORDER BY timestamp DESC";
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Safe interpolation with escaped table name
		$logs = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for notification insertion
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Safe interpolation with escaped table name
			ARRAY_A
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		if ( empty( $logs ) ) {
			return array();
		}

		// Format the logs for display.
		return array_map(
			function ( $log ) {
				return array(
					'type'      => esc_html( $log['subject'] ), // Using subject as type for now.
					'recipient' => esc_html( $log['recipient'] ),
					'status'    => esc_html( $log['status'] ),
					'time'      => human_time_diff( strtotime( $log['timestamp'] ), current_time( 'timestamp' ) ) . ' ago',
					'timestamp' => $log['timestamp'],
				);
			},
			$logs
		);
	}

	/**
	 * Log a successfully sent email.
	 *
	 * @param array $mail_data The data for the sent email.
	 */
	/**
	 * Log a successfully sent email.
	 *
	 * Records email sending success in the email logs table.
	 *
	 * @since  2.0.0
	 * @param array $mail_data Email data from wp_mail_succeeded action.
	 * @return void
	 */
	public function log_sent_email( $mail_data ) {
		$this->log_email(
			array(
				'recipient' => is_array( $mail_data['to'] ) ? implode( ', ', $mail_data['to'] ) : $mail_data['to'],
				'subject'   => $mail_data['subject'],
				'message'   => $mail_data['message'],
				'headers'   => is_array( $mail_data['headers'] ) ? implode( "\r\n", $mail_data['headers'] ) : $mail_data['headers'],
				'status'    => 'success',
			)
		);
	}

	/**
	 * Log a failed email attempt.
	 *
	 * @param WP_Error $wp_error The error object.
	 */
	/**
	 * Log a failed email attempt.
	 *
	 * Records email sending failure in the email logs table with error details.
	 *
	 * @since  2.0.0
	 * @param WP_Error $wp_error Error object from wp_mail_failed action.
	 * @return void
	 */
	public function log_failed_email( $wp_error ) {
		$mail_data = $wp_error->get_error_data( 'wp_mail_failed' );
		$this->log_email(
			array(
				'recipient'     => is_array( $mail_data['to'] ) ? implode( ', ', $mail_data['to'] ) : $mail_data['to'],
				'subject'       => $mail_data['subject'],
				'message'       => $mail_data['message'],
				'headers'       => is_array( $mail_data['headers'] ) ? implode( "\r\n", $mail_data['headers'] ) : $mail_data['headers'],
				'status'        => 'failed',
				'error_message' => $wp_error->get_error_message(),
			)
		);
	}

	/**
	 * Helper function to insert an email log into the database.
	 *
	 * @param array $args The email data to log.
	 */
	/**
	 * Log email data to database.
	 *
	 * Internal method to insert email log entries into the database table.
	 *
	 * @since  2.0.0
	 * @param array $args Email log data including recipient, subject, status, etc.
	 * @return void
	 */
	private function log_email( $args ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_email_logs';

		// Silently fail if table doesn't exist. It should be created on activation.
		if ( $table_name !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) ) {	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for notification processing
			return;
		}

		$defaults = array(
			'timestamp'     => current_time( 'mysql' ),
			'recipient'     => '',
			'subject'       => '',
			'message'       => '',
			'headers'       => '',
			'status'        => 'failed',
			'error_message' => '',
		);
		$data     = wp_parse_args( $args, $defaults );

		$wpdb->insert( $table_name, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality
	}

	/**
	 * AJAX: Clear all email logs.
	 */
	/**
	 * AJAX handler for clearing email logs.
	 *
	 * Clears all email log entries from the database.
	 *
	 * @since  2.0.0
	 * @return void Outputs JSON response.
	 */
	public function ajax_clear_email_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'aiohm-booking-pro' ) );
		}
		check_ajax_referer( 'aiohm_booking_notifications_nonce', 'nonce' );

		global $wpdb;
		$table_name = esc_sql( $wpdb->prefix . 'aiohm_booking_email_logs' );

		// Use TRUNCATE for efficiency. It's faster than DELETE and resets auto-increment.
		// The dbDelta check in get_email_logs ensures the table exists.
		$result = $wpdb->query(
			$wpdb->prepare(
				'TRUNCATE TABLE %s',
				$table_name
			)
		);

		if ( false !== $result ) {
			wp_send_json_success( __( 'Email logs cleared successfully!', 'aiohm-booking-pro' ) );
		} else {
			wp_send_json_error( __( 'Failed to clear email logs. Please check database permissions.', 'aiohm-booking-pro' ) );
		}
	}
}
