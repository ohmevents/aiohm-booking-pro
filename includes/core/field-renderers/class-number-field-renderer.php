<?php
/**
 * Number Field Renderer
 *
 * Handles rendering and sanitization of number input fields.
 * Supports min, max, and step attributes.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Number field renderer class
 */
class AIOHM_Booking_Number_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the number field HTML
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

		$attributes = array(
			'type'  => 'number',
			'id'    => $field_id,
			'name'  => $field_name,
			'value' => $value,
			'class' => $classes,
		);

		// Add number-specific attributes.
		if ( isset( $field['min'] ) ) {
			$attributes['min'] = $field['min'];
		}

		if ( isset( $field['max'] ) ) {
			$attributes['max'] = $field['max'];
		}

		if ( isset( $field['step'] ) ) {
			$attributes['step'] = $field['step'];
		}

		if ( isset( $field['placeholder'] ) ) {
			$attributes['placeholder'] = $field['placeholder'];
		}

		// Add disabled attribute if needed.
		if ( $disabled ) {
			$attributes['disabled'] = 'disabled';
		}

		// Add additional attributes if specified.
		if ( isset( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		$html  = '<input ' . $this->get_attributes_html( $attributes ) . ' />';
		$html .= $this->render_description( $field, 'number' );

		return $html;
	}

	/**
	 * Sanitize the number field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		$sanitized = floatval( $value );

		// Apply min/max constraints if specified.
		if ( isset( $field['min'] ) && $sanitized < $field['min'] ) {
			$sanitized = $field['min'];
		}

		if ( isset( $field['max'] ) && $sanitized > $field['max'] ) {
			$sanitized = $field['max'];
		}

		return $sanitized;
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'number' );
	}
}
