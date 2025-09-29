<?php
/* <fs_premium_only> */
/**
 * Gemini AI Integration Module
 *
 * Provides Gemini AI-powered analytics and insights for AIOHM Booking system.
 * This module is only available for premium users.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking Gemini Module Class
 *
 * Provides Gemini AI integration for analytics and insights.
 * Premium only module.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Module_Gemini extends AIOHM_BOOKING_Module_Abstract {

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
			'id'                  => 'gemini',
			'name'                => __( 'Gemini AI Analytics', 'aiohm-booking-pro' ),
			'description'         => __( 'Advanced AI analytics using Google Gemini for booking insights.', 'aiohm-booking-pro' ),
			'icon'                => '✨',
			'category'            => 'ai',
			'access_level'        => 'premium',
			'is_premium'          => true,
			'priority'            => 20,
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

		// Initialize Gemini functionality here
		add_action( 'wp_ajax_get_gemini_insights', array( $this, 'get_gemini_insights' ) );
		add_action( 'aiohm_booking_weekly_analytics', array( $this, 'generate_weekly_analytics' ) );
	}

	/**
	 * Get settings fields.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'gemini_api_key' => array(
				'type'        => 'password',
				'label'       => __( 'Gemini API Key', 'aiohm-booking-pro' ),
				'description' => __( 'Enter your Google Gemini API key.', 'aiohm-booking-pro' ),
				'required'    => true,
			),
			'gemini_model' => array(
				'type'        => 'select',
				'label'       => __( 'Gemini Model', 'aiohm-booking-pro' ),
				'description' => __( 'Select the Gemini model to use.', 'aiohm-booking-pro' ),
				'options'     => array(
					'gemini-pro'    => 'Gemini Pro',
					'gemini-1.5-pro' => 'Gemini 1.5 Pro',
				),
				'default'     => 'gemini-pro',
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
			'gemini_api_key' => '',
			'gemini_model'   => 'gemini-pro',
		);
	}

	/**
	 * Get Gemini insights.
	 *
	 * @since  2.0.0
	 */
	public function get_gemini_insights() {
		// Premium Gemini analytics logic would go here
		wp_die( esc_html__( 'Gemini AI analytics available in premium version.', 'aiohm-booking-pro' ) );
	}

	/**
	 * Generate weekly analytics.
	 *
	 * @since  2.0.0
	 */
	public function generate_weekly_analytics() {
		// Premium weekly analytics generation would go here
	}
}
/* </fs_premium_only> */