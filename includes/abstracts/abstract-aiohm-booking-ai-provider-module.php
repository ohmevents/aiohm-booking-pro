<?php
/**
 * Abstract AI Provider Module class
 * Base class for all AI provider module implementations
 * Extends the base module class with AI-specific functionality
 *
 * @package AIOHM_Booking_PRO
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 * @license GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.html
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract AI Provider Module class
 *
 * @since  2.0.0
 */
abstract class AIOHM_BOOKING_AI_Provider_Module_Abstract extends AIOHM_BOOKING_Module_Abstract {

	/**
	 * Provider name (lowercase, used for settings keys and hooks)
	 *
	 * @var string
	 */
	protected $provider_name = '';

	/**
	 * Provider display name
	 *
	 * @var string
	 */
	protected $provider_display_name = '';

	/**
	 * Provider icon (emoji or HTML)
	 *
	 * @var string
	 */
	protected $provider_icon = '🤖';

	/**
	 * Default API settings
	 *
	 * @var array
	 */
	protected $default_api_settings = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		// AI provider modules don't have admin pages by default.
		$this->has_admin_page = false;
	}

	/**
	 * Initialize common AI provider hooks
	 * Child classes can override this to add provider-specific hooks
	 */
	protected function init_hooks() {
		$provider = $this->get_provider_name();

		// Register AI provider.
		add_filter( 'aiohm_booking_ai_providers', array( $this, 'register_ai_provider' ) );

		// Handle AI queries.
		add_action( "aiohm_booking_ai_{$provider}_query", array( $this, 'handle_ai_query' ), 10, 2 );

		// Test connection AJAX.
		add_action( "wp_ajax_aiohm_booking_test_{$provider}", array( $this, 'test_connection' ) );

		// Settings save.
		add_action( 'admin_init', array( $this, 'handle_settings_save' ) );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Get provider name (must be implemented by child classes)
	 *
	 * @return string
	 */
	abstract protected function get_provider_name();

	/**
	 * Get provider display name (must be implemented by child classes)
	 *
	 * @return string
	 */
	abstract protected function get_provider_display_name();

	/**
	 * Get provider-specific settings fields (must be implemented by child classes)
	 *
	 * @return array
	 */
	abstract public function get_settings_fields();

	/**
	 * Get default settings (must be implemented by child classes)
	 *
	 * @return array
	 */
	abstract protected function get_default_settings();

	/**
	 * Make API call to provider (must be implemented by child classes)
	 *
	 * @param string $query The user query.
	 * @param array  $context Additional context.
	 * @return array
	 */
	abstract protected function make_api_call( $query, $context = array() );

	/**
	 * Validate API response format
	 *
	 * @param mixed $response Raw API response.
	 * @return array Validated response array.
	 */
	abstract protected function validate_api_response( $response );

	/**
	 * Register this AI provider in the global providers list
	 *
	 * @param array $providers Existing providers.
	 * @return array
	 */
	public function register_ai_provider( $providers ) {
		$providers[ $this->get_provider_name() ] = array(
			'name'       => $this->get_provider_display_name(),
			'class'      => get_class( $this ),
			'configured' => $this->is_configured(),
			'icon'       => $this->provider_icon,
		);

		return $providers;
	}

	/**
	 * Handle AI query with common error handling and validation
	 *
	 * @param string $query The user query.
	 * @param array  $context Additional context.
	 * @return array
	 */
	public function handle_ai_query( $query, $context = array() ) {
		try {
			// Validate input
			if ( empty( $query ) ) {
				return $this->format_error( 'Query cannot be empty' );
			}

			// Check if configured.
			if ( ! $this->is_configured() ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					$this->get_provider_display_name() . ' API key not configured',
					'ai_provider_error',
					array( 'provider' => $this->get_provider_name() )
				);
				return $this->format_error( $this->get_provider_display_name() . ' API key not configured' );
			}

			// Rate limiting.
			if ( ! $this->check_rate_limit() ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					'Rate limit exceeded for ' . $this->get_provider_name(),
					'rate_limit_error',
					array( 'provider' => $this->get_provider_name() )
				);
				return $this->format_error( 'Rate limit exceeded. Please try again later.' );
			}

			// Make the API call.
			$result = $this->make_api_call( $query, $context );

			// Validate response format
			if ( isset( $result['error'] ) ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					'API call failed for ' . $this->get_provider_name(),
					'ai_api_error',
					array(
						'provider' => $this->get_provider_name(),
						'error'    => $result['error'],
					)
				);
				return $result;
			}

			// Validate and format successful response
			$validated_result = $this->validate_api_response( $result );
			if ( isset( $validated_result['error'] ) ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					'Response validation failed for ' . $this->get_provider_name(),
					'validation_error',
					array(
						'provider'     => $this->get_provider_name(),
						'raw_response' => $result,
					)
				);
				return $validated_result;
			}

			// Add provider metadata to successful responses.
			if ( isset( $validated_result['response'] ) ) {
				$validated_result['provider']      = $this->get_provider_name();
				$validated_result['provider_name'] = $this->get_provider_display_name();
				$validated_result['timestamp']     = current_time( 'timestamp' );
			}

			return $validated_result;

		} catch ( Exception $e ) {
			AIOHM_BOOKING_Error_Handler::log_error(
				'Exception in AI query handling: ' . $e->getMessage(),
				'exception_error',
				array(
					'provider'  => $this->get_provider_name(),
					'exception' => get_class( $e ),
					'trace'     => $e->getTraceAsString(),
				)
			);
			return $this->format_error( 'An unexpected error occurred. Please try again.' );
		}
	}

	/**
	 * Test API connection with common structure
	 */
	public function test_connection() {
		$provider = $this->get_provider_name();

		try {
			// Verify security using centralized helper.
			if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'test_' . $provider . '_nonce', 'manage_options' ) ) {
				return; // Error response already sent by helper
			}

			if ( ! $this->is_configured() ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					'Test connection failed: API key not configured for ' . $this->get_provider_name(),
					'configuration_error',
					array( 'provider' => $this->get_provider_name() )
				);
				wp_send_json_error( 'API key is required' );
				return;
			}

			// Test the actual connection.
			$test_result = $this->perform_connection_test();

			if ( $test_result['success'] ) {
				AIOHM_BOOKING_Error_Handler::log_error(
					'Connection test successful for ' . $this->get_provider_name(),
					'connection_test_success',
					array( 'provider' => $this->get_provider_name() ),
					false // Not an error, just informational
				);
				wp_send_json_success( $test_result );
			} else {
				AIOHM_BOOKING_Error_Handler::log_error(
					'Connection test failed for ' . $this->get_provider_name(),
					'connection_test_error',
					array(
						'provider' => $this->get_provider_name(),
						'error'    => $test_result['message'],
					)
				);
				wp_send_json_error( $test_result['message'] );
			}
		} catch ( Exception $e ) {
			AIOHM_BOOKING_Error_Handler::log_error(
				'Exception during connection test: ' . $e->getMessage(),
				'exception_error',
				array(
					'provider'  => $this->get_provider_name(),
					'exception' => get_class( $e ),
					'trace'     => $e->getTraceAsString(),
				)
			);
			wp_send_json_error( 'Connection test failed due to an unexpected error' );
		}
	}

	/**
	 * Perform actual connection test (can be overridden by child classes)
	 *
	 * @return array
	 */
	protected function perform_connection_test() {
		$test_query = 'Hello, this is a connection test.';
		$result     = $this->make_api_call( $test_query );

		if ( isset( $result['error'] ) ) {
			return array(
				'success' => false,
				'message' => $result['error'],
			);
		}

		return array(
			'success' => true,
			'message' => $this->get_provider_display_name() . ' connection successful!',
		);
	}

	/**
	 * Get module settings with provider-specific key
	 *
	 * @return array
	 */
	public function get_module_settings() {
		$provider   = $this->get_provider_name();
		$option_key = "aiohm_booking_{$provider}_settings";
		return array_merge( $this->get_default_settings(), get_option( $option_key, array() ) );
	}

	/**
	 * Check if the provider is configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		$settings = $this->get_module_settings();
		$provider = $this->get_provider_name();
		$api_key  = $settings[ "{$provider}_api_key" ] ?? '';
		return ! empty( $api_key );
	}

	/**
	 * Handle settings save with common validation
	 */
	public function handle_settings_save() {
		$provider     = $this->get_provider_name();
		$nonce_key    = "{$provider}_nonce";
		$nonce_action = "aiohm_booking_{$provider}_settings";
		$settings_key = "{$provider}_settings";

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce verification happens below
		if ( isset( $_POST[ $nonce_key ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), $nonce_action ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Input sanitized in sanitize_settings method
			if ( current_user_can( 'manage_options' ) && isset( $_POST[ $settings_key ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input sanitized in sanitize_settings method
				$settings = $this->sanitize_settings( wp_unslash( $_POST[ $settings_key ] ) );

				$option_key = "aiohm_booking_{$provider}_settings";
				update_option( $option_key, $settings );

				add_action(
					'admin_notices',
					function () use ( $provider ) {
						$display_name = $this->get_provider_display_name();
						echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $display_name ) . ' settings saved successfully!</p></div>';
					}
				);
			}
		}
	}

	/**
	 * Sanitize settings (can be overridden by child classes)
	 *
	 * @param array $raw_settings Raw settings from form.
	 * @return array
	 */
	public function sanitize_settings( $raw_settings ) {
		$provider = $this->get_provider_name();
		$settings = array();

		// Common sanitization patterns.
		foreach ( $raw_settings as $key => $value ) {
			if ( strpos( $key, '_api_key' ) !== false || strpos( $key, '_token' ) !== false ) {
				$settings[ $key ] = sanitize_text_field( $value );
			} elseif ( strpos( $key, '_model' ) !== false ) {
				$settings[ $key ] = sanitize_text_field( $value );
			} elseif ( is_numeric( $value ) ) {
				$settings[ $key ] = is_float( $value + 0 ) ? floatval( $value ) : intval( $value );
			} else {
				$settings[ $key ] = sanitize_text_field( $value );
			}
		}

		return $settings;
	}

	/**
	 * Enqueue admin assets with common structure
	 */
	public function enqueue_admin_assets() {
		$provider = $this->get_provider_name();
		$screen   = get_current_screen();

		// Only load on relevant admin pages.
		if ( ! $screen || strpos( $screen->id, 'aiohm-booking-pro' ) === false ) {
			return;
		}

		// Enqueue modular AI provider assets
		$this->enqueue_ai_provider_admin_assets( $provider );
	}

	/**
	 * Enqueue AI provider admin assets
	 *
	 * @param string $provider_id Provider identifier.
	 * @param string $page Current page.
	 */
	protected function enqueue_ai_provider_admin_assets( $provider_id, $page = '' ) {
		if ( empty( $page ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin asset loading based on page parameter
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		}

		if ( 'aiohm-booking-settings' !== $page ) {
			return;
		}

		$css_file = "aiohm-booking-{$provider_id}-admin.css";
		$js_file  = "aiohm-booking-{$provider_id}-admin.js";

		// Build module path
		$module_base_dir = AIOHM_BOOKING_DIR . "includes/modules/ai/{$provider_id}/";
		$module_base_url = AIOHM_BOOKING_URL . "includes/modules/ai/{$provider_id}/";

		// Enqueue CSS if it exists
		if ( file_exists( $module_base_dir . "assets/css/{$css_file}" ) ) {
			wp_enqueue_style(
				"aiohm-booking-{$provider_id}-admin",
				$module_base_url . "assets/css/{$css_file}",
				array(),
				AIOHM_BOOKING_VERSION
			);
		}

		// Enqueue JS if it exists
		if ( file_exists( $module_base_dir . "assets/js/{$js_file}" ) ) {
			wp_enqueue_script(
				"aiohm-booking-{$provider_id}-admin",
				$module_base_url . "assets/js/{$js_file}",
				array( 'jquery' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// Localize script with provider-specific data
			wp_localize_script(
				"aiohm-booking-{$provider_id}-admin",
				"aiohm_{$provider_id}",
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( "aiohm_booking_test_{$provider_id}" ),
					'strings'  => array(
						'testing' => __( 'Testing connection...', 'aiohm-booking-pro' ),
						'success' => __( 'Connection successful!', 'aiohm-booking-pro' ),
						'error'   => __( 'Connection failed', 'aiohm-booking-pro' ),
					),
				)
			);
		}
	}

	/**
	 * Generate common admin page HTML structure
	 *
	 * @param array $additional_cards Additional cards to display.
	 * @return void
	 */
	protected function render_admin_page_structure( $additional_cards = array() ) {
		$provider      = $this->get_provider_name();
		$display_name  = $this->get_provider_display_name();
		$settings      = $this->get_module_settings();
		$is_configured = $this->is_configured();
		$fields        = $this->get_settings_fields();

		?>
		<div class="wrap aiohm-booking-admin">
			<div class="aiohm-admin-header">
				<div class="aiohm-admin-header-content">
					<div class="aiohm-admin-logo">
						<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM">
					</div>
					<div class="aiohm-admin-title">
						<h1><?php echo esc_html( $this->provider_icon . ' ' . $display_name . ' Integration' ); ?></h1>
						<p class="aiohm-admin-subtitle"><?php echo esc_html( $this->get_provider_description() ); ?></p>
					</div>
				</div>
			</div>

			<div class="aiohm-ai-provider-layout">
				<!-- Configuration Card -->
				<div class="aiohm-provider-card <?php echo esc_attr( $is_configured ? 'connected' : '' ); ?>">
					<div class="aiohm-provider-header">
						<h3 class="aiohm-provider-name">
							<div class="aiohm-provider-icon"><?php echo esc_html( $this->provider_icon ); ?></div>
							<?php echo esc_html( $display_name ); ?>
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
						<?php echo esc_html( $this->get_provider_description() ); ?>
					</div>

					<?php $this->render_settings_form( $provider, $settings, $fields ); ?>
				</div>

				<?php
				// Render additional cards.
				foreach ( $additional_cards as $card ) {
					echo wp_kses_post( $card );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings form with common structure
	 *
	 * @param string $provider Provider name.
	 * @param array  $settings Current settings.
	 * @param array  $fields Settings fields definition.
	 */
	protected function render_settings_form( $provider, $settings, $fields ) {
		?>
		<form method="post" action="">
			<?php wp_nonce_field( "aiohm_booking_{$provider}_settings", "{$provider}_nonce" ); ?>
			
			<?php foreach ( $fields as $field_id => $field ) : ?>
				<div class="aiohm-setting-row<?php echo isset( $field['class'] ) ? ' ' . esc_attr( $field['class'] ) : ''; ?>">
					<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
					
					<?php $this->render_field( $field_id, $field, $settings[ $field_id ] ?? $field['default'] ?? '', $provider ); ?>
					
					<?php if ( ! empty( $field['description'] ) ) : ?>
						<small><?php echo wp_kses_post( $field['description'] ); ?></small>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<div class="aiohm-provider-actions">
				<button type="button" 
						id="test-<?php echo esc_attr( $provider ); ?>-connection" 
						class="button button-secondary aiohm-test-connection">
					Test Connection
				</button>
				<button type="submit" class="button button-primary">
					Save Settings
				</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render individual form field
	 *
	 * @param string $field_id Field ID.
	 * @param array  $field Field definition.
	 * @param mixed  $value Current value.
	 * @param string $provider Provider name.
	 */
	protected function render_field( $field_id, $field, $value, $provider ) {
		$field_name = "{$provider}_settings[{$field_id}]";

		switch ( $field['type'] ) {
			case 'password':
				echo '<div class="aiohm-api-key-wrapper">';
				echo '<input type="password" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $field['placeholder'] ?? '' ) . '" class="aiohm-input">';
				echo '<button type="button" class="aiohm-show-hide-key" onclick="togglePasswordVisibility(\'' . esc_js( $field_id ) . '\')">';
				echo '<span class="dashicons dashicons-visibility"></span>';
				echo '</button>';
				echo '</div>';
				break;

			case 'select':
				echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" class="aiohm-select">';
				foreach ( $field['options'] as $option_value => $option_label ) {
					echo '<option value="' . esc_attr( $option_value ) . '"' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
				break;

			case 'number':
				echo '<input type="number" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '"';
				if ( isset( $field['min'] ) ) {
					echo ' min="' . esc_attr( $field['min'] ) . '"';
				}
				if ( isset( $field['max'] ) ) {
					echo ' max="' . esc_attr( $field['max'] ) . '"';
				}
				if ( isset( $field['step'] ) ) {
					echo ' step="' . esc_attr( $field['step'] ) . '"';
				}
				echo ' class="aiohm-input">';
				break;

			case 'text':
			default:
				echo '<input type="text" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $field['placeholder'] ?? '' ) . '" class="aiohm-input">';
				break;
		}
	}

	/**
	 * Get provider description (can be overridden by child classes)
	 *
	 * @return string
	 */
	protected function get_provider_description() {
		return 'AI-powered booking intelligence and analytics.';
	}

	/**
	 * Check rate limit for API calls
	 *
	 * @param int $max_requests Maximum requests allowed per hour.
	 * @return bool
	 */
	protected function check_rate_limit( $max_requests = 50 ) {
		$user_id  = get_current_user_id();
		$provider = $this->get_provider_name();
		$key      = "aiohm_booking_rate_limit_{$provider}_user_{$user_id}";

		$current_count = get_transient( $key );

		if ( false === $current_count ) {
			set_transient( $key, 1, HOUR_IN_SECONDS );
			return true;
		}

		if ( $current_count >= $max_requests ) {
			return false;
		}

		set_transient( $key, $current_count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Format error response
	 *
	 * @param string $message Error message.
	 * @return array
	 */
	protected function format_error( $message ) {
		return array( 'error' => $message );
	}

	/**
	 * Format success response
	 *
	 * @param string $response The API response.
	 * @param string $model The model used (optional).
	 * @return array
	 */
	protected function format_success( $response, $model = '' ) {
		$result = array( 'response' => $response );
		if ( ! empty( $model ) ) {
			$result['model'] = $model;
		}
		return $result;
	}

	/**
	 * Get provider capabilities/features
	 *
	 * @return array
	 */
	public function get_capabilities() {
		return array(
			'text_generation'  => true,
			'streaming'        => false,
			'function_calling' => false,
			'vision'           => false,
			'rate_limiting'    => true,
		);
	}

	/**
	 * Get module dependencies
	 *
	 * AI provider modules depend on AI Analytics being enabled
	 *
	 * @return array Array of dependency module IDs
	 */
	public function get_dependencies() {
		return array( 'ai_analytics' );
	}

	/**
	 * Check if module dependencies are met
	 *
	 * Verifies that AI Analytics module is enabled
	 *
	 * @return bool True if dependencies are met
	 */
	public function check_dependencies() {
		// Check if AI Analytics module is enabled
		return aiohm_booking_is_module_enabled( 'ai_analytics' );
	}
}
