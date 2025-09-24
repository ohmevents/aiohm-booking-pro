<?php

namespace AIOHM_Booking_PRO\Core\Field_Renderers;
/**
 * Color Field Renderer
 *
 * Handles rendering and sanitization of color picker fields.
 * Uses HTML5 color input type for native color picker support.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color field renderer class
 */
class AIOHM_Booking_Color_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the color field HTML
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public function render( $field_id, $field, $value, $field_name ) {
		$classes  = $this->get_field_classes( $field );
		$disabled = $this->is_field_disabled( $field ) ? 'disabled' : '';

		// Ensure the value is a valid hex color.
		$value = $this->validate_color_value( $value );

		$attributes = array(
			'type'  => 'color',
			'id'    => $field_id,
			'name'  => $field_name,
			'value' => $value,
			'class' => $classes,
		);

		// Add disabled attribute if needed.
		if ( $disabled ) {
			$attributes['disabled'] = 'disabled';
		}

		// Add additional attributes if specified.
		if ( isset( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		$html  = '<input ' . $this->get_attributes_html( $attributes ) . ' />';
		$html .= $this->render_description( $field, 'color' );

		return $html;
	}

	/**
	 * Sanitize the color field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		return $this->validate_color_value( $value );
	}

	/**
	 * Validate and format color value
	 *
	 * @param string $value The color value to validate.
	 * @return string Valid hex color value.
	 */
	private function validate_color_value( $value ) {
		// Use centralized color validation.
		return AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Validation::sanitize_hex_color( $value );
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'color' );
	}
}
