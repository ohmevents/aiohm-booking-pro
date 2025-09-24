<?php

namespace AIOHM_Booking_PRO\Core\Field_Renderers;
/**
 * Select Field Renderer
 *
 * Handles rendering and sanitization of select dropdown fields.
 * Supports single and multiple selection options.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

// phpcs:disable WordPress.Files.FileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Select field renderer class
 */
class AIOHM_Booking_Select_Field_Renderer extends AIOHM_Booking_Field_Renderer_Abstract {

	/**
	 * Render the select field HTML
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
			'id'    => $field_id,
			'name'  => $field_name,
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

		$html = '<select ' . $this->get_attributes_html( $attributes ) . '>';

		// Render options.
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			$html .= $this->render_options( $field['options'], $value );
		}

		$html .= '</select>';
		$html .= $this->render_description( $field, 'select' );

		return $html;
	}

	/**
	 * Render select options
	 *
	 * @param array $options The options array.
	 * @param mixed $current_value The currently selected value.
	 * @return string The options HTML.
	 */
	private function render_options( $options, $current_value ) {
		$html = '';

		foreach ( $options as $option_value => $option_label ) {
			// Handle option groups.
			if ( is_array( $option_label ) ) {
				$html .= '<optgroup label="' . esc_attr( $option_value ) . '">';
				foreach ( $option_label as $group_value => $group_label ) {
					$selected = selected( $current_value, $group_value, false );
					$html    .= '<option value="' . esc_attr( $group_value ) . '" ' . $selected . '>' . esc_html( $group_label ) . '</option>';
				}
				$html .= '</optgroup>';
			} else {
				$selected = selected( $current_value, $option_value, false );
				$html    .= '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>' . esc_html( $option_label ) . '</option>';
			}
		}

		return $html;
	}

	/**
	 * Sanitize the select field value
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, $field ) {
		// Validate that the value is one of the allowed options.
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			$valid_options = $this->get_valid_option_values( $field['options'] );

			if ( ! in_array( $value, $valid_options, true ) ) {
				// Return the default value if invalid.
				return $field['default'] ?? '';
			}
		}

		return $this->default_sanitize( $value, $field );
	}

	/**
	 * Get all valid option values from the options array
	 *
	 * @param array $options The options array.
	 * @return array Array of valid option values.
	 */
	private function get_valid_option_values( $options ) {
		$valid_values = array();

		foreach ( $options as $option_value => $option_label ) {
			if ( is_array( $option_label ) ) {
				// Handle option groups.
				$valid_values = array_merge( $valid_values, array_keys( $option_label ) );
			} else {
				$valid_values[] = $option_value;
			}
		}

		return $valid_values;
	}

	/**
	 * Get the field types this renderer can handle
	 *
	 * @return array Array of field types this renderer supports.
	 */
	public function get_supported_types() {
		return array( 'select' );
	}
}
