<?php
/**
 * OpenAI Module for AIOHM Booking
 * Handles OpenAI GPT integration for booking intelligence
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* <fs_premium_only> */

class AIOHM_BOOKING_Module_OpenAI extends AIOHM_BOOKING_AI_Provider_Module_Abstract {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $provider_name = 'openai';

	/**
	 * Provider display name
	 *
	 * @var string
	 */
	protected $provider_display_name = 'OpenAI';

	/**
	 * Provider icon
	 *
	 * @var string
	 */
	protected $provider_icon = '💡';

	/**
	 * Constructor - Initialize OpenAI module
	 */
	public function __construct() {
		parent::__construct();

		// Add AJAX handler for test connection
		add_action( 'wp_ajax_aiohm_booking_test_openai', array( $this, 'test_connection' ) );

		// Other hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Get UI definition for module registry
	 *
	 * @return array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'openai',
			'name'                => __( 'OpenAI', 'aiohm-booking-pro' ),
			'description'         => __( 'GPT-powered booking intelligence with advanced language understanding and natural responses.', 'aiohm-booking-pro' ),
			'icon'                => '💡',
			'category'            => 'ai',
			'access_level'        => 'premium',
			'is_premium'          => true,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => false,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	protected function get_provider_name() {
		return $this->provider_name;
	}

	/**
	 * Get provider display name
	 *
	 * @return string
	 */
	protected function get_provider_display_name() {
		return $this->provider_display_name;
	}

	/**
	 * Get provider description
	 *
	 * @return string
	 */
	protected function get_provider_description() {
		return 'Industry-leading AI with advanced language understanding and natural conversation capabilities.';
	}

	/**
	 * Get settings fields definition
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'openai_api_key'     => array(
				'type'        => 'password',
				'label'       => 'API Key',
				'description' => 'Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>',
				'default'     => '',
				'placeholder' => 'sk-...',
			),
			'openai_model'       => array(
				'type'        => 'select',
				'label'       => 'OpenAI Model',
				'description' => 'Choose the model that best balances performance and cost',
				'options'     => array(
					'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast & Cost-effective)',
					'gpt-4'         => 'GPT-4 (Most Capable)',
					'gpt-4-turbo'   => 'GPT-4 Turbo (Latest)',
				),
				'default'     => 'gpt-3.5-turbo',
			),
			'openai_max_tokens'  => array(
				'type'        => 'number',
				'label'       => 'Max Tokens',
				'description' => 'Maximum response length',
				'default'     => 500,
				'min'         => 100,
				'max'         => 4000,
			),
			'openai_temperature' => array(
				'type'        => 'number',
				'label'       => 'Temperature',
				'description' => 'Creativity (0.0-1.0)',
				'default'     => 0.7,
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.1,
			),
		);
	}

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	protected function get_default_settings() {
		return array(
			'openai_api_key'     => '',
			'openai_model'       => '',
			'openai_max_tokens'  => 1000,
			'openai_temperature' => 0.7,
		);
	}

	/**
	 * Make API call to OpenAI
	 *
	 * @param string $query The user query.
	 * @param array  $context Additional context.
	 * @return array
	 */
	protected function make_api_call( $query, $context = array() ) {
		$settings = $this->get_module_settings();
		$api_key  = $settings['openai_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return $this->format_error( 'OpenAI API key not configured' );
		}

		$model         = $settings['openai_model'] ?? 'gpt-3.5-turbo';
		$max_tokens    = $settings['openai_max_tokens'] ?? 500;
		$temperature   = $settings['openai_temperature'] ?? 0.7;
		$system_prompt = $context['system_prompt'] ?? 'You are a helpful assistant for AIOHM Booking, a WordPress booking system. Provide accurate and helpful responses to user queries about bookings, accommodations, and events.';

		// Prepare API request.
		$url  = 'https://api.openai.com/v1/chat/completions';
		$body = array(
			'model'       => $model,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => $system_prompt,
				),
				array(
					'role'    => 'user',
					'content' => $query,
				),
			),
			'max_tokens'  => $max_tokens,
			'temperature' => $temperature,
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->format_error( 'Failed to connect to OpenAI API: ' . $response->get_error_message() );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->format_error( 'Invalid response from OpenAI API' );
		}

