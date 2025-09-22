<?php
/**
 * Tickets Module
 *
 * Comprehensive ticket sales management for events, workshops, concerts, classes, conferences,
 * experiences, and activities with advanced booking features and customizable forms.
 *
 * @package AIOHM_Booking
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Tickets Module Class
 *
 * Handles ticket sales management for various event types including workshops,
 * concerts, conferences, seminars, webinars, classes, training sessions, meetups,
 * festivals, exhibitions, shows, and experiences.
 *
 * @since 2.0.0
 */
class AIOHM_BOOKING_Module_Tickets extends AIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Ticket settings data.
	 *
	 * @since 2.0.0
	 * @var array|null
	 */
	private $ticket_settings = null;

	/**
	 * Available ticket types.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private $ticket_types = array();

	/**
	 * Get module UI definition.
	 *
	 * Returns the configuration array that defines this module's appearance
	 * and behavior in the admin interface.
	 *
	 * @since 2.0.0
	 * @return array Module UI definition array.
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'tickets',
			'name'                => __( 'Event Tickets', 'aiohm-booking-pro' ),
			'description'         => __( 'Perfect for events, workshops, concerts, classes, conferences, experiences, and activities.', 'aiohm-booking-pro' ),
			'icon'                => '🎟️',
			'admin_page_slug'     => 'aiohm-booking-tickets',
			'category'            => 'booking',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => true,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Constructor.
	 *
	 * Initializes the tickets module by setting up admin page configuration
	 * and calling the parent constructor.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		// This is a PAGE module - enable admin page.
		$this->has_admin_page  = true;
		$this->admin_page_slug = 'aiohm-booking-tickets';

		$this->init();
	}

	/**
	 * Initialize the module.
	 *
	 * Sets up module configuration including settings section ID,
	 * page titles, and enables quick settings.
	 *
	 * @since 2.0.0
	 */
	public function init() {
		// Settings configuration.
		$this->settings_section_id = 'tickets';
		$this->settings_page_title = __( 'Event Tickets', 'aiohm-booking-pro' );
		$this->settings_tab_title  = __( 'Tickets Settings', 'aiohm-booking-pro' );
		$this->has_quick_settings  = true;

		// Initialize hooks for form handling.
		$this->init_hooks();

		// Listen for payment completion to update ticket availability
		add_action( 'aiohm_booking_payment_completed', array( $this, 'update_event_availability' ), 10, 3 );
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * Sets up action hooks for form submission handling and AJAX endpoints
	 * for ticket management functionality.
	 *
	 * @since 2.0.0
	 */
	protected function init_hooks() {
		$this->ticket_settings = $this->get_ticket_settings();
		$this->ticket_types    = $this->get_ticket_types();

		// Handle event details form submission.
		add_action( 'admin_init', array( $this, 'save_events_data' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_aiohm_booking_save_individual_event', array( $this, 'save_individual_event' ) );
		add_action( 'wp_ajax_aiohm_booking_delete_event', array( $this, 'ajax_delete_event' ) );
		add_action( 'wp_ajax_aiohm_booking_increment_event_count', array( $this, 'ajax_increment_event_count' ) );
		add_action( 'wp_ajax_aiohm_booking_update_ticket_stats', array( $this, 'ajax_update_ticket_stats' ) );

		// EventON integration AJAX handlers
		if ( class_exists( 'EventON' ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'eventON/eventon.php' ) ) ) {
			add_action( 'wp_ajax_aiohm_booking_get_eventon_events_list', array( $this, 'ajax_get_eventon_events_list' ) );
		}

		// Event booking submission handlers (for frontend checkout)
		add_action( 'wp_ajax_aiohm_booking_submit_event', array( $this, 'ajax_process_event_booking' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_submit_event', array( $this, 'ajax_process_event_booking' ) );

		// Only register the unified form settings handler once to avoid conflicts
		if ( ! has_action( 'wp_ajax_aiohm_save_form_settings', array( 'AIOHM_BOOKING_Form_Settings_Handler', 'save_unified_form_settings' ) ) ) {
			add_action( 'wp_ajax_aiohm_save_form_settings', array( 'AIOHM_BOOKING_Form_Settings_Handler', 'save_unified_form_settings' ) );
		}

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
	}

	/**
	 * Enqueue admin assets for the tickets module.
	 *
	 * Loads CSS and JavaScript files specifically for the tickets admin page,
	 * including settings scripts and localization data.
	 *
	 * @since 2.0.0
	 * @param string $hook The current admin page hook.
	 */
	public function admin_enqueue_assets( $hook ) {
		// Only load on our module page.
		if ( false === strpos( $hook, 'aiohm-booking-tickets' ) && false === strpos( $hook, 'aiohm-booking_page_aiohm-booking-tickets' ) ) {
			return;
		}

		// Enqueue WordPress media uploader scripts.
		wp_enqueue_media();

		// Initialize the module

		// Load WordPress color picker assets for form customization.
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );

		// Enqueue existing admin CSS (tickets styles are included in main admin CSS).
		wp_enqueue_style(
			'aiohm-booking-admin',
			AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-admin.css',
			array(),
			AIOHM_BOOKING_VERSION
		);

		// Enqueue main admin JS for basic functionality.
		wp_enqueue_script(
			'aiohm-booking-admin',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-admin.js',
			array( 'jquery', 'wp-media' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Also load settings admin JS for form customization functionality.
		wp_enqueue_script(
			'aiohm-booking-settings-admin',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-settings-admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Localize script for AJAX.
		wp_localize_script(
			'aiohm-booking-admin',
			'aiohm_booking_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
			)
		);

		// Localize settings script for form customization.
		$admin_vars = array(
			'ajax_url'             => admin_url( 'admin-ajax.php' ),
			'nonce'                => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
			'preview_nonce'        => wp_create_nonce( 'aiohm_booking_update_preview' ),
			'current_user_id'      => get_current_user_id(),
			'nonce_generated_time' => time(),
			'i18n'                 => array(
				'saving'                    => __( 'Saving...', 'aiohm-booking-pro' ),
				'saved'                     => __( 'Saved!', 'aiohm-booking-pro' ),
				'applyingChanges'           => __( 'Applying changes...', 'aiohm-booking-pro' ),
				'savingSettings'            => __( 'Saving settings...', 'aiohm-booking-pro' ),
				'testing'                   => __( 'Testing...', 'aiohm-booking-pro' ),
				'testingConnection'         => __( 'Testing connection...', 'aiohm-booking-pro' ),
				'connectionTestFailed'      => __( 'Connection test failed', 'aiohm-booking-pro' ),
				'saveFailed'                => __( 'Save failed', 'aiohm-booking-pro' ),
				'invalidAccommodationIndex' => __( 'Invalid accommodation index', 'aiohm-booking-pro' ),
				'errorPrefix'               => __( 'Error: ', 'aiohm-booking-pro' ),
				'settings_saved'            => __( 'Settings saved successfully!', 'aiohm-booking-pro' ),
				'apiKeySaved'               => __( 'API key saved successfully!', 'aiohm-booking-pro' ),
				'connectionSuccessful'      => __( 'Connection successful!', 'aiohm-booking-pro' ),
				'settings_error'            => __( 'Error saving settings. Please try again.', 'aiohm-booking-pro' ),
			),
		);
		wp_localize_script( 'aiohm-booking-settings-admin', 'aiohm_booking_admin', $admin_vars );
	}

	/**
	 * Get settings fields configuration.
	 *
	 * Returns an array of settings fields that define the ticket module's
	 * configuration options including event type, number of events, pricing,
	 * and private event settings.
	 *
	 * @since 2.0.0
	 * @return array Settings fields configuration array.
	 */
	public function get_settings_fields() {
		return array(
			'number_of_events' => array(
				'type'        => 'number',
				'label'       => 'Number of Events',
				'description' => 'Total number of events available',
				'default'     => 5,
				'min'         => 1,
				'max'         => 20,
			),
			'ticket_price'     => array(
				'type'        => 'text',
				'label'       => 'Base Ticket Price',
				'description' => 'Default ticket price for new events',
				'default'     => '25',
			),
		);
	}

	/**
	 * Get default settings values.
	 *
	 * Returns the default configuration values for the tickets module
	 * when no custom settings have been saved.
	 *
	 * @since 2.0.0
	 * @return array Default settings array.
	 */
	protected function get_default_settings() {
		return array();
	}

	/**
	 * Get module settings.
	 *
	 * Retrieves the current module settings, merging defaults with
	 * saved options from the database.
	 *
	 * @since 2.0.0
	 * @return array Current module settings array.
	 */
	public function get_module_settings() {
		return array_merge( $this->get_default_settings(), get_option( 'aiohm_booking_tickets_settings', array() ) );
	}

	/**
	 * Register admin page in WordPress menu.
	 *
	 * Note: Menu is now handled centrally in AIOHM_BOOKING_Admin class to control order.
	 * This method is kept for compatibility but doesn't add menu items.
	 *
	 * @since 2.0.0
	 */
	public function register_admin_page() {
		// Implementation handled by central admin menu class.
	}

	/**
	 * Render the admin page for the tickets module.
	 *
	 * Handles form submissions for both events data and form settings,
	 * then renders the complete events manager interface.
	 *
	 * @since 2.0.0
	 */
	public function render_admin_page() {
		// Handle form submission for events data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled in the condition below
		if ( isset( $_POST['aiohm_tickets_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aiohm_tickets_nonce'] ) ), 'aiohm_tickets_save' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition
			$this->save_events_data();
		}

		// Handle unified form customization submission (check both action and nonce-based submission).
		// Only handle non-AJAX requests here - AJAX requests are handled by wp_ajax_* hooks.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled within the condition below
		if ( ! empty( $_POST ) && ! defined( 'DOING_AJAX' ) && (
			( isset( $_POST['action'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'aiohm_save_form_settings' ) || // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
			( isset( $_POST['aiohm_form_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aiohm_form_settings_nonce'] ) ), 'aiohm_form_settings_save' ) && ( isset( $_POST['aiohm_booking_form_settings'] ) || isset( $_POST['aiohm_booking_tickets_form_settings'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition
		) ) {
			if ( isset( $_POST['aiohm_form_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aiohm_form_settings_nonce'] ) ), 'aiohm_booking_save_form_settings' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition
				AIOHM_BOOKING_Form_Settings_Handler::save_unified_form_settings();
			}
		}

		// Handle booking settings submission.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled in the condition below
		if ( isset( $_POST['form_submit'] ) && isset( $_POST['aiohm_booking_settings'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'aiohm_booking_settings' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition
			if ( current_user_can( 'manage_options' ) ) {
				// Save the booking settings.
				if ( isset( $_POST['aiohm_booking_settings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
					AIOHM_BOOKING_Settings::update_multiple( array_map( 'sanitize_text_field', wp_unslash( $_POST['aiohm_booking_settings'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
				}

				// Set transient for success message.
				set_transient( 'aiohm_booking_tickets_settings_saved', true, 60 );

				// Clear any output buffers to prevent issues
				if ( ob_get_level() ) {
					ob_end_clean();
				}

				// Redirect to prevent form resubmission and ensure fresh data.
				if ( ! headers_sent() ) {
					wp_safe_redirect( admin_url( 'admin.php?page=aiohm-booking-tickets' ) );
					exit;
				} else {
					// If headers already sent, show success message instead of redirect
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'aiohm-booking-pro' ) . '</p></div>';
				}
			}
		}

		// Show success message if settings were saved.
		if ( get_transient( 'aiohm_booking_tickets_settings_saved' ) ) {
			delete_transient( 'aiohm_booking_tickets_settings_saved' );
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Booking settings saved successfully!', 'aiohm-booking-pro' ) . '</p></div>';
				}
			);
		}

		// Render the tickets admin interface
		$this->render_tickets_admin_interface();

	}

	/**
	 * Render the tickets admin interface.
	 *
	 * Renders the complete admin interface for managing events and form customization.
	 *
	 * @since 2.0.0
	 */
	private function render_tickets_admin_interface() {
		?>
		<div class="aiohm-booking-admin">
			<!-- Header with Logo and Title -->
			<div class="aiohm-booking-admin-header">
				<div class="aiohm-booking-admin-header-content">
					<div class="aiohm-booking-admin-logo">
						<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
					</div>
					<div class="aiohm-booking-admin-header-text">
						<h1><?php esc_html_e( 'Event Tickets Management', 'aiohm-booking-pro' ); ?></h1>
						<p class="aiohm-booking-admin-tagline"><?php esc_html_e( 'Manage your events and customize the booking form.', 'aiohm-booking-pro' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Stats Cards -->
			<div class="aiohm-booking-admin-card">
				<h3><?php esc_html_e( 'Event Statistics', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-booking-orders-stats">
					<?php
					// Get events data for statistics
					$events_data = get_option( 'aiohm_booking_events_data', array() );
					$total_events = count( $events_data );

					// Calculate upcoming events (events with dates in the future)
					$upcoming_events = 0;
					$current_date = current_time( 'Y-m-d' );

					foreach ( $events_data as $event ) {
						if ( ! empty( $event['event_date'] ) && $event['event_date'] >= $current_date ) {
							$upcoming_events++;
						}
					}

					// Calculate total available seats
					$total_seats = 0;
					foreach ( $events_data as $event ) {
						if ( ! empty( $event['available_seats'] ) ) {
							$total_seats += intval( $event['available_seats'] );
						}
					}
					?>
					<div class="aiohm-booking-orders-stat">
						<div class="number"><?php echo esc_html( $total_events ); ?></div>
						<div class="label"><?php esc_html_e( 'Total Events', 'aiohm-booking-pro' ); ?></div>
					</div>
					<div class="aiohm-booking-orders-stat">
						<div class="number"><?php echo esc_html( $upcoming_events ); ?></div>
						<div class="label"><?php esc_html_e( 'Upcoming Events', 'aiohm-booking-pro' ); ?></div>
					</div>
					<div class="aiohm-booking-orders-stat">
						<div class="number"><?php echo esc_html( $total_seats ); ?></div>
						<div class="label"><?php esc_html_e( 'Total Seats', 'aiohm-booking-pro' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Booking Settings Section -->
			<?php $this->render_booking_settings_section(); ?>

			<!-- Event Management Section -->
			<div class="aiohm-booking-admin-card">
				<h3><?php esc_html_e( 'Event Management', 'aiohm-booking-pro' ); ?></h3>
				<p><?php esc_html_e( 'Create and manage your events. Each event can have its own pricing, dates, and configuration.', 'aiohm-booking-pro' ); ?></p>

				<form method="post" action="">
				<?php wp_nonce_field( 'aiohm_tickets_save', 'aiohm_tickets_nonce' ); ?>
				<?php
				// Get saved events data
				$events_data = get_option( 'aiohm_booking_events_data', array() );
				$num_events = count( $events_data );

				// Render events management interface
				$this->render_event_boxes( $events_data, $num_events );
				?>
				<div class="aiohm-form-actions">
					<button type="submit" class="button button-primary">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Events', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
				</form>
			</div>

			<!-- Form Customization Section -->
			<?php
			// Render form customization interface
			$this->render_form_customization_template();
			?>
		</div>
		<?php
	}

	/**
	 * Render the form customization template.
	 *
	 * Displays the form customization interface that allows configuration
	 * of the booking form fields, titles, and field ordering.
	 *
	 * @since 2.0.0
	 */
	private function render_form_customization_template() {

		// Use tickets-specific form settings.
		$form_data = get_option( 'aiohm_booking_tickets_form_settings', array() );

		// Get default colors from existing user settings (global settings or legacy accommodation settings).
		$global_settings               = AIOHM_BOOKING_Settings::get_all();
		$legacy_accommodation_settings = get_option( 'aiohm_booking_accommodation_form_settings', array() );
		$legacy_tickets_settings       = get_option( 'aiohm_booking_tickets_form_settings', array() );

		// Use existing user brand/font colors as defaults, prioritizing any existing data.
		$default_brand_color = $global_settings['brand_color'] ??
								$legacy_accommodation_settings['form_primary_color'] ??
								$legacy_tickets_settings['form_primary_color'] ?? '#457d59';
		$default_font_color  = $global_settings['font_color'] ??
								$legacy_accommodation_settings['form_text_color'] ??
								$legacy_tickets_settings['form_text_color'] ?? '#333333';

		// Load form field settings from tickets form settings (not global settings)
		$form_field_defaults = array(
			'form_field_address'                       => $form_data['form_field_address'] ?? false,
			'form_field_address_required'              => $form_data['form_field_address_required'] ?? false,
			'form_field_country'                       => $form_data['form_field_country'] ?? false,
			'form_field_country_required'              => $form_data['form_field_country_required'] ?? false,
			'form_field_age'                           => $form_data['form_field_age'] ?? false,
			'form_field_age_required'                  => $form_data['form_field_age_required'] ?? false,
			'form_field_company'                       => $form_data['form_field_company'] ?? false,
			'form_field_company_required'              => $form_data['form_field_company_required'] ?? false,
			'form_field_phone'                         => $form_data['form_field_phone'] ?? false,
			'form_field_phone_required'                => $form_data['form_field_phone_required'] ?? false,
			'form_field_arrival_time'                  => $form_data['form_field_arrival_time'] ?? false,
			'form_field_arrival_time_required'         => $form_data['form_field_arrival_time_required'] ?? false,
			'form_field_purpose'                       => $form_data['form_field_purpose'] ?? false,
			'form_field_purpose_required'              => $form_data['form_field_purpose_required'] ?? false,
			'form_field_vat'                           => $form_data['form_field_vat'] ?? false,
			'form_field_vat_required'                  => $form_data['form_field_vat_required'] ?? false,
			'form_field_dietary_requirements'          => $form_data['form_field_dietary_requirements'] ?? false,
			'form_field_dietary_requirements_required' => $form_data['form_field_dietary_requirements_required'] ?? false,
			'form_field_accessibility_needs'           => $form_data['form_field_accessibility_needs'] ?? false,
			'form_field_accessibility_needs_required'  => $form_data['form_field_accessibility_needs_required'] ?? false,
			'form_field_emergency_contact'             => $form_data['form_field_emergency_contact'] ?? false,
			'form_field_emergency_contact_required'    => $form_data['form_field_emergency_contact_required'] ?? false,
			'form_field_special_requests'              => $form_data['form_field_special_requests'] ?? false,
			'form_field_special_requests_required'     => $form_data['form_field_special_requests_required'] ?? false,
			'form_field_departure_time'                => $form_data['form_field_departure_time'] ?? false,
			'form_field_departure_time_required'       => $form_data['form_field_departure_time_required'] ?? false,
			'form_field_nationality'                   => $form_data['form_field_nationality'] ?? false,
			'form_field_nationality_required'          => $form_data['form_field_nationality_required'] ?? false,
			'field_order'                              => $form_data['field_order'] ?? array( 'address', 'country', 'age', 'company', 'phone', 'arrival_time', 'departure_time', 'purpose', 'nationality', 'vat', 'dietary_requirements', 'accessibility_needs', 'emergency_contact', 'special_requests' ),
		);

		$form_data = array_merge(
			array(
				'form_primary_color'   => $default_brand_color,
				'form_text_color'      => $default_font_color,
				'brand_color'          => $default_brand_color, // Template expects this key.
				'font_color'           => $default_font_color,  // Template expects this key.
				'thankyou_page_url'    => '',
				'allow_group_bookings' => '0', // Default to disabled
			),
			$form_field_defaults, // Include the loaded form field settings
			$form_data // Tickets-specific settings override defaults
		);

		// Fix: If tickets-specific field_order is empty, don't override the global/default field_order
		if ( isset( $form_data['field_order'] ) && ( empty( $form_data['field_order'] ) || ( is_array( $form_data['field_order'] ) && count( $form_data['field_order'] ) === 0 ) ) ) {
			// Use the field_order from form_field_defaults (which comes from global settings)
			if ( isset( $form_field_defaults['field_order'] ) ) {
				$form_data['field_order'] = $form_field_defaults['field_order'];
			}
		}

		// Ensure saved color values override defaults and sync between different key formats.
		// The template saves as form_primary_color but displays from brand_color.
		if ( ! empty( $form_data['form_primary_color'] ) ) {
			$form_data['brand_color'] = $form_data['form_primary_color'];
		} else {
			// If no saved form_primary_color, use the brand_color for both.
			$form_data['form_primary_color'] = $form_data['brand_color'];
		}

		if ( ! empty( $form_data['form_text_color'] ) ) {
			$form_data['font_color'] = $form_data['form_text_color'];
		} else {
			// If no saved form_text_color, use the font_color for both.
			$form_data['form_text_color'] = $form_data['font_color'];
		}

		// Get centralized field definitions.
		$fields_definition = $this->get_centralized_field_definitions( 'tickets' );

		// Populate form_field_* values from centralized field definitions
		foreach ( array_keys( $fields_definition ) as $field_key ) {
			// Handle field enabled status - check for '1', 'true', or true
			$field_enabled_value                     = $form_data[ 'form_field_' . $field_key ] ?? $fields_definition[ $field_key ]['default_enabled'];
			$form_data[ 'form_field_' . $field_key ] = $this->normalize_field_value( $field_enabled_value );

			// Handle field required status - check for '1', 'true', or true
			$field_required_value                                  = $form_data[ 'form_field_' . $field_key . '_required' ] ?? $fields_definition[ $field_key ]['required_default'];
			$form_data[ 'form_field_' . $field_key . '_required' ] = $this->normalize_field_value( $field_required_value );
		}

		// Handle field order
		$field_order = $form_data['field_order'] ?? array_keys( $fields_definition );
		if ( ! is_array( $field_order ) ) {
			$field_order = ! empty( $field_order ) ? explode( ',', $field_order ) : array_keys( $fields_definition );
		}
		$form_data['field_order'] = $field_order;

		$template_data = array(
			'form_type'           => 'tickets',
			'section_title'       => 'Event Tickets Booking Form Customization',
			'section_description' => 'Customize the appearance and fields of your [aiohm_booking] shortcode form (events mode)',
			'form_data'           => $form_data,
			'fields_definition'   => $fields_definition,
			'shortcode_preview'   => '[aiohm_booking enable_tickets="true" enable_accommodations="false"]',
			'nonce_action'        => 'aiohm_booking_save_form_settings',
			'nonce_name'          => 'aiohm_form_settings_nonce',
			'option_name'         => 'aiohm_booking_tickets_form_settings',
		);

		// Load the template using helper method.
		AIOHM_BOOKING_Template_Helper::instance()->render_form_customization_template( $template_data );
	}

	/**
	 * Get centralized field definitions based on module type.
	 *
	 * Returns field definitions filtered by the specified module type.
	 * Fields can be:
	 * - 'general': Available to all modules
	 * - 'tickets': Specific to tickets/events module
	 * - 'accommodation': Specific to accommodation module
	 *
	 * @since 2.0.0
	 * @param string $module_type The module type ('tickets', 'accommodation', 'general').
	 * @return array Filtered field definitions.
	 */
	private function get_centralized_field_definitions( $module_type = 'general' ) {
		// Centralized field registry.
		$all_fields = array(
			// General fields (available to all modules).
			'address'              => array(
				'label'            => 'Address',
				'description'      => 'Full address information',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => true,
				'required_default' => false,
			),
			'country'              => array(
				'label'            => 'Country',
				'description'      => 'Country of residence',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => true,
				'required_default' => false,
			),
			'age'                  => array(
				'label'            => 'Age',
				'description'      => 'Age information',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'company'              => array(
				'label'            => 'Company',
				'description'      => 'Business or organization name',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => true,
				'required_default' => false,
			),
			'phone'                => array(
				'label'            => 'Phone Number',
				'description'      => 'Contact phone number',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => true,
				'required_default' => false,
			),
			'arrival_time'         => array(
				'label'            => 'Arrival Time',
				'description'      => 'Expected arrival time',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'purpose'              => array(
				'label'            => 'Purpose',
				'description'      => 'Purpose of visit/stay',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'vat'                  => array(
				'label'            => 'VAT Number',
				'description'      => 'VAT number for business purposes',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'special_requests'     => array(
				'label'            => 'Special Requests',
				'description'      => 'Additional requests or notes',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => true,
				'required_default' => false,
			),

			// Shared fields available in both tickets and accommodation.
			'dietary_requirements' => array(
				'label'            => 'Dietary Requirements',
				'description'      => 'Special dietary needs and food allergies',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'accessibility_needs'  => array(
				'label'            => 'Accessibility Needs',
				'description'      => 'Special accessibility requirements',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'emergency_contact'    => array(
				'label'            => 'Emergency Contact',
				'description'      => 'Emergency contact name and phone number',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),

			// Additional shared fields from accommodation module.
			'departure_time'       => array(
				'label'            => 'Departure Time',
				'description'      => 'Expected departure time',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
			'nationality'          => array(
				'label'            => 'Nationality',
				'description'      => 'Nationality information',
				'modules'          => array( 'general', 'tickets', 'accommodation' ),
				'default_enabled'  => false,
				'required_default' => false,
			),
		);

		// Filter fields based on module type.
		$filtered_fields = array();
		foreach ( $all_fields as $field_key => $field_config ) {
			// Include field if it's available to the requested module or is general.
			if ( in_array( $module_type, $field_config['modules'], true ) || in_array( 'general', $field_config['modules'], true ) ) {
				$filtered_fields[ $field_key ] = array(
					'label'            => $field_config['label'],
					'description'      => $field_config['description'],
					'default_enabled'  => $field_config['default_enabled'],
					'required_default' => $field_config['required_default'],
				);
			}
		}

		return $filtered_fields;
	}

	/**
	 * Get ticket statistics.
	 *
	 * Calculates and returns statistics about tickets including total seats
	 * available, tickets sold, events today, and upcoming events.
	 *
	 * @since 2.0.0
	 * @return array Array containing comprehensive event statistics.
	 */
	private function get_ticket_stats() {
		global $wpdb;
		$events_data = get_option( 'aiohm_booking_events_data', array() );
		$today       = current_time( 'Y-m-d' );

		$total_seats     = 0;
		$events_today    = 0;
		$upcoming_events = 0;

		foreach ( $events_data as $event ) {
			$total_seats += intval( $event['available_seats'] ?? 0 );

			if ( ! empty( $event['event_date'] ) ) {
				if ( $event['event_date'] === $today ) {
					++$events_today;
				}
				if ( $event['event_date'] >= $today ) {
					++$upcoming_events;
				}
			}
		}

		// Check if orders table exists before querying.
		$table_name = $wpdb->prefix . 'aiohm_booking_order';
		if ( $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) ) {	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket lookup
			$tickets_sold = $wpdb->get_var( $wpdb->prepare( 'SELECT SUM(tickets_qty) FROM ' . $wpdb->prefix . 'aiohm_booking_order WHERE status = %s', 'paid' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
		} else {
			$tickets_sold = 0;
		}

		return array(
			'total_seats'     => $total_seats,
			'tickets_sold'    => intval( $tickets_sold ),
			'events_today'    => $events_today,
			'upcoming_events' => $upcoming_events,
		);
	}

	/**
	 * AJAX handler for updating ticket statistics.
	 *
	 * Returns current ticket statistics as JSON response for dynamic
	 * updates in the admin interface.
	 *
	 * @since 2.0.0
	 */
	public function ajax_update_ticket_stats() {
		wp_send_json_success( $this->get_ticket_stats() );
	}

	/**
	 * AJAX handler for saving individual event data.
	 *
	 * Processes AJAX requests to save individual event configurations,
	 * including validation, sanitization, and database updates.
	 *
	 * @since 2.0.0
	 */
	public function save_individual_event() {
		if ( ! check_ajax_referer( 'aiohm_booking_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to save events.' ) );
			return;
		}

		$event_index = isset( $_POST['event_index'] ) ? intval( wp_unslash( $_POST['event_index'] ) ) : -1; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with check_ajax_referer
		$posted_events = isset( $_POST['events'] ) && is_array( $_POST['events'] ) ? wp_unslash( $_POST['events'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified above, data sanitized in processing loop

		if ( $event_index < 0 || ! isset( $posted_events[ $event_index ] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid event data.' ) );
			return;
		}

		$event_data = $posted_events[ $event_index ];

		$events = get_option( 'aiohm_booking_events_data', array() );

		// Sanitize teachers data separately as it contains URLs and needs specific handling.
		$teachers = isset( $event_data['teachers'] ) && is_array( $event_data['teachers'] ) ? $this->sanitize_teachers_data( $event_data['teachers'] ) : array();

		$events[ $event_index ] = array(
			'title'              => substr( sanitize_text_field( $event_data['title'] ?? '' ), 0, 50 ),
			'description'        => substr( sanitize_textarea_field( $event_data['description'] ?? '' ), 0, 150 ),
			'teachers'           => $teachers,
			'location'           => sanitize_text_field( $event_data['location'] ?? '' ),
			'event_date'         => sanitize_text_field( $event_data['event_date'] ?? '' ),
			'event_time'         => sanitize_text_field( $event_data['event_time'] ?? '' ),
			'event_end_date'     => sanitize_text_field( $event_data['event_end_date'] ?? '' ),
			'event_end_time'     => sanitize_text_field( $event_data['event_end_time'] ?? '' ),
			'available_seats'    => intval( $event_data['available_seats'] ?? 50 ),
			'price'              => floatval( $event_data['price'] ?? 0 ),
			'early_bird_price'   => floatval( $event_data['early_bird_price'] ?? 0 ),
			'early_bird_date'    => sanitize_text_field( $event_data['early_bird_date'] ?? '' ),
			'early_bird_days'    => intval( $event_data['early_bird_days'] ?? 0 ),
			'deposit_percentage' => intval( $event_data['deposit_percentage'] ?? 0 ),
			'event_type'         => sanitize_text_field( $event_data['event_type'] ?? '' ),
		);

		update_option( 'aiohm_booking_events_data', $events );

		wp_send_json_success( array( 'message' => 'Event saved successfully.' ) );
	}

	/**
	 * AJAX handler for deleting an event.
	 *
	 * @since 1.2.6
	 */
	public function ajax_delete_event() {
		if ( ! check_ajax_referer( 'aiohm_booking_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to delete events.' ) );
			return;
		}

		$event_index = isset( $_POST['event_index'] ) ? intval( wp_unslash( $_POST['event_index'] ) ) : -1; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with check_ajax_referer

		if ( $event_index < 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid event index.' ) );
			return;
		}

		// Delete from events data option.
		$events_data = get_option( 'aiohm_booking_events_data', array() );
		if ( isset( $events_data[ $event_index ] ) ) {
			// Remove the event at the specified index.
			array_splice( $events_data, $event_index, 1 );
			update_option( 'aiohm_booking_events_data', $events_data );
		}

		// Decrement the number of events in global settings.
		$global_settings = AIOHM_BOOKING_Settings::get_all();
		$num_events      = intval( $global_settings['number_of_events'] ?? 0 );
		if ( $num_events > 0 ) {
			$global_settings['number_of_events'] = $num_events - 1;
			AIOHM_BOOKING_Settings::update( $global_settings );
		}

		wp_send_json_success( array( 'message' => 'Event deleted successfully.' ) );
	}

	/**
	 * AJAX handler for incrementing the event count.
	 *
	 * @since 1.2.6
	 */
	public function ajax_increment_event_count() {
		if ( ! check_ajax_referer( 'aiohm_booking_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to modify event count.' ) );
			return;
		}

		// Increment the number of events in global settings.
		$global_settings = AIOHM_BOOKING_Settings::get_all();
		$num_events      = intval( $global_settings['number_of_events'] ?? 0 );

		// Get max from settings definition.
		$settings_fields = $this->get_settings_fields();
		$max_events      = $settings_fields['number_of_events']['max'] ?? 20;

		if ( $num_events >= $max_events ) {
			wp_send_json_error( array( 'message' => 'Maximum number of events (' . $max_events . ') reached.' ) );
			return;
		}

		$global_settings['number_of_events'] = $num_events + 1;
		AIOHM_BOOKING_Settings::update( $global_settings );

		// We don't need to add an empty event to the data array, as the clone happens on the client-side.
		// The next full save will persist the cloned data.
		wp_send_json_success( array( 'message' => 'Event count incremented successfully.' ) );
	}

	/**
	 * Sanitize teachers data array.
	 *
	 * @since 2.0.0
	 * @param array $teachers Raw teachers data.
	 * @return array Sanitized teachers data.
	 */
	private function sanitize_teachers_data( $teachers ) {
		if ( ! is_array( $teachers ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $teachers as $teacher ) {
			if ( is_array( $teacher ) ) {
				$photo_url = esc_url_raw( $teacher['photo'] ?? '' );

				// Force HTTPS for image URLs to prevent mixed content issues on SSL sites.
				if ( is_ssl() && ! empty( $photo_url ) ) {
					$photo_url = set_url_scheme( $photo_url, 'https' );
				}
				$sanitized[] = array(
					'name'  => substr( sanitize_text_field( $teacher['name'] ?? '' ), 0, 50 ),
					'photo' => $photo_url,
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Render event configuration boxes.
	 *
	 * Creates the specified number of event configuration forms
	 * using the provided events data.
	 *
	 * @since 2.0.0
	 * @param array $events_data Existing event configuration data.
	 * @param int   $num_events  Number of events to render.
	 */
	private function render_event_boxes( $events_data, $num_events ) {
		for ( $i = 0; $i < $num_events; $i++ ) {
			$event = $events_data[ $i ] ?? array();
			$this->render_single_event_box( $i, $event );
		}
	}

	/**
	 * Render a single event configuration box.
	 *
	 * Creates a clean, organized form for configuring individual event
	 * details including title, description, location, date, time, and pricing.
	 *
	 * @since 2.0.0
	 * @param int   $index Event index number.
	 * @param array $event Event configuration data.
	 */
	private function render_single_event_box( $index, $event = array() ) {
		$defaults = array(
			'event_type'         => '',
			'title'              => '',
			'description'        => '',
			'location'           => '',
			'event_date'         => '',
			'event_time'         => '',
			'event_end_date'     => '',
			'event_end_time'     => '',
			'available_seats'    => '',
			'price'              => '',
			'early_bird_price'   => '',
			'early_bird_date'    => '',
			'early_bird_days'    => '',
			'deposit_percentage' => '',
		);

		$event = array_merge( $defaults, $event );

		// Get currency symbol from settings.
		$currency         = $this->get_currency_setting();
		$currency_data    = array(
			'USD' => array(
				'symbol' => '$',
				'title'  => 'US Dollar',
			),
			'EUR' => array(
				'symbol' => '€',
				'title'  => 'Euro',
			),
			'GBP' => array(
				'symbol' => '£',
				'title'  => 'British Pound',
			),
			'RON' => array(
				'symbol' => 'RON',
				'title'  => 'Romanian Leu',
			),
			'JPY' => array(
				'symbol' => '¥',
				'title'  => 'Japanese Yen',
			),
			'CAD' => array(
				'symbol' => 'C$',
				'title'  => 'Canadian Dollar',
			),
			'AUD' => array(
				'symbol' => 'A$',
				'title'  => 'Australian Dollar',
			),
		);
		$current_currency = $currency_data[ $currency ] ?? array(
			'symbol' => '€',
			'title'  => 'Euro',
		);
		$currency_symbol  = $current_currency['symbol'];
		?>
		<div class="aiohm-booking-event-settings aiohm-booking-admin-card" data-event-index="<?php echo esc_attr( $index ); ?>">
			<!-- Event Header -->
			<div class="aiohm-card-header aiohm-event-card-header">
				<div class="aiohm-card-header-title">
					<?php if ( ! empty( $event['event_date'] ) ) : ?>
						<div class="aiohm-event-date-display">
							<?php
							// Display full date format: "18 September 2025"
							$date_obj  = new DateTime( $event['event_date'] );
							$full_date = $date_obj->format( 'j F Y' ); // Day, full month name, year
							echo esc_html( $full_date );
							?>
						</div>
					<?php endif; ?>
					<h3 class="aiohm-card-title">
						<?php
						if ( ! empty( $event['title'] ) ) {
							echo esc_html( $event['title'] );
						} else {
							/* translators: %d: event number */
							printf( esc_html__( 'Event #%d', 'aiohm-booking-pro' ), intval( $index ) + 1 );
						}
						?>
					</h3>
				</div>
				<div class="aiohm-card-header-actions">
					<button type="button" class="aiohm-event-toggle-btn" data-event-index="<?php echo esc_attr( $index ); ?>" aria-label="<?php esc_attr_e( 'Collapse event details', 'aiohm-booking-pro' ); ?>" aria-expanded="true">
						<span class="dashicons dashicons-arrow-up-alt2 aiohm-toggle-icon"></span>
					</button>
				</div>
			</div>
			
			<div class="aiohm-card-body">
				<!-- Event Content - 2 Column Layout -->
				<div class="aiohm-columns-2">
					<!-- Left Column -->
					<div class="aiohm-column">
						<div class="aiohm-form-group">
							<label><?php esc_html_e( 'Event Type', 'aiohm-booking-pro' ); ?></label>
							<input type="text" name="events[<?php echo esc_attr( $index ); ?>][event_type]" value="<?php echo esc_attr( $event['event_type'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., Concert, Workshop, Conference', 'aiohm-booking-pro' ); ?>">
						</div>

						<div class="aiohm-form-group">
							<label><?php esc_html_e( 'Event Title', 'aiohm-booking-pro' ); ?> <span class="aiohm-char-limit">(max 50 chars)</span></label>
							<input type="text" name="events[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $event['title'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., Summer Music Festival', 'aiohm-booking-pro' ); ?>" maxlength="50" class="aiohm-char-limited aiohm-event-title-input">
							<div class="aiohm-char-counter" data-max="50">
								<span class="aiohm-char-current"><?php echo esc_html( strlen( $event['title'] ) ); ?></span>/50
							</div>
						</div>

						<div class="aiohm-form-group">
							<label><?php esc_html_e( 'Event Description', 'aiohm-booking-pro' ); ?> <span class="aiohm-char-limit">(max 150 chars)</span></label>
							<textarea name="events[<?php echo esc_attr( $index ); ?>][description]" rows="3" placeholder="<?php esc_attr_e( 'Brief description of your event...', 'aiohm-booking-pro' ); ?>" maxlength="150" class="aiohm-char-limited"><?php echo esc_textarea( $event['description'] ); ?></textarea>
							<div class="aiohm-char-counter" data-max="150">
								<span class="aiohm-char-current"><?php echo esc_html( strlen( $event['description'] ) ); ?></span>/150
							</div>
						</div>

						<!-- Teacher Information Section -->
						<div class="aiohm-teachers-section">
							<label><?php esc_html_e( 'Event Teachers', 'aiohm-booking-pro' ); ?></label>
							<div class="aiohm-teachers-container" id="teachers-container-<?php echo esc_attr( $index ); ?>">
								<?php
								$teachers = $event['teachers'] ?? array();
								if ( empty( $teachers ) && ( ! empty( $event['teacher_name'] ) || ! empty( $event['teacher_photo'] ) ) ) {
									// Migrate old single teacher data to new format
									$teachers[] = array(
										'name'  => $event['teacher_name'] ?? '',
										'photo' => $event['teacher_photo'] ?? '',
									);
								}
								$teacher_count = count( $teachers );
								?>

								<?php if ( $teacher_count > 0 ) : ?>
									<?php foreach ( $teachers as $teacher_index => $teacher ) : ?>
									<div class="aiohm-teacher-item" data-teacher-index="<?php echo esc_attr( $teacher_index ); ?>">
										<div class="aiohm-teacher-header">
											<h4>
											<?php
												/* translators: %d: Teacher number */
												printf( esc_html__( 'Teacher %d', 'aiohm-booking-pro' ), esc_html( $teacher_index + 1 ) );
											?>
											</h4>
											<button type="button" class="aiohm-remove-teacher-btn" data-event-index="<?php echo esc_attr( $index ); ?>" data-teacher-index="<?php echo esc_attr( $teacher_index ); ?>">
												<span class="dashicons dashicons-no"></span>
											</button>
										</div>

										<div class="aiohm-teacher-fields">
											<div class="aiohm-form-group">
												<label><?php esc_html_e( 'Name', 'aiohm-booking-pro' ); ?> <span class="aiohm-char-limit">(max 50 chars)</span></label>
												<input type="text" name="events[<?php echo esc_attr( $index ); ?>][teachers][<?php echo esc_attr( $teacher_index ); ?>][name]" value="<?php echo esc_attr( $teacher['name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., John Smith', 'aiohm-booking-pro' ); ?>" maxlength="50" class="aiohm-char-limited">
												<div class="aiohm-char-counter" data-max="50">
													<span class="aiohm-char-current"><?php echo esc_html( strlen( $teacher['name'] ?? '' ) ); ?></span>/50
												</div>
											</div>

											<div class="aiohm-form-group">
												<label><?php esc_html_e( 'Photo', 'aiohm-booking-pro' ); ?></label>
												<div class="aiohm-teacher-photo-upload">
													<div class="aiohm-photo-preview <?php echo empty( $teacher['photo'] ) ? 'aiohm-hidden' : ''; ?>" id="teacher-photo-preview-<?php echo esc_attr( $index ); ?>-<?php echo esc_attr( $teacher_index ); ?>">
														<img src="<?php echo esc_url( $teacher['photo'] ?? '' ); ?>" alt="<?php echo esc_attr( $teacher['name'] ?? __( 'Teacher', 'aiohm-booking-pro' ) ); ?>" class="aiohm-teacher-photo-img">
														<button type="button" class="aiohm-remove-photo-btn" data-event-index="<?php echo esc_attr( $index ); ?>" data-teacher-index="<?php echo esc_attr( $teacher_index ); ?>">
															<span class="dashicons dashicons-no"></span>
														</button>
													</div>
													<div class="aiohm-photo-upload-controls <?php echo ! empty( $teacher['photo'] ) ? 'aiohm-hidden' : ''; ?>" id="teacher-photo-upload-<?php echo esc_attr( $index ); ?>-<?php echo esc_attr( $teacher_index ); ?>">
														<input type="hidden" name="events[<?php echo esc_attr( $index ); ?>][teachers][<?php echo esc_attr( $teacher_index ); ?>][photo]" value="<?php echo esc_attr( $teacher['photo'] ?? '' ); ?>" id="teacher-photo-input-<?php echo esc_attr( $index ); ?>-<?php echo esc_attr( $teacher_index ); ?>">
														<button type="button" class="button aiohm-upload-photo-btn" data-event-index="<?php echo esc_attr( $index ); ?>" data-teacher-index="<?php echo esc_attr( $teacher_index ); ?>">
															<span class="dashicons dashicons-camera"></span>
															<?php esc_html_e( 'Upload Photo', 'aiohm-booking-pro' ); ?>
														</button>
														<p class="aiohm-photo-hint"><?php esc_html_e( 'Recommended: 200x200px, JPG/PNG format', 'aiohm-booking-pro' ); ?></p>
													</div>
												</div>
											</div>
										</div>
									</div>
									<?php endforeach; ?>
								<?php else : ?>
									<!-- Empty state - will be populated by JavaScript -->
									<div class="aiohm-no-teachers">
										<p><?php esc_html_e( 'No teachers added yet. Click "Add Teacher" to get started.', 'aiohm-booking-pro' ); ?></p>
									</div>
								<?php endif; ?>
							</div>

							<div class="aiohm-teachers-actions">
								<button type="button" class="button aiohm-add-teacher-btn" data-event-index="<?php echo esc_attr( $index ); ?>">
									<span class="dashicons dashicons-plus"></span>
									<?php esc_html_e( 'Add Teacher', 'aiohm-booking-pro' ); ?>
								</button>
							</div>
						</div>

					</div>

					<!-- Right Column -->
					<div class="aiohm-column">
						<div class="aiohm-columns-2">
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Event Date', 'aiohm-booking-pro' ); ?></label>
								<input type="date" name="events[<?php echo esc_attr( $index ); ?>][event_date]" value="<?php echo esc_attr( $event['event_date'] ); ?>">
							</div>
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Event Time', 'aiohm-booking-pro' ); ?></label>
								<input type="time" name="events[<?php echo esc_attr( $index ); ?>][event_time]" value="<?php echo esc_attr( $event['event_time'] ); ?>">
							</div>
						</div>

						<div class="aiohm-columns-2">
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Event End Date', 'aiohm-booking-pro' ); ?></label>
								<input type="date" name="events[<?php echo esc_attr( $index ); ?>][event_end_date]" value="<?php echo esc_attr( $event['event_end_date'] ); ?>">
							</div>
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Event End Time', 'aiohm-booking-pro' ); ?></label>
								<input type="time" name="events[<?php echo esc_attr( $index ); ?>][event_end_time]" value="<?php echo esc_attr( $event['event_end_time'] ); ?>">
							</div>
						</div>

						<div class="aiohm-columns-2">
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Available Seats', 'aiohm-booking-pro' ); ?></label>
								<input type="number" name="events[<?php echo esc_attr( $index ); ?>][available_seats]" value="<?php echo esc_attr( $event['available_seats'] ); ?>" min="1" max="10000" class="aiohm-number-input">
							</div>
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Price', 'aiohm-booking-pro' ); ?> (<span class="currency-symbol" title="<?php echo esc_attr( $current_currency['title'] ); ?>"><?php echo esc_html( $currency_symbol ); ?></span>)</label>
								<input type="number" name="events[<?php echo esc_attr( $index ); ?>][price]" value="<?php echo esc_attr( $event['price'] ); ?>" step="0.01" min="0" class="aiohm-number-input">
							</div>
						</div>

						<div class="aiohm-early-bird-section">
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Early Bird Price', 'aiohm-booking-pro' ); ?> (<span class="currency-symbol" title="<?php echo esc_attr( $current_currency['title'] ); ?>"><?php echo esc_html( $currency_symbol ); ?></span>)</label>
								<input type="number" name="events[<?php echo esc_attr( $index ); ?>][early_bird_price]" value="<?php echo esc_attr( $event['early_bird_price'] ); ?>" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Optional', 'aiohm-booking-pro' ); ?>" class="aiohm-number-input">
							</div>
						</div>

						<div class="aiohm-columns-2">
							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Early Bird Days Before Event', 'aiohm-booking-pro' ); ?></label>
								<input type="number" name="events[<?php echo esc_attr( $index ); ?>][early_bird_days]" value="<?php echo esc_attr( $event['early_bird_days'] ?? '' ); ?>" min="1" max="365" step="1" placeholder="<?php esc_attr_e( 'e.g. 30', 'aiohm-booking-pro' ); ?>" class="aiohm-number-input">
								<small><?php esc_html_e( 'Number of days before the event when early bird pricing ends', 'aiohm-booking-pro' ); ?></small>
							</div>

							<div class="aiohm-form-group">
								<label><?php esc_html_e( 'Deposit Percentage', 'aiohm-booking-pro' ); ?></label>
								<input type="number" name="events[<?php echo esc_attr( $index ); ?>][deposit_percentage]" value="<?php echo esc_attr( $event['deposit_percentage'] ?? '' ); ?>" min="0" max="100" step="1" placeholder="<?php esc_attr_e( 'e.g. 30', 'aiohm-booking-pro' ); ?>" class="aiohm-number-input">
								<small><?php esc_html_e( 'Percentage of total price required as deposit', 'aiohm-booking-pro' ); ?></small>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="aiohm-booking-card-event-footer">
				<!-- Action Buttons Group -->
				<div class="aiohm-button-group aiohm-action-buttons">
					<button type="button" class="button button-primary aiohm-individual-save-btn aiohm-action-btn" data-event-index="<?php echo esc_attr( $index ); ?>" title="<?php esc_attr_e( 'Save this event with all current settings', 'aiohm-booking-pro' ); ?>">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Event', 'aiohm-booking-pro' ); ?>
					</button>
					
					<button type="button" class="button aiohm-clone-event-btn aiohm-action-btn" data-event-index="<?php echo esc_attr( $index ); ?>" title="<?php esc_attr_e( 'Create a copy of this event with all settings', 'aiohm-booking-pro' ); ?>">
						<span class="dashicons dashicons-admin-page"></span>
						<?php esc_html_e( 'Clone Event', 'aiohm-booking-pro' ); ?>
					</button>
					
					<button type="button" class="button button-danger aiohm-delete-event-btn aiohm-action-btn" data-event-index="<?php echo esc_attr( $index ); ?>" title="<?php esc_attr_e( 'Permanently delete this event and all its data', 'aiohm-booking-pro' ); ?>">
						<span class="dashicons dashicons-trash"></span>
						<?php esc_html_e( 'Delete Event', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
				
				<!-- Import Buttons Group -->
				<div class="aiohm-button-group aiohm-import-buttons">
					<button type="button" class="aiohm-facebook-import-btn aiohm-import-btn" data-event-index="<?php echo esc_attr( $index ); ?>" title="<?php esc_attr_e( 'Import event data from Facebook Events', 'aiohm-booking-pro' ); ?>">
						<span class="dashicons dashicons-facebook"></span>
						<?php esc_html_e( 'Facebook', 'aiohm-booking-pro' ); ?>
					</button>
					
					<?php
					// Check if EventON is available and show import button
					$eventon_available = false;
					if ( class_exists( 'EventON' ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'eventON/eventon.php' ) ) ) {
						$eventon_available = true;
					}

					$eventon_button_class    = 'aiohm-eventon-import-btn aiohm-import-btn';
					$eventon_button_disabled = '';
					$eventon_button_title    = esc_attr__( 'Import event data from EventON calendar', 'aiohm-booking-pro' );

					if ( ! $eventon_available ) {
						$eventon_button_class   .= ' aiohm-pro-feature-disabled';
						$eventon_button_disabled = 'disabled';
						$eventon_button_title    = esc_attr__( 'EventON plugin is required for import functionality', 'aiohm-booking-pro' );
					}
					?>
					<button type="button" class="<?php echo esc_attr( $eventon_button_class ); ?>" data-event-index="<?php echo esc_attr( $index ); ?>" <?php echo esc_attr( $eventon_button_disabled ); ?> title="<?php echo esc_attr( $eventon_button_title ); ?>">
						<span class="dashicons dashicons-calendar-alt"></span>
						<?php esc_html_e( 'EventON', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if AI modules are available for AI import functionality
	 *
	 * @since 1.2.5
	 * @return bool True if AI modules are available, false otherwise
	 */
	private function check_ai_modules_availability() {
		return false; // AI functionality removed in v2.0.0
	}

	/**
	 * Save events data from form submission.
	 *
	 * Processes and saves event configuration data including validation,
	 * sanitization, and database updates. Shows success message on completion.
	 *
	 * @since 2.0.0
	 * @return bool True on success, false if not processed.
	 */
	public function save_events_data() {
		// Only process if this form was submitted.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled below
		if ( ! isset( $_POST['aiohm_tickets_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled below
			return false;
		}

		$nonce = isset( $_POST['aiohm_tickets_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['aiohm_tickets_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_tickets_save' ) ) {
			wp_die( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		$events_data = array();
		if ( isset( $_POST['events'] ) && is_array( $_POST['events'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
			$events_raw = map_deep( wp_unslash( $_POST['events'] ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
			foreach ( $events_raw as $index => $event ) {
				$events_data[ $index ] = array(
					'event_type'         => sanitize_text_field( $event['event_type'] ?? '' ),
					'title'              => substr( sanitize_text_field( $event['title'] ?? '' ), 0, 50 ),
					'description'        => substr( sanitize_textarea_field( $event['description'] ?? '' ), 0, 150 ),
					'location'           => sanitize_text_field( $event['location'] ?? '' ),
					'event_date'         => sanitize_text_field( $event['event_date'] ?? '' ),
					'event_time'         => sanitize_text_field( $event['event_time'] ?? '' ),
					'event_end_date'     => sanitize_text_field( $event['event_end_date'] ?? '' ),
					'event_end_time'     => sanitize_text_field( $event['event_end_time'] ?? '' ),
					'available_seats'    => intval( $event['available_seats'] ?? 50 ),
					'price'              => floatval( $event['price'] ?? 0 ),
					'early_bird_price'   => floatval( $event['early_bird_price'] ?? 0 ),
					'early_bird_date'    => sanitize_text_field( $event['early_bird_date'] ?? '' ),
					'early_bird_days'    => intval( $event['early_bird_days'] ?? 0 ),
					'deposit_percentage' => intval( $event['deposit_percentage'] ?? 0 ),
				);
			}
		}

		update_option( 'aiohm_booking_events_data', $events_data );

		// Show success message.
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Events saved successfully!', 'aiohm-booking-pro' ) . '</p></div>';
			}
		);

		return true;
	}


	/**
	 * Get currency setting.
	 *
	 * Retrieves the current currency setting from the global settings,
	 * defaulting to EUR if not set.
	 *
	 * @since 2.0.0
	 * @return string Currency code (EUR, USD, GBP, etc.).
	 */
	private function get_currency_setting() {
		$settings = AIOHM_BOOKING_Settings::get_all();
		return $settings['currency'] ?? 'EUR';
	}

	/**
	 * Get ticket settings.
	 *
	 * Retrieves ticket-specific settings from the database.
	 *
	 * @since 2.0.0
	 * @return array Ticket settings array.
	 */
	private function get_ticket_settings() {
		return get_option( 'aiohm_booking_ticket_settings', array() );
	}

	/**
	 * Get ticket types.
	 *
	 * Retrieves available ticket types configuration from the database.
	 *
	 * @since 2.0.0
	 * @return array Ticket types array.
	 */
	private function get_ticket_types() {
		return get_option( 'aiohm_booking_ticket_types', array() );
	}

	/**
	 * Render the booking settings section.
	 *
	 * Displays the booking settings interface that allows configuration
	 * of event ticket pricing, policies, and booking restrictions.
	 *
	 * @since 2.0.0
	 */
	private function render_booking_settings_section() {
		// Get current global settings.
		$global_settings = AIOHM_BOOKING_Settings::get_all();
		$currency        = $this->get_currency_setting();
		?>
		<div class="aiohm-booking-admin-card aiohm-booking-settings-priority">
			<div class="aiohm-booking-settings-header">
				<h3>Booking Settings</h3>
			</div>

			<form method="post" action="" class="aiohm-booking-settings-form aiohm-admin-form" id="aiohm-booking-settings-form">
				<?php wp_nonce_field( 'aiohm_booking_settings' ); ?>
				<input type="hidden" name="form_submit" value="1">

				<div class="aiohm-booking-settings-grid aiohm-booking-settings-grid--large">

					<div class="aiohm-booking-setting-item">
						<div class="aiohm-booking-setting-icon">
							<span class="dashicons dashicons-admin-users"></span>
						</div>
						<div class="aiohm-booking-setting-content">
							<label class="aiohm-booking-setting-label">Minimum Age Requirement</label>
							<div class="aiohm-booking-setting-description">Minimum age for event ticket bookings</div>
							<div class="aiohm-booking-setting-input">
								<input type="number" name="aiohm_booking_settings[minimum_age]" value="<?php echo esc_attr( $global_settings['minimum_age'] ?? '18' ); ?>" min="0" max="99" step="1" placeholder="18">
								<span class="aiohm-booking-setting-unit">years</span>
							</div>
						</div>
					</div>


					<div class="aiohm-booking-setting-item">
						<div class="aiohm-booking-setting-icon">
							<span class="dashicons dashicons-plus-alt"></span>
						</div>
						<div class="aiohm-booking-setting-content">
							<label class="aiohm-booking-setting-label">Active Events</label>
							<div class="aiohm-booking-setting-description">Number of events to activate</div>
							<div class="aiohm-booking-setting-input">
								<input type="number" name="aiohm_booking_settings[number_of_events]" value="<?php echo esc_attr( $global_settings['number_of_events'] ?? '5' ); ?>" min="1" max="20" step="1" placeholder="5">
								<span class="aiohm-booking-setting-unit">events</span>
							</div>
						</div>
					</div>

					<div class="aiohm-booking-setting-item">
						<div class="aiohm-booking-setting-icon">
							<span class="dashicons dashicons-money-alt"></span>
						</div>
						<div class="aiohm-booking-setting-content">
							<label class="aiohm-booking-setting-label">Default Ticket Price</label>
							<div class="aiohm-booking-setting-description">Default price for event tickets when no specific price is set</div>
							<div class="aiohm-booking-setting-input">
								<input type="number" name="aiohm_booking_settings[ticket_price]" value="<?php echo esc_attr( $global_settings['ticket_price'] ?? '25' ); ?>" min="0" step="0.01" placeholder="25">
								<span class="aiohm-booking-setting-unit"><?php echo esc_html( $currency ); ?></span>
							</div>
						</div>
					</div>

					<div class="aiohm-booking-setting-item">
						<div class="aiohm-booking-setting-icon">
							<span class="dashicons dashicons-clock"></span>
						</div>
						<div class="aiohm-booking-setting-content">
							<label class="aiohm-booking-setting-label">Default Events Early Bird Price</label>
							<div class="aiohm-booking-setting-description">Default early bird price for events when no specific early bird price is set</div>
							<div class="aiohm-booking-setting-input">
								<input type="text" name="aiohm_booking_settings[aiohm_booking_events_early_bird_price]" value="<?php echo esc_attr( $global_settings['aiohm_booking_events_early_bird_price'] ?? '0' ); ?>" placeholder="0">
								<span class="aiohm-booking-setting-unit"><?php echo esc_html( $currency ); ?></span>
							</div>
						</div>
					</div>

				</div>
			</form>

			<?php if ( ! function_exists( 'aiohm_booking_fs' ) || ! aiohm_booking_fs()->is_premium() ) : ?>
			<div class="aiohm-booking-upsell-section">
				<div class="aiohm-booking-upsell-header">
					<h4>💳 Accept Payments with PRO</h4>
				</div>
				<p>Enable <strong>Stripe</strong> payment processing to collect payments automatically for your ticket sales.</p>
				<div class="aiohm-booking-pro-features">
					<span class="dashicons dashicons-yes-alt"></span> Credit Card Processing
					<span class="dashicons dashicons-yes-alt"></span> Automated Payment Collection
					<span class="dashicons dashicons-yes-alt"></span> AI Analytics & Insights
				</div>
				<?php if ( function_exists( 'aiohm_booking_fs' ) ) : ?>
					<?php
					$upgrade_url = aiohm_booking_fs()->is_paying()
						? aiohm_booking_fs()->_get_admin_page_url('account')
						: aiohm_booking_fs()->get_upgrade_url();
					$button_text = aiohm_booking_fs()->is_paying()
						? esc_html__( 'Manage License', 'aiohm-booking-pro' )
						: esc_html__( 'Upgrade to PRO', 'aiohm-booking-pro' );
					?>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-secondary aiohm-pro-upgrade-btn">
						<span class="dashicons dashicons-star-filled"></span>
						<?php echo $button_text; ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="aiohm-booking-card-event-footer">
				<div class="aiohm-card-footer-actions">
					<button type="submit" class="button button-primary aiohm-booking-settings-save-btn" form="aiohm-booking-settings-form">
						<span class="dashicons dashicons-yes-alt"></span>
						Save Booking Settings
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Normalize field value to boolean
	 *
	 * @param mixed $value The value to normalize
	 * @return bool The normalized boolean value
	 */
	private function normalize_field_value( $value ) {
		// Handle string representations of boolean values
		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			if ( $value === '1' || $value === 'true' || $value === 'yes' || $value === 'on' ) {
				return true;
			}
			if ( $value === '0' || $value === 'false' || $value === 'no' || $value === 'off' || $value === '' ) {
				return false;
			}
		}

		// Handle numeric values
		if ( is_numeric( $value ) ) {
			return (bool) $value;
		}

		// Return boolean value directly
		return (bool) $value;
	}

	/**
	 * AJAX handler for processing event booking submissions from frontend
	 *
	 * @since 1.2.3
	 */
	public function ajax_process_event_booking() {
		// Verify security using centralized helper (only nonce for frontend)
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'frontend_nonce' ) ) {
			return; // Error response already sent by helper
		}

		// SAFETY CHECK: If this is actually an accommodation booking, redirect it
		if ( isset( $_POST['form_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with AIOHM_BOOKING_Security_Helper
			$form_data_raw = sanitize_text_field( wp_unslash( $_POST['form_data'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with AIOHM_BOOKING_Security_Helper
			parse_str( $form_data_raw, $form_data );
			$has_accommodations = ! empty( $form_data['accommodations'] ) || ! empty( $form_data['accommodation_id'] );
			$has_events         = ! empty( $form_data['selected_event'] ) || ! empty( $form_data['selected_events'] );

			if ( $has_accommodations && ! $has_events ) {
				// Redirect to the accommodation handler
				$shortcode_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'shortcode' );

				if ( $shortcode_module && method_exists( $shortcode_module, 'ajax_process_accommodation_booking' ) ) {
					$shortcode_module->ajax_process_accommodation_booking();
					wp_die(); // Stop execution here
				} else {
					wp_send_json_error( array( 'message' => 'Accommodation booking handler not available' ) );
				}
			}
		}

		// Parse form data
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with AIOHM_BOOKING_Security_Helper
		$form_data_raw = isset( $_POST['form_data'] ) ? sanitize_text_field( wp_unslash( $_POST['form_data'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above with AIOHM_BOOKING_Security_Helper
		parse_str( $form_data_raw, $form_data );
		$form_data = array_map( 'sanitize_text_field', $form_data );

		// Extract event selection data - handle both single and multiple event selections
		$selected_event_index = null;
		$ticket_quantity      = 0;

		// Check for single event selection (radio button)
		if ( isset( $form_data['selected_event'] ) && $form_data['selected_event'] !== '' ) {
			$selected_event_index = intval( $form_data['selected_event'] );
			// Check for ticket quantity for this specific event
			if ( isset( $form_data['event_tickets'][ $selected_event_index ] ) ) {
				$ticket_quantity = intval( $form_data['event_tickets'][ $selected_event_index ] );
			}
		}

		// Check for multiple event selections (checkbox array)
		if ( isset( $form_data['selected_events'] ) && $form_data['selected_events'] !== '' ) {
			// Handle single value or array
			if ( is_array( $form_data['selected_events'] ) ) {
				$selected_event_index = intval( $form_data['selected_events'][0] );
			} else {
				$selected_event_index = intval( $form_data['selected_events'] );
			}
		}

		// If we have a selected event, look for ticket quantity in various formats
		if ( $selected_event_index !== null ) {
			// Try different ticket quantity field formats
			if ( isset( $form_data['event_tickets'][ $selected_event_index ] ) ) {
				$ticket_quantity = intval( $form_data['event_tickets'][ $selected_event_index ] );
			} elseif ( isset( $form_data[ 'event_tickets' . $selected_event_index ] ) ) {
				// Handle event_tickets0, event_tickets1 format
				$ticket_quantity = intval( $form_data[ 'event_tickets' . $selected_event_index ] );
			}
		}

		// Validate that we have an event selected (only for pure event bookings)
		$has_accommodations = ! empty( $form_data['accommodations'] ) || ! empty( $form_data['accommodation_id'] );
		if ( $selected_event_index === null && ! $has_accommodations ) {
			wp_send_json_error( array( 'message' => 'Event selection is required' ) );
		}

		// If no ticket quantity was found, default to 1 ticket
		if ( $ticket_quantity <= 0 ) {
			$ticket_quantity = 1;
		}

		// Get event data from saved events
		$events_data = get_option( 'aiohm_booking_events_data', array() );
		if ( ! isset( $events_data[ $selected_event_index ] ) ) {
			wp_send_json_error( array( 'message' => 'Selected event not found' ) );
		}

		$event_data = $events_data[ $selected_event_index ];

		// Extract and sanitize event booking data from event data and form
		$event_title      = sanitize_text_field( $event_data['title'] ?? 'Event Booking' );
		$event_date       = sanitize_text_field( $event_data['event_date'] ?? '' );
		$event_time       = sanitize_text_field( $event_data['event_time'] ?? '' );
		$event_price      = floatval( $event_data['price'] ?? 0 );
		$early_bird_price = floatval( $event_data['early_bird_price'] ?? $event_price );
		$early_bird_date  = sanitize_text_field( $event_data['early_bird_date'] ?? '' );

		// Determine effective price (early bird if applicable)
		$current_date    = current_time( 'Y-m-d' );
		$effective_price = ( ! empty( $early_bird_date ) && $early_bird_date >= $current_date ) ? $early_bird_price : $event_price;

		$total_amount   = $effective_price * $ticket_quantity;
		$currency       = sanitize_text_field( $form_data['currency'] ?? get_option( 'aiohm_booking_settings', array() )['currency'] ?? 'EUR' );
		$payment_method = sanitize_text_field( $form_data['payment_method'] ?? 'full' );

		// Calculate deposit if needed
		$deposit_amount = 0;
		if ( $payment_method === 'deposit' ) {
			$deposit_percent = intval( $form_data['deposit_percent'] ?? 50 );
			$deposit_amount  = $total_amount * ( $deposit_percent / 100 );
		}

		// Get customer information (may be empty for initial booking)
		$buyer_name  = sanitize_text_field( $form_data['name'] ?? '' );
		$buyer_email = sanitize_email( $form_data['email'] ?? '' );
		$buyer_phone = sanitize_text_field( $form_data['phone'] ?? '' );
		$notes       = sanitize_textarea_field( $form_data['notes'] ?? $form_data['special_requests'] ?? '' );

		// Insert booking record into database
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		$booking_data = array(
			'buyer_name'     => $buyer_name,
			'buyer_email'    => $buyer_email,
			'buyer_phone'    => $buyer_phone,
			'check_in_date'  => $event_date, // Use event date as check-in for compatibility
			'check_out_date' => $event_date, // Same day for events
			'guests_qty'     => $ticket_quantity,
			'rooms_qty'      => 1, // Always 1 for events
			'total_amount'   => $total_amount,
			'deposit_amount' => $deposit_amount,
			'currency'       => $currency,
			'notes'          => $notes,
			'status'         => 'pending',
			'mode'           => 'tickets', // Indicate this is an event booking
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $table_name, $booking_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => 'Failed to create booking record' ) );
		}

		$booking_id = $wpdb->insert_id;

		// Store event-specific data in meta or separate table if needed
		// For now, we'll use the notes field to store event details
		$event_details = array(
			'event_index'     => $selected_event_index,
			'event_title'     => $event_title,
			'event_date'      => $event_date,
			'event_time'      => $event_time,
			'event_price'     => $effective_price,
			'ticket_quantity' => $ticket_quantity,
			'payment_method'  => $payment_method,
		);

		// Update notes with event details for reference including event index
		$detailed_notes = $notes;
		if ( empty( $detailed_notes ) ) {
			$detailed_notes = sprintf(
				"Event: %d, %s\nDate: %s %s\nTickets: %d\nPrice per ticket: %s%.2f",
				$selected_event_index,
				$event_title,
				$event_date,
				$event_time,
				$ticket_quantity,
				$currency,
				$effective_price
			);
		} else {
			// Always append event details to ensure they're available for order display
			$detailed_notes .= sprintf(
				"\n\nEvent Details:\nEvent: %d, %s\nDate: %s %s\nTickets: %d\nPrice per ticket: %s%.2f",
				$selected_event_index,
				$event_title,
				$event_date,
				$event_time,
				$ticket_quantity,
				$currency,
				$effective_price
			);
		}

		$wpdb->update(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket data insertion
			$table_name,
			array( 'notes' => $detailed_notes ),
			array( 'id' => $booking_id )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		// Trigger booking confirmation email notification
		do_action( 'aiohm_booking_order_created', $booking_id );

		// Return success with booking ID
		wp_send_json_success(
			array(
				'message'      => 'Event booking created successfully',
				'booking_id'   => $booking_id,
				'redirect_url' => '', // Will be handled by frontend
			)
		);
	}

	/**
	 * Helper method to process event booking part for unified bookings
	 *
	 * @param array $form_data Form data
	 * @return int|WP_Error Booking ID on success, WP_Error on failure
	 * @since 1.2.6
	 */
	public function process_event_booking_part( $form_data ) {
		// Extract event selection data - handle both single and multiple event selections
		$selected_event_index = null;
		$ticket_quantity      = 0;

		// Check for single event selection (radio button)
		if ( isset( $form_data['selected_event'] ) && $form_data['selected_event'] !== '' ) {
			$selected_event_index = intval( $form_data['selected_event'] );
			// Check for ticket quantity for this specific event
			if ( isset( $form_data['event_tickets'][ $selected_event_index ] ) ) {
				$ticket_quantity = intval( $form_data['event_tickets'][ $selected_event_index ] );
			}
		}

		// Check for multiple event selections (checkbox array)
		if ( isset( $form_data['selected_events'] ) && $form_data['selected_events'] !== '' ) {
			// Handle single value or array
			if ( is_array( $form_data['selected_events'] ) ) {
				$selected_event_index = intval( $form_data['selected_events'][0] );
			} else {
				$selected_event_index = intval( $form_data['selected_events'] );
			}
		}

		// If we have a selected event, look for ticket quantity in various formats
		if ( $selected_event_index !== null ) {
			// Try different ticket quantity field formats
			if ( isset( $form_data['event_tickets'][ $selected_event_index ] ) ) {
				$ticket_quantity = intval( $form_data['event_tickets'][ $selected_event_index ] );
			} elseif ( isset( $form_data[ 'event_tickets' . $selected_event_index ] ) ) {
				// Handle event_tickets0, event_tickets1 format
				$ticket_quantity = intval( $form_data[ 'event_tickets' . $selected_event_index ] );
			}
		}

		// Validate that we have an event selected (only for pure event bookings)
		$has_accommodations = ! empty( $form_data['accommodations'] ) || ! empty( $form_data['accommodation_id'] );

		// If no event is selected and we don't have accommodations, require event selection
		if ( $selected_event_index === null ) {
			if ( ! $has_accommodations ) {
				return new WP_Error( 'event_required', 'Event selection is required' );
			} else {
				// If we have accommodations but no events, return success without processing events
				return 0; // Placeholder booking ID indicating no event was processed
			}
		}

		// If no ticket quantity was found, default to 1 ticket
		if ( $ticket_quantity <= 0 ) {
			$ticket_quantity = 1;
		}

		// Get customer information from form data
		$buyer_name  = sanitize_text_field( $form_data['name'] ?? '' );
		$buyer_email = sanitize_email( $form_data['email'] ?? '' );
		$buyer_phone = sanitize_text_field( $form_data['phone'] ?? '' );

		// Get event data from saved events
		$events_data = get_option( 'aiohm_booking_events_data', array() );
		if ( ! isset( $events_data[ $selected_event_index ] ) ) {
			return new WP_Error( 'event_not_found', 'Selected event not found' );
		}

		$event_data = $events_data[ $selected_event_index ];

		// Extract and sanitize event booking data from event data and form
		$event_title      = sanitize_text_field( $event_data['title'] ?? 'Event Booking' );
		$event_date       = sanitize_text_field( $event_data['event_date'] ?? '' );
		$event_time       = sanitize_text_field( $event_data['event_time'] ?? '' );
		$event_price      = floatval( $event_data['price'] ?? 0 );
		$early_bird_price = floatval( $event_data['early_bird_price'] ?? $event_price );
		$early_bird_date  = sanitize_text_field( $event_data['early_bird_date'] ?? '' );

		// Determine effective price (early bird if applicable)
		$current_date    = current_time( 'Y-m-d' );
		$effective_price = ( ! empty( $early_bird_date ) && $early_bird_date >= $current_date ) ? $early_bird_price : $event_price;

		$total_amount   = $effective_price * $ticket_quantity;
		$currency       = sanitize_text_field( $form_data['currency'] ?? get_option( 'aiohm_booking_settings', array() )['currency'] ?? 'EUR' );
		$payment_method = sanitize_text_field( $form_data['payment_method'] ?? 'full' );

		// Calculate deposit if needed
		$deposit_amount = 0;
		if ( $payment_method === 'deposit' ) {
			$deposit_percent = intval( $form_data['deposit_percent'] ?? 50 );
			$deposit_amount  = $total_amount * ( $deposit_percent / 100 );
		}

		$notes = sanitize_textarea_field( $form_data['notes'] ?? $form_data['special_requests'] ?? '' );

		// Insert booking record into database
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		$booking_data = array(
			'buyer_name'     => $buyer_name,
			'buyer_email'    => $buyer_email,
			'buyer_phone'    => $buyer_phone,
			'check_in_date'  => $event_date, // Use event date as check-in for compatibility
			'check_out_date' => $event_date, // Same day for events
			'guests_qty'     => $ticket_quantity,
			'rooms_qty'      => 1, // Always 1 for events
			'total_amount'   => $total_amount,
			'deposit_amount' => $deposit_amount,
			'currency'       => $currency,
			'notes'          => $notes,
			'status'         => 'pending',
			'mode'           => 'tickets', // Indicate this is an event booking
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $table_name, $booking_data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		if ( $result === false ) {
			return new WP_Error( 'booking_creation_failed', 'Failed to create booking record' );
		}

		$booking_id = $wpdb->insert_id;

		// Store event-specific data in notes for reference
		$event_details = array(
			'event_index'     => $selected_event_index,
			'event_title'     => $event_title,
			'event_date'      => $event_date,
			'event_time'      => $event_time,
			'event_price'     => $effective_price,
			'ticket_quantity' => $ticket_quantity,
			'payment_method'  => $payment_method,
		);

		// Update notes with event details for reference including event index
		$detailed_notes = $notes;
		if ( empty( $detailed_notes ) ) {
			$detailed_notes = sprintf(
				"Event: %d, %s\nDate: %s %s\nTickets: %d\nPrice per ticket: %s%.2f",
				$selected_event_index,
				$event_title,
				$event_date,
				$event_time,
				$ticket_quantity,
				$currency,
				$effective_price
			);
		} else {
			// Always append event details to ensure they're available for order display
			$detailed_notes .= sprintf(
				"\n\nEvent Details:\nEvent: %d, %s\nDate: %s %s\nTickets: %d\nPrice per ticket: %s%.2f",
				$selected_event_index,
				$event_title,
				$event_date,
				$event_time,
				$ticket_quantity,
				$currency,
				$effective_price
			);
		}

		$wpdb->update(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket data insertion
			$table_name,
			array( 'notes' => $detailed_notes ),
			array( 'id' => $booking_id )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		// Trigger booking confirmation email notification
		do_action( 'aiohm_booking_order_created', $booking_id );

		// Return the actual booking ID instead of placeholder
		return $booking_id;
	}

	/**
	 * Update event availability when payment is completed
	 *
	 * This method is called when the 'aiohm_booking_payment_completed' action is triggered.
	 * It updates the available seats for the booked event.
	 *
	 * @since 1.2.4
	 * @param int    $booking_id    The booking ID
	 * @param string $payment_method The payment method used
	 * @param mixed  $payment_data  Additional payment data
	 */
	public function update_event_availability( $booking_id, $payment_method, $payment_data ) {

		// Only process ticket bookings
		if ( ! $booking_id ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		// Get the booking details
		$booking = $wpdb->get_row(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket update
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}aiohm_booking_orders` WHERE id = %d",
				$booking_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		if ( ! $booking || $booking->mode !== 'tickets' ) {
			return; // Not a ticket booking
		}

		// Extract event data from notes field
		$event_data = $this->extract_event_data_from_notes( $booking->notes );
		if ( ! $event_data ) {
			return; // No event data found
		}

		$event_index     = $event_data['event_index'] ?? null;
		$ticket_quantity = intval( $booking->guests_qty ?? 0 );

		if ( $event_index === null || $ticket_quantity <= 0 ) {
			return; // Invalid event data
		}

		// Get current events data
		$events = get_option( 'aiohm_booking_events_data', array() );

		if ( ! isset( $events[ $event_index ] ) ) {
			return; // Event doesn't exist
		}

		// Update available seats
		$current_available = intval( $events[ $event_index ]['available_seats'] ?? 0 );
		$new_available     = max( 0, $current_available - $ticket_quantity );

		$events[ $event_index ]['available_seats'] = $new_available;

		// Save updated events data
		update_option( 'aiohm_booking_events_data', $events );
	}

	/**
	 * Calculate real-time available seats for events based on paid orders
	 *
	 * @since 1.2.4
	 * @param array $events_data The events data array
	 * @return array Events data with updated available_seats reflecting real availability
	 */
	public static function get_events_with_realtime_availability( $events_data ) {
		if ( empty( $events_data ) ) {
			return $events_data;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		// Get all paid ticket orders for aggregation
		// Check if the table exists and has the required columns
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket statistics

		if ( ! $table_exists ) {
			return $events_data; // Return original data if table doesn't exist
		}

		// Check if required columns exist
		$notes_column_exists = $wpdb->get_var(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket count
			$wpdb->prepare(
				'SHOW COLUMNS FROM ' . esc_sql( $table_name ) . ' LIKE %s',
				'notes'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		$guests_qty_column_exists = $wpdb->get_var(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket status query
			$wpdb->prepare(
				'SHOW COLUMNS FROM ' . esc_sql( $table_name ) . ' LIKE %s',
				'guests_qty'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		$mode_column_exists = $wpdb->get_var(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket analysis
			$wpdb->prepare(
				'SHOW COLUMNS FROM ' . esc_sql( $table_name ) . ' LIKE %s',
				'mode'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		$status_column_exists = $wpdb->get_var(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for ticket report generation
			$wpdb->prepare(
				'SHOW COLUMNS FROM ' . esc_sql( $table_name ) . ' LIKE %s',
				'status'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		// Build WHERE clause based on available columns
		$where_conditions = array();
		$query_params     = array();

		if ( $mode_column_exists ) {
			$where_conditions[] = 'mode = %s';
			$query_params[]     = 'tickets';
		}

		if ( $status_column_exists ) {
			$where_conditions[] = '(status = %s OR status = %s OR status = %s)';
			$query_params[]     = 'paid';
			$query_params[]     = 'pending';
			$query_params[]     = 'confirmed';
		}

		$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

		// Build SELECT clause based on available columns (safe - from controlled array)
		$select_columns = array();
		if ( $notes_column_exists ) {
			$select_columns[] = 'notes';
		}
		if ( $guests_qty_column_exists ) {
			$select_columns[] = 'guests_qty';
		}
		if ( empty( $select_columns ) ) {
			$select_columns[] = 'id'; // Fallback to id if no other columns available
		}

		// Sanitize column names (though they're from controlled array)
		$safe_columns = array_map( 'esc_sql', $select_columns );
		$select_clause = 'SELECT ' . implode( ', ', $safe_columns );

		// Execute the query - safe construction with escaped elements
		$escaped_table_name = esc_sql( $table_name );
		
		if ( ! empty( $query_params ) ) {
			$full_sql = $select_clause . ' FROM `' . $escaped_table_name . '` ' . $where_clause;
			$paid_orders = $wpdb->get_results( $wpdb->prepare( $full_sql, $query_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic SQL safely constructed from escaped elements, custom table query for plugin functionality
		} else {
			$static_sql = $select_clause . ' FROM `' . $escaped_table_name . '`';
			$paid_orders = $wpdb->get_results( $static_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Static SQL with escaped table name, custom table query for plugin functionality
		}

		// Calculate sold tickets per event
		$sold_per_event = array();
		foreach ( $paid_orders as $order ) {
			// Extract event name from order notes (if notes column exists)
			$order_event_name = self::extract_event_name_from_order_notes( $order->notes ?? '' );
			// Get ticket quantity (if guests_qty column exists, default to 1)
			$ticket_quantity = intval( $order->guests_qty ?? 1 );

			if ( ! empty( $order_event_name ) && $ticket_quantity > 0 ) {
				// Find matching event by normalized name comparison
				foreach ( $events_data as $event_index => $event ) {
					$event_name = $event['title'] ?? '';

					// Normalize both strings for comparison (remove invisible Unicode characters)
					$normalized_order_name = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim( $order_event_name ) );
					$normalized_event_name = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim( $event_name ) );

					if ( $normalized_order_name === $normalized_event_name ) {
						$sold_per_event[ $event_index ] = ( $sold_per_event[ $event_index ] ?? 0 ) + $ticket_quantity;
						break;
					}
				}
			}
		}

		// Update events data with real-time availability
		foreach ( $events_data as $event_index => &$event ) {
			$original_seats  = intval( $event['available_seats'] ?? 0 );
			$sold_tickets    = $sold_per_event[ $event_index ] ?? 0;
			$available_seats = max( 0, $original_seats - $sold_tickets );

			// Preserve original capacity for display purposes and update availability
			$event['total_capacity']  = $original_seats;
			$event['tickets_sold']    = $sold_tickets;
			$event['available_seats'] = $available_seats;
		}

		return $events_data;
	}

	/**
	 * Extract event name from order notes (static version for use in templates)
	 *
	 * @since 1.2.4
	 * @param string $notes The notes field content
	 * @return string Event name or empty string if not found
	 */
	public static function extract_event_name_from_order_notes( $notes ) {
		if ( empty( $notes ) ) {
			return '';
		}

		// Convert escaped newlines to actual newlines for proper parsing
		$notes = str_replace( array( '\\n', '\n' ), "\n", $notes );

		// Try to extract event name from notes format: "Event: 0, Event Name\nDate: ..."
		if ( preg_match( '/Event:\s*\d+,\s*(.+?)(?=\n|$)/i', $notes, $matches ) ) {
			return trim( $matches[1] );
		}

		// Fallback: try to extract from format: "Event: Event Name\nDate: ..." (without index)
		if ( preg_match( '/Event:\s*(.+?)(?=\n|$)/i', $notes, $matches ) ) {
			$event_part = trim( $matches[1] );
			// Skip if it looks like "Event: 0, Event Name" format (already handled above)
			if ( ! preg_match( '/^\d+,/', $event_part ) ) {
				return $event_part;
			}
		}

		return '';
	}

	/**
	 * Extract event data from booking notes field
	 *
	 * @since 1.2.4
	 * @param string $notes The notes field content
	 * @return array|false Event data or false if not found
	 */
	private function extract_event_data_from_notes( $notes ) {
		if ( empty( $notes ) ) {
			return false;
		}

		// Try to extract event index from notes
		// Look for patterns like "Event: 0, Event Name"
		if ( preg_match( '/Event:\s*(\d+),/', $notes, $matches ) ) {
			return array( 'event_index' => intval( $matches[1] ) );
		}

		return false;
	}


	/**
	 * AJAX handler for getting EventON events list.
	 *
	 * @since 1.2.5
	 */
	public function ajax_get_eventon_events_list() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aiohm_booking_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		// Check if EventON is available
		if ( ! class_exists( 'EventON' ) && ! ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'eventON/eventon.php' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'EventON plugin is not active.', 'aiohm-booking-pro' ) ) );
		}

		try {
			$events = $this->get_eventon_events();

			wp_send_json_success(
				array(
					'events' => $events,
					'count'  => count( $events ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Get EventON events.
	 *
	 * @since 1.2.5
	 * @return array Array of EventON events.
	 */
	private function get_eventon_events() {
		$events = array();

		// Get EventON events from WordPress posts
		$eventon_posts = get_posts( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for EventON integration, limited to 50 posts, ordering by date meta key necessary
			array(
				'post_type'      => 'ajde_events',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'meta_value',
				'meta_key'       => 'evcal_srow', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Essential for chronological EventON event ordering
				'order'          => 'ASC',
			)
		);

		foreach ( $eventon_posts as $post ) {
			$event_data = $this->extract_eventon_event_data( $post );
			if ( $event_data ) {
				$events[] = $event_data;
			}
		}

		return $events;
	}

	/**
	 * Extract EventON event data.
	 *
	 * @since 1.2.5
	 * @param WP_Post $post EventON event post.
	 * @return array|false Event data array or false on failure.
	 */
	private function extract_eventon_event_data( $post ) {
		if ( ! $post ) {
			return false;
		}

		// Get EventON meta data
		$start_date = get_post_meta( $post->ID, 'evcal_srow', true );
		$end_date   = get_post_meta( $post->ID, 'evcal_erow', true );
		$location   = get_post_meta( $post->ID, 'evcal_location_name', true );
		$all_day    = get_post_meta( $post->ID, 'evcal_allday', true );

		// Convert timestamps to readable dates
		$start_datetime = $start_date ? gmdate( 'Y-m-d H:i:s', $start_date ) : '';
		$end_datetime   = $end_date ? gmdate( 'Y-m-d H:i:s', $end_date ) : '';

		// Format display date and time
		$date_display = $start_date ? gmdate( 'M j, Y', $start_date ) : '';
		$time_display = '';

		if ( $start_date && ! $all_day ) {
			$time_display = gmdate( 'g:i A', $start_date );
			if ( $end_date && $end_date !== $start_date ) {
				$time_display .= ' - ' . gmdate( 'g:i A', $end_date );
			}
		}

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'description' => $this->extract_clean_content( $post->post_content ),
			'date'        => $date_display,
			'time'        => $time_display,
			'start_date'  => $start_datetime,
			'end_date'    => $end_datetime,
			'location'    => $location,
			'all_day'     => (bool) $all_day,
		);
	}

	/**
	 * Extract clean text content from WordPress blocks.
	 *
	 * Parses Gutenberg blocks and extracts clean text content without markup.
	 *
	 * @since 1.2.6
	 * @param string $content Raw post content that may contain Gutenberg blocks.
	 * @return string Clean text content.
	 */
	private function extract_clean_content( $content ) {
		if ( empty( $content ) ) {
			return '';
		}

		// Check if content contains Gutenberg blocks
		if ( strpos( $content, '<!-- wp:' ) === false ) {
			// Not Gutenberg blocks, return cleaned content
			return wp_strip_all_tags( $content );
		}

		$clean_content = '';

		// Parse Gutenberg blocks
		$blocks = parse_blocks( $content );

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				// Handle different block types
				switch ( $block['blockName'] ) {
					case 'core/paragraph':
					case 'core/heading':
					case 'core/list-item':
						if ( ! empty( $block['innerHTML'] ) ) {
							// Extract text from innerHTML, removing block comments
							$text = preg_replace( '/<!--.*?-->/', '', $block['innerHTML'] );
							$text = wp_strip_all_tags( $text );
							if ( ! empty( trim( $text ) ) ) {
								$clean_content .= trim( $text ) . ' ';
							}
						}
						break;

					case 'core/list':
						if ( ! empty( $block['innerBlocks'] ) ) {
							foreach ( $block['innerBlocks'] as $list_item ) {
								if ( ! empty( $list_item['innerHTML'] ) ) {
									$text = preg_replace( '/<!--.*?-->/', '', $list_item['innerHTML'] );
									$text = wp_strip_all_tags( $text );
									if ( ! empty( trim( $text ) ) ) {
										$clean_content .= '• ' . trim( $text ) . ' ';
									}
								}
							}
						}
						break;

					case 'core/quote':
						if ( ! empty( $block['innerHTML'] ) ) {
							$text = preg_replace( '/<!--.*?-->/', '', $block['innerHTML'] );
							$text = wp_strip_all_tags( $text );
							if ( ! empty( trim( $text ) ) ) {
								$clean_content .= '"' . trim( $text ) . '" ';
							}
						}
						break;

					default:
						// For other blocks, try to extract any text content
						if ( ! empty( $block['innerHTML'] ) ) {
							$text = preg_replace( '/<!--.*?-->/', '', $block['innerHTML'] );
							$text = wp_strip_all_tags( $text );
							if ( ! empty( trim( $text ) ) ) {
								$clean_content .= trim( $text ) . ' ';
							}
						}
						break;
				}
			} elseif ( ! empty( $block['innerHTML'] ) ) {
				// Classic editor content or other HTML
				$text = wp_strip_all_tags( $block['innerHTML'] );
				if ( ! empty( trim( $text ) ) ) {
					$clean_content .= trim( $text ) . ' ';
				}
			}
		}

		// Clean up extra whitespace and return
		return trim( preg_replace( '/\s+/', ' ', $clean_content ) );
	}
}
