<?php
/**
 * Abstract base class for all AIOHM Booking modules
 * Provides standardized interface and common functionality
 *
 * @package AIOHM_Booking
 * @since 1.2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for all AIOHM Booking modules
 * Provides standardized interface and common functionality
 */
abstract class AIOHM_BOOKING_Module_Abstract {

	/**
	 * Module ID
	 *
	 * @var string
	 */
	protected $module_id;

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $module_name;

	/**
	 * Module description
	 *
	 * @var string
	 */
	protected $module_description;

	/**
	 * Whether the module is premium
	 *
	 * @var bool
	 */
	protected $is_premium = false;

	/**
	 * Whether the module is enabled
	 *
	 * @var bool
	 */
	protected $is_enabled = false;

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.2.3';

	/**
	 * Whether the module has an admin page
	 *
	 * @var bool
	 */
	protected $has_admin_page = true;

	/**
	 * Module error handler instance
	 *
	 * @var AIOHM_BOOKING_Module_Error_Handler
	 */
	protected $error_handler;

	/**
	 * Returns the UI definition for the module.
	 * This method should be implemented by each module to provide its metadata.
	 *
	 * @return array{
	 *   id: string,
	 *   name: string,
	 *   description: string,
	 *   is_premium: bool,
	 *   access_level: string,
	 *   category: string,
	 *   icon: string,
	 *   features: array<string>
	 * }
	 */
	abstract public static function get_ui_definition();

	/**
	 * Constructor
	 */
	public function __construct() {
		$definition               = static::get_ui_definition();
		$this->module_id          = $definition['id'];
		$this->module_name        = $definition['name'];
		$this->module_description = $definition['description'];
		$this->is_premium         = $definition['is_premium'] ?? false;

		$this->is_enabled = $this->check_if_enabled();

		// Initialize module error handler
		$this->error_handler = new AIOHM_BOOKING_Module_Error_Handler(
			$this->module_id,
			$this->module_name
		);

		// Initialize hooks if module is enabled.
		if ( $this->is_enabled ) {
			try {
				$this->init_hooks();
			} catch ( Exception $e ) {
				$this->error_handler->handle_exception( $e, 'hook_initialization' );
			}
		}

		// Admin menu registration is now handled centrally in the main admin class.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_assets' ) );
	}

	// Abstract methods that must be implemented by each module.
	/**
	 * Initialize module hooks
	 */
	abstract protected function init_hooks();

	/**
	 * Get settings fields
	 *
	 * @return array
	 */
	abstract public function get_settings_fields();

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	abstract protected function get_default_settings();

