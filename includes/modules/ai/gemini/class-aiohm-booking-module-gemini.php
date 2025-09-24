<?php

namespace AIOHM_Booking_PRO\Modules\AiGemini;
/**
 * Google Gemini Module for AIOHM Booking
 * Handles Google Gemini AI integration for booking intelligence
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* <fs_premium_only> */

class AIOHM_BOOKING_Module_Gemini extends AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_AI_Provider_Module_Abstract {

	public static function get_ui_definition() {
		return array(
			'id'                  => 'gemini',
			'name'                => __( 'Google Gemini', 'aiohm-booking-pro' ),
			'description'         => __( 'Google\'s most capable AI model with multimodal understanding and advanced reasoning.', 'aiohm-booking-pro' ),
			'icon'                => '✨',
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

		// Add admin hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	protected function init_hooks() {
		// AI provider hooks.
		add_filter( 'aiohm_booking_ai_providers', array( $this, 'register_ai_provider' ) );
		add_action( 'aiohm_booking_ai_gemini_query', array( $this, 'handle_ai_query' ), 10, 2 );

		// Admin hooks.
		add_action( 'wp_ajax_aiohm_booking_test_gemini', array( $this, 'test_connection' ) );
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );

		// Enqueue assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function get_settings_fields() {
		return array(
			'gemini_api_key'     => array(
				'type'        => 'password',
				'label'       => 'API Key',
				'description' => 'Enter your Google AI API key',
				'default'     => '',
				'placeholder' => 'AI...',
			),
			'gemini_model'       => array(
				'type'        => 'select',
				'label'       => 'Gemini Model',
				'description' => 'Choose the Gemini model',
				'options'     => array(
					'gemini-pro'        => 'Gemini Pro (Text & Reasoning)',
					'gemini-pro-vision' => 'Gemini Pro Vision (Text & Images)',
					'gemini-ultra'      => 'Gemini Ultra (Most Capable)',
				),
				'default'     => 'gemini-pro',
			),
			'gemini_temperature' => array(
				'type'        => 'number',
				'label'       => 'Temperature',
				'description' => 'Creativity level (0.0 to 1.0)',
				'default'     => 0.7,
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.1,
			),
			'gemini_max_tokens'  => array(
				'type'        => 'number',
				'label'       => 'Max Output Tokens',
				'description' => 'Maximum tokens for responses',
				'default'     => 1000,
				'min'         => 100,
				'max'         => 8000,
			),
		);
	}

	protected function get_default_settings() {
		return array(
			'gemini_api_key'     => '',
			'gemini_model'       => 'gemini-pro',
			'gemini_temperature' => 0.7,
			'gemini_max_tokens'  => 1000,
		);
	}

