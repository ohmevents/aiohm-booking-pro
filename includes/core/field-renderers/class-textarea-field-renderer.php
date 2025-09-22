<?php
/**
 * Textarea Field Renderer
 *
 * Handles rendering and sanitization of textarea fields.
 * Supports rows, cols, and placeholder attributes.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Textarea field renderer class
 */
class AIOHM_Booking_Textarea_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the textarea field HTML
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public function render( $field_id, $field, $value, $field_name ) {
		$classes     = $this->get_field_classes( $field );
		$disabled    = $this->is_field_disabled( $field ) ? 'disabled' : '';
		$rows        = $field['rows'] ?? 4;
		$cols        = $field['cols'] ?? 50;
		$placeholder = $field['placeholder'] ?? '';

		$attributes = array(
			'id'          => $field_id,
			'name'        => $field_name,
			'rows'        => $rows,
			'cols'        => $cols,
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

		$html  = '<textarea ' . $this->get_attributes_html( $attributes ) . '>' . esc_textarea( $value ) . '</textarea>';
		$html .= $this->render_description( $field, 'textarea' );

		return $html;
	}

	/**
	 * Sanitize the textarea field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		// Check if we should allow HTML.
		if ( isset( $field['allow_html'] ) && $field['allow_html'] ) {
			return wp_kses_post( $value );
		}

		return sanitize_textarea_field( $value );
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'textarea' );
	}
}
