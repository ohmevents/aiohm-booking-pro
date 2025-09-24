<?php

namespace AIOHM_Booking_PRO\Core\Field_Renderers;
/**
 * Hidden Field Renderer
 *
 * Handles rendering and sanitization of hidden input fields.
 * Does not render descriptions since hidden fields are not visible.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hidden field renderer class
 */
class AIOHM_Booking_Hidden_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the hidden field HTML
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public function render( $field_id, $field, $value, $field_name ) {
		$attributes = array(
			'type'  => 'hidden',
			'id'    => $field_id,
			'name'  => $field_name,
			'value' => $value,
		);

		// Add additional attributes if specified.
		if ( isset( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			$attributes = array_merge( $attributes, $field['attributes'] );
		}

		// Hidden fields don't show descriptions.
		return '<input ' . $this->get_attributes_html( $attributes ) . ' />';
	}

	/**
	 * Sanitize the hidden field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		return $this->default_sanitize( $value, $field );
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'hidden' );
	}
}
