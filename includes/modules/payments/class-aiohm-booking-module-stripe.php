<?php
/* <fs_premium_only> */
/**
 * Stripe Payment Module
 *
 * Handles Stripe payment processing for AIOHM Booking system.
 * This module is only available for premium users.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Stripe Module Class
 *
 * Provides Stripe payment processing functionality.
 * Premium only module.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Module_Stripe extends AIOHM_BOOKING_Module_Abstract {

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get UI definition for admin interface.
	 *
	 * @since  2.0.0
	 * @return array Module UI definition array.
	 */
	public static function get_ui_definition() {
		return array(
			'id'                  => 'stripe',
			'name'                => __( 'Stripe Payments', 'aiohm-booking-pro' ),
			'description'         => __( 'Accept credit card payments securely with Stripe integration.', 'aiohm-booking-pro' ),
			'icon'                => '💳',
			'category'            => 'payment',
			'access_level'        => 'premium',
			'is_premium'          => true,
			'priority'            => 10,
			'has_settings'        => true,
			'has_admin_page'      => true,
			'visible_in_settings' => true,
			'hidden_in_settings'  => false,
		);
	}

	/**
	 * Initialize hooks.
	 *
	 * @since  2.0.0
	 */
	public function init_hooks() {
		// Only initialize if user has premium access
		if ( ! function_exists( 'aiohm_booking_fs' ) || ! aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
			return;
		}

		// Initialize Stripe functionality here
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_stripe_scripts' ) );
		add_action( 'wp_ajax_process_stripe_payment', array( $this, 'process_stripe_payment' ) );
		add_action( 'wp_ajax_nopriv_process_stripe_payment', array( $this, 'process_stripe_payment' ) );
	}

	/**
	 * Get settings fields.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'stripe_api_key' => array(
				'type'        => 'text',
				'label'       => __( 'Stripe API Key', 'aiohm-booking-pro' ),
				'description' => __( 'Enter your Stripe publishable key.', 'aiohm-booking-pro' ),
				'required'    => true,
			),
			'stripe_secret_key' => array(
				'type'        => 'password',
				'label'       => __( 'Stripe Secret Key', 'aiohm-booking-pro' ),
				'description' => __( 'Enter your Stripe secret key.', 'aiohm-booking-pro' ),
				'required'    => true,
			),
		);
	}

	/**
	 * Get default settings.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function get_default_settings() {
		return array(
			'stripe_api_key'    => '',
			'stripe_secret_key' => '',
		);
	}

	/**
	 * Enqueue Stripe scripts.
	 *
	 * @since  2.0.0
	 */
	public function enqueue_stripe_scripts() {
		wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), '3.0', true );
	}

	/**
	 * Process Stripe payment.
	 *
	 * @since  2.0.0
	 */
	public function process_stripe_payment() {
		// Premium Stripe payment processing logic would go here
		wp_die( esc_html__( 'Stripe payment processing available in premium version.', 'aiohm-booking-pro' ) );
	}
}
/* </fs_premium_only> */