<?php
/**
 * Accommodation Service for AIOHM Booking
 *
 * Centralized service for accommodation counting and management.
 * Provides static methods for easy access to accommodation data
 * while maintaining consistency across the plugin.
 *
 * @package AIOHM_Booking
 * @since 1.2.3
 *
 * @author OHM Events Agency <https://www.ohm.events>
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accommodation Service Class
 *
 * Static service for accommodation counting and management operations.
 * Acts as a centralized facade for the AIOHM_BOOKING_Accommodation_Counter class.
 *
 * @since 1.2.3
 */
class AIOHM_BOOKING_Accommodation_Service {

	/**
	 * Cached accommodation counter instance
	 *
	 * @since 1.2.3
	 * @var AIOHM_BOOKING_Accommodation_Counter|null
	 */
	private static $counter_instance = null;

	/**
	 * Get accommodation counter instance
	 *
	 * Creates and caches a single instance of the accommodation counter
	 * with current cell statuses data.
	 *
	 * @since 1.2.3
	 *
	 * @param bool $force_refresh Force refresh of the counter instance.
	 * @return AIOHM_BOOKING_Accommodation_Counter
	 */
	public static function get_counter( $force_refresh = false ) {
		if ( null === self::$counter_instance || $force_refresh ) {
			// Load accommodation counter class if not available.
			if ( ! class_exists( 'AIOHM_BOOKING_Accommodation_Counter' ) ) {
				require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-accommodation-counter.php';
			}

			$cell_statuses          = get_option( 'aiohm_booking_cell_statuses', array() );
			self::$counter_instance = new AIOHM_BOOKING_Accommodation_Counter( $cell_statuses );
		}

		return self::$counter_instance;
	}

	/**
	 * Get total accommodation count
	 *
	 * Centralized method for getting the total number of accommodations.
	 * Replaces duplicate implementations across the plugin.
	 *
	 * @since 1.2.3
	 *
	 * @return int Total number of accommodation units
	 */
	public static function get_total_accommodation_count() {
		$counter = self::get_counter();
		return $counter->get_total_accommodation_count();
	}

	/**
	 * Get accommodations list
	 *
	 * @since 1.2.3
	 *
	 * @return array Array of accommodation IDs
	 */
	public static function get_accommodations() {
		$counter = self::get_counter();
		return $counter->get_accommodations();
	}

	/**
	 * Get unit counts for a specific date
	 *
	 * @since 1.2.3
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return array Unit counts with keys: booked, pending, blocked, external, available
	 */
	public static function get_unit_counts_for_date( $date ) {
		$counter = self::get_counter();
		return $counter->get_unit_counts_for_date( $date );
	}

	/**
	 * Check if accommodation exists
	 *
	 * @since 1.2.3
	 *
	 * @param int $accommodation_id Accommodation ID to check.
	 * @return bool True if accommodation exists
	 */
	public static function accommodation_exists( $accommodation_id ) {
		$accommodations = self::get_accommodations();
		return in_array( $accommodation_id, $accommodations, true );
	}

	/**
	 * Validate accommodation selection for booking
	 *
	 * Centralized validation logic for accommodation selection
	 * in booking contexts.
	 *
	 * @since 1.2.3
	 *
	 * @param array $selected_accommodations Array of selected accommodation IDs.
	 * @param array $context Booking context data.
	 * @return bool|WP_Error True if valid, WP_Error if validation fails
	 */
	public static function validate_accommodation_selection( $selected_accommodations, $context = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( ! is_array( $selected_accommodations ) ) {
			return new WP_Error(
				'invalid_selection',
				__( 'Invalid accommodation selection.', 'aiohm-booking-pro' )
			);
		}

		$total_count    = self::get_total_accommodation_count();
		$selected_count = count( $selected_accommodations );

		// Check if selection exceeds available accommodations.
		if ( $selected_count > $total_count ) {
			return new WP_Error(
				'exceeds_capacity',
				sprintf(
					/* translators: %1$d: selected count, %2$d: total available */
					__( 'Selected %1$d accommodations but only %2$d are available.', 'aiohm-booking-pro' ),
					$selected_count,
					$total_count
				)
			);
		}

		// Validate individual accommodation IDs.
		foreach ( $selected_accommodations as $accommodation_id ) {
			if ( ! self::accommodation_exists( $accommodation_id ) ) {
				return new WP_Error(
					'invalid_accommodation',
					sprintf(
						/* translators: %d: accommodation ID */
						__( 'Accommodation %d does not exist.', 'aiohm-booking-pro' ),
						$accommodation_id
					)
				);
			}
		}

		return true;
	}

