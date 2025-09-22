<?php
/**
 * AIOHM Booking Form Handler
 *
 * Central form data provider and logic for all booking shortcodes.
 * Ensures consistent form behavior across events, accommodations, and mixed modes.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Form Class
 *
 * Provides unified form data and rendering logic for all booking shortcodes.
 * Handles form settings merging, field definitions, and context detection.
 *
 * @since 1.2.3
 */
class AIOHM_BOOKING_Form {

	/**
	 * Singleton instance.
	 *
	 * @var AIOHM_BOOKING_Form|null
	 */
	private static $instance = null;

	/**
	 * Current form context (events, accommodations, or mixed).
	 *
	 * @var string
	 */
	private $context = 'mixed';

	/**
	 * Merged form settings.
	 *
	 * @var array
	 */
	private $form_settings = array();

	/**
	 * Get singleton instance.
	 *
	 * @return AIOHM_BOOKING_Form
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize form handler.
	 */
	private function __construct() {
		$this->detect_context();
		$this->load_form_settings();
	}

	/**
	 * Detect current form context based on module status and global variables.
	 */
	private function detect_context() {
		global $aiohm_booking_events_context, $aiohm_booking_shortcode_override;

		// Check for shortcode override (used by specific shortcodes).
		if ( isset( $aiohm_booking_shortcode_override ) ) {
			$accommodations_enabled = $aiohm_booking_shortcode_override['enable_accommodations'];
			$tickets_enabled        = $aiohm_booking_shortcode_override['enable_tickets'];
		} else {
			// Check module status normally.
			$accommodations_enabled = aiohm_booking_is_module_enabled( 'accommodations' );
			$tickets_enabled        = aiohm_booking_is_module_enabled( 'tickets' );
		}

		// Determine context.
		if ( $tickets_enabled && ! $accommodations_enabled ) {
			$this->context = 'events';
		} elseif ( $accommodations_enabled && ! $tickets_enabled ) {
			$this->context = 'accommodations';
		} else {
			$this->context = 'mixed';
		}

		// Override for events shortcode context.
		if ( isset( $aiohm_booking_events_context ) ) {
			$this->context = 'events';
		}
	}

	/**
	 * Load and merge form settings based on context.
	 */
	private function load_form_settings() {
		// Get all setting sources.
		$global_settings        = get_option( 'aiohm_booking_settings', array() );
		$accommodation_settings = get_option( 'aiohm_booking_accommodations_form_settings', array() );
		$tickets_settings       = get_option( 'aiohm_booking_tickets_form_settings', array() );

		// Merge settings based on context with proper priority.
		switch ( $this->context ) {
			case 'events':
				// Events only - use tickets settings with global fallback.
				$this->form_settings = array_merge( $global_settings, $tickets_settings );
				break;

			case 'accommodations':
				// Accommodations only - use accommodation settings with global fallback.
				$this->form_settings = array_merge( $global_settings, $accommodation_settings );
				break;

			case 'mixed':
			default:
				// Mixed mode - merge all settings (global → accommodations → tickets).
				$this->form_settings = array_merge(
					$global_settings,
					$accommodation_settings,
					$tickets_settings
				);
				break;
		}
	}

	/**
	 * Get current form context.
	 *
	 * @return string Context: 'events', 'accommodations', or 'mixed'
	 */
	public function get_context() {
		return $this->context;
	}

	/**
	 * Get form customization data (title, subtitle, etc.).
	 *
	 * @return array Form customization settings
	 */
	public function get_form_customization() {
		return array(
			'fields' => $this->get_enabled_form_fields(),
		);
	}

	/**
	 * Get form URLs (checkout, thank you).
	 *
	 * @return array Form URL settings
	 */
	public function get_form_urls() {
		return array(
			'checkout_page_url' => $this->form_settings['checkout_page_url'] ?? '',
			'thankyou_page_url' => $this->form_settings['thankyou_page_url'] ?? '',
		);
	}

	/**
	 * Get enabled form fields with proper field definitions.
	 *
	 * @return array Enabled form fields with metadata
	 */
	public function get_enabled_form_fields() {
		$form_fields = array();

		// Get available fields based on context.
		$available_fields  = $this->get_available_fields();
		$field_definitions = $this->get_field_definitions();

		foreach ( $available_fields as $field_key ) {
			if ( ! empty( $this->form_settings[ 'form_field_' . $field_key ] ) ) {
				$form_fields[ $field_key ] = array_merge(
					$field_definitions[ $field_key ] ?? array(),
					array(
						'enabled'  => true,
						'required' => ! empty( $this->form_settings[ 'form_field_' . $field_key . '_required' ] ),
					)
				);
			}
		}

		return $form_fields;
	}

