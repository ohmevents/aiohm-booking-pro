<?php
/* <fs_premium_only> */
/**
 * OpenAI Integration Module
 *
 * Provides AI-powered analytics and insights for AIOHM Booking system.
 * This module is only available for premium users.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AIOHM Booking OpenAI Module Class
 *
 * Provides OpenAI integration for analytics and insights.
 * Premium only module.
 *
 * @since  2.0.0
 */
class AIOHM_BOOKING_Module_OpenAI extends AIOHM_BOOKING_Module_Abstract {

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
			'id'                  => 'openai',
			'name'                => __( 'OpenAI Analytics', 'aiohm-booking-pro' ),
			'description'         => __( 'AI-powered booking analytics and insights using OpenAI.', 'aiohm-booking-pro' ),
			'icon'                => '🤖',
			'category'            => 'ai',
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

		// Initialize OpenAI functionality here
		add_action( 'wp_ajax_get_ai_insights', array( $this, 'get_ai_insights' ) );
		add_action( 'aiohm_booking_daily_analytics', array( $this, 'generate_daily_analytics' ) );
	}

	/**
	 * Get settings fields.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function get_settings_fields() {
		return array(
			'openai_api_key' => array(
				'type'        => 'password',
				'label'       => __( 'OpenAI API Key', 'aiohm-booking-pro' ),
				'description' => __( 'Enter your OpenAI API key for AI analytics.', 'aiohm-booking-pro' ),
				'required'    => true,
			),
			'ai_model' => array(
				'type'        => 'select',
				'label'       => __( 'AI Model', 'aiohm-booking-pro' ),
				'description' => __( 'Select the OpenAI model to use.', 'aiohm-booking-pro' ),
				'options'     => array(
					'gpt-4'         => 'GPT-4',
					'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
				),
				'default'     => 'gpt-3.5-turbo',
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
			'openai_api_key' => '',
			'ai_model'       => 'gpt-3.5-turbo',
		);
	}

	/**
	 * Get AI insights.
	 *
	 * @since  2.0.0
	 */
	public function get_ai_insights() {
		// Premium AI analytics logic would go here
		wp_die( esc_html__( 'AI analytics available in premium version.', 'aiohm-booking-pro' ) );
	}

	/**
	 * Generate daily analytics.
	 *
	 * @since  2.0.0
	 */
	public function generate_daily_analytics() {
		// Premium daily analytics generation would go here
	}
}
/* </fs_premium_only> */