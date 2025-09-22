<?php
/**
 * Calendar Rule Engine
 *
 * Manages calendar rule registration, execution, and coordination using the Strategy Pattern.
 * This engine dramatically reduces complexity by separating rule logic into individual classes.
 *
 * @package AIOHM_Booking
 * @subpackage Core
 * @since 1.3.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule Engine for managing calendar rules.
 *
 * This class implements the Strategy Pattern to manage and execute calendar rules,
 * providing a clean, extensible architecture for complex business logic.
 */
class AIOHM_Booking_Rule_Engine {

	/**
	 * Registered rules indexed by ID.
	 *
	 * @var AIOHM_Booking_Calendar_Rule[]
	 */
	private array $rules = array();

	/**
	 * Rules sorted by priority and dependencies.
	 *
	 * @var AIOHM_Booking_Calendar_Rule[]|null
	 */
	private ?array $sorted_rules = null;

	/**
	 * Context-specific rule cache.
	 *
	 * @var array
	 */
	private array $context_cache = array();

	/**
	 * Rule execution statistics.
	 *
	 * @var array
	 */
	private array $stats = array(
		'executions' => 0,
		'errors'     => 0,
		'cache_hits' => 0,
		'total_time' => 0.0,
	);

	/**
	 * The single instance of the class.
	 *
	 * @var AIOHM_Booking_Rule_Engine|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return AIOHM_Booking_Rule_Engine
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize the rule engine.
	 */
	private function __construct() {
		$this->register_default_rules();
		$this->setup_hooks();
	}

	/**
	 * Register a new rule.
	 *
	 * @param AIOHM_Booking_Calendar_Rule $rule The rule to register.
	 * @return bool|WP_Error Success or error.
	 * @throws Exception When rule registration fails.
	 */
	public function add_rule( AIOHM_Booking_Calendar_Rule $rule ) {
		try {
			$rule_id = $rule->get_id();

			// Validate rule ID.
			if ( empty( $rule_id ) || ! is_string( $rule_id ) ) {
				return new WP_Error(
					'invalid_rule_id',
					__( 'Rule ID must be a non-empty string.', 'aiohm-booking-pro' )
				);
			}

			// Check for duplicate IDs.
			if ( isset( $this->rules[ $rule_id ] ) ) {
				return new WP_Error(
					'duplicate_rule_id',
					/* translators: %s: rule ID */
					sprintf( __( 'Rule with ID "%s" already exists.', 'aiohm-booking-pro' ), $rule_id )
				);
			}

			// Validate rule configuration.
			$validation = $rule->validate_config();
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Register the rule.
			$this->rules[ $rule_id ] = $rule;
			$this->invalidate_cache();

			do_action( 'aiohm_booking_rule_registered', $rule_id, $rule );

			return true;

		} catch ( Throwable $e ) {
			return new WP_Error(
				'rule_registration_error',
				/* translators: %s: error message */
				sprintf( __( 'Failed to register rule: %s', 'aiohm-booking-pro' ), $e->getMessage() )
			);
		}
	}

	/**
	 * Remove a registered rule.
	 *
	 * @param string $rule_id The rule ID to remove.
	 * @return bool Success status.
	 */
	public function remove_rule( string $rule_id ): bool {
		if ( ! isset( $this->rules[ $rule_id ] ) ) {
			return false;
		}

		unset( $this->rules[ $rule_id ] );
		$this->invalidate_cache();

		do_action( 'aiohm_booking_rule_removed', $rule_id );

		return true;
	}

	/**
	 * Get a registered rule by ID.
	 *
	 * @param string $rule_id The rule ID.
	 * @return AIOHM_Booking_Calendar_Rule|null The rule or null if not found.
	 */
	public function get_rule( string $rule_id ): ?AIOHM_Booking_Calendar_Rule {
		return $this->rules[ $rule_id ] ?? null;
	}

	/**
	 * Get all registered rules.
	 *
	 * @param bool $include_disabled Whether to include disabled rules.
	 * @return AIOHM_Booking_Calendar_Rule[]
	 */
	public function get_rules( bool $include_disabled = false ): array {
		if ( $include_disabled ) {
			return $this->rules;
		}

		return array_filter(
			$this->rules,
			function ( $rule ) {
				return $rule->is_enabled();
			}
		);
	}

	/**
	 * Get rules for a specific context.
	 *
	 * @param string $context The context to filter by.
	 * @param array  $data    Additional context data.
	 * @return AIOHM_Booking_Calendar_Rule[]
	 */
	public function get_rules_for_context( string $context, array $data = array() ): array {
		$cache_key = md5( $context . wp_json_encode( $data ) );

		if ( isset( $this->context_cache[ $cache_key ] ) ) {
			++$this->stats['cache_hits'];
			return $this->context_cache[ $cache_key ];
		}

		$sorted_rules  = $this->get_sorted_rules();
		$context_rules = array();

		foreach ( $sorted_rules as $rule ) {
			if ( ! $rule->is_enabled() ) {
				continue;
			}

			if ( in_array( $context, $rule->get_contexts(), true ) &&
				$rule->applies_to_context( $context, $data ) ) {
				$context_rules[] = $rule;
			}
		}

		$this->context_cache[ $cache_key ] = $context_rules;

		return $context_rules;
	}

