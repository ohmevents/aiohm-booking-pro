<?php
/**
 * ShareAI Module for AIOHM Booking
 * Handles ShareAI integration for booking intelligence
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* <fs_premium_only> */

class AIOHM_BOOKING_Module_ShareAI extends AIOHM_BOOKING_AI_Provider_Module_Abstract {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $provider_name = 'shareai';

	/**
	 * Provider display name
	 *
	 * @var string
	 */
	protected $provider_display_name = 'ShareAI';

	/**
	 * Provider icon
	 *
	 * @var string
	 */
	protected $provider_icon = '🤖';

	/**
	 * Constructor - Initialize ShareAI module
	 */
	public function __construct() {
		parent::__construct();

		// Add AJAX handler for test connection
		add_action( 'wp_ajax_aiohm_booking_test_shareai', array( $this, 'ajax_test_connection' ) );

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
			'id'                  => 'shareai',
			'name'                => __( 'ShareAI', 'aiohm-booking-pro' ),
			'description'         => __( 'AI-powered booking intelligence with collaborative features and advanced analytics.', 'aiohm-booking-pro' ),
			'icon'                => '🤖',
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
		return 'AI-powered booking intelligence with collaborative features and advanced analytics.';
	}

	/**
	 * Get settings fields definition
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'shareai_api_key'           => array(
				'type'        => 'password',
				'label'       => 'API Key',
				'description' => 'Enter your ShareAI API key',
				'default'     => '',
				'placeholder' => 'Enter your ShareAI API key',
			),
			'shareai_model'             => array(
				'type'        => 'select',
				'label'       => 'ShareAI Model',
				'description' => 'Choose the ShareAI model. Availability depends on the decentralized network - smaller models are often more available.',
				'options'     => array(
					'qwen2.5:0.5b'                       => 'Qwen 2.5 0.5B (Fast, most available)',
					'qwen2.5:1.5b'                       => 'Qwen 2.5 1.5B (Fast, very available)',
					'qwen2.5:3b'                         => 'Qwen 2.5 3B (Balanced performance)',
					'llama3.2:1b'                        => 'Llama 3.2 1B (Fast, available)',
					'llama3.2:3b'                        => 'Llama 3.2 3B (Good performance)',
					'qwen2.5:7b'                         => 'Qwen 2.5 7B (Good balance)',
					'deepseek-r1:7b'                     => 'DeepSeek R1 7B (Reasoning model)',
					'qwen2.5-coder:1.5b'                 => 'Qwen 2.5 Coder 1.5B (Code specialist)',
					'qwen2.5-coder:7b'                   => 'Qwen 2.5 Coder 7B (Advanced coding)',
					'llama4:17b-scout-16e-instruct-fp16' => 'Llama 4 17B Scout (Recommended - you have tokens)',
					'qwen2.5:14b'                        => 'Qwen 2.5 14B (High performance)',
					'deepseek-r1:14b'                    => 'DeepSeek R1 14B (Advanced reasoning)',
					'llama3.3:70b'                       => 'Llama 3.3 70B (Maximum quality)',
					'qwen2.5:32b'                        => 'Qwen 2.5 32B (High performance)',
					'deepseek-r1:32b'                    => 'DeepSeek R1 32B (Advanced reasoning)',
					'qwen2.5-coder:14b'                  => 'Qwen 2.5 Coder 14B (Expert coding)',
					'qwen2.5-coder:32b'                  => 'Qwen 2.5 Coder 32B (Master coding)',
					'llama4:90b-instruct-fp16'           => 'Llama 4 90B Instruct (Premium quality)',
					'deepseek-r1:671b'                   => 'DeepSeek R1 671B (Maximum reasoning)',
					'qwen2.5:72b'                        => 'Qwen 2.5 72B (Maximum performance)',
				),
				'default'     => 'llama4:17b-scout-16e-instruct-fp16',
			),
			'shareai_analytics_enabled' => array(
				'type'        => 'checkbox',
				'label'       => 'Enable Analytics Integration',
				'description' => 'Allow ShareAI to analyze your booking patterns for better insights',
				'default'     => true,
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
			'shareai_api_key'           => '',
			'shareai_model'             => 'llama4:17b-scout-16e-instruct-fp16',
			'shareai_analytics_enabled' => true,
		);
	}

	/**
	 * Make API call to ShareAI
	 *
	 * @param string $query The user query.
	 * @param array  $context Additional context.
	 * @return array
	 */
	protected function make_api_call( $query, $context = array() ) {
		$settings = $this->get_module_settings();
		$api_key  = $settings['shareai_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return $this->format_error( 'ShareAI API key not configured' );
		}

		$model         = $settings['shareai_model'] ?? 'llama4:17b-scout-16e-instruct-fp16';
		$system_prompt = $context['system_prompt'] ?? 'You are a helpful assistant for AIOHM Booking, a WordPress booking system. Provide accurate and helpful responses to user queries about bookings, accommodations, and events.';

		// ShareAI works better with combined messages like the knowledge assistant
		$combined_message = $system_prompt . "\n\n" . 'User question: ' . $query;

		// Prepare API request (ShareAI API structure).
		$url  = 'https://api.shareai.now/api/v1/chat/completions';
		$body = array(
			'model'       => $model,
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $combined_message,
				),
			),
			'temperature' => 0.7,
			'max_tokens'  => 4000,
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
			return $this->format_error( 'Failed to connect to ShareAI API: ' . $response->get_error_message() );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->format_error( 'Invalid response from ShareAI API' );
		}

		if ( isset( $data['error'] ) ) {
			// Handle specific ShareAI errors.
			if ( isset( $data['error']['code'] ) && 'insufficient_credits' === $data['error']['code'] ) {
				return $this->format_error( 'Insufficient credits. Please ensure your API key is linked to the account with purchased credits. Visit https://console.shareai.now/app/billing/ to check your balance and https://console.shareai.now/app/api-key/ to verify your API key.' );
			} elseif ( isset( $data['error']['code'] ) && 'no_device' === $data['error']['code'] ) {
				return $this->format_error( 'ShareAI network is busy. All devices are currently occupied. Please try again in a few minutes.' );
			}
			return $this->format_error( 'ShareAI API error: ' . $data['error']['message'] );
		}

		// Handle multiple response formats from ShareAI API
		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return $this->format_success( $data['choices'][0]['message']['content'], $model );
		} elseif ( isset( $data['message'] ) ) {
			$message = $data['message'];

			$final_content = '';
			if ( is_string( $message ) ) {
				$decoded = json_decode( $message, true );
				if ( $decoded !== null && isset( $decoded['content'] ) ) {
					$final_content = $decoded['content'];
				} else {
					$final_content = $message;
				}
			} elseif ( is_array( $message ) && isset( $message['content'] ) ) {
				$final_content = $message['content'];
			} else {
				$final_content = is_string( $message ) ? $message : wp_json_encode( $message );
			}

			return $this->format_success( $final_content, $model );
		} elseif ( isset( $data['response'] ) ) {
			return $this->format_success( $data['response'], $model );
		}

		return $this->format_error( 'Unexpected response format from ShareAI API' );
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
			$error_message = $response['error']['message'] ?? 'Unknown API error';

			// Handle specific ShareAI errors with helpful messages.
			if ( isset( $response['error']['code'] ) ) {
				switch ( $response['error']['code'] ) {
					case 'insufficient_credits':
						$error_message = 'Insufficient credits. Please check your billing at https://console.shareai.now/app/billing/';
						break;
					case 'no_device':
						$error_message = 'ShareAI network is busy. All devices are occupied. Please try again later.';
						break;
				}
			}

			return array(
				'valid' => false,
				'error' => $error_message,
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
	 * Custom field rendering for ShareAI checkbox
	 *
	 * @param string $field_id Field ID.
	 * @param array  $field Field definition.
	 * @param mixed  $value Current value.
	 * @param string $provider Provider name.
	 */
	protected function render_field( $field_id, $field, $value, $provider ) {
		if ( $field['type'] === 'checkbox' ) {
			$field_name = "{$provider}_settings[{$field_id}]";
			echo '<label class="aiohm-checkbox-wrapper">';
			echo '<input type="checkbox" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="1"' . checked( $value, true, false ) . '>';
			echo '<span class="aiohm-checkbox-label">' . esc_html( $field['label'] ) . '</span>';
			echo '</label>';
			return;
		}

		// Use parent implementation for other field types.
		parent::render_field( $field_id, $field, $value, $provider );
	}

	/**
	 * Render admin page (uses parent class structure with additional cards)
	 */
	public function render_admin_page() {
		$features_card = $this->get_features_card();
		$models_card   = $this->get_models_info_card();

		$this->render_admin_page_structure( array( $features_card, $models_card ) );
	}

	/**
	 * Get features card HTML
	 *
	 * @return string
	 */
	private function get_features_card() {
		ob_start();
		?>
		<div class="aiohm-card">
			<h3>ShareAI Features</h3>
			<div class="aiohm-features-list">
				<div class="aiohm-feature-item">
					<strong>Collaborative AI:</strong> Multiple AI models working together for comprehensive insights
				</div>
				<div class="aiohm-feature-item">
					<strong>Advanced Analytics:</strong> Deep analysis of booking patterns and customer behavior
				</div>
				<div class="aiohm-feature-item">
					<strong>Open Source Models:</strong> Access to cutting-edge open source AI models
				</div>
				<div class="aiohm-feature-item">
					<strong>Cost-Effective:</strong> Competitive pricing with transparent usage billing
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get models info card HTML
	 *
	 * @return string
	 */
	private function get_models_info_card() {
		ob_start();
		?>
		<div class="aiohm-card">
			<h3>Available Models</h3>
			<div class="aiohm-models-info">
				<div class="aiohm-model-category">
					<strong>DeepSeek R1 Series:</strong> Latest reasoning models
				</div>
				<div class="aiohm-model-category">
					<strong>Llama 4 Series:</strong> Meta's latest generation models
				</div>
				<div class="aiohm-model-category">
					<strong>Qwen 2.5 Series:</strong> Alibaba's multilingual models
				</div>
				<div class="aiohm-model-category">
					<strong>Qwen Coder Series:</strong> Specialized for code and technical tasks
				</div>
			</div>
			<small>All models are regularly updated with the latest versions</small>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for test connection
	 */
	public function ajax_test_connection() {
		// Verify nonce
		$nonce_check = wp_verify_nonce( $_POST['nonce'] ?? '', 'aiohm_booking_test_shareai' );

		if ( ! $nonce_check ) {
			wp_send_json_error( 'Security check failed' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Call the existing test connection method
		$result = $this->test_connection();

		if ( $result['success'] ) {
			wp_send_json_success(
				array(
					'status'  => 'success',
					'message' => $result['message'] ?? 'ShareAI connection successful!',
					'usage'   => $result['usage'] ?? null,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'status'  => 'error',
					'message' => $result['error'] ?? 'ShareAI connection failed',
				)
			);
		}
	}

	/**
	 * Test ShareAI API connection using the two-way approach
	 * 1. Key info endpoint (no token consumption)
	 * 2. Fallback to minimal chat test
	 *
	 * @return array Test result with success/error status
	 */
	public function test_connection() {
		$settings = $this->get_module_settings();
		$api_key  = $settings['shareai_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'error'   => 'ShareAI API key is missing.',
			);
		}

		// Method 1: Test using key info endpoint (no token consumption)
		$key_info_url = 'https://api.shareai.now/api/v1/iam/key/info';

		$response = wp_remote_get(
			$key_info_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			// Method 2: Fallback to basic connectivity test
			return $this->test_shareai_fallback_connection( $api_key );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$decoded       = json_decode( $response_body, true );

		if ( $response_code === 200 && isset( $decoded['status'] ) && $decoded['status'] === 'success' ) {
			return array(
				'success' => true,
				'message' => 'ShareAI connection successful - ' . ( $decoded['message'] ?? 'API key is valid' ),
				'data'    => array(
					'assigned_models' => $decoded['assigned_models_count'] ?? 0,
					'restrictions'    => $decoded['restrictions'] ?? 'unknown',
					'status'          => $decoded['status'],
				),
			);
		}

		// Method 2: Fallback if key info endpoint fails
		return $this->test_shareai_fallback_connection( $api_key );
	}

	/**
	 * Fallback connection test using minimal chat completion
	 *
	 * @param string $api_key The ShareAI API key.
	 * @return array Test result
	 */
	private function test_shareai_fallback_connection( $api_key ) {
		$test_url  = 'https://api.shareai.now/api/v1/chat/completions';
		$test_data = array(
			'model'      => 'llama4:17b-scout-16e-instruct-fp16',
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => 'Say "test successful"',
				),
			),
			'max_tokens' => 10,
		);

		$response = wp_remote_post(
			$test_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $test_data ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => 'Connection error: ' . $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code === 200 ) {
			return array(
				'success' => true,
				'message' => 'ShareAI connection successful via chat completions endpoint',
			);
		}

		$response_body = wp_remote_retrieve_body( $response );
		$decoded       = json_decode( $response_body, true );

		// Handle specific error responses
		if ( isset( $decoded['error'] ) ) {
			$error         = $decoded['error'];
			$error_message = $error['message'] ?? 'Unknown error';

			// Add helpful information for specific error types
			if ( isset( $error['code'] ) ) {
				switch ( $error['code'] ) {
					case 'insufficient_credits':
						$error_message = 'Insufficient credits. Please check your billing at ' . ( $error['help'] ?? 'https://console.shareai.now/app/billing/' );
						break;
					case 'no_device':
						$error_message = 'ShareAI network is busy. All devices are occupied. Please try again later.';
						break;
				}
			}
		} else {
			$error_message = 'API request failed with status ' . $response_code;
		}

		return array(
			'success' => false,
			'error'   => $error_message,
		);
	}

	/**
	 * Get module settings from main plugin settings
	 * Override the abstract class method to use main settings instead of separate option
	 */
	public function get_module_settings() {
		// Get settings from main plugin settings
		$main_settings = get_option( 'aiohm_booking_settings', array() );

		// Extract ShareAI settings with defaults
		$defaults = array(
			'shareai_api_key'     => '',
			'shareai_model'       => 'llama4:17b-scout-16e-instruct-fp16',
			'shareai_temperature' => 0.7,
			'shareai_max_tokens'  => 500,
		);

		// Merge main settings with defaults, prioritizing main settings
		$settings = array();
		foreach ( $defaults as $key => $default_value ) {
			$settings[ $key ] = $main_settings[ $key ] ?? $default_value;
		}

		return $settings;
	}

	/**
	 * Render ShareAI settings card for main settings page
	 */
	public static function render_settings_card() {
		// Check if AI Analytics module is enabled
		if ( ! aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
			return;
		}

		$settings = get_option( 'aiohm_booking_settings', array() );
		?>
		<!-- ShareAI Configuration Section -->
		<div class="aiohm-booking-card aiohm-card aiohm-mb-8 aiohm-masonry-card" id="aiohm-shareai-settings" data-module="shareai">
			<div class="aiohm-masonry-drag-handle">
				<span class="dashicons dashicons-menu"></span>
			</div>
			<div class="aiohm-card-header aiohm-card__header">
				<div class="aiohm-card-header-title">
					<h3 class="aiohm-card-title aiohm-card__title">
						<span class="aiohm-card-icon">⚙️</span>
						ShareAI Settings
					</h3>
				</div>
				<div class="aiohm-header-controls">
					<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-shareai-settings">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
					</button>
				</div>
			</div>
			<div class="aiohm-card-content aiohm-card__content">
				<p class="aiohm-p">Configure ShareAI integration for collaborative AI-powered booking intelligence and advanced analytics.</p>
				
				<div class="aiohm-form-group">
					<label class="aiohm-form-label">ShareAI API Key</label>
					<div class="aiohm-input-wrapper">
						<input
							type="password"
							name="aiohm_booking_settings[shareai_api_key]"
							id="shareai_api_key"
							value="<?php echo esc_attr( $settings['shareai_api_key'] ?? '' ); ?>"
							placeholder="sk-shareai-..."
							class="aiohm-form-input">
						<button type="button" class="aiohm-input-toggle" data-action="toggle-password" data-target="shareai_api_key">
							<span class="dashicons dashicons-visibility"></span>
						</button>
					</div>
					<small class="description">
						Get your API key from <a href="https://console.shareai.now/app/api-key/?utm_source=AIOHM&utm_medium=plugin&utm_campaign=shareai_integration" target="_blank" rel="noopener">ShareAI Console</a>
					</small>
				</div>

				<div class="aiohm-form-group">
					<label class="aiohm-form-label">ShareAI Model</label>
					<select name="aiohm_booking_settings[shareai_model]" class="aiohm-form-input">
						<option value="">Select a model...</option>
						<option value="deepseek-r1:7b" <?php selected( $settings['shareai_model'] ?? '', 'deepseek-r1:7b' ); ?>>DeepSeek R1 7B</option>
						<option value="deepseek-r1:14b" <?php selected( $settings['shareai_model'] ?? '', 'deepseek-r1:14b' ); ?>>DeepSeek R1 14B</option>
						<option value="deepseek-r1:32b" <?php selected( $settings['shareai_model'] ?? '', 'deepseek-r1:32b' ); ?>>DeepSeek R1 32B</option>
						<option value="deepseek-r1:671b" <?php selected( $settings['shareai_model'] ?? '', 'deepseek-r1:671b' ); ?>>DeepSeek R1 671B</option>
						<option value="llama4:17b-scout-16e-instruct-fp16" <?php selected( $settings['shareai_model'] ?? 'llama4:17b-scout-16e-instruct-fp16', 'llama4:17b-scout-16e-instruct-fp16' ); ?>>Llama 4 17B Scout (Recommended)</option>
						<option value="llama4:90b-instruct-fp16" <?php selected( $settings['shareai_model'] ?? '', 'llama4:90b-instruct-fp16' ); ?>>Llama 4 90B Instruct</option>
						<option value="qwen2.5:0.5b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:0.5b' ); ?>>Qwen 2.5 0.5B</option>
						<option value="qwen2.5:1.5b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:1.5b' ); ?>>Qwen 2.5 1.5B</option>
						<option value="qwen2.5:3b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:3b' ); ?>>Qwen 2.5 3B</option>
						<option value="qwen2.5:7b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:7b' ); ?>>Qwen 2.5 7B</option>
						<option value="qwen2.5:14b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:14b' ); ?>>Qwen 2.5 14B</option>
						<option value="qwen2.5:32b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:32b' ); ?>>Qwen 2.5 32B</option>
						<option value="qwen2.5:72b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5:72b' ); ?>>Qwen 2.5 72B</option>
						<option value="llama3.2:1b" <?php selected( $settings['shareai_model'] ?? '', 'llama3.2:1b' ); ?>>Llama 3.2 1B</option>
						<option value="llama3.2:3b" <?php selected( $settings['shareai_model'] ?? '', 'llama3.2:3b' ); ?>>Llama 3.2 3B</option>
						<option value="llama3.3:70b" <?php selected( $settings['shareai_model'] ?? '', 'llama3.3:70b' ); ?>>Llama 3.3 70B</option>
						<option value="qwen2.5-coder:1.5b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5-coder:1.5b' ); ?>>Qwen 2.5 Coder 1.5B</option>
						<option value="qwen2.5-coder:7b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5-coder:7b' ); ?>>Qwen 2.5 Coder 7B</option>
						<option value="qwen2.5-coder:14b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5-coder:14b' ); ?>>Qwen 2.5 Coder 14B</option>
						<option value="qwen2.5-coder:32b" <?php selected( $settings['shareai_model'] ?? '', 'qwen2.5-coder:32b' ); ?>>Qwen 2.5 Coder 32B</option>
					</select>
					<small class="description">Choose the ShareAI model for your booking intelligence needs</small>
				</div>

				<div class="aiohm-form-actions">
					<?php
					$shareai_api_key       = $settings['shareai_api_key'] ?? '';
					$is_shareai_configured = ! empty( $shareai_api_key );
					?>
					<button
						type="button"
						name="test_shareai_connection"
						id="test-shareai-connection"
						class="aiohm-btn aiohm-btn--primary aiohm-test-ai-connection"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'aiohm_booking_test_ai_connection' ) ); ?>"
						data-provider="shareai"
						data-target="shareai-connection-result"
						<?php echo ! $is_shareai_configured ? 'disabled' : ''; ?>>
						<span class="dashicons dashicons-admin-links"></span>
						<span class="btn-text">Test Connection</span>
					</button>
					<div id="shareai-connection-result" class="aiohm-connection-result"></div>
					<?php submit_button( 'Save ShareAI Settings', 'primary', 'save_shareai_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--save' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

/* </fs_premium_only> */