	/**
	 * Get available field keys based on context.
	 *
	 * @return array Available field keys
	 */
	private function get_available_fields() {
		switch ( $this->context ) {
			case 'events':
				// Tickets-specific fields.
				return array(
					'company',
					'phone',
					'dietary_requirements',
					'accessibility_needs',
					'emergency_contact',
					'special_requests',
				);

			case 'accommodations':
				// Accommodation-specific fields.
				return array(
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

			case 'mixed':
			default:
				// Combined fields from both contexts.
				return array(
					'address',
					'age',
					'company',
					'phone',
					'country',
					'arrival_time',
					'purpose',
					'dietary_requirements',
					'dietary_restrictions',
					'accessibility_needs',
					'emergency_contact',
					'passport_id',
					'special_requests',
				);
		}
	}

	/**
	 * Get field definitions with labels, types, placeholders, etc.
	 *
	 * @return array Field definitions
	 */
	private function get_field_definitions() {
		return array(
			'address'              => array(
				'label'       => __( 'Address', 'aiohm-booking-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Enter your address', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'country'              => array(
				'label'       => __( 'Country', 'aiohm-booking-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Enter your country', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'age'                  => array(
				'label'       => __( 'Age', 'aiohm-booking-pro' ),
				'type'        => 'number',
				'placeholder' => __( 'Enter your age', 'aiohm-booking-pro' ),
				'layout'      => 'half',
			),
			'company'              => array(
				'label'       => __( 'Company', 'aiohm-booking-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Enter company name', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'phone'                => array(
				'label'       => __( 'Phone Number', 'aiohm-booking-pro' ),
				'type'        => 'tel',
				'placeholder' => __( 'Enter phone number', 'aiohm-booking-pro' ),
				'layout'      => 'half',
			),
			'arrival_time'         => array(
				'label'       => __( 'Arrival Time', 'aiohm-booking-pro' ),
				'type'        => 'time',
				'placeholder' => __( 'Select arrival time', 'aiohm-booking-pro' ),
				'layout'      => 'half',
			),
			'purpose'              => array(
				'label'       => __( 'Purpose of Visit', 'aiohm-booking-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Business, leisure, etc.', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'dietary_requirements' => array(
				'label'       => __( 'Dietary Requirements', 'aiohm-booking-pro' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Special dietary needs', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'dietary_restrictions' => array(
				'label'       => __( 'Dietary Restrictions', 'aiohm-booking-pro' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Any dietary restrictions', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'accessibility_needs'  => array(
				'label'       => __( 'Accessibility Needs', 'aiohm-booking-pro' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Special accessibility requirements', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'emergency_contact'    => array(
				'label'       => __( 'Emergency Contact', 'aiohm-booking-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Name and phone number', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
			'passport_id'          => array(
				'label'       => __( 'Passport/ID Number', 'aiohm-booking-pro' ),
				'type'        => 'text',
				'placeholder' => __( 'Enter passport or ID number', 'aiohm-booking-pro' ),
				'layout'      => 'half',
			),
			'special_requests'     => array(
				'label'       => __( 'Special Requests', 'aiohm-booking-pro' ),
				'type'        => 'textarea',
				'placeholder' => __( 'Any special requests or notes', 'aiohm-booking-pro' ),
				'layout'      => 'full',
			),
		);
	}

	/**
	 * Get field order based on context and settings.
	 *
	 * @return array Ordered field keys
	 */
	public function get_field_order() {
		$default_order = array();

		switch ( $this->context ) {
			case 'events':
				$default_order = array( 'company', 'phone', 'dietary_requirements', 'accessibility_needs', 'emergency_contact', 'special_requests' );
				break;

			case 'accommodations':
				$default_order = array( 'address', 'country', 'age', 'company', 'phone', 'arrival_time', 'purpose', 'dietary_restrictions', 'accessibility_needs', 'emergency_contact', 'passport_id' );
				break;

			case 'mixed':
			default:
				$default_order = array( 'address', 'country', 'age', 'company', 'phone', 'arrival_time', 'purpose', 'dietary_requirements', 'accessibility_needs', 'emergency_contact', 'passport_id', 'special_requests' );
				break;
		}

		// Get saved field order or use default.
		$field_order = $this->form_settings['field_order'] ?? $default_order;

		if ( ! is_array( $field_order ) ) {
			$field_order = ! empty( $field_order ) ? explode( ',', $field_order ) : $default_order;
		}

		return $field_order;
	}

	/**
	 * Check if private/full booking is allowed.
	 *
	 * @return bool Whether private booking is allowed
	 */
	public function is_private_booking_allowed() {
		return ! empty( $this->form_settings['allow_private_all'] ) ||
				! empty( $this->form_settings['accommodations_allow_private'] );
	}

	/**
	 * Get all form data for template usage.
	 *
	 * @return array Complete form data
	 */
	public function get_form_data() {
		return array(
			'context'       => $this->get_context(),
			'customization' => $this->get_form_customization(),
			'urls'          => $this->get_form_urls(),
			'fields'        => $this->get_enabled_form_fields(),
			'field_order'   => $this->get_field_order(),
			'allow_private' => $this->is_private_booking_allowed(),
			'raw_settings'  => $this->form_settings, // For backward compatibility.
		);
	}

	/**
	 * Render form template partial.
	 *
	 * @param array $additional_data Additional data to pass to template.
	 * @return string Rendered form HTML
	 */
	public function render_form( $additional_data = array() ) {
		$form_data = array_merge( $this->get_form_data(), $additional_data );

		ob_start();

		$template_path = AIOHM_BOOKING_DIR . 'templates/partials/booking-form.php';

		if ( file_exists( $template_path ) ) {
			// Extract data for template use.
			extract( $form_data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template_path;
		} else {
			echo '<div class="aiohm-booking-error">' .
				esc_html__( 'Booking form template not found.', 'aiohm-booking-pro' ) .
				'</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Reset instance (useful for testing or context changes).
	 */
	public static function reset() {
		self::$instance = null;
	}
}
