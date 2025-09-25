<?php
/**
 * AIOHM Booking Calendar Rules Manager.
 *
 * Centralized system for managing calendar booking rules.
 * This class handles all business logic for calendar restrictions,
 * availability checking, and booking validation.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AIOHM_BOOKING_Calendar_Rules class.
 */
class AIOHM_BOOKING_Calendar_Rules {

	/**
	 * The single instance of the class.
	 *
	 * @var AIOHM_BOOKING_Calendar_Rules|null
	 */
	private static $instance = null;

	/**
	 * Calendar rules array.
	 *
	 * @var array
	 */
	private $rules = array();

	/**
	 * Calendar cell statuses cache.
	 *
	 * @var array|null
	 */
	private $cell_statuses_cache = null;

	/**
	 * Accommodation counter instance.
	 *
	 * @var AIOHM_BOOKING_Accommodation_Counter|null
	 */
	private $accommodation_counter = null;

	/**
	 * Date range validator instance.
	 *
	 * @var AIOHM_BOOKING_Date_Range_Validator|null
	 */
	private $date_validator = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return AIOHM_BOOKING_Calendar_Rules
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_rules();
		$this->init_hooks();
	}

	/**
	 * Initialize calendar rules.
	 */
	private function init_rules() {
		$this->rules = array(
			'private_event_booking_restriction' => array(
				'name'        => __( 'Private Event Booking Restriction', 'aiohm-booking-pro' ),
				'description' => __( 'Days marked as private events can only be booked as full property (book all)', 'aiohm-booking-pro' ),
				'priority'    => 10,
				'enabled'     => true,
				'callback'    => array( $this, 'apply_private_event_booking_restriction' ),
			),
			'partial_booking_color_rule'        => array(
				'name'        => __( 'Partial Booking Color Rule', 'aiohm-booking-pro' ),
				'description' => __( 'Show booked color when no units are available, regardless of whether units are booked, pending, external, or blocked. Show free color when some units are still available.', 'aiohm-booking-pro' ),
				'priority'    => 20,
				'enabled'     => true,
				'callback'    => array( $this, 'apply_partial_booking_color_rule' ),
			),
			'multi_day_private_validation'      => array(
				'name'        => __( 'Multi-day Private Event Validation', 'aiohm-booking-pro' ),
				'description' => __( 'Prevent partial room bookings when date range includes private event days', 'aiohm-booking-pro' ),
				'priority'    => 30,
				'enabled'     => true,
				'callback'    => array( $this, 'apply_multi_day_private_validation' ),
			),
		);

		// Allow developers to add custom rules.
		$this->rules = apply_filters( 'aiohm_booking_calendar_rules', $this->rules );

		// Sort rules by priority.
		uasort(
			$this->rules,
			function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Calendar display hooks.
		add_filter( 'aiohm_booking_calendar_cell_status', array( $this, 'filter_calendar_cell_status' ), 10, 3 );
		add_filter( 'aiohm_booking_calendar_cell_class', array( $this, 'filter_calendar_cell_class' ), 10, 3 );

		// Booking validation hooks.
		add_filter( 'aiohm_booking_validate_booking_request', array( $this, 'validate_booking_request' ), 10, 2 );
		add_filter( 'aiohm_booking_validate_date_selection', array( $this, 'validate_date_selection' ), 10, 3 );

		// Accommodation selection hooks.
		add_filter( 'aiohm_booking_filter_available_accommodations', array( $this, 'filter_available_accommodations' ), 10, 3 );

		// Clear cache when cell statuses change.
		add_action( 'aiohm_booking_cell_status_updated', array( $this, 'clear_cache' ) );
	}

	/**
	 * Get date range validator instance.
	 *
	 * @return AIOHM_BOOKING_Date_Range_Validator
	 */
	public function get_date_validator() {
		if ( null === $this->date_validator ) {
			$this->date_validator = new AIOHM_BOOKING_Date_Range_Validator();
		}

		return $this->date_validator;
	}

	/**
	 * Get cell statuses (with caching).
	 *
	 * @return array
	 */
	public function get_cell_statuses() {
		if ( null === $this->cell_statuses_cache ) {
			$this->cell_statuses_cache = get_option( 'aiohm_booking_cell_statuses', array() );
		}

		return $this->cell_statuses_cache;
	}

	/**
	 * Get accommodation counter instance.
	 *
	 * @return AIOHM_BOOKING_Accommodation_Counter
	 */
	public function get_accommodation_counter() {
		if ( null === $this->accommodation_counter ) {
			$cell_statuses               = $this->get_cell_statuses();
			$this->accommodation_counter = new AIOHM_BOOKING_Accommodation_Counter( $cell_statuses );
		}

		return $this->accommodation_counter;
	}

	/**
	 * Clear caches.
	 */
	public function clear_cache() {
		$this->cell_statuses_cache   = null;
		$this->accommodation_counter = null;
		$this->date_validator        = null;
	}

	/**
	 * Check if a date is a private event date.
	 *
	 * @param string $date Date in Y-m-d format.
	 *
	 * @return bool
	 */
	public function is_private_event_date( $date ) {
		$date_validator = $this->get_date_validator();
		return $date_validator->is_date_blocked_by_private_events( $date );
	}

	/**
	 * Get private events in a date range.
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date End date in Y-m-d format.
	 *
	 * @return array
	 */
	public function get_private_events_in_range( $start_date, $end_date ) {
		$date_validator = $this->get_date_validator();
		$range_events   = $date_validator->get_private_events_in_range( $start_date, $end_date );
		return array_keys( $range_events ); // Return date strings for backward compatibility.
	}

	/**
	 * Get unit counts for a specific date.
	 *
	 * @param string $date Date in Y-m-d format.
	 *
	 * @return array
	 */
	public function get_date_unit_counts( $date ) {
		$accommodation_counter = $this->get_accommodation_counter();
		return $accommodation_counter->get_unit_counts_for_date( $date );
	}

	/**
	 * Apply private event booking restriction.
	 *
	 * @param array $context Rule context.
	 *
	 * @return array|WP_Error
	 */
	public function apply_private_event_booking_restriction( $context ) {
		if ( ! isset( $context['date'] ) ) {
			return $context;
		}

		$date = $context['date'];

		// Check if this is a private event date.
		if ( ! $this->is_private_event_date( $date ) ) {
			return $context;
		}

		// If accommodation selection is provided, check if it's full property booking.
		if ( isset( $context['selected_accommodations'] ) ) {
			$selected_count = count( $context['selected_accommodations'] );
			$total_count    = $this->get_total_accommodation_count();
			$is_book_all    = isset( $context['book_all'] ) && $context['book_all'];

			// Allow only if all accommodations are selected OR book_all is explicitly set.
			if ( $selected_count < $total_count && ! $is_book_all ) {
				return new WP_Error(
					'private_event_restriction',
					sprintf(
						/* translators: %s: date that is reserved for private events */
						__( 'This date (%s) is reserved for private events. You can only book the entire property on this date.', 'aiohm-booking-pro' ),
						$date
					)
				);
			}
		}

		// Mark this date as requiring full property booking.
		$context['requires_full_property'] = true;
		$context['private_event_date']     = true;

		return $context;
	}

	/**
	 * Apply partial booking color rule.
	 *
	 * @param array $context Rule context.
	 *
	 * @return array
	 */
	public function apply_partial_booking_color_rule( $context ) {
		if ( ! isset( $context['date'] ) ) {
			return $context;
		}

		$date        = $context['date'];
		$unit_counts = $this->get_date_unit_counts( $date );

		// Determine the display status based on unit counts.
		$display_status = 'free';

		// If no units are available (all units are booked, pending, blocked, or external),.
		// show booked color regardless of the specific status mix.
		if ( 0 === $unit_counts['available'] ) {
			$display_status = 'booked';
		}

		$context['display_status'] = $display_status;
		return $context;
	}

	/**
	 * Apply multi-day private event validation.
	 *
	 * @param array $context Rule context.
	 *
	 * @return array|WP_Error
	 */
	public function apply_multi_day_private_validation( $context ) {
		if ( ! isset( $context['start_date'] ) || ! isset( $context['end_date'] ) ) {
			return $context;
		}

		$date_validator    = $this->get_date_validator();
		$validation_result = $date_validator->validate_multi_day_booking( $context );

		// Return error if validation failed.
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Merge validation results with context.
		return array_merge( $context, $validation_result );
	}

	/**
	 * Filter calendar cell status.
	 *
	 * @param string $status Current cell status.
	 * @param string $date Date in Y-m-d format.
	 * @param int    $room_id Room/accommodation ID.
	 *
	 * @return string
	 */
	public function filter_calendar_cell_status( $status, $date, $room_id = 0 ) {
		$context = array(
			'date'    => $date,
			'room_id' => $room_id,
			'status'  => $status,
		);

		// Apply enabled rules.
		foreach ( $this->rules as $rule_key => $rule ) {
			if ( ! $rule['enabled'] || ! is_callable( $rule['callback'] ) ) {
				continue;
			}

			$result = call_user_func( $rule['callback'], $context );

			if ( is_wp_error( $result ) ) {
				continue; // Skip validation errors for display.
			}

			$context = array_merge( $context, $result );
		}

		// Return the display status if set by rules.
		$final_status = $context['display_status'] ?? $status;

		return $final_status;
	}

	/**
	 * Filter calendar cell CSS classes.
	 *
	 * @param string $classes Current CSS classes.
	 * @param string $date Date in Y-m-d format.
	 * @param int    $room_id Room/accommodation ID.
	 *
	 * @return string
	 */
	public function filter_calendar_cell_class( $classes, $date, $room_id = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$additional_classes = array();
		// Add private event class.
		if ( $this->is_private_event_date( $date ) ) {
			$additional_classes[] = 'aiohm-private-event-date';
		}

		// Add partial booking class.
		$unit_counts = $this->get_date_unit_counts( $date );
		if ( $unit_counts['available'] > 0 && ( $unit_counts['booked'] > 0 || $unit_counts['pending'] > 0 || $unit_counts['blocked'] > 0 || $unit_counts['external'] > 0 ) ) {
			$additional_classes[] = 'aiohm-partial-booking';
		}

		if ( ! empty( $additional_classes ) ) {
			$classes .= ' ' . implode( ' ', $additional_classes );
		}

		return $classes;
	}

	/**
	 * Validate booking request against all rules.
	 *
	 * @param bool  $is_valid Current validation status.
	 * @param array $booking_data Booking data.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_booking_request( $is_valid, $booking_data ) {
		if ( ! $is_valid ) {
			return $is_valid; // Already invalid.
		}

		$context = array(
			'start_date'              => $booking_data['checkin_date'] ?? '',
			'end_date'                => $booking_data['checkout_date'] ?? '',
			'selected_accommodations' => $booking_data['accommodations'] ?? array(),
			'book_all'                => isset( $booking_data['private_all'] ) && $booking_data['private_all'],
		);

		// Apply enabled validation rules.
		foreach ( $this->rules as $rule_key => $rule ) {
			if ( ! $rule['enabled'] || ! is_callable( $rule['callback'] ) ) {
				continue;
			}

			$result = call_user_func( $rule['callback'], $context );

			if ( is_wp_error( $result ) ) {
				return $result; // Return first error encountered.
			}

			$context = array_merge( $context, $result );
		}

		return true;
	}

	/**
	 * Validate individual date selection.
	 *
	 * @param bool   $is_valid Current validation status.
	 * @param string $date Date in Y-m-d format.
	 * @param array  $context Additional context.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_date_selection( $is_valid, $date, $context = array() ) {
		if ( ! $is_valid ) {
			return $is_valid; // Already invalid.
		}

		$rule_context = array_merge( $context, array( 'date' => $date ) );

		// Apply enabled validation rules for single date.
		foreach ( $this->rules as $rule_key => $rule ) {
			if ( ! $rule['enabled'] || ! is_callable( $rule['callback'] ) ) {
				continue;
			}

			$result = call_user_func( $rule['callback'], $rule_context );

			if ( is_wp_error( $result ) ) {
				return $result; // Return first error encountered.
			}

			$rule_context = array_merge( $rule_context, $result );
		}

		return true;
	}

	/**
	 * Filter available accommodations based on rules.
	 *
	 * @param array  $accommodations Available accommodations.
	 * @param string $date Date in Y-m-d format.
	 * @param array  $context Additional context.
	 *
	 * @return array
	 */
	public function filter_available_accommodations( $accommodations, $date, $context = array() ) {
		// If this is a private event date, show message about full property requirement.
		if ( $this->is_private_event_date( $date ) ) {
			// This can be used by the frontend to show appropriate messaging.
			$context['private_event_restriction'] = true;
		}

		return $accommodations;
	}

	/**
	 * Get total accommodation count.
	 *
	 * @return int
	 */
	private function get_total_accommodation_count() {
		return AIOHM_BOOKING_Accommodation_Service::get_total_accommodation_count();
	}

	/**
	 * Get rules.
	 *
	 * @return array
	 */
	public function get_rules() {
		return $this->rules;
	}

	/**
	 * Enable or disable a specific rule.
	 *
	 * @param string $rule_key Rule key.
	 * @param bool   $enabled Whether to enable the rule.
	 *
	 * @return bool Success status.
	 */
	public function set_rule_status( $rule_key, $enabled ) {
		if ( ! isset( $this->rules[ $rule_key ] ) ) {
			return false;
		}

		$this->rules[ $rule_key ]['enabled'] = (bool) $enabled;

		// Save rule status to database.
		$rule_statuses              = get_option( 'aiohm_booking_calendar_rule_statuses', array() );
		$rule_statuses[ $rule_key ] = (bool) $enabled;
		update_option( 'aiohm_booking_calendar_rule_statuses', $rule_statuses );

		return true;
	}

	/**
	 * Add a custom rule.
	 *
	 * @param string $rule_key Rule key.
	 * @param array  $rule Rule configuration.
	 *
	 * @return bool Success status.
	 */
	public function add_rule( $rule_key, $rule ) {
		$default_rule = array(
			'name'        => '',
			'description' => '',
			'priority'    => 50,
			'enabled'     => true,
			'callback'    => null,
		);

		$rule = array_merge( $default_rule, $rule );

		if ( ! is_callable( $rule['callback'] ) ) {
			return false;
		}

		$this->rules[ $rule_key ] = $rule;

		// Re-sort rules by priority.
		uasort(
			$this->rules,
			function ( $a, $b ) {
				return $a['priority'] <=> $b['priority'];
			}
		);

		return true;
	}

	/**
	 * Remove a custom rule.
	 *
	 * @param string $rule_key Rule key.
	 *
	 * @return bool Success status.
	 */
	public function remove_rule( $rule_key ) {
		if ( ! isset( $this->rules[ $rule_key ] ) ) {
			return false;
		}

		unset( $this->rules[ $rule_key ] );
		return true;
	}
}
