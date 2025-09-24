<?php

namespace AIOHM_Booking_PRO\Modules\Admin;
/**
 * Shortcode Module for AIOHM Booking
 * Handles all booking shortcodes and frontend display functionality
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode Admin Module Class
 *
 * @package AIOHM_Booking
 */
class AIOHM_BOOKING_Module_Shortcode_Admin extends \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Module ID.
	 *
	 * @var string
	 */
	protected $module_id = 'shortcode';

	/**
	 * Whether the module has an admin page.
	 *
	 * @var bool
	 */
	protected $has_admin_page = false;

	/**
	 * Get UI definition for the module.
	 *
	 * @return array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'shortcode',
			'name'                => __( 'Shortcodes', 'aiohm-booking-pro' ),
			'description'         => __( 'Flexible shortcode system for displaying booking forms and accommodation interfaces anywhere on your site.', 'aiohm-booking-pro' ),
			'icon'                => '📝',
			'category'            => 'admin',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 20,
			'has_settings'        => true,
			'has_admin_page'      => false,
			'settings_section'    => true,
			'visible_in_settings' => true,
		);
	}

	/**
	 * Render card content for the module.
	 */
	public function render_card_content() {
		?>
		<div class="shortcode-usage" style="margin-top:10px;padding:10px;border:1px dashed #e6f2eb;border-radius:6px;background:#fbfffb;">
			<strong><?php esc_html_e( 'Available Shortcodes', 'aiohm-booking-pro' ); ?>:</strong>
			<p style="margin:6px 0 12px 0;"><?php esc_html_e( 'Use these shortcodes in posts, pages or custom templates to embed booking interfaces. Click to see attributes.', 'aiohm-booking-pro' ); ?></p>
			
			<?php
			wp_enqueue_style(
				'aiohm-booking-shortcodes',
				AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-shortcodes.css',
				array(),
				AIOHM_BOOKING_VERSION
			);
			?>

			<details class="shortcode-details">
				<summary>
					<span>[aiohm_booking]</span>
					<button type="button" class="copy-shortcode-btn" title="Copy to clipboard"><?php esc_html_e( 'Copy', 'aiohm-booking-pro' ); ?></button>
				</summary>
				<div class="shortcode-details-content">
					<p><?php esc_html_e( 'Displays the main booking form with intelligent tab-based navigation. Shows events and/or accommodations based on enabled modules in a unified 3-step process: 1) Selection, 2) Contact Info, 3) Checkout. If both modules are enabled, both appear in Step 1. If only one is enabled, shows only that module and may skip Step 1 entirely.', 'aiohm-booking-pro' ); ?></p>
					<ul>
						<li><code>mode</code>: <?php esc_html_e( 'Set display mode. Accepts:', 'aiohm-booking-pro' ); ?> <code>accommodations</code>, <code>tickets</code>, <code>both</code>, <code>auto</code> (<?php esc_html_e( 'default', 'aiohm-booking-pro' ); ?>).</li>
						<li><code>theme</code>: <?php esc_html_e( 'Visual theme. Accepts:', 'aiohm-booking-pro' ); ?> <code>default</code>, <code>minimal</code>, <code>modern</code>, <code>classic</code>.</li>
						<li><code>event_id</code>: <?php esc_html_e( 'For ticket mode, specify an event ID to book for.', 'aiohm-booking-pro' ); ?></li>
						<li><code>show_title</code>: <?php esc_html_e( 'Show the form title. Accepts:', 'aiohm-booking-pro' ); ?> <code>true</code> (<?php esc_html_e( 'default', 'aiohm-booking-pro' ); ?>), <code>false</code>.</li>
						<li><code>enable_accommodations</code>: <?php esc_html_e( 'Override accommodations module. Accepts:', 'aiohm-booking-pro' ); ?> <code>true</code>, <code>false</code>.</li>
						<li><code>enable_tickets</code>: <?php esc_html_e( 'Override tickets module. Accepts:', 'aiohm-booking-pro' ); ?> <code>true</code>, <code>false</code>.</li>
					</ul>
				</div>
			</details>


			<?php
			wp_enqueue_script(
				'aiohm-booking-shortcode-copy',
				AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-shortcode-copy.js',
				array(),
				AIOHM_BOOKING_VERSION,
				true
			);
			wp_localize_script(
				'aiohm-booking-shortcode-copy',
				'aiohm_shortcode_admin',
				array(
					'copied_text' => __( 'Copied!', 'aiohm-booking-pro' ),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Initialize hooks for the module.
	 */
	protected function init_hooks() {
		// Register shortcodes - this admin module now handles both admin UI and shortcode registration.
		$this->register_shortcodes();

		// Frontend asset loading.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_shortcode_assets' ) );

		// Shortcode detection for conditional asset loading.
		add_filter( 'the_content', array( $this, 'detect_shortcodes' ), 1 );

		// Add shortcode button to editor.
		add_action( 'media_buttons', array( $this, 'add_shortcode_button' ) );
		add_action( 'admin_footer', array( $this, 'shortcode_modal' ) );

		// Add custom CSS badge if CSS is present.
		add_filter( 'aiohm_booking_module_card_badges', array( $this, 'add_custom_css_badge' ), 10, 3 );

		// Add preview endpoint for iframe
		add_action( 'wp_ajax_aiohm_booking_preview', array( $this, 'preview_endpoint' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_preview', array( $this, 'preview_endpoint' ) );

		// Add hooks for test page creation
		add_action( 'wp_ajax_aiohm_create_test_page', array( $this, 'create_test_page' ) );
		add_action( 'wp_ajax_nopriv_aiohm_create_test_page', array( $this, 'create_test_page' ) );

		// Add hook to modify preview pages
		add_action( 'wp', array( $this, 'handle_preview_page' ) );

		// Frontend calendar data is handled by the calendar module to avoid conflicts.

		// AJAX handler for individual unit status.
		add_action( 'wp_ajax_aiohm_get_unit_status', array( $this, 'ajax_get_unit_status' ) );
		add_action( 'wp_ajax_nopriv_aiohm_get_unit_status', array( $this, 'ajax_get_unit_status' ) );

		// AJAX handler for multiple unit statuses.
		add_action( 'wp_ajax_aiohm_get_multiple_unit_statuses', array( $this, 'ajax_get_multiple_unit_statuses' ) );
		add_action( 'wp_ajax_nopriv_aiohm_get_multiple_unit_statuses', array( $this, 'ajax_get_multiple_unit_statuses' ) );

		// AJAX handler for accommodation booking form submission.
		if ( method_exists( $this, 'ajax_process_accommodation_booking' ) ) {
			add_action( 'wp_ajax_aiohm_booking_submit_accommodation', array( $this, 'ajax_process_accommodation_booking' ) );
			add_action( 'wp_ajax_nopriv_aiohm_booking_submit_accommodation', array( $this, 'ajax_process_accommodation_booking' ) );
		}

		// AJAX handler for unified booking form submission (accommodations + events).
		if ( method_exists( $this, 'ajax_process_unified_booking' ) ) {
			add_action( 'wp_ajax_aiohm_booking_submit_unified', array( $this, 'ajax_process_unified_booking' ) );
			add_action( 'wp_ajax_nopriv_aiohm_booking_submit_unified', array( $this, 'ajax_process_unified_booking' ) );
		}

		// TEMPORARY: Add handler for old action to catch any legacy requests
		add_action( 'wp_ajax_aiohm_booking_submit', array( $this, 'ajax_legacy_booking_handler' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_submit', array( $this, 'ajax_legacy_booking_handler' ) );

		// AJAX handler for checkout completion.
		add_action( 'wp_ajax_aiohm_booking_complete_checkout', array( $this, 'ajax_complete_checkout' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_complete_checkout', array( $this, 'ajax_complete_checkout' ) );

		// AJAX handler for getting checkout HTML with booking ID.
		add_action( 'wp_ajax_aiohm_booking_get_checkout_html', array( $this, 'ajax_get_checkout_html' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_get_checkout_html', array( $this, 'ajax_get_checkout_html' ) );

		// AJAX handler for updating accommodation selection based on dates.
		add_action( 'wp_ajax_aiohm_booking_update_accommodation_selection', array( $this, 'ajax_update_accommodation_selection' ) );
		add_action( 'wp_ajax_nopriv_aiohm_booking_update_accommodation_selection', array( $this, 'ajax_update_accommodation_selection' ) );
	}

	/**
	 * Register all booking shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'aiohm_booking', array( $this, 'booking_form_shortcode' ) );
		add_shortcode( 'aiohm_booking_success', array( $this, 'success_shortcode' ) );
	}

	/**
	 * Register placeholder shortcodes when module is disabled
	 */
	public function register_placeholder_shortcodes() {
		add_shortcode( 'aiohm_booking', array( $this, 'placeholder_shortcode_handler' ) );
		add_shortcode( 'aiohm_booking_success', array( $this, 'placeholder_shortcode_handler' ) );
	}

	/**
	 * Placeholder shortcode handler for disabled state
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function placeholder_shortcode_handler( $atts, $content = null ) {
		if ( is_admin() ) {
			return '<div class="aiohm-shortcode-disabled">' .
					__( 'AIOHM Booking shortcodes are currently disabled. Enable the Shortcode module in plugin settings.', 'aiohm-booking-pro' ) .
					'</div>';
		}
		return '';
	}

	/**
	 * Main booking form shortcode handler
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function booking_form_shortcode( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'mode'                  => 'auto',
				'theme'                 => 'default',
				'title'                 => '',
				'event_id'              => '',
				'show_title'            => 'true',
				'style'                 => 'modern', // Additional style attribute.
				'enable_accommodations' => null, // Override accommodations module.
				'enable_tickets'        => null, // Override tickets module.
			),
			$atts,
			'aiohm_booking'
		);

		// Handle legacy 'type' parameter.
		if ( isset( $atts['type'] ) ) {
			$atts['mode'] = $atts['type'];
		}

		// Set module overrides if explicitly provided in shortcode.
		if ( ! is_null( $atts['enable_accommodations'] ) || ! is_null( $atts['enable_tickets'] ) ) {
			$GLOBALS['aiohm_booking_shortcode_override'] = array(
				'enable_accommodations' => ! is_null( $atts['enable_accommodations'] ) ? ( 'true' === $atts['enable_accommodations'] || '1' === $atts['enable_accommodations'] ) : aiohm_booking_is_module_enabled( 'accommodations' ),
				'enable_tickets'        => ! is_null( $atts['enable_tickets'] ) ? ( 'true' === $atts['enable_tickets'] || '1' === $atts['enable_tickets'] ) : aiohm_booking_is_module_enabled( 'tickets' ),
			);

			// Shortcode override successfully applied.
		}

		// Enqueue frontend assets.
		$this->enqueue_shortcode_assets();

		// Start output buffering.
		ob_start();

		// Set shortcode context.
		global $aiohm_booking_shortcode_context;
		$aiohm_booking_shortcode_context = $atts;

		// Load the shortcode template.
		$template_path = AIOHM_BOOKING_DIR . 'templates/aiohm-booking-shortcode.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		} else {
			echo '<div class="aiohm-booking-error">' .
				esc_html__( 'Booking form template not found. Please check plugin installation.', 'aiohm-booking-pro' ) .
				'</div>';
		}

		// Clean up module overrides after rendering.
		if ( isset( $GLOBALS['aiohm_booking_shortcode_override'] ) ) {
			unset( $GLOBALS['aiohm_booking_shortcode_override'] );
		}

		return ob_get_clean();
	}




	/**
	 * Force display calendar shortcode - with aggressive CSS overrides
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function calendar_force_shortcode( $atts, $content = null ) {
		// Get the regular calendar output.
		$calendar_output = $this->calendar_shortcode( $atts, $content );

		// If there's an error message, return it.
		if ( strpos( $calendar_output, 'aiohm-booking-notice' ) !== false || strpos( $calendar_output, 'aiohm-booking-error' ) !== false ) {
			return $calendar_output;
		}

		// Get calendar colors from settings.
		$default_colors  = array(
			'free'     => '#ffffff',
			'booked'   => '#e74c3c',
			'pending'  => '#f39c12',
			'external' => '#8e44ad',
			'blocked'  => '#2c3e50',
			'special'  => '#3498db',
			'private'  => '#457d59',
		);
		$saved_colors    = get_option( 'aiohm_booking_calendar_colors', array() );
		$calendar_colors = wp_parse_args( $saved_colors, $default_colors );

		// Get brand color from accommodation admin settings.
		$main_settings = get_option( 'aiohm_booking_settings', array() );
		$brand_color   = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? '#457d59';

		// Wrap the calendar with aggressive CSS overrides.
		$forced_output = '<div class="aiohm-calendar-force-display">';

		// Enqueue consolidated shortcodes CSS for dynamic calendar styling.
		wp_enqueue_style(
			'aiohm-booking-shortcodes',
			AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-shortcodes.css',
			array(),
			AIOHM_BOOKING_VERSION
		);

		// Add dynamic color values via CSS custom properties.
		$dynamic_css = '
:root {
    --aiohm-calendar-free-color: ' . esc_attr( $calendar_colors['free'] ) . ';
    --aiohm-calendar-booked-color: ' . esc_attr( $calendar_colors['booked'] ) . ';
    --aiohm-calendar-pending-color: ' . esc_attr( $calendar_colors['pending'] ) . ';
    --aiohm-calendar-external-color: ' . esc_attr( $calendar_colors['external'] ) . ';
    --aiohm-calendar-blocked-color: ' . esc_attr( $calendar_colors['blocked'] ) . ';
    --aiohm-calendar-special-color: ' . esc_attr( $calendar_colors['special'] ) . ';
    --aiohm-calendar-private-color: ' . esc_attr( $calendar_colors['private'] ) . ';
    --aiohm-brand-color: ' . esc_attr( $brand_color ) . ';
}';

		wp_add_inline_style( 'aiohm-booking-shortcode-admin-dynamic', $dynamic_css );

		$forced_output .= $calendar_output;
		$forced_output .= '</div>';

		// Enqueue calendar force display JS.
		wp_enqueue_script(
			'aiohm-booking-calendar-force-display',
			AIOHM_BOOKING_URL . 'assets/js/calendar-force-display.js',
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		return $forced_output;
	}


	/**
	 * Booking success page shortcode handler
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function success_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'show_summary' => 'true',
				'show_steps'   => 'true',
			),
			$atts,
			'aiohm_booking_success'
		);

		$this->enqueue_shortcode_assets();

		ob_start();

		// Load success template.
		$this->load_template(
			'aiohm-booking-success.php',
			array(
				'settings' => $this->get_settings(),
				'atts'     => $atts,
			)
		);

		return ob_get_clean();
	}

	/**
	 * Get settings fields for the module.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'shortcode_custom_css'     => array(
				'type'        => 'textarea',
				'label'       => __( 'Custom CSS for Booking Forms', 'aiohm-booking-pro' ),
				'description' => __( 'Apply custom styles to all booking forms. Note: Primary colors and fonts are managed in the <strong>Accommodation</strong> module settings on this page. Use this for specific overrides.', 'aiohm-booking-pro' ),
				'default'     => '',
				'rows'        => 12,
				'placeholder' => '/* ' . __( 'Example: Change the submit button style', 'aiohm-booking-pro' ) . " */\n.aiohm-booking-form .submit-button {\n    background-color: #ff6b6b;\n    border-radius: 25px;\n    font-weight: bold;\n}\n\n/* " . __( 'Example: Adjust form input padding', 'aiohm-booking-pro' ) . " */\n.aiohm-booking-form input[type=\"text\"] {\n    padding: 12px;\n}",
			),
			'shortcode_cache_enabled'  => array(
				'type'        => 'checkbox',
				'label'       => __( 'Enable Shortcode Caching', 'aiohm-booking-pro' ),
				'description' => __( 'Cache shortcode output for better performance. Recommended for high-traffic sites.', 'aiohm-booking-pro' ),
				'default'     => 1,
			),
			'shortcode_cache_duration' => array(
				'type'        => 'select',
				'label'       => __( 'Cache Duration', 'aiohm-booking-pro' ),
				'description' => __( 'How long to cache shortcode output.', 'aiohm-booking-pro' ),
				'default'     => '6',
				'options'     => array(
					'1'  => __( '1 hour', 'aiohm-booking-pro' ),
					'6'  => __( '6 hours', 'aiohm-booking-pro' ),
					'12' => __( '12 hours', 'aiohm-booking-pro' ),
					'24' => __( '24 hours', 'aiohm-booking-pro' ),
				),
			),
		);
	}

	/**
	 * Add a "Custom CSS" badge to the module card if custom CSS is in use.
	 *
	 * @param array  $badges      Existing badges.
	 * @param string $module_id   The ID of the module being rendered.
	 * @param array  $module_info The UI definition of the module.
	 * @return array Modified badges array.
	 */
	public function add_custom_css_badge( $badges, $module_id, $module_info ) {
		if ( 'shortcode' === $module_id && $this->is_enabled() ) {
			$settings = $this->get_module_settings();
			if ( ! empty( trim( $settings['shortcode_custom_css'] ) ) ) {
				$badges[] = array(
					'text'  => __( 'Custom CSS', 'aiohm-booking-pro' ),
					'icon'  => '🎨',
					'class' => 'feature-badge',
				);
			}
		}
		return $badges;
	}

	/**
	 * Get default settings for the shortcode module.
	 *
	 * @return array Default settings array.
	 */
	protected function get_default_settings() {
		return array(
			'shortcode_custom_css'     => '',
			'shortcode_cache_enabled'  => 1,
			'shortcode_cache_duration' => '6',
		);
	}

	/**
	 * Render the settings content for the shortcode module.
	 */
	protected function render_settings_content() {
		?>
		
		<!-- Shortcode Generator -->
		<div class="aiohm-booking-card">
			<div class="aiohm-card-header">
				<h3><?php esc_html_e( 'Shortcode Generator', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-header-actions">
					<button type="button" id="copy-shortcode" class="button button-secondary">
						<?php esc_html_e( 'Copy Shortcode', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
			</div>
			
			<div class="aiohm-shortcode-generator">
				<div class="generator-tabs">
					<button class="generator-tab active" data-tab="booking-form"><?php esc_html_e( 'Booking Form', 'aiohm-booking-pro' ); ?></button>
				</div>
				
				<div class="generator-content">
					<!-- Booking Form Tab -->
					<div class="tab-content active" id="booking-form-tab">
						<h4><?php esc_html_e( 'Booking Form Shortcode', 'aiohm-booking-pro' ); ?></h4>
						<div class="shortcode-options">
							<div class="option-group">
								<label><?php esc_html_e( 'Display Mode:', 'aiohm-booking-pro' ); ?></label>
								<select id="booking-mode">
									<option value="auto"><?php esc_html_e( 'Auto (based on settings)', 'aiohm-booking-pro' ); ?></option>
									<option value="accommodations"><?php esc_html_e( 'Accommodations Only', 'aiohm-booking-pro' ); ?></option>
									<option value="tickets"><?php esc_html_e( 'Tickets Only', 'aiohm-booking-pro' ); ?></option>
									<option value="both"><?php esc_html_e( 'Both', 'aiohm-booking-pro' ); ?></option>
								</select>
							</div>
							<div class="option-group">
								<label><?php esc_html_e( 'Theme:', 'aiohm-booking-pro' ); ?></label>
								<select id="booking-theme">
									<option value="default"><?php esc_html_e( 'Default', 'aiohm-booking-pro' ); ?></option>
									<option value="minimal"><?php esc_html_e( 'Minimal', 'aiohm-booking-pro' ); ?></option>
									<option value="modern"><?php esc_html_e( 'Modern', 'aiohm-booking-pro' ); ?></option>
									<option value="classic"><?php esc_html_e( 'Classic', 'aiohm-booking-pro' ); ?></option>
								</select>
							</div>
							<div class="option-group">
								<label><?php esc_html_e( 'Show Title:', 'aiohm-booking-pro' ); ?></label>
								<input type="checkbox" id="booking-show-title" checked>
							</div>
						</div>
						<div class="generated-shortcode">
							<code id="booking-form-shortcode">[aiohm_booking]</code>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Available Shortcodes -->
		<div class="aiohm-booking-card">
			<h3><?php esc_html_e( 'Available Shortcodes', 'aiohm-booking-pro' ); ?></h3>
			<div class="aiohm-shortcodes-list">
				<div class="shortcode-item">
					<div class="shortcode-header">
						<h4>[aiohm_booking]</h4>
						<span class="shortcode-badge free"><?php esc_html_e( 'Free', 'aiohm-booking-pro' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Main booking form with auto-adapting display based on enabled modules', 'aiohm-booking-pro' ); ?></p>
					<div class="shortcode-attributes">
						<strong><?php esc_html_e( 'Attributes:', 'aiohm-booking-pro' ); ?></strong>
						<ul>
							<li><code>mode</code> - <?php esc_html_e( 'Display mode (auto, accommodations, tickets, both)', 'aiohm-booking-pro' ); ?></li>
							<li><code>theme</code> - <?php esc_html_e( 'Visual theme (default, minimal, modern, classic)', 'aiohm-booking-pro' ); ?></li>
							<li><code>title</code> - <?php esc_html_e( 'Custom form title', 'aiohm-booking-pro' ); ?></li>
							<li><code>event_id</code> - <?php esc_html_e( 'Specific event ID', 'aiohm-booking-pro' ); ?></li>
						</ul>
					</div>
				</div>
				
				<div class="shortcode-item">
					<div class="shortcode-header">
						<h4>[aiohm_booking_success]</h4>
						<span class="shortcode-badge free"><?php esc_html_e( 'Free', 'aiohm-booking-pro' ); ?></span>
					</div>
					<p><?php esc_html_e( 'Success page displayed after booking completion', 'aiohm-booking-pro' ); ?></p>
					<div class="shortcode-attributes">
						<strong><?php esc_html_e( 'Attributes:', 'aiohm-booking-pro' ); ?></strong>
						<ul>
							<li><code>theme</code> - <?php esc_html_e( 'Visual theme (default, minimal, modern, classic)', 'aiohm-booking-pro' ); ?></li>
							<li><code>show_details</code> - <?php esc_html_e( 'Show booking details (true/false)', 'aiohm-booking-pro' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<?php
		wp_enqueue_style(
			'aiohm-booking-shortcodes',
			AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-shortcodes.css',
			array(),
			AIOHM_BOOKING_VERSION
		);
		?>
		
		<?php
		wp_enqueue_script(
			'aiohm-booking-shortcode-generator',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-shortcode-generator.js',
			array(),
			AIOHM_BOOKING_VERSION,
			true
		);
		?>
		
		<?php
	}

	/**
	 * Render the module features list.
	 */
	protected function render_module_features() {
		?>
		<ul>
			<li><strong><?php esc_html_e( 'Main Shortcode:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( '[aiohm_booking] - Auto-adapting booking form', 'aiohm-booking-pro' ); ?></li>
			<li><strong><?php esc_html_e( 'Success Page:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( '[aiohm_booking_success] - Booking confirmation display', 'aiohm-booking-pro' ); ?></li>
			<li><strong><?php esc_html_e( 'Visual Generator:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( 'Easy-to-use shortcode generator', 'aiohm-booking-pro' ); ?></li>
			<li><strong><?php esc_html_e( 'Flexible Attributes:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( 'Extensive customization options for each shortcode', 'aiohm-booking-pro' ); ?></li>
			<li><strong><?php esc_html_e( 'Auto Asset Loading:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( 'Automatically load required CSS/JS files', 'aiohm-booking-pro' ); ?></li>
			<li><strong><?php esc_html_e( 'Responsive Design:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( 'Mobile-friendly shortcode output', 'aiohm-booking-pro' ); ?></li>
			<li><strong><?php esc_html_e( 'Caching Support:', 'aiohm-booking-pro' ); ?></strong> <?php esc_html_e( 'Optional caching for improved performance', 'aiohm-booking-pro' ); ?></li>
		</ul>
		<?php
	}

	/**
	 * Enqueue shortcode assets.
	 */
	public function enqueue_shortcode_assets() {
		// Check if we're in admin context.
		$is_admin_context = is_admin() || wp_doing_ajax() || ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		// Check if shortcodes are present.
		$should_enqueue = $is_admin_context;

		if ( ! $is_admin_context ) {
			$should_enqueue = true; // Always enqueue on frontend.
		}

		if ( ! $is_admin_context ) {
			global $post;
			$content = '';

			// Check post content for shortcodes.
			if ( $post && isset( $post->post_content ) ) {
				$content = $post->post_content;
				if ( $this->has_shortcodes( $content ) ) {
					$should_enqueue = true;
				}
			}

			// Additional fallback: check if we're on a page that might have the booking shortcode.
			if ( ! $should_enqueue ) {
				// Check if the page content contains our shortcode classes.
				if ( $post && isset( $post->post_content ) ) {
					if ( strpos( $post->post_content, 'aiohm-booking-modern' ) !== false ||
						strpos( $post->post_content, 'aiohm-booking-widget' ) !== false ||
						strpos( $post->post_content, 'pricing-section' ) !== false ) {
						$should_enqueue = true;
					}
				}
			}
		}

		// Enqueue assets if needed.
		if ( $should_enqueue ) {
			// Use the consolidated shortcodes CSS file for unified design.
			wp_enqueue_style( 'aiohm-booking-shortcodes', AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-shortcodes.css', array(), AIOHM_BOOKING_VERSION );

			// Check if events shortcode is present and enqueue events-specific CSS.
			$should_load_events = false;

			// Load events assets on frontend if shortcode is present
			if ( ! $is_admin_context && $post && isset( $post->post_content ) && has_shortcode( $post->post_content, 'aiohm_booking' ) ) {
				$should_load_events = true;
			}

			// Also load events assets in admin context for preview mode
			if ( $is_admin_context && isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) {
				$should_load_events = true;
			}

			if ( $should_load_events ) {
				wp_enqueue_script( 'aiohm-booking-events', AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-events.js', array( 'jquery', 'aiohm-booking-shortcodes' ), AIOHM_BOOKING_VERSION, true );
			}

			// Check if checkout shortcode is present and add checkout-specific localization.
			if ( ! $is_admin_context && $post && isset( $post->post_content ) && has_shortcode( $post->post_content, 'aiohm_booking_checkout' ) ) {
				// Get redirect URL from checkout shortcode context
				global $aiohm_booking_checkout_context;
				$redirect_url = '';
				if ( isset( $aiohm_booking_checkout_context['redirect_url'] ) ) {
					$redirect_url = esc_url_raw( $aiohm_booking_checkout_context['redirect_url'] );
				}

				// Localize checkout script with additional data.
				wp_localize_script(
					'aiohm-booking-checkout',
					'aiohm_booking_checkout',
					array(
						'ajax_url'     => admin_url( 'admin-ajax.php' ),
						'nonce'        => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
						'booking_id'   => isset( $_GET['booking_id'] ) ? sanitize_text_field( wp_unslash( $_GET['booking_id'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public frontend parameter for booking display
						'redirect_url' => $redirect_url,
						'i18n'         => array(
							'select_payment_method'  => __( 'Please select a payment method.', 'aiohm-booking-pro' ),
							'invalid_booking_id'     => __( 'Invalid booking ID.', 'aiohm-booking-pro' ),
							'processing'             => __( 'Processing...', 'aiohm-booking-pro' ),
							'complete_booking'       => __( 'Complete Booking', 'aiohm-booking-pro' ),
							'error_occurred'         => __( 'An error occurred. Please try again.', 'aiohm-booking-pro' ),
							'payment_failed'         => __( 'Payment failed. Please try again.', 'aiohm-booking-pro' ),
							'unknown_payment_method' => __( 'Unknown payment method selected.', 'aiohm-booking-pro' ),
						),
					)
				);
			}

			// Success page styling is included in consolidated CSS file - no additional enqueue needed

			wp_enqueue_script( 'aiohm-booking-shortcode', AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-shortcode.js', array( 'jquery' ), AIOHM_BOOKING_VERSION, true );

			// Only enqueue checkout script on frontend pages (not admin pages)
			if ( ! is_admin() && ! $is_admin_context ) {
				wp_enqueue_script( 'aiohm-booking-checkout', AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-checkout.js', array( 'jquery', 'aiohm-booking-shortcode' ), AIOHM_BOOKING_VERSION, true );
			}

			// Get calendar colors from the calendar module.
			$calendar_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->get_module( 'calendar' );
			$default_colors  = array(
				'free'     => '#ffffff',
				'booked'   => '#e74c3c',
				'pending'  => '#f39c12',
				'external' => '#8e44ad',
				'blocked'  => '#2c3e50',
				'special'  => '#3498db',
				'private'  => '#2949a8',
			);

			// Get saved colors or use defaults.
			$saved_colors    = get_option( 'aiohm_booking_calendar_colors', array() );
			$calendar_colors = wp_parse_args( $saved_colors, $default_colors );

			// Get brand color from accommodation/form settings.
			$main_settings = get_option( 'aiohm_booking_settings', array() );
			$brand_color   = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? '#457d59';

			// Localize script with nonce for calendar data requests and calendar colors.
			wp_localize_script(
				'aiohm-booking-shortcode',
				'aiohm_booking_frontend',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
					'calendar_colors' => $calendar_colors,
					'brand_color'     => $brand_color,
					'booking_url'     => home_url( '/booking/' ),
					// phpcs:ignore WordPress.Security.NonceVerification -- GET parameter for booking ID
					'booking_id'      => isset( $_GET['booking_id'] ) ? sanitize_text_field( wp_unslash( $_GET['booking_id'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public frontend parameter for booking display
					'i18n'            => array(
						'select_payment_method'  => __( 'Please select a payment method.', 'aiohm-booking-pro' ),
						'invalid_booking_id'     => __( 'Invalid booking ID.', 'aiohm-booking-pro' ),
						'processing'             => __( 'Processing...', 'aiohm-booking-pro' ),
						'complete_booking'       => __( 'Complete Booking', 'aiohm-booking-pro' ),
						'error_occurred'         => __( 'An error occurred. Please try again.', 'aiohm-booking-pro' ),
						'payment_failed'         => __( 'Payment failed. Please try again.', 'aiohm-booking-pro' ),
						'unknown_payment_method' => __( 'Unknown payment method selected.', 'aiohm-booking-pro' ),
					),
				)
			);

			// Get accommodation and pricing settings for tooltip display
			$accommodation_settings = get_option( 'aiohm_booking_accommodation_settings', array() );
			$pricing_settings       = get_option( 'aiohm_booking_pricing_settings', array() );
			$base_price             = floatval( $main_settings['default_price'] ?? $accommodation_settings['default_price'] ?? $pricing_settings['accommodation_price'] ?? 100 );
			$currency               = $main_settings['currency'] ?? 'USD';

			// Get early bird settings - check multiple possible locations
			$enable_early_bird        = ! empty( $pricing_settings['enable_early_bird'] ) || ! empty( $main_settings['enable_early_bird_accommodation'] );
			$early_bird_days          = intval( $pricing_settings['early_bird_days'] ?? $main_settings['early_bird_days_accommodation'] ?? 30 );
			$early_bird_default_price = floatval( $main_settings['aiohm_booking_accommodation_early_bird_price'] ?? 0 );

			// Also localize as aiohm_booking_data for compatibility with tooltip system.
			wp_localize_script(
				'aiohm-booking-shortcode',
				'aiohm_booking_data',
				array(
					'accommodation_type' => aiohm_booking_get_accommodation_singular_name( $main_settings['accommodation_type'] ?? 'unit' ),
					'date_format'        => $main_settings['date_format'] ?? 'd/m/Y',
					'pricing'            => array(
						'currency'   => $currency,
						'base_price' => $base_price,
						'early_bird' => array(
							'enabled'       => $enable_early_bird,
							'days'          => $early_bird_days,
							'default_price' => $early_bird_default_price,
						),
					),
				)
			);

			// Localize as aiohm_booking for backward compatibility with shortcode JavaScript.
			wp_localize_script(
				'aiohm-booking-shortcode',
				'aiohm_booking',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
					'calendar_colors' => $calendar_colors,
					'brand_color'     => $brand_color,
				)
			);

			// Enqueue frontend JavaScript for non-admin contexts (needed for widget calendar functionality).
			if ( ! $is_admin_context ) {
				wp_enqueue_script(
					'aiohm-booking-frontend',
					AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-frontend.js',
					array( 'jquery' ),
					AIOHM_BOOKING_VERSION,
					true
				);

				// Localize frontend script with calendar colors for consistency.
				wp_localize_script(
					'aiohm-booking-frontend',
					'aiohm_booking_frontend',
					array(
						'ajax_url'        => admin_url( 'admin-ajax.php' ),
						'rest_url'        => rest_url( 'aiohm-booking/v1/' ),
						'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
						'calendar_colors' => $calendar_colors,
						'brand_color'     => $brand_color,
					)
				);
			}

			// Only enqueue advanced calendar JavaScript for accommodation/calendar admin contexts.
			if ( $is_admin_context ) {
				$current_screen               = get_current_screen();
				$accommodation_calendar_pages = array(
					'aiohm-booking_page_aiohm-booking-calendar',
					'aiohm-booking_page_aiohm-booking-accommodations',
				);

				// Only load advanced calendar script on accommodation and calendar admin pages
				if ( $current_screen && in_array( $current_screen->id, $accommodation_calendar_pages, true ) ) {
					wp_enqueue_script(
						'aiohm-booking-advanced-calendar',
						AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-advanced-calendar.js',
						array( 'jquery', 'aiohm-booking-shortcode' ),
						AIOHM_BOOKING_VERSION,
						true
					);
				}
			}
		}
	}

	/**
	 * Detect shortcodes in content and enqueue assets if needed.
	 *
	 * @param string $content The content to check for shortcodes.
	 * @return string The unmodified content.
	 */
	public function detect_shortcodes( $content ) {
		if ( $this->has_shortcodes( $content ) ) {
			$this->enqueue_shortcode_assets();
		}
		return $content;
	}

	/**
	 * Check if content contains any of our shortcodes.
	 *
	 * @param string $content The content to check.
	 * @return bool True if shortcodes are found, false otherwise.
	 */
	private function has_shortcodes( $content ) {
		$shortcodes = array( 'aiohm_booking', 'aiohm_booking_success' );

		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add shortcode button to the WordPress editor.
	 */
	public function add_shortcode_button() {
		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			echo '<button type="button" id="aiohm-shortcode-button" class="button">' . esc_html__( 'Add AIOHM Shortcode', 'aiohm-booking-pro' ) . '</button>';
		}
	}

	/**
	 * Render the shortcode modal for the editor.
	 */
	public function shortcode_modal() {
		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			?>
			<div id="aiohm-shortcode-modal" style="display: none;">
				<div class="aiohm-modal-content">
					<h3><?php esc_html_e( 'Insert AIOHM Booking Shortcode', 'aiohm-booking-pro' ); ?></h3>
					<!-- Modal content would go here -->
				</div>
			</div>
			<?php
		}
	}


	/**
	 * Load a template file
	 *
	 * @param string $template_name Template filename.
	 * @param array  $template_data Data to pass to template.
	 */
	private function load_template( $template_name, $template_data = array() ) {
		$template_path = AIOHM_BOOKING_DIR . 'templates/' . $template_name;

		if ( file_exists( $template_path ) ) {
			// Extract template data to variables.
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Required for template system
			extract( $template_data );
			include $template_path;
		} else {
			echo '<div class="aiohm-booking-error">Template not found: ' . esc_html( $template_name ) . '</div>';
		}
	}

	/**
	 * Get booking settings
	 *
	 * @return array Settings array
	 */
	private function get_settings() {
		return get_option( 'aiohm_booking_settings', array() );
	}

	/**
	 * AJAX handler to get individual unit status for a specific unit and date.
	 */
	public function ajax_get_unit_status() {
		// Verify security using centralized helper.
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'calendar_data', 'manage_options' ) ) {
			return; // Error response already sent by helper
		}

		$unit_id = isset( $_POST['unit_id'] ) ? absint( wp_unslash( $_POST['unit_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper or form validation
		$date    = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper or form validation

		if ( empty( $date ) ) {
			wp_send_json_error( 'Date is required' );
		}

		// Get the cell status for this specific unit and date.
		$saved_cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );
		$cell_key            = $unit_id . '_' . $date . '_full';

		$status = 'free'; // Default status.
		if ( isset( $saved_cell_statuses[ $cell_key ] ) ) {
			$status = $saved_cell_statuses[ $cell_key ]['status'] ?? 'free';
		}

		wp_send_json_success(
			array(
				'status'  => $status,
				'unit_id' => $unit_id,
				'date'    => $date,
			)
		);
	}

	/**
	 * AJAX handler to get multiple unit statuses for a specific date
	 */
	public function ajax_get_multiple_unit_statuses() {
		// Verify security using centralized helper.
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_security( 'frontend_nonce', 'manage_options' ) ) {
			return; // Error response already sent by helper
		}

		$unit_ids = isset( $_POST['unit_ids'] ) && is_array( $_POST['unit_ids'] ) ? wp_unslash( $_POST['unit_ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by security helper, sanitized with array_map absint below
		$date     = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper or form validation

		if ( empty( $date ) || ! is_array( $unit_ids ) ) {
			wp_send_json_error( 'Date and unit IDs are required' );
		}

		// Sanitize unit IDs.
		$unit_ids = array_map( 'absint', $unit_ids );

		// Get the cell statuses for all units on this date.
		$saved_cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );
		$statuses            = array();

		foreach ( $unit_ids as $unit_id ) {
			$unit_id  = absint( $unit_id );
			$cell_key = $unit_id . '_' . $date . '_full';

			$status = 'free'; // Default status.
			if ( isset( $saved_cell_statuses[ $cell_key ] ) ) {
				$status = $saved_cell_statuses[ $cell_key ]['status'] ?? 'free';
			}

			$statuses[ $unit_id ] = $status;
		}

		wp_send_json_success(
			array(
				'statuses' => $statuses,
				'date'     => $date,
			)
		);
	}

	/**
	 * Get calendar availability data for a date range
	 *
	 * @param string $start_date Start date for the range.
	 * @param string $end_date   End date for the range.
	 * @param int    $unit_id    Unit ID to filter by (optional).
	 */
	private function get_calendar_availability_data( $start_date, $end_date, $unit_id = 0 ) {
		// Load saved data.
		$saved_cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );
		$private_events      = get_option( 'aiohm_booking_private_events', array() );
		$demo_disabled       = get_option( 'aiohm_booking_calendar_disable_demo', true ); // Production: disable demo by default.

		$availability_data = array();

		$start    = new DateTime( $start_date );
		$end      = new DateTime( $end_date );
		$interval = new DateInterval( 'P1D' );
		$period   = new DatePeriod( $start, $interval, $end->add( $interval ) );

		foreach ( $period as $date ) {
			$date_string = $date->format( 'Y-m-d' );

			$status             = 'available';
			$price              = 0;
			$is_private_event   = false;
			$event_name         = '';
			$is_any_unit_booked = false;

			// Check for private events first (global).
			if ( isset( $private_events[ $date_string ] ) ) {
				$event = $private_events[ $date_string ];
				if ( ! empty( $event['is_private_event'] ) ) {
					$status           = 'private';
					$is_private_event = true;
					$event_name       = $event['name'] ?? '';
				}
				if ( ! empty( $event['is_special_pricing'] ) && 0 < $event['price'] ) {
					$price = $event['price'];
					if ( 'available' === $status || 'free' === $status ) {
						$status = 'special_pricing';
					}
				}
			}

			// Get all accommodation post IDs (real post IDs, not just range 0-6).
			$accommodations = get_posts(
				array(
					'post_type'      => 'aiohm_accommodation',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				)
			);

			// Fallback to old range system if no accommodations found.
			if ( empty( $accommodations ) ) {
				$settings                 = get_option( 'aiohm_booking_settings', array() );
				$available_accommodations = intval( $settings['available_accommodations'] ?? 1 );
				$accommodations           = range( 0, $available_accommodations - 1 );
			}

			// Check availability across all units.
			$booked_units   = 0;
			$pending_units  = 0;
			$blocked_units  = 0;
			$external_units = 0;
			$total_units    = count( $accommodations );

			foreach ( $accommodations as $check_unit_id ) {
				$cell_key = $check_unit_id . '_' . $date_string . '_full';

				if ( isset( $saved_cell_statuses[ $cell_key ] ) ) {
					$cell_status = $saved_cell_statuses[ $cell_key ];
					$unit_status = $cell_status['status'];

					if ( 'booked' === $unit_status ) {
						++$booked_units;
						$is_any_unit_booked = true;
					} elseif ( 'pending' === $unit_status ) {
						++$pending_units;
						$is_any_unit_booked = true;
					} elseif ( 'blocked' === $unit_status ) {
						++$blocked_units;
						$is_any_unit_booked = true;
					} elseif ( 'external' === $unit_status ) {
						++$external_units;
						$is_any_unit_booked = true;
					}
				}
			}

			// Determine day status based on unit availability.
			if ( 'available' === $status ) {
				if ( $total_units === $booked_units ) {
					$status = 'booked'; // All units booked.
				} elseif ( $total_units === $blocked_units ) {
					$status = 'blocked'; // All units blocked.
				} elseif ( $total_units === $pending_units ) {
					$status = 'pending'; // All units pending.
				} elseif ( $total_units === $external_units ) {
					$status = 'external'; // All units external.
				} elseif ( $booked_units > 0 || $pending_units > 0 || $blocked_units > 0 || $external_units > 0 ) {
					$status = 'free'; // Some units available, some booked.
				} else {
					$status = 'free'; // All units free.
				}
			}

			// If no specific unit booking found and demo is enabled, generate demo data.
			if ( ! $is_any_unit_booked && ! $demo_disabled && 'available' === $status ) {
				$sample_statuses = array( 'available', 'booked', 'pending', 'blocked' );
				$random_status   = $sample_statuses[ array_rand( $sample_statuses ) ];
				if ( 'available' !== $random_status ) {
					$status = $random_status;
				}
			}

			// Determine badge flags.
			$badges = array();
			if ( isset( $private_events[ $date_string ] ) ) {
				$event = $private_events[ $date_string ];

				// Handle both old and new data structures.
				$is_private_event_flag = isset( $event['is_private_event'] )
					? ! empty( $event['is_private_event'] )
					: ( isset( $event['mode'] ) && ( 'private' === $event['mode'] || 'both' === $event['mode'] ) );

				$is_special_pricing_flag = isset( $event['is_special_pricing'] )
					? ! empty( $event['is_special_pricing'] )
					: ( isset( $event['mode'] ) && ( 'special' === $event['mode'] || 'both' === $event['mode'] ) );

				if ( $is_private_event_flag ) {
					$badges['private'] = true;
				}
				if ( $is_special_pricing_flag && ! empty( $event['price'] ) && $event['price'] > 0 ) {
					$badges['special'] = true;
				}
			}

			// Convert badges to frontend format.
			$formatted_badges = array();
			if ( ! empty( $badges ) ) {
				if ( ! empty( $badges['private'] ) ) {
					$formatted_badges[] = array(
						'type' => 'private',
						'icon' => '🏠',
					);
				}
				if ( ! empty( $badges['special'] ) ) {
					$formatted_badges[] = array(
						'type' => 'special',
						'icon' => '🌞',
					);
				}
			}

			$availability_data[ $date_string ] = array(
				'status'           => 'available' === $status ? 'free' : $status,
				'price'            => $price,
				'is_private_event' => $is_private_event,
				'event_name'       => $event_name,
				'available'        => in_array( $status, array( 'available', 'free', 'special_pricing', 'private' ), true ),
				'badges'           => ! empty( $formatted_badges ) ? $formatted_badges : null,
				'units'            => array(
					'total'     => $total_units,
					'booked'    => $booked_units,
					'pending'   => $pending_units,
					'blocked'   => $blocked_units,
					'external'  => $external_units,
					'available' => $total_units - ( $booked_units + $pending_units + $blocked_units + $external_units ),
				),
			);
		}

		return $availability_data;
	}

	/**
	 * Process accommodation booking form submission via AJAX
	 *
	 * @since 1.2.6
	 */
	public function ajax_process_accommodation_booking() {
		// Verify security using centralized helper (only nonce for frontend).
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'frontend_nonce' ) ) {
			return; // Error response already sent by helper
		}

		// Parse form data.
		$form_data_raw = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by security helper, sanitized by parse_str
		parse_str( $form_data_raw, $form_data );

		// Validate required fields.
		if ( empty( $form_data['checkin_date'] ) || empty( $form_data['checkout_date'] ) ) {
			wp_send_json_error( array( 'message' => 'Check-in and check-out dates are required' ) );
		}

		// Prepare booking data for centralized sanitization.
		$booking_data = array(
			'customer_first_name' => $form_data['name'] ?? '',
			'customer_email'      => $form_data['email'] ?? '',
			'customer_phone'      => $form_data['phone'] ?? '',
			'checkin_date'        => $form_data['checkin_date'] ?? '',
			'checkout_date'       => $form_data['checkout_date'] ?? '',
			'guests'              => $form_data['guests_qty'] ?? 1,
		);

		// Sanitize using centralized validation.
		$sanitized_data = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_booking_data( $booking_data );

		// Extract sanitized values.
		$buyer_name     = $sanitized_data['customer_first_name'];
		$buyer_email    = $sanitized_data['customer_email'];
		$buyer_phone    = $sanitized_data['customer_phone'];
		$checkin_date   = $sanitized_data['checkin_date'];
		$checkout_date  = $sanitized_data['checkout_date'];
		$guest_count    = $sanitized_data['guests'];
		$stay_duration  = intval( $form_data['stay_duration'] ?? 1 );
		$accommodations = $form_data['accommodations'] ?? array();

		// Handle single accommodation selection or checkbox arrays.
		if ( empty( $accommodations ) && isset( $form_data['accommodation_id'] ) ) {
			$accommodations = array( $form_data['accommodation_id'] );
		}

		// Convert single value to array if needed.
		if ( ! is_array( $accommodations ) ) {
			$accommodations = array( $accommodations );
		}

		// Filter out empty values.
		$accommodations = array_filter( array_map( 'intval', $accommodations ) );

		$notes = sanitize_textarea_field( $form_data['notes'] ?? '' );
		if ( empty( $notes ) ) {
			$notes = sanitize_textarea_field( $form_data['special_requests'] ?? '' );
		}

		// Store accommodation IDs in notes for payment completion processing
		if ( empty( $notes ) ) {
			$notes = 'Accommodation IDs: ' . wp_json_encode( $accommodations );
		} else {
			$notes .= "\n\nAccommodation IDs: " . wp_json_encode( $accommodations );
		}

		// Validate that we have accommodations selected.
		if ( empty( $accommodations ) ) {
			wp_send_json_error( array( 'message' => 'Please select at least one accommodation' ) );
		}

		// Validate email.
		if ( ! AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::validate_email( $buyer_email ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid email address' ) );
		}

		// Calculate dates.
		$checkin  = new DateTime( $checkin_date );
		$checkout = new DateTime( $checkout_date );

		// Validate date range.
		if ( $checkout <= $checkin ) {
			wp_send_json_error( array( 'message' => 'Check-out date must be after check-in date' ) );
		}

		// Apply calendar rules validation.
		$booking_data = array(
			'checkin_date'   => $checkin_date,
			'checkout_date'  => $checkout_date,
			'accommodations' => $accommodations,
			'private_all'    => isset( $form_data['private_all'] ) && $form_data['private_all'],
		);

		$validation_result = apply_filters( 'aiohm_booking_validate_booking_request', true, $booking_data );

		if ( is_wp_error( $validation_result ) ) {
			wp_send_json_error( array( 'message' => $validation_result->get_error_message() ) );
		}

		// Calculate total nights.
		$interval     = $checkin->diff( $checkout );
		$total_nights = $interval->days;

		if ( $total_nights !== $stay_duration ) {
			wp_send_json_error( array( 'message' => 'Stay duration does not match date range' ) );
		}

		// Get pricing settings and currency.
		$settings           = get_option( 'aiohm_booking_settings', array() );
		$currency           = $settings['currency'] ?? 'USD';
		$deposit_percentage = floatval( $settings['deposit_percentage'] ?? 0 );
		$earlybird_days     = intval( $settings['early_bird_days'] ?? $settings['earlybird_days'] ?? 30 );

		// Calculate early bird eligibility.
		$current_date          = new DateTime();
		$days_until_checkin    = $current_date->diff( $checkin )->days;
		$is_earlybird_eligible = ( $days_until_checkin >= $earlybird_days );

		// Get accommodation module for default pricing.
		$accommodation_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->get_module( 'accommodations' );
		$default_price        = 0;
		if ( $accommodation_module && method_exists( $accommodation_module, 'get_module_settings' ) ) {
			$module_settings = $accommodation_module->get_module_settings();
			$default_price   = floatval( $module_settings['default_price'] ?? 0 );
		}

		// Calculate total price based on selected accommodations.
		$total_price = 0;

		// Check if booking entire property and if there's special pricing for the date range
		$is_private_all = isset( $form_data['private_all'] ) && $form_data['private_all'];
		if ( $is_private_all ) {
			// Check for special pricing from private events
			$private_events = get_option( 'aiohm_booking_private_events', array() );
			$special_price_found = false;
			$consistent_special_price = null;
			
			// Check each night in the stay for special pricing
			$current_check_date = clone $checkin;
			while ( $current_check_date < $checkout ) {
				$date_string = $current_check_date->format( 'Y-m-d' );
				if ( isset( $private_events[ $date_string ] ) && ! empty( $private_events[ $date_string ]['price'] ) ) {
					$night_price = floatval( $private_events[ $date_string ]['price'] );
					if ( $consistent_special_price === null ) {
						$consistent_special_price = $night_price;
						$special_price_found = true;
					} elseif ( $consistent_special_price !== $night_price ) {
						// Prices vary, can't use for entire property
						$special_price_found = false;
						break;
					}
				} else {
					// No special pricing for this night
					$special_price_found = false;
					break;
				}
				$current_check_date->modify( '+1 day' );
			}
			
			if ( $special_price_found && $consistent_special_price !== null ) {
				// Use special pricing for the entire property
				$total_price = $consistent_special_price * $total_nights;
			}
		}

		// If no special pricing or not booking entire property, calculate based on individual accommodations
		if ( $total_price === 0 ) {
			foreach ( $accommodations as $accommodation_id ) {
				$accommodation_id = intval( $accommodation_id );
				$post_meta        = get_post_meta( $accommodation_id );

				// Get accommodation pricing with early bird logic.
				$accommodation_price = 0;
				$regular_price       = 0;
				$earlybird_price     = 0;

				// Get regular price.
				if ( isset( $post_meta['_aiohm_booking_accommodation_price'][0] ) && ! empty( $post_meta['_aiohm_booking_accommodation_price'][0] ) ) {
					$regular_price = floatval( $post_meta['_aiohm_booking_accommodation_price'][0] );
				} elseif ( isset( $post_meta['_price'][0] ) && ! empty( $post_meta['_price'][0] ) ) {
					$regular_price = floatval( $post_meta['_price'][0] );
				} elseif ( isset( $post_meta['price'][0] ) && ! empty( $post_meta['price'][0] ) ) {
					$regular_price = floatval( $post_meta['price'][0] );
				} elseif ( isset( $post_meta['base_price'][0] ) && ! empty( $post_meta['base_price'][0] ) ) {
					$regular_price = floatval( $post_meta['base_price'][0] );
				}

				// Get early bird price.
				if ( isset( $post_meta['_aiohm_booking_accommodation_earlybird_price'][0] ) && ! empty( $post_meta['_aiohm_booking_accommodation_earlybird_price'][0] ) ) {
					$earlybird_price = floatval( $post_meta['_aiohm_booking_accommodation_earlybird_price'][0] );
				} elseif ( isset( $post_meta['_earlybird_price'][0] ) && ! empty( $post_meta['_earlybird_price'][0] ) ) {
					$earlybird_price = floatval( $post_meta['_earlybird_price'][0] );
				} elseif ( isset( $post_meta['earlybird_price'][0] ) && ! empty( $post_meta['earlybird_price'][0] ) ) {
					$earlybird_price = floatval( $post_meta['earlybird_price'][0] );
				} else {
					// Use fallback: 20% discount from regular price if no early bird price is set.
					$earlybird_price = $regular_price > 0 ? $regular_price * 0.8 : 0;
				}

				// Apply early bird pricing logic.
				if ( $is_earlybird_eligible && $earlybird_price > 0 ) {
					$accommodation_price = $earlybird_price;
				} elseif ( $regular_price > 0 ) {
					$accommodation_price = $regular_price;
				}

				$accommodation_total = $accommodation_price * $total_nights;
				$total_price        += $accommodation_total;
			}
		}

		// If no accommodations have prices, use a fallback base price.
		if ( 0 === $total_price ) {
			$base_price  = floatval( $settings['base_price'] ?? $settings['accommodation_price'] ?? $default_price );
			$total_price = $base_price * $total_nights * count( $accommodations );
		}

		$deposit_amount = $total_price * ( $deposit_percentage / 100 );

		// Save booking to database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		// Check if table exists, create if missing.
		$table_exists = wp_cache_get( 'aiohm_booking_table_exists_' . $table_name, 'aiohm_booking' );
		if ( false === $table_exists ) {
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Cached database call
			wp_cache_set( 'aiohm_booking_table_exists_' . $table_name, $table_exists, 'aiohm_booking', 3600 );
		}
		if ( ! $table_exists ) {
			// Try to create the table by calling the Orders module activation.
			if ( class_exists( 'AIOHM_Booking_Module_Orders' ) ) {
				AIOHM_Booking_Module_Orders::on_activation();
				// Check again if table was created.
				$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Cached database call
				wp_cache_set( 'aiohm_booking_table_exists_' . $table_name, $table_exists, 'aiohm_booking', 3600 );
				if ( ! $table_exists ) {
					wp_send_json_error( array( 'message' => 'Failed to create booking table. Please contact administrator.' ) );
				}
			} else {
				wp_send_json_error( array( 'message' => 'Booking system not properly initialized. Please contact administrator.' ) );
			}
		}

		// Use direct SQL insert to bypass WordPress validation.
		$insert_query = $wpdb->prepare(
			'INSERT INTO ' . esc_sql( $table_name ) . ' (buyer_name, buyer_email, buyer_phone, mode, units_qty, guests_qty, currency, total_amount, deposit_amount, status, check_in_date, check_out_date, notes, created_at) VALUES (%s, %s, %s, %s, %d, %d, %s, %f, %f, %s, %s, %s, %s, %s)',
			$buyer_name,
			$buyer_email,
			$buyer_phone,
			'accommodation',
			count( $accommodations ),
			$guest_count,
			$currency,
			$total_price,
			$deposit_amount,
			'pending',
			$checkin_date,
			$checkout_date,
			$notes,
			current_time( 'mysql' )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- $insert_query is already prepared above
		$result = $wpdb->query( $insert_query );

		if ( false === $result || 0 === $result ) {
			wp_send_json_error(
				array(
					'message'  => 'Failed to save booking',
					'db_error' => $wpdb->last_error,
					'query'    => $wpdb->last_query,
				)
			);
		}

		$booking_id = $wpdb->insert_id;

		// Update calendar data to mark dates as booked.
		$this->update_calendar_for_booking( $checkin_date, $checkout_date, $accommodations, $booking_id );

		// Trigger booking confirmation email notification
		do_action( 'aiohm_booking_order_created', $booking_id );

		wp_send_json_success(
			array(
				'message'    => 'Booking submitted successfully! We will contact you soon to confirm.',
				'booking_id' => $booking_id,
			)
		);
	}

	/**
	 * Process unified booking form submission via AJAX (accommodations + events)
	 *
	 * @since 1.2.6
	 */
	public function ajax_process_unified_booking() {
		// Verify security using centralized helper (only nonce for frontend).
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'frontend_nonce' ) ) {
			return; // Error response already sent by helper
		}

		// Parse form data.
		$form_data_raw = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by security helper, sanitized by parse_str
		parse_str( $form_data_raw, $form_data );

		// Determine booking type based on form data
		$has_accommodations = ! empty( $form_data['accommodations'] ) || ! empty( $form_data['accommodation_id'] );
		$has_events         = ! empty( $form_data['selected_event'] ) || ! empty( $form_data['selected_events'] );

		if ( $has_accommodations && $has_events ) {
			// Mixed booking - handle both accommodations and events

			// Process accommodation part
			$accommodation_result = $this->process_accommodation_booking_part( $form_data );
			if ( is_wp_error( $accommodation_result ) ) {
				wp_send_json_error( array( 'message' => $accommodation_result->get_error_message() ) );
			}

			// Process event part
			$tickets_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->get_module( 'tickets' );
			if ( $tickets_module && method_exists( $tickets_module, 'process_event_booking_part' ) ) {
				$event_result = $tickets_module->process_event_booking_part( $form_data );
				if ( is_wp_error( $event_result ) ) {
					wp_send_json_error( array( 'message' => $event_result->get_error_message() ) );
				}
			}

			wp_send_json_success(
				array(
					'message'                  => 'Mixed booking submitted successfully!',
					'accommodation_booking_id' => $accommodation_result,
					'event_booking_id'         => $event_result ?? null,
				)
			);

		} elseif ( $has_accommodations ) {
			// Redirect to accommodation-only handler
			$this->ajax_process_accommodation_booking();

		} elseif ( $has_events ) {
			// Redirect to event-only handler
			$tickets_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->get_module( 'tickets' );
			if ( $tickets_module && method_exists( $tickets_module, 'ajax_process_event_booking' ) ) {
				$tickets_module->ajax_process_event_booking();
			} else {
				wp_send_json_error( array( 'message' => 'Event booking handler not available' ) );
			}
		} else {
			wp_send_json_error( array( 'message' => 'Please select either accommodations or events to book' ) );
		}
	}

	/**
	 * Helper method to process accommodation part of a booking
	 *
	 * @param array $form_data Form data
	 * @return int|WP_Error Booking ID on success, WP_Error on failure
	 */
	private function process_accommodation_booking_part( $form_data ) {
		// Prepare booking data for centralized sanitization.
		$booking_data = array(
			'customer_first_name' => $form_data['name'] ?? '',
			'customer_email'      => $form_data['email'] ?? '',
			'customer_phone'      => $form_data['phone'] ?? '',
			'checkin_date'        => $form_data['checkin_date'] ?? '',
			'checkout_date'       => $form_data['checkout_date'] ?? '',
			'guests'              => $form_data['guests_qty'] ?? 1,
		);

		// Sanitize using centralized validation.
		$sanitized_data = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_booking_data( $booking_data );

		// Extract sanitized values.
		$buyer_name     = $sanitized_data['customer_first_name'];
		$buyer_email    = $sanitized_data['customer_email'];
		$buyer_phone    = $sanitized_data['customer_phone'];
		$checkin_date   = $sanitized_data['checkin_date'];
		$checkout_date  = $sanitized_data['checkout_date'];
		$guest_count    = $sanitized_data['guests'];
		$stay_duration  = intval( $form_data['stay_duration'] ?? 1 );
		$accommodations = $form_data['accommodations'] ?? array();

		// Handle single accommodation selection or checkbox arrays.
		if ( empty( $accommodations ) && isset( $form_data['accommodation_id'] ) ) {
			$accommodations = array( $form_data['accommodation_id'] );
		}

		// If booking entire property, get all accommodation IDs
		if ( isset( $form_data['private_all'] ) && $form_data['private_all'] ) {
			$all_accommodations = aiohm_booking_get_accommodation_posts( -1 );
			$accommodations = array_map( function( $post ) {
				return $post->ID;
			}, $all_accommodations );
		}

		// Convert single value to array if needed.
		if ( ! is_array( $accommodations ) ) {
			$accommodations = array( $accommodations );
		}

		// Filter out empty values.
		$accommodations = array_filter( array_map( 'intval', $accommodations ) );

		$notes = sanitize_textarea_field( $form_data['notes'] ?? '' );
		if ( empty( $notes ) ) {
			$notes = sanitize_textarea_field( $form_data['special_requests'] ?? '' );
		}

		// Validate that we have accommodations selected.
		if ( empty( $accommodations ) ) {
			return new WP_Error( 'accommodation_required', 'Please select at least one accommodation' );
		}

		// Validate email.
		if ( ! AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::validate_email( $buyer_email ) ) {
			return new WP_Error( 'invalid_email', 'Please enter a valid email address' );
		}

		// Calculate dates.
		$checkin  = new DateTime( $checkin_date );
		$checkout = new DateTime( $checkout_date );

		// Validate date range.
		if ( $checkout <= $checkin ) {
			return new WP_Error( 'invalid_dates', 'Check-out date must be after check-in date' );
		}

		// Apply calendar rules validation.
		$booking_data = array(
			'checkin_date'   => $checkin_date,
			'checkout_date'  => $checkout_date,
			'accommodations' => $accommodations,
			'private_all'    => isset( $form_data['private_all'] ) && $form_data['private_all'],
		);

		$validation_result = apply_filters( 'aiohm_booking_validate_booking_request', true, $booking_data );

		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Calculate total nights.
		$interval     = $checkin->diff( $checkout );
		$total_nights = $interval->days;

		if ( $total_nights !== $stay_duration ) {
			return new WP_Error( 'duration_mismatch', 'Stay duration does not match date range' );
		}

		// Calculate pricing (simplified version)
		$settings             = get_option( 'aiohm_booking_settings', array() );
		$accommodation_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->get_module( 'accommodations' );
		$default_price        = 0;
		if ( $accommodation_module && method_exists( $accommodation_module, 'get_module_settings' ) ) {
			$module_settings = $accommodation_module->get_module_settings();
			$default_price   = floatval( $module_settings['default_price'] ?? 0 );
		}

		$total_price = $default_price * count( $accommodations ) * $total_nights;

		// Insert booking into database
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_orders';

		$booking_id = $wpdb->insert(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for admin data insertion
			$table_name,
			array(
				'buyer_name'     => $buyer_name,
				'buyer_email'    => $buyer_email,
				'buyer_phone'    => $buyer_phone,
				'checkin_date'   => $checkin_date,
				'checkout_date'  => $checkout_date,
				'guest_count'    => $guest_count,
				'total_nights'   => $total_nights,
				'total_price'    => $total_price,
				'accommodations' => wp_json_encode( $accommodations ),
				'notes'          => $notes,
				'booking_date'   => current_time( 'mysql' ),
				'status'         => 'pending',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		if ( false === $booking_id ) {
			return new WP_Error( 'database_error', 'Failed to create booking in database' );
		}

		$booking_id = $wpdb->insert_id;

		// Update calendar data to mark dates as booked.
		$this->update_calendar_for_booking( $checkin_date, $checkout_date, $accommodations, $booking_id );

		// Trigger booking confirmation email notification
		do_action( 'aiohm_booking_order_created', $booking_id );

		return $booking_id;
	}

	/**
	 * TEMPORARY: Handle legacy AJAX requests to the old general handler
	 * This helps us debug what's calling the old action
	 *
	 * @since 1.2.6
	 */
	public function ajax_legacy_booking_handler() {
		// Verify nonce first
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_request() ) {
			return; // Error response already sent by helper
		}
		
		// Parse form data to determine what should have been called
		$form_data_raw = isset( $_POST['form_data'] ) ? sanitize_textarea_field( wp_unslash( $_POST['form_data'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by security helper, sanitized by parse_str
		parse_str( $form_data_raw, $form_data );

		$has_accommodations = ! empty( $form_data['accommodations'] ) || ! empty( $form_data['accommodation_id'] );
		$has_events         = ! empty( $form_data['selected_event'] ) || ! empty( $form_data['selected_events'] );

		// Redirect to the correct handler
		if ( $has_accommodations && $has_events ) {
			$this->ajax_process_unified_booking();
		} elseif ( $has_accommodations ) {
			$this->ajax_process_accommodation_booking();
		} elseif ( $has_events ) {
			$tickets_module = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->get_module( 'tickets' );
			if ( $tickets_module && method_exists( $tickets_module, 'ajax_process_event_booking' ) ) {
				$tickets_module->ajax_process_event_booking();
			}
		} else {
			wp_send_json_error( array( 'message' => 'Legacy handler: Unable to determine booking type' ) );
		}
	}

	/**
	 * Update calendar data when a booking is made
	 *
	 * @param string $checkin_date  Check-in date.
	 * @param string $checkout_date Check-out date.
	 * @param array  $accommodations Array of accommodation IDs.
	 * @param int    $booking_id    Booking ID (optional).
	 */
	private function update_calendar_for_booking( $checkin_date, $checkout_date, $accommodations, $booking_id = null ) {
		$saved_cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );

		$checkin  = new DateTime( $checkin_date );
		$checkout = new DateTime( $checkout_date );

		// Loop through each date in the booking.
		$current_date = clone $checkin;
		while ( $current_date < $checkout ) {
			$date_string = $current_date->format( 'Y-m-d' );

			// Update status for each accommodation.
			foreach ( $accommodations as $accommodation_id ) {
				$cell_key                         = $accommodation_id . '_' . $date_string . '_full';
				$saved_cell_statuses[ $cell_key ] = array(
					'status'     => 'booked',
					'booking_id' => $booking_id,
					'price'      => 0, // Could be enhanced to store booking price.
				);
			}

			$current_date->modify( '+1 day' );
		}

		// Save updated calendar data.
		update_option( 'aiohm_booking_cell_statuses', $saved_cell_statuses );

		// Clear accommodation service cache to ensure statistics are updated.
		if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service' ) ) {
			AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service::clear_cache();
		}
	}

	/**
	 * Update calendar when booking status changes
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $new_status New booking status.
	 */
	public function update_booking_status( $booking_id, $new_status ) {
		global $wpdb;

		// Get booking details.
		$table_name = $wpdb->prefix . 'aiohm_booking_order';
		$booking    = wp_cache_get( 'aiohm_booking_' . $booking_id, 'aiohm_booking' );
		if ( false === $booking ) {
			$booking = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Cached database call
				$wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id ),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
			wp_cache_set( 'aiohm_booking_' . $booking_id, $booking, 'aiohm_booking', 300 );
		}

		if ( ! $booking ) {
			return false;
		}

		// Update booking status in database.
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Direct update for booking status
			$table_name,
			array( 'status' => $new_status ),
			array( 'id' => $booking_id )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

		// Update calendar status.
		$cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );

		$checkin  = new DateTime( $booking['check_in_date'] );
		$checkout = new DateTime( $booking['check_out_date'] );

		$current_date = clone $checkin;
		while ( $current_date < $checkout ) {
			$date_string = $current_date->format( 'Y-m-d' );

			// Update status for all calendar entries with this booking ID.
			foreach ( $cell_statuses as $key => $data ) {
				if ( isset( $data['booking_id'] ) && $data['booking_id'] === $booking_id && strpos( $key, $date_string ) !== false ) {
					$cell_statuses[ $key ]['status'] = $new_status;
				}
			}

			$current_date->modify( '+1 day' );
		}

		update_option( 'aiohm_booking_cell_statuses', $cell_statuses );

		// Clear accommodation service cache to ensure statistics are updated.
		if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service' ) ) {
			AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service::clear_cache();
		}

		return true;
	}

	/**
	 * Process checkout completion via AJAX
	 */
	public function ajax_complete_checkout() {
		// Verify security using centralized helper (only nonce for frontend)
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'frontend_nonce' ) ) {
			return; // Error response already sent by helper
		}

		$booking_id     = isset( $_POST['booking_id'] ) ? intval( wp_unslash( $_POST['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'manual'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$redirect_url   = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		if ( empty( $booking_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		// Get the booking.
		$booking = wp_cache_get( 'aiohm_booking_' . $booking_id, 'aiohm_booking' );
		if ( false === $booking ) {
			$booking = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Cached database call
			wp_cache_set( 'aiohm_booking_' . $booking_id, $booking, 'aiohm_booking', 300 );
		}

		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Booking not found' ) );
		}

		// Check if booking is already processed.
		if ( 'pending' !== $booking->status ) {
			wp_send_json_error( array( 'message' => 'Booking has already been processed' ) );
		}

		// Handle different payment methods.
		if ( 'manual' === $payment_method ) {
			// For manual payments, update status to 'pending' and send invoice.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Direct update for booking status
			$result = $wpdb->update(
				$table_name,
				array(
					'payment_method' => 'manual',
					'status'         => 'pending',
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $booking_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

			if ( false === $result ) {
				wp_send_json_error( array( 'message' => 'Failed to update booking status' ) );
			}

			// Send invoice email using notification module.
			$this->send_manual_payment_invoice( $booking_id );

			// Get user-configured redirect URL from settings
			$success_redirect = '';

			// Try to get the thank you page URL from form settings first
			$thankyou_page_url = '';

			// Check if this is an events booking and get events-specific thank you page
			if ( ! empty( $booking->event_data ) ) {
				$events_settings   = get_option( 'aiohm_booking_tickets_form_settings', array() );
				$thankyou_page_url = $events_settings['thankyou_page_url'] ?? '';
			}

			// Fall back to general form settings (used for accommodation bookings)
			if ( empty( $thankyou_page_url ) ) {
				$form_settings     = get_option( 'aiohm_booking_form_settings', array() );
				$thankyou_page_url = $form_settings['thankyou_page_url'] ?? '';
			}

			// Fall back to legacy accommodation settings if form settings not found
			if ( empty( $thankyou_page_url ) ) {
				$legacy_accommodation_settings = get_option( 'aiohm_booking_accommodation_form_settings', array() );
				$thankyou_page_url             = $legacy_accommodation_settings['thankyou_page_url'] ?? '';
			}

			// Fall back to main settings if module-specific not found
			if ( empty( $thankyou_page_url ) ) {
				$main_settings     = get_option( 'aiohm_booking_settings', array() );
				$thankyou_page_url = $main_settings['thankyou_page_url'] ?? '';
			}

			// Use user-configured redirect URL or fallback to default success page
			if ( ! empty( $thankyou_page_url ) ) {
				// Use user-configured URL from settings and append booking ID parameter
				$success_redirect = add_query_arg( 'booking_id', $booking_id, $thankyou_page_url );
			} elseif ( ! empty( $redirect_url ) ) {
				// Use user-configured URL from POST data and append booking ID parameter
				$success_redirect = add_query_arg( 'booking_id', $booking_id, $redirect_url );
			} else {
				// Fallback to default success page URL pattern
				$success_redirect = home_url( '/booking-success?booking_id=' . $booking_id );
			}

			wp_send_json_success(
				array(
					'message'        => 'Booking confirmed! You will receive payment instructions via email shortly.',
					'booking_id'     => $booking_id,
					'payment_method' => 'manual',
					'redirect'       => $success_redirect,
				)
			);

			// Payment method handling is now delegated to payment modules

		} else {
			wp_send_json_error( array( 'message' => 'Invalid payment method selected' ) );
		}
	}

	/**
	 * Send manual payment invoice email
	 *
	 * @param int $booking_id Booking ID.
	 */
	private function send_manual_payment_invoice( $booking_id ) {
		// Check if notifications module is available.
		$registry             = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance();
		$notifications_module = $registry->get_module( 'notifications' );

		if ( $notifications_module && method_exists( $notifications_module, 'send_invoice_notification' ) ) {
			// Use the notification module to send the invoice.
			$notifications_module->send_invoice_notification( $booking_id );
		} else {
			// Fallback: Send a simple email.
			$this->send_simple_invoice_email( $booking_id );
		}
	}

	/**
	 * Fallback method to send invoice email
	 *
	 * @param int $booking_id Booking ID.
	 */
	private function send_simple_invoice_email( $booking_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		$booking = wp_cache_get( 'aiohm_booking_' . $booking_id, 'aiohm_booking' );
		if ( false === $booking ) {
			$booking = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $booking_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Cached database call
			wp_cache_set( 'aiohm_booking_' . $booking_id, $booking, 'aiohm_booking', 300 );
		}

		if ( ! $booking ) {
			return false;
		}

		// translators: %s: booking ID.
		$subject = sprintf( __( 'Payment Instructions - Booking #%s', 'aiohm-booking-pro' ), $booking_id );

		// translators: %1$s: buyer name, %2$s: booking ID, %3$s: check-in date, %4$s: check-out date, %5$d: number of guests, %6$s: currency, %7$s: total amount, %8$s: currency, %9$s: deposit amount, %10$s: booking ID again, %11$s: site name.
		$message = sprintf(
			// translators: %1$s: buyer name, %2$s: booking ID, %3$s: check-in date, %4$s: check-out date, %5$d: number of guests, %6$s: currency, %7$s: total amount, %8$s: currency, %9$s: deposit amount, %10$s: booking ID again, %11$s: site name.
			__(
				'Dear %1$s,

Thank you for your booking! Here are your payment instructions:

📋 Booking Details:
• Booking ID: #%2$s
• Check-in: %3$s
• Check-out: %4$s
• Guests: %5$d
• Total Amount: %6$s %7$s
• Deposit Due: %8$s %9$s

💳 Payment Instructions:
Please transfer the deposit amount to secure your booking. You will receive detailed payment instructions and our bank details in a separate email.

Your booking reference: #%10$s

Best regards,
%11$s Team',
				'aiohm-booking-pro'
			),
			$booking->buyer_name,
			$booking_id,
			$booking->check_in_date,
			$booking->check_out_date,
			$booking->guests_qty,
			$booking->currency,
			number_format( $booking->total_amount, 2 ),
			$booking->currency,
			number_format( $booking->deposit_amount, 2 ),
			$booking_id,
			get_bloginfo( 'name' )
		);

		return wp_mail( $booking->buyer_email, $subject, $message );
	}

	/**
	 * Get the singular name for accommodation type
	 *
	 * @return string Accommodation singular name.
	 */
	private function get_accommodation_singular_name() {
		$settings           = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$accommodation_type = $settings['accommodation_type'] ?? 'unit';
		return aiohm_booking_get_accommodation_singular_name( $accommodation_type );
	}

	/**
	 * Preview endpoint for iframe-based preview
	 * Renders the shortcode in a clean frontend environment
	 */
	public function preview_endpoint() {
		// Verify nonce for security
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_booking_preview' ) ) {
			wp_die( 'Security check failed' );
		}

		// Get the shortcode type from the request
		$shortcode_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'events'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified above
		$apply_colors   = isset( $_GET['colors'] ) && sanitize_text_field( wp_unslash( $_GET['colors'] ) ) === '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified above

		// Set preview mode to disable form submissions
		$GLOBALS['aiohm_booking_preview_mode'] = true;

		// Force frontend environment
		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		// Remove admin-specific actions that might interfere
		remove_action( 'wp_head', 'wp_admin_bar_header' );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );

		// Instead of custom HTML, let's create a proper WordPress page context
		$this->render_frontend_preview( $shortcode_type, $apply_colors, $current_brand_color, $current_text_color );
	}

	/**
	 * Render preview using WordPress frontend theme context
	 */
	private function render_frontend_preview( $shortcode_type, $apply_colors, $current_brand_color, $current_text_color ) {
		// Create a temporary query to simulate a page
		global $wp_query, $post;

		// Store original query
		$original_query = $wp_query;
		$original_post  = $post;

		// Create a fake post for the preview
		$fake_post                    = new stdClass();
		$fake_post->ID                = -1;
		$fake_post->post_title        = 'AIOHM Booking Preview';
		$fake_post->post_content      = $shortcode_type === 'events' ? '[aiohm_booking mode="tickets"]' : '[aiohm_booking mode="accommodations"]';
		$fake_post->post_type         = 'page';
		$fake_post->post_status       = 'publish';
		$fake_post->comment_status    = 'closed';
		$fake_post->ping_status       = 'closed';
		$fake_post->post_name         = 'aiohm-booking-preview';
		$fake_post->post_date         = current_time( 'mysql' );
		$fake_post->post_date_gmt     = current_time( 'mysql', 1 );
		$fake_post->post_modified     = current_time( 'mysql' );
		$fake_post->post_modified_gmt = current_time( 'mysql', 1 );
		$fake_post->post_parent       = 0;
		$fake_post->menu_order        = 0;
		$fake_post->post_author       = get_current_user_id();
		$fake_post->guid              = home_url( '/aiohm-booking-preview/' );

		// Convert to WP_Post object
		$post = new WP_Post( $fake_post );

		// Create new query
		$wp_query = new WP_Query(
			array(
				'post_type'   => 'page',
				'p'           => -1,
				'post_status' => 'publish',
			)
		);

		$wp_query->post          = $post;
		$wp_query->posts         = array( $post );
		$wp_query->post_count    = 1;
		$wp_query->found_posts   = 1;
		$wp_query->max_num_pages = 1;
		$wp_query->is_page       = true;
		$wp_query->is_singular   = true;
		$wp_query->is_single     = false;
		$wp_query->is_attachment = false;
		$wp_query->is_404        = false;
		$wp_query->is_home       = false;
		$wp_query->is_front_page = false;

		// Add color styles if requested
		if ( $apply_colors ) {
			add_action(
				'wp_head',
				function () use ( $current_brand_color, $current_text_color ) {
					echo '<style type="text/css">';
					echo ':root { ';
					echo '--ohm-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-booking-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-booking-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--aiohm-brand-color: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-text-color: ' . esc_attr( $current_text_color ) . ' !important; ';
					echo '--aiohm-text-color: ' . esc_attr( $current_text_color ) . ' !important; ';
					echo '} ';
					echo '.aiohm-booking-modern, .aiohm-booking-form, .aiohm-booking-event-selection-card { ';
					echo '--ohm-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-booking-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-booking-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--aiohm-brand-color: ' . esc_attr( $current_brand_color ) . ' !important; ';
					echo '--ohm-text-color: ' . esc_attr( $current_text_color ) . ' !important; ';
					echo '--aiohm-text-color: ' . esc_attr( $current_text_color ) . ' !important; ';
					echo '}';
					echo '</style>';
				},
				999
			);
		}

		// Add preview notice
		add_action(
			'wp_footer',
			function () {
				echo '<div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 8px 12px; border-radius: 4px; font-size: 12px; z-index: 9999;">Preview Mode - Form submissions disabled</div>';
			}
		);

		// Output using theme template
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'AIOHM Booking Preview', 'aiohm-booking-pro' ); ?></title>
			<?php
			// Load WordPress head (includes scripts and styles)
			wp_head();

			// Get current color settings if requested
			$current_brand_color = '#457d59';
			$current_text_color  = '#333333';

			if ( $apply_colors ) {
				if ( $shortcode_type === 'events' ) {
					$tickets_settings    = get_option( 'aiohm_booking_tickets_form_settings', array() );
					$current_brand_color = $tickets_settings['form_primary_color'] ?? '#457d59';
					$current_text_color  = $tickets_settings['form_text_color'] ?? '#333333';
				} else {
					$general_settings    = get_option( 'aiohm_booking_form_settings', array() );
					$main_settings       = get_option( 'aiohm_booking_settings', array() );
					$current_brand_color = $general_settings['form_primary_color'] ?? $main_settings['form_primary_color'] ?? $main_settings['brand_color'] ?? '#457d59';
					$current_text_color  = $general_settings['form_text_color'] ?? $main_settings['form_text_color'] ?? $main_settings['font_color'] ?? '#333333';
				}
			}
			?>
			<style>
				/* Minimal reset for clean preview */
				body {
					margin: 0;
					padding: 20px;
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					background: #f5f5f5;
				}
				/* Hide any admin-related elements that might appear */
				#wpadminbar, .admin-bar, .no-js {
					display: none !important;
				}
				
				/* Preview-specific event selection overrides - highest priority */
				.event-card,
				.accommodation-scroll-container .event-card {
					background: #ffffff !important;
					border: 2px solid #e5e7eb !important;
					border-radius: 8px !important;
					margin-bottom: 12px !important;
					overflow: visible !important;
					transition: all 0.3s ease !important;
					position: relative !important;
					min-height: 110px !important;
					padding: 15px !important;
				}

				.event-card:hover,
				.accommodation-scroll-container .event-card:hover {
					border-color: <?php echo esc_attr( $current_brand_color ); ?> !important;
					box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
					transform: translateY(-2px) !important;
					min-height: 300px !important;
				}

				.event-card-header,
				.accommodation-scroll-container .event-card-header {
					display: flex !important;
					justify-content: space-between !important;
					align-items: flex-start !important;
					gap: 12px !important;
					margin-bottom: 8px !important;
				}

				.event-basic-info {
					flex: 1 !important;
					min-width: 0 !important;
				}

				.event-title {
					font-size: 16px !important;
					font-weight: 600 !important;
					margin: 0 0 4px 0 !important;
					color: #1f2937 !important;
					line-height: 1.2 !important;
				}

				.event-date {
					font-size: 13px !important;
					color: #6b7280 !important;
					line-height: 1.3 !important;
				}

				.event-price {
					text-align: right !important;
					flex-shrink: 0 !important;
					display: flex !important;
					flex-direction: column !important;
					align-items: flex-end !important;
					gap: 4px !important;
				}

				.current-price {
					font-size: 18px !important;
					font-weight: 700 !important;
					color: <?php echo esc_attr( $current_brand_color ); ?> !important;
					line-height: 1 !important;
				}
				
				/* Apply current brand colors to main container */
				.aiohm-booking-modern {
					--ohm-primary: <?php echo esc_attr( $current_brand_color ); ?> !important;
					--ohm-primary-hover: <?php echo esc_attr( $current_brand_color ); ?> !important;
					--ohm-booking-primary: <?php echo esc_attr( $current_brand_color ); ?> !important;
					--ohm-booking-primary-hover: <?php echo esc_attr( $current_brand_color ); ?> !important;
					--ohm-text-color: <?php echo esc_attr( $current_text_color ); ?> !important;
				}

				.accommodation-scroll-container {
					max-height: 380px !important;
					overflow-y: auto !important;
					overflow-x: visible !important;
					padding: 15px !important;
					display: flex !important;
					flex-direction: column !important;
					gap: 12px !important;
					scroll-behavior: smooth !important;
					border: 1px solid #e5e7eb !important;
					border-radius: 8px !important;
					background: #f9fafb !important;
				}

				/* Mobile responsive for preview */
				@media (max-width: 768px) {
					.event-card,
					.accommodation-scroll-container .event-card {
						min-height: 110px !important;
						padding: 15px !important;
						margin-bottom: 12px !important;
					}
					
					.event-card-header,
					.accommodation-scroll-container .event-card-header {
						flex-direction: column !important;
						align-items: flex-start !important;
						gap: 8px !important;
					}
					
					.event-basic-info {
						width: 100% !important;
					}
					
					.event-price {
						width: 100% !important;
						align-items: flex-start !important;
						text-align: left !important;
						margin-left: 0 !important;
						margin-top: 8px !important;
					}
					
					.accommodation-scroll-container {
						max-height: 380px !important;
						padding: 15px !important;
					}
				}
			</style>
		</head>
		<body <?php body_class( 'aiohm-booking-preview' ); ?>>
			<div style="min-height: 100vh; padding: 20px; background: #f5f5f5;">
				<div class="container" style="max-width: 1200px; margin: 0 auto;">
					<?php
					// Set events context if needed
					if ( $shortcode_type === 'events' ) {
						global $aiohm_booking_events_context;
						$aiohm_booking_events_context = true;
					}

					// Output the content using WordPress content filters
					echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) );

					// Cleanup
					if ( $shortcode_type === 'events' ) {
						unset( $aiohm_booking_events_context );
					}
					?>
				</div>
			</div>
			
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php

		// Restore original query
		$wp_query = $original_query;
		$post     = $original_post;

		// Clean up
		unset( $GLOBALS['aiohm_booking_preview_mode'] );

		// Exit to prevent WordPress from adding extra content
		exit;
	}

	/**
	 * Create test page via AJAX
	 */
	public function create_test_page() {
		// Check nonce for security
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_create_test_page' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		$form_type = isset( $_POST['form_type'] ) ? sanitize_text_field( wp_unslash( $_POST['form_type'] ) ) : 'accommodations'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$page_slug  = $form_type === 'tickets' ? 'aiohm-events-preview' : 'aiohm-accommodations-preview';
		$page_title = $form_type === 'tickets' ? 'AIOHM Events Preview' : 'AIOHM Accommodations Preview';
		$shortcode  = $form_type === 'tickets' ? '[aiohm_booking mode="tickets"]' : '[aiohm_booking mode="accommodations"]';

		// Check if page already exists
		$existing_page = get_page_by_path( $page_slug );

		if ( $existing_page ) {
			$page_url = get_permalink( $existing_page->ID );
			wp_send_json_success(
				array(
					'message'  => 'Test page already exists!',
					'page_id'  => $existing_page->ID,
					'page_url' => $page_url,
				)
			);
		}

		// Create the page
		$page_content = sprintf(
			'
<!-- AIOHM Booking Test Page - Auto-generated -->
<div style="margin: 20px 0;">
	<p><strong>This is a test page for previewing the AIOHM booking form.</strong></p>
	<p>Form submissions are automatically disabled in preview mode.</p>
</div>

%s

<div style="margin: 20px 0; padding: 15px; background: #f0f8ff; border-left: 4px solid #0073aa;">
	<p><strong>Preview Information:</strong></p>
	<ul>
		<li>This page shows your booking form with your theme\'s full styling</li>
		<li>Form submissions are disabled for testing</li>
		<li>You can safely delete this page when done testing</li>
		<li>Colors and settings reflect your current configuration</li>
	</ul>
</div>
		',
			$shortcode
		);

		$page_data = array(
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_name'      => $page_slug,
			'post_author'    => get_current_user_id(),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'meta_input'     => array(
				'_aiohm_test_page' => '1',
				'_aiohm_form_type' => $form_type,
			),
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( 'Failed to create test page: ' . $page_id->get_error_message() );
		}

		$page_url = get_permalink( $page_id );

		wp_send_json_success(
			array(
				'message'  => 'Test page created successfully!',
				'page_id'  => $page_id,
				'page_url' => $page_url,
			)
		);
	}

	/**
	 * Handle preview page modifications
	 */
	public function handle_preview_page() {
		// Check if this is a preview request
		if ( ! isset( $_GET['aiohm_preview'] ) || sanitize_text_field( wp_unslash( $_GET['aiohm_preview'] ) ) !== '1' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preview check before nonce verification
			return;
		}

		// Verify nonce
		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in condition below
		if ( ! wp_verify_nonce( $nonce, 'aiohm_preview_colors' ) ) {
			return;
		}

		// Set preview mode
		$GLOBALS['aiohm_booking_preview_mode'] = true;

		$form_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'accommodations'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified above

		// Get current color settings
		$current_brand_color = '#457d59';
		$current_text_color  = '#333333';

		if ( $form_type === 'events' ) {
			$tickets_settings    = get_option( 'aiohm_booking_tickets_form_settings', array() );
			$current_brand_color = $tickets_settings['form_primary_color'] ?? '#457d59';
			$current_text_color  = $tickets_settings['form_text_color'] ?? '#333333';
		} else {
			$general_settings    = get_option( 'aiohm_booking_form_settings', array() );
			$main_settings       = get_option( 'aiohm_booking_settings', array() );
			$current_brand_color = $general_settings['form_primary_color'] ?? $main_settings['form_primary_color'] ?? $main_settings['brand_color'] ?? '#457d59';
			$current_text_color  = $general_settings['form_text_color'] ?? $main_settings['form_text_color'] ?? $main_settings['font_color'] ?? '#333333';
		}

		// Add styles to wp_head
		add_action(
			'wp_head',
			function () use ( $current_brand_color, $current_text_color ) {
				echo '<style type="text/css">';
				echo ':root { ';
				echo '--ohm-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-booking-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-booking-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-text-color: ' . esc_attr( $current_text_color ) . ' !important; ';
				echo '} ';
				echo '.aiohm-booking-modern { ';
				echo '--ohm-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-booking-primary: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-booking-primary-hover: ' . esc_attr( $current_brand_color ) . ' !important; ';
				echo '--ohm-text-color: ' . esc_attr( $current_text_color ) . ' !important; ';
				echo '}';
				echo '</style>';
			},
			999
		);

		// Add preview notice
		add_action(
			'wp_footer',
			function () {
				echo '<div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 8px 12px; border-radius: 4px; font-size: 12px; z-index: 9999;">🎭 Preview Mode - Form submissions disabled</div>';
			}
		);
	}

	/**
	 * AJAX handler for getting checkout HTML with booking ID
	 */
	public function ajax_get_checkout_html() {
		// Verify security using centralized helper (only nonce for frontend)
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'frontend_nonce' ) ) {
			return; // Error response already sent by helper
		}

		$booking_id = isset( $_POST['booking_id'] ) ? intval( wp_unslash( $_POST['booking_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		if ( empty( $booking_id ) ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID' ) );
		}

		// Generate checkout shortcode with booking ID
		$checkout_html = do_shortcode( '[aiohm_booking_checkout booking_id="' . $booking_id . '"]' );

		if ( empty( $checkout_html ) ) {
			wp_send_json_error( array( 'message' => 'Failed to generate checkout HTML' ) );
		}

		// Return the HTML as JSON response for proper handling
		wp_send_json_success( array( 'html' => $checkout_html ) );
	}

	/**
	 * AJAX handler for updating accommodation selection based on selected dates
	 */
	public function ajax_update_accommodation_selection() {
		// Verify security using centralized helper (only nonce for frontend)
		if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'frontend_nonce' ) ) {
			return; // Error response already sent by helper
		}

		$arrival_date   = isset( $_POST['arrival_date'] ) ? sanitize_text_field( wp_unslash( $_POST['arrival_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper
		$departure_date = isset( $_POST['departure_date'] ) ? sanitize_text_field( wp_unslash( $_POST['departure_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by security helper

		if ( empty( $arrival_date ) || empty( $departure_date ) ) {
			wp_send_json_error( array( 'message' => 'Missing date parameters' ) );
		}

		// Validate date formats
		if ( ! AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::validate_date( $arrival_date ) || ! AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::validate_date( $departure_date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid date format' ) );
		}

		// Set up template variables that the accommodation selection template expects
		$pricing       = get_option( 'aiohm_booking_pricing', array() );
		$product_names = get_option( 'aiohm_booking_product_names', array() );

		// Get accommodation module settings for default price
		$accommodation_settings = get_option( 'aiohm_booking_accommodation_settings', array() );

		// Get early bird settings from helper
		$early_bird_settings      = AIOHM_Booking_PROHelpersAIOHM_Booking_PROHelpersAIOHM_Booking_PROHelpersAIOHM_BOOKING_Early_Bird_Helper::get_accommodation_early_bird_settings();
		$enable_early_bird        = $early_bird_settings['enabled'];
		$early_bird_days          = $early_bird_settings['days'];
		$default_early_bird_price = $early_bird_settings['default_price'];

		// Set defaults.
		$singular = $product_names['singular_cap'] ?? 'Room';
		$plural   = $product_names['plural_cap'] ?? 'Rooms';

		// Get currency from general settings (not pricing settings) - same as event selection
		$global_settings = get_option( 'aiohm_booking_settings', array() );
		$currency        = $global_settings['currency'] ?? 'USD';

		$p = array( 'currency' => $currency );
		// Get base price from accommodation module settings first, then fallback to accommodation_price from pricing
		$base_acc_price = floatval( $accommodation_settings['default_price'] ?? $pricing['accommodation_price'] ?? 100 );

		// Get calendar colors for CSS variables.
		$admin_colors    = get_option( 'aiohm_booking_calendar_colors', array() );
		$default_colors  = array(
			'free'     => '#ffffff',
			'booked'   => '#e74c3c',
			'pending'  => '#f39c12',
			'external' => '#6c5ce7',
			'blocked'  => '#4b5563',
			'special'  => '#007cba',
			'private'  => '#28a745',
		);
		$calendar_colors = array_merge( $default_colors, $admin_colors );

		// Get brand color for calendar styling
		$main_settings = get_option( 'aiohm_booking_settings', array() );
		$brand_color   = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? '#457d59';

		// Get form settings for text and brand colors - match sandwich header logic
		$form_settings = get_option( 'aiohm_booking_form_settings', array() );

		// Simulate POST data for the accommodation selection template
		$_POST['arrival_date']   = $arrival_date;
		$_POST['departure_date'] = $departure_date;

		// Generate the accommodation selection HTML
		ob_start();
		include AIOHM_BOOKING_DIR . 'templates/partials/aiohm-booking-accommodation-selection.php';
		$html = ob_get_clean();

		// Clean up simulated POST data
		unset( $_POST['arrival_date'], $_POST['departure_date'] );

		if ( empty( $html ) ) {
			wp_send_json_error( array( 'message' => 'Failed to generate accommodation selection HTML' ) );
		}

		wp_send_json_success( array( 'html' => $html ) );
	}
}

// Register the module.
if ( class_exists( 'AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry' ) ) {
	AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::register_module( 'shortcode', 'AIOHM_BOOKING_Module_Shortcode_Admin' );
}
