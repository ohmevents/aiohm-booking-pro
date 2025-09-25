<?php
/**
 * EventOn Integration Module
 *
 * Integrates with EventOn plugin to import event data into AIOHM Booking system.
 * Allows importing events from EventOn with data mapping for the booking system.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking EventOn Integration Module Class
 *
 * Provides EventOn import functionality without settings UI.
 * Handles importing event data from EventOn posts.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Module_EventOn extends AIOHM_BOOKING_Module_Abstract {

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Get module UI definition.
	 *
	 * Returns a minimal definition to enable module loading but hide from UI.
	 *
	 * @since  2.0.0
	 * @return array Module UI definition array.
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'eventon',
			'name'                => __( 'EventOn Integration', 'aiohm-booking-pro' ),
			'description'         => __( 'Import events from EventOn plugin directly into your booking system.', 'aiohm-booking-pro' ),
			'icon'                => '📅',
			'category'            => 'integration',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 20,
			'has_settings'        => false,
			'has_admin_page'      => false,
			'visible_in_settings' => false,
			'hidden_in_settings'  => true,
		);
	}


	/**
	 * Get default settings for this module.
	 *
	 * @since  2.0.0
	 * @return array Default settings array.
	 */
	public function get_default_settings() {
		return array();
	}

	/**
	 * Get settings fields configuration.
	 *
	 * @since  2.0.0
	 * @return array Settings fields configuration array.
	 */
	public function get_settings_fields() {
		return array();
	}

	/**
	 * Check if EventOn plugin is active.
	 *
	 * @since  2.0.0
	 * @return bool True if EventOn is active, false otherwise.
	 */
	private function is_eventon_active() {
		// Check if EventOn class exists
		if ( class_exists( 'EventON' ) ) {
			return true;
		}

		// Check if EventOn plugin is active
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return function_exists( 'is_plugin_active' ) && is_plugin_active( 'eventON/eventon.php' );
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * Sets up action hooks for AJAX endpoints and form handling.
	 *
	 * @since  2.0.0
	 */
	protected function init_hooks() {
		// Only initialize if EventOn is active
		if ( ! $this->is_eventon_active() ) {
			return;
		}

		// AJAX handlers for EventOn import
		add_action( 'wp_ajax_aiohm_booking_import_eventon_events', array( $this, 'ajax_import_eventon_events' ) );
		add_action( 'wp_ajax_aiohm_booking_get_eventon_events_list', array( $this, 'ajax_get_eventon_events_list' ) );
	}


	/**
	 * Get EventOn events.
	 *
	 * @since  2.0.0
	 * @return array Array of EventOn events.
	 */
	public function get_eventon_events() {
		if ( ! $this->is_eventon_active() ) {
			return array();
		}

		// Check cache first for performance
		$cache_key = 'aiohm_booking_eventon_events';
		$cached_events = wp_cache_get( $cache_key, 'aiohm_booking' );
		if ( false !== $cached_events ) {
			return $cached_events;
		}

		$events = array();

		// Get EventOn events from WordPress posts
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for ordering EventOn events by start date
		$eventon_posts = get_posts( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for EventON integration, limited to 100 posts, ordering by event date meta key necessary
			array(
				'post_type'      => 'ajde_events',
				'post_status'    => array( 'publish', 'future' ), // Include both published and scheduled events
				'posts_per_page' => 100,
				'orderby'        => 'meta_value',
				'meta_key'       => 'evcal_srow', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Essential for chronological EventON event ordering
				'order'          => 'ASC',
			)
		);

		foreach ( $eventon_posts as $post ) {
			$event_data = $this->extract_eventon_event_data( $post );
			if ( $event_data ) {
				$events[] = $event_data;
			}
		}

		// Cache the results for 15 minutes to improve performance
		wp_cache_set( $cache_key, $events, 'aiohm_booking', 15 * MINUTE_IN_SECONDS );

		return $events;
	}

	/**
	 * Extract EventOn event data.
	 *
	 * @since  2.0.0
	 * @param WP_Post $post EventOn event post.
	 * @return array|false Event data array or false on failure.
	 */
	private function extract_eventon_event_data( $post ) {
		if ( ! $post ) {
			return false;
		}

		// Get EventOn meta data
		$start_date = get_post_meta( $post->ID, 'evcal_srow', true );
		$end_date   = get_post_meta( $post->ID, 'evcal_erow', true );
		$all_day    = get_post_meta( $post->ID, 'evcal_allday', true );

		// Convert timestamps to readable dates
		$start_datetime = $start_date ? gmdate( 'Y-m-d H:i:s', $start_date ) : '';
		$end_datetime   = $end_date ? gmdate( 'Y-m-d H:i:s', $end_date ) : '';

		// Format display date and time
		$date_display = $start_date ? gmdate( 'M j, Y', $start_date ) : '';
		$time_display = '';

		if ( $start_date && ! $all_day ) {
			$time_display = gmdate( 'g:i A', $start_date );
			if ( $end_date && $end_date !== $start_date ) {
				$time_display .= ' - ' . gmdate( 'g:i A', $end_date );
			}
		}

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'description' => $this->extract_clean_content( $post->post_content ),
			'date'        => $date_display,
			'time'        => $time_display,
			'start_date'  => $start_datetime,
			'end_date'    => $end_datetime,
			'all_day'     => (bool) $all_day,
		);
	}

	/**
	 * Extract clean text content from WordPress blocks.
	 *
	 * Parses Gutenberg blocks and extracts clean text content without markup.
	 *
	 * @since  2.0.0
	 * @param string $content Raw post content that may contain Gutenberg blocks.
	 * @return string Clean text content.
	 */
	private function extract_clean_content( $content ) {
		if ( empty( $content ) ) {
			return '';
		}

		// Check if content contains Gutenberg blocks
		if ( strpos( $content, '<!-- wp:' ) === false ) {
			// Not Gutenberg blocks, return cleaned content
			return wp_strip_all_tags( $content );
		}

		$clean_content = '';

		// Parse Gutenberg blocks
		$blocks = parse_blocks( $content );

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				// Handle different block types
				switch ( $block['blockName'] ) {
					case 'core/paragraph':
					case 'core/heading':
					case 'core/list-item':
						if ( ! empty( $block['innerHTML'] ) ) {
							// Extract text from innerHTML, removing block comments
							$text = preg_replace( '/<!--.*?-->/', '', $block['innerHTML'] );
							$text = wp_strip_all_tags( $text );
							if ( ! empty( trim( $text ) ) ) {
								$clean_content .= trim( $text ) . ' ';
							}
						}
						break;

					case 'core/list':
						if ( ! empty( $block['innerBlocks'] ) ) {
							foreach ( $block['innerBlocks'] as $list_item ) {
								if ( ! empty( $list_item['innerHTML'] ) ) {
									$text = preg_replace( '/<!--.*?-->/', '', $list_item['innerHTML'] );
									$text = wp_strip_all_tags( $text );
									if ( ! empty( trim( $text ) ) ) {
										$clean_content .= '• ' . trim( $text ) . ' ';
									}
								}
							}
						}
						break;

					case 'core/quote':
						if ( ! empty( $block['innerHTML'] ) ) {
							$text = preg_replace( '/<!--.*?-->/', '', $block['innerHTML'] );
							$text = wp_strip_all_tags( $text );
							if ( ! empty( trim( $text ) ) ) {
								$clean_content .= '"' . trim( $text ) . '" ';
							}
						}
						break;

					default:
						// For other blocks, try to extract any text content
						if ( ! empty( $block['innerHTML'] ) ) {
							$text = preg_replace( '/<!--.*?-->/', '', $block['innerHTML'] );
							$text = wp_strip_all_tags( $text );
							if ( ! empty( trim( $text ) ) ) {
								$clean_content .= trim( $text ) . ' ';
							}
						}
						break;
				}
			} elseif ( ! empty( $block['innerHTML'] ) ) {
				// Classic editor content or other HTML
				$text = wp_strip_all_tags( $block['innerHTML'] );
				if ( ! empty( trim( $text ) ) ) {
					$clean_content .= trim( $text ) . ' ';
				}
			}
		}

		// Clean up extra whitespace and return
		return trim( preg_replace( '/\s+/', ' ', $clean_content ) );
	}

	/**
	 * AJAX handler for getting EventOn events list.
	 *
	 * @since  2.0.0
	 */
	public function ajax_get_eventon_events_list() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aiohm_booking_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$events = $this->get_eventon_events();

		wp_send_json_success(
			array(
				'events' => $events,
				'count'  => count( $events ),
			)
		);
	}

	/**
	 * AJAX handler for importing EventOn events.
	 *
	 * @since  2.0.0
	 */
	public function ajax_import_eventon_events() {
		// Verify nonce and permissions
		if ( ! check_ajax_referer( 'aiohm_booking_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( -1 );
		}

		$event_ids     = isset( $_POST['event_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['event_ids'] ) ) : array();
		$import_limit  = isset( $_POST['import_limit'] ) ? intval( wp_unslash( $_POST['import_limit'] ) ) : 10;
		$import_status = isset( $_POST['import_status'] ) ? sanitize_text_field( wp_unslash( $_POST['import_status'] ) ) : 'draft';

		if ( empty( $event_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No events selected for import.', 'aiohm-booking-pro' ) ) );
		}

		// Limit the number of events to import
		$event_ids = array_slice( $event_ids, 0, $import_limit );

		$results = array(
			'success' => array(),
			'errors'  => array(),
			'total'   => count( $event_ids ),
		);

		foreach ( $event_ids as $event_id ) {
			$result = $this->import_single_eventon_event( $event_id, $import_status );

			if ( $result['success'] ) {
				$results['success'][] = $result;
			} else {
				$results['errors'][] = $result;
			}
		}

		wp_send_json_success( $results );
	}

	/**
	 * Import a single EventOn event.
	 *
	 * @since  2.0.0
	 * @param int    $event_id EventOn event ID.
	 * @param string $status Import status.
	 * @return array Import result.
	 */
	private function import_single_eventon_event( $event_id, $status = 'draft' ) {
		$post = get_post( $event_id );

		if ( ! $post || $post->post_type !== 'ajde_events' ) {
			return array(
				'success'  => false,
				'event_id' => $event_id,
				'message'  => __( 'EventOn event not found.', 'aiohm-booking-pro' ),
			);
		}

		$event_data = $this->extract_eventon_event_data( $post );

		if ( ! $event_data ) {
			return array(
				'success'  => false,
				'event_id' => $event_id,
				'message'  => __( 'Failed to extract event data.', 'aiohm-booking-pro' ),
			);
		}

		// Get current AIOHM booking events using compatible method
		$existing_events = AIOHM_BOOKING_Module_Tickets::get_events_data();

		// Check if event already exists (by EventOn ID)
		foreach ( $existing_events as $existing_event ) {
			if ( isset( $existing_event['eventon_id'] ) && $existing_event['eventon_id'] == $event_id ) {
				return array(
					'success'  => false,
					'event_id' => $event_id,
					/* translators: %s: Event title */
					'message'  => sprintf( __( 'Event "%s" already imported.', 'aiohm-booking-pro' ), $event_data['title'] ),
				);
			}
		}

		// Create new AIOHM booking event
		$new_event = array(
			'title'           => $event_data['title'],
			'description'     => wp_trim_words( $event_data['description'], 50 ),
			'event_date'      => $event_data['start_date'] ? gmdate( 'Y-m-d', strtotime( $event_data['start_date'] ) ) : '',
			'event_time'      => $event_data['start_date'] && ! $event_data['all_day'] ? gmdate( 'H:i', strtotime( $event_data['start_date'] ) ) : '',
			'event_end_date'  => $event_data['end_date'] ? gmdate( 'Y-m-d', strtotime( $event_data['end_date'] ) ) : '',
			'event_end_time'  => $event_data['end_date'] && ! $event_data['all_day'] ? gmdate( 'H:i', strtotime( $event_data['end_date'] ) ) : '',
			'event_type'      => 'EventOn Import',
			'price'           => 0, // Default price, user can modify later
			'capacity'        => 50, // Default capacity, user can modify later
			'available_seats' => 50,
			'eventon_id'      => $event_id, // Keep reference to original EventOn event
			'import_source'   => 'eventon',
			'import_date'     => current_time( 'mysql' ),
		);

		// Add the new event to existing events
		$existing_events[] = $new_event;

		// Save using modern CPT approach if tickets module is available
		$tickets_module = AIOHM_BOOKING_Module_Registry::get_module_instance( 'tickets' );
		
		if ( $tickets_module && method_exists( $tickets_module, 'create_event_cpt' ) ) {
			// Create CPT version of the event
			$post_id = $tickets_module->create_event_cpt( $new_event );
			if ( $post_id && ! is_wp_error( $post_id ) ) {
				// Save EventON specific meta
				update_post_meta( $post_id, '_eventon_id', $new_event['eventon_id'] );
			}
		}

		return array(
			'success'  => true,
			'event_id' => $event_id,
			'title'    => $event_data['title'],
			/* translators: %s: Event title */
			'message'  => sprintf( __( 'Event "%s" imported successfully.', 'aiohm-booking-pro' ), $event_data['title'] ),
		);
	}
}
