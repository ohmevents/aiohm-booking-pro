<?php

namespace AIOHM_Booking_PRO\Core;
/**
 * AIOHM Booking Date Range Validator.
 *
 * Specialized utility class for validating date ranges and handling
 * private event conflicts in booking requests.
 *
 * @package AIOHM_Booking
 *
 * @since 1.2.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Date_Range_Validator class.
 */
class AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Date_Range_Validator {

	/**
	 * Private events cache.
	 *
	 * @var array|null
	 */
	private $private_events_cache = null;

	/**
	 * Cache expiration timestamp.
	 *
	 * @var int
	 */
	private $cache_expiration = 0;

	/**
	 * Cache duration in seconds (5 minutes).
	 *
	 * @var int
	 */
	private const CACHE_DURATION = 300;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Clear cache when private events change.
		add_action( 'aiohm_booking_private_event_saved', array( $this, 'clear_cache' ) );
		add_action( 'aiohm_booking_private_event_removed', array( $this, 'clear_cache' ) );
		add_action( 'update_option_aiohm_booking_private_events', array( $this, 'clear_cache' ) );
	}

	/**
	 * Get all private events with caching.
	 *
	 * @return array
	 */
	public function get_all_private_events(): array {
		$current_time = time();

		// Check if cache is valid.
		if ( null !== $this->private_events_cache && $current_time < $this->cache_expiration ) {
			return $this->private_events_cache;
		}

		// Load fresh data from multiple sources.
		$private_events = array();

		// Primary source: option data.
		$option_events = get_option( 'aiohm_booking_private_events', array() );
		if ( is_array( $option_events ) ) {
			$private_events = array_merge( $private_events, $option_events );
		}

		// Secondary source: custom posts (if any).
		$private_events = $this->merge_custom_post_events( $private_events );

		// Tertiary source: external API events (if configured).
		$private_events = $this->merge_external_events( $private_events );

		// Format and validate all event data.
		$private_events = $this->format_private_event_dates( $private_events );

		// Cache the results.
		$this->private_events_cache = $private_events;
		$this->cache_expiration     = $current_time + self::CACHE_DURATION;

		return $private_events;
	}

	/**
	 * Get private events in a specific date range.
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date   End date in Y-m-d format.
	 *
	 * @return array Array of private event dates and their details.
	 */
	public function get_private_events_in_range( string $start_date, string $end_date ): array {
		$all_events   = $this->get_all_private_events();
		$range_events = array();

		$start_timestamp = strtotime( $start_date );
		$end_timestamp   = strtotime( $end_date );

		if ( false === $start_timestamp || false === $end_timestamp ) {
			return array();
		}

		// Iterate through each date in the range.
		for ( $timestamp = $start_timestamp; $timestamp <= $end_timestamp; $timestamp = strtotime( '+1 day', $timestamp ) ) {
			$date_string = gmdate( 'Y-m-d', $timestamp );

			if ( $this->is_date_blocked_by_private_events( $date_string, $all_events ) ) {
				$range_events[ $date_string ] = $all_events[ $date_string ] ?? array(
					'is_private_event'       => true,
					'event_title'            => __( 'Private Event', 'aiohm-booking-pro' ),
					'requires_full_property' => true,
				);
			}
		}

		return $range_events;
	}

	/**
	 * Validate multi-day booking against private events.
	 *
	 * @param array $context Booking context with start_date, end_date, selected_accommodations, etc.
	 *
	 * @return array|WP_Error Validation result or error.
	 */
	public function validate_multi_day_booking( array $context ) {
		// Validate required context fields.
		if ( ! isset( $context['start_date'] ) || ! isset( $context['end_date'] ) ) {
			return new WP_Error(
				'invalid_date_range',
				__( 'Start date and end date are required for validation.', 'aiohm-booking-pro' )
			);
		}

		$start_date = $context['start_date'];
		$end_date   = $context['end_date'];

		// Validate date format.
		if ( ! $this->is_valid_date_format( $start_date ) || ! $this->is_valid_date_format( $end_date ) ) {
			return new WP_Error(
				'invalid_date_format',
				__( 'Invalid date format. Please use Y-m-d format.', 'aiohm-booking-pro' )
			);
		}

		// Check date order.
		if ( strtotime( $start_date ) > strtotime( $end_date ) ) {
			return new WP_Error(
				'invalid_date_order',
				__( 'Start date must be before or equal to end date.', 'aiohm-booking-pro' )
			);
		}

		// Get private events in the date range.
		$private_events_in_range = $this->get_private_events_in_range( $start_date, $end_date );

		if ( empty( $private_events_in_range ) ) {
			// No private events in range, validation passes.
			return array_merge( $context, array( 'has_private_event_dates' => false ) );
		}

		// Check accommodation selection if provided.
		if ( isset( $context['selected_accommodations'] ) ) {
			$validation_result = $this->validate_accommodation_selection( $context, $private_events_in_range );
			if ( is_wp_error( $validation_result ) ) {
				return $validation_result;
			}
		}

		// Validation passed with private events present.
		return array_merge(
			$context,
			array(
				'has_private_event_dates' => true,
				'private_event_dates'     => array_keys( $private_events_in_range ),
				'requires_full_property'  => true,
			)
		);
	}

	/**
	 * Check if a specific date is blocked by private events.
	 *
	 * @param string $date           Date in Y-m-d format.
	 * @param array  $private_events Optional pre-loaded private events array.
	 *
	 * @return bool
	 */
	public function is_date_blocked_by_private_events( string $date, array $private_events = array() ): bool {
		if ( empty( $private_events ) ) {
			$private_events = $this->get_all_private_events();
		}

		if ( ! isset( $private_events[ $date ] ) ) {
			return false;
		}

		$event = $private_events[ $date ];

		// Handle multiple data structure formats for backward compatibility.
		$is_private = false;

		// New structure: explicit is_private_event flag.
		if ( isset( $event['is_private_event'] ) ) {
			$is_private = (bool) $event['is_private_event'];
		}

		// Legacy structure: mode-based detection.
		if ( ! $is_private && isset( $event['mode'] ) ) {
			$is_private = ( 'private' === $event['mode'] );
		}

		// Additional check: event status must be active.
		if ( $is_private && isset( $event['status'] ) ) {
			$is_private = ( 'active' === $event['status'] );
		}

		return $is_private;
	}

	/**
	 * Format and normalize private event date arrays.
	 *
	 * @param array $dates Raw event dates array.
	 *
	 * @return array Formatted and validated dates array.
	 */
	public function format_private_event_dates( array $dates ): array {
		$formatted_dates = array();

		foreach ( $dates as $date_key => $event_data ) {
			// Ensure date key is properly formatted.
			$normalized_date = $this->normalize_date_key( $date_key );
			if ( false === $normalized_date ) {
				continue; // Skip invalid dates.
			}

			// Ensure event data is properly structured.
			$formatted_event = $this->format_single_event_data( $event_data );
			if ( false !== $formatted_event ) {
				$formatted_dates[ $normalized_date ] = $formatted_event;
			}
		}

		return $formatted_dates;
	}

	/**
	 * Clear all caches.
	 */
	public function clear_cache(): void {
		$this->private_events_cache = null;
		$this->cache_expiration     = 0;
	}

	/**
	 * Validate accommodation selection against private events.
	 *
	 * @param array $context      Booking context.
	 * @param array $private_events Private events in range.
	 *
	 * @return array|WP_Error
	 */
	private function validate_accommodation_selection( array $context, array $private_events ) {
		$selected_accommodations = $context['selected_accommodations'] ?? array();
		$is_book_all             = $context['book_all'] ?? false;

		// Use centralized private event validator.
		$validation_result = AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Private_Event_Validator::validate_selection_against_private_events(
			$selected_accommodations,
			$private_events,
			$context
		);

		// Return context if validation passed, otherwise return WP_Error.
		if ( AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Private_Event_Validator::VALIDATION_PASSED === $validation_result['status'] ) {
			return $context;
		}

		return AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Private_Event_Validator::result_to_wp_error( $validation_result );
	}

	/**
	 * Merge custom post events into private events array.
	 *
	 * @param array $existing_events Existing private events.
	 *
	 * @return array Merged events array.
	 */
	private function merge_custom_post_events( array $existing_events ): array {
		// Check cache first for performance
		$cache_key = 'aiohm_booking_private_events';
		$custom_events = wp_cache_get( $cache_key, 'aiohm_booking' );
		
		if ( false === $custom_events ) {
			// Query for custom post type events if they exist.
			$custom_events = get_posts( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to check for existing private events, limited by post type
				array(
					'post_type'   => 'aiohm_private_event',
					'post_status' => 'publish',
					'numberposts' => -1,
					'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Essential for filtering private events by date metadata
						array(
							'key'     => '_event_date',
							'compare' => 'EXISTS',
						),
					),
				)
			);
			
			// Cache for 10 minutes to improve performance
			wp_cache_set( $cache_key, $custom_events, 'aiohm_booking', 10 * MINUTE_IN_SECONDS );
		}

		foreach ( $custom_events as $event_post ) {
			$event_date = get_post_meta( $event_post->ID, '_aiohm_booking_tickets_event_date', true );
			if ( $this->is_valid_date_format( $event_date ) ) {
				$existing_events[ $event_date ] = array(
					'is_private_event'  => true,
					'event_title'       => $event_post->post_title,
					'event_description' => $event_post->post_content,
					'status'            => 'active',
					'source'            => 'custom_post',
					'post_id'           => $event_post->ID,
				);
			}
		}

		return $existing_events;
	}

	/**
	 * Merge external API events into private events array.
	 *
	 * @param array $existing_events Existing private events.
	 *
	 * @return array Merged events array.
	 */
	private function merge_external_events( array $existing_events ): array {
		// Check if external API integration is enabled.
		$api_config = get_option( 'aiohm_booking_external_api_config', array() );
		if ( empty( $api_config['enabled'] ) || empty( $api_config['private_events_endpoint'] ) ) {
			return $existing_events;
		}

		// Fetch external events (with error handling).
		$external_events = $this->fetch_external_private_events( $api_config );
		if ( is_array( $external_events ) ) {
			foreach ( $external_events as $event_date => $event_data ) {
				if ( $this->is_valid_date_format( $event_date ) ) {
					$existing_events[ $event_date ] = array_merge(
						$event_data,
						array( 'source' => 'external_api' )
					);
				}
			}
		}

		return $existing_events;
	}

	/**
	 * Fetch private events from external API.
	 *
	 * @param array $api_config API configuration.
	 *
	 * @return array|false External events array or false on failure.
	 */
	private function fetch_external_private_events( array $api_config ) {
		$endpoint = $api_config['private_events_endpoint'];
		$timeout  = $api_config['timeout'] ?? 10;

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => $timeout,
				'headers' => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'AIOHM-Booking/' . AIOHM_BOOKING_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		return $data['events'] ?? array();
	}

	/**
	 * Format single event data structure.
	 *
	 * @param mixed $event_data Raw event data.
	 *
	 * @return array|false Formatted event data or false on failure.
	 */
	private function format_single_event_data( $event_data ) {
		if ( ! is_array( $event_data ) ) {
			// Handle legacy scalar values.
			return array(
				'is_private_event' => true,
				'event_title'      => __( 'Private Event', 'aiohm-booking-pro' ),
				'status'           => 'active',
			);
		}

		// Ensure required fields with defaults.
		$formatted = array(
			'is_private_event' => $event_data['is_private_event'] ?? true,
			'event_title'      => $event_data['event_title'] ?? __( 'Private Event', 'aiohm-booking-pro' ),
			'status'           => $event_data['status'] ?? 'active',
		);

		// Optional fields.
		$optional_fields = array( 'event_description', 'source', 'post_id', 'mode', 'external_id' );
		foreach ( $optional_fields as $field ) {
			if ( isset( $event_data[ $field ] ) ) {
				$formatted[ $field ] = $event_data[ $field ];
			}
		}

		return $formatted;
	}

	/**
	 * Normalize date key to standard Y-m-d format.
	 *
	 * @param string $date_key Raw date key.
	 *
	 * @return string|false Normalized date or false on failure.
	 */
	private function normalize_date_key( $date_key ) {
		if ( ! is_string( $date_key ) ) {
			return false;
		}

		// Try to parse various date formats.
		$timestamp = strtotime( $date_key );
		if ( false === $timestamp ) {
			return false;
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Check if date string is in valid Y-m-d format.
	 *
	 * @param string $date Date string to validate.
	 *
	 * @return bool
	 */
	private function is_valid_date_format( $date ): bool {
		if ( ! is_string( $date ) ) {
			return false;
		}

		$parsed = date_parse( $date );
		return 0 === $parsed['error_count'] && 0 === $parsed['warning_count']
			&& checkdate( $parsed['month'], $parsed['day'], $parsed['year'] );
	}

	/**
	 * Get total accommodation count.
	 *
	 * @return int
	 */
	private function get_total_accommodation_count(): int {
		return AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service::get_total_accommodation_count();
	}
}
