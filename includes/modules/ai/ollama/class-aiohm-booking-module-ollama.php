<?php
/**
 * Ollama Module for AIOHM Booking
 * Handles Ollama local AI integration for booking intelligence
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* <fs_premium_only> */

class AIOHM_BOOKING_Module_Ollama extends AIOHM_BOOKING_AI_Provider_Module_Abstract {

	protected $module_id = 'ollama';

	public static function get_ui_definition() {
		return array(
			'id'                  => 'ollama',
			'name'                => __( 'Ollama', 'aiohm-booking-pro' ),
			'description'         => __( 'Private, local AI models running on your own infrastructure for complete data privacy.', 'aiohm-booking-pro' ),
			'icon'                => '🏠',
			'category'            => 'ai',
			'access_level'        => 'premium',
			'is_premium'          => true,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => false,
			'visible_in_settings' => true,
		);
	}

	public function __construct() {
		parent::__construct();

		// This module has no admin page - settings only.
		$this->has_admin_page = false;
	}

	protected function init_hooks() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// AI provider hooks.
		add_filter( 'aiohm_booking_ai_providers', array( $this, 'register_ai_provider' ) );
		add_action( 'aiohm_booking_ai_ollama_query', array( $this, 'handle_ai_query' ), 10, 2 );

		// Admin hooks.
		add_action( 'wp_ajax_aiohm_booking_test_ollama', array( $this, 'test_connection' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );

		// Enqueue assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function get_settings_fields() {
		return array(
			'ollama_endpoint'     => array(
				'type'        => 'url',
				'label'       => 'Ollama Endpoint',
				'description' => 'URL to your Ollama server',
				'default'     => 'http://localhost:11434',
				'placeholder' => 'http://localhost:11434',
			),
			'ollama_model'        => array(
				'type'        => 'select',
				'label'       => 'Model',
				'description' => 'Choose the Ollama model',
				'options'     => array(
					'llama2'     => 'Llama 2 (7B)',
					'llama2:13b' => 'Llama 2 (13B)',
					'llama2:70b' => 'Llama 2 (70B)',
					'mistral'    => 'Mistral (7B)',
					'codellama'  => 'Code Llama',
					'vicuna'     => 'Vicuna',
					'custom'     => 'Custom Model',
				),
				'default'     => 'llama2',
			),
			'ollama_custom_model' => array(
				'type'        => 'text',
				'label'       => 'Custom Model Name',
				'description' => 'Name of your custom model (only if "Custom Model" is selected)',
				'default'     => '',
				'placeholder' => 'my-custom-model',
			),
			'ollama_temperature'  => array(
				'type'        => 'number',
				'label'       => 'Temperature',
				'description' => 'Creativity level (0.0 to 1.0)',
				'default'     => 0.7,
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.1,
			),
			'ollama_timeout'      => array(
				'type'        => 'number',
				'label'       => 'Timeout (seconds)',
				'description' => 'Request timeout for AI queries',
				'default'     => 30,
				'min'         => 5,
				'max'         => 120,
			),
		);
	}

	protected function get_default_settings() {
		return array(
			'ollama_endpoint'     => 'http://localhost:11434',
			'ollama_model'        => '',
			'ollama_custom_model' => '',
			'ollama_temperature'  => 0.7,
			'ollama_timeout'      => 30,
		);
	}



	protected function check_if_enabled() {
		$settings   = AIOHM_BOOKING_Settings::get_all();
		$enable_key = 'enable_' . $this->module_id;

		// If the setting exists (either '1' or '0'), respect it.
		if ( isset( $settings[ $enable_key ] ) ) {
			return ! empty( $settings[ $enable_key ] );
		}

		// Default to disabled for premium module.
		return false;
	}

