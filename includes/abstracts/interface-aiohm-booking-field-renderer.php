<?php
/**
 * Field Renderer Interface
 *
 * Defines the contract for field renderer classes in the AIOHM Booking system.
 * Each field type has its own renderer that implements this interface.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for field renderers
 */
interface AIOHM_Booking_Field_Renderer_Interface {

	/**
	 * Render the field HTML
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public function render( $field_id, $field, $value, $field_name );

	/**
	 * Sanitize the field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field );

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types();
}
