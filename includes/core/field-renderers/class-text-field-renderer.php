<?php

namespace AIOHM_Booking_PRO\Core\Field_Renderers;
/**
 * Text Field Renderer
 *
 * Handles rendering and sanitization of text-based input fields.
 * Supports text, email, url, and password field types.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Text field renderer class
 */
class AIOHM_Booking_Text_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the text field HTML
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public function render( $field_id, $field, $value, $field_name ) {
		$type        = $field['type'] ?? 'text';
		$classes     = $this->get_field_classes( $field );
		$disabled    = $this->is_field_disabled( $field ) ? 'disabled' : '';
		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		$attributes = array(
			'type'        => $type,
			'id'          => $field_id,
			'name'        => $field_name,
			'value'       => $value,
			'class'       => $classes,
			'placeholder' => $placeholder,
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
		$html .= $this->render_description( $field, $type );

		return $html;
	}

	/**
	 * Sanitize the text field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		$type = $field['type'] ?? 'text';

		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'password':
				// Don't sanitize passwords, just ensure they're strings.
				return is_string( $value ) ? $value : '';

			case 'text':
			default:
				return $this->default_sanitize( $value, $field );
		}
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'text', 'email', 'url', 'password' );
	}
}
