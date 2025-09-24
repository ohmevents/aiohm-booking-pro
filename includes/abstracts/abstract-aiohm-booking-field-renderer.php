<?php

namespace AIOHM_Booking_PRO\Abstracts;
/**
 * Abstract Field Renderer Base Class
 *
 * Provides common functionality for field renderers in the AIOHM Booking system.
 * All field renderers should extend this class.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for field renderers
 */
abstract class AIOHM_Booking_Field_Renderer_Abstract implements AIOHM_Booking_Field_Renderer_Interface {

	/**
	 * Render field description if present
	 *
	 * @param array  $field      The field configuration.
	 * @param string $field_type The field type (to handle special cases).
	 * @return string The description HTML or empty string.
	 */
	protected function render_description( $field, $field_type = '' ) {
		// For checkbox fields, description is rendered inline, so skip here.
		if ( 'checkbox' === $field_type ) {
			return '';
		}

		// For custom fields, let them handle their own descriptions.
		if ( 'custom' === $field_type ) {
			return '';
		}

		if ( isset( $field['description'] ) && ! empty( $field['description'] ) ) {
			return '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}

		return '';
	}

	/**
	 * Get field attributes as HTML string
	 *
	 * @param array $attributes Array of attributes.
	 * @return string HTML attributes string.
	 */
	protected function get_attributes_html( $attributes ) {
		$html_attributes = array();

		foreach ( $attributes as $key => $value ) {
			if ( null !== $value ) {
				$html_attributes[] = esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return implode( ' ', $html_attributes );
	}

	/**
	 * Get CSS classes for the field
	 *
	 * @param array $field The field configuration.
	 * @return string CSS classes string.
	 */
	protected function get_field_classes( $field ) {
		$classes = array();

		// Add default class based on field type.
		$classes[] = 'aiohm-field';

		// Add custom classes if specified.
		if ( isset( $field['class'] ) ) {
			$classes[] = $field['class'];
		}

		// Add WordPress default classes for certain field types.
		if ( isset( $field['type'] ) ) {
			switch ( $field['type'] ) {
				case 'text':
				case 'email':
				case 'url':
				case 'password':
				case 'number':
					$classes[] = 'regular-text';
					break;
				case 'textarea':
					$classes[] = 'large-text';
					break;
			}
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Check if field is disabled
	 *
	 * @param array $field The field configuration.
	 * @return bool Whether the field is disabled.
	 */
	protected function is_field_disabled( $field ) {
		return isset( $field['disabled'] ) && $field['disabled'];
	}

	/**
	 * Default sanitization fallback
	 *
	 * @param mixed $value The value to sanitize.
	 * @param array $field The field configuration.
	 * @return mixed Sanitized value.
	 */
	protected function default_sanitize( $value, $field ) {
		// Check if field has custom sanitization callback.
		if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
			return call_user_func( $field['sanitize_callback'], $value );
		}

		// Default to text field sanitization.
		return sanitize_text_field( $value );
	}
}
