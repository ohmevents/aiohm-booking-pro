<?php
/**
 * AIOHM Booking Accommodation Counter.
 *
 * Handles complex accommodation counting logic for calendar availability.
 * Extracts accommodation counting functionality to reduce complexity
 * and improve maintainability.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AIOHM_BOOKING_Accommodation_Counter class.
 */
class AIOHM_BOOKING_Accommodation_Counter {

	/**
	 * Cell statuses data.
	 *
	 * @var array
	 */
	private $cell_statuses;

	/**
	 * Accommodations cache.
	 *
	 * @var array|null
	 */
	private $accommodations_cache = null;

	/**
	 * Total accommodation count cache.
	 *
	 * @var int|null
	 */
	private $total_count_cache = null;

	/**
	 * Constructor.
	 *
	 * @param array $cell_statuses Cell statuses data.
	 */
	public function __construct( array $cell_statuses = array() ) {
		$this->cell_statuses = $cell_statuses;
	}

	/**
	 * Get unit counts for a specific date.
	 *
	 * This is the main entry point that extracts complex logic
	 * from the Calendar Rules class get_date_unit_counts method.
	 *
	 * @param string $date Date in Y-m-d format.
	 *
	 * @return array Unit counts with keys: booked, pending, blocked, external, available.
	 */
	public function get_unit_counts_for_date( string $date ): array {
		$accommodations = $this->get_accommodations();
		$total_units    = count( $accommodations );

		$counts = array(
			'booked'    => 0,
			'pending'   => 0,
			'blocked'   => 0,
			'external'  => 0,
			'available' => $total_units, // Start with all units available.
		);

		// Check status of each accommodation for this date.
		foreach ( $accommodations as $accommodation_id ) {
			$cell_key = $accommodation_id . '_' . $date . '_full';

			if ( isset( $this->cell_statuses[ $cell_key ] ) ) {
				$status = $this->cell_statuses[ $cell_key ]['status'];

				// Handle both string status and potential array corruption.
				if ( is_array( $status ) ) {
					// Skip corrupted entries - unit remains available.
					continue;
				} elseif ( isset( $counts[ $status ] ) ) {
					++$counts[ $status ];
					--$counts['available']; // This unit is not available.
				} else {
					// Unknown status, count as available (don't decrement available count).
					continue;
				}
			}
			// If no status entry exists, the unit remains available.
		}

		return $counts;
	}

	/**
	 * Get total accommodation count.
	 *
	 * @return int Total number of accommodation units.
	 */
	public function get_total_accommodation_count(): int {
		if ( null === $this->total_count_cache ) {
			$accommodations          = $this->get_accommodations();
			$this->total_count_cache = count( $accommodations );
		}

		return $this->total_count_cache;
	}

