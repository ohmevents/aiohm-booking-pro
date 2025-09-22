<?php
/**
 * Early Bird Helper Functions for AIOHM Booking
 * Provides separate early bird functionality for accommodations and events
 *
 * @package AIOHM_Booking
 * @since 1.2.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Early Bird Helper
 *
 * Helper class for managing early bird pricing across different modules
 * without conflicts between accommodations and events
 *
 * @package AIOHM_Booking
 * @since 1.2.6
 */
class AIOHM_BOOKING_Early_Bird_Helper {

	/**
	 * Get accommodation early bird settings
	 *
	 * @return array Accommodation early bird settings
	 */
	public static function get_accommodation_early_bird_settings() {
		$global_settings = AIOHM_BOOKING_Settings::get_all();

		return array(
			'enabled'       => $global_settings['enable_early_bird_accommodation'] ?? false,
			'days'          => intval( $global_settings['early_bird_days_accommodation'] ?? 30 ),
			'default_price' => floatval( $global_settings['aiohm_booking_accommodation_early_bird_price'] ?? 0 ),
		);
	}

	/**
	 * Get events early bird settings
	 *
	 * @return array Events early bird settings
	 */
	public static function get_events_early_bird_settings() {
		$global_settings = AIOHM_BOOKING_Settings::get_all();

		return array(
			'enabled'       => $global_settings['enable_early_bird_events'] ?? false,
			'days'          => intval( $global_settings['early_bird_days_events'] ?? 30 ),
			'default_price' => floatval( $global_settings['aiohm_booking_events_early_bird_price'] ?? 0 ),
		);
	}

	/**
	 * Calculate accommodation early bird price
	 *
	 * @param float $regular_price Regular accommodation price
	 * @param float $early_bird_price Specific early bird price (if set)
	 * @param int   $days_until_checkin Days until check-in
	 * @return float Early bird price to use
	 */
	public static function calculate_accommodation_early_bird_price( $regular_price, $early_bird_price = 0, $days_until_checkin = 0 ) {
		$settings = self::get_accommodation_early_bird_settings();

		// Check if early bird is enabled and eligible
		if ( ! $settings['enabled'] || $days_until_checkin < $settings['days'] ) {
			return $regular_price;
		}

		// Use specific early bird price if set
		if ( $early_bird_price > 0 ) {
			return $early_bird_price;
		}

		// Use default early bird price if set
		if ( $settings['default_price'] > 0 ) {
			return $settings['default_price'];
		}

		// Fallback: use regular price when no early bird price is set
		return $regular_price;
	}

	/**
	 * Calculate events early bird price
	 *
	 * @param float  $regular_price Regular event price
	 * @param float  $early_bird_price Specific early bird price (if set)
	 * @param string $early_bird_date Early bird cutoff date
	 * @param int    $days_until_event Days until event
	 * @return float Early bird price to use
	 */
	public static function calculate_events_early_bird_price( $regular_price, $early_bird_price = 0, $early_bird_date = '', $days_until_event = 0 ) {
		$settings     = self::get_events_early_bird_settings();
		$current_date = current_time( 'Y-m-d' );

		// Check if early bird is enabled
		if ( ! $settings['enabled'] ) {
			return $regular_price;
		}

		// Check early bird date eligibility
		if ( ! empty( $early_bird_date ) && $early_bird_date < $current_date ) {
			return $regular_price;
		}

		// Check days eligibility
		if ( $days_until_event > 0 && $days_until_event < $settings['days'] ) {
			return $regular_price;
		}

		// Use specific early bird price if set
		if ( $early_bird_price > 0 ) {
			return $early_bird_price;
		}

		// Use default early bird price if set
		if ( $settings['default_price'] > 0 ) {
			return $settings['default_price'];
		}

		// Fallback: use regular price when no early bird price is set
		return $regular_price;
	}

	/**
	 * Check if accommodation early bird is applicable
	 *
	 * @param int $days_until_checkin Days until check-in
	 * @return bool True if early bird applies
	 */
	public static function is_accommodation_early_bird_applicable( $days_until_checkin ) {
		$settings = self::get_accommodation_early_bird_settings();
		return $settings['enabled'] && $days_until_checkin >= $settings['days'];
	}

	/**
	 * Check if events early bird is applicable
	 *
	 * @param string $early_bird_date Early bird cutoff date
	 * @param int    $days_until_event Days until event
	 * @return bool True if early bird applies
	 */
	public static function is_events_early_bird_applicable( $early_bird_date = '', $days_until_event = 0 ) {
		$settings     = self::get_events_early_bird_settings();
		$current_date = current_time( 'Y-m-d' );

		if ( ! $settings['enabled'] ) {
			return false;
		}

		// Check early bird date
		if ( ! empty( $early_bird_date ) && $early_bird_date < $current_date ) {
			return false;
		}

		// Check days
		if ( $days_until_event > 0 && $days_until_event < $settings['days'] ) {
			return false;
		}

		return true;
	}
}
