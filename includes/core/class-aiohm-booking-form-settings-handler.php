<?php
/**
 * AIOHM Booking Form Settings Handler
 *
 * Centralized handler for form settings to avoid duplication
 * between accommodation and tickets modules.
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unified form settings handler
 */
class AIOHM_BOOKING_Form_Settings_Handler {

	/**
	 * Save unified form settings for both accommodation and tickets
	 *
	 * Handles form customization settings submission with proper security validation
	 * and cross-module synchronization between tickets and accommodations.
	 *
	 * @since 2.0.3
	 * @return void
	 */
	public static function save_unified_form_settings() {
		// Verify nonce for security - use security helper for AJAX requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// For AJAX requests, use the security helper
			if ( ! AIOHM_BOOKING_Security_Helper::verify_ajax_nonce( 'save_form_settings', 'aiohm_form_settings_nonce' ) ) {
				return; // Error response already sent by security helper
			}
		} else {
			// For non-AJAX requests, verify nonce manually
			$nonce_verified = false;
			$possible_nonce_fields = array( 'aiohm_form_settings_nonce', '_wpnonce' );
			foreach ( $possible_nonce_fields as $nonce_field ) {
				if ( isset( $_POST[ $nonce_field ] ) ) {
					$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) );
					// Check for both possible nonce actions for backwards compatibility
					$check1 = wp_verify_nonce( $nonce, 'aiohm_booking_save_form_settings' );
					$check2 = wp_verify_nonce( $nonce, 'aiohm_booking_form_settings' );
					
					if ( $check1 || $check2 ) {
						$nonce_verified = true;
						break;
					}
				}
			}
			
			if ( ! $nonce_verified ) {
				wp_die( esc_html__( 'Security check failed.', 'aiohm-booking-pro' ) );
			}
		}

		// Extract the option name and form type from the POST data.
		$option_name = sanitize_text_field( wp_unslash( $_POST['option_name'] ?? 'aiohm_booking_form_settings' ) );
		$form_type   = sanitize_text_field( wp_unslash( $_POST['form_type'] ?? 'accommodations' ) );

		$form_settings = array();
		if ( isset( $_POST[ $option_name ] ) && is_array( $_POST[ $option_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$submitted_data = wp_unslash( $_POST[ $option_name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// Sanitize form settings.
			$form_settings = self::sanitize_form_settings( $submitted_data );
		} else {
			// Fallback: collect individual POST parameters with bracket notation
			$form_settings = self::collect_individual_form_fields( $option_name );
		}

		// Handle field order - it can be in the main option array or as a separate POST param.
		$field_order_set = false;

		// Check if field_order is in the main option array (as an array - already processed by sanitize).
		if ( isset( $form_settings['field_order'] ) && is_array( $form_settings['field_order'] ) ) {
			$field_order_set = true;
		}

		// Fallback: check if field_order is a separate POST param.
		if ( ! $field_order_set && isset( $_POST['field_order'] ) && is_array( $_POST['field_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$field_order                  = array_map( 'sanitize_text_field', wp_unslash( $_POST['field_order'] ) );
			$form_settings['field_order'] = $field_order;
		}

		// Update the appropriate option using the sync-enabled method.
		$form_type_for_sync = ( 'aiohm_booking_tickets_form_settings' === $option_name ) ? 'tickets' : 'accommodations';
		$updated            = self::update_form_settings( $form_type_for_sync, $form_settings );

		// Also save brand color to main settings for unified color across all contexts
		if ( isset( $form_settings['form_primary_color'] ) && ! empty( $form_settings['form_primary_color'] ) ) {
			$main_settings                = get_option( 'aiohm_booking_settings', array() );
			$main_settings['brand_color'] = $form_settings['form_primary_color'];
			update_option( 'aiohm_booking_settings', $main_settings );
		}

		// Clear any related caches
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		if ( $updated ) {
			// Prepare success response.
			$field_order_text = isset( $form_settings['field_order'] )
				? implode( ', ', $form_settings['field_order'] )
				: 'not set';

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				wp_send_json_success(
					array(
						'message'     => 'Form settings saved successfully! Field order: ' . $field_order_text,
						'settings'    => $form_settings,
						'option_name' => $option_name,
					)
				);
			} else {
				// Non-AJAX context.
				add_action(
					'admin_notices',
					function () use ( $field_order_text ) {
						echo '<div class="notice notice-success is-dismissible"><p>Form settings saved successfully! Field order: ' . esc_html( $field_order_text ) . '</p></div>';
					}
				);
			}
		} elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				wp_send_json_error(
					array(
						'message' => 'Failed to save form settings. Please try again.',
					)
				);
		} else {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible"><p>Failed to save form settings. Please try again.</p></div>';
				}
			);
		}
	}

	/**
	 * Collect form fields from individual POST parameters
	 *
	 * @param string $option_name The option name prefix.
	 * @return array Collected form settings
	 */
	private static function collect_individual_form_fields( $option_name ) {
		$form_settings = array();

		// Get all POST data
		$post_data = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Look for parameters that start with the option name
		$prefix        = $option_name . '[';
		$prefix_length = strlen( $prefix );

		foreach ( $post_data as $key => $value ) {
			if ( strpos( $key, $prefix ) === 0 && substr( $key, -1 ) === ']' ) {
				// Extract the field name from between the brackets
				$field_name                   = substr( $key, $prefix_length, -1 );
				$form_settings[ $field_name ] = $value;
			}
		}

		// Sanitize the collected settings
		return self::sanitize_form_settings( $form_settings );
	}

	/**
	 * Sanitize form settings array
	 *
	 * @param array $settings Raw form settings.
	 * @return array Sanitized settings
	 */
	private static function sanitize_form_settings( $settings ) {
		$sanitized = array();

		// Define expected fields and their types.
		$field_definitions = array(
			'form_primary_color'                       => 'color',
			'form_text_color'                          => 'color',
			'form_field_name'                          => 'checkbox',
			'form_field_name_required'                 => 'checkbox',
			'form_field_email'                         => 'checkbox',
			'form_field_email_required'                => 'checkbox',
			'form_field_phone'                         => 'checkbox',
			'form_field_phone_required'                => 'checkbox',
			'form_field_company'                       => 'checkbox',
			'form_field_company_required'              => 'checkbox',
			'form_field_address'                       => 'checkbox',
			'form_field_address_required'              => 'checkbox',
			'form_field_country'                       => 'checkbox',
			'form_field_country_required'              => 'checkbox',
			'form_field_age'                           => 'checkbox',
			'form_field_age_required'                  => 'checkbox',
			'form_field_arrival_time'                  => 'checkbox',
			'form_field_arrival_time_required'         => 'checkbox',
			'form_field_arrival_date'                  => 'checkbox',
			'form_field_arrival_date_required'         => 'checkbox',
			'form_field_departure_date'                => 'checkbox',
			'form_field_departure_date_required'       => 'checkbox',
			'form_field_purpose'                       => 'checkbox',
			'form_field_purpose_required'              => 'checkbox',
			'form_field_vat'                           => 'checkbox',
			'form_field_vat_required'                  => 'checkbox',
			'form_field_dietary_requirements'          => 'checkbox',
			'form_field_dietary_requirements_required' => 'checkbox',
			'form_field_accessibility_needs'           => 'checkbox',
			'form_field_accessibility_needs_required'  => 'checkbox',
			'form_field_emergency_contact'             => 'checkbox',
			'form_field_emergency_contact_required'    => 'checkbox',
			'form_field_special_requests'              => 'checkbox',
			'form_field_special_requests_required'     => 'checkbox',
			'form_field_departure_time'                => 'checkbox',
			'form_field_departure_time_required'       => 'checkbox',
			'form_field_nationality'                   => 'checkbox',
			'form_field_nationality_required'          => 'checkbox',
			'field_order'                              => 'array',
			'allow_group_bookings'                     => 'checkbox',
			'allow_private_all'                        => 'checkbox',
			'thankyou_page_url'                        => 'text',
		);

		foreach ( $settings as $key => $value ) {
			if ( isset( $field_definitions[ $key ] ) ) {
				$type = $field_definitions[ $key ];

				switch ( $type ) {
					case 'text':
						$sanitized[ $key ] = sanitize_text_field( $value );
						break;
					case 'color':
						$sanitized[ $key ] = sanitize_hex_color( $value );
						break;
					case 'checkbox':
						$sanitized[ $key ] = ! empty( $value ) ? '1' : '';
						break;
					case 'array':
						if ( $key === 'field_order' && is_string( $value ) ) {
							// Handle field_order as comma-separated string
							$sanitized[ $key ] = ! empty( $value ) ? explode( ',', $value ) : array();
							$sanitized[ $key ] = array_map( 'sanitize_text_field', $sanitized[ $key ] );
						} else {
							$sanitized[ $key ] = is_array( $value )
								? array_map( 'sanitize_text_field', $value )
								: array();
						}
						break;
					default:
						$sanitized[ $key ] = sanitize_text_field( $value );
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Get form settings for a specific type
	 *
	 * @param string $form_type Type of form ('accommodations', 'tickets').
	 * @return array Form settings
	 */
	public static function get_form_settings( $form_type = 'accommodations' ) {
		$option_name = 'tickets' === $form_type
			? 'aiohm_booking_tickets_form_settings'
			: 'aiohm_booking_form_settings';

		return get_option( $option_name, array() );
	}

	/**
	 * Update form settings for a specific type
	 *
	 * @param string $form_type Type of form ('accommodations', 'tickets').
	 * @param array  $settings Settings to save.
	 * @return bool Success status
	 */
	public static function update_form_settings( $form_type, $settings ) {
		$option_name = 'tickets' === $form_type
			? 'aiohm_booking_tickets_form_settings'
			: 'aiohm_booking_form_settings';

		$sanitized_settings = self::sanitize_form_settings( $settings );

		// Set brand_color and font_color from form_primary_color and form_text_color for consistency
		// This ensures templates can use either field naming convention
		if ( isset( $sanitized_settings['form_primary_color'] ) && ! empty( $sanitized_settings['form_primary_color'] ) ) {
			$sanitized_settings['brand_color'] = $sanitized_settings['form_primary_color'];
		}
		if ( isset( $sanitized_settings['form_text_color'] ) && ! empty( $sanitized_settings['form_text_color'] ) ) {
			$sanitized_settings['font_color'] = $sanitized_settings['form_text_color'];
		}

		// Synchronize form field settings between accommodation and tickets if both modules are enabled
		$sanitized_settings = self::sync_form_field_settings( $form_type, $sanitized_settings );

		// Also update main settings with brand colors for unified templates
		if ( isset( $sanitized_settings['brand_color'] ) && ! empty( $sanitized_settings['brand_color'] ) ) {
			$main_settings                       = get_option( 'aiohm_booking_settings', array() );
			$main_settings['brand_color']        = $sanitized_settings['brand_color'];
			$main_settings['form_primary_color'] = $sanitized_settings['brand_color']; // Keep both for compatibility
			update_option( 'aiohm_booking_settings', $main_settings );
		}
		if ( isset( $sanitized_settings['font_color'] ) && ! empty( $sanitized_settings['font_color'] ) ) {
			$main_settings                    = get_option( 'aiohm_booking_settings', array() );
			$main_settings['font_color']      = $sanitized_settings['font_color'];
			$main_settings['form_text_color'] = $sanitized_settings['font_color']; // Keep both for compatibility
			update_option( 'aiohm_booking_settings', $main_settings );
		}

		$result = update_option( $option_name, $sanitized_settings );
		
		// Handle AJAX response
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( $result ) {
				wp_send_json_success( array( 'message' => 'Settings saved successfully!' ) );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to save settings.' ) );
			}
		}
		
		return $result;
	}

	/**
	 * Synchronize form field settings between accommodation and tickets
	 *
	 * @param string $updated_form_type The form type being updated ('accommodations' or 'tickets').
	 * @param array  $settings The sanitized settings being saved.
	 * @return array The settings with synchronized field configurations.
	 */
	private static function sync_form_field_settings( $updated_form_type, $settings ) {
		// Check if both modules are enabled
		$main_settings          = get_option( 'aiohm_booking_settings', array() );
		$tickets_enabled        = ! empty( $main_settings['enable_tickets'] );
		$accommodations_enabled = ! empty( $main_settings['enable_accommodations'] );

		// Only sync if both modules are enabled
		if ( ! $tickets_enabled || ! $accommodations_enabled ) {
			return $settings;
		}

		// Define the field keys that should be synchronized
		$sync_field_keys = array(
			'form_primary_color',
			'form_text_color',
			'brand_color',
			'font_color',
			'thankyou_page_url',
			'checkout_page_url',
			'allow_group_bookings',
			'form_field_name',
			'form_field_name_required',
			'form_field_email',
			'form_field_email_required',
			'form_field_phone',
			'form_field_phone_required',
			'form_field_company',
			'form_field_company_required',
			'form_field_address',
			'form_field_address_required',
			'form_field_country',
			'form_field_country_required',
			'form_field_age',
			'form_field_age_required',
			'form_field_arrival_time',
			'form_field_arrival_time_required',
			'form_field_departure_time',
			'form_field_departure_time_required',
			'form_field_purpose',
			'form_field_purpose_required',
			'form_field_vat',
			'form_field_vat_required',
			'form_field_dietary_requirements',
			'form_field_dietary_requirements_required',
			'form_field_accessibility_needs',
			'form_field_accessibility_needs_required',
			'form_field_emergency_contact',
			'form_field_emergency_contact_required',
			'form_field_special_requests',
			'form_field_special_requests_required',
			'form_field_departure_time',
			'form_field_departure_time_required',
			'form_field_nationality',
			'form_field_nationality_required',
			'form_field_arrival_date',
			'form_field_arrival_date_required',
			'form_field_departure_date',
			'form_field_departure_date_required',
		);

		// Get the other form's settings
		$other_form_type   = ( 'accommodations' === $updated_form_type ) ? 'tickets' : 'accommodations';
		$other_option_name = ( 'tickets' === $other_form_type ) ? 'aiohm_booking_tickets_form_settings' : 'aiohm_booking_form_settings';
		$other_settings    = get_option( $other_option_name, array() );

		// Sync field settings from the updated form to the other form
		$updated_other_settings = $other_settings;
		$sync_count             = 0;
		foreach ( $sync_field_keys as $field_key ) {
			if ( isset( $settings[ $field_key ] ) ) {
				$old_value = $updated_other_settings[ $field_key ] ?? 'not_set';
				$new_value = $settings[ $field_key ];
				if ( $old_value !== $new_value ) {
					$updated_other_settings[ $field_key ] = $new_value;
					++$sync_count;
				}
			}
		}

		// Also sync field order if it exists
		if ( isset( $settings['field_order'] ) ) {
			$updated_other_settings['field_order'] = $settings['field_order'];
		}

		// Save the synchronized settings to the other form
		if ( $sync_count > 0 ) {
			$save_result = update_option( $other_option_name, $updated_other_settings );
		}

		return $settings;
	}
}