	public function render_admin_page() {
		$settings          = $this->get_module_settings();
		$api_key           = $settings['gemini_api_key'] ?? '';
		$is_configured     = ! empty( $api_key );
		$connection_status = $this->get_connection_status();
		?>
		
		<div class="wrap aiohm-booking-admin">
			<div class="aiohm-header">
				<div class="aiohm-header-content">
					<div class="aiohm-logo">
						<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-header-logo">
					</div>
					<div class="aiohm-header-text">
						<h1>✨ Google Gemini Integration</h1>
						<p class="aiohm-tagline">Google's most capable AI with multimodal understanding and advanced reasoning.</p>
					</div>
				</div>
			</div>

			<div class="aiohm-ai-provider-layout">
				<!-- Configuration Card -->
				<div class="aiohm-provider-card <?php echo $is_configured ? 'connected' : ''; ?>">
					<div class="aiohm-provider-header">
						<h3 class="aiohm-provider-name">
							<div class="aiohm-provider-icon gemini-icon">✨</div>
							Google Gemini
						</h3>
						<div class="aiohm-provider-status">
							<?php if ( $is_configured ) : ?>
								<span class="status-connected">✅ Configured</span>
							<?php else : ?>
								<span class="status-disconnected">⚠️ Not Configured</span>
							<?php endif; ?>
						</div>
					</div>
					
					<div class="aiohm-provider-description">
						Google's cutting-edge AI with multimodal capabilities and state-of-the-art reasoning.
					</div>

					<form method="post" action="">
						<?php wp_nonce_field( 'aiohm_booking_gemini_settings', 'gemini_nonce' ); ?>
						
						<div class="aiohm-api-key-section">
							<label for="gemini_api_key">Google AI API Key</label>
							<div class="aiohm-api-key-wrapper">
								<input type="password" 
										id="gemini_api_key" 
										name="gemini_settings[gemini_api_key]" 
										value="<?php echo esc_attr( $api_key ); ?>" 
										placeholder="AI..."
										class="aiohm-input">
								<button type="button" class="aiohm-show-hide-key" onclick="togglePasswordVisibility('gemini_api_key')">
									<span class="dashicons dashicons-visibility"></span>
								</button>
							</div>
							<small>Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></small>
						</div>

						<div class="aiohm-setting-row">
							<label for="gemini_model">Gemini Model</label>
							<select id="gemini_model" name="gemini_settings[gemini_model]" class="aiohm-select">
								<option value="gemini-pro" <?php selected( $settings['gemini_model'] ?? 'gemini-pro', 'gemini-pro' ); ?>>Gemini Pro (Text & Reasoning)</option>
								<option value="gemini-pro-vision" <?php selected( $settings['gemini_model'] ?? 'gemini-pro', 'gemini-pro-vision' ); ?>>Gemini Pro Vision (Text & Images)</option>
								<option value="gemini-ultra" <?php selected( $settings['gemini_model'] ?? 'gemini-pro', 'gemini-ultra' ); ?>>Gemini Ultra (Most Capable)</option>
							</select>
							<small>Choose based on your needs - Vision for image analysis, Ultra for complex reasoning</small>
						</div>

						<div class="aiohm-setting-row aiohm-row-split">
							<div class="aiohm-setting-col">
								<label for="gemini_temperature">Temperature</label>
								<input type="number" 
										id="gemini_temperature" 
										name="gemini_settings[gemini_temperature]"
										value="<?php echo esc_attr( $settings['gemini_temperature'] ?? 0.7 ); ?>"
										min="0" 
										max="1"
										step="0.1"
										class="aiohm-input">
								<small>Creativity (0.0-1.0)</small>
							</div>
							<div class="aiohm-setting-col">
								<label for="gemini_max_tokens">Max Tokens</label>
								<input type="number" 
										id="gemini_max_tokens" 
										name="gemini_settings[gemini_max_tokens]"
										value="<?php echo esc_attr( $settings['gemini_max_tokens'] ?? 1000 ); ?>"
										min="100" 
										max="8000"
										class="aiohm-input">
								<small>Maximum response length</small>
							</div>
						</div>

						<div class="aiohm-provider-actions">
							<button type="button" 
									id="test-gemini-connection" 
									class="aiohm-btn aiohm-btn--primary"
									<?php echo ! $is_configured ? 'disabled' : ''; ?>>
								<span class="dashicons dashicons-admin-links"></span>
								<span class="btn-text">Test Connection</span>
							</button>
							<button type="submit" 
									name="save_gemini_settings" 
									class="aiohm-btn aiohm-btn--save">
								<span class="dashicons dashicons-admin-settings"></span>
								<span class="btn-text">Save Settings</span>
							</button>
						</div>

						<?php if ( $connection_status ) : ?>
							<div class="aiohm-connection-status <?php echo esc_attr( $connection_status['status'] ); ?>">
								<?php echo esc_html( $connection_status['message'] ); ?>
							</div>
						<?php endif; ?>
					</form>
				</div>

				<!-- Features Card -->
				<div class="aiohm-card">
					<h3>Gemini Features</h3>
					<div class="aiohm-features-grid">
						<div class="aiohm-feature-item">
							<div class="aiohm-feature-icon">🔮</div>
							<h4>Multimodal AI</h4>
							<p>Process text, images, and data together for comprehensive analysis.</p>
						</div>
						<div class="aiohm-feature-item">
							<div class="aiohm-feature-icon">⚡</div>
							<h4>Fast Responses</h4>
							<p>Optimized for speed without compromising on quality or accuracy.</p>
						</div>
						<div class="aiohm-feature-item">
							<div class="aiohm-feature-icon">🎯</div>
							<h4>Advanced Reasoning</h4>
							<p>Complex problem-solving and logical reasoning capabilities.</p>
						</div>
						<div class="aiohm-feature-item">
							<div class="aiohm-feature-icon">🌐</div>
							<h4>Global Knowledge</h4>
							<p>Trained on diverse, up-to-date information from around the world.</p>
						</div>
					</div>
				</div>

				<!-- Usage Examples -->
				<div class="aiohm-card">
					<h3>Usage Examples</h3>
					<p>Example queries you can ask Gemini:</p>
					<div class="aiohm-query-examples">
						<div class="aiohm-query-example">
							<code>"Analyze seasonal booking patterns and recommend strategies"</code>
						</div>
						<div class="aiohm-query-example">
							<code>"Compare guest satisfaction across different accommodation types"</code>
						</div>
						<div class="aiohm-query-example">
							<code>"Generate personalized welcome messages for guests"</code>
						</div>
						<div class="aiohm-query-example">
							<code>"Identify opportunities to increase booking conversion rates"</code>
						</div>
					</div>
				</div>

				<!-- Model Comparison -->
				<div class="aiohm-card">
					<h3>Model Comparison</h3>
					<div class="aiohm-model-comparison">
						<div class="aiohm-model-item">
							<strong>Gemini Pro:</strong> Best for text analysis and general reasoning
						</div>
						<div class="aiohm-model-item">
							<strong>Gemini Pro Vision:</strong> Includes image understanding capabilities  
						</div>
						<div class="aiohm-model-item">
							<strong>Gemini Ultra:</strong> Most capable model for complex tasks
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register this AI provider
	 */
	public function register_ai_provider( $providers ) {
		$providers['gemini'] = array(
			'name'       => 'Google Gemini',
			'class'      => get_class( $this ),
			'configured' => ! empty( $this->get_module_settings()['gemini_api_key'] ),
			'icon'       => '✨',
		);

		return $providers;
	}

	/**
	 * Handle AI query
	 */
	public function handle_ai_query( $query, $context = array() ) {
		$settings = $this->get_module_settings();
		$api_key  = $settings['gemini_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return array( 'error' => 'Gemini API key not configured' );
		}

		$model         = $settings['gemini_model'] ?? 'gemini-1.5-flash';
		$system_prompt = $context['system_prompt'] ?? 'You are a helpful assistant for AIOHM Booking, a WordPress booking system. Provide accurate and helpful responses to user queries about bookings, accommodations, and events.';

		// Make API call to Gemini.
		$url  = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
		$body = array(
			'contents'         => array(
				array(
					'parts' => array(
						array(
							'text' => $system_prompt . "\n\nUser query: " . $query,
						),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => 0.7,
				'maxOutputTokens' => 1000,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'error' => 'Failed to connect to Gemini API: ' . $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array( 'error' => 'Invalid response from Gemini API' );
		}

		if ( isset( $data['error'] ) ) {
			return array( 'error' => 'Gemini API error: ' . $data['error']['message'] );
		}

		if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return array( 'error' => 'Unexpected response format from Gemini API' );
		}

		return array(
			'response' => $data['candidates'][0]['content']['parts'][0]['text'],
			'provider' => 'gemini',
			'model'    => $model,
		);
	}

	/**
	 * Test API connection
	 */
	public function test_connection() {
		// Verify nonce.
		$nonce_check = wp_verify_nonce( $_POST['nonce'] ?? '', 'aiohm_booking_test_gemini' );

		if ( ! $nonce_check ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$settings = $this->get_module_settings();
		$api_key  = $settings['gemini_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( 'Gemini API key is not configured. Please save your API key first.' );
		}

		// Test actual connection to Gemini API.
		$test_result = $this->test_gemini_connection();

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
	 * Test Gemini API connection
	 */
	private function test_gemini_connection() {
		$settings = $this->get_module_settings();
		$api_key  = $settings['gemini_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_api_key', 'Gemini API key is missing. Please save your API key first.' );
		}

		try {
			// Use the same proven pattern as the knowledge assistant - simple test request
			$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

			$data = array(
				'contents' => array(
					array( 'parts' => array( array( 'text' => 'Say hello' ) ) ),
				),
			);

			$response = wp_remote_post(
				$url,
				array(
					'headers' => array(
						'Content-Type'   => 'application/json',
						'x-goog-api-key' => $api_key,
					),
					'body'    => json_encode( $data ),
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
				return new WP_Error( 'json_error', 'Invalid JSON response from Gemini API' );
			}

			// Check if we got a valid response
			if ( ! isset( $decoded_response['candidates'][0]['content']['parts'][0]['text'] ) ) {
				return new WP_Error( 'response_error', 'Unexpected response structure from Gemini API' );
			}

			$ai_response = $decoded_response['candidates'][0]['content']['parts'][0]['text'];

			return array(
				'status'   => 'success',
				'message'  => 'Gemini connection successful! API is responding correctly.',
				'response' => substr( $ai_response, 0, 100 ) . ( strlen( $ai_response ) > 100 ? '...' : '' ),
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'test_failed', 'Test failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Get connection status
	 */
	private function get_connection_status() {
		$settings = $this->get_module_settings();
		$api_key  = $settings['gemini_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return null;
		}

		// In a real implementation, this would test the actual connection.
		return array(
			'status'  => 'success',
			'message' => 'Gemini connection ready. Click "Test Connection" to verify.',
		);
	}

	/**
	 * Get module settings
	 */
	public function get_module_settings() {
		// Get settings from main plugin settings
		$main_settings = get_option( 'aiohm_booking_settings', array() );

		// Extract Gemini settings with defaults
		$defaults = array(
			'gemini_api_key'     => '',
			'gemini_model'       => 'gemini-1.5-flash-latest',
			'gemini_temperature' => 0.7,
			'gemini_max_tokens'  => 500,
		);

		// Merge main settings with defaults, prioritizing main settings
		$settings = array();
		foreach ( $defaults as $key => $default_value ) {
			$settings[ $key ] = $main_settings[ $key ] ?? $default_value;
		}

		return $settings;
	}

	/**
	 * Handle settings save
	 */
	public function handle_settings_save() {
		if ( isset( $_POST['gemini_nonce'] ) && wp_verify_nonce( $_POST['gemini_nonce'], 'aiohm_booking_gemini_settings' ) ) {
			if ( current_user_can( 'manage_options' ) && isset( $_POST['gemini_settings'] ) ) {
				$settings                       = array();
				$settings['gemini_api_key']     = sanitize_text_field( $_POST['gemini_settings']['gemini_api_key'] ?? '' );
				$settings['gemini_model']       = sanitize_text_field( $_POST['gemini_settings']['gemini_model'] ?? 'gemini-pro' );
				$settings['gemini_temperature'] = floatval( $_POST['gemini_settings']['gemini_temperature'] ?? 0.7 );
				$settings['gemini_max_tokens']  = intval( $_POST['gemini_settings']['gemini_max_tokens'] ?? 1000 );

				update_option( 'aiohm_booking_gemini_settings', $settings );

				add_action(
					'admin_notices',
					function () {
						echo '<div class="notice notice-success is-dismissible"><p>Gemini settings saved successfully!</p></div>';
					}
				);
			}
		}
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	protected function get_provider_name() {
		return 'gemini';
	}

	/**
	 * Get provider display name
	 *
	 * @return string
	 */
	protected function get_provider_display_name() {
		return 'Google Gemini';
	}

	/**
	 * Make API call to Gemini
	 *
	 * @param string $query The query to send
	 * @param array  $context Additional context
	 * @return array|WP_Error Response or error
	 */
	protected function make_api_call( $query, $context = array() ) {
		$settings = $this->get_module_settings();
		$api_key  = $settings['gemini_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return $this->format_error( 'Gemini API key not configured' );
		}

		$model         = $settings['gemini_model'] ?? 'gemini-1.5-flash';
		$max_tokens    = $settings['gemini_max_tokens'] ?? 1000;
		$temperature   = $settings['gemini_temperature'] ?? 0.7;
		$system_prompt = $context['system_prompt'] ?? 'You are a helpful AI assistant for AIOHM Booking, a WordPress booking system. Provide accurate and helpful responses to user queries about bookings, accommodations, and events.';

		// Prepare API request for Gemini
		$url  = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
		$body = array(
			'contents'         => array(
				array(
					'parts' => array(
						array(
							'text' => $system_prompt . "\n\n" . $query,
						),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => $temperature,
				'maxOutputTokens' => $max_tokens,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->format_error( 'Failed to connect to Gemini API: ' . $response->get_error_message() );
		}

		$http_code     = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( $http_code !== 200 ) {
			return $this->format_error( 'Gemini API HTTP error: ' . $http_code . ' - ' . ( $data['error']['message'] ?? 'Unknown error' ) );
		}

		if ( isset( $data['error'] ) ) {
			return $this->format_error( 'Gemini API error: ' . $data['error']['message'] );
		}

		// Extract the response content
		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return array(
				'choices' => array(
					array(
						'message' => array(
							'content' => $data['candidates'][0]['content']['parts'][0]['text'],
						),
					),
				),
			);
		}

		// If we get here, the response format is unexpected
		return $this->format_error( 'Unexpected Gemini API response format. Response: ' . substr( $response_body, 0, 200 ) );
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
				'error' => 'Response is not an array',
			);
		}

		if ( isset( $response['error'] ) ) {
			return array(
				'error' => $response['error']['message'] ?? 'Unknown Gemini API error',
			);
		}

		if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
			return array(
				'error' => 'Missing content in Gemini API response',
			);
		}

		return array(
			'response' => $response['choices'][0]['message']['content'],
		);
	}

	/**
	 * Render Gemini settings card for main settings page
	 */
	public static function render_settings_card() {
		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$settings = get_option( 'aiohm_booking_settings', array() );
		?>
		<!-- Google Gemini Configuration Section -->
		<div class="aiohm-booking-card aiohm-card aiohm-mb-8 aiohm-masonry-card" id="aiohm-gemini-settings" data-module="gemini">
			<div class="aiohm-masonry-drag-handle">
				<span class="dashicons dashicons-menu"></span>
			</div>
			<div class="aiohm-card-header aiohm-card__header">
				<div class="aiohm-card-header-title">
					<h3 class="aiohm-card-title aiohm-card__title">
						<span class="aiohm-card-icon">⚙️</span>
						Google Gemini Settings
					</h3>
				</div>
				<div class="aiohm-header-controls">
					<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-gemini-settings">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
					</button>
				</div>
			</div>
			<div class="aiohm-card-content aiohm-card__content">
				<p class="aiohm-p">Configure Google Gemini AI integration for advanced booking intelligence, natural language processing, and automated content generation.</p>
				
				<div class="aiohm-form-group">
					<label class="aiohm-form-label">Google Gemini API Key</label>
					<div class="aiohm-input-wrapper">
						<input
							type="password"
							name="aiohm_booking_settings[gemini_api_key]"
							id="gemini_api_key"
							value="<?php echo esc_attr( $settings['gemini_api_key'] ?? '' ); ?>"
							placeholder="AIza..."
							class="aiohm-form-input">
						<button type="button" class="aiohm-input-toggle" data-action="toggle-password" data-target="gemini_api_key">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<small class="description">
						Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>
					</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-label">Gemini Model</label>
					<input
						type="text"
						name="aiohm_booking_settings[gemini_model]"
						value="<?php echo esc_attr( $settings['gemini_model'] ?? 'gemini-1.5-flash' ); ?>"
						placeholder="gemini-1.5-flash"
						class="aiohm-form-input">
					<small class="description">Enter the Gemini model name (e.g., gemini-1.5-flash, gemini-1.5-pro)</small>
				</div>

				<div class="aiohm-form-actions">
					<?php
					$gemini_api_key       = $settings['gemini_api_key'] ?? '';
					$is_gemini_configured = ! empty( $gemini_api_key );
					?>
					<button
						type="button"
						name="test_gemini_connection"
						id="test-gemini-connection"
						class="aiohm-btn aiohm-btn--primary aiohm-test-ai-connection"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'aiohm_booking_test_ai_connection' ) ); ?>"
						data-provider="gemini"
						data-target="gemini-connection-result"
						<?php echo ! $is_gemini_configured ? 'disabled' : ''; ?>>
						<span class="dashicons dashicons-admin-links"></span>
						<span class="btn-text">Test Connection</span>
					</button>
					<div id="gemini-connection-result" class="aiohm-connection-result"></div>
					<?php submit_button( 'Save Gemini Settings', 'primary', 'save_gemini_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--save' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

/* </fs_premium_only> */
