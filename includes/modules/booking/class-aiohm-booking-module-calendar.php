<?php
/**
 * Calendar Module - Visual booking calendar with availability management
 *
 * @package AIOHM_Booking_PRO
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 * @license GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.html
 * @since  2.0.0
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Legacy method names
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Legacy variable names

/**
 * Calendar Module class
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Module_Calendar extends AIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Module identifier.
	 *
	 * @var string
	 */
	protected $module_id = 'calendar';

	/**
	 * Admin page slug for the calendar module.
	 *
	 * @var string
	 */
	protected $admin_page_slug = 'aiohm-booking-calendar';

	// Calendar Configuration Constants.
	const ALL_ROOM_TYPES      = '0';
	const PERIOD_TYPE_MONTH   = 'month';
	const PERIOD_TYPE_QUARTER = 'quarter';
	const PERIOD_TYPE_CUSTOM  = 'custom';
	const DEFAULT_ROOM_COUNT  = 1;
	const DEFAULT_PERIOD_DAYS = 6;

	// Calendar Properties.
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	/**
	 * The type of period being displayed (month, quarter, custom)
	 *
	 * @var string
	 */
	private $period_type;
	/**
	 * The current page number for pagination
	 *
	 * @var int
	 */
	private $period_page;
	/**
	 * Start date for custom period
	 *
	 * @var string
	 */
	private $custom_period_from;
	/**
	 * End date for custom period
	 *
	 * @var string
	 */
	private $custom_period_to;
	/**
	 * Current period object
	 *
	 * @var object
	 */
	private $period;
	/**
	 * Array of period data
	 *
	 * @var array
	 */
	private $period_array;
	/**
	 * Start date of the current period
	 *
	 * @var string
	 */
	private $period_start_date;
	/**
	 * End date of the current period
	 *
	 * @var string
	 */
	private $period_end_date;
	/**
	 * ID of the selected unit type
	 *
	 * @var int
	 */
	private $unit_type_id;
	/**
	 * Array of unit posts
	 *
	 * @var array
	 */
	private $unit_posts = array();
	/**
	 * Array of booking data
	 *
	 * @var array
	 */
	private $booking_data = array();
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Constructor for the calendar module
	 */
	public function __construct() {
		parent::__construct();
		// Always initialize hooks for shortcode functionality.
		// Note: This will be called again by parent if enabled, but that's OK.
		$this->init_hooks();
		$this->init_calendar_rules();
	}

	/**
	 * Get UI definition for the calendar module
	 *
	 * @return array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'calendar',
			'name'                => __( 'Calendar', 'aiohm-booking-pro' ),
			'description'         => __( 'Visual booking calendar showing accommodation availability and reservations in real-time.', 'aiohm-booking-pro' ),
			'icon'                => '📅',
			'admin_page_slug'     => 'aiohm-booking-calendar',
			'category'            => 'booking',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 15,
			'has_settings'        => true,
			'has_admin_page'      => true,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Create the calendar data table during plugin activation
	 * This should be called from the main plugin activation hook.
	 *
	 * @return void
	 */
	public static function on_activation() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_calendar_data';

		// Check if table already exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
		if ( $table_exists === $table_name ) {
			return; // Table already exists.
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            unit_type_id bigint(20) unsigned DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'available',
            notes text,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY date_unit (date, unit_type_id),
            KEY status (status),
            KEY date (date)
        ) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Initialize calendar rules integration.
	 */
	private function init_calendar_rules() {
		// Initialize the rules system - but only if class is available.
		if ( class_exists( 'AIOHM_BOOKING_Calendar_Rules' ) ) {
			AIOHM_BOOKING_Calendar_Rules::get_instance();
		}
	}

	/**
	 * Initialize hooks for the calendar module
	 */
	public function init_hooks() {
		// Calendar module only handles admin page functionality.
		// No shortcode registration needed.

		// Only register admin hooks if module is enabled.
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 999 );

		// Clear calendar cache when settings are updated
		add_action( 'update_option_aiohm_booking_settings', array( $this, 'clear_calendar_cache' ) );

		// AJAX handlers for calendar functionality.
		add_action( 'wp_ajax_aiohm_booking_set_date_status', array( $this, 'handle_set_date_status' ) );
		add_action( 'wp_ajax_aiohm_booking_update_cell_status', array( $this, 'handle_update_cell_status' ) );
		add_action( 'wp_ajax_aiohm_booking_reset_all_days', array( $this, 'handle_reset_all_days' ) );
		add_action( 'wp_ajax_aiohm_booking_set_private_event', array( $this, 'handle_set_private_event' ) );
		add_action( 'wp_ajax_aiohm_booking_remove_private_event', array( $this, 'handle_remove_private_event' ) );
		add_action( 'wp_ajax_aiohm_booking_get_private_events', array( $this, 'handle_get_private_events' ) );
		add_action( 'wp_ajax_aiohm_booking_save_calendar_color', array( $this, 'handle_save_calendar_color' ) );
		add_action( 'wp_ajax_aiohm_booking_reset_all_calendar_colors', array( $this, 'handle_reset_all_calendar_colors' ) );
		add_action( 'wp_ajax_aiohm_booking_sync_calendar', array( $this, 'handle_sync_calendar' ) );

		// Frontend AJAX handlers (available for both logged-in and non-logged-in users).
		add_action( 'wp_ajax_aiohm_get_calendar_availability', array( $this, 'handle_get_calendar_availability' ) );
		add_action( 'wp_ajax_nopriv_aiohm_get_calendar_availability', array( $this, 'handle_get_calendar_availability' ) );
	}

	/**
	 * Add admin menu for the calendar module
	 */
	public function add_admin_menu() {
		// Calendar menu is handled by the main admin class to avoid duplicates.
		// This method is kept for compatibility but doesn't add menu items.
	}

	/**
	 * Enqueue admin assets for the calendar module
	 */
	protected function enqueue_admin_assets() {
		// Since the base class already checks the hook_suffix, we don't need to check the screen here.
		// The base admin_enqueue_assets method ensures this is only called on calendar admin pages.

		// Calendar admin styles are now included in aiohm-booking-admin.css.
		// The main assets handler automatically enqueues admin CSS via admin_enqueue_scripts hook

		wp_enqueue_script(
			'aiohm-booking-calendar-admin',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-calendar-admin.js',
			array( 'jquery', 'aiohm-booking-admin', 'aiohm-booking-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Enqueue advanced calendar JavaScript.
		wp_enqueue_script(
			'aiohm-booking-advanced-calendar',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-advanced-calendar.js',
			array( 'jquery', 'aiohm-booking-calendar-admin' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		$default_colors  = $this->get_default_calendar_colors();
		$saved_colors    = get_option( 'aiohm_booking_calendar_colors', array() );
		$calendar_colors = wp_parse_args( $saved_colors, $default_colors );

		// Get brand color from accommodation/form settings.
		$main_settings = get_option( 'aiohm_booking_settings', array() );
		$brand_color   = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? '#457d59';

		// Get private events for mini calendar
		$private_events = get_option( 'aiohm_booking_private_events', array() );

		// Localize for both calendar admin and advanced calendar scripts
		$localization_data = array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
			'calendar_colors' => $calendar_colors,
			'brand_color'     => $brand_color,
			'private_events'  => $private_events,
			'i18n'            => array(
				'cellNotEditable'          => __( 'This cell is not editable.', 'aiohm-booking-pro' ),
				'activeBookingWarning'     => __( 'This date has an active booking and cannot be modified.', 'aiohm-booking-pro' ),
				'updateError'              => __( 'Failed to update date status.', 'aiohm-booking-pro' ),
				'saveSettingsError'        => __( 'Failed to save settings.', 'aiohm-booking-pro' ),
				'statusFree'               => __( 'Free', 'aiohm-booking-pro' ),
				'statusAvailable'          => __( 'Available', 'aiohm-booking-pro' ),
				'statusBooked'             => __( 'Booked', 'aiohm-booking-pro' ),
				'statusPending'            => __( 'Pending', 'aiohm-booking-pro' ),
				'statusExternal'           => __( 'External', 'aiohm-booking-pro' ),
				'statusBlocked'            => __( 'Blocked', 'aiohm-booking-pro' ),
				'roomDateTitle'            => sprintf( '%s: %%s - %%s', $this->get_accommodation_singular_name( $main_settings['accommodation_type'] ?? 'unit' ) ),
				'activeBookingMenuWarning' => __( 'This date has an active booking.', 'aiohm-booking-pro' ),
				'clearStatus'              => __( 'Clear Status', 'aiohm-booking-pro' ),
				'setStatus'                => __( 'Set Status:', 'aiohm-booking-pro' ),
				'chooseStatus'             => __( 'Choose status', 'aiohm-booking-pro' ),
				'customPrice'              => __( 'Custom Price (optional):', 'aiohm-booking-pro' ),
				'applyToAllUnits'          => __( 'Apply to all units on this day', 'aiohm-booking-pro' ),
				'pricePlaceholder'         => __( '0.00', 'aiohm-booking-pro' ),
				'reasonPlaceholder'        => __( 'Reason for status change', 'aiohm-booking-pro' ),
				'updateStatus'             => __( 'Update Status', 'aiohm-booking-pro' ),
				'updating'                 => __( 'Updating...', 'aiohm-booking-pro' ),
				'errorPrefix'              => __( 'Error: ', 'aiohm-booking-pro' ),
				'unknownError'             => __( 'Unknown error', 'aiohm-booking-pro' ),
				'errorProcessingResponse'  => __( 'Error processing response', 'aiohm-booking-pro' ),
				'filtering'                => __( 'Filtering...', 'aiohm-booking-pro' ),
				'resetting'                => __( 'Resetting...', 'aiohm-booking-pro' ),
				'showingAllDates'          => __( 'Showing all dates', 'aiohm-booking-pro' ),
				/* translators: %1$d: number of dates, %2$s: status name */
				'foundDates'               => __( 'Found %1$d dates with status: %2$s', 'aiohm-booking-pro' ),
				'carryOver'                => __( '(carry-over)', 'aiohm-booking-pro' ),
				'previouslyBlocked'        => __( 'Previously blocked', 'aiohm-booking-pro' ),
				'saving'                   => __( 'Saving...', 'aiohm-booking-pro' ),
				'saved'                    => __( 'Saved!', 'aiohm-booking-pro' ),
				'setStatusTo'              => __( 'Set status to', 'aiohm-booking-pro' ),
				'bulkUpdateDescription'    => __( 'Apply this status change to all accommodation units for this date', 'aiohm-booking-pro' ),
				'reasonLabel'              => __( 'Reason for blocking this date', 'aiohm-booking-pro' ),
				'reasonDescription'        => __( 'Optional reason for blocking this date (max 100 characters)', 'aiohm-booking-pro' ),
				'updateButtonDescription'  => __( 'Update the status for this date', 'aiohm-booking-pro' ),
			),
		);

		// Localize for calendar admin script
		wp_localize_script(
			'aiohm-booking-calendar-admin',
			'aiohm_booking_calendar',
			$localization_data
		);

		// Also localize for advanced calendar script (for mini calendar)
		wp_localize_script(
			'aiohm-booking-advanced-calendar',
			'aiohm_booking_calendar',
			$localization_data
		);
	}

	/**
	 * Render the calendar page.
	 */
	public function render_calendar_page() {
		// Initialize calendar with default or request parameters.
		$default_arguments = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for calendar display
		$period_param      = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for calendar display
		if ( empty( $period_param ) ) {
			$default_arguments = array(
				'period_type' => self::PERIOD_TYPE_CUSTOM,
			);
		}

		$this->initialize_calendar( $default_arguments );

		// Enqueue assets for calendar page.
		$this->enqueue_admin_assets();

		// Include the updated calendar template.
		include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-calendar.php';

		// Render AI insights section if enabled.
		$this->maybe_render_ai_insights();
	}


	/**
	 * Get the module name.
	 *
	 * @return string The module name.
	 */


	/**
	 * Get the settings fields for this module.
	 *
	 * @return array The settings fields configuration.
	 */
	public function get_settings_fields() {
		return array(
			'calendar_default_view'       => array(
				'type'        => 'select',
				'label'       => __( 'Default Calendar View', 'aiohm-booking-pro' ),
				'description' => __( 'Default time period to display when opening calendar', 'aiohm-booking-pro' ),
				'options'     => array(
					'custom' => __( 'Week View', 'aiohm-booking-pro' ),
					'month'  => __( 'Month View', 'aiohm-booking-pro' ),
				),
				'default'     => 'custom',
			),
			'calendar_unit_count'         => array(
				'type'        => 'number',
				/* translators: %s: accommodation plural name (e.g., "rooms", "units") */
				'label'       => sprintf( __( 'Number of %s', 'aiohm-booking-pro' ), $this->get_accommodation_plural_name() ),
				/* translators: %s: accommodation plural name (e.g., "rooms", "units") */
				'description' => sprintf( __( 'How many %s to display in the calendar', 'aiohm-booking-pro' ), strtolower( $this->get_accommodation_plural_name() ) ),
				'default'     => 1,
				'min'         => 1,
				'max'         => 20,
			),
			'calendar_show_prices'        => array(
				'type'        => 'checkbox',
				'label'       => __( 'Show Prices in Calendar', 'aiohm-booking-pro' ),
				'description' => __( 'Display pricing information in calendar cells', 'aiohm-booking-pro' ),
				'default'     => 0,
			),
			'calendar_allow_cell_editing' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Allow Cell Status Editing', 'aiohm-booking-pro' ),
				/* translators: %s: accommodation singular name (e.g., "room", "unit") */
				'description' => sprintf( __( 'Allow administrators to manually set %s availability status', 'aiohm-booking-pro' ), strtolower( $this->get_accommodation_singular_name( $settings['accommodation_type'] ?? 'unit' ) ) ),
				'default'     => 1,
			),
		);
	}

	/**
	 * Get the default settings for this module.
	 *
	 * @return array The default settings.
	 */
	protected function get_default_settings() {
		return array(
			'calendar_default_view'       => 'custom',
			'calendar_unit_count'         => 7,
			'calendar_show_prices'        => 0,
			'calendar_allow_cell_editing' => 1,
		);
	}

	/**
	 * Check if the module is enabled.
	 *
	 * @return bool Whether the module is enabled.
	 */
	protected function check_if_enabled() {
		$settings   = AIOHM_BOOKING_Settings::get_all();
		$enable_key = 'enable_' . $this->module_id;

		if ( isset( $settings[ $enable_key ] ) ) {
			return true === $settings[ $enable_key ] || 'true' === $settings[ $enable_key ] || 1 === $settings[ $enable_key ] || '1' === $settings[ $enable_key ];
		}

		// Default to enabled.
		return true;
	}

	/**
	 * Get the default calendar colors.
	 *
	 * @return array The default calendar colors.
	 */
	private function get_default_calendar_colors() {
		$css_manager_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'css_manager' );
		if ( ! $css_manager_module ) {
			// Fallback if CSS manager is not available.
			return array(
				'free'     => '#ffffff',
				'booked'   => '#e74c3c',
				'pending'  => '#f39c12',
				'external' => '#6c5ce7',
				'blocked'  => '#4b5563',
				'special'  => '#007cba',
				'private'  => '#28a745',
			);
		}
		$defaults      = $css_manager_module->get_default_css_settings();
		$status_colors = $defaults['status_colors'];

		return array(
			'free'     => $status_colors['--calendar-free'] ?? '#ffffff',
			'booked'   => $status_colors['--calendar-booked'] ?? '#e74c3c',
			'pending'  => $status_colors['--calendar-pending'] ?? '#f39c12',
			'external' => $status_colors['--calendar-external'] ?? '#6c5ce7',
			'blocked'  => $status_colors['--calendar-blocked'] ?? '#4b5563',
			'special'  => $status_colors['--calendar-special'] ?? '#007cba',
			'private'  => $status_colors['--calendar-private'] ?? '#28a745',
		);
	}

	// Calendar Initialization Methods.

	/**
	 * Initialize the calendar with provided attributes.
	 *
	 * @param array $attributes Optional. Calendar configuration attributes.
	 */
	private function initialize_calendar( $attributes = array() ) {
		$default_attributes = $this->get_default_attributes();
		$attributes         = array_merge( $default_attributes, $attributes );
		$attributes         = $this->parse_filter_attributes( $attributes );

		$this->set_calendar_properties( $attributes );
		$this->initialize_calendar_components();
	}

	/**
	 * Get the default attributes for the calendar.
	 *
	 * @return array The default attributes.
	 */
	private function get_default_attributes() {
		return array(
			'unit_type_id'       => self::ALL_ROOM_TYPES,
			'period_type'        => self::PERIOD_TYPE_CUSTOM,
			'period_page'        => 0,
			'custom_period_from' => new DateTime( 'today' ),
			'custom_period_to'   => new DateTime( '+' . self::DEFAULT_PERIOD_DAYS . ' days' ),
		);
	}

	/**
	 * Set the calendar properties from attributes.
	 *
	 * @param array $attributes The attributes to set.
	 */
	private function set_calendar_properties( $attributes ) {
		$this->unit_type_id = absint( $attributes['unit_type_id'] );
		$this->period_type  = $attributes['period_type'];
		$this->period_page  = $attributes['period_page'];

		if ( self::PERIOD_TYPE_CUSTOM === $this->period_type ) {
			$this->custom_period_from = $attributes['custom_period_from'];
			$this->custom_period_to   = $attributes['custom_period_to'];
		}
	}

	/**
	 * Initialize the calendar components.
	 */
	private function initialize_calendar_components() {
		$this->setup_calendar_period();
		$this->setup_unit_configuration();
		$this->setup_booking_data();
	}

	/**
	 * Set up the calendar period based on the period type.
	 */
	private function setup_calendar_period() {
		switch ( $this->period_type ) {
			case self::PERIOD_TYPE_QUARTER:
				$this->period = $this->create_quarter_period( $this->period_page );
				break;
			case self::PERIOD_TYPE_CUSTOM:
				$this->period = $this->create_custom_period();
				break;
			case self::PERIOD_TYPE_MONTH:
			default:
				$this->period = $this->create_month_period( $this->period_page );
				break;
		}

		$this->period_array      = iterator_to_array( $this->period );
		$this->period_end_date   = end( $this->period_array );
		$this->period_start_date = reset( $this->period_array );
	}

	/**
	 * Create a custom period based on the custom dates.
	 *
	 * @return DatePeriod The custom date period.
	 */
	private function create_custom_period() {
		$start_date = $this->custom_period_from;
		$end_date   = $this->custom_period_to;

		if ( $start_date > $end_date ) {
			list($start_date, $end_date) = array( $end_date, $start_date );
		}

		return $this->create_date_period( $start_date, $end_date, true );
	}

	/**
	 * Create a month period based on the month page.
	 *
	 * @param int $month_page The month page offset.
	 * @return DatePeriod The month date period.
	 */
	private function create_month_period( $month_page = 0 ) {
		$base_date = new DateTime( 'first day of this month' );
		$direction = $month_page < 0 ? '-' : '+';

		$first_day = clone $base_date;
		$first_day->modify( $direction . absint( $month_page ) . ' month' );

		$last_day = clone $first_day;
		$last_day->modify( 'last day of this month' );

		return $this->create_date_period( $first_day, $last_day, true );
	}

	/**
	 * Create a quarter period based on the quarter page.
	 *
	 * @param int $quarter_page The quarter page offset.
	 * @return DatePeriod The quarter date period.
	 */
	private function create_quarter_period( $quarter_page = 0 ) {
		$current_quarter = ceil( current_time( 'n' ) / 3 );
		$target_quarter  = $current_quarter + $quarter_page;
		$year            = current_time( 'Y' ) + floor( $target_quarter / 4 );
		$quarter         = $target_quarter % 4 ? $target_quarter % 4 : 4;

		$first_month = ( $quarter - 1 ) * 3 + 1;
		$last_month  = $quarter * 3;

		$first_day = new DateTime( $year . '-' . sprintf( '%02d', $first_month ) . '-01' );
		$last_day  = new DateTime( $year . '-' . sprintf( '%02d', $last_month ) . '-01' );
		$last_day->modify( 'last day of this month' );

		return $this->create_date_period( $first_day, $last_day, true );
	}

	/**
	 * Create a date period between two dates.
	 *
	 * @param DateTime $start_date The start date.
	 * @param DateTime $end_date The end date.
	 * @param bool     $include_end Whether to include the end date.
	 * @return DatePeriod The date period.
	 */
	private function create_date_period( $start_date, $end_date, $include_end = false ) {
		$interval = new DateInterval( 'P1D' );

		if ( $include_end ) {
			$end_date_extended = clone $end_date;
			$end_date_extended->modify( '+1 day' );
			return new DatePeriod( $start_date, $interval, $end_date_extended );
		}

		return new DatePeriod( $start_date, $interval, $end_date );
	}

	/**
	 * Set up the room configuration for the calendar.
	 */
	private function setup_unit_configuration() {
		$settings   = AIOHM_BOOKING_Settings::get_all();
		$unit_count = intval( $settings['calendar_unit_count'] ?? self::DEFAULT_ROOM_COUNT );

		// Get existing accommodation posts.
		$this->unit_posts = aiohm_booking_get_accommodation_posts( $unit_count );

		// If no posts exist, create fallback objects for display only.
		if ( empty( $this->unit_posts ) ) {
			$this->unit_posts   = array();
			$accommodation_type = aiohm_booking_get_current_accommodation_type();
			$singular_name      = aiohm_booking_get_accommodation_singular_name( $accommodation_type );

			for ( $unit_number = 1; $unit_number <= $unit_count; $unit_number++ ) {
				$room               = new stdClass();
				$room->ID           = $unit_number;
				$room->post_title   = $singular_name . ' ' . $unit_number;
				$this->unit_posts[] = $room;
			}
		}
	}

	/**
	 * Get the accommodation singular name for a given type.
	 *
	 * @param string $accommodation_type The accommodation type.
	 * @return string The singular name.
	 */
	private function get_accommodation_singular_name( $accommodation_type ) {
		return aiohm_booking_get_accommodation_singular_name( $accommodation_type );
	}

	/**
	 * Get the accommodation plural name.
	 *
	 * @return string The plural name.
	 */
	private function get_accommodation_plural_name() {
		$settings           = AIOHM_BOOKING_Settings::get_all();
		$accommodation_type = $settings['accommodation_type'] ?? 'unit';
		return aiohm_booking_get_accommodation_plural_name( $accommodation_type );
	}

	/**
	 * Set up the booking data for the calendar.
	 */
	private function setup_booking_data() {
		// This would integrate with the booking system.
		// For now, initialize empty booking data.
		$this->booking_data = array();
	}

	/**
	 * Parse filter attributes from GET parameters.
	 *
	 * @param array $defaults Default attributes.
	 * @return array Parsed attributes.
	 */
	private function parse_filter_attributes( $defaults = array() ) {
		$attributes = $defaults;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering
		if ( isset( $_GET['unit_type_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering
			$attributes['unit_type_id'] = absint( wp_unslash( $_GET['unit_type_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering
		if ( isset( $_GET['period'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_GET['period'] ) ), $this->get_available_periods() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering
			$attributes['period_type'] = sanitize_text_field( wp_unslash( $_GET['period'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for filtering
			$attributes                = $this->parse_custom_period_attributes( $attributes );
			$attributes                = $this->parse_period_page_attributes( $attributes );
		}

		$attributes = $this->parse_period_navigation( $attributes );

		return $attributes;
	}

	/**
	 * Parse custom period attributes from GET parameters.
	 *
	 * @param array $attributes Current attributes.
	 * @return array Updated attributes.
	 */
	private function parse_custom_period_attributes( $attributes ) {
		if ( self::PERIOD_TYPE_CUSTOM !== $attributes['period_type'] ) {
			return $attributes;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for custom period
		if ( ! empty( $_GET['custom_period_from'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for custom period
			$date_string = sanitize_text_field( wp_unslash( $_GET['custom_period_from'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for custom period
			if ( AIOHM_BOOKING_Validation::validate_date( $date_string ) ) {
				$attributes['custom_period_from'] = DateTime::createFromFormat( 'Y-m-d', $date_string );
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for custom period
		if ( ! empty( $_GET['custom_period_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for custom period
			$date_string = sanitize_text_field( wp_unslash( $_GET['custom_period_to'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for custom period
			if ( AIOHM_BOOKING_Validation::validate_date( $date_string ) ) {
				$attributes['custom_period_to'] = DateTime::createFromFormat( 'Y-m-d', $date_string );
			}
		}

		return $attributes;
	}

	/**
	 * Parse period page attributes from GET parameters.
	 *
	 * @param array $attributes Current attributes.
	 * @return array Updated attributes.
	 */
	private function parse_period_page_attributes( $attributes ) {
		$page_parameter = 'period_page_' . $attributes['period_type'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for pagination
		if ( isset( $_GET[ $page_parameter ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for pagination
			$attributes['period_page'] = intval( wp_unslash( $_GET[ $page_parameter ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for pagination
		}

		return $attributes;
	}

	/**
	 * Parse period navigation attributes from GET parameters.
	 *
	 * @param array $attributes Current attributes.
	 * @return array Updated attributes.
	 */
	private function parse_period_navigation( $attributes ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for navigation
		if ( isset( $_GET['action_period_next'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for navigation
			if ( self::PERIOD_TYPE_CUSTOM === $attributes['period_type'] ) {
				$attributes = $this->shift_custom_period_forward( $attributes );
			} else {
				++$attributes['period_page'];
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for navigation
		if ( isset( $_GET['action_period_prev'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for navigation
			if ( self::PERIOD_TYPE_CUSTOM === $attributes['period_type'] ) {
				$attributes = $this->shift_custom_period_backward( $attributes );
			} else {
				--$attributes['period_page'];
			}
		}

		return $attributes;
	}

	/**
	 * Shift the custom period forward by its duration.
	 *
	 * @param array $attributes Current attributes.
	 * @return array Updated attributes.
	 */
	private function shift_custom_period_forward( $attributes ) {
		$days_difference = $attributes['custom_period_from']->diff( $attributes['custom_period_to'] )->days;

		$attributes['custom_period_from'] = clone $attributes['custom_period_from'];
		$attributes['custom_period_from']->modify( '+' . ( $days_difference + 1 ) . ' days' );

		$attributes['custom_period_to'] = clone $attributes['custom_period_from'];
		$attributes['custom_period_to']->modify( '+' . $days_difference . ' days' );

		return $attributes;
	}

	/**
	 * Shift the custom period backward by its duration.
	 *
	 * @param array $attributes Current attributes.
	 * @return array Updated attributes.
	 */
	private function shift_custom_period_backward( $attributes ) {
		$days_difference = $attributes['custom_period_from']->diff( $attributes['custom_period_to'] )->days;

		$attributes['custom_period_from'] = clone $attributes['custom_period_from'];
		$attributes['custom_period_from']->modify( '-' . ( $days_difference + 1 ) . ' days' );

		$attributes['custom_period_to'] = clone $attributes['custom_period_from'];
		$attributes['custom_period_to']->modify( '+' . $days_difference . ' days' );

		return $attributes;
	}

	/**
	 * Get the available period types.
	 *
	 * @return array Available period types.
	 */
	public static function get_available_periods() {
		return array(
			self::PERIOD_TYPE_CUSTOM => __( 'Week', 'aiohm-booking-pro' ),
			self::PERIOD_TYPE_MONTH  => __( 'Month', 'aiohm-booking-pro' ),
		);
	}

	// Calendar Rendering Methods.

	/**
	 * Render the complete calendar interface.
	 *
	 * @return void
	 */
	public function render_calendar() {
		$period_type = $this->period_type;
		if ( self::PERIOD_TYPE_CUSTOM === $period_type ) {
			$period_type .= '-period';
		}

		$unit_count    = count( $this->unit_posts );
		$calendar_size = ( $unit_count > 5 ) ? 'default' : 'large';
		?>
		<div class="aiohm-bookings-calendar-wrapper">
		<?php $this->render_calendar_filters(); ?>
			<div class="aiohm-booking-calendar-single-wrapper <?php echo esc_attr( "aiohm-booking-calendar-{$period_type}-table" ); ?> <?php echo esc_attr( "aiohm-booking-calendar-size-{$calendar_size}" ); ?>">
		<?php $this->render_single_calendar_table(); ?>
			</div>
		<?php $this->render_booking_details_popup(); ?>
		<?php $this->render_footer_filters(); ?>
		</div>
		<?php
	}

	/**
	 * Render the calendar filters.
	 */
	private function render_calendar_filters() {
		?>
		<div class="aiohm-bookings-calendar-filters-wrapper">
			<form id="aiohm-bookings-calendar-filters" method="get" class="wp-filter">
		<?php $this->render_hidden_form_parameters(); ?>
				<div class="aiohm-bookings-calendar-controls">
					<div class="aiohm-filter-group">
		<?php $this->render_period_filter_controls(); ?>
		<?php submit_button( __( 'Show', 'aiohm-booking-pro' ), 'button aiohm-show-button', 'action_filter', false ); ?>
					</div>
		<?php $this->render_calendar_legend(); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render hidden form parameters.
	 */
	private function render_hidden_form_parameters() {
		$parameters = array();
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for form rendering
			$parameters['page'] = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for form rendering
		}

		foreach ( $parameters as $param_name => $param_value ) {
			printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $param_name ), esc_attr( $param_value ) );
		}
	}

	/**
	 * Render the period filter controls.
	 */
	private function render_period_filter_controls() {
		$available_periods = $this->get_available_periods();

		foreach ( $available_periods as $period => $period_label ) {
			if ( self::PERIOD_TYPE_CUSTOM === $period ) {
				continue;
			}

			$period_page = $this->period_type === $period ? $this->period_page : 0;
			printf(
				'<input type="hidden" name="period_page_%s" value="%s" />',
				esc_attr( $period ),
				esc_attr( $period_page )
			);
		}

		echo '<label for="aiohm-bookings-calendar-filter-period">' . esc_html__( 'Period:', 'aiohm-booking-pro' ) . '</label>';

		submit_button(
			'&lt;',
			'button aiohm-period-prev',
			'action_period_prev',
			false,
			array( 'title' => __( '&lt; Prev', 'aiohm-booking-pro' ) )
		);

		echo '<select id="aiohm-bookings-calendar-filter-period" name="period">';
		foreach ( $available_periods as $period => $period_label ) {
			printf(
				'<option %s value="%s">%s</option>',
				selected( $this->period_type, $period, false ),
				esc_attr( $period ),
				esc_html( $period_label )
			);
		}
		echo '</select>';

		submit_button(
			'&gt;',
			'button aiohm-period-next',
			'action_period_next',
			false,
			array( 'title' => __( 'Next &gt;', 'aiohm-booking-pro' ) )
		);

		$this->render_custom_period_inputs();
	}

	/**
	 * Render the custom period input fields.
	 */
	private function render_custom_period_inputs() {
		$custom_period_class = ' aiohm-hide';

		$date_from = ! is_null( $this->custom_period_from ) ? $this->custom_period_from->format( 'Y-m-d' ) : '';
		$date_to   = ! is_null( $this->custom_period_to ) ? $this->custom_period_to->format( 'Y-m-d' ) : '';
		?>
		<div class="aiohm-custom-period-wrapper<?php echo esc_attr( $custom_period_class ); ?>">
			<input type="date" class="aiohm-custom-period-from" name="custom_period_from"
					placeholder="<?php esc_attr_e( 'From', 'aiohm-booking-pro' ); ?>"
					value="<?php echo esc_attr( $date_from ); ?>" />
			<input type="date" class="aiohm-custom-period-to" name="custom_period_to"
					placeholder="<?php esc_attr_e( 'Until', 'aiohm-booking-pro' ); ?>"
					value="<?php echo esc_attr( $date_to ); ?>" />
		</div>
		<?php
	}

	/**
	 * Render the calendar legend.
	 */
	private function render_calendar_legend() {
		?>
		<div class="aiohm-calendar-legend" aria-label="Calendar legend">
			<!-- Booking Status Colors -->
			<div class="legend-group">
				<span class="legend-group-title"><?php esc_html_e( 'Booking Status:', 'aiohm-booking-pro' ); ?></span>
				<span class="legend-item"><span class="legend-dot legend-free" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Free', 'aiohm-booking-pro' ); ?></span></span>
				<span class="legend-item"><span class="legend-dot legend-booked" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Booked', 'aiohm-booking-pro' ); ?></span></span>
				<span class="legend-item"><span class="legend-dot legend-pending" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Pending', 'aiohm-booking-pro' ); ?></span></span>
				<span class="legend-item"><span class="legend-dot legend-external" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'External', 'aiohm-booking-pro' ); ?></span></span>
				<span class="legend-item"><span class="legend-dot legend-blocked" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Blocked', 'aiohm-booking-pro' ); ?></span></span>
			</div>
			<!-- Badge Indicators -->
			<div class="legend-group">
				<span class="legend-group-title"><?php esc_html_e( 'Event Flags:', 'aiohm-booking-pro' ); ?></span>
				<span class="legend-item"><span class="legend-dot legend-private" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'Private Event', 'aiohm-booking-pro' ); ?></span></span>
				<span class="legend-item"><span class="legend-dot legend-special" aria-hidden="true"></span><span class="legend-text"><?php esc_html_e( 'High Season', 'aiohm-booking-pro' ); ?></span></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single calendar table.
	 */
	private function render_single_calendar_table() {
		?>
		<div class="aiohm-bookings-calendar-holder">
			<table class="aiohm-bookings-single-calendar-table wp-list-table fixed">
				<thead>
					<tr>
						<th class="accommodation-column"><?php esc_html_e( 'Accommodations', 'aiohm-booking-pro' ); ?></th>
		<?php foreach ( $this->period_array as $date ) : ?>
			<?php
			$is_today     = $date->format( 'Y-m-d' ) === current_time( 'Y-m-d' );
			$header_class = $is_today ? 'aiohm-date-today' : '';
			?>
							<th colspan="2" class="date-column <?php echo esc_attr( $header_class ); ?>">
								<div class="aiohm-date-header">
									<span class="aiohm-date-main"><?php echo esc_html( $date->format( 'j M' ) ); ?></span>
									<span class="aiohm-date-year"><?php echo esc_html( $date->format( 'Y' ) ); ?></span>
									<span class="aiohm-date-weekday"><?php echo esc_html( $date->format( 'D' ) ); ?></span>
								</div>
							</th>
		<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
		<?php if ( ! empty( $this->unit_posts ) ) : ?>
			<?php foreach ( $this->unit_posts as $unit_post ) : ?>
							<tr room-id="<?php echo esc_attr( $unit_post->ID ); ?>">
								<td class="accommodation-column">
									<a href="#" class="aiohm-accommodation-link" data-accommodation-id="<?php echo esc_attr( $unit_post->ID ); ?>">
				<?php echo esc_html( $unit_post->post_title ); ?>
									</a>
								</td>
				<?php foreach ( $this->period_array as $date ) : ?>
					<?php $this->render_calendar_cell( $unit_post->ID, $date ); ?>
				<?php endforeach; ?>
							</tr>
			<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td class="accommodation-column"><?php /* translators: %s: accommodation plural name (e.g., "rooms", "units") */ echo esc_html( sprintf( __( 'No %s configured', 'aiohm-booking-pro' ), $this->get_accommodation_plural_name() ) ); ?></td>
							<td class="aiohm-no-accommodations-found" colspan="<?php echo esc_attr( count( $this->period_array ) * 2 ); ?>">
						<?php /* translators: %s: accommodation plural name (e.g., "rooms", "units") */ echo esc_html( sprintf( __( 'No %s configured.', 'aiohm-booking-pro' ), $this->get_accommodation_plural_name() ) ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render a single calendar cell for a unit and date.
	 *
	 * @param int    $unit_id The unit/accommodation ID.
	 * @param object $date    The date object for the cell.
	 */
	private function render_calendar_cell( $unit_id, $date ) {
		$date_string = $date->format( 'Y-m-d' );
		$is_today    = current_time( 'Y-m-d' ) === $date_string;

		$first_class  = $is_today ? ' aiohm-date-today aiohm-date-free' : ' aiohm-date-free';
		$second_class = $is_today ? ' aiohm-date-today aiohm-date-free' : ' aiohm-date-free';

		$edit_attributes = '';
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
			$edit_attributes = ' data-editable="true"';
			$first_class    .= ' aiohm-editable-cell';
			$second_class   .= ' aiohm-editable-cell';
		}

		$date_title = $date->format( 'D j, M Y' ) . ': ' . __( 'Available', 'aiohm-booking-pro' );
		?>
		<td class="aiohm-date-first-part<?php echo esc_attr( $first_class ); ?>"
			data-accommodation-id="<?php echo esc_attr( $unit_id ); ?>"
			data-date="<?php echo esc_attr( $date_string ); ?>"
			data-part="first"
			title="<?php echo esc_attr( $date_title ); ?>"<?php echo wp_kses( $edit_attributes, array() ); ?>>
		</td>
		<td class="aiohm-date-second-part<?php echo esc_attr( $second_class ); ?>"
			data-accommodation-id="<?php echo esc_attr( $unit_id ); ?>"
			data-date="<?php echo esc_attr( $date_string ); ?>"
			data-part="second"
			title="<?php echo esc_attr( $date_title ); ?>"<?php echo wp_kses( $edit_attributes, array() ); ?>>
		</td>
		<?php
	}

	/**
	 * Render the booking details popup modal.
	 */
	private function render_booking_details_popup() {
		?>
		<div id="aiohm-bookings-calendar-popup" class="aiohm-popup aiohm-hide">
			<div class="aiohm-popup-backdrop"></div>
			<div class="aiohm-popup-body">
				<div class="aiohm-header">
					<h2 class="aiohm-title aiohm-inline"><?php esc_html_e( 'Booking Details', 'aiohm-booking-pro' ); ?></h2>
					<button class="aiohm-close-popup-button dashicons dashicons-no-alt"></button>
				</div>
				<div class="aiohm-content"></div>
				<div class="aiohm-footer">
					<a href="#" class="button button-primary aiohm-edit-button"><?php esc_html_e( 'View Order', 'aiohm-booking-pro' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the footer filters for the calendar.
	 */
	private function render_footer_filters() {
		?>
		<div class="aiohm-bookings-calendar-footer-wrapper">
			<div class="aiohm-bookings-calendar-controls">
				<div class="aiohm-filter-group">
					<label for="aiohm-calendar-status-filter"><?php esc_html_e( 'Filter by Status:', 'aiohm-booking-pro' ); ?></label>
					<select id="aiohm-calendar-status-filter" class="aiohm-status-filter">
						<option value=""><?php esc_html_e( 'Show All', 'aiohm-booking-pro' ); ?></option>
						<option value="free"><?php esc_html_e( 'Free/Available', 'aiohm-booking-pro' ); ?></option>
						<option value="booked"><?php esc_html_e( 'Booked', 'aiohm-booking-pro' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', 'aiohm-booking-pro' ); ?></option>
						<option value="external"><?php esc_html_e( 'External', 'aiohm-booking-pro' ); ?></option>
						<option value="blocked"><?php esc_html_e( 'Blocked', 'aiohm-booking-pro' ); ?></option>
					</select>
					<button type="button" id="aiohm-calendar-search-btn" class="button aiohm-search-button">
		<?php esc_html_e( 'Filter Calendar', 'aiohm-booking-pro' ); ?>
					</button>
					<button type="button" id="aiohm-calendar-reset-btn" class="button aiohm-reset-button">
		<?php esc_html_e( 'Show All', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	// AJAX Handlers.

	/**
	 * Handle AJAX request to set date status.
	 */
	public function handle_set_date_status() {
		// Verify nonce for security.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Sanitize input data.
		$accommodation_id = isset( $_POST['accommodation_id'] ) ? absint( wp_unslash( $_POST['accommodation_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$date             = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$status           = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$reason           = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		// Fix boolean conversion - handle string 'false' correctly.
		$apply_to_all_raw = isset( $_POST['apply_to_all'] ) ? sanitize_text_field( wp_unslash( $_POST['apply_to_all'] ) ) : 'false'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$apply_to_all     = 'true' === $apply_to_all_raw || true === $apply_to_all_raw;

		// Validate required fields.
		if ( ! $date ) {
			wp_send_json_error( 'Missing required fields' );
		}

		// Validate status values.
		$valid_statuses = array( 'free', 'booked', 'pending', 'external', 'blocked', 'special', 'private' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			wp_send_json_error( 'Invalid status value' );
		}

		// Get or create cell status data storage.
		$option_name   = 'aiohm_booking_cell_statuses';
		$cell_statuses = get_option( $option_name, array() );
		$cell_key      = ''; // Initialize cell key.

		if ( $apply_to_all ) {
			// Get all accommodation IDs - we need to find all accommodations for bulk update.
			$accommodations = get_posts(
				array(
					'post_type'      => 'aiohm_accommodation',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				)
			);

			if ( empty( $accommodations ) ) {
				wp_send_json_error( 'No accommodations found for bulk update' );
			}

			foreach ( $accommodations as $acc_id ) {
				$cell_key                   = $acc_id . '_' . $date . '_full';
				$cell_statuses[ $cell_key ] = array(
					'accommodation_id' => $acc_id,
					'date'             => $date,
					'part'             => 'full',
					'status'           => $status,
					'price'            => 0,
					'reason'           => $reason,
					'updated_at'       => current_time( 'mysql' ),
				);
			}
		} else {
			// Single accommodation update.
			if ( ! $accommodation_id ) {
				wp_send_json_error( 'Missing accommodation ID' );
			}

			$cell_key                   = $accommodation_id . '_' . $date . '_full';
			$cell_statuses[ $cell_key ] = array(
				'accommodation_id' => $accommodation_id,
				'date'             => $date,
				'part'             => 'full',
				'status'           => $status,
				'price'            => 0,
				'reason'           => $reason,
				'updated_at'       => current_time( 'mysql' ),
			);
		}

		// Save to database.
		$old_cell_statuses = get_option( $option_name, array() );
		$saved             = update_option( $option_name, $cell_statuses );

		// Check if save was successful or data was already the same.
		$current_data = get_option( $option_name, array() );
		$data_matches = isset( $current_data[ $apply_to_all ? array_key_first( $cell_statuses ) : $cell_key ] );

		if ( $saved || $data_matches ) {
			wp_send_json_success( 'Status updated successfully' );
		} else {
			wp_send_json_error( 'Failed to save status' );
		}
	}

	/**
	 * Handle AJAX request to update cell status.
	 */
	public function handle_update_cell_status() {
		// Verify nonce for security.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		// Sanitize input data.
		$accommodation_id = isset( $_POST['accommodation_id'] ) ? absint( wp_unslash( $_POST['accommodation_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$date             = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$part             = isset( $_POST['part'] ) ? sanitize_text_field( wp_unslash( $_POST['part'] ) ) : 'full'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$status           = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'free'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$price            = isset( $_POST['price'] ) ? floatval( wp_unslash( $_POST['price'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		// Properly handle apply_to_all boolean - PHP converts string "false" to TRUE, so check explicitly.
		$apply_to_all = false;
		if ( isset( $_POST['apply_to_all'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
			$apply_to_all_value = sanitize_text_field( wp_unslash( $_POST['apply_to_all'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
			// Only set to true if it's actually true/1/yes, not just any non-empty string.
			$apply_to_all = in_array( strtolower( $apply_to_all_value ), array( 'true', '1', 'yes', 'on' ), true );
		}

		// Validate required fields.
		if ( ! $date ) {
			wp_send_json_error( 'Missing required fields' );
			return;
		}

		// Validate status values.
		$valid_statuses = array( 'free', 'booked', 'pending', 'external', 'blocked', 'special', 'private' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			wp_send_json_error( 'Invalid status value' );
			return;
		}

		// Get or create cell status data storage.
		$option_name   = 'aiohm_booking_cell_statuses';
		$cell_statuses = get_option( $option_name, array() );

		if ( $apply_to_all ) {
			// Get all accommodation posts - we need to find all accommodations for bulk update.
			$accommodations = aiohm_booking_get_accommodation_posts( -1 );

			// Extract IDs from posts.
			$accommodation_ids = array();
			foreach ( $accommodations as $accommodation ) {
				$accommodation_ids[] = $accommodation->ID;
			}

			if ( empty( $accommodation_ids ) ) {
				wp_send_json_error( 'No accommodations found to update' );
				return;
			}

			foreach ( $accommodation_ids as $acc_id ) {
				$cell_key                   = $acc_id . '_' . $date . '_' . $part;
				$cell_statuses[ $cell_key ] = array(
					'accommodation_id' => $acc_id,
					'date'             => $date,
					'part'             => $part,
					'status'           => $status,
					'price'            => 0,
					'updated_at'       => current_time( 'mysql' ),
				);
			}
		} else {
			// Single accommodation update.
			if ( ! $accommodation_id ) {
				wp_send_json_error( 'Missing accommodation ID' );
				return;
			}

			// Create unique key for this cell.
			$cell_key = $accommodation_id . '_' . $date . '_' . $part;

			// Store the cell status.
			$cell_statuses[ $cell_key ] = array(
				'accommodation_id' => $accommodation_id,
				'date'             => $date,
				'part'             => $part,
				'status'           => $status,
				'price'            => $price,
				'updated_at'       => current_time( 'mysql' ),
			);
		}

		// Save to database.
		$saved = update_option( $option_name, $cell_statuses );

		if ( $saved ) {
			// Clear frontend calendar cache so changes appear immediately.
			delete_transient( 'aiohm_booking_calendar_cache' );

			// Also clear any accommodation caches.
			if ( class_exists( 'AIOHM_BOOKING_Module_Accommodation' ) ) {
				$registry             = AIOHM_BOOKING_Module_Registry::instance();
				$accommodation_module = $registry->get_module( 'accommodations' );
				if ( $accommodation_module && method_exists( $accommodation_module, 'clear_settings_cache' ) ) {
					$accommodation_module->clear_settings_cache();
				}
				if ( $accommodation_module && method_exists( $accommodation_module, 'clear_accommodations_cache' ) ) {
					$accommodation_module->clear_accommodations_cache();
				}
			}

			wp_send_json_success(
				array(
					'message' => 'Cell status updated successfully',
					'data'    => array(
						'accommodation_id' => $accommodation_id,
						'date'             => $date,
						'part'             => $part,
						'status'           => $status,
						'price'            => $price,
					),
				)
			);
		} else {
			wp_send_json_error( 'Failed to save cell status' );
		}
	}

	/**
	 * Handle AJAX request to reset all days.
	 */
	public function handle_reset_all_days() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		$nonce_result = wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' );

		if ( ! $nonce_result ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		try {
			// Clear various calendar data storage options.
			$reset_count = 0;

			// Clear manual calendar status overrides.
			if ( delete_option( 'aiohm_booking_calendar_status_overrides' ) ) {
				++$reset_count;
			}

			// Clear manual date blocks/availability.
			if ( delete_option( 'aiohm_booking_calendar_manual_status' ) ) {
				++$reset_count;
			}

			// Clear cell-specific status data (THIS IS THE MAIN ONE!).
			if ( delete_option( 'aiohm_booking_cell_statuses' ) ) {
				++$reset_count;
			}

			// Clear date-specific pricing.
			if ( delete_option( 'aiohm_booking_calendar_pricing_overrides' ) ) {
				++$reset_count;
			}

			// Clear private events.
			if ( delete_option( 'aiohm_booking_private_events' ) ) {
				++$reset_count;
			}

			// Clear accommodation-specific calendar data.
			$settings                 = get_option( 'aiohm_booking_settings', array() );
			$available_accommodations = intval( $settings['available_accommodations'] ?? 1 );

			for ( $i = 1; $i <= $available_accommodations; $i++ ) {
				if ( delete_option( "aiohm_booking_calendar_accommodation_{$i}" ) ) {
					++$reset_count;
				}
			}

			// Clear any temporary calendar cache.
			if ( delete_transient( 'aiohm_booking_calendar_cache' ) ) {
				++$reset_count;
			}

			// Clear demo data mode flag (forces calendar to show real data only).
			if ( delete_option( 'aiohm_booking_calendar_demo_mode' ) ) {
				++$reset_count;
			}

			// Set flag to disable demo data generation.
			update_option( 'aiohm_booking_calendar_disable_demo', true );
			++$reset_count;

			// Clear booking-related data that might affect calendar display.
			global $wpdb;

			// Delete calendar-related meta data (if using custom tables).
			$table_name   = $wpdb->prefix . 'aiohm_booking_calendar_data';
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
			if ( $table_exists === $table_name ) {
				$deleted = $wpdb->query(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for calendar data query
					$wpdb->prepare(
						'DELETE FROM %s WHERE status IN (%s, %s, %s)',
						$table_name,
						'blocked',
						'external',
						'manual_override'
					)
				);
				if ( false !== $deleted ) {
					$reset_count += $deleted;
				}
			}

			// Clear any actual booking orders (use with caution).
			$booking_posts = get_posts(
				array(
					'post_type'   => 'aiohm_booking',
					'post_status' => 'any',
					'numberposts' => -1,
				)
			);

			foreach ( $booking_posts as $booking ) {
				wp_delete_post( $booking->ID, true ); // Force delete.
				++$reset_count;
			}

			// Log the reset action.
			wp_send_json_success( "Successfully reset calendar data and disabled demo mode. Cleared {$reset_count} entries. Calendar will now show actual booking data only." );

		} catch ( Exception $e ) {
			wp_send_json_error( 'Error resetting calendar: ' . $e->getMessage() );
		}
	}

	/**
	 * Clear calendar cache when settings are updated
	 */
	public function clear_calendar_cache() {
		delete_transient( 'aiohm_booking_calendar_cache' );
	}

	/**
	 * Handle AJAX request to set private event.
	 */
	public function handle_set_private_event() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$date             = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$event_price      = isset( $_POST['event_price'] ) ? floatval( wp_unslash( $_POST['event_price'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$event_name       = isset( $_POST['event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['event_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$is_private_event = isset( $_POST['is_private_event'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['is_private_event'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above

		// Determine special pricing.
		$is_special_pricing = $event_price > 0;

		// Validate required fields - need at least event name, private toggle, or special pricing.
		if ( empty( $event_name ) && ! $is_private_event && ! $is_special_pricing ) {
			wp_send_json_error( 'Please enter either an Event Name, enable Private Event, or set High Season pricing' );
		}

		if ( $is_special_pricing && $event_price <= 0 ) {
			wp_send_json_error( 'High Season price must be greater than 0' );
		}

		// Validate date format.
		if ( ! AIOHM_BOOKING_Validation::validate_date( $date ) ) {
			wp_send_json_error( 'Invalid date format' );
		}

		// Get existing private events.
		$private_events = get_option( 'aiohm_booking_private_events', array() );

		// Save the private event with dual mode support.
		$private_events[ $date ] = array(
			'name'               => $event_name,
			'price'              => $event_price,
			'is_private_event'   => $is_private_event,
			'is_special_pricing' => $is_special_pricing,
			'created'            => current_time( 'mysql' ),
			'created_by'         => get_current_user_id(),
		);

		// Update the option.
		$saved = update_option( 'aiohm_booking_private_events', $private_events );

		if ( $saved || isset( $private_events[ $date ] ) ) {
			wp_send_json_success(
				array(
					'message' => 'Private event saved successfully',
					'event'   => $private_events[ $date ],
				)
			);
		} else {
			wp_send_json_error( 'Failed to save private event' );
		}
	}

	/**
	 * Handle AJAX request to remove private event.
	 */
	public function handle_remove_private_event() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => 'Insufficient permissions',
					)
				)
			);
		}

		$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above

		if ( empty( $date ) ) {
			wp_send_json_error( 'Date is required' );
		}

		// Validate date format
		if ( ! AIOHM_BOOKING_Validation::validate_date( $date ) ) {
			wp_send_json_error( 'Invalid date format' );
		}

		// Get existing private events.
		$private_events = get_option( 'aiohm_booking_private_events', array() );

		// Check if event exists for this date.
		if ( ! isset( $private_events[ $date ] ) ) {
			wp_send_json_error( 'No private event found for this date' );
		}

		// Store event data for response.
		$removed_event = $private_events[ $date ];

		// Remove the event.
		unset( $private_events[ $date ] );

		// Save updated events list.
		if ( update_option( 'aiohm_booking_private_events', $private_events ) ) {
			wp_send_json_success(
				array(
					'message'       => 'Private event removed successfully',
					'date'          => $date,
					'removed_event' => $removed_event,
				)
			);
		} else {
			wp_send_json_error( 'Failed to remove private event' );
		}
	}

	/**
	 * Handle AJAX request to get private events.
	 */
	public function handle_get_private_events() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => 'Invalid nonce',
					)
				)
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => 'Insufficient permissions',
					)
				)
			);
		}

		// Here you would get private events and return HTML.
		// For now, return empty.
		wp_die(
			wp_json_encode(
				array(
					'success' => true,
					'data'    => array(
						'html'  => '<em style="color: #666;">' . __( 'No private events currently set.', 'aiohm-booking-pro' ) . '</em>',
						'count' => 0,
					),
				)
			)
		);
	}

	/**
	 * Handle AJAX request to sync calendar.
	 */
	public function handle_sync_calendar() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_die(
				json_encode(
					array(
						'success' => false,
						'data'    => 'Invalid nonce',
					)
				)
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => 'Insufficient permissions',
					)
				)
			);
		}

		$source        = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$accommodation = isset( $_POST['accommodation'] ) ? sanitize_text_field( wp_unslash( $_POST['accommodation'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above

		// Here you would sync with external calendar.
		// For now, just return success.
		wp_die( wp_json_encode( array( 'success' => true ) ) );
	}

	/**
	 * Handle AJAX request to save calendar color.
	 */
	public function handle_save_calendar_color() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$color  = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above

		// Validate status.
		$valid_statuses = array( 'free', 'booked', 'pending', 'external', 'blocked', 'special', 'private', 'private_event', 'high_season' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			wp_send_json_error( 'Invalid status' );
		}

		// Validate color.
		if ( ! $color ) {
			wp_send_json_error( 'Invalid color format' );
		}

		// Get existing calendar colors.
		$calendar_colors = get_option( 'aiohm_booking_calendar_colors', array() );

		// Update the specific status color.
		$calendar_colors[ $status ] = $color;

		// Save to database.
		if ( update_option( 'aiohm_booking_calendar_colors', $calendar_colors ) ) {
			wp_send_json_success(
				array(
					'message' => 'Calendar color saved successfully',
					'status'  => $status,
					'color'   => $color,
				)
			);
		} else {
			wp_send_json_error( 'Failed to save color' );
		}
	}

	/**
	 * Handle AJAX request to reset all calendar colors.
	 */
	public function handle_reset_all_calendar_colors() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Get default colors.
		$default_colors = $this->get_default_calendar_colors();

		// Reset all colors to defaults by deleting the option (will use defaults).
		// delete_option returns false if option doesn't exist, but that's still success for reset.
		$option_existed = get_option( 'aiohm_booking_calendar_colors', false ) !== false;
		delete_option( 'aiohm_booking_calendar_colors' );

		wp_send_json_success(
			array(
				'message' => 'All calendar colors reset to default successfully',
				'colors'  => $default_colors,
				'reset'   => $option_existed ? 'Custom colors removed' : 'Already using defaults',
			)
		);
	}

	/**
	 * Handle frontend calendar availability requests
	 * Available for both logged-in and non-logged-in users
	 */
	public function handle_get_calendar_availability() {
		// For frontend requests, we don't need strict nonce verification.
		// since this is public data, but we'll still validate the request.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Frontend request for public data

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Frontend request for public data
		$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Frontend request for public data
		$unit_id    = isset( $_POST['unit_id'] ) ? absint( wp_unslash( $_POST['unit_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Frontend request for public data

		// Basic validation.
		if ( empty( $start_date ) || empty( $end_date ) ) {
			wp_send_json_error( 'Missing required date parameters' );
		}

		// Validate date formats.
		if ( ! AIOHM_BOOKING_Validation::validate_date( $start_date ) || ! AIOHM_BOOKING_Validation::validate_date( $end_date ) ) {
			wp_send_json_error( 'Invalid date format' );
		}

		try {
			$start = new DateTime( $start_date );
			$end   = new DateTime( $end_date );

			// Prevent requests for too large date ranges (max 3 months).
			$interval = $start->diff( $end );
			if ( $interval->days > 90 ) {
				wp_send_json_error( 'Date range too large' );
			}

			// Generate availability data for the date range.
			$availability_data = array();

			$current_date = clone $start;
			while ( $current_date <= $end ) {
				$date_string = $current_date->format( 'Y-m-d' );

				// Get status from various sources.
				$status = $this->get_date_status( $date_string, $unit_id );

				// Get private event data for badges.
				$private_events     = get_option( 'aiohm_booking_private_events', array() );
				$private_event_data = $private_events[ $date_string ] ?? null;

				$badges = array();
				if ( $private_event_data ) {
					// Handle both old and new data structures.
					$is_private_event = isset( $private_event_data['is_private_event'] )
					? ! empty( $private_event_data['is_private_event'] )
					: ( 'private' === $private_event_data['mode'] || 'both' === $private_event_data['mode'] );

					$is_special_pricing = isset( $private_event_data['is_special_pricing'] )
					? ! empty( $private_event_data['is_special_pricing'] )
					: ( 'special' === $private_event_data['mode'] || 'both' === $private_event_data['mode'] );

					if ( $is_private_event ) {
						$badges['private'] = true;
					}
					if ( $is_special_pricing && ! empty( $private_event_data['price'] ) && $private_event_data['price'] > 0 ) {
						$badges['special'] = true;
					}
				}

				// Get detailed unit breakdown for tooltip information.
				$unit_details = $this->get_unit_breakdown( $date_string );

				// Get early bird pricing information
				$global_settings   = get_option( 'aiohm_booking_settings', array() );
				$enable_early_bird = $global_settings['enable_early_bird'] ?? false;
				$early_bird_days   = intval( $global_settings['early_bird_days'] ?? 30 );
				// Get accommodation early bird settings
				$early_bird_settings      = AIOHM_BOOKING_Early_Bird_Helper::get_accommodation_early_bird_settings();
				$default_early_bird_price = $early_bird_settings['default_price'];

				// Calculate early bird pricing for this date
				$early_bird_price = 0;
				if ( $enable_early_bird ) {
					$today      = new DateTime();
					$check_date = new DateTime( $date_string );
					$days_until = $today->diff( $check_date )->days;

					if ( $days_until >= $early_bird_days ) {
						// Use special pricing if available, otherwise use default early bird price
						if ( ! empty( $private_event_data['price'] ) && $private_event_data['price'] > 0 ) {
							$early_bird_price = $private_event_data['price']; // Use same price as regular price
						} else {
							$early_bird_price = $default_early_bird_price;
						}
					}
				}

				$availability_data[ $date_string ] = array(
					'status'           => $status,
					'available'        => 'free' === $status,
					'date'             => $date_string,
					'is_private_event' => $this->is_private_event( $date_string ),
					'event_name'       => $this->get_private_event_name( $date_string ),
					'booking_status'   => $status, // For private events, this shows the underlying status.
					'badges'           => $badges, // Badge information for frontend display.
					'price'            => $private_event_data['price'] ?? 0, // Special pricing if applicable.
					'earlybird_price'  => $early_bird_price, // Early bird pricing for this date.
					'units'            => $unit_details, // Detailed unit breakdown for tooltips.
				);

				$current_date->modify( '+1 day' );
			}

			wp_send_json_success( $availability_data );

		} catch ( Exception $e ) {
			wp_send_json_error( 'Error processing availability request: ' . $e->getMessage() );
		}
	}

	/**
	 * Get the status for a specific date.
	 *
	 * @param string $date The date to check.
	 * @param int    $unit_id The room ID (0 for general status).
	 */
	private function get_date_status( $date, $unit_id = 0 ) {
		// Get base status from manual overrides and booking data.
		$base_status = $this->get_base_date_status( $date, $unit_id );

		// Use the centralized rules system to determine status for all contexts.
		$rules_system = AIOHM_BOOKING_Calendar_Rules::get_instance();

		// Apply calendar rules filter for consistent display across admin and frontend.
		$display_status = apply_filters( 'aiohm_booking_calendar_cell_status', $base_status, $date, $unit_id );

		return $display_status;
	}

	/**
	 * Get the base date status without rule filters.
	 * This is the original logic extracted for use by the rules system.
	 *
	 * @param string $date   Date to check.
	 * @param int    $unit_id Room ID to check.
	 */
	private function get_base_date_status( $date, $unit_id = 0 ) {
		// Check for manual status overrides first.
		$cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );

		// Check for specific room status.
		if ( $unit_id > 0 ) {
			$cell_key = $unit_id . '_' . $date . '_full';
			if ( isset( $cell_statuses[ $cell_key ] ) ) {
				return $cell_statuses[ $cell_key ]['status'];
			}
		}

		// Check for general date status (unit_id = 0).
		$general_key = '0_' . $date . '_full';
		if ( isset( $cell_statuses[ $general_key ] ) ) {
			return $cell_statuses[ $general_key ]['status'];
		}

		// Check all accommodation rooms for this date to determine overall day status.
		$accommodations = get_posts(
			array(
				'post_type'      => 'aiohm_accommodation',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		);

		// If no accommodations found via get_posts, try to extract from cell statuses.
		if ( empty( $accommodations ) ) {
			$status_accommodation_ids = array();
			foreach ( $cell_statuses as $key => $status_data ) {
				if ( is_array( $status_data ) && isset( $status_data['accommodation_id'] ) ) {
					$status_accommodation_ids[] = $status_data['accommodation_id'];
				} elseif ( strpos( $key, '_' . $date . '_full' ) !== false ) {
					// Extract ID from key like "123_2025-09-05_full".
					$parts = explode( '_', $key );
					if ( count( $parts ) >= 3 && is_numeric( $parts[0] ) ) {
						$status_accommodation_ids[] = (int) $parts[0];
					}
				}
			}
			$status_accommodation_ids = array_unique( $status_accommodation_ids );

			if ( ! empty( $status_accommodation_ids ) ) {
				$accommodations = $status_accommodation_ids;
			} else {
				// Fallback: use settings.
				$settings       = get_option( 'aiohm_booking_settings', array() );
				$total_units    = isset( $settings['available_accommodations'] ) ? (int) $settings['available_accommodations'] : 1;
				$accommodations = range( 1, $total_units );
			}
		}

		if ( ! empty( $accommodations ) ) {
			$booked_count    = 0;
			$pending_count   = 0;
			$blocked_count   = 0;
			$external_count  = 0;
			$total_rooms     = count( $accommodations );
			$available_count = $total_rooms; // Start with all units available.

			// Check status of each accommodation for this date.
			foreach ( $accommodations as $accommodation_id ) {
				$unit_key = $accommodation_id . '_' . $date . '_full';
				if ( isset( $cell_statuses[ $unit_key ] ) ) {
					$unit_status = $cell_statuses[ $unit_key ]['status'];

					switch ( $unit_status ) {
						case 'booked':
							++$booked_count;
							--$available_count; // This unit is not available.
							break;
						case 'pending':
							++$pending_count;
							--$available_count; // This unit is not available.
							break;
						case 'blocked':
								++$blocked_count;
								--$available_count; // This unit is not available.
							break;
						case 'external':
							++$external_count;
							--$available_count; // This unit is not available.
							break;
					}
				}
				// If no status entry exists, the unit remains available (available_count is not decremented).
			}

			// Apply the updated partial booking color rule.
			// If no units are available, show booked color regardless of status mix.
			if ( 0 === $available_count ) {
				return 'booked'; // No units available - show booked color.
			}

			// If some units are available, show as free.
			return 'free';
		}

		// Check for private events.
		$private_events = get_option( 'aiohm_booking_private_events', array() );
		if ( isset( $private_events[ $date ] ) ) {
			$event = $private_events[ $date ];

			if ( $event['is_special_pricing'] ) {
				return 'special';
			}
			if ( $event['is_private_event'] ) {
				return 'private';
			}
		}

		// Production: Default to free/available (no demo data).
		return 'free';
	}

	/**
	 * Get detailed unit breakdown for a specific date.
	 *
	 * @param  string $date Date in Y-m-d format.
	 * @return array Array with unit counts and details.
	 */
	public function get_unit_breakdown( $date ) {
		$cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );

		// Get accommodation settings to use the same limit as admin calendar.
		$settings   = get_option( 'aiohm_booking_settings', array() );
		$unit_count = intval( $settings['available_accommodations'] ?? self::DEFAULT_ROOM_COUNT );

		// Get accommodation posts using the same method as other calendar functions.
		$accommodations = get_posts(
			array(
				'post_type'      => 'aiohm_accommodation',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft' ),
			)
		);

		// Limit accommodations to the available count setting
		if ( count( $accommodations ) > $unit_count ) {
			$accommodations = array_slice( $accommodations, 0, $unit_count );
		}

		// If no accommodations found via get_posts, try to extract from cell statuses.
		if ( empty( $accommodations ) ) {
			$cell_statuses            = get_option( 'aiohm_booking_cell_statuses', array() );
			$status_accommodation_ids = array();
			foreach ( $cell_statuses as $key => $status_data ) {
				if ( is_array( $status_data ) && isset( $status_data['accommodation_id'] ) ) {
					$status_accommodation_ids[] = $status_data['accommodation_id'];
				} elseif ( strpos( $key, '_full' ) !== false ) {
					// Extract ID from key like "123_2025-09-05_full".
					$parts = explode( '_', $key );
					if ( count( $parts ) >= 3 && is_numeric( $parts[0] ) ) {
						$status_accommodation_ids[] = (int) $parts[0];
					}
				}
			}
			$status_accommodation_ids = array_unique( $status_accommodation_ids );

			if ( ! empty( $status_accommodation_ids ) ) {
				// Convert IDs to post objects for compatibility.
				$accommodations = array_map(
					function ( $id ) {
						$post = get_post( $id );
						return $post ? $post : (object) array(
							'ID'         => $id,
							'post_title' => 'Accommodation ' . $id,
						);
					},
					$status_accommodation_ids
				);

				// Limit accommodations to the available count setting
				if ( count( $accommodations ) > $unit_count ) {
					$accommodations = array_slice( $accommodations, 0, $unit_count );
				}
			} else {
				// Fallback: use settings.
				$settings       = get_option( 'aiohm_booking_settings', array() );
				$total_units    = isset( $settings['available_accommodations'] ) ? (int) $settings['available_accommodations'] : 1;
				$accommodations = array_map(
					function ( $id ) {
						return (object) array(
							'ID'         => $id,
							'post_title' => 'Accommodation ' . $id,
						);
					},
					range( 1, $total_units )
				);
			}
		}

		$total_units = count( $accommodations );

		$breakdown = array(
			'total'     => $total_units,
			'available' => 0, // Use 'available' for JavaScript compatibility.
			'free'      => 0,      // Keep 'free' for backward compatibility.
			'booked'    => 0,
			'pending'   => 0,
			'blocked'   => 0,
			'external'  => 0,
			'details'   => array(), // Individual unit details for tooltip.
		);

		if ( empty( $accommodations ) ) {
			return $breakdown;
		}

		// Check status of each accommodation for this date.
		foreach ( $accommodations as $accommodation_post ) {
			$accommodation_id = $accommodation_post->ID;
			$unit_key         = $accommodation_id . '_' . $date . '_full';

			if ( isset( $cell_statuses[ $unit_key ] ) ) {
				$unit_status = $cell_statuses[ $unit_key ]['status'];

				// Add units to the appropriate status counter.
				$breakdown[ $unit_status ] += 1;

				// Add detail entry for this accommodation.
				$breakdown['details'][] = array(
					'id'     => $accommodation_id,
					'name'   => get_the_title( $accommodation_id ),
					'status' => $unit_status,
				);
			} else {
				// Default to available/free if no specific status.
				$breakdown['available'] += 1;
				$breakdown['free']      += 1;

				// Add detail entry for this accommodation.
				$breakdown['details'][] = array(
					'id'     => $accommodation_id,
					'name'   => get_the_title( $accommodation_id ),
					'status' => 'free',
				);
			}
		}

		// Calculate available units (total - all unavailable statuses).
		// Include 'free' status as available since those units can be booked.
		$unavailable            = $breakdown['booked'] + $breakdown['pending'] + $breakdown['blocked'] + $breakdown['external'];
		$breakdown['available'] = $breakdown['total'] - $unavailable;

		return $breakdown;
	}

	/**
	 * Check if a date has a private event
	 *
	 * @param string $date Date to check.
	 */
	private function is_private_event( $date ) {
		$private_events = get_option( 'aiohm_booking_private_events', array() );
		return isset( $private_events[ $date ] ) && $private_events[ $date ]['is_private_event'];
	}

	/**
	 * Get private event name for a date
	 *
	 * @param string $date Date to check.
	 */
	private function get_private_event_name( $date ) {
		$private_events = get_option( 'aiohm_booking_private_events', array() );
		return isset( $private_events[ $date ] ) ? $private_events[ $date ]['name'] : '';
	}

	/**
	 * Maybe render AI insights section.
	 */
	private function maybe_render_ai_insights() {
		// Check if user has access to premium AI features
		if ( ! function_exists( 'aiohm_booking_fs' ) || ! aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
			return;
		}
		try {
			$settings = get_option( 'aiohm_booking_settings', array() );
		} catch ( Exception $e ) {
			return;
		}

		// Get AI Analytics module settings.
		$ai_analytics_settings = get_option( 'aiohm_booking_ai_analytics_settings', array() );

		// Check if AI Analytics module is enabled and there's a default AI provider set.
		$ai_analytics_enabled       = ! empty( $settings['enable_ai_analytics'] );
		$default_ai_provider        = $settings['shortcode_ai_provider'] ?? '';
		$calendar_analytics_enabled = ! empty( $ai_analytics_settings['enable_calendar_analytics'] );

		// Show AI section only if ALL conditions are met:.
		// 1. AI Analytics module is enabled
		// 2. AI Analytics for Calendar page is enabled
		// 3. Default provider is set
		// 4. That specific provider is enabled.
		$show_ai_section = $ai_analytics_enabled &&
							$calendar_analytics_enabled &&
							! empty( $default_ai_provider ) &&
							! empty( $settings[ 'enable_' . $default_ai_provider ] );

		if ( ! $show_ai_section ) {
			return;
		}
	}

	/**
	 * Get period data for template usage
	 * Provides calculated period array and navigation data for templates
	 *
	 * @param  string $period_type  The period type (week, month).
	 * @param  int    $week_offset  Week offset for navigation.
	 * @param  int    $month_offset Month offset for navigation.
	 * @return array Period data including dates array and navigation info.
	 */
	public function get_period_data_for_template( $period_type = 'week', $week_offset = 0, $month_offset = 0 ) {
		// Limit offsets to prevent memory exhaustion.
		$week_offset  = max( -52, min( 52, $week_offset ) ); // Max 1 year back/forward.
		$month_offset = max( -12, min( 12, $month_offset ) ); // Max 1 year back/forward.

		$period_array    = array();
		$navigation_info = array(
			'current_period' => '',
			'prev_offset'    => 0,
			'next_offset'    => 0,
			'period_label'   => '',
		);

		if ( 'week' === $period_type ) {
			$start_date = new DateTime( 'monday this week' );
			$start_date->modify( ( $week_offset * 7 ) . ' days' );

			for ( $i = 0; $i < 7; $i++ ) {
				$date = clone $start_date;
				$date->modify( "+$i days" );
				$period_array[] = $date;
			}

			$navigation_info['current_period'] = $start_date->format( 'M j, Y' ) . ' - ' . $start_date->modify( '+6 days' )->format( 'M j, Y' );
			$navigation_info['prev_offset']    = $week_offset - 1;
			$navigation_info['next_offset']    = $week_offset + 1;
			$navigation_info['period_label']   = __( 'Week of', 'aiohm-booking-pro' ) . ' ' . $start_date->modify( '-6 days' )->format( 'M j, Y' );

		} elseif ( 'month' === $period_type ) {
			$start_date = new DateTime( 'first day of this month' );
			$start_date->modify( $month_offset . ' months' );

			$days_in_month = $start_date->format( 't' );
			for ( $i = 0; $i < $days_in_month; $i++ ) {
				$date = clone $start_date;
				$date->modify( "+$i days" );
				$period_array[] = $date;
			}

			$navigation_info['current_period'] = $start_date->format( 'F Y' );
			$navigation_info['prev_offset']    = $month_offset - 1;
			$navigation_info['next_offset']    = $month_offset + 1;
			$navigation_info['period_label']   = $start_date->format( 'F Y' );

		} else {
			// Fallback to current week if invalid period.
			$start_date = new DateTime( 'monday this week' );
			for ( $i = 0; $i < 7; $i++ ) {
				$date = clone $start_date;
				$date->modify( "+$i days" );
				$period_array[] = $date;
			}

			$navigation_info['current_period'] = $start_date->format( 'M j, Y' ) . ' - ' . $start_date->modify( '+6 days' )->format( 'M j, Y' );
			$navigation_info['prev_offset']    = $week_offset - 1;
			$navigation_info['next_offset']    = $week_offset + 1;
			$navigation_info['period_label']   = __( 'Week of', 'aiohm-booking-pro' ) . ' ' . $start_date->modify( '-6 days' )->format( 'M j, Y' );
		}

		return array(
			'period_array' => $period_array,
			'navigation'   => $navigation_info,
			'period_type'  => $period_type,
		);
	}
}

// Register the Calendar module.
if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
	AIOHM_BOOKING_Module_Registry::register_module( 'calendar', 'AIOHM_BOOKING_Module_Calendar' );
}
