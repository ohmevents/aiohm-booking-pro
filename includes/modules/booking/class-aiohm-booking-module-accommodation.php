<?php
/**
 * Accommodation Module for AIOHM Booking
 * Handles accommodation booking functionality with enhanced design
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load DTO classes.
require_once __DIR__ . '/class-accommodation-settings-dto.php';
require_once __DIR__ . '/class-accommodation-dto.php';

/**
 * AIOHM Booking Accommodation Module
 *
 * Handles accommodation booking functionality with enhanced design.
 *
 * @since 2.0.0
 */
class AIOHM_BOOKING_Module_Accommodation extends AIOHM_BOOKING_Settings_Module_Abstract {

	// Configuration constants.
	const MODULE_PRIORITY         = 10;
	const ADMIN_MENU_PRIORITY     = 15;
	const MIN_ACCOMMODATIONS      = 1;
	const MAX_ACCOMMODATIONS      = 50;
	const SYNC_MAX_ACCOMMODATIONS = 50;
	const AJAX_TIMEOUT            = 30000; // milliseconds.
	const BUTTON_RESET_DELAY      = 2000; // milliseconds.
	const NOTICE_AUTO_HIDE_DELAY  = 5000; // milliseconds.

	/**
	 * Get UI definition for the accommodation module.
	 *
	 * @return array
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'accommodations',
			'name'                => __( 'Accommodation', 'aiohm-booking-pro' ),
			'description'         => __( 'Perfect for accommodations, vacation rentals, hotels, venues, houses, bungalows, and private spaces.', 'aiohm-booking-pro' ),
			'icon'                => '🏡',
			'category'            => 'booking',
			'access_level'        => 'free',
			'is_premium'          => false,
			'priority'            => self::MODULE_PRIORITY,
			'has_settings'        => true,
			'has_admin_page'      => true,
			// Explicit admin page slug to avoid singular/plural mismatches in settings links.
			'admin_page_slug'     => 'aiohm-booking-accommodations',
			'visible_in_settings' => true,
		);
	}

	/**
	 * Constructor for the Accommodation module.
	 */
	public function __construct() {
		parent::__construct();

		// This is a PAGE module - enable admin page.
		$this->has_admin_page = true;

		// Initialize the module.
		$this->init();
	}

	/**
	 * Initialize the Accommodation module.
	 */
	public function init() {
		// Settings configuration.
		$this->settings_section_id = 'accommodation';
		$this->settings_page_title = __( 'Accommodation', 'aiohm-booking-pro' );
		$this->settings_tab_title  = __( 'Accommodation Settings', 'aiohm-booking-pro' );
		$this->has_quick_settings  = true;
		$this->admin_page_slug     = 'aiohm-booking-accommodations';

		// Initialize caching hooks.
		$this->init_cache_hooks();
	}

	/**
	 * Initialize hooks for the Accommodation module.
	 */
	protected function init_hooks() {

		// Register the Custom Post Type for accommodations.
		add_action( 'init', array( $this, 'register_accommodation_cpt' ) );

		// Add meta boxes to the CPT edit screen.
		add_action( 'add_meta_boxes', array( $this, 'add_accommodation_meta_boxes' ) );
		add_action( 'save_post_aiohm_accommodation', array( $this, 'save_accommodation_meta_data' ) );

		// Clear accommodation service cache when cell statuses are updated.
		add_action( 'update_option_aiohm_booking_cell_statuses', array( $this, 'clear_accommodation_service_cache' ) );
		add_action( 'add_option_aiohm_booking_cell_statuses', array( $this, 'clear_accommodation_service_cache' ) );
		add_action( 'delete_option_aiohm_booking_cell_statuses', array( $this, 'clear_accommodation_service_cache' ) );

		// Update calendar when accommodation payment is completed
		add_action( 'aiohm_booking_payment_completed', array( $this, 'update_accommodation_availability' ), 10, 3 );

		// Register admin page with higher priority to control menu order.
		add_action( 'admin_menu', array( $this, 'register_admin_page' ), self::ADMIN_MENU_PRIORITY );

		// Handle accommodation details form submission.
		add_action( 'admin_init', array( $this, 'save_accommodation_details' ) );

		// Handle form settings submission.
		add_action( 'admin_init', array( $this, 'save_form_settings' ) );

		// Handle add new accommodation action.
		add_action( 'admin_init', array( $this, 'handle_add_new_accommodation' ) );

		// AJAX handlers - only register if module is enabled.
		if ( $this->is_enabled() ) {
			// Only register the unified form settings handler once to avoid conflicts
			if ( ! has_action( 'wp_ajax_aiohm_save_form_settings', array( 'AIOHM_BOOKING_Form_Settings_Handler', 'save_unified_form_settings' ) ) ) {
				add_action( 'wp_ajax_aiohm_save_form_settings', array( 'AIOHM_BOOKING_Form_Settings_Handler', 'save_unified_form_settings' ) );
			}
			add_action( 'wp_ajax_aiohm_booking_save_individual_accommodation', array( $this, 'ajax_save_individual_accommodation' ) );
			add_action( 'wp_ajax_aiohm_booking_update_preview', array( $this, 'update_preview_ajax' ) );
			add_action( 'wp_ajax_aiohm_booking_sync_accommodations', array( $this, 'ajax_sync_accommodations' ) );
		}
	}

	/**
	 * Register the 'accommodation' Custom Post Type.
	 */
	public function register_accommodation_cpt() {
		$labels = array(
			'name'              => _x( 'Accommodations', 'Post Type General Name', 'aiohm-booking-pro' ),
			'singular_name'     => _x( 'Accommodation', 'Post Type Singular Name', 'aiohm-booking-pro' ),
			'menu_name'         => __( 'Accommodations', 'aiohm-booking-pro' ),
			'name_admin_bar'    => __( 'Accommodation', 'aiohm-booking-pro' ),
			'archives'          => __( 'Accommodation Archives', 'aiohm-booking-pro' ),
			'attributes'        => __( 'Accommodation Attributes', 'aiohm-booking-pro' ),
			'parent_item_colon' => __( 'Parent Accommodation:', 'aiohm-booking-pro' ),
			'all_items'         => __( 'All Accommodations', 'aiohm-booking-pro' ),
			'add_new_item'      => __( 'Add New Accommodation', 'aiohm-booking-pro' ),
			'add_new'           => __( 'Add New', 'aiohm-booking-pro' ),
			'new_item'          => __( 'New Accommodation', 'aiohm-booking-pro' ),
			'edit_item'         => __( 'Edit Accommodation', 'aiohm-booking-pro' ),
			'update_item'       => __( 'Update Accommodation', 'aiohm-booking-pro' ),
			'view_item'         => __( 'View Accommodation', 'aiohm-booking-pro' ),
			'view_items'        => __( 'View Accommodations', 'aiohm-booking-pro' ),
			'search_items'      => __( 'Search Accommodation', 'aiohm-booking-pro' ),
		);
		$args   = array(
			'label'           => __( 'Accommodation', 'aiohm-booking-pro' ),
			'description'     => __( 'Accommodation units for booking.', 'aiohm-booking-pro' ),
			'labels'          => $labels,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'hierarchical'    => false,
			'public'          => true,
			'show_ui'         => true,
			'show_in_menu'    => true, // Show in admin menu
			'show_in_admin_bar' => true,
			'menu_icon'       => 'dashicons-building',
			'capability_type' => 'post',
			'has_archive'     => false,
			'can_export'      => true,
		);
		register_post_type( 'aiohm_accommodation', $args );
	}

	/**
	 * Add meta boxes for the accommodation CPT.
	 */
	public function add_accommodation_meta_boxes() {
		add_meta_box(
			'aiohm_accommodation_details',
			__( 'Accommodation Details', 'aiohm-booking-pro' ),
			array( $this, 'render_accommodation_meta_box' ),
			'aiohm_accommodation',
			'normal',
			'high'
		);
	}