	public function render_admin_page() {
		// Ollama module uses settings template only - no dedicated admin page.
		echo '<div class="wrap"><h1>Ollama Settings</h1><p>Ollama settings are managed through the main <a href="' . esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ) . '">Settings page</a>.</p></div>';
	}



	/**
	 * Test API connection
	 */
	public function test_connection() {

		// Verify nonce.
		$nonce_check = wp_verify_nonce( $_POST['nonce'] ?? '', 'aiohm_booking_test_ollama' );

		if ( ! $nonce_check ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$settings = $this->get_module_settings();
		$endpoint = $settings['ollama_base_url'] ?? $settings['ollama_endpoint'] ?? '';
		$model    = $settings['ollama_model'] ?? '';

		if ( empty( $endpoint ) ) {
			wp_send_json_error( 'Ollama endpoint is not configured. Please save your Ollama settings first.' );
		}

		if ( empty( $model ) ) {
			wp_send_json_error( 'Ollama model is not specified. Please enter a model name you have downloaded (use "ollama list" to see available models).' );
		}

		// Test actual connection to Ollama server.
		$test_result = $this->test_ollama_connection();

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
	 * Get connection status
	 */
	private function get_connection_status() {
		$settings = $this->get_module_settings();
		$endpoint = $settings['ollama_endpoint'] ?? '';

		if ( empty( $endpoint ) ) {
			return null;
		}

		// In a real implementation, this would test the actual connection.
		return array(
			'status'  => 'info',
			'message' => 'Ollama endpoint configured. Click "Test Connection" to verify server is running.',
		);
	}

	/**
	 * Get module settings
	 */
	public function get_module_settings() {
		// Read from global settings first, fall back to module-specific settings.
		$global_settings = get_option( 'aiohm_booking_settings', array() );
		$module_settings = get_option( 'aiohm_booking_ollama_settings', array() );
		return array_merge( $this->get_default_settings(), $module_settings, $global_settings );
	}

	/**
	 * Handle settings save
	 */
	public function handle_settings_save() {
		if ( isset( $_POST['ollama_nonce'] ) && wp_verify_nonce( $_POST['ollama_nonce'], 'aiohm_booking_ollama_settings' ) ) {
			if ( current_user_can( 'manage_options' ) && isset( $_POST['ollama_settings'] ) ) {
				$settings                        = array();
				$settings['ollama_endpoint']     = sanitize_url( $_POST['ollama_settings']['ollama_endpoint'] ?? 'http://localhost:11434' );
				$settings['ollama_model']        = sanitize_text_field( $_POST['ollama_settings']['ollama_model'] ?? 'llama2' );
				$settings['ollama_custom_model'] = sanitize_text_field( $_POST['ollama_settings']['ollama_custom_model'] ?? '' );
				$settings['ollama_temperature']  = floatval( $_POST['ollama_settings']['ollama_temperature'] ?? 0.7 );
				$settings['ollama_timeout']      = intval( $_POST['ollama_settings']['ollama_timeout'] ?? 30 );

				update_option( 'aiohm_booking_ollama_settings', $settings );

				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-success is-dismissible"><p>Ollama settings saved successfully!</p></div>';
					}
				);
			}
		}
	}

	/**
	 * Register this provider with the AI system
	 */
	public function register_ai_provider( $providers ) {
		$providers['ollama'] = array(
			'name'             => 'Ollama',
			'display_name'     => 'Ollama (Local AI)',
			'description'      => 'Private, local AI models for complete data privacy',
			'icon'             => '🏠',
			'class'            => get_class( $this ),
			'is_local'         => true,
			'requires_consent' => false,
			'configured'       => $this->is_configured(),
		);

		return $providers;
	}

	/**
	 * Handle AI query requests
	 */


	/**
	 * Check if Ollama is properly configured
	 */
	public function is_configured() {
		$settings = $this->get_module_settings();
		$endpoint = $settings['ollama_endpoint'] ?? '';
		$model    = $settings['ollama_model'] ?? '';

		// Check if we have an endpoint and model.
		if ( empty( $endpoint ) || empty( $model ) ) {
			return false;
		}

		// If custom model is selected, check if custom model name is provided.
		if ( $model === 'custom' && empty( $settings['ollama_custom_model'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Test actual connection to Ollama server
	 */
	public function test_ollama_connection() {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', 'Ollama is not properly configured' );
		}

		$settings = $this->get_module_settings();
		$endpoint = $settings['ollama_endpoint'] ?? '';
		$model    = $settings['ollama_model'] ?? 'llama2';

		if ( $model === 'custom' && ! empty( $settings['ollama_custom_model'] ) ) {
			$model = $settings['ollama_custom_model'];
		}

		// Test connection by checking if the server is responding.
		$response = wp_remote_get(
			$endpoint . '/api/tags',
			array(
				'timeout'    => 10,
				'user-agent' => 'AIOHM-Booking/' . AIOHM_BOOKING_VERSION,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_failed', 'Cannot connect to Ollama server: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			return new WP_Error( 'server_error', "Ollama server returned status: {$response_code}" );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! $data || ! isset( $data['models'] ) ) {
			return new WP_Error( 'invalid_response', 'Invalid response from Ollama server' );
		}

		// Check if the selected model is available.
		$available_models = array_column( $data['models'], 'name' );
		if ( ! in_array( $model, $available_models ) ) {
			return new WP_Error( 'model_not_found', "Model '{$model}' is not available on the server. Available models: " . implode( ', ', $available_models ) );
		}

		return array(
			'status'           => 'success',
			'message'          => "Successfully connected to Ollama server. Model '{$model}' is available.",
			'available_models' => $available_models,
			'endpoint'         => $endpoint,
		);
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	protected function get_provider_name() {
		return 'ollama';
	}

	/**
	 * Get provider display name
	 *
	 * @return string
	 */
	protected function get_provider_display_name() {
		return 'Ollama';
	}

	/**
	 * Make API call to Ollama
	 *
	 * @param string $query The query to send
	 * @param array  $context Additional context
	 * @return array|WP_Error Response or error
	 */
	protected function make_api_call( $query, $context = array() ) {
		$settings = $this->get_module_settings();
		$endpoint = $settings['ollama_endpoint'] ?? $settings['ollama_host'] ?? 'http://localhost:11434';

		if ( empty( $endpoint ) ) {
			return $this->format_error( 'Ollama endpoint not configured' );
		}

		$model         = $settings['ollama_model'] ?? 'llama2';
		$temperature   = $settings['ollama_temperature'] ?? 0.7;
		$system_prompt = $context['system_prompt'] ?? 'You are a helpful AI assistant for AIOHM Booking, a WordPress booking system. Provide accurate and helpful responses to user queries about bookings, accommodations, and events.';

		// Prepare API request for Ollama
		$url  = rtrim( $endpoint, '/' ) . '/api/generate';
		$body = array(
			'model'   => $model,
			'prompt'  => $system_prompt . "\n\n" . $query,
			'stream'  => false,
			'options' => array(
				'temperature' => $temperature,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 60, // Ollama can be slower
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->format_error( 'Failed to connect to Ollama server: ' . $response->get_error_message() );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( isset( $data['error'] ) ) {
			return $this->format_error( 'Ollama API error: ' . $data['error'] );
		}

		// Extract the response content
		if ( isset( $data['response'] ) ) {
			return array(
				'choices' => array(
					array(
						'message' => array(
							'content' => $data['response'],
						),
					),
				),
			);
		}

		return $this->format_error( 'Invalid Ollama API response format' );
	}

	/**
	 * Format error response
	 *
	 * @param string $message Error message
	 * @return WP_Error
	 */
	protected function format_error( $message ) {
		return array( 'error' => $message );
	}

	/**
	 * Validate API response
	 *
	 * @param array $response The API response to validate
	 * @return array Validated response array
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
				'error' => $response['error']['message'] ?? 'Unknown Ollama API error',
			);
		}

		if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
			return array(
				'valid' => false,
				'error' => 'Missing content in Ollama API response',
			);
		}

		return array(
			'valid'   => true,
			'content' => $response['choices'][0]['message']['content'],
		);
	}

	/**
	 * Render Ollama settings card for main settings page
	 */
	public static function render_settings_card() {
		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$settings = get_option( 'aiohm_booking_settings', array() );
		?>
		<!-- Ollama Configuration Section -->
		<div class="aiohm-booking-card aiohm-card aiohm-mb-8 aiohm-masonry-card" id="aiohm-ollama-settings" data-module="ollama">
			<div class="aiohm-masonry-drag-handle">
				<span class="dashicons dashicons-menu"></span>
			</div>
			<div class="aiohm-card-header aiohm-card__header">
				<div class="aiohm-card-header-title">
					<h3 class="aiohm-card-title aiohm-card__title">
						<span class="aiohm-card-icon">⚙️</span>
						Ollama Settings
					</h3>
				</div>
				<div class="aiohm-header-controls">
					<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-ollama-settings">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
					</button>
				</div>
			</div>
			<div class="aiohm-card-content aiohm-card__content">
				<p class="aiohm-p">Configure Ollama integration for private, local AI processing and automated content generation.</p>
				
				<div class="aiohm-form-group">
					<label class="aiohm-form-label">Ollama Server Endpoint</label>
					<div class="aiohm-input-wrapper">
						<input
							type="url"
							name="aiohm_booking_settings[ollama_base_url]"
							id="ollama_base_url"
							value="<?php echo esc_attr( $settings['ollama_base_url'] ?? 'http://localhost:11434' ); ?>"
							placeholder="http://localhost:11434"
							class="aiohm-form-input">
					</div>
					<div class="aiohm-preset-buttons">
						<button type="button" class="aiohm-preset-btn" data-endpoint="http://localhost:11434" data-target="ollama_base_url">
							Local Default
						</button>
						<button type="button" class="aiohm-preset-btn" data-endpoint="https://ollama.servbay.host/" data-target="ollama_base_url">
							ServBay Cloud
						</button>
					</div>
					<small class="description">
						<strong>Local:</strong> http://localhost:11434 (default Ollama installation)<br>
						<strong>ServBay:</strong> https://ollama.servbay.host/ (managed hosting)
					</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-label">Ollama Model</label>
					<input
						type="text"
						name="aiohm_booking_settings[ollama_model]"
						value="<?php echo esc_attr( $settings['ollama_model'] ?? '' ); ?>"
						placeholder="llama2, deepseek-llm:latest, codellama"
						class="aiohm-form-input">
					<small class="description">Enter the exact name of a model you've downloaded (e.g., llama2, deepseek-llm:latest, codellama). Use <code>ollama list</code> to see available models.</small>
				</div>

				<div class="aiohm-form-actions">
					<?php
					$ollama_base_url      = $settings['ollama_base_url'] ?? '';
					$is_ollama_configured = ! empty( $ollama_base_url );
					?>
					<button
						type="button"
						name="test_ollama_connection"
						id="test-ollama-connection"
						class="aiohm-btn aiohm-btn--primary aiohm-test-ai-connection"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'aiohm_booking_test_ai_connection' ) ); ?>"
						data-provider="ollama"
						data-target="ollama-connection-result"
						<?php echo ! $is_ollama_configured ? 'disabled' : ''; ?>>
						<span class="dashicons dashicons-admin-links"></span>
						<span class="btn-text">Test Connection</span>
					</button>
					<div id="ollama-connection-result" class="aiohm-connection-result"></div>
					<?php submit_button( 'Save Ollama Settings', 'primary', 'save_ollama_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--save' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

/* </fs_premium_only> */
