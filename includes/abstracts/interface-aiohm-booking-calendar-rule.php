<?php

namespace AIOHM_Booking_PRO\Abstracts;
/**
 * Calendar Rule Interface
 *
 * Defines the contract for all calendar rules in the AIOHM Booking system.
 * Each rule implements specific calendar logic using the Strategy Pattern.
 *
 * @package AIOHM_Booking
 * @subpackage Abstracts
 * @since 1.3.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for all calendar rules.
 *
 * This interface defines the contract that all calendar rules must implement,
 * enabling a clean Strategy Pattern architecture for rule processing.
 */
interface AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_Booking_Calendar_Rule {

	/**
	 * Get the unique identifier for this rule.
	 *
	 * @return string The rule identifier
	 */
	public function get_id(): string;

	/**
	 * Get the human-readable name for this rule.
	 *
	 * @return string The rule name
	 */
	public function get_name(): string;

	/**
	 * Get the rule description.
	 *
	 * @return string The rule description
	 */
	public function get_description(): string;

	/**
	 * Get the execution priority for this rule.
	 * Lower numbers execute first.
	 *
	 * @return int The priority (0-100, default 50)
	 */
	public function get_priority(): int;

	/**
	 * Get the contexts where this rule applies.
	 *
	 * @return array Array of context strings (e.g., ['calendar_display', 'booking_validation'])
	 */
	public function get_contexts(): array;

	/**
	 * Check if this rule applies to the given context and data.
	 *
	 * @param string $context The execution context.
	 * @param array  $data    The context data.
	 * @return bool True if rule should execute
	 */
	public function applies_to_context( string $context, array $data = array() ): bool;

	/**
	 * Execute the rule logic.
	 *
	 * @param array $calendar_data The calendar data to process.
	 * @param array $context_data  Additional context data.
	 * @return array|WP_Error Modified calendar data or error
	 */
	public function execute( array $calendar_data, array $context_data = array() );

	/**
	 * Get any dependencies this rule has on other rules.
	 *
	 * @return array Array of rule IDs this rule depends on
	 */
	public function get_dependencies(): array;

	/**
	 * Validate the rule configuration.
	 *
	 * @param array $config The rule configuration.
	 * @return true|WP_Error True if valid, WP_Error if invalid
	 */
	public function validate_config( array $config = array() );

	/**
	 * Get the rule version for compatibility checks.
	 *
	 * @return string The rule version
	 */
	public function get_version(): string;

	/**
	 * Check if the rule is enabled.
	 *
	 * @return bool True if enabled
	 */
	public function is_enabled(): bool;

	/**
	 * Enable or disable the rule.
	 *
	 * @param bool $enabled Whether to enable the rule.
	 * @return bool Success status
	 */
	public function set_enabled( bool $enabled ): bool;
}
