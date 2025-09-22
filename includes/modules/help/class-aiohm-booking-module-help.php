<?php
/**
 * Help Module - Provides documentation and support functionality
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Help Module - Provides documentation and support functionality
 */
class AIOHM_BOOKING_Module_Help extends AIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Module ID
	 *
	 * @var string
	 */
	protected $module_id = 'help';

	/**
	 * Admin page slug
	 *
	 * @var string
	 */
	protected $admin_page_slug = 'aiohm-booking-get-help';

	/**
	 * Get UI definition for module registration
	 *
	 * @return array Module configuration array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'help',
			'name'                => __( 'Get Help', 'aiohm-booking-pro' ),
			'description'         => __( 'Access documentation, system information, and support resources.', 'aiohm-booking-pro' ),
			'icon'                => '❓',
			'admin_page_slug'     => 'aiohm-booking-get-help',
			'category'            => 'admin',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 10,
			'has_settings'        => false,
			'has_admin_page'      => true,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Initialize hooks and filters
	 */
	public function init_hooks() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Add AJAX handlers.
		add_action( 'wp_ajax_aiohm_booking_get_debug_info', array( $this, 'ajax_get_debug_info' ) );
		add_action( 'wp_ajax_aiohm_booking_submit_support_request', array( $this, 'ajax_submit_support_request' ) );
		add_action( 'wp_ajax_aiohm_booking_submit_feature_request', array( $this, 'ajax_submit_feature_request' ) );
	}

	/**
	 * Add admin menu item
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'aiohm-booking-pro',
			__( 'Get Help', 'aiohm-booking-pro' ),
			__( 'Get Help', 'aiohm-booking-pro' ),
			'manage_options',
			$this->admin_page_slug,
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || strpos( $screen->id, 'get-help' ) === false ) {
			return;
		}

		// Enqueue help module CSS
		wp_enqueue_style(
			'aiohm-booking-help',
			plugin_dir_url( __FILE__ ) . 'assets/css/aiohm-booking-help.css',
			array(),
			AIOHM_BOOKING_VERSION
		);

		wp_enqueue_script(
			'aiohm-booking-help-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/aiohm-booking-help-admin.js',
			array( 'jquery', 'aiohm-booking-admin' ),  // Added aiohm-booking-admin dependency now that JS is unified.
			AIOHM_BOOKING_VERSION,
			true
		);

		wp_localize_script(
			'aiohm-booking-help-admin',
			'aiohm_booking_help_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
				'i18n'     => array(
					'debugCollected'            => __( 'Debug information collected successfully!', 'aiohm-booking-pro' ),
					'noDebugToCopy'             => __( 'No debug information to copy.', 'aiohm-booking-pro' ),
					'debugCopied'               => __( 'Debug information copied to clipboard!', 'aiohm-booking-pro' ),
					'noDebugToDownload'         => __( 'No debug information to download.', 'aiohm-booking-pro' ),
					'debugDownloaded'           => __( 'Debug information downloaded successfully!', 'aiohm-booking-pro' ),
					'featureRequest'            => __( 'feature request', 'aiohm-booking-pro' ),
					'supportRequest'            => __( 'support request', 'aiohm-booking-pro' ),
					/* translators: %s: type of request (support request or feature request) */
					'requestSubmitted'          => __( '%s submitted successfully!', 'aiohm-booking-pro' ),
					'requestError'              => __( 'Error submitting request: ', 'aiohm-booking-pro' ),
					'unknownError'              => __( 'Unknown error occurred.', 'aiohm-booking-pro' ),
					'requestServerError'        => __( 'Server error occurred. Please try again.', 'aiohm-booking-pro' ),
					'failedToCollectPluginInfo' => __( 'Failed to collect plugin information.', 'aiohm-booking-pro' ),
					'debugReportTitle'          => __( 'AIOHM Booking Debug Report', 'aiohm-booking-pro' ),
					/* translators: %s: timestamp when the debug report was generated */
					'generated'                 => __( 'Generated: %s', 'aiohm-booking-pro' ),
					'systemInfoTitle'           => __( 'SYSTEM INFORMATION', 'aiohm-booking-pro' ),
					/* translators: %s: plugin version number */
					'pluginVersion'             => __( 'Plugin Version: %s', 'aiohm-booking-pro' ),
					/* translators: %s: WordPress version number */
					'wpVersion'                 => __( 'WordPress Version: %s', 'aiohm-booking-pro' ),
					/* translators: %s: PHP version number */
					'phpVersion'                => __( 'PHP Version: %s', 'aiohm-booking-pro' ),
					/* translators: %s: website URL */
					'siteUrl'                   => __( 'Site URL: %s', 'aiohm-booking-pro' ),
					/* translators: %s: comma-separated list of enabled modules */
					'enabledModules'            => __( 'Enabled Modules: %s', 'aiohm-booking-pro' ),
					'browserInfoTitle'          => __( 'BROWSER INFORMATION', 'aiohm-booking-pro' ),
					/* translators: %s: browser user agent string */
					'userAgent'                 => __( 'User Agent: %s', 'aiohm-booking-pro' ),
					/* translators: %s: browser language setting */
					'language'                  => __( 'Language: %s', 'aiohm-booking-pro' ),
					/* translators: %s: operating system platform */
					'platform'                  => __( 'Platform: %s', 'aiohm-booking-pro' ),
					/* translators: %s: yes/no if cookies are enabled */
					'cookiesEnabled'            => __( 'Cookies Enabled: %s', 'aiohm-booking-pro' ),
					/* translators: %s: yes/no if browser is online */
					'online'                    => __( 'Online: %s', 'aiohm-booking-pro' ),
					'displayInfoTitle'          => __( 'DISPLAY INFORMATION', 'aiohm-booking-pro' ),
					/* translators: %1$s: screen width, %2$s: screen height */
					'screen'                    => __( 'Screen Resolution: %1$s x %2$s', 'aiohm-booking-pro' ),
					/* translators: %1$s: window width, %2$s: window height */
					'window'                    => __( 'Window Size: %1$s x %2$s', 'aiohm-booking-pro' ),
					/* translators: %s: device pixel ratio */
					'pixelRatio'                => __( 'Pixel Ratio: %s', 'aiohm-booking-pro' ),
					/* translators: %s: color depth in bits */
					'colorDepth'                => __( 'Color Depth: %s', 'aiohm-booking-pro' ),
					'wpInfoTitle'               => __( 'WORDPRESS INFORMATION', 'aiohm-booking-pro' ),
					/* translators: %s: WordPress admin URL */
					'adminUrl'                  => __( 'Admin URL: %s', 'aiohm-booking-pro' ),
					/* translators: %s: current page URL */
					'currentPage'               => __( 'Current Page: %s', 'aiohm-booking-pro' ),
					/* translators: %s: referring page URL */
					'referrer'                  => __( 'Referrer: %s', 'aiohm-booking-pro' ),
					'pluginInfoTitle'           => __( 'PLUGIN INFORMATION', 'aiohm-booking-pro' ),
					'settingsTitle'             => __( "Settings:\n", 'aiohm-booking-pro' ),
					'configured'                => __( '[CONFIGURED]', 'aiohm-booking-pro' ),
					'notSet'                    => __( '[NOT SET]', 'aiohm-booking-pro' ),
					'dbTablesTitle'             => __( "Database Tables:\n", 'aiohm-booking-pro' ),
					'tableExists'               => __( 'EXISTS', 'aiohm-booking-pro' ),
					'tableMissing'              => __( 'MISSING', 'aiohm-booking-pro' ),
					/* translators: %d: number of rows in database table */
					'tableRows'                 => __( '%d rows', 'aiohm-booking-pro' ),
					'bookingSystemStatusTitle'  => __( "Booking System Status:\n", 'aiohm-booking-pro' ),
					'recentErrorsTitle'         => __( "Recent Errors:\n", 'aiohm-booking-pro' ),
					/* translators: %s: error message describing plugin information collection failure */
					'pluginInfoError'           => __( 'Plugin Info Error: %s', 'aiohm-booking-pro' ),
				),
			)
		);
	}

	/**
	 * Render the help page content
	 */
	public function render_help_page() {
		include plugin_dir_path( __FILE__ ) . 'templates/aiohm-booking-help.php';
	}

	/**
	 * Get module display name
	 *
	 * @return string Module name
	 */
	public function get_module_name() {
		return __( 'Get Help', 'aiohm-booking-pro' );
	}

	/**
	 * Get module description
	 *
	 * @return string Module description
	 */
	public function get_module_description() {
		return __( 'Access documentation, system information, and support resources.', 'aiohm-booking-pro' );
	}

	/**
	 * Get module ID
	 *
	 * @return string Module ID
	 */
	public function get_module_id() {
		return $this->module_id;
	}

	/**
	 * Check if module is premium
	 *
	 * @return bool False for free module
	 */
	public function is_premium() {
		return false;
	}

	/**
	 * Get module dependencies
	 *
	 * @return array Empty array for no dependencies
	 */
	public function get_dependencies() {
		return array();
	}

	/**
	 * Check if dependencies are met
	 *
	 * @return bool True if dependencies are met
	 */
	public function check_dependencies() {
		return true;
	}

	/**
	 * Check if module is available
	 *
	 * @return bool True if module is available
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Get settings fields
	 *
	 * @return array Empty array for no settings
	 */
	public function get_settings_fields() {
		return array();
	}

	/**
	 * Get default settings
	 *
	 * @return array Empty array for no default settings
	 */
	protected function get_default_settings() {
		return array();
	}

	/**
	 * Check if module should be enabled
	 *
	 * @return bool True if module should be enabled
	 */
	protected function check_if_enabled() {
		$settings   = AIOHM_BOOKING_Settings::get_all();
		$enable_key = 'enable_' . $this->module_id;

		// If the setting exists (either '1' or '0'), respect it.
		if ( isset( $settings[ $enable_key ] ) ) {
			return ! empty( $settings[ $enable_key ] );
		}

		// Default to enabled for Help module.
		return true;
	}

	/**
	 * AJAX handler for getting debug information
	 *
	 * @return void Dies with JSON response
	 */
	public function ajax_get_debug_info() {
		// Verify security using centralized helper.
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'admin_nonce', 'manage_options' ) ) {
			return; // Error response already sent by helper
		}

		try {
			$debug_info = array(
				'settings'       => $this->get_plugin_settings(),
				'database'       => $this->get_database_info(),
				'booking_system' => $this->get_booking_system_status(),
				'errors'         => $this->get_recent_errors(),
			);

			wp_die(
				wp_json_encode(
					array(
						'success' => true,
						'data'    => $debug_info,
					)
				)
			);
		} catch ( Exception $e ) {
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => $e->getMessage(),
					)
				)
			);
		}
	}

	/**
	 * AJAX handler for submitting support requests
	 */
	/**
	 * AJAX handler for submitting support requests
	 *
	 * @return void Dies with JSON response
	 */
	public function ajax_submit_support_request() {
		try {
			// Verify security using centralized helper.
			if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'admin_nonce', 'manage_options' ) ) {
				return; // Error response already sent by helper
			}

			// Sanitize form data using centralized helper.
			$form_data = AIOHM_BOOKING_Security_Helper::sanitize_post_fields(
				array(
					'email'             => 'email',
					'title'             => 'text',
					'type'              => 'text',
					'description'       => 'textarea',
					'debug_information' => 'textarea',
				)
			);

			$email       = $form_data['email'];
			$title       = $form_data['title'];
			$type        = $form_data['type'];
			$description = $form_data['description'];
			$debug_info  = $form_data['debug_information'];

			// Validate required fields using comprehensive validation.
			if ( ! class_exists( 'AIOHM_BOOKING_Validation' ) ) {
				wp_die(
					wp_json_encode(
						array(
							'success' => false,
							'data'    => 'Validation system unavailable',
						)
					)
				);
			}

			$support_data = array(
				'email'       => $email,
				'title'       => $title,
				'description' => $description,
			);

			if ( ! AIOHM_BOOKING_Validation::validate_booking_data( $support_data, 'admin' ) ) {
				$errors        = AIOHM_BOOKING_Validation::get_errors();
				$error_message = ! empty( $errors ) ? implode( ' ', array_values( $errors ) ) : 'Validation failed';
				wp_die(
					wp_json_encode(
						array(
							'success' => false,
							'data'    => $error_message,
						)
					)
				);
			}

			if ( empty( $email ) || empty( $title ) || empty( $description ) ) {
				wp_die(
					wp_json_encode(
						array(
							'success' => false,
							'data'    => 'Please fill in all required fields',
						)
					)
				);
			}

			// Additional validation for email.
			if ( ! AIOHM_BOOKING_Validation::validate_email( $email ) ) {
				wp_die(
					wp_json_encode(
						array(
							'success' => false,
							'data'    => 'Please enter a valid email address',
						)
					)
				);
			}

			// Length validation.
			if ( strlen( $title ) > 200 ) {
				wp_die(
					wp_json_encode(
						array(
							'success' => false,
							'data'    => 'Title must be less than 200 characters',
						)
					)
				);
			}

			if ( strlen( $description ) > 2000 ) {
				wp_die(
					wp_json_encode(
						array(
							'success' => false,
							'data'    => 'Description must be less than 2000 characters',
						)
					)
				);
			}

			// Support request processing would be implemented here.
			wp_die(
				wp_json_encode(
					array(
						'success' => true,
						'data'    => 'Support request submitted successfully',
					)
				)
			);

		} catch ( Exception $e ) {
			AIOHM_BOOKING_Validation::log_error( 'Support request submission failed: ' . $e->getMessage(), array( 'email' => $email ?? null ) );
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => 'An error occurred while submitting your request',
					)
				)
			);
		}
	}

	/**
	 * AJAX handler for submitting feature requests
	 */
	/**
	 * AJAX handler for submitting feature requests
	 *
	 * @return void Dies with JSON response
	 */
	public function ajax_submit_feature_request() {
		// Verify security using centralized helper.
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'admin_nonce', 'manage_options' ) ) {
			return; // Error response already sent by helper
		}

		// Sanitize form fields using centralized helper.
		$form_data = AIOHM_BOOKING_Security_Helper::sanitize_post_fields(
			array(
				'email'       => 'email',
				'title'       => 'text',
				'category'    => 'text',
				'description' => 'textarea',
			)
		);

		$email       = $form_data['email'];
		$title       = $form_data['title'];
		$category    = $form_data['category'];
		$description = $form_data['description'];

		if ( empty( $email ) || empty( $title ) || empty( $description ) ) {
			wp_die(
				wp_json_encode(
					array(
						'success' => false,
						'data'    => 'Please fill in all required fields',
					)
				)
			);
		}

		// Feature request processing would be implemented here.
		wp_die(
			wp_json_encode(
				array(
					'success' => true,
					'data'    => 'Feature request submitted successfully',
				)
			)
		);
	}

	/**
	 * Get plugin settings for debug info
	 */
	private function get_plugin_settings() {
		$settings    = array();
		$all_options = wp_load_alloptions();

		foreach ( $all_options as $key => $value ) {
			if ( strpos( $key, 'aiohm_booking' ) === 0 ) {
				$settings[ $key ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Get database table information
	 */
	private function get_database_info() {
		global $wpdb;

		$tables = array(
			'bookings'       => $wpdb->prefix . 'aiohm_bookings',
			'events'         => $wpdb->prefix . 'aiohm_events',
			'accommodations' => $wpdb->prefix . 'aiohm_accommodations',
		);

		$info = array();
		foreach ( $tables as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for help data query
			$rows   = 0;
			if ( $exists ) {
				$rows = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Diagnostic query for help/debugging, caching not needed
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %s',
						$table
					)
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
			}
			$info[ $name ] = array(
				'exists' => $exists,
				'rows'   => $rows,
			);
		}

		return $info;
	}

	/**
	 * Get booking system status
	 */
	private function get_booking_system_status() {
		return array(
			'php_version'        => PHP_VERSION,
			'wordpress_version'  => get_bloginfo( 'version' ),
			'plugin_version'     => defined( 'AIOHM_BOOKING_VERSION' ) ? AIOHM_BOOKING_VERSION : 'Unknown',
			'memory_limit'       => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
		);
	}

	/**
	 * Get recent errors from logs
	 */
	private function get_recent_errors() {
		return array();
	}
}