		if ( isset( $data['error'] ) ) {
			return $this->format_error( 'OpenAI API error: ' . $data['error']['message'] );
		}

		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			return $this->format_error( 'Unexpected response format from OpenAI API' );
		}

		return $this->format_success( $data['choices'][0]['message']['content'], $model );
	}

	/**
	 * Validate API response format
	 *
	 * @param mixed $response Raw API response.
	 * @return array Validated response array.
	 */
	protected function validate_api_response( $response ) {
		if ( ! is_array( $response ) ) {
			return array(
				'valid' => false,
				'error' => 'Response is not an array',
			);
		}

		if ( isset( $response['error'] ) ) {
			return array(
				'valid' => false,
				'error' => $response['error']['message'] ?? 'Unknown API error',
			);
		}

		if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
			return array(
				'valid' => false,
				'error' => 'Missing content in API response',
			);
		}

		return array(
			'valid'   => true,
			'content' => $response['choices'][0]['message']['content'],
		);
	}

	/**
	 * Render admin page (uses parent class structure with additional cards)
	 */
	public function render_admin_page() {
		$usage_card   = $this->get_usage_examples_card();
		$pricing_card = $this->get_pricing_info_card();

		$this->render_admin_page_structure( array( $usage_card, $pricing_card ) );
	}

	/**
	 * Get usage examples card HTML
	 *
	 * @return string
	 */
	private function get_usage_examples_card() {
		ob_start();
		?>
		<div class="aiohm-card">
			<h3>Usage Examples</h3>
			<p>Example queries you can ask OpenAI:</p>
			<div class="aiohm-query-examples">
				<div class="aiohm-query-example">
					<code>"Analyze booking trends and suggest pricing optimizations"</code>
				</div>
				<div class="aiohm-query-example">
					<code>"Generate a welcome email for new bookings"</code>
				</div>
				<div class="aiohm-query-example">
					<code>"What factors are driving booking cancellations?"</code>
				</div>
				<div class="aiohm-query-example">
					<code>"Create marketing content for off-season periods"</code>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get pricing info card HTML
	 *
	 * @return string
	 */
	private function get_pricing_info_card() {
		ob_start();
		?>
		<div class="aiohm-card">
			<h3>Pricing Information</h3>
			<div class="aiohm-pricing-info">
				<div class="aiohm-pricing-item">
					<strong>GPT-3.5 Turbo:</strong> $0.002 per 1K tokens (most cost-effective)
				</div>
				<div class="aiohm-pricing-item">
					<strong>GPT-4:</strong> $0.03 per 1K tokens (highest quality)
				</div>
				<div class="aiohm-pricing-item">
					<strong>GPT-4 Turbo:</strong> $0.01 per 1K tokens (balanced option)
				</div>
			</div>
			<small>Visit <a href="https://openai.com/pricing" target="_blank">OpenAI Pricing</a> for current rates</small>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'aiohm-booking' ) === false ) {
			return;
		}

		// Enqueue OpenAI admin JavaScript
		wp_enqueue_script(
			'aiohm-booking-openai-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/aiohm-booking-openai-admin.js',
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Localize script with necessary data
		wp_localize_script(
			'aiohm-booking-openai-admin',
			'aiohm_openai',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_booking_test_ai_connection' ),
			)
		);
	}

	/**
	 * Test API connection
	 */
	public function test_connection() {
		// Verify nonce.
		$nonce_check = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'aiohm_booking_test_ai_connection' );

		if ( ! $nonce_check ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$settings = $this->get_module_settings();
		$api_key  = $settings['openai_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( 'OpenAI API key is not configured. Please save your API key first.' );
		}

		// Test actual connection to OpenAI API.
		$test_result = $this->test_openai_connection();

		if ( is_wp_error( $test_result ) ) {
			wp_send_json_error(
				array(
					'status'  => 'error',
					'message' => $test_result->get_error_message(),
				)
			);
		} else {
			wp_send_json_success( $test_result );
		}
	}

	/**
	 * Test OpenAI API connection
	 */
	private function test_openai_connection() {
		$settings = $this->get_module_settings();
		$api_key  = $settings['openai_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', 'OpenAI API key is missing. Please save your API key first.' );
		}

		try {
			// Use embeddings endpoint for testing - it's faster and cheaper than chat completions
			$response = wp_remote_post(
				'https://api.openai.com/v1/embeddings',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body'    => json_encode(
						array(
							'input' => 'test',
							'model' => 'text-embedding-ada-002',
						)
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'connection_failed', 'Connection error: ' . $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			if ( $response_code !== 200 ) {
				$decoded       = json_decode( $response_body, true );
				$error_message = $decoded['error']['message'] ?? 'API request failed with status ' . $response_code;
				return new WP_Error( 'api_error', $error_message );
			}

			$decoded_response = json_decode( $response_body, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_Error( 'json_error', 'Invalid JSON response from OpenAI API' );
			}

			// Check if we got valid embeddings response
			if ( ! isset( $decoded_response['data'][0]['embedding'] ) ) {
				return new WP_Error( 'response_error', 'Unexpected response structure from OpenAI API' );
			}

			return array(
				'status'  => 'success',
				'message' => 'OpenAI connection successful! API key is valid and working.',
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'test_failed', 'Test failed: ' . $e->getMessage() );
		}
	}


	/**
	 * Get module settings
	 */
	public function get_module_settings() {
		// Get settings from main plugin settings
		$main_settings = get_option( 'aiohm_booking_settings', array() );

		// Extract OpenAI settings with defaults
		$defaults = array(
			'openai_api_key'     => '',
			'openai_model'       => 'gpt-3.5-turbo',
			'openai_temperature' => 0.7,
			'openai_max_tokens'  => 500,
		);

		// Merge main settings with defaults, prioritizing main settings
		$settings = array();
		foreach ( $defaults as $key => $default_value ) {
			$settings[ $key ] = $main_settings[ $key ] ?? $default_value;
		}

		return $settings;
	}

	/**
	 * Render OpenAI settings card for main settings page
	 */
	public static function render_settings_card() {
		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$settings = get_option( 'aiohm_booking_settings', array() );
		?>
		<!-- OpenAI Configuration Section -->
		<div class="aiohm-booking-card aiohm-card aiohm-mb-8 aiohm-masonry-card" id="aiohm-openai-settings" data-module="openai">
			<div class="aiohm-masonry-drag-handle">
				<span class="dashicons dashicons-menu"></span>
			</div>
			<div class="aiohm-card-header aiohm-card__header">
				<div class="aiohm-card-header-title">
					<h3 class="aiohm-card-title aiohm-card__title">
						<span class="aiohm-card-icon">⚙️</span>
						OpenAI Settings
					</h3>
				</div>
				<div class="aiohm-header-controls">
					<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-openai-settings">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
					</button>
				</div>
			</div>
			<div class="aiohm-card-content aiohm-card__content">
				<p class="aiohm-p">Configure OpenAI GPT integration for advanced booking intelligence, natural language processing, and automated content generation.</p>
				
				<div class="aiohm-form-group">
					<label class="aiohm-form-label">OpenAI API Key</label>
					<div class="aiohm-input-wrapper">
						<input
							type="password"
							name="aiohm_booking_settings[openai_api_key]"
							id="openai_api_key"
							value="<?php echo esc_attr( $settings['openai_api_key'] ?? '' ); ?>"
							placeholder="sk-..."
							class="aiohm-form-input">
						<button type="button" class="aiohm-input-toggle" data-action="toggle-password" data-target="openai_api_key">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<small class="description">
						Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI Platform</a>
					</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-label">OpenAI Model</label>
					<input
						type="text"
						name="aiohm_booking_settings[openai_model]"
						value="<?php echo esc_attr( $settings['openai_model'] ?? 'GPT-5' ); ?>"
						placeholder="GPT-5"
						class="aiohm-form-input">
					<small class="description">Enter the OpenAI model name (e.g., GPT-5, GPT-4, GPT-3.5-turbo)</small>
				</div>

				<div class="aiohm-form-actions">
					<?php
					$openai_api_key       = $settings['openai_api_key'] ?? '';
					$is_openai_configured = ! empty( $openai_api_key );
					?>
					<button
						type="button"
						name="test_openai_connection"
						id="test-openai-connection"
						class="aiohm-btn aiohm-btn--primary aiohm-test-ai-connection"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'aiohm_booking_test_ai_connection' ) ); ?>"
						data-provider="openai"
						data-target="openai-connection-result"
						<?php echo ! $is_openai_configured ? 'disabled' : ''; ?>>
						<span class="dashicons dashicons-admin-links"></span>
						<span class="btn-text">Test Connection</span>
					</button>
					<div id="openai-connection-result" class="aiohm-connection-result"></div>
					<?php submit_button( 'Save OpenAI Settings', 'primary', 'save_openai_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--save' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

/* </fs_premium_only> */