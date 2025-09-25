<?php
/**
 * Field Renderer Factory
 *
 * Factory class for creating field renderer instances.
 * Manages the registry of field types and their corresponding renderers.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field renderer factory class
 */
class AIOHM_Booking_Field_Renderer_Factory {

	/**
	 * Registry of field type to renderer class mappings
	 *
	 * @var array
	 */
	private static $renderer_registry = array();

	/**
	 * Cache of instantiated renderers
	 *
	 * @var array
	 */
	private static $renderer_cache = array();

	/**
	 * Initialize the factory and register default renderers
	 */
	public static function init() {
		self::register_default_renderers();
	}

	/**
	 * Register default field renderers
	 */
	private static function register_default_renderers() {
		// Register text-based fields.
		self::register_renderer( 'text', 'AIOHM_Booking_Text_Field_Renderer' );
		self::register_renderer( 'email', 'AIOHM_Booking_Text_Field_Renderer' );
		self::register_renderer( 'url', 'AIOHM_Booking_Text_Field_Renderer' );
		self::register_renderer( 'password', 'AIOHM_Booking_Text_Field_Renderer' );

		// Register number fields.
		self::register_renderer( 'number', 'AIOHM_Booking_Number_Field_Renderer' );

		// Register checkbox fields.
		self::register_renderer( 'checkbox', 'AIOHM_Booking_Checkbox_Field_Renderer' );

		// Register select fields.
		self::register_renderer( 'select', 'AIOHM_Booking_Select_Field_Renderer' );

		// Register textarea fields.
		self::register_renderer( 'textarea', 'AIOHM_Booking_Textarea_Field_Renderer' );

		// Register hidden fields.
		self::register_renderer( 'hidden', 'AIOHM_Booking_Hidden_Field_Renderer' );

		// Register radio fields.
		self::register_renderer( 'radio', 'AIOHM_Booking_Radio_Field_Renderer' );

		// Register color fields.
		self::register_renderer( 'color', 'AIOHM_Booking_Color_Field_Renderer' );

		// Register custom fields.
		self::register_renderer( 'custom', 'AIOHM_Booking_Custom_Field_Renderer' );
	}

	/**
	 * Register a field renderer for a specific field type
	 *
	 * @param string $field_type      The field type.
	 * @param string $renderer_class  The renderer class name.
	 */
	public static function register_renderer( $field_type, $renderer_class ) {
		self::$renderer_registry[ $field_type ] = $renderer_class;
	}

	/**
	 * Get a renderer instance for a field type
	 *
	 * @param string $field_type The field type.
	 * @return AIOHM_Booking_Field_Renderer_Interface|null The renderer instance or null if not found.
	 */
	public static function get_renderer( $field_type ) {
		// Check if we have a renderer registered for this field type.
		if ( ! isset( self::$renderer_registry[ $field_type ] ) ) {
			return null;
		}

		$renderer_class = self::$renderer_registry[ $field_type ];

		// Check cache first.
		if ( isset( self::$renderer_cache[ $renderer_class ] ) ) {
			return self::$renderer_cache[ $renderer_class ];
		}

		// Try to instantiate the renderer.
		if ( class_exists( $renderer_class ) ) {
			$renderer = new $renderer_class();

			// Validate that the renderer implements the correct interface.
			if ( $renderer instanceof AIOHM_Booking_Field_Renderer_Interface ) {
				self::$renderer_cache[ $renderer_class ] = $renderer;
				return $renderer;
			}
		}

		return null;
	}

	/**
	 * Check if a field type is supported
	 *
	 * @param string $field_type The field type.
	 * @return bool Whether the field type is supported.
	 */
	public static function is_supported( $field_type ) {
		return isset( self::$renderer_registry[ $field_type ] );
	}

	/**
	 * Get all registered field types
	 *
	 * @return array Array of registered field types.
	 */
	public static function get_registered_types() {
		return array_keys( self::$renderer_registry );
	}

	/**
	 * Render a field using the appropriate renderer
	 *
	 * @param string $field_id   The field ID.
	 * @param array  $field      The field configuration.
	 * @param mixed  $value      The current field value.
	 * @param string $field_name The HTML name attribute for the field.
	 * @return string The rendered HTML.
	 */
	public static function render_field( $field_id, $field, $value, $field_name ) {
		$field_type = $field['type'] ?? 'text';
		$renderer   = self::get_renderer( $field_type );

		if ( null === $renderer ) {
			// Fallback for unsupported field types.
			return '<p class="description">Unsupported field type: ' . esc_html( $field_type ) . '</p>';
		}

		return $renderer->render( $field_id, $field, $value, $field_name );
	}

	/**
	 * Sanitize a field value using the appropriate renderer
	 *
	 * @param mixed $value The raw field value.
	 * @param array $field The field configuration.
	 * @return mixed The sanitized value.
	 */
	public static function sanitize_field( $value, $field ) {
		$field_type = $field['type'] ?? 'text';
		$renderer   = self::get_renderer( $field_type );

		if ( null === $renderer ) {
			// Fallback sanitization.
			return sanitize_text_field( $value );
		}

		return $renderer->sanitize( $value, $field );
	}

	/**
	 * Clear the renderer cache
	 */
	public static function clear_cache() {
		self::$renderer_cache = array();
	}

	/**
	 * Get registered renderers (for debugging)
	 *
	 * @return array Array of registered renderers.
	 */
	public static function get_registry() {
		return self::$renderer_registry;
	}
}