	/**
	 * Apply rules to calendar data for a specific context.
	 *
	 * @param string $context       The execution context.
	 * @param array  $calendar_data The calendar data to process.
	 * @param array  $context_data  Additional context data.
	 * @return array|WP_Error Processed calendar data or error.
	 * @throws Exception When rule execution fails.
	 */
	public function apply_rules( string $context, array $calendar_data, array $context_data = array() ) {
		$start_time = microtime( true );

		try {
			$rules = $this->get_rules_for_context( $context, $context_data );

			if ( empty( $rules ) ) {
				return $calendar_data;
			}

			$processed_data = $calendar_data;
			$executed_rules = array();

			foreach ( $rules as $rule ) {
				// Check dependencies.
				if ( ! $this->check_dependencies( $rule, $executed_rules ) ) {
					continue;
				}

				$result = $rule->execute( $processed_data, $context_data );

				if ( is_wp_error( $result ) ) {
					++$this->stats['errors'];

					do_action(
						'aiohm_booking_rule_execution_error',
						$rule->get_id(),
						$result,
						$context,
						$context_data
					);

					return $result;
				}

				$processed_data   = $result;
				$executed_rules[] = $rule->get_id();

				do_action(
					'aiohm_booking_rule_executed',
					$rule->get_id(),
					$context,
					$processed_data
				);
			}

			++$this->stats['executions'];
			$this->stats['total_time'] += microtime( true ) - $start_time;

			return $processed_data;

		} catch ( Throwable $e ) {
			++$this->stats['errors'];

			return new WP_Error(
				'rule_execution_failed',
				/* translators: %s: error message */
				sprintf( __( 'Rule execution failed: %s', 'aiohm-booking-pro' ), $e->getMessage() )
			);
		}
	}

	/**
	 * Check if a rule's dependencies are satisfied.
	 *
	 * @param AIOHM_Booking_Calendar_Rule $rule           The rule to check.
	 * @param array                       $executed_rules Already executed rule IDs.
	 * @return bool True if dependencies are satisfied.
	 */
	private function check_dependencies( AIOHM_Booking_Calendar_Rule $rule, array $executed_rules ): bool {
		$dependencies = $rule->get_dependencies();

		if ( empty( $dependencies ) ) {
			return true;
		}

		foreach ( $dependencies as $dependency_id ) {
			if ( ! in_array( $dependency_id, $executed_rules, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get rules sorted by priority and dependencies.
	 *
	 * @return AIOHM_Booking_Calendar_Rule[]
	 */
	private function get_sorted_rules(): array {
		if ( null !== $this->sorted_rules ) {
			return $this->sorted_rules;
		}

		$rules = $this->get_rules();

		// Sort by priority first.
		uasort(
			$rules,
			function ( $a, $b ) {
				return $a->get_priority() <=> $b->get_priority();
			}
		);

		$this->sorted_rules = array_values( $rules );

		return $this->sorted_rules;
	}

	/**
	 * Register default rules.
	 */
	private function register_default_rules(): void {
		// Rules will be registered by their respective classes.
		// This method provides a hook for future default rule registration.
		do_action( 'aiohm_booking_register_default_rules', $this );
	}

	/**
	 * Setup WordPress hooks.
	 */
	private function setup_hooks(): void {
		add_action( 'init', array( $this, 'load_rule_classes' ) );
		add_action( 'aiohm_booking_clear_cache', array( $this, 'clear_cache' ) );
	}

	/**
	 * Load rule classes from the calendar-rules directory.
	 */
	public function load_rule_classes(): void {
		$rules_dir = AIOHM_BOOKING_PATH . 'includes/core/calendar-rules/';

		if ( ! is_dir( $rules_dir ) ) {
			return;
		}

		$rule_files = glob( $rules_dir . 'class-*.php' );

		foreach ( $rule_files as $file ) {
			require_once $file;
		}

		do_action( 'aiohm_booking_rule_classes_loaded', $this );
	}

	/**
	 * Invalidate all caches.
	 */
	private function invalidate_cache(): void {
		$this->sorted_rules  = null;
		$this->context_cache = array();
	}

	/**
	 * Clear all caches.
	 */
	public function clear_cache(): void {
		$this->invalidate_cache();
		do_action( 'aiohm_booking_rule_cache_cleared' );
	}

	/**
	 * Get execution statistics.
	 *
	 * @return array The statistics.
	 */
	public function get_stats(): array {
		return $this->stats;
	}

	/**
	 * Reset execution statistics.
	 */
	public function reset_stats(): void {
		$this->stats = array(
			'executions' => 0,
			'errors'     => 0,
			'cache_hits' => 0,
			'total_time' => 0.0,
		);
	}

	/**
	 * Check if the rule engine is healthy.
	 *
	 * @return bool|WP_Error True if healthy, WP_Error if not.
	 * @throws Exception When health check fails.
	 */
	public function health_check() {
		try {
			// Check if rules are loaded.
			if ( empty( $this->rules ) ) {
				return new WP_Error(
					'no_rules_loaded',
					__( 'No rules are currently loaded.', 'aiohm-booking-pro' )
				);
			}

			// Check for circular dependencies.

			// Check rule configurations.
			foreach ( $this->rules as $rule_id => $rule ) {
				$validation = $rule->validate_config();
				if ( is_wp_error( $validation ) ) {
					return new WP_Error(
						'invalid_rule_config',
						sprintf(
							/* translators: %1$s: rule ID, %2$s: error message */
							__( 'Rule "%1$s" has invalid configuration: %2$s', 'aiohm-booking-pro' ),
							$rule_id,
							$validation->get_error_message()
						)
					);
				}
			}

			return true;

		} catch ( Throwable $e ) {
			return new WP_Error(
				'health_check_failed',
				/* translators: %s: error message */
				sprintf( __( 'Rule engine health check failed: %s', 'aiohm-booking-pro' ), $e->getMessage() )
			);
		}
	}

	/**
	 * Prevent cloning of the instance.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the instance.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}
