<?php
/**
 * Booking Form Partial Template
 *
 * Central form template used by all booking shortcodes.
 * Provides consistent form structure and behavior across events, accommodations, and mixed modes.
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Form data should be passed via extract() from the form handler.
$form_context          = $context ?? 'mixed';
$form_customization    = $customization ?? array();
$form_urls             = $urls ?? array();
$form_fields           = $fields ?? array();
$form_field_order      = $field_order ?? array();
$allow_private_booking = $allow_private ?? false;

// Get global settings for backward compatibility.
$global_settings = get_option( 'aiohm_booking_settings', array() );
?>

<!-- Contact Information Section -->
<div class="aiohm-form-section">
	<h3 class="aiohm-form-section-title"><?php esc_html_e( 'Contact Information', 'aiohm-booking-pro' ); ?></h3>
	
	<div class="aiohm-form-row">
		<div class="aiohm-form-group aiohm-form-group--half">
			<label for="customer_name" class="aiohm-form-label">
				<?php esc_html_e( 'Full Name', 'aiohm-booking-pro' ); ?> <span class="required">*</span>
			</label>
			<input type="text" id="customer_name" name="customer_name" class="aiohm-form-input" required 
					placeholder="<?php esc_attr_e( 'Enter your full name', 'aiohm-booking-pro' ); ?>">
		</div>
		
		<div class="aiohm-form-group aiohm-form-group--half">
			<label for="customer_email" class="aiohm-form-label">
				<?php esc_html_e( 'Email Address', 'aiohm-booking-pro' ); ?> <span class="required">*</span>
			</label>
			<input type="email" id="customer_email" name="customer_email" class="aiohm-form-input" required 
					placeholder="<?php esc_attr_e( 'Enter your email address', 'aiohm-booking-pro' ); ?>">
		</div>
	</div>
</div>

<!-- Additional Contact Fields - Dynamic Order -->
<?php if ( empty( $form_fields ) !== true ) : ?>
<div class="aiohm-form-section">
	<h3 class="aiohm-form-section-title"><?php esc_html_e( 'Additional Information', 'aiohm-booking-pro' ); ?></h3>
	
	<?php
	// Process fields in the specified order.
	$current_row_fields = array();

	foreach ( $form_field_order as $field_key ) {
		if ( ! isset( $form_fields[ $field_key ] ) || true !== $form_fields[ $field_key ]['enabled'] ) {
			continue;
		}

		$field        = $form_fields[ $field_key ];
		$field_layout = $field['layout'] ?? 'full';

		// If this is a full-width field or we have accumulated half-width fields, render the row.
		if ( 'full' === $field_layout && empty( $current_row_fields ) !== true ) {
			// Render accumulated half-width fields first.
			echo '<div class="aiohm-form-row">';
			foreach ( $current_row_fields as $row_field ) {
				render_form_field( $row_field['key'], $row_field['field'] );
			}
			echo '</div>';
			$current_row_fields = array();
		}

		if ( 'full' === $field_layout ) {
			// Render full-width field immediately.
			echo '<div class="aiohm-form-row">';
			render_form_field( $field_key, $field );
			echo '</div>';
		} else {
			// Accumulate half-width fields.
			$current_row_fields[] = array(
				'key'   => $field_key,
				'field' => $field,
			);

			// If we have 2 half-width fields, render the row.
			if ( 2 <= count( $current_row_fields ) ) {
				echo '<div class="aiohm-form-row">';
				foreach ( $current_row_fields as $row_field ) {
					render_form_field( $row_field['key'], $row_field['field'] );
				}
				echo '</div>';
				$current_row_fields = array();
			}
		}
	}

	// Render any remaining half-width fields.
	if ( empty( $current_row_fields ) !== true ) {
		echo '<div class="aiohm-form-row">';
		foreach ( $current_row_fields as $row_field ) {
			render_form_field( $row_field['key'], $row_field['field'] );
		}
		echo '</div>';
	}

	/**
	 * Render a single form field.
	 *
	 * @param string $field_key The field key/name.
	 * @param array  $field     The field configuration.
	 */
	function render_form_field( $field_key, $field ) {
		$field_layout      = $field['layout'] ?? 'full';
		$field_type        = $field['type'] ?? 'text';
		$field_label       = $field['label'] ?? ucfirst( $field_key );
		$field_placeholder = $field['placeholder'] ?? '';
		$field_required    = $field['required'] ?? false;

		$group_class = 'half' === $field_layout ? 'aiohm-form-group--half' : 'aiohm-form-group--full';
		$field_name  = 'form_field_' . $field_key;
		$field_id    = 'form_field_' . $field_key;
		?>
		<div class="aiohm-form-group <?php echo esc_attr( $group_class ); ?>">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="aiohm-form-label">
				<?php echo esc_html( $field_label ); ?>
				<?php if ( $field_required ) : ?>
					<span class="required">*</span>
				<?php endif; ?>
			</label>
			
			<?php if ( 'textarea' === $field_type ) : ?>
				<textarea id="<?php echo esc_attr( $field_id ); ?>" 
							name="<?php echo esc_attr( $field_name ); ?>" 
							class="aiohm-form-input aiohm-form-textarea"
							<?php
							if ( true === $field_required ) :
								?>
								required<?php endif; ?>
							placeholder="<?php echo esc_attr( $field_placeholder ); ?>"
							rows="3"></textarea>
				<?php
			elseif ( 'select' === $field_type && isset( $field['options'] ) ) :
				?>
				<select id="<?php echo esc_attr( $field_id ); ?>" 
						name="<?php echo esc_attr( $field_name ); ?>" 
						class="aiohm-form-input aiohm-form-select"
						<?php
						if ( true === $field_required ) :
							?>
							required<?php endif; ?>>
					<option value=""><?php echo esc_html( ! empty( $field_placeholder ) ? $field_placeholder : __( 'Please select...', 'aiohm-booking-pro' ) ); ?></option>
					<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_value ); ?>"><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input type="<?php echo esc_attr( $field_type ); ?>" 
						id="<?php echo esc_attr( $field_id ); ?>" 
						name="<?php echo esc_attr( $field_name ); ?>" 
						class="aiohm-form-input"
						<?php echo esc_attr( $field_required ? 'required' : '' ); ?>
						placeholder="<?php echo esc_attr( $field_placeholder ); ?>"
						<?php if ( 'number' === $field_type && isset( $field['min'] ) ) : ?>
								min="<?php echo esc_attr( $field['min'] ); ?>"
						<?php endif; ?>
						<?php if ( 'number' === $field_type && isset( $field['max'] ) ) : ?>
								max="<?php echo esc_attr( $field['max'] ); ?>"
						<?php endif; ?>>
			<?php endif; ?>
		</div>
		<?php
	}
	?>
