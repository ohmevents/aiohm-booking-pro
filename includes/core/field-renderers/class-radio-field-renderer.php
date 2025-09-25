<?php
/**
 * Radio Field Renderer
 *
 * Handles rendering and sanitization of radio button fields.
 * Renders multiple radio buttons based on the options array.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Radio field renderer class
 */
class AIOHM_Booking_Radio_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the radio field HTML
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
		$html     = '';

		// Render radio buttons.
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			$html .= '<fieldset>';

			foreach ( $field['options'] as $option_value => $option_label ) {
				$radio_id = $field_id . '_' . $option_value;
				$checked  = checked( $value, $option_value, false );

				$attributes = array(
					'type'  => 'radio',
					'id'    => $radio_id,
					'name'  => $field_name,
					'value' => $option_value,
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

				$html .= '<label>';
				$html .= '<input ' . $this->get_attributes_html( $attributes ) . ' />';
				$html .= '<span>' . esc_html( $option_label ) . '</span>';
				$html .= '</label><br>';
			}

			$html .= '</fieldset>';
		}

		$html .= $this->render_description( $field, 'radio' );

		return $html;
	}

	/**
	 * Sanitize the radio field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		// Validate that the value is one of the allowed options.
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			$valid_options = array_keys( $field['options'] );

			if ( ! in_array( $value, $valid_options, true ) ) {
				// Return the default value if invalid.
				return $field['default'] ?? '';
			}
		}

		return $this->default_sanitize( $value, $field );
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'radio' );
	}
}