	/**
	 * Render the content of the accommodation details meta box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_accommodation_meta_box( $post ) {
		wp_nonce_field( 'aiohm_save_accommodation_meta', 'aiohm_accommodation_meta_nonce' );

		$price           = get_post_meta( $post->ID, '_aiohm_booking_accommodation_price', true );
		$earlybird_price = get_post_meta( $post->ID, '_aiohm_booking_accommodation_earlybird_price', true );
		$type            = get_post_meta( $post->ID, '_aiohm_booking_accommodation_type', true );
		$max_guests      = get_post_meta( $post->ID, '_aiohm_booking_accommodation_max_guests', true );
		?>
		<p>
			<label for="aiohm_price"><?php esc_html_e( 'Standard Price:', 'aiohm-booking-pro' ); ?></label>
			<input type="number" id="aiohm_price" name="aiohm_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" min="0" />
		</p>
		<p>
			<label for="aiohm_earlybird_price"><?php esc_html_e( 'Early Bird Price:', 'aiohm-booking-pro' ); ?></label>
			<input type="number" id="aiohm_earlybird_price" name="aiohm_earlybird_price" value="<?php echo esc_attr( $earlybird_price ); ?>" step="0.01" min="0" />
		</p>
		<p>
			<label for="aiohm_type"><?php esc_html_e( 'Type:', 'aiohm-booking-pro' ); ?></label>
			<input type="text" id="aiohm_type" name="aiohm_type" value="<?php echo esc_attr( $type ); ?>" />
		</p>
		<p>
			<label for="aiohm_max_guests"><?php esc_html_e( 'Maximum Guests:', 'aiohm-booking-pro' ); ?></label>
			<input type="number" id="aiohm_max_guests" name="aiohm_max_guests" value="<?php echo esc_attr( $max_guests ); ?>" min="1" max="50" step="1" />
		</p>
		<?php
	}

	/**
	 * Save meta data for the accommodation CPT.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_accommodation_meta_data( $post_id ) {
		$meta_nonce = sanitize_text_field( wp_unslash( $_POST['aiohm_accommodation_meta_nonce'] ?? '' ) );
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $meta_nonce, 'aiohm_booking_save_accommodation_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( isset( $_POST['post_type'] ) && 'aiohm_accommodation' == sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) && ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_aiohm_booking_accommodation_price', sanitize_text_field( wp_unslash( $_POST['aiohm_price'] ?? 0 ) ) );
		update_post_meta( $post_id, '_aiohm_booking_accommodation_earlybird_price', sanitize_text_field( wp_unslash( $_POST['aiohm_earlybird_price'] ?? 0 ) ) );
		update_post_meta( $post_id, '_aiohm_booking_accommodation_type', sanitize_text_field( wp_unslash( $_POST['aiohm_type'] ?? 'unit' ) ) );
		update_post_meta( $post_id, '_aiohm_booking_accommodation_max_guests', intval( wp_unslash( $_POST['aiohm_max_guests'] ?? 2 ) ) );
	}

	/**
	 * Register admin page in WordPress menu
	 * Note: Menu is now handled centrally in AIOHM_BOOKING_Admin class to control order
	 * This method is kept for compatibility but doesn't add menu items
	 */
	public function register_admin_page() {
		// Implementation handled by parent class.
	}

	/**
	 * Enqueue admin assets for accommodation module pages
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_enqueue_assets( $hook ) {
		// Only load on our module page.
		if ( strpos( $hook, 'aiohm-booking-accommodations' ) === false ) {
			return;
		}

		// Don't enqueue the accommodation admin script here - it's handled by the assets class.
		// with proper dependencies including jquery-ui-sortable.

		// Enqueue shortcode JS for calendar functionality in preview.
		wp_enqueue_script(
			'aiohm-booking-shortcode',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-shortcode.js',
			// Add aiohm-booking-admin as a dependency.
			array( 'jquery', 'aiohm-booking-admin' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Note: CSS loading handled by main Assets class

		// Enqueue advanced calendar JavaScript for preview.
		wp_enqueue_script(
			'aiohm-booking-advanced-calendar',
			AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-advanced-calendar.js',
			array( 'jquery', 'aiohm-booking-shortcode' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Ensure settings admin script is loaded for toggle functionality
		if ( ! wp_script_is( 'aiohm-booking-settings-admin', 'enqueued' ) ) {
			wp_enqueue_script(
				'aiohm-booking-settings-admin',
				AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-settings-admin.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				AIOHM_BOOKING_VERSION,
				true
			);
		}

		// Localize scripts with all necessary data for this page.
		// This object is used by accommodation-admin.js and settings-admin.js.
		$localized_data = array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => AIOHM_BOOKING_Security_Helper::create_nonce( 'admin_nonce' ),
			'preview_nonce' => AIOHM_BOOKING_Security_Helper::create_nonce( 'update_preview' ),
			'i18n'          => array(
				'saving'                    => __( 'Saving...', 'aiohm-booking-pro' ),
				'saved'                     => __( 'Saved!', 'aiohm-booking-pro' ),
				'applyingChanges'           => __( 'Applying changes...', 'aiohm-booking-pro' ),
				'savingSettings'            => __( 'Saving settings...', 'aiohm-booking-pro' ),
				'testing'                   => __( 'Testing...', 'aiohm-booking-pro' ),
				'testingConnection'         => __( 'Testing connection...', 'aiohm-booking-pro' ),
				'connectionTestFailed'      => __( 'Connection test failed', 'aiohm-booking-pro' ),
				'saveFailed'                => __( 'Save failed', 'aiohm-booking-pro' ),
				'invalidAccommodationIndex' => __( 'Invalid accommodation index', 'aiohm-booking-pro' ), // Kept for compatibility.
				'invalidAccommodationId'    => __( 'Invalid Accommodation ID', 'aiohm-booking-pro' ),
				'errorPrefix'               => __( 'Error: ', 'aiohm-booking-pro' ),
			),
		);
		wp_localize_script( 'aiohm-booking-accommodation-admin', 'aiohm_booking_admin', $localized_data );
	}

	/**
	 * Get settings fields configuration for accommodation module
	 *
	 * @return array Settings fields configuration
	 */
	public function get_settings_fields() {
		return array(
			'accommodation_type'              => array(
				'type'        => 'select',
				'label'       => 'Accommodation Type',
				'description' => 'Primary type of accommodation offered',
				'options'     => self::get_accommodation_types_for_select(),
				'default'     => 'unit',
			),
			'available_accommodations'        => array(
				'type'        => 'number',
				'label'       => 'Number of Accommodations',
				'description' => 'Total number of accommodations available',
				'default'     => 7,
				'min'         => self::MIN_ACCOMMODATIONS,
				'max'         => self::MAX_ACCOMMODATIONS,
			),
			'allow_private_all'               => array(
				'type'        => 'checkbox',
				'label'       => 'Allow Entire Property Booking',
				'description' => 'Allow guests to book the entire property',
				'default'     => false,
			),
			'default_price'                   => array(
				'type'        => 'number',
				'label'       => 'Default Price',
				'description' => 'Default price for accommodations when no specific price is set',
				'default'     => 0,
				'min'         => 0,
				'step'        => 0.01,
			),
			'default_earlybird_price'         => array(
				'type'        => 'number',
				'label'       => 'Default Early Bird Price',
				'description' => 'Default early bird price for accommodations when no specific price is set',
				'default'     => 0,
				'min'         => 0,
				'step'        => 0.01,
			),
			'enable_early_bird_accommodation' => array(
				'type'        => 'checkbox',
				'label'       => 'Enable Early Bird Feature',
				'description' => 'Activate early bird pricing for accommodations',
				'default'     => false,
			),
			'early_bird_days_accommodation'   => array(
				'type'        => 'number',
				'label'       => 'Early Bird Window',
				'description' => 'Days before check-in for early bird pricing',
				'default'     => 30,
				'min'         => 1,
				'max'         => 365,
			),
		);
	}

	/**
	 * Get default settings for accommodation module
	 *
	 * @return array Default settings
	 */
	protected function get_default_settings() {
		return array(
			'accommodation_type'              => 'unit',
			'available_accommodations'        => 1,
			'allow_private_all'               => false,
			'default_price'                   => 0,
			'default_earlybird_price'         => 0,
			'enable_early_bird_accommodation' => false,
			'early_bird_days_accommodation'   => 30,
		);
	}

