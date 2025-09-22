<?php
/**
 * Facebook Integration Module
 *
 * Integrates with Facebook Graph API to import event data into AIOHM Booking system.
 * Supports event import from Facebook Events with comprehensive data mapping.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Facebook Integration Module Class
 *
 * Handles Facebook Graph API integration for importing event data including
 * event details, location, date/time, description, and cover images.
 *
 * @since 1.2.3
 */
class AIOHM_BOOKING_Module_Facebook extends AIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Facebook Graph API base URL.
	 *
	 * @since 1.2.3
	 * @var string
	 */
	private $graph_api_url = 'https://graph.facebook.com/v18.0/';

	/**
	 * Get module UI definition.
	 *
	 * Returns the configuration array that defines this module's appearance
	 * and behavior in the admin interface.
	 *
	 * @since 1.2.3
	 * @return array Module UI definition array.
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'facebook',
			'name'                => __( 'Facebook Integration', 'aiohm-booking-pro' ),
			'description'         => __( 'Import events from Facebook Events directly into your booking system.', 'aiohm-booking-pro' ),
			'icon'                => '📘',
			'category'            => 'integration',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 15,
			'has_settings'        => true,
			'has_admin_page'      => false,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * Sets up action hooks for AJAX endpoints and form handling.
	 *
	 * @since 1.2.3
	 */
	protected function init_hooks() {
		// AJAX handlers for Facebook import.
		add_action( 'wp_ajax_aiohm_booking_import_facebook_event', array( $this, 'ajax_import_facebook_event' ) );
		add_action( 'wp_ajax_aiohm_booking_get_facebook_event_info', array( $this, 'ajax_get_facebook_event_info' ) );

		// Add Facebook import JavaScript to tickets page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_facebook_scripts' ) );
	}

	/**
	 * Get settings fields configuration.
	 *
	 * Returns an array of settings fields that define the Facebook module's
	 * configuration options including API access tokens and permissions.
	 *
	 * @since 1.2.3
	 * @return array Settings fields configuration array.
	 */
	public function get_settings_fields() {
		return array(
			'facebook_app_id'           => array(
				'type'        => 'text',
				'label'       => 'Facebook App ID',
				'description' => 'Your Facebook App ID for API access',
				'default'     => '',
			),
			'facebook_app_secret'       => array(
				'type'        => 'text',
				'label'       => 'Facebook App Secret',
				'description' => 'Your Facebook App Secret (keep this secure)',
				'default'     => '',
			),
			'facebook_access_token'     => array(
				'type'        => 'text',
				'label'       => 'Facebook Access Token',
				'description' => 'Page Access Token or User Access Token with events_read permission',
				'default'     => '',
			),
			'import_cover_images'       => array(
				'type'        => 'checkbox',
				'label'       => 'Import Cover Images',
				'description' => 'Download and import Facebook event cover images',
				'default'     => true,
			),
			'default_location_fallback' => array(
				'type'        => 'text',
				'label'       => 'Default Location Fallback',
				'description' => 'Default location to use when Facebook event has no location',
				'default'     => '',
			),
		);
	}

	/**
	 * Get default settings values.
	 *
	 * Returns the default configuration values for the Facebook module
	 * when no custom settings have been saved.
	 *
	 * @since 1.2.3
	 * @return array Default settings array.
	 */
	protected function get_default_settings() {
		return array(
			'facebook_app_id'           => '',
			'facebook_app_secret'       => '',
			'facebook_access_token'     => '',
			'import_cover_images'       => true,
			'default_location_fallback' => '',
		);
	}

	/**
	 * Enqueue Facebook-specific JavaScript for the import functionality.
	 *
	 * Loads JavaScript files needed for Facebook event import on the tickets admin page.
	 *
	 * @since 1.2.3
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_facebook_scripts( $hook ) {
		// Only load on tickets page.
		if ( false === strpos( $hook, 'aiohm-booking-tickets' ) ) {
			return;
		}

		// Enqueue Facebook import JavaScript.
		wp_enqueue_script(
			'aiohm-booking-facebook-import',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-facebook-import.js',
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Localize script for AJAX.
		wp_localize_script(
			'aiohm-booking-facebook-import',
			'aiohm_facebook_import',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_booking_facebook_import' ),
				'i18n'     => array(
					'importing'      => __( 'Importing from Facebook...', 'aiohm-booking-pro' ),
					'import_success' => __( 'Event imported successfully!', 'aiohm-booking-pro' ),
					'import_failed'  => __( 'Import failed. Please check your settings.', 'aiohm-booking-pro' ),
					'invalid_url'    => __( 'Please enter a valid Facebook event URL', 'aiohm-booking-pro' ),
					'enter_url'      => __( 'Enter Facebook Event URL', 'aiohm-booking-pro' ),
					'example_url'    => __( 'e.g., https://www.facebook.com/events/123456789', 'aiohm-booking-pro' ),
					'import_button'  => __( 'Import Event', 'aiohm-booking-pro' ),
					'cancel_button'  => __( 'Cancel', 'aiohm-booking-pro' ),
					'loading'        => __( 'Loading...', 'aiohm-booking-pro' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for importing Facebook event data.
	 *
	 * Processes AJAX requests to import event data from Facebook Graph API
	 * and populate the event form with the imported information.
	 *
	 * @since 1.2.3
	 */
	public function ajax_import_facebook_event() {
		if ( ! check_ajax_referer( 'aiohm_booking_facebook_import', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to import events.' ) );
			return;
		}

		$facebook_url = isset( $_POST['facebook_url'] ) ? esc_url_raw( wp_unslash( $_POST['facebook_url'] ) ) : '';
		$event_index  = isset( $_POST['event_index'] ) ? intval( $_POST['event_index'] ) : -1;

		if ( empty( $facebook_url ) || $event_index < 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
			return;
		}

		// Extract event ID from Facebook URL.
		$event_id = $this->extract_event_id_from_url( $facebook_url );
		if ( empty( $event_id ) ) {
			wp_send_json_error( array( 'message' => 'Could not extract event ID from URL.' ) );
			return;
		}

		// Import event data from Facebook.
		$event_data = $this->import_facebook_event_data( $event_id );
		if ( is_wp_error( $event_data ) ) {
			wp_send_json_error( array( 'message' => $event_data->get_error_message() ) );
			return;
		}

		wp_send_json_success(
			array(
				'event_data' => $event_data,
				'message'    => 'Event imported successfully!',
			)
		);
	}

	/**
	 * AJAX handler for getting Facebook event information preview.
	 *
	 * Returns basic event information for preview before importing.
	 *
	 * @since 1.2.3
	 */
	public function ajax_get_facebook_event_info() {
		if ( ! check_ajax_referer( 'aiohm_booking_facebook_import', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Nonce verification failed.' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'You do not have permission to access event info.' ) );
			return;
		}

		$facebook_url = isset( $_POST['facebook_url'] ) ? esc_url_raw( wp_unslash( $_POST['facebook_url'] ) ) : '';

		if ( empty( $facebook_url ) ) {
			wp_send_json_error( array( 'message' => 'Invalid Facebook URL.' ) );
			return;
		}

		// Extract event ID from Facebook URL.
		$event_id = $this->extract_event_id_from_url( $facebook_url );
		if ( empty( $event_id ) ) {
			wp_send_json_error( array( 'message' => 'Could not extract event ID from URL.' ) );
			return;
		}

		// Get basic event info.
		$event_info = $this->get_facebook_event_info( $event_id );
		if ( is_wp_error( $event_info ) ) {
			wp_send_json_error( array( 'message' => $event_info->get_error_message() ) );
			return;
		}

		wp_send_json_success( array( 'event_info' => $event_info ) );
	}

	/**
	 * Extract event ID from Facebook event URL.
	 *
	 * Supports various Facebook event URL formats including:
	 * - https://www.facebook.com/events/123456789
	 * - https://facebook.com/events/123456789/
	 * - https://m.facebook.com/events/123456789
	 *
	 * @since 1.2.3
	 * @param string $url Facebook event URL.
	 * @return string|false Event ID or false on failure.
	 */
	private function extract_event_id_from_url( $url ) {
		// Match various Facebook event URL formats.
		$patterns = array(
			'/facebook\.com\/events\/(\d+)/',
			'/fb\.me\/e\/(\d+)/',
			'/facebook\.com\/events\/[^\/]+\/(\d+)/',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $url, $matches ) ) {
				return $matches[1];
			}
		}

		return false;
	}

	/**
	 * Get basic Facebook event information for preview.
	 *
	 * Returns limited event data for preview purposes before full import.
	 *
	 * @since 1.2.3
	 * @param string $event_id Facebook event ID.
	 * @return array|WP_Error Event information or error.
	 */
	private function get_facebook_event_info( $event_id ) {
		$settings     = $this->get_module_settings();
		$access_token = $settings['facebook_access_token'];

		if ( empty( $access_token ) ) {
			return new WP_Error( 'no_access_token', 'Facebook access token not configured.' );
		}

		// Request basic event information.
		$fields = 'name,start_time,place,description';
		$url    = $this->graph_api_url . $event_id . '?fields=' . $fields . '&access_token=' . $access_token;

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_request_failed', 'Failed to connect to Facebook API: ' . $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'facebook_api_error', 'Facebook API Error: ' . $data['error']['message'] );
		}

		return array(
			'name'        => $data['name'] ?? '',
			'start_time'  => $data['start_time'] ?? '',
			'location'    => isset( $data['place']['name'] ) ? $data['place']['name'] : '',
			'description' => wp_trim_words( $data['description'] ?? '', 20 ),
		);
	}

	/**
	 * Import complete Facebook event data.
	 *
	 * Fetches comprehensive event data from Facebook Graph API and formats it
	 * for use in the AIOHM Booking system.
	 *
	 * @since 1.2.3
	 * @param string $event_id Facebook event ID.
	 * @return array|WP_Error Complete event data or error.
	 */
	private function import_facebook_event_data( $event_id ) {
		$settings     = $this->get_module_settings();
		$access_token = $settings['facebook_access_token'];

		if ( empty( $access_token ) ) {
			return new WP_Error( 'no_access_token', 'Facebook access token not configured. Please check your Facebook integration settings.' );
		}

		// Request comprehensive event data.
		$fields = 'name,description,start_time,end_time,place,cover,ticket_uri,attending_count,interested_count';
		$url    = $this->graph_api_url . $event_id . '?fields=' . $fields . '&access_token=' . $access_token;

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_request_failed', 'Failed to connect to Facebook API: ' . $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'facebook_api_error', 'Facebook API Error: ' . $data['error']['message'] );
		}

		// Process and format the event data.
		return $this->format_facebook_event_data( $data );
	}

	/**
	 * Format Facebook event data for AIOHM Booking system.
	 *
	 * Converts Facebook event data structure to match the expected format
	 * for AIOHM Booking events.
	 *
	 * @since 1.2.3
	 * @param array $facebook_data Raw Facebook event data.
	 * @return array Formatted event data.
	 */
	private function format_facebook_event_data( $facebook_data ) {
		$settings = $this->get_module_settings();

		// Extract date and time from Facebook's ISO 8601 format.
		$start_datetime = $facebook_data['start_time'] ?? '';
		$event_date     = '';
		$event_time     = '';

		if ( ! empty( $start_datetime ) ) {
			$datetime   = new DateTime( $start_datetime );
			$event_date = $datetime->format( 'Y-m-d' );
			$event_time = $datetime->format( 'H:i' );
		}

		// Extract location information.
		$location = '';
		if ( isset( $facebook_data['place'] ) ) {
			$place    = $facebook_data['place'];
			$location = $place['name'] ?? '';

			// Add location details if available.
			if ( isset( $place['location'] ) ) {
				$location_details = array();
				if ( ! empty( $place['location']['street'] ) ) {
					$location_details[] = $place['location']['street'];
				}
				if ( ! empty( $place['location']['city'] ) ) {
					$location_details[] = $place['location']['city'];
				}

				if ( ! empty( $location_details ) ) {
					$location .= ' - ' . implode( ', ', $location_details );
				}
			}
		}

		// Use fallback location if none provided.
		if ( empty( $location ) && ! empty( $settings['default_location_fallback'] ) ) {
			$location = $settings['default_location_fallback'];
		}

		// Estimate attendance for available seats.
		$available_seats = 50; // Default.
		if ( isset( $facebook_data['attending_count'] ) || isset( $facebook_data['interested_count'] ) ) {
			$attending       = intval( $facebook_data['attending_count'] ?? 0 );
			$interested      = intval( $facebook_data['interested_count'] ?? 0 );
			$estimated       = max( $attending + ( $interested * 0.3 ), 20 ); // 30% of interested usually attend.
			$available_seats = max( ceil( $estimated * 1.2 ), 50 ); // 20% buffer, minimum 50.
		}

		return array(
			'title'              => sanitize_text_field( $facebook_data['name'] ?? '' ),
			'description'        => sanitize_textarea_field( $facebook_data['description'] ?? '' ),
			'location'           => sanitize_text_field( $location ),
			'event_date'         => $event_date,
			'event_time'         => $event_time,
			'available_seats'    => $available_seats,
			'price'              => 25, // Default price, can be adjusted.
			'early_bird_price'   => 20, // Default early bird price.
			'early_bird_date'    => '', // Can be set manually.
			'facebook_event_id'  => sanitize_text_field( $facebook_data['id'] ?? '' ),
			'facebook_cover_url' => $facebook_data['cover']['source'] ?? '',
		);
	}

	/**
	 * Render extra content within the module card on the admin settings page.
	 *
	 * Displays Facebook integration setup instructions and connection status.
	 *
	 * @since 1.2.3
	 */
	public function render_admin_card_extras() {
		$settings     = $this->get_module_settings();
		$access_token = $settings['facebook_access_token'];

		?>
		<div class="aiohm-module-status">
			<?php if ( empty( $access_token ) ) : ?>
				<div class="aiohm-status-warning">
					<span class="dashicons dashicons-warning"></span>
					<?php esc_html_e( 'Access token required for event import', 'aiohm-booking-pro' ); ?>
				</div>
			<?php else : ?>
				<?php
				// Test the connection.
				$test_result = $this->test_facebook_connection();
				?>
				<?php if ( is_wp_error( $test_result ) ) : ?>
					<div class="aiohm-status-error">
						<span class="dashicons dashicons-dismiss"></span>
						<?php esc_html_e( 'Connection failed', 'aiohm-booking-pro' ); ?>
					</div>
				<?php else : ?>
					<div class="aiohm-status-success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Connected and ready!', 'aiohm-booking-pro' ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		
		<div class="aiohm-module-setup-hint">
			<p><strong><?php esc_html_e( 'Quick Setup:', 'aiohm-booking-pro' ); ?></strong></p>
			<p><?php esc_html_e( 'Get a temporary token from ', 'aiohm-booking-pro' ); ?><a href="https://developers.facebook.com/tools/explorer/" target="_blank"><?php esc_html_e( 'Facebook Graph API Explorer', 'aiohm-booking-pro' ); ?></a> <?php esc_html_e( 'with "events_read" permission.', 'aiohm-booking-pro' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Test Facebook API connection.
	 *
	 * Makes a test request to Facebook Graph API to validate credentials.
	 *
	 * @since 1.2.3
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function test_facebook_connection() {
		$settings     = $this->get_module_settings();
		$access_token = $settings['facebook_access_token'];

		if ( empty( $access_token ) ) {
			return new WP_Error( 'no_access_token', 'No access token provided.' );
		}

		// Test with a simple "me" request.
		$url = $this->graph_api_url . 'me?access_token=' . $access_token;

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', 'Could not connect to Facebook API.' );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'api_error', $data['error']['message'] );
		}

		return true;
	}
}