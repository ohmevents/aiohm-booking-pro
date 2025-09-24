<?php

namespace AIOHM_Booking_PRO\Modules\Admin;
/**
 * Settings Module class
 *
 * Module - Main plugin settings management
 *
 * @package AIOHM_Booking
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 * @license GPL-2.0+ https://www.gnu.org/licenses/gpl       // Only process on settings or accommodation pages.
		if ( ! isset( $_GET['page'] ) || ( 'aiohm-booking-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) && 'aiohm-booking-accommodations' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) ) {
			return;
		}.html
 * @since 1.2.6
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Legacy method names
// phpcs:disable WordPress.NamingConventions.ValidFunctionName -- Legacy method names
// phpcs:disable WordPress.NamingConventions -- Legacy method names

/**
 * Settings Module class
 *
 * @package  AIOHM_Booking
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 * @license  GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.2.6
 */
class AIOHM_BOOKING_Module_Settings extends \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Module identifier.
	 *
	 * @var string
	 */
	protected $module_id = 'settings';

	/**
	 * Admin page slug for the settings module.
	 *
	 * @var string
	 */
	protected $admin_page_slug = 'aiohm-booking-settings';

	/**
	 * Get UI definition for the settings module
	 *
	 * @return array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'settings',
			'name'                => __( 'Settings', 'aiohm-booking-pro' ),
			'description'         => __( 'Main plugin settings and module configuration.', 'aiohm-booking-pro' ),
			'icon'                => '⚙️',
			'admin_page_slug'     => 'aiohm-booking-settings',
			'category'            => 'admin',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 1,
			'has_settings'        => true,
			'has_admin_page'      => true,
			'visible_in_settings' => false, // This is the main settings page, so don't show in settings list.
		);
	}

	/**
	 * Initialize hooks for the settings module
	 */
	public function init_hooks() {
		// Settings module disabled from admin menu.
		// add_action('admin_menu', [$this, 'add_admin_menu'], 5);.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 20 );

		// Check if we need a fallback AJAX handler after all modules are loaded.
		add_action( 'wp_loaded', array( $this, 'maybe_add_fallback_handler' ), 20 );

		// Add form processing.
		add_action( 'admin_init', array( $this, 'process_settings_form' ) );

		// Add AJAX handler for saving module order.
		add_action( 'wp_ajax_aiohm_save_module_order', array( $this, 'ajax_save_module_order' ) );

		// Add AJAX handler for saving masonry card order.
		add_action( 'wp_ajax_aiohm_save_masonry_order', array( $this, 'ajax_save_masonry_order' ) );

		// Add AJAX handler for resetting plugin data.
		add_action( 'wp_ajax_aiohm_booking_reset_plugin_data', array( $this, 'ajax_reset_plugin_data' ) );
	}

	/**
	 * Add fallback AJAX handler only if the main one isn't registered
	 *
	 * @return void
	 */
	public function maybe_add_fallback_handler() {
		// Check if accommodations module handler is already registered.
		if ( ! has_action( 'wp_ajax_aiohm_booking_update_preview' ) ) {
			add_action( 'wp_ajax_aiohm_booking_update_preview', array( $this, 'fallback_preview_ajax' ) );
		}
	}

	/**
	 * Add admin menu for the settings page
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'aiohm-booking-pro',
			__( 'Settings', 'aiohm-booking-pro' ),
			__( 'Settings', 'aiohm-booking-pro' ),
			'manage_options',
			$this->admin_page_slug,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets for the settings page
	 */
	public function enqueue_admin_assets() {
		// Prevent double execution.
		static $already_executed = false;
		if ( $already_executed ) {
			return;
		}
		$already_executed = true;

		// Always load on AIOHM Booking admin pages.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Check if we're on any AIOHM Booking admin page.
		if ( ! $screen ) {
			return;
		}

		// More permissive check - load on any aiohm-booking admin page.
		if ( strpos( $screen->id, 'aiohm-booking-pro' ) === false ) {
			return;
		}

		// Settings styles are now included in the unified CSS system.
		// No need to load separate CSS file.

		// Load settings admin script.
		$script_url = AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-settings-admin.js';

		// Ensure jQuery UI sortable is enqueued first.
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'aiohm-booking-settings-admin',
			$script_url,
			array( 'jquery', 'jquery-ui-sortable' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Force enqueue jQuery UI sortable again to make sure it loads.
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Localize settings script.
		wp_localize_script(
			'aiohm-booking-settings-admin',
			'aiohm_booking_admin',
			array(
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
			)
		);

		// AI provider modules now handle their own scripts and localizations

		// The main 'aiohm-booking-admin.js' is loaded on all admin pages and contains the necessary logic.
		// We only need to ensure its localization data is available.
		wp_localize_script(
			'aiohm-booking-admin',
			'aiohm_booking_admin',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
				'preview_nonce' => wp_create_nonce( 'aiohm_booking_update_preview' ),
				'i18n'          => array(
				// i18n strings are now centralized in the main admin script loader.
				),
			)
		);
	}

	/**
	 * Process settings form submissions
	 *
	 * @return void
	 */
	public function process_settings_form() {
		// Only process on our settings page.
		if ( ! isset( $_GET['page'] ) || 'aiohm-booking-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		// Check if form was submitted.
		if ( ! isset( $_POST['aiohm_booking_settings_nonce'] ) ) {
			return;
		}

		// Check if the save_booking_settings button was clicked
		if ( ! isset( $_POST['save_booking_settings'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aiohm_booking_settings_nonce'] ) ), 'aiohm_booking_save_settings' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>';
				}
			);
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Process the form submission.
		if ( isset( $_POST['aiohm_booking_settings'] ) ) {
			$settings = wp_unslash( $_POST['aiohm_booking_settings'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Settings array sanitized below in save_settings method

			// Filter out non-setting fields - allow all posted settings from the debug log.
			$allowed_settings = array(
				'language',
				'timezone',
				'date_format',
				'currency',
				'deposit_percent',
				'deposit_percentage',
				'min_age',
				'minimum_age',
				'early_bird_days',
				'accommodation_type',
				'available_accommodations',
				'default_price',
				'default_earlybird_price',
				'company_email',
				'module_order',
				'shortcode_ai_provider',
				'enable_order_analytics',
				'enable_calendar_analytics',
				'enable_early_bird',
				'ai_consent',
				'openai_api_key',
				'openai_model',
				'facebook_app_id',
				'facebook_app_secret',
			);

			// Payment module settings - Add Stripe settings (always allow if Stripe module exists)
			$stripe_module_file = AIOHM_BOOKING_DIR . 'includes/modules/payments/stripe/class-aiohm-booking-module-stripe.php';
			if ( file_exists( $stripe_module_file ) ) {
				$allowed_settings = array_merge(
					$allowed_settings,
					array(
						'stripe_publishable_key',
						'stripe_secret_key',
						'stripe_webhook_secret',
					)
				);
			}

			if ( AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Utilities::is_module_available( 'gemini' ) ) {
				$allowed_settings = array_merge(
					$allowed_settings,
					array(
						'gemini_api_key',
						'gemini_model',
					)
				);
			}

			if ( AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Utilities::is_module_available( 'shareai' ) ) {
				$allowed_settings = array_merge(
					$allowed_settings,
					array(
						'shareai_api_key',
						'shareai_model',
					)
				);
			}

			if ( AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Utilities::is_module_available( 'ollama' ) ) {
				$allowed_settings = array_merge(
					$allowed_settings,
					array(
						'ollama_base_url',
						'ollama_model',
					)
				);
			}

			$settings = array_intersect_key( $settings, array_flip( $allowed_settings ) );

			// Sanitize settings.
			$sanitized_settings = array();
			foreach ( $settings as $key => $value ) {
				if ( is_array( $value ) ) {
					$sanitized_settings[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$sanitized_settings[ $key ] = sanitize_text_field( $value );
				}
			}

			// Get current settings and merge.
			$current_settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
			$merged_settings  = array_merge( $current_settings, $sanitized_settings );

			// Save settings - try direct database approach as fallback.
			$result = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::update( $merged_settings );

			// If the class method fails, try direct WordPress function as fallback.
			if ( ! $result ) {
				$result = update_option( 'aiohm_booking_settings', $merged_settings );
			}

			if ( $result ) {
				// If shortcode_ai_provider was updated, sync it with AI Analytics default_ai_provider.
				if ( isset( $sanitized_settings['shortcode_ai_provider'] ) ) {
					$ai_analytics_settings                        = get_option( 'aiohm_booking_ai_analytics_settings', array() );
					$ai_analytics_settings['default_ai_provider'] = $sanitized_settings['shortcode_ai_provider'];
					update_option( 'aiohm_booking_ai_analytics_settings', $ai_analytics_settings );
				}

				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
					}
				);
			} else {
				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-error is-dismissible"><p>Error saving settings. Please try again.</p></div>';
					}
				);
			}
		}
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page() {
		include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-settings.php';
	}

	/**
	 * Get the module name
	 *
	 * @return string
	 */
	public function get_module_name() {
		return __( 'Settings', 'aiohm-booking-pro' );
	}

	/**
	 * Fallback AJAX handler for preview when accommodations module is not available
	 *
	 * @return void
	 */
	public function fallback_preview_ajax() {

		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_update_preview' ) ) {
			wp_send_json_error( 'Security check failed' );
			return;
		}

		// Check if accommodations module is enabled.
		$is_enabled = aiohm_booking_is_module_enabled( 'accommodations' );

		if ( ! $is_enabled ) {
			wp_send_json_error( 'Accommodations module is not enabled. Preview is not available.' );
			return;
		}

		// If we get here, the module should be enabled but something else went wrong.
		wp_send_json_error( 'Preview functionality is not available. Please ensure all required modules are properly loaded.' );
	}

	/**
	 * AJAX handler for saving module order
	 *
	 * @return void
	 */
	public function ajax_save_module_order() {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		// Get the order from POST data.
		$order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below

		if ( ! is_array( $order ) ) {
			wp_send_json_error( 'Invalid order data' );
			return;
		}

		// Sanitize the order array.
		$sanitized_order = array_map( 'sanitize_text_field', $order );

		// Get current settings.
		$settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();

		// Update module order.
		$settings['module_order'] = $sanitized_order;

		// Save settings.
		$result = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::update( $settings );

		if ( $result ) {
			wp_send_json_success( 'Module order saved successfully' );
		} else {
			wp_send_json_error( 'Failed to save module order' );
		}
	}

	/**
	 * AJAX handler for saving masonry card order
	 *
	 * @return void
	 */
	public function ajax_save_masonry_order() {
		// Verify nonce.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		// Get the order from POST data.
		$order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below

		if ( ! is_array( $order ) ) {
			wp_send_json_error( 'Invalid order data' );
			return;
		}

		// Sanitize the order array.
		$sanitized_order = array_map( 'sanitize_text_field', $order );

		// Get current settings.
		$settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();

		// Update masonry card order.
		$settings['masonry_card_order'] = $sanitized_order;

		// Save settings.
		$result = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::update( $settings );

		if ( $result ) {
			wp_send_json_success( 'Masonry card order saved successfully' );
		} else {
			wp_send_json_error( 'Failed to save masonry card order' );
		}
	}

	/**
	 * Get the module description
	 *
	 * @return string
	 */
	public function get_module_description() {
		return __( 'Main plugin settings and module configuration.', 'aiohm-booking-pro' );
	}

	/**
	 * Get the module ID
	 *
	 * @return string
	 */
	public function get_module_id() {
		return $this->module_id;
	}

	/**
	 * Check if the module is premium
	 *
	 * @return bool
	 */
	public function is_premium() {
		return false;
	}

	/**
	 * Get module dependencies
	 *
	 * @return array
	 */
	public function get_dependencies() {
		return array();
	}

	/**
	 * Check module dependencies
	 *
	 * @return bool
	 */
	public function check_dependencies() {
		return true;
	}

	/**
	 * Check if the module is available
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Get settings fields for the module
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		// Global settings moved to Booking Settings module.
		return array();
	}

	/**
	 * Get default settings for the module
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return array(
			'currency'           => 'USD',
			'plugin_language'    => 'en',
			'deposit_percentage' => 0,
			'min_age'            => 0,
		);
	}

	/**
	 * Check if the module is enabled
	 *
	 * @return bool
	 */
	protected function check_if_enabled() {
		// Settings module is always enabled.
		return true;
	}

	/**
	 * AJAX handler for resetting all plugin data
	 * 
	 * @since 2.0.3
	 */
	public function ajax_reset_plugin_data() {
		// Verify nonce and capabilities
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_save_settings' ) ) {
			wp_send_json_error( array( 'message' => 'Security verification failed.' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
			return;
		}

		global $wpdb;

		try {
			// Delete all plugin options
			$plugin_options = array(
				'aiohm_booking_settings',
				'aiohm_booking_events_migrated',
				'aiohm_booking_tickets_settings',
				'aiohm_booking_accommodation_settings',
				'aiohm_booking_calendar_settings',
				'aiohm_booking_tickets_form_settings',
				'aiohm_booking_accommodation_form_settings',
				'aiohm_booking_ticket_settings',
				'aiohm_booking_ticket_types',
				'aiohm_booking_private_events',
				'aiohm_booking_cell_statuses',
				'aiohm_booking_ai_analytics_settings',
				'aiohm_booking_openai_settings',
				'aiohm_booking_gemini_settings',
				'aiohm_booking_ollama_settings',
				'aiohm_booking_shareai_settings',
				'aiohm_booking_stripe_settings',
			);

			foreach ( $plugin_options as $option ) {
				delete_option( $option );
			}

			// Delete all Custom Post Types
			$cpt_types = array( 'aiohm_accommodation', 'aiohm_booking_event' );
			foreach ( $cpt_types as $post_type ) {
				$posts = get_posts( array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => 'any',
				) );
				foreach ( $posts as $post ) {
					wp_delete_post( $post->ID, true );
				}
			}

			// Delete custom tables
			$tables_to_delete = array(
				$wpdb->prefix . 'aiohm_booking_order',
				$wpdb->prefix . 'aiohm_booking_calendar_data',
				$wpdb->prefix . 'aiohm_booking_email_logs',
			);

			foreach ( $tables_to_delete as $table ) {
				$wpdb->query( "DROP TABLE IF EXISTS " . esc_sql( $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange
			}

			// Clear any transients
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aiohm_%' OR option_name LIKE '_transient_timeout_aiohm_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			wp_send_json_success( array( 
				'message' => 'All plugin data has been successfully reset.',
				'redirect' => admin_url( 'admin.php?page=aiohm-booking-settings' )
			) );

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error during reset: ' . $e->getMessage() ) );
		}
	}

}

// Register the Settings module.
if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry' ) ) {
	AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::register_module( 'settings', 'AIOHM_BOOKING_Module_Settings' );
}
