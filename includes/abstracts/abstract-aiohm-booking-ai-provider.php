<?php

namespace AIOHM_Booking_PRO\Abstracts;
/**
 * Abstract AI Provider class
 * Base class for all AI provider implementations
 *
 * @package AIOHM_Booking
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 * @license GPL-2.0+ https://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract AI Provider class
 *
 * @package AIOHM_Booking
 * @since 1.1.1
 */
abstract class AIOHM_BOOKING_AI_Provider_Abstract {


	/**
	 * Provider settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $provider_name = '';

	/**
	 * Constructor
	 *
	 * @param array|null $settings Provider settings.
	 */
	public function __construct( $settings = null ) {
		if ( null === $settings ) {
			$this->settings = \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Settings::get_all();
		} else {
			$this->settings = $settings;
		}
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	abstract public function get_provider_name();

	/**
	 * Get provider display name
	 *
	 * @return string
	 */
	abstract public function get_display_name();

	/**
	 * Check if API key/credentials are configured
	 *
	 * @return bool
	 */
	abstract public function is_configured();

	/**
	 * Test API connection
	 *
	 * @return bool
	 */
	abstract public function test_connection();

	/**
	 * Generate AI response
	 *
	 * @param  string $prompt The prompt to generate response for.
	 * @return array
	 */
	abstract public function generate_response( $prompt );

	/**
	 * Check if user has consented to external AI services
	 *
	 * @return bool
	 */
	public function has_user_consent() {
		return ! empty( $this->settings['ai_external_consent'] );
	}

	/**
	 * Check if provider requires external consent
	 *
	 * @return bool
	 */
	public function requires_external_consent() {
		return $this->get_provider_name() !== 'ollama';
	}

	/**
	 * Check rate limit for API calls
	 *
	 * @param  int $max_requests Maximum requests allowed.
	 * @return bool
	 */
	protected function check_rate_limit( $max_requests = 50 ) {
		$user_id  = get_current_user_id();
		$user_ip  = $this->get_client_ip();
		$provider = $this->get_provider_name();

		$user_key   = "aiohm_booking_rate_limit_{$provider}_user_{$user_id}";
		$user_count = get_transient( $user_key );

		$ip_key   = "aiohm_booking_rate_limit_{$provider}_ip_" . md5( $user_ip );
		$ip_count = get_transient( $ip_key );

		if ( false === $user_count ) {
			set_transient( $user_key, 1, HOUR_IN_SECONDS );
			$user_count = 1;
		}

		if ( false === $ip_count ) {
			set_transient( $ip_key, 1, HOUR_IN_SECONDS );
			$ip_count = 1;
		}

		if ( $user_count >= $max_requests || $ip_count >= $max_requests ) {
			return false;
		}

		set_transient( $user_key, $user_count + 1, HOUR_IN_SECONDS );
		set_transient( $ip_key, $ip_count + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Get client IP address securely
	 *
	 * @return string
	 */
	protected function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '127.0.0.1';
		return $ip;
	}

	/**
	 * Build context-aware prompt for booking assistance
	 *
	 * @param  string $user_prompt The user's prompt.
	 * @return string
	 */
	protected function build_booking_prompt( $user_prompt ) {
		$system_prompt = 'You are a helpful assistant for AIOHM Booking, a conscious business booking system. ' .
		'Help users with booking inquiries, accommodation availability, pricing questions, and event information. ' .
		'Be warm, professional, and aligned with conscious business values.';

		return $system_prompt . "\n\nUser inquiry: " . $user_prompt;
	}

	/**
	 * Build context-aware prompt for analytics insights
	 *
	 * @param  string $question       The question.
	 * @param  array  $analytics_data The analytics data.
	 * @return string
	 */
	protected function build_analytics_prompt( $question, $analytics_data ) {
		$system_prompt = 'You are a friendly sales and marketing consultant helping business owners understand their customer data and sales patterns. ' .
		'You work with all types of online businesses: accommodation rentals, event bookings, WooCommerce stores, membership sites, course sales, and service bookings. ' .
		"Explain sales data like you're talking to a business owner who wants to grow their revenue but isn't a data expert.\n\n" .

		"Your expertise covers:\n" .
		"- Sales trends and revenue patterns\n" .
		"- Customer behavior and buying patterns\n" .
		"- Lead conversion and sales funnels\n" .
		"- Seasonal trends and peak periods\n" .
		"- Customer lifetime value and repeat business\n" .
		"- Pricing strategies and revenue optimization\n" .
		"- Marketing opportunities and growth areas\n\n" .

		'Always provide practical, actionable advice that business owners can implement immediately. ' .
		'Use simple language, avoid technical terms, and focus on insights that directly impact revenue and customer satisfaction. ' .
		'When you see patterns, explain what they mean for the business and what actions to take.';

		$data_context = "BUSINESS SALES & CUSTOMER DATA:\n" . wp_json_encode( $analytics_data, JSON_PRETTY_PRINT );

		$full_prompt = $system_prompt . "\n\n" . $data_context . "\n\nBUSINESS OWNER QUESTION: " . $question . "\n\n" .
		"Provide insights that help grow the business. Focus on:\n" .
		"- What the numbers mean for revenue growth\n" .
		"- Customer patterns that reveal opportunities\n" .
		"- Specific actions to increase sales\n" .
		"- Marketing insights and recommendations\n" .
		"- Ways to improve customer experience and retention\n\n" .
		'Explain everything in simple terms with real business impact:';

		return $full_prompt;
	}

	/**
	 * Format API error response
	 *
	 * @param  string $message The error message.
	 * @return array
	 */
	protected function format_error( $message ) {
		return array(
			'success' => false,
			'error'   => $message,
		);
	}

	/**
	 * Format API success response
	 *
	 * @param  mixed       $response The API response data.
	 * @param  string|null $message  Optional success message.
	 * @return array
	 */
	protected function format_success( $response, $message = null ) {
		return array(
			'success'  => true,
			'response' => $response,
			'message'  => $message,
		);
	}

	/**
	 * Make HTTP request with common settings
	 *
	 * @param  string $url  The API endpoint URL.
	 * @param  array  $args Additional arguments for the request.
	 * @return array|\WP_Error
	 */
	protected function make_request( $url, $args = array() ) {
		$default_args = array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'AIOHM-Booking-Plugin/' . AIOHM_BOOKING_VERSION,
			),
		);

		$args = wp_parse_args( $args, $default_args );

		return wp_remote_post( $url, $args );
	}
}
