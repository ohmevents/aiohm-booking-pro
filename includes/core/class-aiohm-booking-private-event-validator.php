<?php
/**
 * Private Event Validator for AIOHM Booking
 *
 * Centralized validation service for private event booking restrictions.
 * Handles the complex logic of validating accommodation selections
 * against private event requirements.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 *
 * @author OHM Events Agency <https://www.ohm.events>
 * @copyright  2025 AIOHM
 * @license    GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private Event Validator Class
 *
 * Centralizes private event validation logic that was previously
 * duplicated across multiple calendar rules and validators.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Private_Event_Validator {

	/**
	 * Validation result codes
	 */
	const VALIDATION_PASSED                 = 'passed';
	const VALIDATION_REQUIRES_FULL_PROPERTY = 'requires_full_property';
	const VALIDATION_FAILED                 = 'failed';

	/**
	 * Validate accommodation selection against private events
	 *
	 * Central method that replaces duplicate validation logic across
	 * the date range validator and calendar rules.
	 *
	 * @since  2.0.0
	 *
	 * @param array $selected_accommodations Array of selected accommodation IDs.
	 * @param array $private_events Array of private event dates and data.
	 * @param array $context Booking context including 'book_all' flag.
	 * @return array Validation result with status and data
	 */
	public static function validate_selection_against_private_events(
		$selected_accommodations,
		$private_events,
		$context = array()
	) {
		// Early return if no private events.
		if ( empty( $private_events ) ) {
			return array(
				'status'  => self::VALIDATION_PASSED,
				'message' => '',
				'data'    => array(),
			);
		}

		$total_count    = AIOHM_BOOKING_Accommodation_Service::get_total_accommodation_count();
		$selected_count = count( $selected_accommodations );
		$is_book_all    = $context['book_all'] ?? false;

		// Check if this is a full property booking.
		if ( self::is_full_property_booking( $selected_count, $total_count, $is_book_all ) ) {
			return array(
				'status'  => self::VALIDATION_PASSED,
				'message' => '',
				'data'    => array(
					'is_full_property' => true,
					'private_events'   => $private_events,
				),
			);
		}

		// Partial booking with private events - determine the specific error.
		$error_data = self::build_private_event_error( $private_events, $selected_count, $total_count );

		return array(
			'status'  => self::VALIDATION_FAILED,
			'message' => $error_data['message'],
			'data'    => $error_data['data'],
		);
	}

	/**
	 * Validate single date against private events
	 *
	 * @since  2.0.0
	 *
	 * @param string $date Date in Y-m-d format.
	 * @param array  $selected_accommodations Array of selected accommodation IDs.
	 * @param array  $context Booking context.
	 * @return array Validation result
	 */
	public static function validate_single_date( $date, $selected_accommodations, $context = array() ) {
		// Check if date has private events.
		$private_events = self::get_private_events_for_date( $date );

		if ( empty( $private_events ) ) {
			return array(
				'status'  => self::VALIDATION_PASSED,
				'message' => '',
				'data'    => array(),
			);
		}

		$total_count    = AIOHM_BOOKING_Accommodation_Service::get_total_accommodation_count();
		$selected_count = count( $selected_accommodations );
		$is_book_all    = $context['book_all'] ?? false;

		if ( self::is_full_property_booking( $selected_count, $total_count, $is_book_all ) ) {
			return array(
				'status'  => self::VALIDATION_PASSED,
				'message' => '',
				'data'    => array(
					'requires_full_property' => true,
					'private_event_date'     => true,
				),
			);
		}

		// Format date for user display.
		$formatted_date = wp_date( 'M j, Y', strtotime( $date ) );

		return array(
			'status'  => self::VALIDATION_FAILED,
			'message' => sprintf(
				/* translators: %s: date that is reserved for private events */
				__( 'This date (%s) is reserved for private events. You can only book the entire property on this date.', 'aiohm-booking-pro' ),
				$formatted_date
			),
			'data'    => array(
				'error_code'     => 'private_event_restriction',
				'date'           => $date,
				'formatted_date' => $formatted_date,
			),
		);
	}

	/**
	 * Validate date range against private events
	 *
	 * @since  2.0.0
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date End date in Y-m-d format.
	 * @param array  $selected_accommodations Array of selected accommodation IDs.
	 * @param array  $context Booking context.
	 * @return array Validation result
	 */
	public static function validate_date_range( $start_date, $end_date, $selected_accommodations, $context = array() ) {
		$private_events = self::get_private_events_in_range( $start_date, $end_date );

		return self::validate_selection_against_private_events( $selected_accommodations, $private_events, $context );
	}

	/**
	 * Check if selection constitutes full property booking
	 *
	 * @since  2.0.0
	 *
	 * @param int  $selected_count Number of selected accommodations.
	 * @param int  $total_count Total available accommodations.
	 * @param bool $is_book_all Whether book_all flag is set.
	 * @return bool True if full property booking
	 */
	public static function is_full_property_booking( $selected_count, $total_count, $is_book_all = false ) {
		return $selected_count >= $total_count || $is_book_all;
	}

	/**
	 * Build error data for private event restriction
	 *
	 * @since  2.0.0
	 *
	 * @param array $private_events Private event data.
	 * @param int   $selected_count Number of selected accommodations.
	 * @param int   $total_count Total available accommodations.
	 * @return array Error data with message and context
	 */
	private static function build_private_event_error( $private_events, $selected_count, $total_count ) {
		$private_event_dates = array_keys( $private_events );
		$formatted_dates     = array_map(
			function ( $date ) {
				return wp_date( 'M j, Y', strtotime( $date ) );
			},
			$private_event_dates
		);

		// Determine error type based on number of dates.
		if ( count( $private_event_dates ) === 1 ) {
			$error_code = 'private_event_restriction';
			$message    = sprintf(
				/* translators: %s: date that is reserved for private events */
				__( 'This date (%s) is reserved for private events. You can only book the entire property on this date.', 'aiohm-booking-pro' ),
				$formatted_dates[0]
			);
		} else {
			$error_code = 'multi_day_private_event_restriction';
			$message    = sprintf(
				/* translators: %s: comma-separated list of private event dates */
				__( 'Your selected date range includes private event dates (%s). You must book the entire property for stays that include private event dates.', 'aiohm-booking-pro' ),
				implode( ', ', $formatted_dates )
			);
		}

		return array(
			'message' => $message,
			'data'    => array(
				'error_code'                  => $error_code,
				'private_event_dates'         => $private_event_dates,
				'formatted_dates'             => $formatted_dates,
				'selected_count'              => $selected_count,
				'total_count'                 => $total_count,
				'minimum_accommodation_count' => $total_count,
				'constraint_reason'           => 'private_events_in_range',
			),
		);
	}

	/**
	 * Get private events for a specific date
	 *
	 * @since  2.0.0
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return array Private events for the date
	 */
	public static function get_private_events_for_date( $date ) {
		$cell_statuses  = get_option( 'aiohm_booking_cell_statuses', array() );
		$private_events = array();

		// Check all accommodation cells for this date.
		foreach ( $cell_statuses as $cell_key => $cell_data ) {
			if ( strpos( $cell_key, '_' . $date . '_' ) !== false ) {
				$status = $cell_data['status'] ?? '';
				if ( 'private_event' === $status ) {
					$private_events[ $date ] = $cell_data;
					break; // Found private event for this date.
				}
			}
		}

		return $private_events;
	}

	/**
	 * Get private events in date range
	 *
	 * @since  2.0.0
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date End date in Y-m-d format.
	 * @return array Private events in the range
	 */
	public static function get_private_events_in_range( $start_date, $end_date ) {
		$private_events = array();

		$start    = new DateTime( $start_date );
		$end      = new DateTime( $end_date );
		$interval = new DateInterval( 'P1D' );
		$period   = new DatePeriod( $start, $interval, $end );

		foreach ( $period as $date ) {
			$date_string = $date->format( 'Y-m-d' );
			$events      = self::get_private_events_for_date( $date_string );
			if ( ! empty( $events ) ) {
				$private_events = array_merge( $private_events, $events );
			}
		}

		return $private_events;
	}

	/**
	 * Generate validation messages for multi-day booking
	 *
	 * @since  2.0.0
	 *
	 * @param array $private_event_dates Array of dates with private events.
	 * @param array $selected_accommodations Array of selected accommodations.
	 * @param array $context Booking context.
	 * @return array Array of validation messages
	 */
	public static function generate_multi_day_messages( $private_event_dates, $selected_accommodations, $context = array() ) {
		$messages       = array();
		$total_count    = AIOHM_BOOKING_Accommodation_Service::get_total_accommodation_count();
		$selected_count = count( $selected_accommodations );

		if ( ! empty( $private_event_dates ) ) {
			if ( $selected_count < $total_count ) {
				$messages[] = sprintf(
					/* translators: %1$d: selected accommodations, %2$d: total accommodations */
					__( 'You have selected %1$d of %2$d accommodations. All accommodations must be booked for dates with private events.', 'aiohm-booking-pro' ),
					$selected_count,
					$total_count
				);
			}
		}

		return $messages;
	}

	/**
	 * Check if private events exist in cell statuses
	 *
	 * @since  2.0.0
	 *
	 * @param array $cell_statuses Cell statuses data.
	 * @return bool True if private events exist
	 */
	public static function has_private_events( $cell_statuses = null ) {
		if ( null === $cell_statuses ) {
			$cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );
		}

		foreach ( $cell_statuses as $cell_data ) {
			if ( isset( $cell_data['status'] ) && 'private_event' === $cell_data['status'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all private event dates
	 *
	 * @since  2.0.0
	 *
	 * @return array Array of dates with private events
	 */
	public static function get_all_private_event_dates() {
		$cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );
		$private_dates = array();

		foreach ( $cell_statuses as $cell_key => $cell_data ) {
			if ( isset( $cell_data['status'] ) && 'private_event' === $cell_data['status'] ) {
				// Extract date from cell key format: accommodation_id_date_type.
				$parts = explode( '_', $cell_key );
				if ( count( $parts ) >= 3 ) {
					$date = $parts[1]; // Second part should be the date.
					if ( ! in_array( $date, $private_dates, true ) ) {
						$private_dates[] = $date;
					}
				}
			}
		}

		return $private_dates;
	}

	/**
	 * Convert validation result to WP_Error if failed
	 *
	 * @since  2.0.0
	 *
	 * @param array $validation_result Result from validation method.
	 * @return bool|WP_Error True if passed, WP_Error if failed
	 */
	public static function result_to_wp_error( $validation_result ) {
		if ( self::VALIDATION_PASSED === $validation_result['status'] ) {
			return true;
		}

		$error_data = $validation_result['data'];
		$error_code = $error_data['error_code'] ?? 'private_event_validation_failed';

		return new WP_Error( $error_code, $validation_result['message'], $error_data );
	}

	/**
	 * Get validation statistics
	 *
	 * @since  2.0.0
	 *
	 * @return array Statistics about private event validations
	 */
	public static function get_validation_statistics() {
		$all_private_dates = self::get_all_private_event_dates();

		return array(
			'total_private_event_dates' => count( $all_private_dates ),
			'private_event_dates'       => $all_private_dates,
			'has_private_events'        => ! empty( $all_private_dates ),
		);
	}
}
