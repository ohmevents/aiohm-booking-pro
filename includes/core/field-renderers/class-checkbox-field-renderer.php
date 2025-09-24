<?php

namespace AIOHM_Booking_PRO\Core\Field_Renderers;
/**
 * Checkbox Field Renderer
 *
 * Handles rendering and sanitization of checkbox input fields.
 * Includes inline label rendering for the checkbox.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkbox field renderer class
 */
class AIOHM_Booking_Checkbox_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the checkbox field HTML
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
		$checked  = checked( 1, $value, false );

		$attributes = array(
			'type'  => 'checkbox',
			'id'    => $field_id,
			'name'  => $field_name,
			'value' => '1',
			'class' => $classes,
		);

		// Add checked attribute if needed.
		if ( $checked ) {
			$attributes['checked'] = 'checked';
		}

		// Add disabled attribute if needed.
		if ( $disabled ) {
			$attributes['disabled'] = 'disabled';
		}

		// Add additional attributes if specified.
		if ( isset( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		$html = '<input ' . $this->get_attributes_html( $attributes ) . ' />';

		// Add inline label for checkbox.
		$description = $field['description'] ?? '';
		if ( $description ) {
			$html .= '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $description ) . '</label>';
		}

		return $html;
	}

	/**
	 * Sanitize the checkbox field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		// Checkbox values should be 1 or 0.
		return $value ? 1 : 0;
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'checkbox' );
	}
}
