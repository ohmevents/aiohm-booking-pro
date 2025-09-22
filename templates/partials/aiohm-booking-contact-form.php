<?php
/**
 * Contact Information Card - Modular Component
 * Displays dynamic contact form fields based on user configuration
 * Fields are configured in Event Tickets Booking Form Customization
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Use the context and settings that are already determined in the main template.
// These variables are set in aiohm-booking-sandwich-template.php:
// - $is_events_context (boolean)
// - $form_settings (array) - now context-specific
// - $opts (array)

// If not set (defensive programming), determine them here.
if ( ! isset( $is_events_context ) ) {
	global $aiohm_booking_events_context;
	$is_events_context = isset( $aiohm_booking_events_context );
}

if ( ! isset( $form_settings ) ) {
	if ( $is_events_context ) {
		$form_settings = get_option( 'aiohm_booking_tickets_form_settings', array() );
	} else {
		$form_settings = get_option( 'aiohm_booking_form_settings', array() );
	}
}

if ( ! isset( $opts ) ) {
	$opts = get_option( 'aiohm_booking_settings', array() );
}

/**
 * Get the appropriate input type for a field
 */
function get_field_input_type( $field_key ) {
	$type_map = array(
		'phone'                => 'tel',
		'age'                  => 'number',
		'arrival_time'         => 'time',
		'departure_time'       => 'time',
		'purpose'              => 'select',
		'dietary_requirements' => 'textarea',
		'accessibility_needs'  => 'textarea',
		'special_requests'     => 'textarea',
		'emergency_contact'    => 'text',
	);

	return $type_map[ $field_key ] ?? 'text';
}

/**
 * Get the appropriate placeholder for a field
 */
function get_field_placeholder( $field_key ) {
	$placeholder_map = array(
		'address'              => __( 'Enter your full address', 'aiohm-booking-pro' ),
		'age'                  => __( 'Enter your age', 'aiohm-booking-pro' ),
		'company'              => __( 'Enter company name', 'aiohm-booking-pro' ),
		'country'              => __( 'Enter your country', 'aiohm-booking-pro' ),
		'phone'                => __( 'Enter your phone number', 'aiohm-booking-pro' ),
		'arrival_time'         => __( 'Expected arrival time', 'aiohm-booking-pro' ),
		'departure_time'       => __( 'Expected departure time', 'aiohm-booking-pro' ),
		'purpose'              => __( 'Select purpose', 'aiohm-booking-pro' ),
		'vat'                  => __( 'Enter VAT number (if applicable)', 'aiohm-booking-pro' ),
		'nationality'          => __( 'Enter your nationality', 'aiohm-booking-pro' ),
		'dietary_requirements' => __( 'Please specify any dietary requirements or food allergies', 'aiohm-booking-pro' ),
		'accessibility_needs'  => __( 'Please describe any accessibility requirements', 'aiohm-booking-pro' ),
		'emergency_contact'    => __( 'Emergency contact name and phone number', 'aiohm-booking-pro' ),
		'special_requests'     => __( 'Any special requests or additional information', 'aiohm-booking-pro' ),
	);

	return $placeholder_map[ $field_key ] ?? __( 'Enter value', 'aiohm-booking-pro' );
}

/**
 * Get the appropriate layout for a field
 */
function get_field_layout( $field_key ) {
	$layout_map = array(
		'address'              => 'full',
		'country'              => 'full',
		'phone'                => 'full',
		'dietary_requirements' => 'full',
		'accessibility_needs'  => 'full',
		'emergency_contact'    => 'full',
		'special_requests'     => 'full',
		// Half-width fields
		'age'                  => 'half',
		'company'              => 'half',
		'arrival_time'         => 'half',
		'departure_time'       => 'half',
		'purpose'              => 'half',
		'vat'                  => 'half',
		'nationality'          => 'half',
	);

	return $layout_map[ $field_key ] ?? 'full';
}
?>

