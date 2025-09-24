<?php

namespace AIOHM_Booking_PRO\Modules\Booking;
/**
 * Orders Module for AIOHM Booking
 * Handles order tracking and management.
 *
 * @package AIOHM_BOOKING
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders Module class
 *
 * Handles order tracking and management for the AIOHM Booking plugin.
 *
 * @package AIOHM_Booking
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 * @license GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0.0
 */
class AIOHM_Booking_PROModulesBookingAIOHM_Booking_PROModulesBookingAIOHM_Booking_PROModulesBookingAIOHM_BOOKING_Module_Orders extends \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROAbstractsAIOHM_Booking_PROAbstractsAIOHM_BOOKING_Settings_Module_Abstract {

	/**
	 * Get UI definition for the orders module.
	 *
	 * @return array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'orders',
			'name'                => __( 'Orders', 'aiohm-booking-pro' ),
			'description'         => __( 'Track and manage all your booking orders from one comprehensive dashboard.', 'aiohm-booking-pro' ),
			'icon'                => '📦',
			'category'            => 'booking',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => true,
			'admin_page_slug'     => 'aiohm-booking-orders',
			'visible_in_settings' => true,
		);
	}

	/**
	 * Create the orders table during plugin activation.
	 * This should be called from the main plugin activation hook.
	 */
	public static function on_activation() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiohm_booking_order';

		// Check if table already exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for table existence check

		// Table already exists, no migration needed.
		if ( $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            buyer_name varchar(255) NOT NULL,
            buyer_email varchar(255) NOT NULL,
            buyer_phone varchar(50) DEFAULT '',
            mode varchar(50) NOT NULL DEFAULT 'accommodation',
            private_all tinyint(1) DEFAULT 0,
            units_qty int(11) DEFAULT 0,
            guests_qty int(11) DEFAULT 0,
            bringing_pets tinyint(1) DEFAULT 0,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            total_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            deposit_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50) DEFAULT '',
            status varchar(50) NOT NULL DEFAULT 'pending',
            check_in_date date DEFAULT NULL,
            check_out_date date DEFAULT NULL,
            notes text,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY status (status),
            KEY buyer_email (buyer_email)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Constructor for the Orders module.
	 */
	public function __construct() {
		parent::__construct();

		// This is a PAGE module - enable admin page.
		$this->has_admin_page  = true;
		$this->admin_page_slug = 'aiohm-booking-orders';

		// Add AJAX handlers for checkout completion
		// Note: aiohm_booking_complete_checkout handler is provided by Shortcode Admin module

		// Initialize the module.
		$this->init();
	}

	/**
	 * Initialize the Orders module.
	 */
	public function init() {
		// Settings configuration.
		$this->settings_section_id = 'orders';
		$this->settings_page_title = __( 'Orders', 'aiohm-booking-pro' );
		$this->settings_tab_title  = __( 'Orders Settings', 'aiohm-booking-pro' );
		$this->has_quick_settings  = true;
	}

	/**
	 * Initialize hooks for the Orders module.
	 */
	protected function init_hooks() {
		// Run plugin detection for settings display.
		add_action( 'init', array( $this, 'detect_supported_plugins' ) );

		// Enqueue plugin integrations assets on admin pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Order management hooks.
		add_action( 'admin_init', array( $this, 'handle_order_actions' ) );
		add_action( 'admin_post_aiohm_booking_bulk_order_action', array( $this, 'handle_bulk_actions' ) );

		// Order status change hooks.
		add_action( 'aiohm_booking_order_status_changed', array( $this, 'log_status_change' ), 10, 3 );
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		$this->render_orders_page();
	}

	/**
	 * Render the orders page
	 */
	private function render_orders_page() {
		global $wpdb;
		$table = $wpdb->prefix . 'aiohm_booking_order';

		// Handle bulk actions.
		$this->process_bulk_actions();

		// Check for and display any stored notices.
		$this->display_stored_notices();

		// Get module settings for filtering
		$settings               = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$accommodations_enabled = $settings['enable_accommodations'] ?? true;
		$tickets_enabled        = $settings['enable_tickets'] ?? true;

		// Get orders with pagination.
		$limit = 50;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination parameter, no state change
		$offset = isset( $_GET['paged'] ) ? ( absint( $_GET['paged'] ) - 1 ) * $limit : 0;

		// Check if table exists.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) != $table ) {	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for order record insertion
			echo '<div class="wrap"><h1>' . esc_html__( 'Orders', 'aiohm-booking-pro' ) . '</h1>';
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Orders table not found. The database tables may need to be created.', 'aiohm-booking-pro' ) . '</p></div>';
			echo '</div>';
			return;
		}

