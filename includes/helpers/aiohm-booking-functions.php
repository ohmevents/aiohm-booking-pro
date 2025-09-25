<?php
/**
 * Helper Functions for AIOHM Booking
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if premium features are available
 * For now, return true for all admin users
 *
 * @return bool
 */
function aiohm_booking_is_premium() {
	// Check if we're in demo mode first
	if ( apply_filters( 'aiohm_booking_demo_mode', false ) ) {
		return true;
	}
	
	// Check Freemius premium status
	if ( function_exists( 'aiohm_booking_fs' ) ) {
		return aiohm_booking_fs()->can_use_premium_code__premium_only();
	}
	
	// Fallback for admin users
	return current_user_can( 'manage_options' );
}

/**
 * Get a specific setting value
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value if not found.
 * @return mixed
 */
function aiohm_booking_get_setting( $key, $default = '' ) {
	$settings = get_option( 'aiohm_booking_settings', array() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Check if a module is enabled
 *
 * @param string $module_id Module ID.
 * @return bool
 */
function aiohm_booking_is_module_enabled( $module_id ) {
	$enable_key = 'enable_' . $module_id;
	return ! empty( aiohm_booking_get_setting( $enable_key ) );
}

/**
 * Enable a module
 *
 * @param string $module_id Module ID.
 * @return bool
 */
function aiohm_booking_enable_module( $module_id ) {
	$settings                           = get_option( 'aiohm_booking_settings', array() );
	$settings[ 'enable_' . $module_id ] = '1';
	return update_option( 'aiohm_booking_settings', $settings );
}

/**
 * Disable a module
 *
 * @param string $module_id Module ID.
 * @return bool
 */
function aiohm_booking_disable_module( $module_id ) {
	$settings                           = get_option( 'aiohm_booking_settings', array() );
	$settings[ 'enable_' . $module_id ] = '0';
	return update_option( 'aiohm_booking_settings', $settings );
}

/**
 * Get all enabled modules
 *
 * @return array Array of enabled module IDs
 */
function aiohm_booking_get_enabled_modules() {
	$settings        = get_option( 'aiohm_booking_settings', array() );
	$enabled_modules = array();

	foreach ( $settings as $key => $value ) {
		if ( strpos( $key, 'enable_' ) === 0 && ! empty( $value ) ) {
			$enabled_modules[] = substr( $key, 7 ); // Remove 'enable_' prefix.
		}
	}

	return $enabled_modules;
}

/**
 * Check if module dependencies are met
 *
 * @param array $dependencies Array of required module IDs.
 * @return bool
 */
function aiohm_booking_check_module_dependencies( $dependencies ) {
	if ( empty( $dependencies ) ) {
		return true;
	}

	$enabled_modules = aiohm_booking_get_enabled_modules();

	foreach ( $dependencies as $dependency ) {
		if ( ! in_array( $dependency, $enabled_modules ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Get asset URL for plugin assets
 *
 * @param string $path Asset path relative to assets directory.
 * @return string Full URL to asset
 */
function aiohm_booking_asset_url( $path ) {
	return AIOHM_BOOKING_URL . 'assets/' . ltrim( $path, '/' );
}

/**
 * Get upgrade URL for premium features
 *
 * @return string
 */
function aiohm_booking_get_upgrade_url() {
	return 'https://www.aiohm.app/booking/upgrade';
}

/**
 * Log message for debugging
 *
 * @param string $message Log message.
 * @param string $level Log level (DEBUG, INFO, WARNING, ERROR).
 */
function aiohm_booking_log( $message, $level = 'INFO' ) {
	return;
}

/**
 * Get environment mode setting
 *
 * @param string $check Environment check type: 'debug', 'cache', 'minify', 'performance'.
 * @return bool Environment setting
 */
function aiohm_booking_get_env_mode( $check = 'debug' ) {
	switch ( $check ) {
		case 'debug':
			return false;
		case 'cache':
		case 'minify':
		case 'performance':
			return true;
		default:
			return false;
	}
}

/**
 * Check if caching is enabled
 *
 * @return bool True if caching is enabled
 */
function aiohm_booking_cache_enabled() {
	return aiohm_booking_get_env_mode( 'cache' );
}

/**
 * Check if asset minification is enabled
 *
 * @return bool True if asset minification is enabled
 */
function aiohm_booking_minify_assets() {
	return aiohm_booking_get_env_mode( 'minify' );
}

/**
 * Check if performance mode is enabled
 *
 * @return bool True if performance mode is enabled
 */
function aiohm_booking_performance_mode() {
	return aiohm_booking_get_env_mode( 'performance' );
}

/**
 * Get environment configuration (backward compatibility)
 *
 * @return array Environment configuration settings
 */
function aiohm_booking_get_env_config() {
	return array(
		'minify_assets'    => aiohm_booking_get_env_mode( 'minify' ),
		'performance_mode' => aiohm_booking_get_env_mode( 'performance' ),
	);
}

/**
 * Get log directory path
 *
 * @return string Path to logs directory
 */
function aiohm_booking_get_log_dir() {
	return AIOHM_BOOKING_DIR . 'logs/';
}

/**
 * Get current log file path
 *
 * @return string Path to current log file
 */
function aiohm_booking_get_log_file() {
	$date = gmdate( 'Y-m-d' );
	return aiohm_booking_get_log_dir() . "aiohm-booking-{$date}.log";
}

/**
 * Get accommodation types configuration
 *
 * @return array Array of accommodation types with singular and plural forms
 */
function aiohm_booking_get_accommodation_types() {
	return array(
		'room'      => array(
			'singular' => __( 'Room', 'aiohm-booking-pro' ),
			'plural'   => __( 'Rooms', 'aiohm-booking-pro' ),
		),
		'house'     => array(
			'singular' => __( 'House', 'aiohm-booking-pro' ),
			'plural'   => __( 'Houses', 'aiohm-booking-pro' ),
		),
		'apartment' => array(
			'singular' => __( 'Apartment', 'aiohm-booking-pro' ),
			'plural'   => __( 'Apartments', 'aiohm-booking-pro' ),
		),
		'villa'     => array(
			'singular' => __( 'Villa', 'aiohm-booking-pro' ),
			'plural'   => __( 'Villas', 'aiohm-booking-pro' ),
		),
		'bungalow'  => array(
			'singular' => __( 'Bungalow', 'aiohm-booking-pro' ),
			'plural'   => __( 'Bungalows', 'aiohm-booking-pro' ),
		),
		'cabin'     => array(
			'singular' => __( 'Cabin', 'aiohm-booking-pro' ),
			'plural'   => __( 'Cabins', 'aiohm-booking-pro' ),
		),
		'cottage'   => array(
			'singular' => __( 'Cottage', 'aiohm-booking-pro' ),
			'plural'   => __( 'Cottages', 'aiohm-booking-pro' ),
		),
		'suite'     => array(
			'singular' => __( 'Suite', 'aiohm-booking-pro' ),
			'plural'   => __( 'Suites', 'aiohm-booking-pro' ),
		),
		'studio'    => array(
			'singular' => __( 'Studio', 'aiohm-booking-pro' ),
			'plural'   => __( 'Studios', 'aiohm-booking-pro' ),
		),
		'unit'      => array(
			'singular' => __( 'Unit', 'aiohm-booking-pro' ),
			'plural'   => __( 'Units', 'aiohm-booking-pro' ),
		),
		'space'     => array(
			'singular' => __( 'Space', 'aiohm-booking-pro' ),
			'plural'   => __( 'Spaces', 'aiohm-booking-pro' ),
		),
		'venue'     => array(
			'singular' => __( 'Venue', 'aiohm-booking-pro' ),
			'plural'   => __( 'Venues', 'aiohm-booking-pro' ),
		),
	);
}

/**
 * Get accommodation name (singular or plural)
 *
 * @param string $accommodation_type Accommodation type.
 * @param string $form Form: 'singular' or 'plural' (default: 'singular').
 * @return string Accommodation name
 */
function aiohm_booking_get_accommodation_name( $accommodation_type, $form = 'singular' ) {
	$types    = aiohm_booking_get_accommodation_types();
	$fallback = 'singular' === $form ? __( 'Accommodation', 'aiohm-booking-pro' ) : __( 'Accommodations', 'aiohm-booking-pro' );
	return $types[ $accommodation_type ][ $form ] ?? $fallback;
}

/**
 * Get accommodation singular name (backward compatibility)
 *
 * @param string $accommodation_type Accommodation type.
 * @return string Singular name
 */
function aiohm_booking_get_accommodation_singular_name( $accommodation_type ) {
	return aiohm_booking_get_accommodation_name( $accommodation_type, 'singular' );
}

/**
 * Get accommodation plural name (backward compatibility)
 *
 * @param string $accommodation_type Accommodation type.
 * @return string Plural name
 */
function aiohm_booking_get_accommodation_plural_name( $accommodation_type ) {
	return aiohm_booking_get_accommodation_name( $accommodation_type, 'plural' );
}

/**
 * Get current accommodation type from settings
 *
 * @return string Accommodation type
 */
function aiohm_booking_get_current_accommodation_type() {
	return aiohm_booking_get_setting( 'accommodation_type', 'unit' );
}

/**
 * Get accommodation posts safely
 *
 * @param int $count Number of accommodations to get.
 * @return array Array of accommodation posts
 */
function aiohm_booking_get_accommodation_posts( $count = -1 ) {
	$args = array(
		'post_type'      => 'aiohm_accommodation',
		'posts_per_page' => $count,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	);

	return get_posts( $args );
}