<div class="aiohm-contact-form-card">
	<div class="aiohm-booking-shortcode-card-header">
		<div class="aiohm-card-icon">👤</div>
	</div>

	<div class="aiohm-contact-form-container">
		<!-- Core Required Fields (Name & Email) -->
		<div class="aiohm-contact-core-fields">
			<?php
			// Name and Email are ALWAYS required regardless of settings
			$show_name_field  = true; // Always show name field
			$show_email_field = true; // Always show email field
			?>

			<?php if ( $show_name_field ) : ?>
				<div class="aiohm-contact-field aiohm-field-full">
					<label class="aiohm-contact-label" for="customer_name">
						<?php esc_html_e( 'Full Name', 'aiohm-booking-pro' ); ?>
						<span class="aiohm-required-indicator">*</span>
					</label>
					<input type="text" 
							name="name" 
							id="customer_name"
							class="aiohm-contact-input" 
							placeholder="<?php esc_attr_e( 'Enter your full name', 'aiohm-booking-pro' ); ?>"
							required
							data-field="name">
					<p class="aiohm-contact-help"><?php esc_html_e( 'Please enter your full name as it appears on official documents.', 'aiohm-booking-pro' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $show_email_field ) : ?>
				<div class="aiohm-contact-field aiohm-field-full">
					<label class="aiohm-contact-label" for="customer_email">
						<?php esc_html_e( 'Email Address', 'aiohm-booking-pro' ); ?>
						<span class="aiohm-required-indicator">*</span>
					</label>
					<input type="email" 
							name="email" 
							id="customer_email"
							class="aiohm-contact-input" 
							placeholder="<?php esc_attr_e( 'Enter your email address', 'aiohm-booking-pro' ); ?>"
							required
							data-field="email">
					<p class="aiohm-contact-help"><?php esc_html_e( 'We will send booking confirmations to this email address.', 'aiohm-booking-pro' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<!-- Dynamic Additional Fields -->
		<div class="aiohm-contact-additional-fields">
			<?php
			// Get centralized field definitions from accommodation module
			if ( class_exists( 'AIOHM_BOOKING_Module_Accommodation' ) ) {
				$accommodation_module = new AIOHM_BOOKING_Module_Accommodation();
				$field_definitions    = $accommodation_module->get_centralized_field_definitions( $is_events_context ? 'tickets' : 'accommodation' );
			} else {
				// Fallback if module not available
				$field_definitions = array();
			}

			// Get saved field order from form settings (consistent for both contexts)
			$field_order = $form_settings['field_order'] ?? array_keys( $field_definitions );

			if ( ! is_array( $field_order ) ) {
				$field_order = ! empty( $field_order ) ? explode( ',', $field_order ) : array_keys( $field_definitions );
			}

			// Build dynamic field configurations based on settings
			$additional_fields = array();
			foreach ( $field_definitions as $field_key => $field_info ) {
				// Check if field is enabled in settings
				$field_enabled  = false;
				$field_required = false;

				if ( $is_events_context ) {
					// For events, check tickets form settings
					$field_enabled  = ! empty( $form_settings[ 'form_field_' . $field_key ] );
					$field_required = ! empty( $form_settings[ 'form_field_' . $field_key . '_required' ] );
				} else {
					// For accommodation, check form settings (not main settings)
					$field_enabled  = ! empty( $form_settings[ 'form_field_' . $field_key ] );
					$field_required = ! empty( $form_settings[ 'form_field_' . $field_key . '_required' ] );
				}

				if ( $field_enabled ) {
					$additional_fields[ $field_key ] = array(
						'enabled'     => true,
						'required'    => $field_required,
						'label'       => $field_info['label'],
						'type'        => get_field_input_type( $field_key ),
						'placeholder' => get_field_placeholder( $field_key ),
						'layout'      => get_field_layout( $field_key ),
						'help'        => $field_info['description'],
					);

					// Add field-specific attributes
					if ( $field_key === 'age' ) {
						$additional_fields[ $field_key ]['min'] = 1;
						$additional_fields[ $field_key ]['max'] = 120;
					}

					if ( $field_key === 'purpose' ) {
						$additional_fields[ $field_key ]['options'] = array(
							''           => __( 'Select purpose...', 'aiohm-booking-pro' ),
							'leisure'    => __( 'Leisure/Vacation', 'aiohm-booking-pro' ),
							'business'   => __( 'Business', 'aiohm-booking-pro' ),
							'conference' => __( 'Conference/Event', 'aiohm-booking-pro' ),
							'family'     => __( 'Family Visit', 'aiohm-booking-pro' ),
							'other'      => __( 'Other', 'aiohm-booking-pro' ),
						);
					}
				}
			}

			// Filter field order to only include enabled fields
			$enabled_field_order = array();
			foreach ( $field_order as $field_name ) {
				if ( isset( $additional_fields[ $field_name ] ) && $additional_fields[ $field_name ]['enabled'] ) {
					$enabled_field_order[] = $field_name;
				}
			}

			// Use filtered field order, fallback to enabled fields in definition order if empty
			if ( ! empty( $enabled_field_order ) ) {
				$field_order = $enabled_field_order;
			} else {
				$field_order = array_keys( $additional_fields );
			}

			// Render fields in configured order
			$current_row_fields = array();

			foreach ( $field_order as $field_name ) {
				if ( ! isset( $additional_fields[ $field_name ] ) ) {
					continue;
				}

				$field = $additional_fields[ $field_name ];

				// Skip if field is not enabled
				if ( ! $field['enabled'] ) {
					continue;
				}

				// Handle row layout for half-width fields
				if ( $field['layout'] === 'half' ) {
					$current_row_fields[] = array(
						'name'   => $field_name,
						'config' => $field,
					);

					// If we have 2 half fields or this is the last field, render the row
					if ( count( $current_row_fields ) === 2 || $field_name === end( $field_order ) ) {
						echo '<div class="aiohm-contact-field-row">';

						foreach ( $current_row_fields as $row_field ) {
							render_contact_field( $row_field['name'], $row_field['config'] );
						}

						echo '</div>';
						$current_row_fields = array();
					}
				} else {
					// Full width field - render immediately
					// If there's a pending half field, render it first
					if ( ! empty( $current_row_fields ) ) {
						echo '<div class="aiohm-contact-field-row">';
						foreach ( $current_row_fields as $row_field ) {
							render_contact_field( $row_field['name'], $row_field['config'] );
						}
						echo '</div>';
						$current_row_fields = array();
					}

					render_contact_field( $field_name, $field );
				}
			}

			// Handle any remaining half fields
			if ( ! empty( $current_row_fields ) ) {
				echo '<div class="aiohm-contact-field-row">';
				foreach ( $current_row_fields as $row_field ) {
					render_contact_field( $row_field['name'], $row_field['config'] );
				}
				echo '</div>';
			}
			?>
		</div>

		<!-- Special Additional Fields (handled separately in backup) -->
		<div class="aiohm-contact-special-fields">
			<?php
						// Date Fields for Accommodation (only show for accommodation context).
			if ( ! $is_events_context && ( ! empty( $form_settings['form_field_arrival_date'] ) || ! empty( $form_settings['form_field_departure_date'] ) ) ) :
				?>
			<div class="aiohm-contact-field-row">
				<?php if ( ! empty( $form_settings['form_field_arrival_date'] ) ) : ?>
				<div class="aiohm-contact-field aiohm-field-half">
					<label class="aiohm-contact-label" for="customer_arrival_date">
						<?php esc_html_e( 'Arrival Date', 'aiohm-booking-pro' ); ?>
					</label>
					<input type="date"
							name="arrival_date"
							id="customer_arrival_date"
							class="aiohm-contact-input"
							min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
							data-field="arrival_date">
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $form_settings['form_field_departure_date'] ) ) : ?>
				<div class="aiohm-contact-field aiohm-field-half">
					<label class="aiohm-contact-label" for="customer_departure_date">
						<?php esc_html_e( 'Departure Date', 'aiohm-booking-pro' ); ?>
					</label>
					<input type="date"
							name="departure_date"
							id="customer_departure_date"
							class="aiohm-contact-input"
							min="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>"
							data-field="departure_date">
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
/**
 * Render individual contact field
 */