	/**
	 * Check if selection is for full property booking
	 *
	 * @since 1.2.3
	 *
	 * @param array $selected_accommodations Array of selected accommodation IDs.
	 * @param array $context Optional booking context.
	 * @return bool True if booking all accommodations
	 */
	public static function is_full_property_booking( $selected_accommodations, $context = array() ) {
		$total_count    = self::get_total_accommodation_count();
		$selected_count = count( $selected_accommodations );
		$is_book_all    = $context['book_all'] ?? false;

		return $selected_count >= $total_count || $is_book_all;
	}

	/**
	 * Get accommodation availability for date range
	 *
	 * @since 1.2.3
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date End date in Y-m-d format.
	 * @return array Availability data by date
	 */
	public static function get_availability_for_range( $start_date, $end_date ) {
		$availability = array();

		$start    = new DateTime( $start_date );
		$end      = new DateTime( $end_date );
		$interval = new DateInterval( 'P1D' );
		$period   = new DatePeriod( $start, $interval, $end );

		foreach ( $period as $date ) {
			$date_string                  = $date->format( 'Y-m-d' );
			$availability[ $date_string ] = self::get_unit_counts_for_date( $date_string );
		}

		return $availability;
	}

	/**
	 * Calculate accommodation pricing
	 *
	 * @since 1.2.3
	 *
	 * @param array $selected_accommodations Array of accommodation IDs.
	 * @param int   $nights Number of nights.
	 * @param array $context Optional pricing context.
	 * @return float Total price
	 */
	public static function calculate_pricing( $selected_accommodations, $nights, $context = array() ) {
		$accommodations = self::get_accommodations();
		$base_price     = (float) get_option( 'aiohm_booking_accommodation_price', 100 );

		$total_price = 0;

		foreach ( $selected_accommodations as $accommodation_id ) {
			if ( in_array( $accommodation_id, $accommodations, true ) ) {
				// Get accommodation-specific pricing if available.
				$accommodation_price = get_post_meta( $accommodation_id, '_accommodation_price', true );
				$price_per_night     = $accommodation_price ? (float) $accommodation_price : $base_price;

				$total_price += $price_per_night * $nights;
			}
		}

		// Apply any pricing modifiers from context.
		if ( isset( $context['discount_percentage'] ) && $context['discount_percentage'] > 0 ) {
			$discount     = $total_price * ( $context['discount_percentage'] / 100 );
			$total_price -= $discount;
		}

		return $total_price;
	}

	/**
	 * Clear accommodation cache
	 *
	 * Forces refresh of accommodation data on next access.
	 *
	 * @since 1.2.3
	 */
	public static function clear_cache() {
		self::$counter_instance = null;
	}

	/**
	 * Get accommodation statistics
	 *
	 * @since 1.2.3
	 *
	 * @return array Statistics about accommodations
	 */
	public static function get_statistics() {
		$accommodations = self::get_accommodations();

		// Get the available accommodations setting to limit the count
		$settings                 = get_option( 'aiohm_booking_settings', array() );
		$available_accommodations = isset( $settings['available_accommodations'] ) ? (int) $settings['available_accommodations'] : 7;

		// Limit accommodations to the available count setting
		$limited_accommodations = array_slice( $accommodations, 0, $available_accommodations );
		$total_count            = count( $limited_accommodations );

		$stats = array(
			'total_accommodations'     => $total_count,
			'active_accommodations'    => 0,
			'post_type_accommodations' => count( $accommodations ), // Keep original count for reference
		);

		// Count active accommodations from the limited set.
		foreach ( $limited_accommodations as $accommodation_id ) {
			$post_status = get_post_status( $accommodation_id );
			if ( 'publish' === $post_status ) {
				++$stats['active_accommodations'];
			}
		}

		return $stats;
	}
}
