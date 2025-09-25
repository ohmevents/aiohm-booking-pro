<?php
/**
 * Custom Field Renderer
 *
 * Handles rendering and sanitization of custom fields with callbacks.
 * Allows for complex field types that require custom rendering logic.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom field renderer class
 */
class AIOHM_Booking_Custom_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the custom field HTML
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public function render( $field_id, $field, $value, $field_name ) {
		// Handle custom field types with callback.
		if ( isset( $field['callback'] ) && is_callable( $field['callback'] ) ) {
			ob_start();
			call_user_func( $field['callback'], $field_id, $field, $value, $field_name );
			return ob_get_clean();
		}

		// Fallback for custom fields without callback.
		return '<p class="description">Custom field configuration error: No callback provided.</p>';
	}

	/**
	 * Sanitize the custom field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		// Custom fields should handle their own sanitization.
		// Check for custom sanitize callback.
		if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
			return call_user_func( $field['sanitize_callback'], $value, $field );
		}

		// If no custom sanitization, skip (custom fields handle their own data).
		return $value;
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'custom' );
	}
}