function render_contact_field( $field_name, $field_config ) {
	$field_id           = 'customer_' . $field_name;
	$layout_class       = $field_config['layout'] === 'half' ? 'aiohm-field-half' : 'aiohm-field-full';
	$required_attr      = ! isset( $GLOBALS['aiohm_booking_preview_mode'] ) && $field_config['required'] ? 'required' : '';
	$required_indicator = $field_config['required'] ? '<span class="aiohm-required-indicator">*</span>' : '';
	?>
	
	<div class="aiohm-contact-field <?php echo esc_attr( $layout_class ); ?>">
		<label class="aiohm-contact-label" for="<?php echo esc_attr( $field_id ); ?>">
			<?php echo esc_html( $field_config['label'] ); ?>
			<?php echo wp_kses_post( $required_indicator ); ?>
		</label>
		
		<?php if ( $field_config['type'] === 'select' ) : ?>
			<select name="<?php echo esc_attr( $field_name ); ?>" 
					id="<?php echo esc_attr( $field_id ); ?>"
					class="aiohm-contact-select" 
					<?php echo esc_attr( $required_attr ); ?>
					data-field="<?php echo esc_attr( $field_name ); ?>">
				<?php foreach ( $field_config['options'] as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			
		<?php elseif ( $field_config['type'] === 'textarea' ) : ?>
			<textarea name="<?php echo esc_attr( $field_name ); ?>" 
						id="<?php echo esc_attr( $field_id ); ?>"
						class="aiohm-contact-textarea" 
						placeholder="<?php echo esc_attr( $field_config['placeholder'] ); ?>"
						<?php echo esc_attr( $required_attr ); ?>
						data-field="<?php echo esc_attr( $field_name ); ?>"
						rows="3"></textarea>
					  
		<?php else : ?>
			<input type="<?php echo esc_attr( $field_config['type'] ); ?>" 
					name="<?php echo esc_attr( $field_name ); ?>" 
					id="<?php echo esc_attr( $field_id ); ?>"
					class="aiohm-contact-input" 
					placeholder="<?php echo esc_attr( $field_config['placeholder'] ); ?>"
					<?php echo esc_attr( $required_attr ); ?>
					<?php
					if ( isset( $field_config['min'] ) ) {
						echo 'min="' . esc_attr( $field_config['min'] ) . '"';}
					?>
					<?php
					if ( isset( $field_config['max'] ) ) {
						echo 'max="' . esc_attr( $field_config['max'] ) . '"';}
					?>
					data-field="<?php echo esc_attr( $field_name ); ?>">
		<?php endif; ?>
		
		<?php if ( ! empty( $field_config['help'] ) ) : ?>
			<p class="aiohm-contact-help"><?php echo esc_html( $field_config['help'] ); ?></p>
		<?php endif; ?>
	</div>
	
	<?php
}
?>