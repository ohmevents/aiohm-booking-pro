<?php

namespace AIOHM_Booking_PRO\Modules\AiAi-analytics;
/**
 * AI Analytics Module for AIOHM Booking
 * Provides AI-powered insights and analytics for booking data
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* <fs_premium_only> */

/**
 * AI Analytics Module for AIOHM Booking
 *
 * Provides AI-powered insights and analytics for booking data
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */
class AIOHM_BOOKING_Module_AI_Analytics extends AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_Module_Abstract {

	/**
	 * Get UI definition for the AI Analytics module
	 *
	 * @return array Module UI configuration
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'ai_analytics',
			'name'                => __( 'AI Analytics', 'aiohm-booking-pro' ),
			'description'         => __( 'Intelligent insights into your booking patterns and guest behavior.', 'aiohm-booking-pro' ),
			'icon'                => '📊',
			'category'            => 'booking',
			'access_level'        => 'premium',
			'is_premium'          => true,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => false,
			'settings_section'    => true,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Constructor for AI Analytics module
	 */
	public function __construct() {
		parent::__construct();

		// This module has no admin page - settings only.
		$this->has_admin_page = false;

		// Initialize AJAX hooks.
		add_action( 'wp_ajax_aiohm_booking_ai_query', array( $this, 'handle_ai_query' ) );
		add_action( 'wp_ajax_aiohm_booking_generate_insights', array( $this, 'generate_ai_insights' ) );
		add_action( 'wp_ajax_aiohm_booking_ai_extract_event_info', array( $this, 'handle_ai_extract_event_info' ) );
		add_action( 'wp_ajax_aiohm_booking_ai_import_event', array( $this, 'handle_ai_import_event' ) );
	}

	/**
	 * Initialize module hooks
	 */
	protected function init_hooks() {
		$settings = $this->get_module_settings();

		// Enqueue AI analytics scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Settings handler.
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );

		// Add analytics sections to orders page if enabled.
		if ( ! empty( $settings['enable_order_analytics'] ) ) {
			add_action( 'aiohm_booking_orders_page_bottom', array( $this, 'render_orders_analytics_section' ) );
		}

