<?php
/**
 * Multi-Day Private Event Validation Rule
 *
 * Prevents partial room bookings when date range includes private event days.
 * Validates multi-day bookings to ensure private event restrictions are enforced across ranges.
 *
 * @package AIOHM_Booking
 * @subpackage Core\Calendar_Rules
 * @since 1.3.0
 */

use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_Calendar_Rule;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Date_Range_Validator as Date_Range_Validator;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Private_Event_Validator as Private_Event_Validator;
use AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Accommodation_Service as Accommodation_Service;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multi-Day Private Event Validation Rule class.
 *
 * Implements the strategy pattern for handling multi-day private event validation.
 */
class AIOHM_Booking_Multi_Day_Private_Validation_Rule implements AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_Calendar_Rule {

	/**
	 * Date range validator instance.
	 *
	 * @var Date_Range_Validator|null
	 */
	private ?Date_Range_Validator $date_validator = null;

	/**
	 * Rule enabled status.
	 *
	 * @var bool
	 */
	private bool $enabled = true;

	/**
	 * Validation cache to prevent repeated calculations.
	 *
	 * @var array
	 */
	private array $validation_cache = array();

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
		return 'multi_day_private_validation';
	}

	/**
	 * Get the human-readable name for this rule.
	 *
	 * @return string The rule name.
	 */
	public function get_name(): string {
		return __( 'Multi-day Private Event Validation', 'aiohm-booking-pro' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string The rule description.
	 */
	public function get_description(): string {
		return __( 'Prevent partial room bookings when date range includes private event days', 'aiohm-booking-pro' );
	}

	/**
	 * Get the execution priority for this rule.
	 *
	 * @return int The priority (0-100, default 50).
	 */
	public function get_priority(): int {
		return 30;
	}

	/**
	 * Get the contexts where this rule applies.
	 *
	 * @return array Array of context strings.
	 */
	public function get_contexts(): array {
		return array(
			'booking_validation',
			'date_range_validation',
			'multi_day_validation',
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

		// This rule only applies to multi-day validation contexts.
		return in_array( $context, $this->get_contexts(), true );
	}

	/**
	 * Execute the rule logic.
	 *
	 * @param array $calendar_data The calendar data to process.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Modified calendar data or error.
	 * @throws Exception When multi-day validation fails.
	 */
	public function execute( array $calendar_data, array $context_data = array() ) {
		try {
			// This rule requires start_date and end_date.
			if ( ! isset( $calendar_data['start_date'] ) || ! isset( $calendar_data['end_date'] ) ) {
				return $calendar_data;
			}

			return $this->validate_multi_day_booking( $calendar_data, $context_data );

		} catch ( Throwable $e ) {
			return new \WP_Error(
				'multi_day_validation_error',
				/* translators: %s: Error message */
				sprintf( __( 'Multi-day private validation failed: %s', 'aiohm-booking-pro' ), $e->getMessage() )
			);
		}
	}

	/**
	 * Validate multi-day booking against private event restrictions.
	 *
	 * @param array $calendar_data The calendar data.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Validation result or error.
	 */
	private function validate_multi_day_booking( array $calendar_data, array $context_data = array() ) {
		$start_date = $calendar_data['start_date'];
		$end_date   = $calendar_data['end_date'];

		// Create cache key for this validation.
		$cache_key = md5( $start_date . '|' . $end_date . '|' . wp_json_encode( $calendar_data ) );

		// Check cache first.
		if ( isset( $this->validation_cache[ $cache_key ] ) ) {
			return $this->validation_cache[ $cache_key ];
		}

		// Use the date validator to perform the multi-day validation.
		$date_validator    = $this->get_date_validator();
		$validation_result = $date_validator->validate_multi_day_booking( $calendar_data );

		// Cache the result.
		$this->validation_cache[ $cache_key ] = $validation_result;

		// If validation failed, return the error.
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		// Add additional validation metadata.
		$enhanced_result = $this->enhance_validation_result( $validation_result, $calendar_data );

		// Cache the enhanced result.
		$this->validation_cache[ $cache_key ] = $enhanced_result;

		return $enhanced_result;
	}

	/**
	 * Enhance validation result with additional metadata.
	 *
	 * @param array $validation_result The base validation result.
	 * @param array $calendar_data     The original calendar data.
	 * @return array Enhanced validation result.
	 */
	private function enhance_validation_result( array $validation_result, array $calendar_data ): array {
		$start_date = $calendar_data['start_date'];
		$end_date   = $calendar_data['end_date'];

		// Add date range analysis.
		$enhanced_result = array_merge( $calendar_data, $validation_result );

		// Get private events in the range for detailed information.
		$private_events = $this->get_private_events_in_range( $start_date, $end_date );

		if ( ! empty( $private_events ) ) {
			$enhanced_result['private_events_in_range']        = $private_events;
			$enhanced_result['private_event_count']            = count( $private_events );
			$enhanced_result['requires_full_property_booking'] = true;

			// Add specific validation messages.
			$enhanced_result['validation_messages'] = $this->generate_validation_messages( $private_events, $calendar_data );
		}

		// Add booking constraints based on private events.
		if ( isset( $enhanced_result['requires_full_property_booking'] ) && $enhanced_result['requires_full_property_booking'] ) {
			$enhanced_result['booking_constraints'] = array(
				'full_property_required'      => true,
				'partial_booking_blocked'     => true,
				'minimum_accommodation_count' => $this->get_total_accommodation_count(),
				'constraint_reason'           => 'private_events_in_range',
			);
		}

		// Add date-by-date validation status.
		$enhanced_result['daily_validation'] = $this->get_daily_validation_status( $start_date, $end_date );

		return $enhanced_result;
	}

	/**
	 * Generate validation messages for private events in range.
	 *
	 * @param array $private_events Private event dates.
	 * @param array $calendar_data  Calendar data.
	 * @return array Validation messages.
	 */
	private function generate_validation_messages( array $private_events, array $calendar_data ): array {
		$messages = array();

		if ( count( $private_events ) === 1 ) {
			$messages[] = sprintf(
				/* translators: %s: private event date */
				__( 'Your booking includes a private event date (%s). Full property booking is required.', 'aiohm-booking-pro' ),
				reset( $private_events )
			);
		} else {
			$date_list  = implode( ', ', $private_events );
			$messages[] = sprintf(
				/* translators: %s: list of private event dates */
				__( 'Your booking includes multiple private event dates (%s). Full property booking is required for all dates.', 'aiohm-booking-pro' ),
				$date_list
			);
		}

		// Add accommodation-specific messages if selection is provided.
		if ( isset( $calendar_data['selected_accommodations'] ) ) {
			$additional_messages = Private_Event_Validator::generate_multi_day_messages(
				$private_event_dates,
				$calendar_data['selected_accommodations'],
				$calendar_data
			);
			$messages            = array_merge( $messages, $additional_messages );
		}

		return $messages;
	}

	/**
	 * Get daily validation status for a date range.
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date   End date in Y-m-d format.
	 * @return array Daily validation statuses.
	 */
	private function get_daily_validation_status( string $start_date, string $end_date ): array {
		$daily_status = array();
		$current_date = new DateTime( $start_date );
		$end_date_obj = new DateTime( $end_date );

		while ( $current_date <= $end_date_obj ) {
			$date_str   = $current_date->format( 'Y-m-d' );
			$is_private = $this->is_private_event_date( $date_str );

			$daily_status[ $date_str ] = array(
				'is_private_event'       => $is_private,
				'requires_full_property' => $is_private,
				'validation_status'      => $is_private ? 'restricted' : 'open',
			);

			$current_date->add( new DateInterval( 'P1D' ) );
		}

		return $daily_status;
	}

	/**
	 * Get any dependencies this rule has on other rules.
	 *
	 * @return array Array of rule IDs this rule depends on.
	 */
	public function get_dependencies(): array {
		// This rule may depend on private event restriction rule being processed first.
		return array( 'private_event_restriction' );
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
				__( 'Date Range Validator class is required for multi-day private validation rule.', 'aiohm-booking-pro' )
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

	/**
	 * Clear validation cache.
	 */
	public function clear_cache(): void {
		$this->validation_cache = array();
		$this->date_validator   = null;
	}

	/**
	 * Get detailed validation report for a date range.
	 *
	 * @param string $start_date Start date in Y-m-d format.
	 * @param string $end_date   End date in Y-m-d format.
	 * @param array  $booking_data Additional booking data.
	 * @return array Detailed validation report.
	 */
	public function get_validation_report( string $start_date, string $end_date, array $booking_data = array() ): array {
		$calendar_data = array_merge(
			$booking_data,
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
			)
		);

		$validation_result = $this->validate_multi_day_booking( $calendar_data, array() );

		return array(
			'date_range'        => array(
				'start' => $start_date,
				'end'   => $end_date,
			),
			'validation_passed' => ! is_wp_error( $validation_result ),
			'validation_result' => $validation_result,
			'private_events'    => $this->get_private_events_in_range( $start_date, $end_date ),
			'daily_status'      => $this->get_daily_validation_status( $start_date, $end_date ),
			'rule_version'      => $this->get_version(),
			'generated_at'      => current_time( 'Y-m-d H:i:s' ),
		);
	}
}

// Auto-register the rule with the rule engine.
add_action(
	'aiohm_booking_register_default_rules',
	function ( $rule_engine ) {
		$rule_engine->add_rule( new AIOHM_Booking_Multi_Day_Private_Validation_Rule() );
	},
	30
);