	// Optional method that can be overridden for admin page.
	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		$this->render_default_admin_page();
	}

	// Common methods with default implementations.
	/**
	 * Get module ID
	 *
	 * @return string
	 */
	public function get_module_id() {
		return $this->module_id;
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_module_name() {
		return $this->module_name;
	}

	/**
	 * Get module description
	 *
	 * @return string
	 */
	public function get_module_description() {
		return $this->module_description;
	}

	/**
	 * Check if module is premium
	 *
	 * @return bool
	 */
	public function is_premium() {
		return $this->is_premium;
	}

	/**
	 * Check if module is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->is_enabled;
	}
	/**
	 * Set enabled state at runtime and initialize hooks if enabling
	 *
	 * @param bool $enabled The enabled state.
	 */
	public function set_enabled( $enabled ) {
		$this->is_enabled = $enabled;
		if ( $this->is_enabled ) {
			$this->init_hooks();
		}
	}

	/**
	 * Check if module has admin page
	 *
	 * @return bool
	 */
	public function has_admin_page() {
		return $this->has_admin_page;
	}

	/**
	 * Get module dependencies
	 *
	 * Override in child classes to specify dependencies
	 *
	 * @return array Array of dependency module IDs
	 */
	public function get_dependencies() {
		return array();
	}

	/**
	 * Check if module dependencies are met
	 *
	 * Override in child classes for specific dependency checks
	 *
	 * @return bool True if dependencies are met
	 */
	public function check_dependencies() {
		return true;
	}

	/**
	 * Check if module is available
	 *
	 * Override in child classes for specific availability checks
	 *
	 * @return bool True if module is available
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Check if module is enabled
	 *
	 * @return bool
	 */
	protected function check_if_enabled() {
		// All modules are now enabled by default.
		return true;
	}

	/**
	 * Admin page wrapper
	 */
	public function admin_page_wrapper() {
		echo '<div class="wrap aiohm-booking-admin aiohm-module-' . esc_attr( $this->module_id ) . '">';
		$this->render_module_header();
		$this->render_admin_page();
		$this->render_module_footer();
		echo '</div>';
	}

	/**
	 * Render module header
	 */
	protected function render_module_header() {
		?>
		<div class="aiohm-admin-header">
			<div class="aiohm-admin-header-content">
				<div class="aiohm-admin-logo">
					<img src="<?php echo esc_url( aiohm_booking_asset_url( 'images/aiohm-booking-OHM_logo-black.svg' ) ); ?>" alt="AIOHM">
				</div>
				<div class="aiohm-admin-title">
					<h1><?php echo esc_html( $this->module_name ); ?> Module</h1>
					<p class="aiohm-admin-subtitle"><?php echo esc_html( $this->module_description ); ?></p>
				</div>
			</div>
		</div>
		
		<!-- Admin Notices Container -->
		<div id="aiohm-admin-notices" class="aiohm-admin-notices-container"></div>
		<?php
	}

	/**
	 * Render module footer
	 */
	protected function render_module_footer() {
		?>
		<div class="aiohm-booking-footer">
			<p>Built with ♡ by <a href="https://www.ohm.events" target="_blank">OHM Events Agency</a> | Part of the <a href="https://www.aiohm.app" target="_blank">AIOHM Ecosystem</a></p>
		</div>
		<?php
	}

	/**
	 * Render default admin page
	 */
	protected function render_default_admin_page() {
		?>
		<div class="aiohm-booking-card">
			<h3><?php echo esc_html( $this->module_name ); ?> Settings</h3>
			<p>Configure your <?php echo esc_html( strtolower( $this->module_name ) ); ?> settings below.</p>
			
			<form method="post" action="options.php">
				<?php
				settings_fields( 'aiohm_booking_' . $this->module_id . '_settings' );
				do_settings_sections( 'aiohm_booking_' . $this->module_id . '_settings' );
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		
		<div class="aiohm-booking-card">
			<h3>Module Features</h3>
			<p><?php echo esc_html( $this->module_description ); ?></p>
			<?php $this->render_module_features(); ?>
		</div>
		<?php
	}

	/**
	 * Render module features
	 */
	protected function render_module_features() {
		echo '<p>This module provides core functionality for the AIOHM Booking system.</p>';
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		$settings_fields = $this->get_settings_fields();
		if ( empty( $settings_fields ) ) {
			return;
		}

		register_setting(
			'aiohm_booking_' . $this->module_id . '_settings',
			'aiohm_booking_' . $this->module_id . '_settings',
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'aiohm_booking_' . $this->module_id . '_main',
			$this->module_name . ' Settings',
			array( $this, 'render_settings_section_description' ),
			'aiohm_booking_' . $this->module_id . '_settings'
		);

		foreach ( $settings_fields as $field_id => $field ) {
			add_settings_field(
				$field_id,
				$field['label'],
				array( $this, 'render_settings_field' ),
				'aiohm_booking_' . $this->module_id . '_settings',
				'aiohm_booking_' . $this->module_id . '_main',
				array(
					'field_id' => $field_id,
					'field'    => $field,
				)
			);
		}
	}

	/**
	 * Render settings section description
	 */
	public function render_settings_section_description() {
		echo '<p>' . esc_html( $this->module_description ) . '</p>';
	}

	/**
	 * Render settings field
	 *
	 * @param array $args The field arguments.
	 */
	public function render_settings_field( $args ) {
		$field_id = $args['field_id'];
		$field    = $args['field'];
		$settings = $this->get_module_settings();
		$value    = $settings[ $field_id ] ?? $field['default'] ?? '';

		$field_name = 'aiohm_booking_' . $this->module_id . '_settings[' . $field_id . ']';

		// Use the field renderer factory to render the field.
		if ( class_exists( 'AIOHM_Booking_Field_Renderer_Factory' ) ) {
			echo wp_kses_post( AIOHM_Booking_Field_Renderer_Factory::render_field( $field_id, $field, $value, $field_name ) );
			return;
		}

		// Fallback to original implementation if factory is not available.
		$this->render_settings_field_fallback( $field_id, $field, $value, $field_name );
	}

	/**
	 * Fallback method for rendering settings field (original implementation)
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 */
	private function render_settings_field_fallback( $field_id, $field, $value, $field_name ) {
		switch ( $field['type'] ) {
			case 'text':
			case 'email':
			case 'url':
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
				break;

			case 'number':
				$min  = isset( $field['min'] ) ? 'min="' . esc_attr( $field['min'] ) . '"' : '';
				$max  = isset( $field['max'] ) ? 'max="' . esc_attr( $field['max'] ) . '"' : '';
				$step = isset( $field['step'] ) ? 'step="' . esc_attr( $field['step'] ) . '"' : '';
				echo '<input type="number" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" class="regular-text" ' . esc_attr( $min ) . ' ' . esc_attr( $max ) . ' ' . esc_attr( $step ) . ' />';
				break;

			case 'textarea':
				echo '<textarea id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" rows="4" cols="50" class="large-text">' . esc_textarea( $value ) . '</textarea>';
				break;

			case 'checkbox':
				$disabled = isset( $field['disabled'] ) && $field['disabled'] ? 'disabled' : '';
				echo '<input type="checkbox" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . checked( 1, $value, false ) . ' ' . esc_attr( $disabled ) . ' />';
				echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['description'] ?? '' ) . '</label>';
				break;

			case 'select':
				echo '<select id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '">';
				if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
					foreach ( $field['options'] as $option_value => $option_label ) {
						echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
					}
				}
				echo '</select>';
				break;

			case 'color':
				echo '<input type="color" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" />';
				break;

			case 'custom':
				// Handle custom field types with callback.
				if ( isset( $field['callback'] ) && is_callable( $field['callback'] ) ) {
					call_user_func( $field['callback'] );
				}
				break;
		}

		if ( isset( $field['description'] ) && 'checkbox' !== $field['type'] && 'custom' !== $field['type'] ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input The input settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$fields = $this->get_settings_fields();

		// Delegate to the Settings Manager for processing.
		if ( class_exists( 'AIOHM_Booking_Module_Settings_Manager' ) ) {
			return AIOHM_Booking_Module_Settings_Manager::sanitize_module_settings( $input, $fields );
		}

		// Fallback to simplified legacy processing.
		return $this->sanitize_settings_fallback( $input, $fields );
	}

	/**
	 * Simplified fallback sanitization when Settings Manager is not available
	 *
	 * @param array $input  The input settings.
	 * @param array $fields The field definitions.
	 * @return array The sanitized settings.
	 */
	private function sanitize_settings_fallback( $input, $fields ) {
		$sanitized = array();

		foreach ( $fields as $field_id => $field ) {
			if ( isset( $input[ $field_id ] ) ) {
				$sanitized[ $field_id ] = $this->sanitize_field_fallback( $input[ $field_id ], $field );
				continue;
			}

			$sanitized[ $field_id ] = ( 'checkbox' === ( $field['type'] ?? '' ) ) ? 0 : ( $field['default'] ?? '' );
		}

		return $sanitized;
	}

	/**
	 * Fallback method for sanitizing individual field values
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	private function sanitize_field_fallback( $value, $field ) {
		// Skip custom fields as they handle their own data.
		if ( 'custom' === $field['type'] ) {
			return $value;
		}

		switch ( $field['type'] ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'number':
				return is_numeric( $value ) ? floatval( $value ) : 0;

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'password':
				// Don't sanitize passwords, just ensure they're strings.
				return is_string( $value ) ? $value : '';

			default:
				$sanitize_callback = $field['sanitize_callback'] ?? 'sanitize_text_field';
				if ( is_callable( $sanitize_callback ) ) {
					return call_user_func( $sanitize_callback, $value );
				}

				return sanitize_text_field( $value );
		}
	}

	/**
	 * Admin enqueue assets
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function admin_enqueue_assets( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'aiohm-booking-' . str_replace( '_', '-', $this->module_id ) ) !== false ) {
			$this->enqueue_admin_assets();
		}
	}

	/**
	 * Enqueue admin assets
	 */
	protected function enqueue_admin_assets() {
		// Module-specific CSS files are no longer needed as styles are included in the unified CSS.
		// Only load JavaScript files for module functionality.
		$js_file = 'js/aiohm-booking-' . str_replace( '_', '-', $this->module_id ) . '-admin.js';

		if ( file_exists( AIOHM_BOOKING_DIR . 'assets/' . $js_file ) ) {
			wp_enqueue_script(
				'aiohm-booking-' . str_replace( '_', '-', $this->module_id ) . '-admin',
				aiohm_booking_asset_url( $js_file ),
				array( 'aiohm-booking-admin', 'jquery' ),
				$this->version,
				true
			);

			// Pass module data to JavaScript.
			wp_localize_script(
				'aiohm-booking-' . str_replace( '_', '-', $this->module_id ) . '-admin',
				'aiohm_booking_' . $this->module_id . '_admin',
				array(
					'module_id'   => $this->module_id,
					'module_name' => $this->module_name,
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'aiohm_booking_' . $this->module_id . '_nonce' ),
				)
			);
		}
	}

	/**
	 * Frontend enqueue assets.
	 */
	public function frontend_enqueue_assets() {
		if ( ! $this->is_enabled ) {
			return;
		}

		// Frontend styles are included in the main frontend CSS file.
		// Only load JavaScript files for frontend functionality.
		$js_file = 'js/aiohm-booking-' . str_replace( '_', '-', $this->module_id ) . '.js';

		if ( file_exists( AIOHM_BOOKING_DIR . 'assets/' . $js_file ) ) {
			wp_enqueue_script(
				'aiohm-booking-' . str_replace( '_', '-', $this->module_id ),
				aiohm_booking_asset_url( $js_file ),
				array( 'aiohm-booking-frontend', 'jquery' ),
				$this->version,
				true
			);

			// Pass module data to JavaScript.
			wp_localize_script(
				'aiohm-booking-' . str_replace( '_', '-', $this->module_id ),
				'aiohm_booking_' . $this->module_id,
				array(
					'module_id' => $this->module_id,
					'ajax_url'  => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'aiohm_booking_' . $this->module_id . '_nonce' ),
					'rest_url'  => rest_url( 'aiohm-booking/v1/' ),
				)
			);
		}
	}

	// Settings helper methods.
	/**
	 * Get module settings.
	 *
	 * @return array
	 */
	public function get_module_settings() {
		$defaults       = $this->get_default_settings();
		$saved_settings = get_option( 'aiohm_booking_' . $this->module_id . '_settings', array() );
		return array_merge( $defaults, $saved_settings );
	}

	/**
	 * Update module settings.
	 *
	 * @param array $settings The settings to update.
	 * @return bool
	 */
	protected function update_module_settings( $settings ) {
		return update_option( 'aiohm_booking_' . $this->module_id . '_settings', $settings );
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key           The setting key.
	 * @param mixed  $default_value The default value.
	 * @return mixed
	 */
	protected function get_setting( $key, $default_value = '' ) {
		$settings = $this->get_module_settings();
		return $settings[ $key ] ?? $default_value;
	}

	// Premium check helper.
	/**
	 * Check premium access.
	 */
	protected function check_premium_access() {
		if ( $this->is_premium && ! aiohm_booking_is_premium() ) {
			return false;
		}
		return true;
	}

	/**
	 * Render premium upgrade banner.
	 */
	protected function render_premium_upgrade_banner() {
		if ( ! $this->is_premium || aiohm_booking_is_premium() ) {
			return;
		}

		?>
		<div class="aiohm-premium-upgrade-banner">
			<div class="aiohm-premium-content">
				<div class="aiohm-premium-icon"></div>
				<div class="aiohm-premium-text">
					<h4>Premium Feature</h4>
					<p><?php echo esc_html( $this->module_name ); ?> module requires the PRO version. Upgrade to unlock all features!</p>
					<a href="<?php echo esc_url( aiohm_booking_get_upgrade_url() ); ?>" class="aiohm-premium-button">Upgrade to PRO</a>
				</div>
			</div>
			<div class="aiohm-premium-note">
				<span class="aiohm-note-icon">💡</span>
				<p>Professional features for serious booking businesses</p>
			</div>
		</div>
		<?php
	}


	/**
	 * Render extra content within the module card on the admin settings page.
	 * Modules can override this to display special information.
	 */
	public function render_admin_card_extras() {
		// By default, do nothing.
	}

	/**
	 * Get module error handler
	 *
	 * @return AIOHM_BOOKING_Module_Error_Handler
	 */
	public function get_error_handler() {
		return $this->error_handler;
	}

	/**
	 * Log module error
	 *
	 * @param string $message Error message.
	 * @param string $type Error type.
	 * @param array  $context Additional context.
	 */
	protected function log_error( $message, $type = 'module_error', $context = array() ) {
		$this->error_handler->log_error( $message, $type, $context );
	}

	/**
	 * Log module warning
	 *
	 * @param string $message Warning message.
	 * @param array  $context Additional context.
	 */
	protected function log_warning( $message, $context = array() ) {
		$this->error_handler->log_warning( $message, $context );
	}

	/**
	 * Handle module exception
	 *
	 * @param Exception $exception The exception.
	 * @param string    $operation Operation being performed.
	 */
	protected function handle_exception( $exception, $operation = 'operation' ) {
		$this->error_handler->handle_exception( $exception, $operation );
	}

	/**
	 * Validate module settings
	 *
	 * @param array $settings Settings to validate.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	protected function validate_settings( $settings ) {
		try {
			$fields = $this->get_settings_fields();
			$errors = array();

			foreach ( $fields as $field_id => $field ) {
				if ( isset( $field['required'] ) && $field['required'] ) {
					if ( empty( $settings[ $field_id ] ) ) {
						$errors[ $field_id ] = sprintf(
							/* translators: %s: name of the required field */
							__( '%s is required', 'aiohm-booking-pro' ),
							$field['label'] ?? $field_id
						);
					}
				}

				// Type validation
				if ( isset( $field['type'] ) && isset( $settings[ $field_id ] ) ) {
					$value = $settings[ $field_id ];
					switch ( $field['type'] ) {
						case 'email':
							if ( ! is_email( $value ) ) {
								$errors[ $field_id ] = __( 'Invalid email address', 'aiohm-booking-pro' );
							}
							break;
						case 'number':
							if ( ! is_numeric( $value ) ) {
								$errors[ $field_id ] = __( 'Must be a number', 'aiohm-booking-pro' );
							}
							break;
					}
				}
			}

			if ( ! empty( $errors ) ) {
				$this->log_error(
					'Settings validation failed',
					'validation_error',
					array( 'validation_errors' => $errors )
				);
				return new WP_Error( 'validation_failed', __( 'Settings validation failed', 'aiohm-booking-pro' ), $errors );
			}

			return true;

		} catch ( Exception $e ) {
			$this->handle_exception( $e, 'settings_validation' );
			return new WP_Error( 'validation_exception', __( 'Error during validation', 'aiohm-booking-pro' ) );
		}
	}
}

