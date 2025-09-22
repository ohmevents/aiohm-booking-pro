<?php
/**
 * Partial Booking Color Rule
 *
 * Determines calendar cell display colors based on accommodation availability.
 * Shows booked color when no units are available, free color when units are still available.
 *
 * @package AIOHM_Booking
 * @subpackage Core\Calendar_Rules
 * @since 1.3.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Partial Booking Color Rule class.
 *
 * Implements the strategy pattern for handling calendar cell color logic.
 */
class AIOHM_Booking_Partial_Booking_Color_Rule implements AIOHM_Booking_Calendar_Rule {

	/**
	 * Accommodation counter instance.
	 *
	 * @var AIOHM_BOOKING_Accommodation_Counter|null
	 */
	private ?AIOHM_BOOKING_Accommodation_Counter $accommodation_counter = null;

	/**
	 * Rule enabled status.
	 *
	 * @var bool
	 */
	private bool $enabled = true;

	/**
	 * Unit count cache to prevent repeated calculations.
	 *
	 * @var array
	 */
	private array $unit_count_cache = array();

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
		return 'partial_booking_color';
	}

	/**
	 * Get the human-readable name for this rule.
	 *
	 * @return string The rule name.
	 */
	public function get_name(): string {
		return __( 'Partial Booking Color Rule', 'aiohm-booking-pro' );
	}

	/**
	 * Get the rule description.
	 *
	 * @return string The rule description.
	 */
	public function get_description(): string {
		return __( 'Show booked color when no units are available, regardless of whether units are booked, pending, external, or blocked. Show free color when some units are still available.', 'aiohm-booking-pro' );
	}

	/**
	 * Get the execution priority for this rule.
	 *
	 * @return int The priority (0-100, default 50).
	 */
	public function get_priority(): int {
		return 20;
	}

	/**
	 * Get the contexts where this rule applies.
	 *
	 * @return array Array of context strings.
	 */
	public function get_contexts(): array {
		return array(
			'calendar_display',
			'cell_status_filtering',
			'cell_class_filtering',
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

		// This rule applies to calendar display contexts.
		return in_array( $context, $this->get_contexts(), true );
	}

	/**
	 * Execute the rule logic.
	 *
	 * @param array $calendar_data The calendar data to process.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Modified calendar data or error.
	 * @throws Exception When partial booking color rule execution fails.
	 */
	public function execute( array $calendar_data, array $context_data = array() ) {
		try {
			// Handle single date context.
			if ( isset( $calendar_data['date'] ) ) {
				return $this->process_date_display( $calendar_data, $context_data );
			}

			// Handle batch processing of multiple dates.
			if ( isset( $calendar_data['dates'] ) && is_array( $calendar_data['dates'] ) ) {
				return $this->process_batch_dates( $calendar_data, $context_data );
			}

			// No applicable data, return unchanged.
			return $calendar_data;

		} catch ( Throwable $e ) {
			return new WP_Error(
				'partial_booking_color_rule_error',
				/* translators: %s: error message */
				sprintf( __( 'Partial booking color rule execution failed: %s', 'aiohm-booking-pro' ), $e->getMessage() )
			);
		}
	}

	/**
	 * Process single date display logic.
	 *
	 * @param array $calendar_data The calendar data.
	 * @param array $context_data  Additional context data.
	 * @return array Modified data.
	 */
	private function process_date_display( array $calendar_data, array $context_data = array() ): array {
		$date        = $calendar_data['date'];
		$unit_counts = $this->get_date_unit_counts( $date );

		// Determine the display status based on unit counts.
		$display_status = 'free';

		// If no units are available (all units are booked, pending, blocked, or external),.
		// show booked color regardless of the specific status mix.
		if ( 0 === $unit_counts['available'] ) {
			$display_status = 'booked';
		}

		// Add detailed availability info for advanced usage.
		$calendar_data['display_status']     = $display_status;
		$calendar_data['unit_counts']        = $unit_counts;
		$calendar_data['availability_ratio'] = $this->calculate_availability_ratio( $unit_counts );

		// Add CSS classes for styling.
		$calendar_data['css_classes'] = $this->generate_css_classes( $unit_counts, $calendar_data['css_classes'] ?? array() );

		return $calendar_data;
	}

	/**
	 * Process batch dates for efficient processing.
	 *
	 * @param array $calendar_data The calendar data.
	 * @param array $context_data  Additional context data.
	 * @return array Modified data.
	 */
	private function process_batch_dates( array $calendar_data, array $context_data = array() ): array {
		$processed_dates = array();

		foreach ( $calendar_data['dates'] as $date => $date_data ) {
			$date_context             = array_merge( $date_data, array( 'date' => $date ) );
			$processed_date           = $this->process_date_display( $date_context, $context_data );
			$processed_dates[ $date ] = $processed_date;
		}

		$calendar_data['dates'] = $processed_dates;
		return $calendar_data;
	}

	/**
	 * Generate CSS classes based on unit counts.
	 *
	 * @param array $unit_counts The unit counts.
	 * @param array $existing_classes Existing CSS classes.
	 * @return array Updated CSS classes.
	 */
	private function generate_css_classes( array $unit_counts, array $existing_classes = array() ): array {
		$classes = $existing_classes;

		// Add partial booking class if some but not all units are available.
		$total_units     = $unit_counts['total'] ?? 0;
		$available_units = $unit_counts['available'] ?? 0;
		$occupied_units  = ( $unit_counts['booked'] ?? 0 ) +
						( $unit_counts['pending'] ?? 0 ) +
						( $unit_counts['blocked'] ?? 0 ) +
						( $unit_counts['external'] ?? 0 );

		if ( $available_units > 0 && $occupied_units > 0 ) {
			$classes[] = 'aiohm-partial-booking';
		}

		// Add availability ratio classes for advanced styling.
		$ratio = $this->calculate_availability_ratio( $unit_counts );
		if ( $ratio > 0.75 ) {
			$classes[] = 'aiohm-high-availability';
		} elseif ( $ratio > 0.25 ) {
			$classes[] = 'aiohm-medium-availability';
		} elseif ( $ratio > 0 ) {
			$classes[] = 'aiohm-low-availability';
		} else {
			$classes[] = 'aiohm-no-availability';
		}

		return array_unique( $classes );
	}

	/**
	 * Calculate availability ratio.
	 *
	 * @param array $unit_counts The unit counts.
	 * @return float Availability ratio (0.0 to 1.0).
	 */
	private function calculate_availability_ratio( array $unit_counts ): float {
		$total     = $unit_counts['total'] ?? 0;
		$available = $unit_counts['available'] ?? 0;

		if ( 0 === $total ) {
			return 0.0;
		}

		return $available / $total;
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
		// Check if accommodation counter is available.
		if ( ! class_exists( 'AIOHM_BOOKING_Accommodation_Counter' ) ) {
			return new WP_Error(
				'missing_dependency',
				__( 'Accommodation Counter class is required for partial booking color rule.', 'aiohm-booking-pro' )
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
	 * Get accommodation counter instance.
	 *
	 * @return AIOHM_BOOKING_Accommodation_Counter
	 */
	private function get_accommodation_counter(): AIOHM_BOOKING_Accommodation_Counter {
		if ( null === $this->accommodation_counter ) {
			$cell_statuses               = get_option( 'aiohm_booking_cell_statuses', array() );
			$this->accommodation_counter = new AIOHM_BOOKING_Accommodation_Counter( $cell_statuses );
		}

		return $this->accommodation_counter;
	}

	/**
	 * Get unit counts for a specific date with caching.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return array Unit counts.
	 */
	private function get_date_unit_counts( string $date ): array {
		// Check cache first.
		if ( isset( $this->unit_count_cache[ $date ] ) ) {
			return $this->unit_count_cache[ $date ];
		}

		// Get from accommodation counter.
		$accommodation_counter = $this->get_accommodation_counter();
		$unit_counts           = $accommodation_counter->get_unit_counts_for_date( $date );

		// Cache the result.
		$this->unit_count_cache[ $date ] = $unit_counts;

		return $unit_counts;
	}

	/**
	 * Clear the unit count cache.
	 */
	public function clear_cache(): void {
		$this->unit_count_cache      = array();
		$this->accommodation_counter = null;
	}

	/**
	 * Get detailed availability information for a date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return array Detailed availability info.
	 */
	public function get_availability_details( string $date ): array {
		$unit_counts = $this->get_date_unit_counts( $date );

		return array(
			'date'               => $date,
			'display_status'     => 0 === $unit_counts['available'] ? 'booked' : 'free',
			'availability_ratio' => $this->calculate_availability_ratio( $unit_counts ),
			'unit_counts'        => $unit_counts,
			'is_partial'         => $unit_counts['available'] > 0 &&
							( $unit_counts['booked'] > 0 || $unit_counts['pending'] > 0 ||
							$unit_counts['blocked'] > 0 || $unit_counts['external'] > 0 ),
		);
	}
}

// Auto-register the rule with the rule engine.
add_action(
	'aiohm_booking_register_default_rules',
	function ( $rule_engine ) {
		$rule_engine->add_rule( new AIOHM_Booking_Partial_Booking_Color_Rule() );
	},
	20
);