	/**
	 * Get current module settings merged with defaults
	 *
	 * @return array Module settings
	 */
	public function get_module_settings() {
		// This function should ideally fetch settings stored under a single key for this module.
		// The current implementation merges multiple options, which can be confusing.
		// Let's simplify to pull from the main settings array.
		$defaults        = $this->get_default_settings();
		$global_settings = get_option( 'aiohm_booking_settings', array() );

		// Extract accommodation-specific settings if they exist under their own key.
		$accommodation_settings = $global_settings['accommodation'] ?? array();
		return array_merge( $defaults, $accommodation_settings, $global_settings );
	}
	/**
	 * Render the admin page for accommodation management
	 */
	public function render_admin_page() {
		$settings_saved = false;

		// Handle legacy booking form customization form submission.
		$nonce = sanitize_text_field( wp_unslash( $_POST['aiohm_booking_settings_nonce'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce retrieved for verification below
		if ( isset( $_POST['form_submit'] ) && isset( $_POST['aiohm_booking_settings'] ) && AIOHM_BOOKING_Security_Helper::verify_nonce( $nonce, 'save_settings' ) && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by AIOHM_BOOKING_Security_Helper
			$posted_settings = wp_unslash( $_POST['aiohm_booking_settings'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified by AIOHM_BOOKING_Security_Helper, input sanitized in save_settings method

			// Save the form customization settings using the already unslashed data
			$save_result = AIOHM_BOOKING_Settings::save_settings( $posted_settings );
			// Clear all related caches after saving settings
			$this->clear_settings_cache();

			// Force fresh settings fetch by nullifying cached values
			self::$cached_settings = null;
			AIOHM_BOOKING_Settings::clear_cache();

			if ( $save_result ) {
				$settings_saved = true;
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to save settings. Please try again.', 'aiohm-booking-pro' ) . '</p></div>';
			}
		} elseif ( isset( $_POST['form_submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checking for failed nonce verification above
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'aiohm-booking-pro' ) . '</p></div>';
		}

		// Force fresh settings retrieval to ensure we see the latest data
		self::$cached_settings = null;
		AIOHM_BOOKING_Settings::clear_cache();

		// Get settings AFTER potential save and cache clear to ensure fresh data
		$settings        = $this->get_module_settings();
		$global_settings = AIOHM_BOOKING_Settings::get_all(); // Use direct call instead of cached version

		$available_accommodations = intval( $global_settings['available_accommodations'] ?? 1 );
		$accommodation_price      = floatval( $settings['default_price'] ?? 0 );
		$currency                 = esc_attr( $this->get_currency_setting() );
		$allow_private            = ! empty( $settings['allow_private_all'] );

		// Get dynamic product names.
		$product_names = $this->get_product_names();

		// Get all accommodation posts with caching.
		$accommodation_posts = $this->get_cached_accommodations( $available_accommodations );

		// Sync accommodation posts with available_accommodations setting.
		$current_count = count( $accommodation_posts );
		if ( $current_count !== $available_accommodations ) {
			$accommodation_posts = $this->sync_accommodation_posts( $accommodation_posts, $available_accommodations, $global_settings );
		}

		// Optimize performance: Pre-load all post meta data to avoid N+1 queries.
		if ( ! empty( $accommodation_posts ) ) {
			$post_ids = wp_list_pluck( $accommodation_posts, 'ID' );
			update_postmeta_cache( $post_ids );
		}

		// Prepare accommodation display data with dynamic titles.
		$accommodation_data = array();
		foreach ( $accommodation_posts as $post ) {
			$accommodation_data[] = $this->get_accommodation_display_data( $post );
		}

		// Include the template file to render the page.
		include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-accommodation.php';
	}

	/**
	 * Save accommodation details from form submission
	 */
	public function save_accommodation_details() {
		// Only process if this form was submitted.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce verification happens below
		if ( ! isset( $_POST['aiohm_accommodation_details_nonce'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verification
		$details_nonce = sanitize_text_field( wp_unslash( $_POST['aiohm_accommodation_details_nonce'] ?? '' ) );
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $details_nonce, 'save_accommodation_details' ) ) {
			wp_die( esc_html__( 'Security check failed', 'aiohm-booking-pro' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'aiohm-booking-pro' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Input validated and sanitized below  
		if ( isset( $_POST['aiohm_accommodations'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually below
			$accommodations_data = wp_unslash( $_POST['aiohm_accommodations'] );
			
			// Validate input is array
			if ( ! is_array( $accommodations_data ) ) {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid accommodation data format received.', 'aiohm-booking-pro' ) . '</p></div>';
				});
				return;
			}
			
			$errors = array();
			$updated_count = 0;
			
			foreach ( $accommodations_data as $post_id => $accommodation ) {
				$post_id = intval( $post_id );
				
				// Validate accommodation data
				if ( ! is_array( $accommodation ) ) {
					/* translators: %d: accommodation ID */
					$errors[] = sprintf( __( 'Invalid data for accommodation ID %d', 'aiohm-booking-pro' ), $post_id );
					continue;
				}
				
				if ( get_post_type( $post_id ) !== 'aiohm_accommodation' ) {
					/* translators: %d: accommodation ID */
					$errors[] = sprintf( __( 'Accommodation with ID %d not found or invalid type', 'aiohm-booking-pro' ), $post_id );
					continue;
				}

				// Validate required fields
				$title = isset( $accommodation['title'] ) ? sanitize_text_field( $accommodation['title'] ) : '';
				$description = isset( $accommodation['description'] ) ? wp_kses_post( $accommodation['description'] ) : '';
				
				$post_data = array(
					'ID'           => $post_id,
					'post_title'   => $title,
					'post_content' => $description,
				);

				$updated_post_id = wp_update_post( $post_data, true );
				
				if ( is_wp_error( $updated_post_id ) ) {
					/* translators: %1$d: accommodation ID, %2$s: error message */
					$errors[] = sprintf( __( 'Failed to update accommodation ID %1$d: %2$s', 'aiohm-booking-pro' ), $post_id, $updated_post_id->get_error_message() );
					continue;
				}
				
				$updated_count++;

				if ( ! is_wp_error( $updated_post_id ) ) {
					// Validate and sanitize meta data
					$earlybird_price = isset( $accommodation['earlybird_price'] ) ? sanitize_text_field( $accommodation['earlybird_price'] ) : '';
					$price = isset( $accommodation['price'] ) ? sanitize_text_field( $accommodation['price'] ) : '';
					$type = isset( $accommodation['type'] ) ? sanitize_text_field( $accommodation['type'] ) : 'unit';
					
					// Validate price fields are numeric
					if ( ! empty( $earlybird_price ) && ! is_numeric( $earlybird_price ) ) {
						/* translators: %d: accommodation ID */
						$errors[] = sprintf( __( 'Invalid early bird price for accommodation ID %d', 'aiohm-booking-pro' ), $post_id );
					} else {
						update_post_meta( $updated_post_id, '_aiohm_booking_accommodation_earlybird_price', $earlybird_price );
					}
					
					if ( ! empty( $price ) && ! is_numeric( $price ) ) {
						/* translators: %d: accommodation ID */
						$errors[] = sprintf( __( 'Invalid price for accommodation ID %d', 'aiohm-booking-pro' ), $post_id );
					} else {
						update_post_meta( $updated_post_id, '_aiohm_booking_accommodation_price', $price );
					}
					
					update_post_meta( $updated_post_id, '_aiohm_booking_accommodation_type', $type );
				}
			}
			
			// Display errors if any occurred
			if ( ! empty( $errors ) ) {
				add_action( 'admin_notices', function() use ( $errors, $updated_count ) {
					$error_list = '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $errors ) ) . '</li></ul>';
					echo '<div class="notice notice-warning"><p>' . 
						/* translators: %1$d: number of updated accommodations, %2$d: number of errors */
						sprintf( __( 'Updated %1$d accommodations with %2$d errors:', 'aiohm-booking-pro' ), $updated_count, count( $errors ) ) . 
						'</p>' . $error_list . '</div>';
				});
			} else if ( $updated_count > 0 ) {
				add_action( 'admin_notices', function() use ( $updated_count ) {
					echo '<div class="notice notice-success"><p>' . 
						/* translators: %d: number of updated accommodations */
						sprintf( __( 'Successfully updated %d accommodations.', 'aiohm-booking-pro' ), $updated_count ) . 
						'</p></div>';
				});
			}

			wp_redirect( add_query_arg( array( 'updated' => 'true' ), wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Save form settings from admin page
	 *
	 * Handles form customization settings for non-AJAX requests only.
	 * AJAX requests are processed by the unified form settings handler.
	 *
	 * @since 2.0.3
	 */
	public function save_form_settings() {
		// Skip AJAX requests - they are handled by dedicated AJAX handlers
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		
		// Check if this is our form submission.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce verification happens below
		if ( ! isset( $_POST['aiohm_form_settings_nonce'] ) ) {
			return;
		}

		// Verify nonce.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verification
		$form_nonce = sanitize_text_field( wp_unslash( $_POST['aiohm_form_settings_nonce'] ?? '' ) );
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $form_nonce, 'aiohm_booking_save_form_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'aiohm-booking-pro' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'aiohm-booking-pro' ) );
		}

		// Handle unified form customization submission (check both action and nonce-based submission).
		// Only handle non-AJAX requests here - AJAX requests are handled by wp_ajax_* hooks.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce already verified above
		if ( ! defined( 'DOING_AJAX' ) && (
			( isset( $_POST['action'] ) && sanitize_text_field( wp_unslash( $_POST['action'] ) ) === 'aiohm_save_form_settings' ) ||
			( isset( $_POST['aiohm_form_settings_nonce'] ) && isset( $_POST['aiohm_booking_form_settings'] ) )
		) ) {
			AIOHM_BOOKING_Form_Settings_Handler::save_unified_form_settings();
			return;
		}

		// Legacy handling for old form format (if needed).
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually below
		if ( isset( $_POST['aiohm_booking_settings'] ) && is_array( wp_unslash( $_POST['aiohm_booking_settings'] ) ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually below
			$new_settings = wp_unslash( $_POST['aiohm_booking_settings'] );

			// Sanitize settings.
			$sanitized_settings = array();
			foreach ( $new_settings as $key => $value ) {
				if ( 'field_order' === $key && is_string( $value ) ) {
					// Handle field_order as comma-separated string.
					$sanitized_settings[ $key ] = sanitize_text_field( $value );
				} elseif ( is_string( $value ) ) {
					$sanitized_settings[ $key ] = sanitize_text_field( $value );
				} elseif ( is_numeric( $value ) ) {
					$sanitized_settings[ $key ] = intval( $value );
				} elseif ( is_bool( $value ) ) {
					$sanitized_settings[ $key ] = (bool) $value;
				} elseif ( is_array( $value ) ) {
					$sanitized_settings[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$sanitized_settings[ $key ] = sanitize_text_field( strval( $value ) );
				}
			}

			// Merge with existing settings.
			$updated_settings = array_merge( $current_settings, $sanitized_settings );

			// Save settings.
			$result = update_option( 'aiohm_booking_settings', $updated_settings );

			// Sync accommodations with new settings.
			if ( isset( $sanitized_settings['available_accommodations'] ) ) {
				$this->sync_accommodations_with_setting( intval( $sanitized_settings['available_accommodations'] ) );
			}

			// Clear template helper cache to ensure fresh data.
			AIOHM_BOOKING_Template_Helper::instance()->clear_cache();
		}

		// Redirect with success message.
		// wp_redirect( add_query_arg( array( 'settings-updated' => 'true' ), wp_get_referer() ) );.
		// exit.

		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Booking settings saved successfully!', 'aiohm-booking-pro' ) . '</p></div>';
			}
		);
	}

	/**
	 * Handle add new accommodation action from banner link.
	 */
	public function handle_add_new_accommodation() {
		// Check if this is our action.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'aiohm-booking-accommodations' ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'add_new_accommodation' ) {
			return;
		}

		// Verify nonce using standardized security helper.
		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $nonce, 'aiohm_booking_add_accommodation' ) ) {
			wp_die( esc_html__( 'Security verification failed. Please refresh the page and try again.', 'aiohm-booking-pro' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'aiohm-booking-pro' ) );
		}

		try {
			// Get current settings and accommodation count.
			$current_settings = AIOHM_BOOKING_Settings::get_all();
			$current_available = intval( $current_settings['available_accommodations'] ?? 1 );
			
			// Check maximum limit to prevent issues.
			if ( $current_available >= 50 ) {
				wp_die( esc_html__( 'Maximum number of accommodations (50) reached. Please contact support if you need more.', 'aiohm-booking-pro' ) );
			}
			
			// Increase the available accommodations count by 1.
			$new_count = $current_available + 1;
			
			// Update the setting which will trigger the sync method.
			$settings_updated = AIOHM_BOOKING_Settings::update_multiple( array( 'available_accommodations' => $new_count ) );
			
			if ( ! $settings_updated ) {
				wp_die( esc_html__( 'Failed to update accommodation settings. Please try again.', 'aiohm-booking-pro' ) );
			}
			
			// Use the same sync method as the settings box.
			$this->sync_accommodations_with_setting( $new_count );

			// Redirect back to accommodation page with success message.
			$redirect_url = add_query_arg( 
				array( 'accommodation_added' => '1' ), 
				admin_url( 'admin.php?page=aiohm-booking-accommodations' ) 
			);
			
			if ( ! wp_redirect( $redirect_url ) ) {
				// Fallback if redirect fails.
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Accommodation added successfully!', 'aiohm-booking-pro' ) . '</p></div>';
			}
			exit;
			
		} catch ( Exception $e ) {
			wp_die( 
				sprintf( 
					/* translators: %s: error message */
					esc_html__( 'An error occurred while adding the accommodation: %s', 'aiohm-booking-pro' ), 
					esc_html( $e->getMessage() )
				)
			);
		}
	}

	/**
	 * AJAX handler for saving individual accommodation data
	 */
	public function ajax_save_individual_accommodation() {
		// Verify nonce first.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		
		if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $nonce, 'admin_nonce' ) ) {
			wp_send_json_error( 
				array( 
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'aiohm-booking-pro' ),
					'code'    => 'security_check_failed'
				)
			);
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 
				array( 
					'message' => __( 'You do not have permission to perform this action.', 'aiohm-booking-pro' ),
					'code'    => 'insufficient_permissions'
				)
			);
		}

		// Validate and sanitize input data.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Input validated below
		$post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually below
		$accommodation_data = isset( $_POST['accommodation_data'] ) && is_array( wp_unslash( $_POST['accommodation_data'] ) )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['accommodation_data'] ) )
			: array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array elements sanitized individually below

		// Validate required fields.
		if ( ! $post_id || $post_id <= 0 ) {
			wp_send_json_error( 
				array( 
					'message' => __( 'Invalid accommodation ID provided.', 'aiohm-booking-pro' ),
					'code'    => 'missing_accommodation_id'
				)
			);
		}

		// Verify accommodation exists.
		if ( ! get_post( $post_id ) || get_post_type( $post_id ) !== 'aiohm_accommodation' ) {
			wp_send_json_error( 
				array( 
					'message' => __( 'Accommodation not found or invalid type.', 'aiohm-booking-pro' ),
					'code'    => 'accommodation_not_found'
				)
			);
		}

		// Create and validate DTO.
		try {
			$accommodation_data['id'] = $post_id;
			$dto                      = AccommodationDTO::from_array( $accommodation_data );

			if ( ! $dto->is_valid() ) {
				wp_send_json_error( 
					array( 
						'message' => __( 'Invalid accommodation data provided. Please check all required fields.', 'aiohm-booking-pro' ),
						'code'    => 'invalid_accommodation_data',
						'errors'  => $dto->get_validation_errors()
					)
				);
			}
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error( 
				array( 
					/* translators: %s: validation error message */
					'message' => sprintf( __( 'Validation error: %s', 'aiohm-booking-pro' ), $e->getMessage() ),
					'code'    => 'validation_exception'
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error( 
				array( 
					'message' => __( 'An unexpected error occurred while saving accommodation data.', 'aiohm-booking-pro' ),
					'code'    => 'unexpected_error'
				)
			);
		}

		// Prepare post data for update using DTO.
		$post_data = array(
			'ID'           => $post_id,
			'post_title'   => $dto->title,
			'post_content' => $dto->description,
		);

		// Update post with error handling.
		$updated_post_id = wp_update_post( $post_data, true );

		if ( is_wp_error( $updated_post_id ) ) {
			wp_send_json_error( 
				array( 
					/* translators: %s: error message from WordPress */
					'message' => sprintf( __( 'Failed to update accommodation: %s', 'aiohm-booking-pro' ), $updated_post_id->get_error_message() ),
					'code'    => 'post_update_failed'
				)
			);
		}

		// Update post meta using DTO values.
		update_post_meta( $post_id, '_aiohm_booking_accommodation_price', $dto->price );
		update_post_meta( $post_id, '_aiohm_booking_accommodation_earlybird_price', $dto->earlybird_price );
		update_post_meta( $post_id, '_aiohm_booking_accommodation_type', $dto->type );

		wp_send_json_success( array( 'message' => __( 'Accommodation saved successfully!', 'aiohm-booking-pro' ) ) );
	}



	/**
	 * Generate preview using the actual shortcode template
	 */
	public function render_form_customization_template() {
		// Use shared form settings for all booking types.
		$form_data = get_option( 'aiohm_booking_form_settings', array() );

		// Get default colors from existing user settings (global settings or legacy settings).
		$global_settings               = AIOHM_BOOKING_Settings::get_all();
		$legacy_accommodation_settings = get_option( 'aiohm_booking_accommodation_form_settings', array() );
		$legacy_tickets_settings       = get_option( 'aiohm_booking_tickets_form_settings', array() );

		// Use existing user brand/font colors as defaults, prioritizing any existing data.
		$default_brand_color = $global_settings['brand_color'] ??
								$legacy_accommodation_settings['form_primary_color'] ??
								$legacy_tickets_settings['form_primary_color'] ?? '#457d59';
		$default_font_color  = $global_settings['font_color'] ??
								$legacy_accommodation_settings['form_text_color'] ??
								$legacy_tickets_settings['form_text_color'] ?? '#333333';

		$form_data = array_merge(
			array(
				'form_primary_color'                       => $default_brand_color,
				'form_text_color'                          => $default_font_color,
				'brand_color'                              => $default_brand_color, // Template expects this key.
				'font_color'                               => $default_font_color,  // Template expects this key.
				'thankyou_page_url'                        => '',
				'allow_private_all'                        => false,
				'form_field_address'                       => false,
				'form_field_address_required'              => false,
				'form_field_age'                           => false,
				'form_field_age_required'                  => false,
				'form_field_company'                       => false,
				'form_field_company_required'              => false,
				'form_field_country'                       => false,
				'form_field_country_required'              => false,
				'form_field_arrival_time'                  => false,
				'form_field_arrival_time_required'         => false,
				'form_field_departure_time'                => false,
				'form_field_departure_time_required'       => false,
				'form_field_dietary_requirements'          => false,
				'form_field_dietary_requirements_required' => false,
				'form_field_accessibility_needs'           => false,
				'form_field_accessibility_needs_required'  => false,
				'form_field_emergency_contact'             => false,
				'form_field_emergency_contact_required'    => false,
				'form_field_nationality'                   => false,
				'form_field_nationality_required'          => false,
				'form_field_phone'                         => false,
				'form_field_phone_required'                => false,
				'form_field_purpose'                       => false,
				'form_field_purpose_required'              => false,
				'form_field_vat'                           => false,
				'form_field_vat_required'                  => false,
				'form_field_special_requests'              => false,
				'form_field_special_requests_required'     => false,
				'field_order'                              => array( 'address', 'age', 'company', 'country', 'arrival_time', 'departure_time', 'dietary_requirements', 'accessibility_needs', 'emergency_contact', 'nationality', 'phone', 'purpose', 'special_requests', 'vat' ),
			),
			$form_data
		);

		// Ensure saved color values override defaults and sync between different key formats.
		// The template saves as form_primary_color but displays from brand_color.
		if ( ! empty( $form_data['form_primary_color'] ) ) {
			$form_data['brand_color'] = $form_data['form_primary_color'];
		} else {
			// If no saved form_primary_color, use the brand_color for both.
			$form_data['form_primary_color'] = $form_data['brand_color'];
		}

		if ( ! empty( $form_data['form_text_color'] ) ) {
			$form_data['font_color'] = $form_data['form_text_color'];
		} else {
			// If no saved form_text_color, use the font_color for both.
			$form_data['form_text_color'] = $form_data['font_color'];
		}

		$fields_definition = $this->get_centralized_field_definitions( 'accommodation' );

		foreach ( array_keys( $fields_definition ) as $field_key ) {
			$form_data[ 'form_field_' . $field_key ]               = ! empty( $form_data[ 'form_field_' . $field_key ] );
			$form_data[ 'form_field_' . $field_key . '_required' ] = ! empty( $form_data[ 'form_field_' . $field_key . '_required' ] );
		}

		$field_order = $form_data['field_order'] ?? array_keys( $fields_definition );
		if ( ! is_array( $field_order ) ) {
			$field_order = ! empty( $field_order ) ? explode( ',', $field_order ) : array_keys( $fields_definition );
		}
		$form_data['field_order'] = $field_order;

		// Data for the template.
		$template_data = array(
			'form_type'           => 'accommodations',
			'section_title'       => 'Booking Form Customization',
			'section_description' => 'Customize the appearance and fields of your [aiohm_booking] shortcode form (accommodations mode)',
			'form_data'           => $form_data,
			'fields_definition'   => $fields_definition,
			'shortcode_preview'   => '[aiohm_booking enable_accommodations="true" enable_tickets="false"]',
			'nonce_action'        => 'save_form_settings',
			'nonce_name'          => 'aiohm_form_settings_nonce',
			'option_name'         => 'aiohm_booking_form_settings',
		);

		// Load the template using helper method.
		AIOHM_BOOKING_Template_Helper::instance()->render_form_customization_template( $template_data );
	}

	/**
	 * AJAX handler for updating preview
	 */
	public function update_preview_ajax() {
		$preview_filter = null; // Initialize filter variable outside try block.

		try {
			// Validate and sanitize nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Nonce verification happens below
			$received_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			// Verify nonce using security helper.
			if ( ! AIOHM_BOOKING_Security_Helper::verify_nonce( $received_nonce, 'aiohm_booking_update_preview' ) ) {
				wp_send_json_error( 
					array(
						'message' => __( 'Security verification failed. Please refresh the page and try again.', 'aiohm-booking-pro' ),
						'code'    => 'security_check_failed'
					)
				);
				return;
			}

			// Check user permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
				return;
			}

			// Handle simple test request.
			if ( isset( $_POST['test'] ) && sanitize_text_field( wp_unslash( $_POST['test'] ) ) === '1' ) {
				wp_send_json_success( array( 'html' => '<div style="padding: 20px; background: #e8f5e8; border: 1px solid #4caf50; border-radius: 4px;">✅ AJAX connection and nonce verification successful!</div>' ) );
				return;
			}

			// Sanitize and validate settings for preview.
			$temp_settings = array();
			if ( is_array( $_POST ) ) {
				foreach ( wp_unslash( $_POST ) as $key => $value ) {
					if ( strpos( $key, 'aiohm_booking_settings[' ) === 0 && is_string( $value ) ) {
						$setting_key                   = str_replace( array( 'aiohm_booking_settings[', ']' ), '', $key );
						$setting_key                   = sanitize_key( $setting_key );
						$temp_settings[ $setting_key ] = sanitize_text_field( $value );
					}
				}
			}

			// Get current settings before adding filter to avoid infinite recursion.
			$current_settings = $this->get_cached_global_settings();

			// Create filter function to temporarily override settings.
			$preview_filter = function ( $default ) use ( $temp_settings, $current_settings ) {
				return array_merge( $current_settings, $temp_settings );
			};

			// Temporarily override settings for this request.
			add_filter( 'pre_option_aiohm_booking_settings', $preview_filter );

			// Generate preview HTML.
			ob_start();

			// Set preview mode flag to disable form submission.
			$GLOBALS['aiohm_booking_preview_mode'] = true;

			// Replace with shortcode message for consistency.
			$shortcode_output = '<div class="aiohm-booking-shortcode-message" style="padding: 30px; border: 2px solid #0073aa; border-radius: 8px; background: #f0f8ff; text-align: center; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">
				<div style="margin-bottom: 20px;">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="#0073aa" style="vertical-align: middle;">
						<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,7V13H13V7H11M11,15V17H13V15H11Z"/>
					</svg>
				</div>
				<h3 style="margin: 0 0 15px 0; color: #0073aa; font-size: 18px; font-weight: 600;">Use Shortcode to Display Accommodations Form</h3>
				<p style="margin: 0 0 20px 0; color: #333; font-size: 14px; line-height: 1.5;">To display the accommodations booking form on your website, copy and paste this shortcode into any page or post:</p>
				<div style="background: #ffffff; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 20px 0; font-family: Monaco, Consolas, monospace;">
					<code style="color: #d63384; font-size: 16px; font-weight: 600;">[aiohm_booking_accommodations]</code>
				</div>
				<p style="margin: 15px 0 0 0; color: #666; font-size: 12px;">This will display the full accommodations booking form with your configured settings and styling.</p>
			</div>';

			echo wp_kses_post( $shortcode_output );

			// Reset preview mode.
			unset( $GLOBALS['aiohm_booking_preview_mode'] );

			// Remove the temporary settings filter.
			remove_filter( 'pre_option_aiohm_booking_settings', $preview_filter );

			$html = ob_get_clean();

			wp_send_json_success( array( 'html' => $html ) );

		} catch ( Exception $e ) {
			// Clean up the filter in case of error.
			if ( $preview_filter ) {
				remove_filter( 'pre_option_aiohm_booking_settings', $preview_filter );
			}

			// Provide a simple error preview.
			$error_html = '<div class="aiohm-booking-preview-error" style="padding: 20px; border: 1px solid #f00; border-radius: 8px; background: #fee; text-align: center;">
                <h3 style="margin: 0 0 10px 0; color: #c00;">Preview Error</h3>
                <p style="margin: 0; color: #600;">Unable to generate preview. Please check the error logs.</p>
            </div>';

			wp_send_json_success( array( 'html' => $error_html ) );
		}
	}

	// Helper methods.
	/**
	 * Cached global settings.
	 *
	 * @var array|null Cached global settings
	 */
	private static $cached_settings = null;
	/**
	 * Cached accommodations.
	 *
	 * @var array|null Cached accommodations
	 */
	private static $cached_accommodations = null;
	/**
	 * Cache key for settings.
	 *
	 * @var string Cache key for settings
	 */
	private static $cache_key = 'aiohm_booking_accommodation_settings_cache';
	/**
	 * Cache key for accommodations.
	 *
	 * @var string Cache key for accommodations
	 */
	private static $accommodations_cache_key = 'aiohm_booking_accommodations_cache';
	/**
	 * Cache expiration time in seconds.
	 *
	 * @var int Cache expiration time in seconds
	 */
	private static $cache_expiration = 3600; // 1 hour

	/**
	 * Get global settings with multi-layer caching to avoid duplicate retrievals
	 *
	 * @return array Global settings array
	 */
	private function get_cached_global_settings() {
		// First check runtime cache.
		if ( null !== self::$cached_settings ) {
			return self::$cached_settings;
		}

		// Check transient cache.
		$cached = get_transient( self::$cache_key );
		if ( false !== $cached ) {
			self::$cached_settings = $cached;
			return self::$cached_settings;
		}

		// Fetch fresh data and cache it.
		self::$cached_settings = AIOHM_BOOKING_Settings::get_all();

		// Store in transient for future requests.
		set_transient( self::$cache_key, self::$cached_settings, self::$cache_expiration );

		return self::$cached_settings;
	}

	/**
	 * Get cached accommodation posts with multi-layer caching
	 *
	 * @param int $limit Maximum number of accommodations to return.
	 * @return array Accommodation posts
	 */
	public function get_cached_accommodations( $limit = -1 ) {
		$cache_key_with_limit = self::$accommodations_cache_key . '_' . $limit;

		// First check runtime cache.
		if ( isset( self::$cached_accommodations[ $cache_key_with_limit ] ) ) {
			return self::$cached_accommodations[ $cache_key_with_limit ];
		}

		// Check transient cache.
		$cached = get_transient( $cache_key_with_limit );
		if ( false !== $cached ) {
			self::$cached_accommodations[ $cache_key_with_limit ] = $cached;
			return $cached;
		}

		// Fetch fresh data with optimized query parameters.
		$accommodations_query = new WP_Query(
			array(
				'post_type'              => 'aiohm_accommodation',
				'posts_per_page'         => $limit,
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'post_status'            => array( 'publish', 'draft' ),
				'fields'                 => 'all',
				'no_found_rows'          => true, // Skip pagination count queries
				'update_post_meta_cache' => true, // Pre-load meta cache
				'update_post_term_cache' => false, // Skip taxonomy cache (not needed)
				'suppress_filters'       => true, // Skip unnecessary filters for performance
			)
		);

		$posts = $accommodations_query->posts;

		// Cache the results.
		self::$cached_accommodations[ $cache_key_with_limit ] = $posts;
		set_transient( $cache_key_with_limit, $posts, self::$cache_expiration );

		return $posts;
	}

	/**
	 * Clear the settings cache - useful after settings updates
	 */
	public function clear_settings_cache() {
		self::$cached_settings = null;
		delete_transient( self::$cache_key );
		// Also clear the global settings cache.
		AIOHM_BOOKING_Settings::clear_cache();
		// Clear accommodation service cache too since accommodation count may have changed
		$this->clear_accommodation_service_cache();
	}

	/**
	 * Clear the accommodations cache - useful after accommodation updates
	 */
	public function clear_accommodations_cache() {
		self::$cached_accommodations = array(); // Clear runtime cache more efficiently
		
		// Clear specific cache keys instead of wildcard deletion for better performance
		$common_limits = array( 10, 20, 50, -1 ); // Common pagination limits
		foreach ( $common_limits as $limit ) {
			$cache_key = self::$accommodations_cache_key . '_limit_' . $limit;
			delete_transient( $cache_key );
		}
		
		// Clear object cache if available
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'aiohm_accommodations' );
		}
	}

	/**
	 * Clear accommodation service cache when cell statuses are updated
	 */
	public function clear_accommodation_service_cache() {
		if ( class_exists( 'AIOHM_BOOKING_Accommodation_Service' ) ) {
			AIOHM_BOOKING_Accommodation_Service::clear_cache();
		}
	}

	/**
	 * Initialize cache invalidation hooks
	 */
	private function init_cache_hooks() {
		// Clear settings cache when settings are updated.
		add_action( 'update_option_aiohm_booking_settings', array( $this, 'clear_settings_cache' ) );
		add_action( 'add_option_aiohm_booking_settings', array( $this, 'clear_settings_cache' ) );
		add_action( 'delete_option_aiohm_booking_settings', array( $this, 'clear_settings_cache' ) );

		// Clear accommodations cache when accommodation posts are updated.
		add_action( 'save_post_aiohm_accommodation', array( $this, 'clear_accommodations_cache' ) );
		add_action( 'delete_post', array( $this, 'clear_accommodations_cache_on_delete' ) );
		add_action( 'wp_trash_post', array( $this, 'clear_accommodations_cache' ) );
		add_action( 'untrash_post', array( $this, 'clear_accommodations_cache' ) );
	}

	/**
	 * Clear accommodations cache when a post is deleted (with post type check)
	 *
	 * @param int $post_id Post ID being deleted.
	 */
	public function clear_accommodations_cache_on_delete( $post_id ) {
		if ( get_post_type( $post_id ) === 'aiohm_accommodation' ) {
			$this->clear_accommodations_cache();
		}
	}

	/**
	 * Get currency setting from global settings
	 *
	 * @return string Currency code
	 */
	private function get_currency_setting() {
		$settings = $this->get_cached_global_settings();
		return $settings['currency'] ?? 'EUR';
	}

	/**
	 * Get all available accommodation types with singular and plural forms
	 * Centralized source of truth for accommodation types across the application.
	 *
	 * @return array Array of accommodation types with singular/plural forms
	 */
	public static function get_accommodation_types() {
		return aiohm_booking_get_accommodation_types();
	}

	/**
	 * Get accommodation types as simple key-value pairs for select dropdowns.
	 *
	 * @return array Simple array of type => label pairs
	 */
	public static function get_accommodation_types_for_select() {
		$types          = self::get_accommodation_types();
		$select_options = array();

		foreach ( $types as $key => $type ) {
			$select_options[ $key ] = $type['singular'];
		}

		return $select_options;
	}

	/**
	 * Get product names based on accommodation type
	 *
	 * @return array Array of product name variations
	 */
	private function get_product_names() {
		// Get accommodation type from global settings.
		$global_settings    = $this->get_cached_global_settings();
		$accommodation_type = $global_settings['accommodation_type'] ?? 'unit';

		// Use centralized accommodation types.
		$accommodation_types = self::get_accommodation_types();
		
		// Get type info with fallback to room if unit doesn't exist, then unit if room doesn't exist
		if ( isset( $accommodation_types[ $accommodation_type ] ) ) {
			$type_info = $accommodation_types[ $accommodation_type ];
		} elseif ( isset( $accommodation_types['room'] ) ) {
			$type_info = $accommodation_types['room'];
		} else {
			$type_info = $accommodation_types['unit'] ?? array( 'singular' => 'Accommodation', 'plural' => 'Accommodations' );
		}

		return array(
			'singular_cap'   => $type_info['singular'],
			'plural_cap'     => $type_info['plural'],
			'singular_lower' => strtolower( $type_info['singular'] ),
			'plural_lower'   => strtolower( $type_info['plural'] ),
		);
	}

	/**
	 * Generate default title for accommodation based on type and number.
	 *
	 * @param string $type Accommodation type.
	 * @param int    $number Accommodation number.
	 * @return string Default title
	 */
	public static function generate_default_title( $type, $number ) {
		$types     = self::get_accommodation_types();
		$type_info = $types[ $type ] ?? $types['unit'];

		return $type_info['singular'] . ' ' . $number;
	}

	/**
	 * Get accommodation display data with dynamic titles.
	 *
	 * @param WP_Post $post Accommodation post object.
	 * @return array Display data with dynamic title
	 */
	public function get_accommodation_display_data( $post ) {
		$custom_title = get_the_title( $post->ID );
		
		// Get all meta data in single calls (cache is pre-loaded).
		$type = get_post_meta( $post->ID, '_aiohm_booking_accommodation_type', true ) ?: 'unit';
		$number = get_post_meta( $post->ID, '_aiohm_booking_accommodation_number', true ) ?: 1;
		$earlybird_price = get_post_meta( $post->ID, '_aiohm_booking_accommodation_earlybird_price', true );
		$price = get_post_meta( $post->ID, '_aiohm_booking_accommodation_price', true );

		// Generate default title once.
		$default_title = self::generate_default_title( $type, $number );
		
		// Use custom title if set, otherwise use generated default.
		$display_title = ! empty( trim( $custom_title ) ) ? $custom_title : $default_title;

		return array(
			'id'              => $post->ID,
			'title'           => $display_title,
			'custom_title'    => $custom_title,
			'default_title'   => $default_title,
			'description'     => $post->post_content,
			'earlybird_price' => $earlybird_price,
			'price'           => $price,
			'type'            => $type,
			'number'          => $number,
			'is_custom_title' => ! empty( trim( $custom_title ) ),
		);
	}

	/**
	 * AJAX handler for syncing accommodations when count changes
	 */
	public function ajax_sync_accommodations() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'aiohm_booking_admin', 'nonce', false ) ) {
			wp_send_json_error( 'Security check failed' );
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		// Validate and sanitize input.
		$new_count = isset( $_POST['accommodation_count'] ) ? intval( wp_unslash( $_POST['accommodation_count'] ) ) : 0;

		// Validate accommodation count range..
		if ( $new_count < self::MIN_ACCOMMODATIONS || $new_count > self::SYNC_MAX_ACCOMMODATIONS ) {
			wp_send_json_error( 'Invalid accommodation count. Must be between ' . self::MIN_ACCOMMODATIONS . ' and ' . self::SYNC_MAX_ACCOMMODATIONS . '.' );
			return;
		}

		// Get current accommodation posts with optimized query.
		$accommodations_query = new WP_Query(
			array(
				'post_type'              => 'aiohm_accommodation',
				'posts_per_page'         => -1,
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
				'post_status'            => array( 'publish', 'draft' ),
				'fields'                 => 'ids', // Only need IDs for syncing.
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		// Convert IDs back to post objects only if needed.
		$current_posts = array();
		foreach ( $accommodations_query->posts as $post_id ) {
			$current_posts[] = get_post( $post_id );
		}

		// Get global settings for accommodation type.
		$global_settings = $this->get_cached_global_settings();

		// Sync the posts.
		$updated_posts = $this->sync_accommodation_posts( $current_posts, $new_count, $global_settings );

		// Update settings array with accommodation information.
		$this->sync_accommodations_to_settings( $updated_posts );

		// Clear all relevant caches after syncing.
		$this->clear_accommodations_cache();
		$this->clear_settings_cache();

		// Also clear calendar cache if available.
		if ( class_exists( 'AIOHM_BOOKING_Module_Calendar' ) ) {
			delete_transient( 'aiohm_booking_calendar_cache' );
		}

		wp_send_json_success(
			array(
				'message'             => sprintf( 'Successfully synced to %d accommodations', $new_count ),
				'accommodation_count' => count( $updated_posts ),
			)
		);
	}

	/**
	 * Sync accommodation posts with the available_accommodations setting.
	 * Creates missing posts or removes excess posts to match the desired count
	 *
	 * @param array $current_posts Current accommodation posts.
	 * @param int   $target_count  Target number of accommodations.
	 * @param array $global_settings Global settings for accommodation type.
	 * @return array Updated list of accommodation posts
	 */
	private function sync_accommodation_posts( $current_posts, $target_count, $global_settings ) {
		$current_count      = count( $current_posts );
		$accommodation_type = $global_settings['accommodation_type'] ?? 'unit';

		// Use centralized accommodation types.
		$accommodation_types = self::get_accommodation_types_for_select();
		$singular_name       = $accommodation_types[ $accommodation_type ] ?? 'Accommodation';

		if ( $current_count < $target_count ) {
			// Create missing posts.
			$posts_to_create = $target_count - $current_count;

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
					// Add accommodation meta.
					update_post_meta( $post_id, '_aiohm_booking_accommodation_number', $i );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_type', $accommodation_type );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_price', floatval( $global_settings['default_price'] ?? 0 ) );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_earlybird_price', floatval( $global_settings['default_earlybird_price'] ?? 0 ) );

					// Add missing meta fields required by calendar system.
					update_post_meta( $post_id, '_aiohm_booking_accommodation_units', 1 ); // Default to 1 unit per accommodation.
					update_post_meta( $post_id, '_aiohm_booking_accommodation_max_guests', 2 ); // Default maximum guests.

					// Add the new post to our array.
					$new_post = get_post( $post_id );
					if ( $new_post ) {
						$current_posts[] = $new_post;
					}
				}
			}
		} elseif ( $current_count > $target_count ) {
			// Remove excess posts (keep the first target_count posts).
			$posts_to_remove = array_slice( $current_posts, $target_count );

			foreach ( $posts_to_remove as $post_to_remove ) {
				wp_delete_post( $post_to_remove->ID, true ); // Force delete.
			}

			// Update the current_posts array to only include the remaining posts.
			$current_posts = array_slice( $current_posts, 0, $target_count );
		}

		return $current_posts;
	}

