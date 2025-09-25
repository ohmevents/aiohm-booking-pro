<?php
/**
 * Assets management class for AIOHM Booking.
 *
 * @package AIOHM_Booking_PRO
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assets management class.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Assets {

	/**
	 * Con      // Frontend base JavaScript.
		wp_enqueue_script(
			'aiohm-booking-frontend-base',
			self::get_url( 'js/aiohm-booking-base.js' ),
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Register shortcode JavaScript (needed for calendar functionality).
		wp_register_script(
			'aiohm-booking-shortcode',
			self::get_url( 'js/aiohm-booking-shortcode.js' ),
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Localize shortcode script with frontend variables.
		wp_localize_script(
			'aiohm-booking-shortcode',
			'aiohm_booking_frontend',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
				'calendar_colors' => $calendar_colors,
				'brand_color'     => $brand_color,
				'booking_url'     => home_url( '/booking/' ),
				'booking_id'      => $localization_booking_id,
				'i18n'            => array(
					'select_payment_method'  => __( 'Please select a payment method.', 'aiohm-booking-pro' ),
					'invalid_booking_id'     => __( 'Invalid booking ID.', 'aiohm-booking-pro' ),
					'processing'             => __( 'Processing...', 'aiohm-booking-pro' ),
					'complete_booking'       => __( 'Complete Booking', 'aiohm-booking-pro' ),
					'error_occurred'         => __( 'An error occurred. Please try again.', 'aiohm-booking-pro' ),
					'payment_failed'         => __( 'Payment failed. Please try again.', 'aiohm-booking-pro' ),
					'unknown_payment_method' => __( 'Unknown payment method selected.', 'aiohm-booking-pro' ),
				),
			)
		);

		// Load frontend JavaScript.
		wp_enqueue_script(
			'aiohm-booking-frontend',
			self::get_url( 'js/aiohm-booking-frontend.js' ),
			array( 'jquery', 'aiohm-booking-shortcode', 'aiohm-booking-frontend-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp_head', array( $this, 'suppress_jquery_migrate_warnings' ), 1 );
		add_action( 'admin_head', array( $this, 'suppress_jquery_migrate_warnings' ), 1 );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only load on our admin pages, unless we're in preview mode
		if ( ! $this->is_aiohm_booking_admin_page( $hook_suffix ) && ! isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) {
			return;
		}

		// Base JavaScript (must load first).
		wp_enqueue_script(
			'aiohm-booking-base',
			self::get_url( 'js/aiohm-booking-base.js' ),
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Admin JavaScript.
		wp_enqueue_script(
			'aiohm-booking-admin',
			self::get_url( 'js/aiohm-booking-admin.js' ),
			array( 'jquery', 'jquery-ui-sortable', 'wp-util', 'aiohm-booking-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// jQuery migrate suppression - load early with jquery-migrate dependency.
		wp_enqueue_script(
			'aiohm-booking-jquery-migrate',
			self::get_url( 'js/aiohm-booking-jquery-migrate.js' ),
			array( 'jquery', 'jquery-migrate' ),
			AIOHM_BOOKING_VERSION,
			false
		);

		// Load shortcodes CSS (includes sandwich system styles).
		wp_enqueue_style(
			'aiohm-booking-shortcodes',
			self::get_url( 'css/aiohm-booking-shortcodes.css' ),
			array(),
			AIOHM_BOOKING_VERSION
		);

		// Admin-specific CSS (includes calendar and admin interface styles).
		wp_enqueue_style(
			'aiohm-booking-admin',
			self::get_url( 'css/aiohm-booking-admin.css' ),
			array( 'aiohm-booking-shortcodes' ),
			AIOHM_BOOKING_VERSION . '-admin-fix'
		);

		// Unified CSS for general booking functionality.
		wp_enqueue_style(
			'aiohm-booking-unified',
			self::get_url( 'css/aiohm-booking-unified.css' ),
			array( 'aiohm-booking-admin' ),
			AIOHM_BOOKING_VERSION
		);

		// Localize admin script.
		wp_localize_script(
			'aiohm-booking-admin',
			'aiohm_booking_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
				'strings'  => array(
					'loading' => __( 'Loading...', 'aiohm-booking-pro' ),
					'error'   => __( 'An error occurred. Please try again.', 'aiohm-booking-pro' ),
					'success' => __( 'Success!', 'aiohm-booking-pro' ),
				),
				'i18n'     => array(
					'applyingChanges'      => __( 'Applying changes...', 'aiohm-booking-pro' ),
					'saving'               => __( 'Saving...', 'aiohm-booking-pro' ),
					'saved'                => __( 'Saved!', 'aiohm-booking-pro' ),
					'savingSettings'       => __( 'Saving settings...', 'aiohm-booking-pro' ),
					'testing'              => __( 'Testing...', 'aiohm-booking-pro' ),
					'testingConnection'    => __( 'Testing connection...', 'aiohm-booking-pro' ),
					'connectionTestFailed' => __( 'Connection test failed', 'aiohm-booking-pro' ),
					'saveFailed'           => __( 'Save failed', 'aiohm-booking-pro' ),
					'apiKeySaved'          => __( 'API key saved successfully!', 'aiohm-booking-pro' ),
					'connectionSuccessful' => __( 'Connection successful!', 'aiohm-booking-pro' ),
				),
			)
		);

		// Load enhanced settings CSS on settings page.
		if ( strpos( $hook_suffix, 'aiohm-booking-settings' ) !== false ) {
			// Settings styles are now included in the unified CSS.
			// Only load additional JavaScript for settings page interactions.

			// Settings template script.
			wp_enqueue_script(
				'aiohm-booking-settings-template',
				self::get_url( 'js/aiohm-booking-settings-template.js' ),
				array( 'jquery' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// AI provider modules now handle their own assets through their respective classes

			// Settings script is now loaded by the settings module.
			wp_enqueue_script(
				'aiohm-booking-settings-admin',
				self::get_url( 'js/aiohm-booking-settings-admin.js' ),
				array( 'jquery', 'jquery-ui-sortable' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// Localize settings script for toggles and interactions.
			$admin_vars = array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
				'preview_nonce'        => wp_create_nonce( 'aiohm_booking_update_preview' ),
				'current_user_id'      => get_current_user_id(),
				'nonce_generated_time' => time(),
				'stripe_nonce'         => wp_create_nonce( 'aiohm_booking_admin_nonce' ),
				'i18n'                 => array(
					'saving'                    => __( 'Saving...', 'aiohm-booking-pro' ),
					'saved'                     => __( 'Saved!', 'aiohm-booking-pro' ),
					'applyingChanges'           => __( 'Applying changes...', 'aiohm-booking-pro' ),
					'savingSettings'            => __( 'Saving settings...', 'aiohm-booking-pro' ),
					'testing'                   => __( 'Testing...', 'aiohm-booking-pro' ),
					'testingConnection'         => __( 'Testing connection...', 'aiohm-booking-pro' ),
					'connectionTestFailed'      => __( 'Connection test failed', 'aiohm-booking-pro' ),
					'saveFailed'                => __( 'Save failed', 'aiohm-booking-pro' ),
					'invalidAccommodationIndex' => __( 'Invalid accommodation index', 'aiohm-booking-pro' ),
					'errorPrefix'               => __( 'Error: ', 'aiohm-booking-pro' ),
					'settings_saved'            => __( 'Settings saved successfully!', 'aiohm-booking-pro' ),
					'apiKeySaved'               => __( 'API key saved successfully!', 'aiohm-booking-pro' ),
					'connectionSuccessful'      => __( 'Connection successful!', 'aiohm-booking-pro' ),
					'settings_error'            => __( 'Error saving settings. Please try again.', 'aiohm-booking-pro' ),
				),
			);
			wp_localize_script( 'aiohm-booking-settings-admin', 'aiohm_booking_admin', $admin_vars );
		}

		// Load accommodation admin assets ONLY on accommodation pages.
		if ( strpos( $hook_suffix, 'aiohm-booking-accommodations' ) !== false ) {
			// Load WordPress color picker assets for form customization.
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_script(
				'aiohm-booking-accommodation-admin',
				self::get_url( 'js/aiohm-booking-accommodation-admin.js' ),
				array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker', 'aiohm-booking-admin' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// Accommodation script uses the main admin localization.
		}

		// Load tickets admin assets on tickets pages.
		if ( strpos( $hook_suffix, 'aiohm-booking-tickets' ) !== false ) {
			// Load WordPress color picker assets for form customization.
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			// Load EventON popup script if EventON is available
			if ( class_exists( 'EventON' ) || ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'eventON/eventon.php' ) ) ) {
				wp_enqueue_script(
					'aiohm-booking-eventon-popup',
					self::get_url( 'js/aiohm-booking-eventon-popup.js' ),
					array( 'jquery', 'aiohm-booking-admin' ),
					AIOHM_BOOKING_VERSION,
					true
				);
			}
		}

		// Load calendar template assets on calendar page.
		if ( strpos( $hook_suffix, 'aiohm-booking-calendar' ) !== false ) {
			// Admin CSS is already enqueued above, just load calendar-specific JS
			wp_enqueue_script(
				'aiohm-booking-calendar-template',
				self::get_url( 'js/aiohm-booking-calendar-template.js' ),
				array( 'jquery' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// Localize calendar script with saved colors.
			$saved_colors = get_option( 'aiohm_booking_calendar_colors', array() );
			wp_localize_script(
				'aiohm-booking-calendar-template',
				'aiohm_booking_calendar',
				array(
					'saved_colors' => $saved_colors,
				)
			);
		}

		// Admin pages now show shortcode messages instead of actual forms.
		// No need to load modular card JavaScript on admin pages.

		// Load frontend assets for preview mode
		if ( isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) {
			$this->load_frontend_assets_for_preview();
		}
	}

	/**
	 * Load frontend assets specifically for preview mode in admin.
	 */
	private function load_frontend_assets_for_preview() {
		// Load unified frontend CSS.
		wp_enqueue_style(
			'aiohm-booking-frontend',
			self::get_url( 'css/aiohm-booking-unified.css' ),
			array( 'aiohm-booking-shortcodes' ),
			AIOHM_BOOKING_VERSION
		);

		// Load frontend base JavaScript.
		wp_enqueue_script(
			'aiohm-booking-frontend-base',
			self::get_url( 'js/aiohm-booking-base.js' ),
			array( 'jquery' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Load frontend JavaScript.
		wp_enqueue_script(
			'aiohm-booking-frontend',
			self::get_url( 'js/aiohm-booking-frontend.js' ),
			array( 'jquery', 'aiohm-booking-shortcode', 'aiohm-booking-frontend-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Load event selection assets for preview.
		// Event selection CSS is now consolidated in aiohm-booking-shortcodes.css
		wp_enqueue_script(
			'aiohm-booking-event-selection',
			self::get_url( 'js/aiohm-booking-event-selection.js' ),
			array( 'jquery', 'aiohm-booking-frontend-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Load contact form assets for preview.
		// Contact form CSS is now included in aiohm-booking-shortcodes.css
		wp_enqueue_script(
			'aiohm-booking-contact-form',
			self::get_url( 'js/aiohm-booking-contact-form.js' ),
			array( 'jquery', 'aiohm-booking-frontend-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Pricing summary assets are now included in consolidated shortcodes CSS

		wp_enqueue_script(
			'aiohm-booking-pricing-summary',
			self::get_url( 'js/aiohm-booking-pricing-summary.js' ),
			array( 'jquery', 'aiohm-booking-frontend-base' ),
			AIOHM_BOOKING_VERSION,
			true
		);

		// Get calendar colors for localization.
		$calendar_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'calendar' );
		$default_colors  = array(
			'free'     => '#ffffff',
			'booked'   => '#e74c3c',
			'pending'  => '#f39c12',
			'external' => '#8e44ad',
			'blocked'  => '#2c3e50',
			'special'  => '#3498db',
			'private'  => '#2949a8',
		);
		$saved_colors    = get_option( 'aiohm_booking_calendar_colors', array() );
		$calendar_colors = wp_parse_args( $saved_colors, $default_colors );

		// Get brand color.
		$main_settings = get_option( 'aiohm_booking_settings', array() );
		$brand_color   = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? '#457d59';

		// Localize frontend script.
		wp_localize_script(
			'aiohm-booking-frontend',
			'aiohm_booking_frontend',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
				'calendar_colors' => $calendar_colors,
				'brand_color'     => $brand_color,
				'booking_url'     => home_url( '/booking/' ),
				'booking_id'      => '',
				'i18n'            => array(
					'select_payment_method' => __( 'Please select a payment method.', 'aiohm-booking-pro' ),
					'invalid_booking_id'    => __( 'Invalid booking ID.', 'aiohm-booking-pro' ),
					'processing'            => __( 'Processing...', 'aiohm-booking-pro' ),
					'complete_booking'      => __( 'Complete Booking', 'aiohm-booking-pro' ),
					'error_occurred'        => __( 'An error occurred. Please try again.', 'aiohm-booking-pro' ),
				),
			)
		);
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		// Load unified frontend CSS when shortcodes are present or booking pages are being viewed.
		// Also load in admin context if we're in preview mode
		if ( is_admin() && ! isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) {
			return;
		}

		// Check if any booking shortcodes are present on the page or force load on all frontend pages.
		global $post;
		$load_assets = false;

		// Force load on booking page or pages with booking in the slug
		if ( is_page() && $post && (
			$post->post_name === 'booking' ||
			strpos( $post->post_name, 'booking' ) !== false ||
			strpos( $post->post_content, 'aiohm_booking' ) !== false
		) ) {
			$load_assets = true;
		}

		// Always load if we have booking shortcodes, or if we're in preview mode
		if ( $post && ( has_shortcode( $post->post_content, 'aiohm_booking' ) ||
			has_shortcode( $post->post_content, 'aiohm_booking_checkout' ) ||
			has_shortcode( $post->post_content, 'aiohm_booking_events' ) ||
			has_shortcode( $post->post_content, 'aiohm_booking_accommodations' ) ) ) {
			$load_assets = true;
		}

		// Also load in preview mode even without shortcodes
		if ( isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) {
			$load_assets = true;
		}

		// Also load if query parameter suggests we should (for testing).
		if ( isset( $_GET['aiohm_booking_test'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$load_assets = true;
		}


		if ( $load_assets ) {

			// Load shortcodes CSS (includes sandwich system styles).
			wp_enqueue_style(
				'aiohm-booking-shortcodes',
				self::get_url( 'css/aiohm-booking-shortcodes.css' ),
				array(),
				AIOHM_BOOKING_VERSION
			);

			// Use the same unified CSS as admin/preview to ensure consistency.
			wp_enqueue_style(
				'aiohm-booking-frontend',
				self::get_url( 'css/aiohm-booking-unified.css' ),
				array( 'aiohm-booking-shortcodes' ),
				AIOHM_BOOKING_VERSION
			);

			// Store checkout page detection and booking ID for later use
			$is_checkout_page    = $post && has_shortcode( $post->post_content, 'aiohm_booking_checkout' );
			$is_booking_page     = $post && has_shortcode( $post->post_content, 'aiohm_booking' );
			$checkout_booking_id = 0;

			// Load checkout JS for checkout pages and booking pages with sandwich template.
			if ( $is_checkout_page || $is_booking_page ) {
				wp_enqueue_script(
					'aiohm-booking-checkout',
					self::get_url( 'js/aiohm-booking-checkout.js' ),
					array( 'jquery' ),
					AIOHM_BOOKING_VERSION,
					true
				);

				// Get booking ID from URL for checkout functionality
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameter for read-only booking display
				$checkout_booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;

			}

			// Frontend base JavaScript.
			wp_enqueue_script(
				'aiohm-booking-frontend-base',
				self::get_url( 'js/aiohm-booking-base.js' ),
				array( 'jquery' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			wp_enqueue_script(
				'aiohm-booking-frontend',
				self::get_url( 'js/aiohm-booking-frontend.js' ),
				array( 'jquery', 'aiohm-booking-shortcode', 'aiohm-booking-frontend-base' ),
				AIOHM_BOOKING_VERSION,
				true
			);

			// Enqueue shortcode script for calendar functionality
			wp_enqueue_script( 'aiohm-booking-shortcode' );

			// ==========================================
			// MODULAR 3-CARD SYSTEM ASSETS (FRONTEND ONLY).
			// ==========================================

			// Only load modular assets when shortcodes are actually present, or in preview mode.
			if ( ( $post && ( has_shortcode( $post->post_content, 'aiohm_booking' ) ||
				has_shortcode( $post->post_content, 'aiohm_booking_events' ) ||
				has_shortcode( $post->post_content, 'aiohm_booking_accommodations' ) ) ) ||
				isset( $GLOBALS['aiohm_booking_preview_mode'] ) ) {

				// Card 1: Event Selection Assets.
				// Event selection CSS is now consolidated in aiohm-booking-shortcodes.css
				wp_enqueue_script(
					'aiohm-booking-event-selection',
					self::get_url( 'js/aiohm-booking-event-selection.js' ),
					array( 'jquery', 'aiohm-booking-frontend-base' ),
					AIOHM_BOOKING_VERSION,
					true
				);

				// Card 2: Contact Form Assets.
				// Contact form CSS is now included in aiohm-booking-shortcodes.css
				wp_enqueue_script(
					'aiohm-booking-contact-form',
					self::get_url( 'js/aiohm-booking-contact-form.js' ),
					array( 'jquery', 'aiohm-booking-frontend-base' ),
					AIOHM_BOOKING_VERSION,
					true
				);

				// Card 3: Pricing Summary Assets (CSS now in consolidated shortcodes file).

				wp_enqueue_script(
					'aiohm-booking-pricing-summary',
					self::get_url( 'js/aiohm-booking-pricing-summary.js' ),
					array( 'jquery', 'aiohm-booking-frontend-base' ),
					AIOHM_BOOKING_VERSION,
					true
				);

				// Sandwich Navigation for Tab-based Booking Form.
				wp_enqueue_script(
					'aiohm-booking-sandwich-navigation',
					self::get_url( 'js/aiohm-booking-sandwich-navigation.js' ),
					array( 'jquery', 'aiohm-booking-frontend-base' ),
					AIOHM_BOOKING_VERSION,
					true
				);
			}

			// Localize frontend script.
			// Use checkout-specific booking ID if on checkout page, otherwise general detection
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameter for read-only booking display
			$localization_booking_id = $is_checkout_page ? $checkout_booking_id :
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameter for read-only booking display
				( isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0 );

			// Check if user has premium access
			$is_premium_user = false;
			if ( function_exists( 'aiohm_booking_fs' ) ) {
				$is_premium_user = aiohm_booking_fs()->can_use_premium_code__premium_only();
			}

			wp_localize_script(
				'aiohm-booking-frontend',
				'aiohm_booking_frontend', // Changed to match checkout.js expectations.
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'rest_url'        => rest_url( 'aiohm-booking/v1/' ),
					'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
					// Use properly sanitized booking ID
					'booking_id'      => $localization_booking_id,
					// Premium user status for navigation logic
					'is_premium_user' => $is_premium_user,
					// Add i18n translations.
					'i18n'            => array(
						'select_payment_method'  => __( 'Please select a payment method.', 'aiohm-booking-pro' ),
						'invalid_booking_id'     => __( 'Invalid booking ID.', 'aiohm-booking-pro' ),
						'processing'             => __( 'Processing...', 'aiohm-booking-pro' ),
						'complete_booking'       => __( 'Complete Booking', 'aiohm-booking-pro' ),
						'error_occurred'         => __( 'An error occurred. Please try again.', 'aiohm-booking-pro' ),
						'payment_failed'         => __( 'Payment failed. Please try again.', 'aiohm-booking-pro' ),
						'unknown_payment_method' => __( 'Unknown payment method selected.', 'aiohm-booking-pro' ),
					),
				)
			);

			// Also localize for compatibility with legacy JavaScript that expects 'aiohm_booking' object
			wp_localize_script(
				'aiohm-booking-frontend-base',
				'aiohm_booking',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
					'booking_id'      => $localization_booking_id,
					'is_premium_user' => $is_premium_user,
				)
			);

			// Add additional localization for checkout page if needed
			if ( $is_checkout_page ) {
				wp_localize_script(
					'aiohm-booking-checkout',
					'aiohm_booking_checkout',
					array(
						'ajax_url'   => admin_url( 'admin-ajax.php' ),
						'nonce'      => wp_create_nonce( 'aiohm_booking_frontend_nonce' ),
						'booking_id' => $checkout_booking_id,
					)
				);
			}
		}
	}

	/**
	 * Check if current page is an AIOHM Booking admin page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 *
	 * @return bool
	 */
	private function is_aiohm_booking_admin_page( $hook_suffix ) {
		// Main plugin pages and all module pages.
		$aiohm_pages = array(
			'toplevel_page_aiohm-booking',
			'aiohm-booking_page_aiohm-booking-settings',
			'aiohm-booking_page_aiohm-booking-accommodations',
			'aiohm-booking_page_aiohm-booking-calendar',
			'aiohm-booking_page_aiohm-booking-orders',
			'aiohm-booking_page_aiohm-booking-tickets',
			'aiohm-booking_page_aiohm-booking-notifications',
			'aiohm-booking_page_aiohm-booking-css-manager',
			'aiohm-booking_page_aiohm-booking-get-help',
		);

		// Also match any page that starts with 'aiohm-booking-pro'.
		if ( strpos( $hook_suffix, 'aiohm-booking-pro' ) !== false ) {
			return true;
		}

		$result = in_array( $hook_suffix, $aiohm_pages, true );
		return $result;
	}


	/**
	 * Get asset URL.
	 *
	 * @since  2.0.0
	 *
	 * @param string $path Asset path relative to plugin assets directory.
	 *
	 * @return string Full URL to asset.
	 */
	public static function get_url( $path ) {
		return AIOHM_BOOKING_URL . 'assets/' . ltrim( $path, '/' );
	}

	/**
	 * Get asset path.
	 *
	 * @since  2.0.0
	 *
	 * @param string $path Asset path relative to plugin assets directory.
	 *
	 * @return string Full path to asset.
	 */
	public static function get_path( $path ) {
		return AIOHM_BOOKING_DIR . 'assets/' . ltrim( $path, '/' );
	}

	/**
	 * Suppress jQuery Migrate warnings for this plugin.
	 *
	 * @since  2.0.0
	 */
	public function suppress_jquery_migrate_warnings() {
		// Output early JavaScript to fix jQuery migrate compatibility
		?>
		<script type="text/javascript">
		(function() {
			// Fix jQuery migrate warnings compatibility with other plugins
			function fixJQueryMigrate() {
				if (typeof jQuery !== 'undefined' && jQuery.migrateWarnings) {
					// Ensure migrateWarnings is always an array
					if (!Array.isArray(jQuery.migrateWarnings)) {
						jQuery.migrateWarnings = [];
					}
					// Disable trace logging
					if (jQuery.migrateTrace !== false) {
						jQuery.migrateTrace = false;
					}
				}
			}
			
			// Apply fix immediately if jQuery is available
			if (typeof jQuery !== 'undefined') {
				fixJQueryMigrate();
			}
			
			// Also apply when jQuery is ready
			document.addEventListener('DOMContentLoaded', function() {
				if (typeof jQuery !== 'undefined') {
					fixJQueryMigrate();
				}
			});
		})();
		</script>
		<?php
	}
}
