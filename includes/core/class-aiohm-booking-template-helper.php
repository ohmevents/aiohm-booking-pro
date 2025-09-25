<?php
/**
 * Template Helper Class
 * Provides data and logic without cluttering templates.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Helper Class
 */
class AIOHM_BOOKING_Template_Helper {

	/**
	 * Singleton instance.
	 *
	 * @var AIOHM_BOOKING_Template_Helper|null
	 */
	private static $instance = null;

	/**
	 * Plugin settings.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AIOHM_BOOKING_Template_Helper
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize template helper.
	 */
	private function __construct() {
		$this->settings = get_option( 'aiohm_booking_settings', array() );
	}

	/**
	 * Get form styling data.
	 */
	public function get_form_styling() {
		// Get unified brand color from main settings first, then fall back to context-specific settings
		$main_settings       = get_option( 'aiohm_booking_settings', array() );
		$unified_brand_color = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? null;

		// Use unified color if available, otherwise fall back to context-specific settings
		$primary_color = sanitize_hex_color( $unified_brand_color ?? $this->settings['form_primary_color'] ?? $this->settings['brand_color'] ?? '#6b9d7a' );
		if ( empty( $primary_color ) ) {
			$primary_color = '#6b9d7a';
		}

		$text_color = sanitize_hex_color( $this->settings['form_text_color'] ?? $this->settings['font_color'] ?? '#1a1a1a' );

		// Convert hex colors to RGB for rgba() usage.
		$hex = ltrim( $primary_color, '#' );
		if ( strlen( $hex ) === 6 ) {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		} else {
			$r = 107; // Default fallback.
			$g = 107; // Default fallback.
			$b = 107; // Default fallback.
		}
		$primary_light = "rgba({$r}, {$g}, {$b}, 0.1)";

		// Convert text color to RGB.
		$text_hex = ltrim( $text_color, '#' );
		if ( strlen( $text_hex ) === 6 ) {
			$text_r = hexdec( substr( $text_hex, 0, 2 ) );
			$text_g = hexdec( substr( $text_hex, 2, 2 ) );
			$text_b = hexdec( substr( $text_hex, 4, 2 ) );
		} else {
			$text_r = 26; // Default fallback.
			$text_g = 26; // Default fallback.
			$text_b = 26; // Default fallback.
		}

		return array(
			'primary_color'  => $primary_color,
			'text_color'     => $text_color,
			'text_color_rgb' => "{$text_r}, {$text_g}, {$text_b}",
			'primary_light'  => $primary_light,
			'instance_id'    => uniqid( 'aiohm-booking-' ),
		);
	}

	/**
	 * Get module enablement status.
	 */
	public function get_module_status() {
		// Check for shortcode overrides.
		if ( isset( $GLOBALS['aiohm_booking_shortcode_override'] ) ) {
			$override = $GLOBALS['aiohm_booking_shortcode_override'];
			return array(
				'accommodations_enabled' => $override['enable_accommodations'],
				'tickets_enabled'        => $override['enable_tickets'],
			);
		}

		return array(
			'accommodations_enabled' => aiohm_booking_is_module_enabled( 'accommodations' ),
			'tickets_enabled'        => aiohm_booking_is_module_enabled( 'tickets' ),
		);
	}

	/**
	 * Get pricing information.
	 */
	public function get_pricing_data() {
		// Get accommodation module for default pricing.
		$accommodation_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'accommodations' );
		$default_price        = 0;
		if ( $accommodation_module && method_exists( $accommodation_module, 'get_module_settings' ) ) {
			$module_settings = $accommodation_module->get_module_settings();
			$default_price   = floatval( $module_settings['default_price'] ?? 0 );
		}