</div>
<?php endif; ?>

<!-- Privacy and Terms Section -->
<div class="aiohm-form-section">
	<div class="aiohm-form-row">
		<div class="aiohm-form-group aiohm-form-group--full">
			<label class="aiohm-checkbox-label">
				<input type="checkbox" name="privacy_accepted" class="aiohm-checkbox" required>
				<span class="aiohm-checkbox-custom"></span>
				<span class="aiohm-checkbox-text">
					<?php
					$privacy_text = $global_settings['privacy_policy_text'] ??
						__( 'I agree to the privacy policy and terms of service', 'aiohm-booking-pro' );
					echo esc_html( $privacy_text );
					?>
					<span class="required">*</span>
				</span>
			</label>
		</div>
	</div>
	
	<?php if ( ! empty( $global_settings['marketing_consent_enabled'] ) ) : ?>
	<div class="aiohm-form-row">
		<div class="aiohm-form-group aiohm-form-group--full">
			<label class="aiohm-checkbox-label">
				<input type="checkbox" name="marketing_consent" class="aiohm-checkbox">
				<span class="aiohm-checkbox-custom"></span>
				<span class="aiohm-checkbox-text">
					<?php
					$marketing_text = $global_settings['marketing_consent_text'] ??
						__( 'I would like to receive marketing communications', 'aiohm-booking-pro' );
					echo esc_html( $marketing_text );
					?>
				</span>
			</label>
		</div>
	</div>
	<?php endif; ?>
</div>

<!-- Private/Full Booking Option (Accommodations Only) -->
<?php if ( $allow_private_booking && 'events' !== $form_context ) : ?>
<div class="aiohm-form-section">
	<div class="aiohm-form-row">
		<div class="aiohm-form-group aiohm-form-group--full">
			<label class="aiohm-checkbox-label aiohm-private-booking-option">
				<input type="checkbox" name="private_booking" class="aiohm-checkbox aiohm-private-booking-checkbox">
				<span class="aiohm-checkbox-custom"></span>
				<span class="aiohm-checkbox-text">
					<?php esc_html_e( 'Book All Accommodations (Exclusive Booking)', 'aiohm-booking-pro' ); ?>
					<small class="aiohm-field-description">
						<?php esc_html_e( 'Reserve all accommodations exclusively for your group', 'aiohm-booking-pro' ); ?>
					</small>
				</span>
			</label>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Special Requests (Always Available) -->
<div class="aiohm-form-section">
	<div class="aiohm-form-row">
		<div class="aiohm-form-group aiohm-form-group--full">
			<label for="special_notes" class="aiohm-form-label">
				<?php esc_html_e( 'Special Requests or Notes', 'aiohm-booking-pro' ); ?>
			</label>
			<textarea id="special_notes" name="special_notes" class="aiohm-form-input aiohm-form-textarea" 
						placeholder="<?php esc_attr_e( 'Any additional requests or information...', 'aiohm-booking-pro' ); ?>" 
						rows="3"></textarea>
		</div>
	</div>
</div>

<!-- Hidden Fields -->
<input type="hidden" name="form_context" value="<?php echo esc_attr( $form_context ); ?>">
<input type="hidden" name="thankyou_url" value="<?php echo esc_url( $form_urls['thankyou_page_url'] ?? '' ); ?>">

<!-- Form Actions -->
<div class="aiohm-form-actions">
	<button type="submit" class="aiohm-btn aiohm-btn--primary aiohm-submit-btn">
		<span class="aiohm-btn-text">
			<?php if ( 'events' === $form_context ) : ?>
				<?php esc_html_e( 'Book Tickets', 'aiohm-booking-pro' ); ?>
			<?php elseif ( 'accommodations' === $form_context ) : ?>
				<?php esc_html_e( 'Book Accommodation', 'aiohm-booking-pro' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Complete Booking', 'aiohm-booking-pro' ); ?>
			<?php endif; ?>
		</span>
		<span class="aiohm-btn-loading" style="display: none;">
			<?php esc_html_e( 'Processing...', 'aiohm-booking-pro' ); ?>
		</span>
	</button>
	
	<div class="aiohm-form-security-notice">
		<small>
			<span class="dashicons dashicons-lock"></span>
			<?php esc_html_e( 'Your information is secure and encrypted', 'aiohm-booking-pro' ); ?>
		</small>
	</div>
</div>

<!-- Form Validation Messages Container -->
<div id="aiohm-form-messages" class="aiohm-form-messages" style="display: none;"></div>

<?php
// Enqueue booking form script
wp_enqueue_script(
	'aiohm-booking-form',
	AIOHM_BOOKING_URL . 'assets/js/booking-form.js',
	array(),
	AIOHM_BOOKING_VERSION,
	true
);
?>