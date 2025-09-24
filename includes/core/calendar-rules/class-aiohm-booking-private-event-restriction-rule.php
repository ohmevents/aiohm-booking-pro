<?php
/**
 * Private Event Restriction Rule
 *
 * Handles restrictions for private event dates where only full property booking is allowed.
 * This rule ensures that dates marked as private events can only be booked as complete property rentals.
 *
 * @package AIOHM_Booking
 * @subpackage Core\Calendar_Rules
 * @since 1.3.0
 */

use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_Calendar_Rule;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Date_Range_Validator as Date_Range_Validator;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Counter as Accommodation_Counter;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Private_Event_Validator as Private_Event_Validator;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service as Accommodation_Service;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Private Event Restriction Rule class.
 *
 * Implements the strategy pattern for handling private event booking restrictions.
 */
class AIOHM_Booking_Private_Event_Restriction_Rule implements AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_Calendar_Rule {

	/**
	 * Date range validator instance.
	 *
	 * @var Date_Range_Validator|null
	 */
	private ?Date_Range_Validator $date_validator = null;

	/**
	 * Accommodation counter instance.
	 *
	 * @var Accommodation_Counter|null
	 */
	private ?Accommodation_Counter $accommodation_counter = null;

	/**
	 * Rule enabled status.
	 *
	 * @var bool
	 */
	private bool $enabled = true;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_enabled_status();
	}

	/**
	 * Get the unique identifier for this rule.
	 *
	 * @return string The rule identifier.
	 */
	public function get_id(): string {
		return 'private_event_restriction';
	}

	/**
	 * Get the human-readable name for this rule.
	 *
	 * @return string The rule name.
	 */
	public function get_name(): string {
		return __( 'Private Event Booking Restriction', 'aiohm-booking-pro' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string The rule description.
	 */
	public function get_description(): string {
		return __( 'Days marked as private events can only be booked as full property (book all)', 'aiohm-booking-pro' );
	}

	/**
	 * Get the execution priority for this rule.
	 *
	 * @return int The priority (0-100, default 50).
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * Get the contexts where this rule applies.
	 *
	 * @return array Array of context strings.
	 */
	public function get_contexts(): array {
		return array(
			'calendar_display',
			'booking_validation',
			'date_selection_validation',
			'accommodation_filtering',
		);
	}

	/**
	 * Check if this rule applies to the given context and data.
	 *
	 * @param string $context The execution context.
	 * @param array  $data    The context data.
	 * @return bool True if rule should execute.
	 */
	public function applies_to_context( string $context, array $data = array() ): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		// This rule applies to all supported contexts.
		return in_array( $context, $this->get_contexts(), true );
	}

	/**
	 * Execute the rule logic.
	 *
	 * @param array $calendar_data The calendar data to process.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Modified calendar data or error.
	 * @throws Exception When private event rule execution fails.
	 */
	public function execute( array $calendar_data, array $context_data = array() ) {
		try {
			// Handle single date context.
			if ( isset( $calendar_data['date'] ) ) {
				return $this->process_single_date( $calendar_data, $context_data );
			}

			// Handle date range context.
			if ( isset( $calendar_data['start_date'] ) && isset( $calendar_data['end_date'] ) ) {
				return $this->process_date_range( $calendar_data, $context_data );
			}

			// Handle accommodation filtering context.
			if ( isset( $calendar_data['accommodations'] ) ) {
				return $this->process_accommodation_filtering( $calendar_data, $context_data );
			}

			// No applicable data, return unchanged.
			return $calendar_data;

		} catch ( Throwable $e ) {
			return new \WP_Error(
				'private_event_rule_error',
				/* translators: %s: error message */
				sprintf( __( 'Private event rule execution failed: %s', 'aiohm-booking-pro' ), $e->getMessage() )
			);
		}
	}

	/**
	 * Process single date restrictions.
	 *
	 * @param array $calendar_data The calendar data.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Modified data or error.
	 */
	private function process_single_date( array $calendar_data, array $context_data = array() ) {
		$date = $calendar_data['date'];

		// Check if this is a private event date.
		if ( ! $this->is_private_event_date( $date ) ) {
			return $calendar_data;
		}

		// If accommodation selection is provided, validate using centralized validator.
		if ( isset( $calendar_data['selected_accommodations'] ) ) {
			$validation_result = Private_Event_Validator::validate_single_date(
				$date,
				$calendar_data['selected_accommodations'],
				$calendar_data
			);

			if ( Private_Event_Validator::VALIDATION_FAILED === $validation_result['status'] ) {
				return Private_Event_Validator::result_to_wp_error( $validation_result );
			}

			// Add validation result data to calendar data.
			if ( isset( $validation_result['data'] ) ) {
				$calendar_data = array_merge( $calendar_data, $validation_result['data'] );
			}
		} else {
			// Mark this date as requiring full property booking.
			$calendar_data['requires_full_property'] = true;
			$calendar_data['private_event_date']     = true;
		}

		return $calendar_data;
	}

	/**
	 * Process date range restrictions.
	 *
	 * @param array $calendar_data The calendar data.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Modified data or error.
	 */
	private function process_date_range( array $calendar_data, array $context_data = array() ) {
		$start_date = $calendar_data['start_date'];
		$end_date   = $calendar_data['end_date'];

		// Get private events in the range.
		$private_events = $this->get_private_events_in_range( $start_date, $end_date );

		if ( empty( $private_events ) ) {
			return $calendar_data;
		}

		// If there are private events and accommodation selection is provided.
		if ( isset( $calendar_data['selected_accommodations'] ) ) {
			$selected_count = count( $calendar_data['selected_accommodations'] );
			$total_count    = $this->get_total_accommodation_count();
			$is_book_all    = $calendar_data['book_all'] ?? false;

			// Require full property booking if private events are present.
			if ( $selected_count < $total_count && ! $is_book_all ) {
				$private_dates = implode( ', ', $private_events );
				return new \WP_Error(
					'private_event_range_restriction',
					sprintf(
						/* translators: %s: dates that are reserved for private events */
						__( 'Your booking includes private event dates (%s). You can only book the entire property when private events are included.', 'aiohm-booking-pro' ),
						$private_dates
					)
				);
			}
		}

		// Mark the range as containing private events.
		$calendar_data['contains_private_events'] = true;
		$calendar_data['private_event_dates']     = $private_events;

		return $calendar_data;
	}

	/**
	 * Process accommodation filtering.
	 *
	 * @param array $calendar_data The calendar data.
	 * @param array $context_data  Additional context data.
	 * @return array Modified data.
	 */
	private function process_accommodation_filtering( array $calendar_data, array $context_data = array() ) {
		$date = $context_data['date'] ?? '';

		if ( ! empty( $date ) && $this->is_private_event_date( $date ) ) {
			$calendar_data['private_event_restriction'] = true;
		}

		return $calendar_data;
	}

	/**
	 * Get any dependencies this rule has on other rules.
	 *
	 * @return array Array of rule IDs this rule depends on.
	 */
	public function get_dependencies(): array {
		return array();
	}

	/**
	 * Validate the rule configuration.
	 *
	 * @param array $config The rule configuration.
	 * @return true|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_config( array $config = array() ) {
		// Check if date validator is available.
		if ( ! class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Date_Range_Validator' ) ) {
			return new \WP_Error(
				'missing_dependency',
				__( 'Date Range Validator class is required for private event restriction rule.', 'aiohm-booking-pro' )
			);
		}

		// Check if accommodation counter is available.
		if ( ! class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Counter' ) ) {
			return new \WP_Error(
				'missing_dependency',
				__( 'Accommodation Counter class is required for private event restriction rule.', 'aiohm-booking-pro' )
			);
		}

		return true;
	}

	/**
	 * Get the rule version for compatibility checks.
	 *
	 * @return string The rule version.
	 */
	public function get_version(): string {
		return '1.0.0';
	}

	/**
	 * Check if the rule is enabled.
	 *
	 * @return bool True if enabled.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Enable or disable the rule.
	 *
	 * @param bool $enabled Whether to enable the rule.
	 * @return bool Success status.
	 */
	public function set_enabled( bool $enabled ): bool {
		$this->enabled = $enabled;

		// Save to database.
		$rule_statuses                    = get_option( 'aiohm_booking_calendar_rule_statuses', array() );
		$rule_statuses[ $this->get_id() ] = $enabled;
		update_option( 'aiohm_booking_calendar_rule_statuses', $rule_statuses );

		return true;
	}

	/**
	 * Load enabled status from database.
	 */
	private function load_enabled_status(): void {
		$rule_statuses = get_option( 'aiohm_booking_calendar_rule_statuses', array() );
		$this->enabled = $rule_statuses[ $this->get_id() ] ?? true;
	}

	/**
	 * Get date range validator instance.
	 *
	 * @return AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Date_Range_Validator
	 */
	private function get_date_validator(): Date_Range_Validator {
		if ( null === $this->date_validator ) {
			$this->date_validator = new Date_Range_Validator();
		}

		return $this->date_validator;
	}

	/**
	 * Get accommodation counter instance.
	 *
	 * @return Accommodation_Counter
	 */
	private function get_accommodation_counter(): Accommodation_Counter {
		if ( null === $this->accommodation_counter ) {
			$cell_statuses               = get_option( 'aiohm_booking_cell_statuses', array() );
			$this->accommodation_counter = new Accommodation_Counter( $cell_statuses );
		}

		return $this->accommodation_counter;
	}

	/**
	 * Check if a date is a private event date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return bool
	 */
	private function is_private_event_date( string $date ): bool {
		$date_validator = $this->get_date_validator();
		return $date_validator->is_date_blocked_by_private_events( $date );
	}

	/**
	 * Get private events in a date range.
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date   End date in Y-m-d format.
	 * @return array Array of private event date strings.
	 */
	private function get_private_events_in_range( string $start_date, string $end_date ): array {
		$date_validator = $this->get_date_validator();
		$range_events   = $date_validator->get_private_events_in_range( $start_date, $end_date );
		return array_keys( $range_events );
	}

	/**
	 * Get total accommodation count.
	 *
	 * @return int
	 */
	private function get_total_accommodation_count(): int {
		return Accommodation_Service::get_total_accommodation_count();
	}
}

// Auto-register the rule with the rule engine.
add_action(
	'aiohm_booking_register_default_rules',
	function ( $rule_engine ) {
		$rule_engine->add_rule( new AIOHM_Booking_Private_Event_Restriction_Rule() );
	},
	10
);