	/**
	 * Get accommodations list.
	 *
	 * This method handles the complex logic of finding accommodations
	 * from multiple sources: post type, settings, and cell statuses.
	 *
	 * @return array Array of accommodation IDs.
	 */
	public function get_accommodations(): array {
		if ( null !== $this->accommodations_cache ) {
			return $this->accommodations_cache;
		}

		// Get accommodations using the same method as the calendar module.
		$accommodations = get_posts(
			array(
				'post_type'      => 'aiohm_accommodation',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		);

		// If no accommodations found, try to get them from settings and sync posts.
		if ( empty( $accommodations ) ) {
			// Get target count from settings
			$settings    = get_option( 'aiohm_booking_settings', array() );
			$total_units = isset( $settings['available_accommodations'] ) ? (int) $settings['available_accommodations'] : 1;

			// Try to create missing accommodation posts based on the settings
			$this->ensure_accommodation_posts_exist( $total_units );

			// Try to get accommodations again after ensuring posts exist
			$accommodations = get_posts(
				array(
					'post_type'      => 'aiohm_accommodation',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				)
			);

			// If still no posts, try to extract accommodation IDs from cell statuses.
			if ( empty( $accommodations ) ) {
				$status_accommodation_ids = $this->extract_accommodation_ids_from_statuses( $this->cell_statuses );

				if ( ! empty( $status_accommodation_ids ) ) {
					$accommodations = $status_accommodation_ids;
				} else {
					// Final fallback to default count.
					$accommodations = $this->get_fallback_accommodations( $total_units );
				}
			}
		}

		$this->accommodations_cache = $accommodations;
		return $this->accommodations_cache;
	}

	/**
	 * Extract accommodation IDs from cell status data.
	 *
	 * Handles the complex logic of parsing accommodation IDs from
	 * various cell status key formats and data structures.
	 *
	 * @param array $cell_statuses Cell statuses array.
	 *
	 * @return array Array of unique accommodation IDs.
	 */
	public function extract_accommodation_ids_from_statuses( array $cell_statuses ): array {
		$status_accommodation_ids = array();

		foreach ( $cell_statuses as $key => $status_data ) {
			if ( is_array( $status_data ) && isset( $status_data['accommodation_id'] ) ) {
				// Direct accommodation ID in status data.
				$status_accommodation_ids[] = $status_data['accommodation_id'];
			} elseif ( is_string( $key ) && strpos( $key, '_full' ) !== false ) {
				// Extract ID from key like "123_2025-09-05_full".
				$parts = explode( '_', $key );
				if ( count( $parts ) >= 3 && is_numeric( $parts[0] ) ) {
					$status_accommodation_ids[] = (int) $parts[0];
				}
			}
		}

		return array_unique( $status_accommodation_ids );
	}

	/**
	 * Get fallback accommodations when none are found.
	 *
	 * Creates a simple numeric range as fallback accommodation IDs.
	 *
	 * @param int $total_units Total number of units to create.
	 *
	 * @return array Array of accommodation IDs (1 to $total_units).
	 */
	public function get_fallback_accommodations( int $total_units ): array {
		return range( 1, max( 1, $total_units ) );
	}

	/**
	 * Update cell statuses data.
	 *
	 * Allows updating the cell statuses and clears related caches.
	 *
	 * @param array $cell_statuses New cell statuses data.
	 *
	 * @return void
	 */
	public function update_cell_statuses( array $cell_statuses ): void {
		$this->cell_statuses        = $cell_statuses;
		$this->accommodations_cache = null;
		$this->total_count_cache    = null;
	}

	/**
	 * Clear all caches.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->accommodations_cache = null;
		$this->total_count_cache    = null;
	}

	/**
	 * Get accommodation status for a specific date and accommodation ID.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @param int    $accommodation_id Accommodation ID.
	 *
	 * @return string|null Status string or null if no status found.
	 */
	public function get_accommodation_status( string $date, int $accommodation_id ): ?string {
		$cell_key = $accommodation_id . '_' . $date . '_full';

		if ( ! isset( $this->cell_statuses[ $cell_key ] ) ) {
			return null;
		}

		$status = $this->cell_statuses[ $cell_key ]['status'];

		// Handle potential array corruption.
		if ( is_array( $status ) ) {
			return null;
		}

		return is_string( $status ) ? $status : null;
	}

	/**
	 * Check if an accommodation is available on a specific date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @param int    $accommodation_id Accommodation ID.
	 *
	 * @return bool True if available, false if booked/blocked/etc.
	 */
	public function is_accommodation_available( string $date, int $accommodation_id ): bool {
		$status = $this->get_accommodation_status( $date, $accommodation_id );

		// If no status is set, accommodation is available.
		if ( null === $status ) {
			return true;
		}

		// Known unavailable statuses.
		$unavailable_statuses = array( 'booked', 'pending', 'blocked', 'external' );

		return ! in_array( $status, $unavailable_statuses, true );
	}

	/**
	 * Get available accommodations for a specific date.
	 *
	 * @param string $date Date in Y-m-d format.
	 *
	 * @return array Array of available accommodation IDs.
	 */
	public function get_available_accommodations_for_date( string $date ): array {
		$accommodations = $this->get_accommodations();
		$available      = array();

		foreach ( $accommodations as $accommodation_id ) {
			if ( $this->is_accommodation_available( $date, $accommodation_id ) ) {
				$available[] = $accommodation_id;
			}
		}

		return $available;
	}

	/**
	 * Get unavailable accommodations for a specific date.
	 *
	 * @param string $date Date in Y-m-d format.
	 *
	 * @return array Array with status as key and accommodation IDs as values.
	 */
	public function get_unavailable_accommodations_for_date( string $date ): array {
		$accommodations = $this->get_accommodations();
		$unavailable    = array(
			'booked'   => array(),
			'pending'  => array(),
			'blocked'  => array(),
			'external' => array(),
		);

		foreach ( $accommodations as $accommodation_id ) {
			$status = $this->get_accommodation_status( $date, $accommodation_id );

			if ( $status && isset( $unavailable[ $status ] ) ) {
				$unavailable[ $status ][] = $accommodation_id;
			}
		}

		return $unavailable;
	}

	/**
	 * Ensure accommodation posts exist for the specified count.
	 *
	 * Creates accommodation posts if they don't exist to match the settings.
	 *
	 * @param int $target_count Target number of accommodations.
	 *
	 * @return void
	 */
	private function ensure_accommodation_posts_exist( int $target_count ): void {
		// Get current accommodation posts
		$current_posts = get_posts(
			array(
				'post_type'      => 'aiohm_accommodation',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
			)
		);

		$current_count = count( $current_posts );

		// Only create posts if we have fewer than target
		if ( $current_count < $target_count ) {
			// Get global settings for accommodation type and pricing
			$global_settings         = get_option( 'aiohm_booking_settings', array() );
			$accommodation_type      = $global_settings['accommodation_type'] ?? 'room';
			$default_price           = floatval( $global_settings['default_price'] ?? 0 );
			$default_earlybird_price = floatval( $global_settings['default_earlybird_price'] ?? 0 );

			// Get accommodation type name
			$accommodation_types = array(
				'room'      => 'Room',
				'apartment' => 'Apartment',
				'house'     => 'House',
				'cabin'     => 'Cabin',
				'tent'      => 'Tent',
				'bed'       => 'Bed',
				'suite'     => 'Suite',
				'villa'     => 'Villa',
				'studio'    => 'Studio',
				'loft'      => 'Loft',
			);
			$singular_name       = $accommodation_types[ $accommodation_type ] ?? 'Accommodation';

			// Create missing posts
			for ( $i = $current_count + 1; $i <= $target_count; $i++ ) {
				$post_title = $singular_name . ' ' . $i;

				$post_id = wp_insert_post(
					array(
						'post_title'  => $post_title,
						'post_type'   => 'aiohm_accommodation',
						'post_status' => 'publish',
						'menu_order'  => $i,
					)
				);

				if ( $post_id && ! is_wp_error( $post_id ) ) {
					// Add accommodation meta
					update_post_meta( $post_id, '_aiohm_booking_accommodation_number', $i );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_type', $accommodation_type );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_price', $default_price );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_earlybird_price', $default_earlybird_price );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_units', 1 );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_max_guests', 2 ); // Default maximum guests
				}
			}
		}
	}
}