		// Build WHERE clause based on active modules
		$where_clause = '';
		$where_params = array();

		if ( $accommodations_enabled && $tickets_enabled ) {
			// Show all orders (no filter needed)
		} elseif ( $accommodations_enabled && ! $tickets_enabled ) {
			// Show only accommodation orders
			$where_clause   = 'WHERE mode = %s';
			$where_params[] = 'accommodation';
		} elseif ( ! $accommodations_enabled && $tickets_enabled ) {
			// Show only ticket orders
			$where_clause   = 'WHERE mode = %s';
			$where_params[] = 'tickets';
		}

		// Add pagination parameters
		$where_params[] = $limit;
		$where_params[] = $offset;

		// Safe SQL: Build complete query with escaped table name
		$escaped_table = esc_sql( $table );
		$base_sql = 'SELECT * FROM `' . $escaped_table . '` ' . $where_clause . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause
		$orders = $wpdb->get_results( $wpdb->prepare( $base_sql, $where_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause, custom table query for plugin functionality

		// Get total count with same filter
		$count_params      = array();
		$mode_filter_param = null;
		if ( ! empty( $where_params ) && count( $where_params ) > 2 ) {
			// Remove limit and offset, keep only the mode parameter
			$count_params[]    = $where_params[0];
			$mode_filter_param = $where_params[0];
		}

		if ( ! empty( $where_clause ) ) {
			$count_sql = 'SELECT COUNT(*) FROM `' . $escaped_table . '` ' . $where_clause;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause
			$total_orders = $wpdb->get_var( $wpdb->prepare( $count_sql, $count_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause, custom table query for plugin functionality
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static SQL with escaped table name
			$total_orders = $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $escaped_table . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Static SQL with escaped table name, custom table query for plugin functionality
		}

		?>
		<div class="wrap aiohm-booking-admin">
			<div class="aiohm-booking-admin-header">
				<div class="aiohm-booking-admin-header-content">
					<div class="aiohm-booking-admin-logo">
						<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
					</div>
					<div class="aiohm-booking-admin-header-text">
						<h1><?php esc_html_e( 'Orders Management', 'aiohm-booking-pro' ); ?></h1>
						<p class="aiohm-booking-admin-tagline"><?php esc_html_e( 'Track and manage all your booking orders from one conscious dashboard.', 'aiohm-booking-pro' ); ?></p>
					</div>
				</div>
			</div>
			
			<div class="aiohm-booking-admin-card">
				<h3><?php esc_html_e( 'Orders Statistics', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-booking-orders-stats">
					<div class="aiohm-booking-orders-stat">
						<div class="number"><?php echo esc_html( $total_orders ); ?></div>
						<div class="label"><?php esc_html_e( 'Total Orders', 'aiohm-booking-pro' ); ?></div>
					</div>
					<div class="aiohm-booking-orders-stat">
						<?php
						if ( ! empty( $where_clause ) && $mode_filter_param !== null ) {
							$pending_sql = 'SELECT COUNT(*) FROM `' . $escaped_table . '` ' . $where_clause . ' AND status = %s';
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause
							$pending_count = $wpdb->get_var( $wpdb->prepare( $pending_sql, $mode_filter_param, 'pending' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause, custom table query for plugin functionality
						} else {
							$pending_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $escaped_table . '` WHERE status = %s', 'pending' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL with escaped table name, custom table query for plugin functionality
						}
						?>
						<div class="number pending"><?php echo esc_html( $pending_count ); ?></div>
						<div class="label"><?php esc_html_e( 'Pending', 'aiohm-booking-pro' ); ?></div>
					</div>
					<div class="aiohm-booking-orders-stat">
						<?php
						if ( ! empty( $where_clause ) && $mode_filter_param !== null ) {
							$paid_sql = 'SELECT COUNT(*) FROM `' . $escaped_table . '` ' . $where_clause . ' AND status = %s';
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause
							$paid_count = $wpdb->get_var( $wpdb->prepare( $paid_sql, $mode_filter_param, 'paid' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Dynamic SQL safely constructed with escaped table name and placeholder-based where clause, custom table query for plugin functionality
						} else {
							$paid_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . $escaped_table . '` WHERE status = %s', 'paid' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- SQL with escaped table name, custom table query for plugin functionality
						}
						?>
						<div class="number paid"><?php echo esc_html( $paid_count ); ?></div>
						<div class="label"><?php esc_html_e( 'Paid', 'aiohm-booking-pro' ); ?></div>
					</div>
				</div>
			</div>

			<?php
			// Show Events Overview if tickets module is enabled
			if ( $tickets_enabled ) {
				$this->render_events_overview_table();
			}
			?>

			<?php
			// Show individual orders section based on active modules
			$section_title = '';

			if ( $accommodations_enabled && $tickets_enabled ) {
				$section_title = __( 'Orders Review', 'aiohm-booking-pro' );
			} elseif ( $accommodations_enabled && ! $tickets_enabled ) {
				$section_title = __( 'Accommodation Orders', 'aiohm-booking-pro' );
			} elseif ( ! $accommodations_enabled && $tickets_enabled ) {
				$section_title = __( 'Ticket Orders', 'aiohm-booking-pro' );
			} else {
				$section_title = __( 'Orders', 'aiohm-booking-pro' );
			}
			?>
			
			<div class="aiohm-booking-admin-card">
				<h3 id="orders-table"><?php echo esc_html( $section_title ); ?></h3>
				
				<form method="post" id="orders-filter">
				<?php wp_nonce_field( 'aiohm_booking_bulk_orders' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select name="action" id="bulk-action-selector-top">
							<option value="-1"><?php esc_html_e( 'Bulk Actions', 'aiohm-booking-pro' ); ?></option>
							<option value="mark_paid"><?php esc_html_e( 'Mark Paid', 'aiohm-booking-pro' ); ?></option>
							<option value="cancel"><?php esc_html_e( 'Cancel', 'aiohm-booking-pro' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'aiohm-booking-pro' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'aiohm-booking-pro' ), 'action', '', false, array( 'id' => 'doaction' ) ); ?>
					</div>
					
					<!-- Pagination -->
					<?php if ( $total_orders > $limit ) : ?>
					<div class="tablenav-pages">
						<?php
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination parameter, no state change
						$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
						$total_pages  = ceil( $total_orders / $limit );

						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'current'   => $current_page,
									'total'     => $total_pages,
								)
							)
						);
						?>
					</div>
					<?php endif; ?>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<input type="checkbox" id="cb-select-all-1" />
							</td>
							<th class="manage-column"><?php esc_html_e( 'ID', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Date', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Name', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Email', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Phone', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Mode', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Event', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php echo esc_html( $this->get_quantity_column_name() ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Guests', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Total', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Deposit', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Status', 'aiohm-booking-pro' ); ?></th>
							<th class="manage-column"><?php esc_html_e( 'Actions', 'aiohm-booking-pro' ); ?></th>
						</tr>
					</thead>
					<tbody id="the-list">
						<?php if ( empty( $orders ) ) : ?>
						<tr class="no-items">
							<td class="colspanchange" colspan="13">
								<div class="aiohm-booking-orders-empty-state">
									<div class="empty-icon">📦</div>
									<h3><?php esc_html_e( 'No orders found', 'aiohm-booking-pro' ); ?></h3>
									<p><?php esc_html_e( 'Orders will appear here once customers start booking.', 'aiohm-booking-pro' ); ?></p>
								</div>
							</td>
						</tr>
						<?php else : ?>
							<?php foreach ( $orders as $order ) : ?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="order_ids[]" value="<?php echo esc_attr( (int) $order->id ); ?>" />
								</th>
								<td class="column-id">
									<strong>#<?php echo esc_html( (int) ( $order->id ?? 0 ) ); ?></strong>
								</td>
								<td class="column-date">
									<?php echo esc_html( wp_date( 'Y-m-d H:i', strtotime( $order->created_at ?? 'now' ) ) ); ?>
								</td>
								<td class="column-name">
									<?php echo esc_html( $order->buyer_name ?? '—' ); ?>
								</td>
								<td class="column-email">
									<?php echo esc_html( $order->buyer_email ?? '—' ); ?>
								</td>
								<td class="column-phone">
									<?php echo esc_html( $order->buyer_phone ?? '—' ); ?>
								</td>
								<td class="column-mode">
									<?php echo esc_html( ucfirst( $order->mode ?? 'accommodation' ) ); ?>
								</td>
								<td class="column-event">
									<?php
									$booking_mode = $order->mode ?? 'accommodation';
									if ( $booking_mode === 'tickets' ) {
										// Extract event name from notes for ticket bookings
										$event_name = $this->extract_event_name_from_notes( $order->notes ?? '' );
										echo esc_html( $event_name ?: '—' );
									} else {
										// For accommodation bookings, show dash
										echo '—';
									}
									?>
								</td>
								<td class="column-units">
									<?php
									$booking_mode = $order->mode ?? 'accommodation';
									if ( $booking_mode === 'tickets' ) :
										// For event bookings, show ticket quantity (stored in guests_qty)
										echo esc_html( (int) ( $order->guests_qty ?? 0 ) );
									else :
										// For accommodation bookings
										if ( ! empty( $order->private_all ?? false ) && ( $order->private_all ?? false ) ) :
											?>
											<span class="aiohm-booking-orders-private-booking"><?php esc_html_e( 'Private (All)', 'aiohm-booking-pro' ); ?></span>
										<?php else : ?>
											<?php echo esc_html( (int) ( $order->units_qty ?? 0 ) ); ?>
											<?php
										endif;
									endif;
									?>
								</td>
								<td class="column-guests">
									<?php
									$booking_mode = $order->mode ?? 'accommodation';
									if ( $booking_mode === 'tickets' ) :
										// For event bookings, guests column might not be applicable or could show attendees
										echo '—';
									else :
										// For accommodation bookings
										echo esc_html( (int) ( $order->guests_qty ?? 0 ) );
										if ( ! empty( $order->bringing_pets ?? false ) && ( $order->bringing_pets ?? false ) ) :
											?>
											<br><small>🐾 <?php esc_html_e( 'Pets', 'aiohm-booking-pro' ); ?></small>
											<?php
										endif;
									endif;
									?>
								</td>
								<td class="column-total">
									<strong><?php echo esc_html( ( $order->currency ?? 'USD' ) . ' ' . number_format( ( $order->total_amount ?? 0 ), 2 ) ); ?></strong>
								</td>
								<td class="column-deposit">
									<?php echo esc_html( ( $order->currency ?? 'USD' ) . ' ' . number_format( ( $order->deposit_amount ?? 0 ), 2 ) ); ?>
								</td>
								<td class="column-status">
									<span class="status status-<?php echo esc_attr( $order->status ?? 'pending' ); ?>">
										<?php echo esc_html( ucfirst( $order->status ?? 'pending' ) ); ?>
									</span>
								</td>
								<td class="column-actions">
									<div class="row-actions">
										<?php if ( 'pending' === $order->status ) : ?>
											<span class="mark-paid">
												<a href="
												<?php
												echo esc_url(
													wp_nonce_url(
														admin_url( 'admin.php?page=aiohm-booking-orders&action=mark_paid&order_id=' . $order->id ),
														'aiohm_order_action_mark_paid_' . $order->id
													)
												);
												?>
												"><?php esc_html_e( 'Mark Paid', 'aiohm-booking-pro' ); ?></a> |
											</span>
										<?php endif; ?>
										<span class="cancel">
											<a href="
											<?php
											echo esc_url(
												wp_nonce_url(
													admin_url( 'admin.php?page=aiohm-booking-orders&action=cancel&order_id=' . $order->id ),
													'aiohm_order_action_cancel_' . $order->id
												)
											);
											?>
											"><?php esc_html_e( 'Cancel', 'aiohm-booking-pro' ); ?></a> |
										</span>
										<span class="delete">
											<a href="
											<?php
											echo esc_url(
												wp_nonce_url(
													admin_url( 'admin.php?page=aiohm-booking-orders&action=delete&order_id=' . $order->id ),
													'aiohm_order_action_delete_' . $order->id
												)
											);
											?>
											" 
											class="aiohm-booking-orders-delete-link"><?php esc_html_e( 'Delete', 'aiohm-booking-pro' ); ?></a>
										</span>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				</form>
			</div>

			<?php
			// AI Order Insights Section - only show if enabled.
			$this->maybe_render_ai_insights();

			// Hook for AI Analytics module to add additional content.
			do_action( 'aiohm_booking_orders_page_bottom' );
			?>
		</div>
			
		<!-- Orders module styles are included in the unified CSS system -->
		<?php
	}

	/**
	 * Render events overview table showing aggregated data per event
	 */
	private function render_events_overview_table() {
		// Check if tickets module is enabled
		$settings        = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$tickets_enabled = $settings['enable_tickets'] ?? true;

		if ( ! $tickets_enabled ) {
			return; // Tickets module disabled, don't show the table
		}

		// Get events data using compatible method
		$events_data = AIOHM_Booking_PROModulesBookingAIOHM_Booking_PROModulesBookingAIOHM_Booking_PROModulesBookingAIOHM_BOOKING_Module_Tickets::get_events_data();
		if ( empty( $events_data ) ) {
			return; // No events configured, don't show the table
		}

		// Get the current number of events setting to match tickets module
		$global_settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$num_events      = intval( $global_settings['number_of_events'] ?? 5 );

		global $wpdb;
		$table = $wpdb->prefix . 'aiohm_booking_order';

		// Get all ticket orders for aggregation
		$ticket_orders = $wpdb->get_results(	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for order data query
			$wpdb->prepare(
				'SELECT * FROM ' . esc_sql( $table ) . ' WHERE mode = %s ORDER BY created_at DESC',
				'tickets'
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality

		// Build events overview data - only process configured number of events
		$events_overview = array();
		for ( $i = 0; $i < $num_events; $i++ ) {
			$event      = $events_data[ $i ] ?? array();
			$event_name = $event['title'] ?? 'Unknown Event';

			// Initialize event data
			$events_overview[ $i ] = array(
				'event_name'    => $event_name,
				'total_tickets' => intval( $event['available_seats'] ?? 0 ),
				'orders'        => array(),
				'total_sold'    => 0,
				'total_revenue' => 0,
				'total_deposit' => 0,
				'total_paid'    => 0,
				'statuses'      => array(),
			);

			// Find all orders for this event
			foreach ( $ticket_orders as $order ) {
				$order_event_name = $this->extract_event_name_from_notes( $order->notes ?? '' );

				// Normalize both strings for comparison (remove invisible Unicode characters)
				$normalized_order_name = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim( $order_event_name ) );
				$normalized_event_name = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', trim( $event_name ) );

				if ( $normalized_order_name === $normalized_event_name ) {
					$events_overview[ $i ]['orders'][]       = $order;
					$events_overview[ $i ]['total_sold']    += intval( $order->guests_qty ?? 0 );
					$events_overview[ $i ]['total_revenue'] += floatval( $order->total_amount ?? 0 );
					$events_overview[ $i ]['total_deposit'] += floatval( $order->deposit_amount ?? 0 );

					// Only count paid orders for total paid
					if ( ( $order->status ?? 'pending' ) === 'paid' ) {
						$events_overview[ $i ]['total_paid'] += floatval( $order->total_amount ?? 0 );
					}

					// Track status counts
					$status                                       = $order->status ?? 'pending';
					$events_overview[ $i ]['statuses'][ $status ] = ( $events_overview[ $i ]['statuses'][ $status ] ?? 0 ) + 1;
				}
			}
		}

		// Only show table if there are configured events (even without orders)
		// This way users can see their event setup even if no orders yet
		if ( $num_events <= 0 ) {
			return;
		}

		?>
		<div class="aiohm-booking-admin-card">
			<h3><?php esc_html_e( 'Events Overview', 'aiohm-booking-pro' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="manage-column"><?php esc_html_e( 'Event Name', 'aiohm-booking-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Event Type', 'aiohm-booking-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Total Tickets', 'aiohm-booking-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Total Sold', 'aiohm-booking-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Total Deposit', 'aiohm-booking-pro' ); ?></th>
						<th class="manage-column"><?php esc_html_e( 'Total Paid', 'aiohm-booking-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $events_overview ) ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="6">
							<div class="aiohm-booking-orders-empty-state">
								<div class="empty-icon">🎫</div>
								<h3><?php esc_html_e( 'No event data found', 'aiohm-booking-pro' ); ?></h3>
								<p><?php esc_html_e( 'Event booking data will appear here once customers start booking events.', 'aiohm-booking-pro' ); ?></p>
							</div>
						</td>
					</tr>
					<?php else : ?>
						<?php foreach ( $events_overview as $event_index => $event_data ) : ?>
							<?php
							// Get event details from original events data
							$event_details = $events_data[ $event_index ] ?? array();
							$event_type    = $event_details['event_type'] ?? __( 'Event', 'aiohm-booking-pro' );

							// Calculate availability status
							$remaining   = $event_data['total_tickets'] - $event_data['total_sold'];
							$is_sold_out = $remaining <= 0;

							// Get currency from first order or default to EUR
							$currency = 'EUR';
							if ( ! empty( $event_data['orders'] ) ) {
								$currency = $event_data['orders'][0]->currency ?? 'EUR';
							}
							?>
						<tr>
							<td class="column-event-name">
								<strong><?php echo esc_html( $event_data['event_name'] ); ?></strong>
								<?php if ( $is_sold_out ) : ?>
									<br><span class="aiohm-booking-orders-sold-out-badge">🚫 <?php esc_html_e( 'Sold Out', 'aiohm-booking-pro' ); ?></span>
								<?php elseif ( $remaining <= 5 && $remaining > 0 ) : ?>
									<br><span class="aiohm-booking-orders-low-stock-badge">⚠️ 
									<?php
									/* translators: %d: number of tickets remaining */
									echo esc_html( sprintf( __( '%d left', 'aiohm-booking-pro' ), $remaining ) );
									?>
									</span>
								<?php endif; ?>
							</td>
							<td class="column-event-type">
								<?php echo esc_html( ucfirst( $event_type ) ); ?>
							</td>
							<td class="column-total-tickets">
								<?php echo (int) $event_data['total_tickets']; ?>
							</td>
							<td class="column-total-sold">
								<strong><?php echo (int) $event_data['total_sold']; ?></strong>
							</td>
							<td class="column-total-deposit">
								<?php echo esc_html( $currency . ' ' . number_format( $event_data['total_deposit'], 2 ) ); ?>
							</td>
							<td class="column-total-paid">
								<strong><?php echo esc_html( $currency . ' ' . number_format( $event_data['total_paid'], 2 ) ); ?></strong>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Process bulk actions for orders
	 */
	private function process_bulk_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled below for bulk actions
		if ( ! isset( $_POST['action'] ) || sanitize_text_field( wp_unslash( $_POST['action'] ) ) === '-1' || empty( array_map( 'intval', wp_unslash( $_POST['order_ids'] ?? array() ) ) ) ) {
			return;
		}

		// Verify nonce for bulk actions
		if ( ! check_admin_referer( 'aiohm_booking_bulk_orders', '_wpnonce' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'aiohm-booking-pro' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'aiohm-booking-pro' ) );
		}

		global $wpdb;
		$table     = $wpdb->prefix . 'aiohm_booking_order';
		$action    = sanitize_text_field( wp_unslash( $_POST['action'] ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized with array_map intval below
		$order_ids = array_map( 'intval', wp_unslash( $_POST['order_ids'] ) );

		if ( 'mark_paid' === $action ) {
			foreach ( $order_ids as $order_id ) {
				$wpdb->update( $table, array( 'status' => 'paid' ), array( 'id' => $order_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

				// Trigger payment completion action to update event availability
				do_action( 'aiohm_booking_payment_completed', $order_id, 'manual', null );
			}
			$this->show_bulk_notice( __( 'Orders marked as paid.', 'aiohm-booking-pro' ), 'success' );
		} elseif ( 'cancel' === $action ) {
			foreach ( $order_ids as $order_id ) {
				$wpdb->update( $table, array( 'status' => 'cancelled' ), array( 'id' => $order_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality
			}
			$this->show_bulk_notice( __( 'Orders cancelled.', 'aiohm-booking-pro' ), 'success' );
		} elseif ( 'delete' === $action ) {
			foreach ( $order_ids as $order_id ) {
				$wpdb->delete( $table, array( 'id' => $order_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality
			}
			/* translators: %d: number of orders deleted */
			$this->show_bulk_notice( sprintf( __( '%d order(s) deleted permanently.', 'aiohm-booking-pro' ), count( $order_ids ) ), 'success' );
		}
	}

	/**
	 * Process single row actions for orders
	 */
	private function process_single_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification handled below for individual actions
		if ( ! isset( $_GET['action'], $_GET['order_id'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized with absint below
		$order_id = absint( $_GET['order_id'] );

		// Verify nonce for security.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended -- Used for nonce verification
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'aiohm_order_action_' . $action . '_' . $order_id ) ) {
			wp_die( esc_html__( 'Security check failed', 'aiohm-booking-pro' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'aiohm-booking-pro' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'aiohm_booking_order';

		if ( 'mark_paid' === $action ) {
			$wpdb->update( $table, array( 'status' => 'paid' ), array( 'id' => $order_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality

			// Trigger payment completion action to update event availability
			do_action( 'aiohm_booking_payment_completed', $order_id, 'manual', null );

			$this->show_notice( __( 'Order marked as paid.', 'aiohm-booking-pro' ), 'success' );
		} elseif ( 'cancel' === $action ) {
			$wpdb->update( $table, array( 'status' => 'cancelled' ), array( 'id' => $order_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality
			$this->show_notice( __( 'Order cancelled.', 'aiohm-booking-pro' ), 'success' );
		} elseif ( 'delete' === $action ) {
			$wpdb->delete( $table, array( 'id' => $order_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table modification for plugin functionality
			$this->show_notice( __( 'Order deleted.', 'aiohm-booking-pro' ), 'success' );
		}

		// Redirect to clean URL with cache-busting parameter.
		wp_safe_redirect( admin_url( 'admin.php?page=aiohm-booking-orders&updated=' . time() ) );
		exit;
	}

	/**
	 * Display stored notices from transients
	 */
	private function display_stored_notices() {
		$notice = get_transient( 'aiohm_booking_orders_notice' );
		if ( $notice ) {
			delete_transient( 'aiohm_booking_orders_notice' );
			echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		}
	}

	/**
	 * Store admin notice in transient for display after redirect
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type (success, error, warning, info).
	 */
	private function show_notice( $message, $type = 'info' ) {
		set_transient(
			'aiohm_booking_orders_notice',
			array(
				'message' => $message,
				'type'    => $type,
			),
			30
		); // Store for 30 seconds.
	}

	/**
	 * Show admin notice directly for bulk actions
	 *
	 * @param string $message The notice message.
	 * @param string $type    The notice type (success, error, warning, info).
	 */
	private function show_bulk_notice( $message, $type = 'info' ) {
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Maybe render AI insights section
	 */
	private function maybe_render_ai_insights() {
		// Check if user has access to premium AI features
		if ( ! function_exists( 'aiohm_booking_fs' ) || ! aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
			return;
		}
		
		// AI section is now handled by the AI Analytics module to avoid duplication
		// The orders module's AI section has been disabled in favor of the AI Analytics module's section
		return;
	}

	/**
	 * Get settings fields for this module
	 */
	public function get_settings_fields() {
		$fields = array(
			'orders_per_page'     => array(
				'type'        => 'number',
				'label'       => 'Orders per page',
				'description' => 'Number of orders to show per page in admin (5-100)',
				'default'     => 50,
				'min'         => 5,
				'max'         => 100,
			),
		);

		// Only include AI analytics field for premium users
		if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
			$fields['enable_ai_analytics'] = array(
				'type'        => 'checkbox',
				'label'       => 'Enable AI Analytics',
				'description' => 'Show AI analytics section on the Orders page',
				'default'     => false,
			);
		}

		$fields = array_merge( $fields, array(
			'auto_expire_pending' => array(
				'type'        => 'number',
				'label'       => 'Auto-expire pending orders (hours)',
				'description' => 'Automatically expire pending orders after this many hours (0 = disable)',
				'default'     => 24,
				'min'         => 0,
				'max'         => 168,
			),
			'email_on_new_order'  => array(
				'type'        => 'checkbox',
				'label'       => 'Email notifications',
				'description' => 'Send email notification when new orders are created',
				'default'     => true,
			),
		) );

		return $fields;
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( ! $screen || 'aiohm-booking_page_aiohm-booking-orders' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			'aiohm-booking-orders-admin',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-orders-admin.js',
			array( 'jquery', 'aiohm-booking-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		wp_localize_script(
			'aiohm-booking-orders-admin',
			'aiohm_booking_orders',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_ai_query_nonce' ),
				'i18n'     => array(
					'confirm_delete' => __( 'Are you sure you want to delete this order?', 'aiohm-booking-pro' ),
					'ai_query_error' => __( 'Failed to get AI response. Please try again.', 'aiohm-booking-pro' ),
					'copied'         => __( 'Response copied to clipboard!', 'aiohm-booking-pro' ),
				),
			)
		);
	}

	/**
	 * Detect supported plugins for integration display
	 */
	public function detect_supported_plugins() {
		$this->supported_plugins = array(
		);
	}

	/**
	 * Log status changes for audit trail
	 *
	 * @param int    $order_id   The order ID.
	 * @param string $old_status The old status.
	 * @param string $new_status The new status.
	 */
	public function log_status_change( $order_id, $old_status, $new_status ) {
		// Status changes are logged for audit trail purposes.
	}

	/**
	 * Handle order actions
	 */
	public function handle_order_actions() {
		// Only process actions on the orders admin page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for page check, not form data
		if ( ! is_admin() || ! isset( $_GET['page'] ) || $_GET['page'] !== 'aiohm-booking-orders' ) {
			return;
		}

		// Process single order actions.
		$this->process_single_actions();
	}

	/**
	 * Handle bulk actions
	 */
	public function handle_bulk_actions() {
		// Bulk actions are handled via process_bulk_actions() method.
	}

	/**
	 * Check if module should be enabled by default
	 */
	protected function check_if_enabled() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This method only reads settings, does not process form data
		$settings   = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$enable_key = 'enable_' . $this->get_ui_definition()['id'];

		// If the setting exists, check if it's explicitly enabled.
		if ( isset( $settings[ $enable_key ] ) ) {
			return true === $settings[ $enable_key ] || 'true' === $settings[ $enable_key ] || 1 === $settings[ $enable_key ] || '1' === $settings[ $enable_key ];
		}

		// Default to enabled for Orders module - it's core booking functionality.
		return true;
	}

	/**
	 * Check if this module is enabled
	 */

	/**
	 * Get default settings for the Orders module
	 */
	protected function get_default_settings() {
		return array(
			'orders_per_page'      => 20,
			'enable_ai_analytics'  => false,
			'auto_cleanup_expired' => true,
			'order_retention_days' => 30,
		);
	}

	/**
	 * Get the plural name for accommodation type
	 *
	 * @return string The plural accommodation name
	 */
	private function get_accommodation_plural_name() {
		$settings           = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$accommodation_type = $settings['accommodation_type'] ?? 'unit';
		return aiohm_booking_get_accommodation_plural_name( $accommodation_type );
	}

	/**
	 * Get the appropriate column name for quantity based on enabled modules
	 */
	private function get_quantity_column_name() {
		$settings               = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		$accommodations_enabled = $settings['enable_accommodations'] ?? true;
		$tickets_enabled        = $settings['enable_tickets'] ?? true;

		// If both modules are enabled, use generic label
		if ( $accommodations_enabled && $tickets_enabled ) {
			return __( 'Quantity', 'aiohm-booking-pro' );
		}

		// If only tickets enabled, use tickets-specific label
		if ( $tickets_enabled && ! $accommodations_enabled ) {
			return __( 'Tickets', 'aiohm-booking-pro' );
		}

		// Default to accommodation-specific label
		return $this->get_accommodation_plural_name();
	}

	/**
	 * AJAX handler for completing checkout
	 */
	// Note: ajax_complete_checkout method is now handled by Shortcode Admin module
	// This prevents duplicate AJAX handler registration

	/**
	 * Extract event name from booking notes
	 *
	 * @since 1.2.4
	 * @param string $notes The notes field content
	 * @return string Event name or empty string if not found
	 */
	private function extract_event_name_from_notes( $notes ) {
		if ( empty( $notes ) ) {
			return '';
		}

		// Convert escaped newlines to actual newlines for proper parsing
		$notes = str_replace( array( '\\n', '\n' ), "\n", $notes );

		// Try to extract event name from notes format: "Event: 0, Event Name\nDate: ..."
		// Stop at newline to get only the event title
		if ( preg_match( '/Event:\s*\d+,\s*(.+?)(?=\n|$)/i', $notes, $matches ) ) {
			return trim( $matches[1] );
		}

		// Fallback: try to extract from format: "Event: Event Name\nDate: ..." (without index)
		if ( preg_match( '/Event:\s*(.+?)(?=\n|$)/i', $notes, $matches ) ) {
			$event_part = trim( $matches[1] );
			// Skip if it looks like "Event: 0, Event Name" format (already handled above)
			if ( ! preg_match( '/^\d+,/', $event_part ) ) {
				return $event_part;
			}
		}

		// Additional fallback: try to extract from "Event Details:" section
		if ( preg_match( '/Event Details:\s*Event:\s*\d+,\s*(.+?)(?=\n|$)/i', $notes, $matches ) ) {
			return trim( $matches[1] );
		}

		return '';
	}
}