	/**
	 * Update settings with accommodation information from posts.
	 *
	 * @param array $accommodation_posts Array of accommodation post objects.
	 */
	private function sync_accommodations_to_settings( $accommodation_posts ) {
		$settings = get_option( 'aiohm_booking_settings', array() );

		// Build accommodations array for settings.
		$accommodations_array = array();

		foreach ( $accommodation_posts as $post ) {
			$accommodation_number = get_post_meta( $post->ID, '_aiohm_booking_accommodation_number', true );
			$accommodation_type   = get_post_meta( $post->ID, '_aiohm_booking_accommodation_type', true );
			$units                = get_post_meta( $post->ID, '_aiohm_booking_accommodation_units', true );

			$accommodations_array[] = array(
				'id'     => $post->ID,
				'name'   => $post->post_title,
				'type'   => $accommodation_type ? $accommodation_type : 'unit',
				'units'  => $units ? $units : 1,
				'number' => $accommodation_number ? $accommodation_number : $post->menu_order,
			);
		}

		// Update settings with accommodations array.
		$settings['accommodations'] = $accommodations_array;

		// Save updated settings.
		update_option( 'aiohm_booking_settings', $settings );
	}

	/**
	 * Sync accommodation posts with the available_accommodations setting.
	 * Deletes excess accommodations when user reduces the count
	 *
	 * @param int $target_count Target number of accommodations.
	 */
	private function sync_accommodations_with_setting( $target_count ) {
		// Get all accommodation posts.
		$accommodations = get_posts(
			array(
				'post_type'   => 'aiohm_accommodation',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'ID',
				'order'       => 'ASC',
			)
		);

		$current_count = count( $accommodations );

		if ( $current_count > $target_count ) {
			// Delete excess accommodations (keep the first $target_count).
			$excess_count             = $current_count - $target_count;
			$accommodations_to_delete = array_slice( $accommodations, $target_count );

			foreach ( $accommodations_to_delete as $accommodation ) {
				wp_delete_post( $accommodation->ID, true );

			}
		} elseif ( $current_count < $target_count ) {
			// Create missing accommodations.
			$missing_count      = $target_count - $current_count;
			$global_settings    = $this->get_cached_global_settings();
			$accommodation_type = $global_settings['accommodation_type'] ?? 'unit';

			for ( $i = 1; $i <= $missing_count; $i++ ) {
				$new_number = $current_count + $i;
				$new_title  = self::generate_default_title( $accommodation_type, $new_number );

				$post_id = wp_insert_post(
					array(
						'post_title'   => $new_title,
						'post_status'  => 'publish',
						'post_type'    => 'aiohm_accommodation',
						'post_content' => '',
						'menu_order'   => $new_number,
					)
				);

				if ( $post_id ) {
					update_post_meta( $post_id, '_aiohm_booking_accommodation_number', $new_number );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_type', $accommodation_type );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_price', floatval( $global_settings['default_price'] ?? 0 ) );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_earlybird_price', floatval( $global_settings['default_earlybird_price'] ?? 0 ) );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_units', 1 );
					update_post_meta( $post_id, '_aiohm_booking_accommodation_max_guests', 2 ); // Default maximum guests.

				}
			}
		}
	}

	/**
	 * Get centralized field definitions for accommodation module
	 *
	 * @param string $module_type The module type requesting fields (accommodation, tickets, or general).
	 * @return array Field definitions array
	 */
	public function get_centralized_field_definitions( $module_type = 'accommodation' ) {
		// Define shared/general fields that can be used by any module.
		$general_fields = array(
			'address'              => array(
				'label'       => 'Address',
				'description' => 'Guest\'s full address',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'age'                  => array(
				'label'       => 'Age',
				'description' => 'Guest\'s age',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'company'              => array(
				'label'       => 'Company',
				'description' => 'Business or organization name',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'country'              => array(
				'label'       => 'Country',
				'description' => 'Guest\'s country of residence',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'phone'                => array(
				'label'       => 'Phone Number',
				'description' => 'Guest\'s contact phone number',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'special_requests'     => array(
				'label'       => 'Special Requests',
				'description' => 'Additional guest requirements and requests',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'arrival_time'         => array(
				'label'       => 'Estimated Arrival Time',
				'description' => 'Ask guests for their estimated arrival time',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'departure_time'       => array(
				'label'       => 'Estimated Departure Time',
				'description' => 'Ask guests for their estimated departure time',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'purpose'              => array(
				'label'       => 'Purpose of Visit',
				'description' => 'Ask about the purpose of the visit - leisure, business, etc.',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'vat'                  => array(
				'label'       => 'VAT Number',
				'description' => 'Field for guests to provide a VAT number for invoicing',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'nationality'          => array(
				'label'       => 'Nationality',
				'description' => 'Guest\'s nationality',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'dietary_requirements' => array(
				'label'       => 'Dietary Requirements',
				'description' => 'Ask guests about dietary restrictions or preferences',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'accessibility_needs'  => array(
				'label'       => 'Accessibility Needs',
				'description' => 'Special accessibility requirements for guests',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
			'emergency_contact'    => array(
				'label'       => 'Emergency Contact',
				'description' => 'Emergency contact information for guests',
				'modules'     => array( 'accommodation', 'tickets' ),
			),
		);

		// Define accommodation-specific fields (fields that are ONLY for accommodation).
		$accommodation_fields = array(
			// Currently no fields are accommodation-only since we want all fields shared
			// All fields are now in $general_fields for both accommodation and tickets
		);

		// Define tickets-specific fields (for reference, not used in accommodation).
		$tickets_fields = array(
			'ticket_type' => array(
				'label'       => 'Ticket Type',
				'description' => 'Type of ticket being purchased',
				'modules'     => array( 'tickets' ),
			),
			'quantity'    => array(
				'label'       => 'Quantity',
				'description' => 'Number of tickets to purchase',
				'modules'     => array( 'tickets' ),
			),
		);

		// Combine fields based on module type.
		$all_fields = array();

		// Add general fields that are available to this module.
		foreach ( $general_fields as $key => $field ) {
			if ( in_array( $module_type, $field['modules'], true ) ) {
				$all_fields[ $key ] = array(
					'label'            => $field['label'],
					'description'      => $field['description'],
					'default_enabled'  => $field['default_enabled'] ?? true,
					'required_default' => $field['required_default'] ?? false,
				);
			}
		}

		// Add module-specific fields.
		if ( 'accommodation' === $module_type ) {
			foreach ( $accommodation_fields as $key => $field ) {
				$all_fields[ $key ] = array(
					'label'            => $field['label'],
					'description'      => $field['description'],
					'default_enabled'  => $field['default_enabled'] ?? false,
					'required_default' => $field['required_default'] ?? false,
				);
			}
		} elseif ( 'tickets' === $module_type ) {
			foreach ( $tickets_fields as $key => $field ) {
				$all_fields[ $key ] = array(
					'label'            => $field['label'],
					'description'      => $field['description'],
					'default_enabled'  => $field['default_enabled'] ?? false,
					'required_default' => $field['required_default'] ?? false,
				);
			}
		}

		return $all_fields;
	}

	/**
	 * Update accommodation availability when payment is completed
	 *
	 * This method is called when the 'aiohm_booking_payment_completed' action is triggered
	 * for accommodation bookings. It updates the calendar cell statuses to reflect that
	 * the accommodation units are now booked.
	 *
	 * @param int    $order_id      Order ID.
	 * @param string $payment_method Payment method used.
	 * @param mixed  $payment_data  Additional payment data.
	 */
	public function update_accommodation_availability( $order_id, $payment_method, $payment_data ) {
		global $wpdb;

		// Get order details from the database
		$table_name = $wpdb->prefix . 'aiohm_booking_order';
		$order = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query for plugin functionality
			$wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE id = %d', $order_id ),
			ARRAY_A
		);

		if ( ! $order ) {
			return;
		}

		// Only process accommodation orders
		if ( 'accommodation' !== $order['mode'] ) {
			return;
		}

		// Check if order is already marked as paid
		if ( 'paid' !== $order['status'] ) {
			return;
		}

		// Get accommodation details from order data
		$checkin_date = $order['check_in_date'];
		$checkout_date = $order['check_out_date'];
		$units_qty = intval( $order['units_qty'] );

		if ( empty( $checkin_date ) || empty( $checkout_date ) || $units_qty <= 0 ) {
			return;
		}

		// Get accommodation IDs from order metadata or determine which accommodations were booked
		// For now, we'll assume the booking was for specific accommodation units
		// This may need to be enhanced based on how accommodation selections are stored
		$accommodation_ids = array();

		// Try to get accommodation IDs from order notes (stored during booking creation)
		if ( ! empty( $order['notes'] ) ) {
			// Look for "Accommodation IDs: " followed by JSON
			if ( preg_match( '/Accommodation IDs:\s*(\[.*?\])/', $order['notes'], $matches ) ) {
				$decoded_ids = json_decode( $matches[1], true );
				if ( is_array( $decoded_ids ) ) {
					$accommodation_ids = array_map( 'intval', $decoded_ids );
				}
			}
		}

		// Fallback: get the first N accommodations based on units_qty
		if ( empty( $accommodation_ids ) ) {
			$available_accommodations = AIOHM_BOOKING_Accommodation_Service::get_accommodations();
			$accommodation_ids = array_slice( $available_accommodations, 0, $units_qty );
		}

		if ( empty( $accommodation_ids ) ) {
			return;
		}

		// Update calendar cell statuses
		$this->update_calendar_for_accommodation_booking( $checkin_date, $checkout_date, $accommodation_ids, $order_id );
	}

	/**
	 * Update calendar data when an accommodation booking is confirmed
	 *
	 * @param string $checkin_date     Check-in date.
	 * @param string $checkout_date    Check-out date.
	 * @param array  $accommodation_ids Array of accommodation IDs.
	 * @param int    $order_id         Order ID.
	 */
	private function update_calendar_for_accommodation_booking( $checkin_date, $checkout_date, $accommodation_ids, $order_id ) {
		$cell_statuses = get_option( 'aiohm_booking_cell_statuses', array() );

		$checkin  = new DateTime( $checkin_date );
		$checkout = new DateTime( $checkout_date );

		// Loop through each date in the booking
		$current_date = clone $checkin;
		while ( $current_date < $checkout ) {
			$date_string = $current_date->format( 'Y-m-d' );

			// Update status for each accommodation
			foreach ( $accommodation_ids as $accommodation_id ) {
				$cell_key = $accommodation_id . '_' . $date_string . '_full';
				$cell_statuses[ $cell_key ] = array(
					'status'     => 'booked',
					'booking_id' => $order_id,
					'price'      => 0, // Could be enhanced to store booking price
				);
			}

			$current_date->modify( '+1 day' );
		}

		// Save updated calendar data
		update_option( 'aiohm_booking_cell_statuses', $cell_statuses );

		// Clear accommodation service cache to ensure statistics are updated
		AIOHM_BOOKING_Accommodation_Service::clear_cache();
	}
}