		return array(
			'prices'                   => $this->get_price_settings(),
			'accommodation_price'      => floatval( $this->settings['accommodation_price'] ?? $default_price ),
			'available_accommodations' => intval( $this->settings['available_accommodations'] ?? 10 ),
			'accommodations'           => $this->get_accommodation_data(),
		);
	}

	/**
	 * Get price settings for compatibility.
	 */
	private function get_price_settings() {
		return array(
			'currency'          => $this->settings['currency'] ?? 'EUR',
			'ticket_price'      => floatval( $this->settings['tickets_price'] ?? 0 ),
			'deposit_percent'   => intval( $this->settings['deposit_percentage'] ?? $this->settings['deposit_percent'] ?? 0 ),
			'allow_private_all' => ! empty( $this->settings['accommodations_allow_private'] ) || ! empty( $this->settings['allow_private_all'] ),
			'earlybird_days'    => intval( $this->settings['early_bird_days'] ?? $this->settings['earlybird_days'] ?? 30 ),
		);
	}

	/**
	 * Get product names for dynamic display.
	 */
	public function get_product_names() {
		// Get accommodation type from settings (matches accommodation module approach).
		$accommodation_type = $this->settings['accommodation_type'] ?? 'room';

		$singular = aiohm_booking_get_accommodation_singular_name( $accommodation_type );
		$plural   = aiohm_booking_get_accommodation_plural_name( $accommodation_type );

		return array(
			'singular'       => strtolower( $singular ),
			'plural'         => strtolower( $plural ),
			'singular_cap'   => $singular,
			'plural_cap'     => $plural,
			'singular_lower' => strtolower( $singular ),
			'plural_lower'   => strtolower( $plural ),
		);
	}

	/**
	 * Get form customization settings.
	 */
	public function get_form_customization() {
		// Check if this is for events/tickets context.
		global $aiohm_booking_events_context;
		$is_events_context = isset( $aiohm_booking_events_context );

		if ( $is_events_context ) {
			// Use tickets-specific form settings.
			$tickets_form_settings = get_option( 'aiohm_booking_tickets_form_settings', array() );
			return array(
				'fields' => $this->get_enabled_form_fields( true ), // Pass true for tickets context.
			);
		}

		// Default to accommodation settings.
		return array(
			'fields' => $this->get_enabled_form_fields(),
		);
	}

	/**
	 * Get enabled form fields.
	 *
	 * @param bool $is_tickets_context Whether this is for tickets/events context.
	 */
	public function get_enabled_form_fields( $is_tickets_context = false ) {
		$form_fields = array();

		if ( $is_tickets_context ) {
			// Use tickets-specific form settings and fields.
			$tickets_form_settings = get_option( 'aiohm_booking_tickets_form_settings', array() );

			// Define available form fields for tickets (from tickets module).
			$available_fields = array(
				'company',
				'phone',
				'dietary_requirements',
				'accessibility_needs',
				'emergency_contact',
				'special_requests',
			);

			foreach ( $available_fields as $field ) {
				if ( ! empty( $tickets_form_settings[ 'form_field_' . $field ] ) ) {
					$form_fields[ $field ] = array(
						'enabled'  => true,
						'required' => ! empty( $tickets_form_settings[ 'form_field_' . $field . '_required' ] ),
					);
				}
			}
		} else {
			// Define available form fields for accommodations.
			$available_fields = array(
				'address',
				'age',
				'company',
				'country',
				'arrival_time',
				'purpose',
				'dietary_restrictions',
				'accessibility_needs',
				'emergency_contact',
				'passport_id',
			);

			foreach ( $available_fields as $field ) {
				if ( ! empty( $this->settings[ 'form_field_' . $field ] ) ) {
					$form_fields[ $field ] = array(
						'enabled'  => true,
						'required' => ! empty( $this->settings[ 'form_field_' . $field . '_required' ] ),
					);
				}
			}
		}

		return $form_fields;
	}

	/**
	 * Get form data settings.
	 */
	public function get_form_data() {
		// Check if this is for events/tickets context.
		global $aiohm_booking_events_context;
		$is_events_context = isset( $aiohm_booking_events_context );

		if ( $is_events_context ) {
			// Use tickets-specific form settings.
			$tickets_form_settings = get_option( 'aiohm_booking_tickets_form_settings', array() );
			return array(
				'checkout_page_url' => $tickets_form_settings['checkout_page_url'] ?? '',
				'thankyou_page_url' => $tickets_form_settings['thankyou_page_url'] ?? '',
			);
		}

		// Default to accommodation settings.
		return array(
			'checkout_page_url' => $this->settings['checkout_page_url'] ?? '',
			'thankyou_page_url' => $this->settings['thankyou_page_url'] ?? '',
		);
	}

	/**
	 * Get order data for checkout template.
	 *
	 * @param int $order_id The order ID to retrieve.
	 */
	public function get_order_data( $order_id ) {
		global $wpdb;

		$order = null;
		if ( $order_id ) {
			$table_name = $wpdb->prefix . 'aiohm_booking_order';
			$order      = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for order lookup in custom table
				$wpdb->prepare(
					'SELECT * FROM %s WHERE id = %d',
					$table_name,
					absint( $order_id )
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
		}

		return $order;
	}

	/**
	 * Get payment gateway status.
	 */
	public function get_payment_status() {
		$status = array(
			'currency' => $this->settings['currency'] ?? 'EUR',
		);

		// Payment module status will be handled by their respective modules

		return $status;
	}

	/**
	 * Get accommodation data from posts.
	 */
	public function get_accommodation_data() {
		// Get the accommodation module instance.
		$registry             = AIOHM_BOOKING_Module_Registry::instance();
		$accommodation_module = $registry->get_module( 'accommodations' );

		if ( ! $accommodation_module ) {
			return array();
		}

		// Get available accommodations count from settings.
		$available_accommodations = intval( $this->settings['available_accommodations'] ?? 1 );

		// Get accommodation posts using the module's cached method.
		$accommodation_posts = $accommodation_module->get_cached_accommodations( $available_accommodations );

		// Convert posts to display data.
		$accommodations = array();
		foreach ( $accommodation_posts as $post ) {
			$accommodations[] = $accommodation_module->get_accommodation_display_data( $post );
		}

		return $accommodations;
	}

	/**
	 * Get accommodation type display names.
	 */
	public function get_accommodation_labels() {
		$type = $this->settings['accommodations_product_name'] ?? 'accommodation';

		return array(
			'singular'     => $type,
			'plural'       => $type . 's',
			'singular_cap' => ucfirst( $type ),
			'plural_cap'   => ucfirst( $type ) . 's',
		);
	}

	/**
	 * Get booking mode label for display.
	 *
	 * @param string $mode The booking mode.
	 */
	public function get_booking_mode_label( $mode ) {
		$labels = $this->get_accommodation_labels();

		switch ( $mode ) {
			case 'accommodations':
				return $labels['plural_cap'];
			case 'tickets':
				return function_exists( '__' ) && did_action( 'init' ) ? __( 'Tickets', 'aiohm-booking-pro' ) : 'Tickets';
			case 'both':
				return function_exists( '__' ) && did_action( 'init' ) ? __( 'Mixed', 'aiohm-booking-pro' ) : 'Mixed';
			default:
				return ucfirst( $mode );
		}
	}

	/**
	 * Generate CSS custom properties for styling.
	 */
	public function get_css_custom_properties() {
		$styling = $this->get_form_styling();

		return sprintf(
			'--ohm-primary: %s; --ohm-primary-hover: %s; --ohm-primary-light: %s; --ohm-booking-primary: %s; --ohm-booking-primary-hover: %s; --ohm-booking-primary-light: %s; --ohm-text-color: %s; --ohm-text-color-rgb: %s;',
			$styling['primary_color'],
			$styling['primary_color'],
			$styling['primary_light'],
			$styling['primary_color'],
			$styling['primary_color'],
			$styling['primary_light'],
			$styling['text_color'],
			$styling['text_color_rgb']
		);
	}

	/**
	 * Check if any modules are enabled.
	 */
	public function has_enabled_modules() {
		$status = $this->get_module_status();
		return $status['accommodations_enabled'] || $status['tickets_enabled'];
	}

	/**
	 * Get template data for widget.
	 */
	public function get_widget_data() {
		return array(
			'styling'        => $this->get_form_styling(),
			'modules'        => $this->get_module_status(),
			'pricing'        => $this->get_pricing_data(),
			'product_names'  => $this->get_product_names(),
			'customization'  => $this->get_form_customization(),
			'css_properties' => $this->get_css_custom_properties(),
			'form_data'      => $this->get_form_data(),
		);
	}

	/**
	 * Get or create preview page for testing
	 *
	 * @param string $form_type The type of form ('tickets' or 'accommodations').
	 * @return WP_Post|null The page object or null if not found.
	 */
	public static function get_or_create_preview_page( $form_type ) {
		$page_slug = $form_type === 'tickets' ? 'aiohm-events-preview' : 'aiohm-accommodations-preview';

		// Check if page already exists.
		$existing_page = get_page_by_path( $page_slug );

		if ( $existing_page ) {
			return $existing_page;
		}

		return null; // Don't auto-create, let user click button.
	}

	/**
	 * Get template data for checkout.
	 *
	 * @param int $order_id The order ID.
	 */
	public function get_checkout_data( $order_id ) {
		$order = $this->get_order_data( $order_id );

		return array(
			'order'              => $order,
			'payment'            => $this->get_payment_status(),
			'modules'            => $this->get_module_status(),
			'labels'             => $this->get_accommodation_labels(),
			'booking_mode_label' => $order ? $this->get_booking_mode_label( $order->mode ?? 'booking' ) : '',
		);
	}

	/**
	 * Render template with data.
	 *
	 * @param string $template_name The template name to render.
	 * @param array  $data The data to pass to the template.
	 */
	public function render_template( $template_name, $data = array() ) {
		// Sanitize template name to prevent directory traversal.
		$template_name = str_replace( '..', '', $template_name );

		// Check if .php is already appended, if not, add it.
		if ( substr( $template_name, -4 ) !== '.php' ) {
			$template_name .= '.php';
		}

		$template_path = AIOHM_BOOKING_DIR . "templates/{$template_name}";

		if ( ! file_exists( $template_path ) ) {
			return false;
		}

		// Extract data as variables.
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Include template.
		include $template_path;

		return true;
	}

	/**
	 * Get safe template data (sanitized for output).
	 *
	 * @param mixed $data The data to sanitize.
	 */
	public function get_safe_data( $data ) {
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'get_safe_data' ), $data );
		} elseif ( is_string( $data ) ) {
			return esc_html( $data );
		} elseif ( is_numeric( $data ) ) {
			return $data;
		}

		return $data;
	}

	/**
	 * Clear cache.
	 */
	public function clear_cache() {
		$this->settings = null;
		$this->settings = get_option( 'aiohm_booking_settings', array() );
	}

	/**
	 * Render form customization template with error handling
	 * Consolidates duplicate error handling code from modules.
	 *
	 * @param array $template_data The template data to render.
	 */
	public function render_form_customization_template( $template_data ) {
		$rendered = $this->render_template( 'partials/aiohm-booking-form-customization.php', $template_data );
		if ( ! $rendered ) {
			$template_path = str_replace( ABSPATH, '', AIOHM_BOOKING_DIR ) . 'templates/partials/aiohm-booking-form-customization.php';
			echo '<div class="notice notice-error" style="border-left-color: #d63638; padding: 15px;">';
			echo '<p style="font-weight: bold; font-size: 1.1em; color: #d63638;">' . esc_html__( 'CRITICAL TEMPLATE ERROR', 'aiohm-booking-pro' ) . '</p>';
			echo '<p>' . esc_html__( 'The form customization template partial could not be found. This section cannot be displayed.', 'aiohm-booking-pro' ) . '</p>';
			echo '<p><strong>' . esc_html__( 'Please ensure this file exists:', 'aiohm-booking-pro' ) . '</strong><br><code>' . esc_html( $template_path ) . '</code></p>';
			echo '</div>';
		}
	}
}