		// Note: AI Calendar Insights is now rendered directly in the calendar template
		// instead of using a hook to ensure proper container width
	}

	/**
	 * Get settings fields for the AI Analytics module.
	 *
	 * @return array Array of settings field configurations.
	 */
	public function get_settings_fields() {
		return array(
			'default_ai_provider'       => array(
				'type'        => 'select',
				'label'       => 'Default AI Provider',
				'description' => 'Choose your default AI provider for all analytics',
				'options'     => array(
					'shareai' => 'ShareAI',
					'openai'  => 'OpenAI',
					'gemini'  => 'Google Gemini',
					'ollama'  => 'Ollama (Private)',
				),
				'default'     => 'shareai',
			),
			'enable_order_analytics'    => array(
				'type'        => 'checkbox',
				'label'       => 'Add Analytics to Order Page',
				'description' => 'Show AI Order Insights section on the orders page',
				'default'     => true,
			),
			'enable_calendar_analytics' => array(
				'type'        => 'checkbox',
				'label'       => 'Add Analytics to Calendar Page',
				'description' => 'Show AI analytics section on the calendar page',
				'default'     => true,
			),
			'enable_ai_event_import'    => array(
				'type'        => 'checkbox',
				'label'       => 'Enable AI Event Import',
				'description' => 'Allow importing event details from any URL using AI',
				'default'     => true,
			),
		);
	}

	/**
	 * Get default settings for the AI Analytics module.
	 *
	 * @return array Array of default settings.
	 */
	protected function get_default_settings() {
		return array(
			'default_ai_provider'       => 'shareai',
			'enable_order_analytics'    => true,
			'enable_calendar_analytics' => true,
			'enable_ai_event_import'    => true,
		);
	}

	/**
	 * Render module settings for the main settings page
	 */
	public function render_settings() {
		$settings = $this->get_module_settings();
		// Check if global shortcode_ai_provider setting exists and use it.
		$global_settings  = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$current_provider = $global_settings['shortcode_ai_provider'] ?? $settings['default_ai_provider'] ?? 'shareai';
		?>
		<div class="aiohm-setting-row-inline">
			<div class="aiohm-setting-row">
				<label>Default AI Provider</label>
				<select name="ai_analytics_settings[default_ai_provider]" class="regular-text">
					<option value="shareai" <?php selected( $current_provider, 'shareai' ); ?>>ShareAI</option>
					<option value="openai" <?php selected( $current_provider, 'openai' ); ?>>OpenAI</option>
					<option value="gemini" <?php selected( $current_provider, 'gemini' ); ?>>Google Gemini</option>
					<option value="ollama" <?php selected( $current_provider, 'ollama' ); ?>>Ollama (Private)</option>
				</select>
				<small>Choose your default AI provider for all analytics</small>
			</div>
		</div>
		
		<div class="aiohm-setting-row aiohm-setting-row-spaced">
			<label>
				<input type="checkbox" name="ai_analytics_settings[enable_order_analytics]" value="1" <?php checked( $settings['enable_order_analytics'] ?? true ); ?> />
				Add Analytics to Order Page
			</label>
			<small>Show AI Order Insights section on the orders page</small>
		</div>
		
		<div class="aiohm-setting-row aiohm-setting-row-spaced">
			<label>
				<input type="checkbox" name="ai_analytics_settings[enable_calendar_analytics]" value="1" <?php checked( $settings['enable_calendar_analytics'] ?? true ); ?> />
				Add Analytics to Calendar Page
			</label>
			<small>Show AI analytics section on the calendar page</small>
		</div>
		
		<div class="aiohm-setting-row aiohm-setting-row-spaced">
			<label>
				<input type="checkbox" name="ai_analytics_settings[enable_ai_event_import]" value="1" <?php checked( $settings['enable_ai_event_import'] ?? true ); ?> />
				Enable AI Event Import
			</label>
			<small>Allow importing event details from any URL using AI</small>
		</div>
		
		<div class="aiohm-setting-row">
			<?php submit_button( 'Save Analytics Settings', 'primary', 'save_ai_analytics_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--save' ) ); ?>
		</div>
		<?php
	}

	/**
	 * Handle settings save for AI Analytics module
	 */
	public function handle_settings_save() {
		// Check if our settings were submitted
		if ( ! isset( $_POST['save_ai_analytics_settings'] ) ) {
			return;
		}

		// Verify nonce for security
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'update-options' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}

		// Get the submitted settings
		$ai_analytics_settings = $_POST['ai_analytics_settings'] ?? array();

		// Sanitize the settings
		$sanitized_settings = array(
			'default_ai_provider'       => sanitize_text_field( $ai_analytics_settings['default_ai_provider'] ?? 'shareai' ),
			'enable_order_analytics'    => ! empty( $ai_analytics_settings['enable_order_analytics'] ),
			'enable_calendar_analytics' => ! empty( $ai_analytics_settings['enable_calendar_analytics'] ),
			'enable_ai_event_import'    => ! empty( $ai_analytics_settings['enable_ai_event_import'] ),
		);

		// Save the settings
		$current_settings = $this->get_module_settings();
		$updated_settings = array_merge( $current_settings, $sanitized_settings );
		
		$result = $this->save_module_settings( $updated_settings );

		// Add admin notice
		if ( $result ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'AI Analytics settings saved successfully!', 'aiohm-booking-pro' ) . '</p></div>';
			} );
		} else {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to save AI Analytics settings.', 'aiohm-booking-pro' ) . '</p></div>';
			} );
		}

		// Redirect to prevent resubmission
		wp_redirect( add_query_arg( array( 'settings-updated' => 'true' ), wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle AI query requests
	 */
	public function handle_ai_query() {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ?? '' ), 'aiohm_ai_query_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$query   = sanitize_textarea_field( wp_unslash( $_POST['query'] ?? '' ) );
		$context = sanitize_text_field( wp_unslash( $_POST['context'] ?? 'general' ) );
		if ( empty( $query ) ) {
			wp_send_json_error( 'Query cannot be empty' );
		}

		// Get booking data for AI analysis.
		$booking_data = $this->get_booking_data_for_ai( $context );

		// Process AI query (stub implementation).
		$response = $this->process_ai_query( $query, $booking_data );

		wp_send_json_success(
			array(
				'response'  => $response,
				'query'     => $query,
				'timestamp' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Generate AI insights
	 */
	public function generate_ai_insights() {
		if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ?? '' ), 'aiohm_ai_query_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$booking_data = $this->get_booking_data_for_ai();
		$insights     = $this->generate_automated_insights( $booking_data );

		wp_send_json_success(
			array(
				'insights'  => $insights,
				'timestamp' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get booking data formatted for AI analysis
	 *
	 * @param string $context The context for the data (e.g., 'general', 'calendar').
	 */
	private function get_booking_data_for_ai( $context = 'general' ) {
		$data = array(
			'business_summary'     => $this->get_summary_statistics(),
			'recent_activity'      => $this->get_recent_bookings_data(),
			'product_performance'  => $this->get_product_performance_data(),
			'system_configuration' => $this->get_system_settings_context(),
			'query_context'        => $context,
			'report_generated_at'  => current_time( 'mysql' ) . ' ' . wp_timezone_string(),
		);

		if ( 'calendar' === $context ) {
			$data['calendar_context'] = $this->get_calendar_context();
		}

		return $data;
	}

	/**
	 * Get high-level summary statistics for the last 30 days.
	 */
	private function get_summary_statistics() {
		global $wpdb;
		$order_table = $wpdb->prefix . 'aiohm_booking_order';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $order_table ) ) !== $order_table ) {
			return array( 'error' => 'Booking data table not found.' );
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'paid' THEN total_amount END) as avg_order_value,
                COUNT(DISTINCT buyer_email) as unique_customers
            FROM %i
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ",
				$order_table
			),
			ARRAY_A
		);
	}

	/**
	 * Get data on the last few bookings (anonymized).
	 *
	 * @param int $limit The number of recent bookings to retrieve.
	 */
	private function get_recent_bookings_data( $limit = 5 ) {
		global $wpdb;
		$order_table = $wpdb->prefix . 'aiohm_booking_order';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $order_table ) ) !== $order_table ) {
			return array();
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				'
            SELECT 
                id,
                created_at,
                total_amount,
                currency,
                status,
                payment_method,
                rooms_qty,
                guests_qty
            FROM %i
            ORDER BY created_at DESC
            LIMIT %d
        ',
				$order_table,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get performance data for accommodations/events.
	 */
	private function get_product_performance_data() {
		$accommodation_posts = get_posts(
			array(
				'post_type'   => 'aiohm_accommodation',
				'numberposts' => -1,
				'post_status' => 'publish',
			)
		);

		$accommodations = array();
		foreach ( $accommodation_posts as $post ) {
			$accommodations[] = array(
				'name'  => $post->post_title,
				'price' => get_post_meta( $post->ID, '_aiohm_booking_accommodation_price', true ),
				'type'  => get_post_meta( $post->ID, '_aiohm_booking_accommodation_type', true ),
			);
		}

		return array(
			'available_accommodations' => $accommodations,
		);
	}

	/**
	 * Get key system settings to provide context.
	 */
	private function get_system_settings_context() {
		$settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		return array(
			'currency'                   => $settings['currency'] ?? 'USD',
			'deposit_percentage'         => $settings['deposit_percentage'] ?? 0,
			'early_bird_days'            => $settings['early_bird_days'] ?? 0,
			'default_accommodation_type' => $settings['accommodation_type'] ?? 'room',
			'notifications_enabled'      => ! empty( $settings['enable_notifications'] ),
		);
	}

	/**
	 * Get specific context for the calendar view.
	 */
	private function get_calendar_context() {
		$private_events = get_option( 'aiohm_booking_private_events', array() );
		$cell_statuses  = get_option( 'aiohm_booking_cell_statuses', array() );

		$total_accommodations = count(
			get_posts(
				array(
					'post_type'   => 'aiohm_accommodation',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			)
		);
		$total_days           = 30;
		$total_slots          = $total_accommodations * $total_days;
		$booked_slots         = 0;

		foreach ( $cell_statuses as $key => $status_info ) {
			if ( in_array( $status_info['status'], array( 'booked', 'external', 'blocked' ), true ) ) {
				++$booked_slots;
			}
		}

		$occupancy_rate = ( $total_slots > 0 ) ? round( ( $booked_slots / $total_slots ) * 100, 2 ) : 0;

		return array(
			'private_events_count'             => count( $private_events ),
			'manual_overrides_count'           => count( $cell_statuses ),
			'projected_occupancy_next_30_days' => $occupancy_rate . '%',
		);
	}

	/**
	 * Process AI query (stub implementation)
	 *
	 * @param string $query The user's query.
	 * @param array  $data  The booking data for analysis.
	 */
	private function process_ai_query( $query, $data ) {
		// Get default AI provider from global settings first, then module settings.
		$global_settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$provider        = $global_settings['shortcode_ai_provider'] ?? null;

		// If no global setting, fall back to module setting.
		if ( empty( $provider ) ) {
			$module_settings = $this->get_module_settings();
			$provider        = $module_settings['default_ai_provider'] ?? 'shareai';
		}

		// Get all plugin settings to find API keys.
		$all_settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();

		$api_key = $all_settings[ $provider . '_api_key' ] ?? '';
		$model   = $all_settings[ $provider . '_model' ] ?? '';

		if ( empty( $api_key ) && 'ollama' !== $provider ) {
			return "Error: API key for {$provider} is not configured in the AI Providers settings.";
		}

		// Get the context from the data array to generate a dynamic system prompt.
		$context       = $data['query_context'] ?? 'general';
		$system_prompt = $this->get_system_prompt_for_context( $context );

		$user_prompt = "Here is a summary of the booking data:\n\n" . wp_json_encode( $data, JSON_PRETTY_PRINT ) . "\n\nQuestion: " . $query;

		// Get the AI module instance.
		$ai_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::get_module_instance( $provider );

		if ( ! $ai_module ) {
			return 'Error: AI provider ' . $provider . ' is not available.';
		}

		// Call the AI module's handle_ai_query method.
		$result = $ai_module->handle_ai_query(
			$user_prompt,
			array(
				'system_prompt' => $system_prompt,
				'model'         => $model,
				'api_key'       => $api_key,
			)
		);

		if ( isset( $result['error'] ) ) {
			return 'Error from AI provider: ' . $result['error'];
		}

		return $result['response'];
	}

	/**
	 * Get a dynamic system prompt based on the query context.
	 *
	 * @param string $context The context of the query (e.g., 'general', 'calendar', 'orders').
	 * @return string The system prompt.
	 */
	private function get_system_prompt_for_context( $context ) {
		$base_prompt = "You are a helpful assistant for a WordPress booking system called AIOHM Booking. Your role is to answer questions about booking data. You will be provided with a summary of the booking data in JSON format. Use this data to answer the user's question. Be concise and helpful.";

		switch ( $context ) {
			case 'calendar':
				return $base_prompt . ' The user is currently viewing the booking calendar. Focus your answer on availability, occupancy, and date-related patterns. Provide insights that would be useful for a front-desk manager or a revenue manager looking at the calendar.';

			case 'orders':
				return $base_prompt . ' The user is on the orders management page. Focus on order statuses, revenue, customer data, and payment methods. Provide insights that would help an administrator or a business owner understand their sales performance.';

			case 'dashboard':
				return $base_prompt . ' The user is on the main dashboard. Provide a high-level overview of the business performance, highlighting key metrics and potential areas for growth.';

			default:
				return $base_prompt;
		}
	}

	/**
	 * Generate automated insights
	 *
	 * @param array $data The booking data for analysis.
	 */
	private function generate_automated_insights( $data ) {
		$insights = array();

		if ( $data['summary']->total_orders > 0 ) {
			$conversion_rate = ( $data['summary']->paid_orders / $data['summary']->total_orders ) * 100;

			$insights[] = array(
				'title'       => 'Conversion Performance',
				'value'       => round( $conversion_rate, 1 ) . '%',
				'description' => $conversion_rate > 70 ? 'Excellent conversion rate!' : 'Consider optimizing checkout process.',
				'type'        => $conversion_rate > 70 ? 'positive' : 'warning',
			);
		}

		if ( $data['summary']->total_revenue > 0 ) {
			$daily_revenue = $data['summary']->total_revenue / 30;
			$insights[]    = array(
				'title'       => 'Daily Revenue',
				'value'       => '$' . number_format( $daily_revenue, 2 ),
				'description' => 'Average daily revenue over the last 30 days',
				'type'        => 'info',
			);
		}

		return $insights;
	}

	/**
	 * REMOVED: Duplicate AI section - now handled by individual modules with green buttons
	 */

	/**
	 * REMOVED: Duplicate AI section - now handled by individual modules with green buttons
	 */

	/**
	 * Get module settings
	 */
	public function get_module_settings() {
		$saved_settings = get_option( 'aiohm_booking_ai_analytics_settings', array() );

		// Handle corrupted settings (if saved as string instead of array).
		if ( is_string( $saved_settings ) ) {
			// Try to decode JSON first.
			$decoded = json_decode( $saved_settings, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$saved_settings = $decoded;
				// Fix the corrupted option by saving the decoded array.
				update_option( 'aiohm_booking_ai_analytics_settings', $saved_settings );
			} else {
				// If not valid JSON, reset to empty array.
				$saved_settings = array();
				delete_option( 'aiohm_booking_ai_analytics_settings' );
			}
		}

		// Ensure we have an array.
		if ( ! is_array( $saved_settings ) ) {
			$saved_settings = array();
		}

		return array_merge( $this->get_default_settings(), $saved_settings );
	}

	/* Duplicate handle_settings_save method removed - using the one at line 194 */

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$valid_screens = array(
			'toplevel_page_aiohm-booking', // Dashboard.
			'aiohm-booking_page_aiohm-booking-orders', // Orders.
			'aiohm-booking_page_aiohm-booking-calendar', // Calendar.
		);

		if ( in_array( $screen->id, $valid_screens, true ) ) {
			// Enqueue AI Analytics JavaScript for calendar AI functionality
			if ( $screen->id === 'aiohm-booking_page_aiohm-booking-calendar' ) {
				wp_enqueue_script(
					'aiohm-ai-analytics-admin',
					AIOHM_BOOKING_URL . 'includes/modules/ai/ai-analytics/assets/js/aiohm-booking-ai-analytics-admin.js',
					array( 'jquery', 'aiohm-booking-admin' ),
					AIOHM_BOOKING_VERSION,
					true
				);

				wp_localize_script(
					'aiohm-ai-analytics-admin',
					'aiohm_ai_analytics',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'aiohm_ai_query_nonce' ),
					)
				);
			}
		}
	}

	/**
	 * Render AI analytics section on orders page
	 */
	public function render_orders_analytics_section() {
		$settings = $this->get_module_settings();
		if ( empty( $settings['enable_order_analytics'] ) ) {
			return;
		}

		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		?>
		<div class="aiohm-booking-card aiohm-ai-insights-card">
			<h3>
				<?php esc_html_e( 'AI Order Analytics', 'aiohm-booking-pro' ); ?>
				<span class="aiohm-ai-provider-badge">
					<?php
					$provider_names   = array(
						'shareai' => 'ShareAI',
						'openai'  => 'OpenAI',
						'gemini'  => 'Google Gemini',
						'ollama'  => 'Ollama',
					);
					$global_settings  = get_option( 'aiohm_booking_settings', array() );
					$default_provider = $global_settings['shortcode_ai_provider'] ?? '';
					echo esc_html( $provider_names[ $default_provider ] ?? 'AI' );
					?>
				</span>
			</h3>
			
			<p class="aiohm-module-description">
				<?php esc_html_e( 'Ask natural language questions about your order data, revenue patterns, and customer insights.', 'aiohm-booking-pro' ); ?>
			</p>
			
			<div class="aiohm-ai-query-interface">
				<div class="aiohm-query-input-section">
					<div class="aiohm-query-input-wrapper">
						<textarea id="aiohm-orders-ai-query" 
							placeholder="<?php esc_attr_e( 'Ask questions like: How many orders do I have this month? or What is the structure of the booking tables? or Which payment methods are most popular?', 'aiohm-booking-pro' ); ?>" 
							rows="3"></textarea>
						<button type="button" id="aiohm-orders-ai-submit" class="button button-primary">
							<span class="dashicons dashicons-search"></span>
							<?php esc_html_e( 'Ask AI', 'aiohm-booking-pro' ); ?>
						</button>
					</div>
				</div>
				
				<div class="aiohm-query-examples">
					<small><strong><?php esc_html_e( 'Example questions:', 'aiohm-booking-pro' ); ?></strong></small>
					<div class="aiohm-example-buttons">
						<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'What are my top revenue sources this month?', 'aiohm-booking-pro' ); ?>">
							<?php esc_html_e( 'Revenue Analysis', 'aiohm-booking-pro' ); ?>
						</button>
						<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'How is my order volume distributed by payment method?', 'aiohm-booking-pro' ); ?>">
							<?php esc_html_e( 'Payment Methods', 'aiohm-booking-pro' ); ?>
						</button>
						<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'Analyze customer booking patterns and preferences', 'aiohm-booking-pro' ); ?>">
							<?php esc_html_e( 'Customer Insights', 'aiohm-booking-pro' ); ?>
						</button>
					</div>
				</div>
				
				<div id="aiohm-orders-ai-results" class="aiohm-ai-response-area aiohm-hidden">
					<div class="aiohm-response-header">
						<h4><?php esc_html_e( 'AI Response', 'aiohm-booking-pro' ); ?></h4>
						<span class="aiohm-provider-badge"><?php esc_html_e( 'AI Assistant', 'aiohm-booking-pro' ); ?></span>
					</div>
					<div class="aiohm-response-content">
						<div class="aiohm-response-card">
							<div id="aiohm-orders-ai-response"></div>
						</div>
					</div>
					<div class="aiohm-response-actions">
						<button type="button" id="aiohm-orders-copy-response" class="button button-secondary">
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e( 'Copy Response', 'aiohm-booking-pro' ); ?>
						</button>
						<button type="button" id="aiohm-orders-clear-response" class="button button-secondary">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Clear', 'aiohm-booking-pro' ); ?>
						</button>
					</div>
				</div>
				
				<div id="aiohm-orders-ai-loading" class="aiohm-loading-indicator aiohm-hidden">
					<div class="aiohm-loading-spinner"></div>
					<span><?php esc_html_e( 'AI is analyzing your order data...', 'aiohm-booking-pro' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render AI analytics section on calendar page
	 */
	public function render_calendar_analytics_section() {
		$settings = $this->get_module_settings();
		if ( empty( $settings['enable_calendar_analytics'] ) ) {
			return;
		}

		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$global_settings     = get_option( 'aiohm_booking_settings', array() );
		$default_ai_provider = $global_settings['shortcode_ai_provider'] ?? '';

		?>
		<div class="aiohm-booking-admin-card">
			<div class="aiohm-booking-calendar-card-header">
			<div class="aiohm-header-content">
				<h3><?php esc_html_e( 'AI Calendar Insights', 'aiohm-booking-pro' ); ?></h3>
				<p class="aiohm-header-subtitle">
				<?php esc_html_e( 'Ask natural language questions about your booking database structure, data patterns, and business insights.', 'aiohm-booking-pro' ); ?>
				</p>
			</div>
			<div class="aiohm-header-actions">
				<span class="aiohm-ai-provider-badge">
					<?php
					$provider_names = array(
						'shareai' => 'ShareAI',
						'openai'  => 'OpenAI',
						'gemini'  => 'Google Gemini',
						'ollama'  => 'Ollama',
					);
					echo esc_html( $provider_names[ $default_ai_provider ] ?? 'AI' );
					?>
				</span>
			</div>
			</div>

			<div class="aiohm-ai-query-interface">
				<div class="aiohm-query-input-section">
					<div class="aiohm-query-input-wrapper">
						<textarea id="ai-table-query-input" placeholder="<?php esc_attr_e( 'Ask questions like: How many orders do I have this month? or What is the structure of the booking tables? or Which payment methods are most popular?', 'aiohm-booking-pro' ); ?>" rows="3"></textarea>
						<button type="button" id="submit-ai-table-query" class="button button-primary"><?php esc_html_e( 'Ask', 'aiohm-booking-pro' ); ?></button>
					</div>
					<div class="aiohm-query-examples">
						<small><strong><?php esc_html_e( 'Example questions:', 'aiohm-booking-pro' ); ?></strong></small>
						<div class="aiohm-example-buttons">
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'What are my busiest booking days and peak seasons?', 'aiohm-booking-pro' ); ?>">
								📅 <?php esc_html_e( 'Peak Season Analysis', 'aiohm-booking-pro' ); ?>
							</button>
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'How is my availability distributed and what are the occupancy rates?', 'aiohm-booking-pro' ); ?>">
								🏨 <?php esc_html_e( 'Occupancy Insights', 'aiohm-booking-pro' ); ?>
							</button>
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'What booking patterns show advance reservations vs last-minute bookings?', 'aiohm-booking-pro' ); ?>">
								⏰ <?php esc_html_e( 'Booking Timeline', 'aiohm-booking-pro' ); ?>
							</button>
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'Which dates have the highest demand and pricing opportunities?', 'aiohm-booking-pro' ); ?>">
								💎 <?php esc_html_e( 'Demand Forecast', 'aiohm-booking-pro' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div id="ai-table-response-area" class="aiohm-ai-response-area aiohm-hidden">
					<div class="aiohm-response-header">
						<h4><?php esc_html_e( 'AI Response', 'aiohm-booking-pro' ); ?></h4>
						<span class="aiohm-provider-badge"><?php esc_html_e( 'AI Assistant', 'aiohm-booking-pro' ); ?></span>
					</div>
					<div class="aiohm-response-content">
						<div class="aiohm-response-card">
							<div id="ai-response-text"></div>
						</div>
					</div>
					<div class="aiohm-response-actions">
						<button type="button" id="copy-ai-response" class="button button-secondary">
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e( 'Copy Response', 'aiohm-booking-pro' ); ?>
						</button>
						<button type="button" id="clear-ai-response" class="button button-secondary">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Clear', 'aiohm-booking-pro' ); ?>
						</button>
					</div>
				</div>

				<div id="ai-query-loading" class="aiohm-loading-indicator aiohm-hidden">
					<div class="aiohm-loading-spinner"></div>
					<span><?php esc_html_e( 'AI is analyzing your database...', 'aiohm-booking-pro' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AI event information extraction from URL
	 */
	public function handle_ai_extract_event_info() {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ?? '' ), 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
			return;
		}

		$event_url = sanitize_text_field( wp_unslash( $_POST['event_url'] ?? '' ) );

		if ( empty( $event_url ) ) {
			wp_send_json_error( array( 'message' => 'Event URL is required' ) );
			return;
		}

		if ( ! filter_var( $event_url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( array( 'message' => 'Invalid URL format' ) );
			return;
		}

		try {
			// Extract event information using AI.
			$extracted_info = $this->extract_event_info_from_url( $event_url );

			if ( is_wp_error( $extracted_info ) ) {
				$error_message = $extracted_info->get_error_message();

				// Provide more helpful error messages.
				if ( strpos( $error_message, 'API key not configured' ) !== false ) {
					$error_message = 'AI provider API key is not configured. Please configure your AI provider in the settings.';
				} elseif ( strpos( $error_message, 'Failed to connect' ) !== false ) {
					$error_message = 'Unable to connect to AI service. Please check your internet connection and try again.';
				} elseif ( strpos( $error_message, 'HTTP' ) !== false ) {
					$error_message = 'Unable to fetch content from the provided URL. The website may be blocking automated requests.';
				} elseif ( strpos( $error_message, 'Empty content' ) !== false ) {
					$error_message = 'The webpage appears to be empty or does not contain readable content.';
				} elseif ( strpos( $error_message, 'ai_provider_error' ) !== false || strpos( $error_message, 'ai_response_error' ) !== false ) {
					$error_message = 'AI service is temporarily unavailable. The AI model may be overloaded. Please try again in a few minutes.';
				} elseif ( strpos( $error_message, 'ai_extraction_failed' ) !== false ) {
					$error_message = 'AI could not extract event information from this page. The page may not contain clear event details or may use dynamic content loading.';
				}

				wp_send_json_error( array( 'message' => $error_message ) );
				return;
			}

			wp_send_json_success(
				array(
					'event_info' => $extracted_info,
					'confidence' => 'High',
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Failed to extract event information. Please try again.' ) );
		}
	}

	/**
	 * Handle AI event import
	 */
	public function handle_ai_import_event() {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ?? '' ), 'aiohm_booking_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
			return;
		}

		$event_data  = wp_unslash( $_POST['event_data'] ?? array() );
		$event_index = intval( wp_unslash( $_POST['event_index'] ?? 0 ) );

		if ( empty( $event_data ) || ! is_array( $event_data ) ) {
			wp_send_json_error( array( 'message' => 'Event data is required' ) );
			return;
		}

		try {
			// Sanitize the event data.
			$sanitized_data = $this->sanitize_event_data( $event_data );

			// Here you would typically save the event data to your system.
			// For now, we'll just return success with the sanitized data.
			wp_send_json_success(
				array(
					'message'     => 'Event imported successfully',
					'event_data'  => $sanitized_data,
					'event_index' => $event_index,
				)
			);

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => 'Failed to import event. Please try again.' ) );
		}
	}

	/**
	 * Extract event information from URL using AI
	 *
	 * @param string $url The event URL to extract information from.
	 * @return array|WP_Error Extracted event information or error
	 */
	private function extract_event_info_from_url( $url ) {
		// Get the default AI provider - check global settings first, then module settings.
		$global_settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$ai_provider     = $global_settings['shortcode_ai_provider'] ?? null;

		// If no global setting, fall back to module setting.
		if ( empty( $ai_provider ) ) {
			$module_settings = $this->get_module_settings();
			$ai_provider     = $module_settings['default_ai_provider'] ?? 'shareai';
		}

		// Get AI provider module.
		$ai_module = $this->get_ai_provider_module( $ai_provider );

		if ( ! $ai_module ) {
			return new WP_Error( 'ai_provider_not_found', 'AI provider not available: ' . $ai_provider );
		}

		// First, try to fetch the URL content.
		$page_content = $this->fetch_url_content( $url );

		if ( is_wp_error( $page_content ) ) {
			// If we can't fetch the content, fall back to URL-only approach.
			$page_content = '';
		} elseif ( empty( $page_content ) ) {
			$page_content = '';
		}

		// Create the AI prompt for event extraction.
		$prompt = $this->create_event_extraction_prompt( $url, $page_content );

		// Make AI request.
		try {
			$ai_response = $ai_module->handle_ai_query(
				$prompt,
				array(
					'context' => 'event_extraction',
					'url'     => $url,
				)
			);

			if ( is_wp_error( $ai_response ) ) {
				return $ai_response;
			}

			// Check if AI provider returned an error.
			if ( is_array( $ai_response ) && isset( $ai_response['error'] ) ) {
				return new WP_Error( 'ai_provider_error', $ai_response['error'] );
			}

			// Extract the response text from successful AI responses.
			if ( is_array( $ai_response ) && isset( $ai_response['response'] ) ) {
				$ai_response_text = $ai_response['response'];
			} elseif ( is_string( $ai_response ) ) {
				$ai_response_text = $ai_response;
			} else {
				return new WP_Error( 'ai_response_error', 'Unexpected response format from AI provider' );
			}

			// Parse the AI response to extract structured event data.
			$parsed_data = $this->parse_ai_event_response( $ai_response_text );

			// Check if we got any useful data.
			$has_useful_data = ! empty( $parsed_data['name'] ) || ! empty( $parsed_data['description'] ) ;

			if ( ! $has_useful_data ) {
				return new WP_Error( 'no_event_data', 'AI could not extract event information from this page. The page may not contain clear event details or may use dynamic content loading.' );
			}

			return $parsed_data;

		} catch ( Exception $e ) {
			return new WP_Error( 'ai_extraction_failed', 'Failed to extract event information: ' . $e->getMessage() );
		}
	}

	/**
	 * Create AI prompt for event extraction
	 *
	 * @param string $url The event URL.
	 * @param string $page_content The fetched page content (optional).
	 * @return string The AI prompt
	 */
	private function create_event_extraction_prompt( $url, $page_content = '' ) {
		$base_prompt = "Please analyze the following event page and extract event information. Return the information in JSON format with these fields:

URL: {$url}";

		if ( ! empty( $page_content ) ) {
			// Clean up the content for better AI processing.
			$clean_content = $this->clean_page_content( $page_content );
			$base_prompt  .= "\n\nPage Content:\n" . $clean_content;
		} else {
			$base_prompt .= "\n\nNote: Unable to fetch page content directly. Please try to infer event information from the URL structure if possible.";
		}

		$base_prompt .= "\n\nPlease extract and return in JSON format:
{
  \"name\": \"Event name/title\",
  \"description\": \"Event description\",
  \"start_date\": \"YYYY-MM-DD format\",
  \"end_date\": \"YYYY-MM-DD format (if different from start)\",
  \"start_time\": \"HH:MM format\",
  \"end_time\": \"HH:MM format\",
  \"price\": \"Ticket price or price range\",
  \"event_type\": \"Type of event (concert, workshop, conference, etc.)\",
  \"organizer\": \"Event organizer name\"
}

IMPORTANT: 
- If any information is not available, use null or empty string
- Focus on extracting accurate event details from the webpage content
- For dates, use YYYY-MM-DD format
- For times, use HH:MM format (24-hour)
- If the page doesn't appear to be an event page, return an empty JSON object {}
- Look for event-specific information like dates, times and pricing";

		return $base_prompt;
	}

	/**
	 * Fetch URL content for AI processing
	 *
	 * @param string $url The URL to fetch.
	 * @return string|WP_Error The page content or error
	 */
	private function fetch_url_content( $url ) {
		// Validate URL.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'Invalid URL provided' );
		}

		// For Facebook URLs, try a different approach.
		if ( strpos( $url, 'facebook.com' ) !== false ) {
			return $this->fetch_facebook_content( $url );
		}

		// Set up request arguments with more comprehensive headers.
		$args = array(
			'timeout'     => 20, // Increased timeout.
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			'headers'     => array(
				'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Language'           => 'en-US,en;q=0.9',
				'Accept-Encoding'           => 'gzip, deflate, br',
				'Connection'                => 'keep-alive',
				'Upgrade-Insecure-Requests' => '1',
				'Cache-Control'             => 'max-age=0',
				'Sec-Fetch-Dest'            => 'document',
				'Sec-Fetch-Mode'            => 'navigate',
				'Sec-Fetch-Site'            => 'none',
				'Sec-Fetch-User'            => '?1',
			),
			'sslverify'   => true,
		);

		// Make the request.
		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return new WP_Error( 'http_error', 'HTTP ' . $response_code . ' error when fetching URL' );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new WP_Error( 'empty_content', 'Empty content received from URL' );
		}

		return $body;
	}

	/**
	 * Fetch Facebook content with special handling
	 *
	 * @param string $url Facebook URL.
	 * @return string|WP_Error The page content or error
	 */
	private function fetch_facebook_content( $url ) {
		// Facebook blocks most automated requests, so we'll try multiple approaches.

		// Use WordPress HTTP API instead of curl
		$args = array(
			'timeout'     => 15,
			'redirection' => 3,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
			'headers'     => array(
				'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language'           => 'en-US,en;q=0.5',
				'Accept-Encoding'           => 'gzip, deflate',
				'Connection'                => 'keep-alive',
				'Upgrade-Insecure-Requests' => '1',
			),
		);

		$response = wp_remote_get( $url, $args );
		
		if ( ! is_wp_error( $response ) ) {
			$content   = wp_remote_retrieve_body( $response );
			$http_code = wp_remote_retrieve_response_code( $response );

			if ( $content && $http_code == 200 ) {
				return $content;
			}
		}

		// Try file_get_contents as fallback.
		$context = stream_context_create(
			array(
				'http' => array(
					'timeout'    => 15,
					'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
					'header'     => "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
								"Accept-Language: en-US,en;q=0.5\r\n" .
								"Accept-Encoding: gzip, deflate\r\n" .
								"Connection: keep-alive\r\n" .
								"Upgrade-Insecure-Requests: 1\r\n",
				),
			)
		);

		$content = @file_get_contents( $url, false, $context );

		if ( $content !== false && ! empty( $content ) ) {
			return $content;
		}

		// If all methods fail, return a helpful error.
		return new WP_Error( 'facebook_blocked', 'Facebook events cannot be automatically extracted due to Facebook\'s security measures. Please manually enter the event details or try a different event platform like Eventbrite.' );
	}

	/**
	 * Clean page content for AI processing
	 *
	 * @param string $content Raw page content.
	 * @return string Cleaned content
	 */
	private function clean_page_content( $content ) {
		// Remove HTML tags but keep text content.
		$content = wp_strip_all_tags( $content );

		// Remove excessive whitespace.
		$content = preg_replace( '/\s+/', ' ', $content );

		// Remove non-printable characters.
		$content = preg_replace( '/[^\x20-\x7E\x0A\x0D]/', '', $content );

		// Limit content length to avoid token limits (keep first 8000 characters).
		if ( strlen( $content ) > 8000 ) {
			$content = substr( $content, 0, 8000 ) . '...';
		}

		return trim( $content );
	}

	/**
	 * Parse AI response to extract structured event data
	 *
	 * @param string $ai_response The AI response.
	 * @return array Parsed event data
	 */
	private function parse_ai_event_response( $ai_response ) {
		// Try to extract JSON from the AI response.
		$json_start = strpos( $ai_response, '{' );
		$json_end   = strrpos( $ai_response, '}' );

		if ( $json_start !== false && $json_end !== false && $json_end > $json_start ) {
			$json_string = substr( $ai_response, $json_start, $json_end - $json_start + 1 );

			$parsed_data = json_decode( $json_string, true );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $this->sanitize_event_data( $parsed_data );
			}
		}

		// Fallback: try to parse the response manually.
		return $this->fallback_parse_event_response( $ai_response );
	}

	/**
	 * Sanitize extracted event data
	 *
	 * @param array $data Raw event data.
	 * @return array Sanitized event data
	 */
	private function sanitize_event_data( $data ) {
		return array(
			'name'        => sanitize_text_field( $data['name'] ?? '' ),
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'start_date'  => sanitize_text_field( $data['start_date'] ?? '' ),
			'end_date'    => sanitize_text_field( $data['end_date'] ?? '' ),
			'start_time'  => sanitize_text_field( $data['start_time'] ?? '' ),
			'end_time'    => sanitize_text_field( $data['end_time'] ?? '' ),
			'price'       => sanitize_text_field( $data['price'] ?? '' ),
			'event_type'  => sanitize_text_field( $data['event_type'] ?? '' ),
			'organizer'   => sanitize_text_field( $data['organizer'] ?? '' ),
		);
	}

	/**
	 * Fallback parsing for AI response
	 *
	 * @param string $response AI response text.
	 * @return array Parsed event data
	 */
	private function fallback_parse_event_response( $response ) {
		// Simple fallback parsing - look for common patterns.
		$data = array(
			'name'        => '',
			'description' => '',
			'start_date'  => '',
			'end_date'    => '',
			'start_time'  => '',
			'end_time'    => '',
			'price'       => '',
			'event_type'  => '',
			'organizer'   => '',
		);

		// Try to extract information using regex patterns.
		$patterns = array(
			'name'        => '/(?:event name|title)[:\s]*([^\n\r]+)/i',
			'description' => '/(?:description|about)[:\s]*([^\n\r]+)/i',
			'price'       => '/(?:price|cost|fee)[:\s]*([$€£¥]?\d+(?:\.\d{2})?)/i',
		);

		foreach ( $patterns as $field => $pattern ) {
			if ( preg_match( $pattern, $response, $matches ) ) {
				$data[ $field ] = trim( $matches[1] );
			}
		}

		return $this->sanitize_event_data( $data );
	}

	/**
	 * Get AI provider module instance
	 *
	 * @param string $provider_name The provider name.
	 * @return object|null AI provider module instance
	 */
	private function get_ai_provider_module( $provider_name ) {
		// Use the same method as the process_ai_query method.
		$ai_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::get_module_instance( $provider_name );

		return $ai_module;
	}

	/**
	 * Render AI Analytics settings card for main settings page
	 */
	public static function render_ai_analytics_settings_card() {
		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$settings              = get_option( 'aiohm_booking_settings', array() );
		$ai_analytics_settings = get_option( 'aiohm_booking_ai_analytics_settings', array() );

		// Merge settings for easier access
		$settings = array_merge( $settings, $ai_analytics_settings );
		?>
		<!-- AI Analytics Configuration Section -->
		<div class="aiohm-booking-card aiohm-card aiohm-mb-8" id="aiohm-ai-analytics-settings">
			<div class="aiohm-card-header aiohm-card__header">
				<div class="aiohm-card-header-title">
					<h3 class="aiohm-card-title aiohm-card__title">
						<span class="aiohm-card-icon">⚙️</span>
						AI Analytics Configuration
					</h3>
				</div>
				<div class="aiohm-header-controls">
					<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-ai-analytics-settings">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
					</button>
				</div>
			</div>
			<div class="aiohm-card-content aiohm-card__content">
				<p class="aiohm-p">Configure AI provider settings and privacy consent for AI analytics functionality.</p>
				
				<div class="aiohm-form-group">
					<label class="aiohm-form-label">Shortcode AI Provider</label>
					<select name="aiohm_booking_settings[shortcode_ai_provider]" class="aiohm-form-input">
						<option value="shareai" <?php selected( $settings['shortcode_ai_provider'] ?? 'gemini', 'shareai' ); ?>>ShareAI</option>
						<option value="openai" <?php selected( $settings['shortcode_ai_provider'] ?? 'gemini', 'openai' ); ?>>OpenAI</option>
						<option value="gemini" <?php selected( $settings['shortcode_ai_provider'] ?? 'gemini', 'gemini' ); ?>>Google Gemini</option>
						<option value="claude" <?php selected( $settings['shortcode_ai_provider'] ?? 'gemini', 'claude' ); ?>>Claude</option>
						<option value="ollama" <?php selected( $settings['shortcode_ai_provider'] ?? 'gemini', 'ollama' ); ?>>Ollama (Private)</option>
					</select>
					<small class="description">Choose the AI provider for shortcode processing</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-checkbox">
						<input type="checkbox" name="ai_analytics_settings[enable_order_analytics]" value="1" 
							<?php checked( isset( $ai_analytics_settings['enable_order_analytics'] ) ? $ai_analytics_settings['enable_order_analytics'] : false ); ?>>
						Add AI Analytics to Orders Page
					</label>
					<small class="description">Show AI analytics widget on the orders page</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-checkbox">
						<input type="checkbox" name="ai_analytics_settings[enable_calendar_analytics]" value="1" 
							<?php checked( isset( $ai_analytics_settings['enable_calendar_analytics'] ) ? $ai_analytics_settings['enable_calendar_analytics'] : false ); ?>>
						Add AI Analytics to Calendar Page
					</label>
					<small class="description">Show AI analytics widget on the calendar page</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-checkbox">
						<input type="checkbox" name="ai_analytics_settings[enable_ai_event_import]" value="1" 
							<?php checked( isset( $ai_analytics_settings['enable_ai_event_import'] ) ? $ai_analytics_settings['enable_ai_event_import'] : false ); ?>>
						Enable AI Event Import
					</label>
					<small class="description">Allow importing event details from any URL using AI</small>
				</div>

				<div class="aiohm-privacy-notice aiohm-mt-4">
					<h5>Privacy Notice</h5>
					<p>This plugin makes API calls to external AI services (OpenAI, Google Gemini, Claude, ShareAI, etc.) to process your content and provide AI responses.</p>
					<p>Your data is sent to these third-party services according to their respective privacy policies. No sensitive information such as payment details or personal customer data is transmitted.</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render AI sections for the dashboard
	 *
	 * @param array    $stats Dashboard statistics array
	 * @param string   $default_ai_provider Current AI provider
	 * @param string   $currency Currency setting
	 * @param callable $safe_number_format Number formatting function
	 */
	public static function render_dashboard_sections( $stats, $default_ai_provider, $currency, $safe_number_format ) {
		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		// Extract stats with fallbacks
		$total_revenue        = $stats['total_revenue'] ?? 0;
		$total_orders_30_days = $stats['total_orders_30_days'] ?? 0;
		$all_time_orders      = $stats['all_time_orders'] ?? 0;
		$avg_order_value      = $stats['avg_order_value'] ?? 0;

		// Provider configuration
		$provider_names = array(
			'shareai' => 'ShareAI',
			'openai'  => 'OpenAI',
			'gemini'  => 'Google Gemini',
			'ollama'  => 'Ollama',
		);
		$provider_icons = array(
			'shareai' => 'aiohm-booking-shareai-icon.jpeg',
			'openai'  => 'aiohm-booking-openai-icon.svg',
			'gemini'  => 'aiohm-booking-gemini-icon.svg',
			'ollama'  => 'aiohm-booking-ollama-icon.png',
		);
		$provider_name  = $provider_names[ $default_ai_provider ] ?? 'ShareAI';
		$provider_icon  = $provider_icons[ $default_ai_provider ] ?? 'aiohm-booking-shareai-icon.jpeg';
		?>
		<!-- AI Content Section -->
		<div class="aiohm-ai-row">
			<div class="aiohm-booking-card">
				<h3>Data Intelligence at Work</h3>
				<div class="aiohm-ai-stats">
					<div class="aiohm-ai-stat-main">
						<div class="ai-number"><?php echo esc_html( $safe_number_format( $all_time_orders * 16 ) ); ?></div>
						<div class="ai-label">Data Points Collected</div>
						<div class="ai-subtitle"><?php echo esc_html( $all_time_orders ); ?> orders × 16 data points each</div>
					</div>
					<div class="aiohm-ai-providers">
						<div class="ai-provider-active">
							<span class="ai-dot active"></span>
							<div class="ai-provider-info">
								<div class="ai-provider-icon">
									<?php
									$icon_path = AIOHM_BOOKING_DIR . 'assets/images/' . $provider_icon;
									if ( file_exists( $icon_path ) ) {
										echo '<img src="' . esc_url( AIOHM_BOOKING_URL . 'assets/images/' . $provider_icon ) . '" alt="' . esc_attr( $provider_name ) . '" class="provider-icon">';
									} else {
										// Fallback: Show provider name as text if icon doesn't exist
										echo '<div class="provider-icon-fallback">' . esc_html( substr( $provider_name, 0, 1 ) ) . '</div>';
									}
									?>
								</div>
								<div class="ai-provider-name"><?php echo esc_html( $provider_name ); ?></div>
								<div class="ai-provider-subtitle">We process this data with</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ai-insight-demo">
					<div class="insight-icon">📊</div>
					<div class="insight-content">
						<h4>Revenue Optimization Insight</h4>
						<p>"Based on <strong><?php echo esc_html( $safe_number_format( $all_time_orders * 16 ) ); ?> data points</strong> from <?php echo esc_html( $all_time_orders ); ?> orders, customers paying with credit cards show <strong>28% higher average order value</strong> ($<?php echo esc_html( $safe_number_format( $avg_order_value * 1.28, 2 ) ); ?>) compared to other payment methods. Consider offering card payment incentives to boost revenue."</p>
					</div>
				</div>

				<div class="ai-insight-demo">
					<div class="insight-icon">📅</div>
					<div class="insight-content">
						<h4>Calendar Intelligence Insight</h4>
						<p>"Calendar data analysis reveals <strong>65% of bookings occur within 14 days of inquiry</strong>. Rooms with <strong>pet-friendly policies show 34% higher occupancy rates</strong> and <strong>22% lower cancellation rates</strong>. Consider expanding pet accommodation options to increase bookings."</p>
					</div>
				</div>
			</div>

			<div class="aiohm-booking-card aiohm-ai-insights-card">
				<h3>AI Analytics in Action</h3>
				<div class="aiohm-ai-preview">
					<div class="ai-data-types">
						<h4>Data Points We Collect:</h4>
						<div class="data-grid">
							<span class="data-point">Order status & timing</span>
							<span class="data-point">Customer demographics</span>
							<span class="data-point">Booking dates & duration</span>
							<span class="data-point">Room & guest quantities</span>
							<span class="data-point">Payment methods & amounts</span>
							<span class="data-point">Revenue & deposit data</span>
							<span class="data-point">Accommodation preferences</span>
							<span class="data-point">Seasonal booking patterns</span>
							<span class="data-point">Cancellation rates</span>
							<span class="data-point">Repeat customer data</span>
							<span class="data-point">Early bird discounts</span>
							<span class="data-point">Pet accommodation requests</span>
							<span class="data-point">Calendar occupancy rates</span>
							<span class="data-point">System configuration</span>
							<span class="data-point">Notification preferences</span>
							<span class="data-point">Geographic booking trends</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render AI Calendar Insights section for direct template inclusion
	 */
	public function render_ai_calendar_insights_section() {
		$settings = $this->get_module_settings();
		if ( empty( $settings['enable_calendar_analytics'] ) ) {
			return;
		}

		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$global_settings     = get_option( 'aiohm_booking_settings', array() );
		$default_ai_provider = $global_settings['shortcode_ai_provider'] ?? '';

		?>
		<div class="aiohm-booking-calendar-card-header">
		<div class="aiohm-header-content">
			<h3><?php esc_html_e( 'AI Calendar Insights', 'aiohm-booking-pro' ); ?></h3>
			<p class="aiohm-header-subtitle">
			<?php esc_html_e( 'Ask natural language questions about your booking database structure, data patterns, and business insights.', 'aiohm-booking-pro' ); ?>
			</p>
		</div>
		<div class="aiohm-header-actions">
			<span class="aiohm-ai-provider-badge">
				<?php
				$provider_names = array(
					'shareai' => 'ShareAI',
					'openai'  => 'OpenAI',
					'gemini'  => 'Google Gemini',
					'ollama'  => 'Ollama',
				);
				echo esc_html( $provider_names[ $default_ai_provider ] ?? 'AI' );
				?>
			</span>
		</div>
		</div>

			<div class="aiohm-ai-query-interface">
				<div class="aiohm-query-input-section">
					<div class="aiohm-query-input-wrapper">
						<textarea id="ai-table-query-input" placeholder="<?php esc_attr_e( 'Ask questions like: How many orders do I have this month? or What is the structure of the booking tables? or Which payment methods are most popular?', 'aiohm-booking-pro' ); ?>" rows="3"></textarea>
						<button type="button" id="submit-ai-table-query" class="button button-primary"><?php esc_html_e( 'Ask', 'aiohm-booking-pro' ); ?></button>
					</div>
					<div class="aiohm-query-examples">
						<small><strong><?php esc_html_e( 'Example questions:', 'aiohm-booking-pro' ); ?></strong></small>
						<div class="aiohm-example-buttons">
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'What are my busiest booking days and peak seasons?', 'aiohm-booking-pro' ); ?>">
								📅 <?php esc_html_e( 'Peak Season Analysis', 'aiohm-booking-pro' ); ?>
							</button>
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'How is my availability distributed and what are the occupancy rates?', 'aiohm-booking-pro' ); ?>">
								🏨 <?php esc_html_e( 'Occupancy Insights', 'aiohm-booking-pro' ); ?>
							</button>
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'What booking patterns show advance reservations vs last-minute bookings?', 'aiohm-booking-pro' ); ?>">
								⏰ <?php esc_html_e( 'Booking Timeline', 'aiohm-booking-pro' ); ?>
							</button>
							<button type="button" class="aiohm-example-btn" data-query="<?php esc_attr_e( 'Which dates have the highest demand and pricing opportunities?', 'aiohm-booking-pro' ); ?>">
								💎 <?php esc_html_e( 'Demand Forecast', 'aiohm-booking-pro' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div id="ai-table-response-area" class="aiohm-ai-response-area aiohm-hidden">
					<div class="aiohm-response-header">
						<h4><?php esc_html_e( 'AI Response', 'aiohm-booking-pro' ); ?></h4>
						<span class="aiohm-provider-badge"><?php esc_html_e( 'AI Assistant', 'aiohm-booking-pro' ); ?></span>
					</div>
					<div class="aiohm-response-content">
						<div class="aiohm-response-card">
							<div id="ai-response-text"></div>
						</div>
					</div>
					<div class="aiohm-response-actions">
						<button type="button" id="copy-ai-response" class="button button-secondary">
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e( 'Copy Response', 'aiohm-booking-pro' ); ?>
						</button>
						<button type="button" id="clear-ai-response" class="button button-secondary">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Clear', 'aiohm-booking-pro' ); ?>
						</button>
					</div>
				</div>
			</div>
		<?php
	}
}

/* </fs_premium_only> */
