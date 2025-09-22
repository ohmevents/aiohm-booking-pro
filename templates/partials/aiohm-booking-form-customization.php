<?php
/**
 * Form Customization Template Partial - Original Structure Replica
 *
 * Provides form customization interface matching original sophisticated design
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Extract template variables.
$form_type           = $form_type ?? 'events';
$section_title       = $section_title ?? 'Event Booking Form Customization';
$section_description = $section_description ?? 'Customize the appearance and fields of your [aiohm_booking] shortcode form';

$form_data         = $form_data ?? array();
$fields_definition = $fields_definition ?? array();
$shortcode_preview = $shortcode_preview ?? '[aiohm_booking]';
$nonce_action      = $nonce_action ?? 'aiohm_booking_save_form_settings';
$nonce_name        = $nonce_name ?? 'aiohm_form_settings_nonce';
$option_name       = $option_name ?? 'aiohm_booking_settings';

// Prepare form data with defaults based on form type.
$default_data = array(
	'thankyou_page_url' => '',
	'allow_private_all' => false,
);

$data = array_merge( $default_data, $form_data );

// Ensure color data exists with proper fallbacks.
$data['brand_color'] = $data['form_primary_color'] ?? $data['brand_color'] ?? '#457d59';
$data['font_color']  = $data['form_text_color'] ?? $data['font_color'] ?? '#333333';
?>

<form method="post" action="" class="aiohm-form-customization-form aiohm-admin-form" id="aiohm-form-customization-<?php echo esc_attr( $form_type ); ?>" onsubmit="event.preventDefault(); return false;">
	<div class="aiohm-booking-admin-card">
		<div class="aiohm-booking-settings-header">
			<h3><?php echo esc_html( $section_title ); ?></h3>
			<p class="aiohm-booking-section-description"><?php echo esc_html( $section_description ); ?></p>
		</div>

		<div class="aiohm-form-customization-content">
			<?php wp_nonce_field( $nonce_action, $nonce_name ); ?>

			<!-- Hidden fields for form submission -->
			<input type="hidden" name="action" value="aiohm_save_form_settings" />
			<input type="hidden" name="form_type" value="<?php echo esc_attr( $form_type ); ?>" />
			<input type="hidden" name="option_name" value="<?php echo esc_attr( $option_name ); ?>" />

			<div class="aiohm-form-customization-two-column">
				<div class="aiohm-form-customization-left">
					<h4><?php esc_html_e( 'Form Content', 'aiohm-booking-pro' ); ?></h4>

					<!-- Color Settings - Inline Row -->
					<div class="aiohm-setting-row-inline">
						<div class="aiohm-setting-row">
							<label><?php esc_html_e( 'Brand Color', 'aiohm-booking-pro' ); ?> <small><?php esc_html_e( '(Change Main color for header and footer of booking forms)', 'aiohm-booking-pro' ); ?></small></label>
							<input type="color" class="form-color-input" data-field="primary" name="<?php echo esc_attr( $option_name ); ?>[form_primary_color]" value="<?php echo esc_attr( $data['brand_color'] ); ?>">
						</div>
						<div class="aiohm-setting-row">
							<label><?php esc_html_e( 'Font Color', 'aiohm-booking-pro' ); ?> <small><?php esc_html_e( '(Change Text color for different form elements)', 'aiohm-booking-pro' ); ?></small></label>
							<input type="color" class="form-color-input" data-field="text" name="<?php echo esc_attr( $option_name ); ?>[form_text_color]" value="<?php echo esc_attr( $data['font_color'] ); ?>">
						</div>
					</div>

					<div class="aiohm-setting-row">
						<label><?php esc_html_e( 'Thank You Page URL', 'aiohm-booking-pro' ); ?> <small><?php esc_html_e( '(page to redirect customers after successful payment. Use [aiohm_booking_success] shortcode on your thank you page)', 'aiohm-booking-pro' ); ?></small></label>
						<input type="url" name="<?php echo esc_attr( $option_name ); ?>[thankyou_page_url]" value="<?php echo esc_url( $data['thankyou_page_url'] ); ?>" placeholder="https://yoursite.com/thank-you">
					</div>

					<?php if ( 'accommodations' === $form_type ) : ?>
					<div class="aiohm-setting-row">
						<label><?php esc_html_e( 'Show "Book Entire Property" Option', 'aiohm-booking-pro' ); ?> <small><?php esc_html_e( '(Display option for guests to book the entire property exclusively)', 'aiohm-booking-pro' ); ?></small></label>
						<div class="aiohm-toggle-switch">
						<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>[allow_private_all]" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[allow_private_all]" id="allow_private_all_toggle" value="1" <?php checked( $data['allow_private_all'] ?? '0', '1' ); ?> class="toggle-input">
						<label for="allow_private_all_toggle" class="toggle-label">
							<span class="toggle-slider"></span>
						</label>
						<span class="toggle-text"><?php echo ( $data['allow_private_all'] ?? '0' ) === '1' ? esc_html__( 'Enabled', 'aiohm-booking-pro' ) : esc_html__( 'Disabled', 'aiohm-booking-pro' ); ?></span>
					</div>
				</div>
				<?php endif; ?>

					<?php if ( 'events' === $form_type || 'tickets' === $form_type ) : ?>
					<div class="aiohm-setting-row">
						<label><?php esc_html_e( 'Allow Multiple Event Bookings', 'aiohm-booking-pro' ); ?> <small><?php esc_html_e( '(Allow users to book tickets from 2 different events at the same time)', 'aiohm-booking-pro' ); ?></small></label>
						<div class="aiohm-toggle-switch">
						<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>[allow_group_bookings]" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[allow_group_bookings]" id="allow_group_bookings_toggle" value="1" <?php checked( $data['allow_group_bookings'] ?? '0', '1' ); ?> class="toggle-input">
						<label for="allow_group_bookings_toggle" class="toggle-label">
							<span class="toggle-slider"></span>
						</label>
						<span class="toggle-text"><?php echo ( $data['allow_group_bookings'] ?? '0' ) === '1' ? esc_html__( 'Enabled', 'aiohm-booking-pro' ) : esc_html__( 'Disabled', 'aiohm-booking-pro' ); ?></span>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $fields_definition ) ) : ?>
				<!-- Additional Contact Fields -->
				<div class="aiohm-fields-manager">
					<h4><?php esc_html_e( 'Additional Contact Fields', 'aiohm-booking-pro' ); ?> <span class="field-order-hint"><?php esc_html_e( 'Drag to reorder', 'aiohm-booking-pro' ); ?></span></h4>
					<p><?php echo ( 'events' === $form_type || 'tickets' === $form_type ) ? esc_html__( 'Activate additional fields for event bookings:', 'aiohm-booking-pro' ) : esc_html__( 'Activate additional fields for bookings:', 'aiohm-booking-pro' ); ?></p>
					
					<div class="aiohm-field-cards" id="sortable-fields">
						<?php
						$field_order = $data['field_order'] ?? array_keys( $fields_definition );
						if ( true !== is_array( $field_order ) ) {
							$field_order = empty( $field_order ) !== true ? explode( ',', $field_order ) : array_keys( $fields_definition );
						}

						// Always show all available fields, ordered by field_order preference
						$ordered_fields = array();

						// First, add fields in the saved order
						foreach ( $field_order as $field_key ) {
							if ( isset( $fields_definition[ $field_key ] ) ) {
								$ordered_fields[ $field_key ] = $fields_definition[ $field_key ];
							}
						}

						// Then add any remaining fields that weren't in the saved order
						foreach ( $fields_definition as $field_key => $field_info ) {
							if ( ! isset( $ordered_fields[ $field_key ] ) ) {
								$ordered_fields[ $field_key ] = $field_info;
							}
						}

						foreach ( $ordered_fields as $field_key => $field_info ) :
							$field_enabled  = $data[ 'form_field_' . $field_key ] ?? false;
							$field_required = $data[ 'form_field_' . $field_key . '_required' ] ?? false;
							?>
							<div class="aiohm-field-card" data-field="<?php echo esc_attr( $field_key ); ?>">
								<div class="field-drag-handle">
									<span class="dashicons dashicons-menu"></span>
								</div>
								<div class="field-info">
									<label class="field-label"><?php echo esc_html( $field_info['label'] ); ?></label>
									<span class="field-description"><?php echo esc_html( $field_info['description'] ); ?></span>
								</div>
								<div class="field-actions">
									<div class="field-status-badge">
										<span class="status-badge <?php echo esc_attr( $field_required ? 'required' : 'optional' ); ?>" 
												data-field="<?php echo esc_attr( $field_key ); ?>">
											<?php echo esc_html( $field_required ? 'REQUIRED' : 'OPTIONAL' ); ?>
										</span>
										<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>[form_field_<?php echo esc_attr( $field_key ); ?>_required]" value="<?php echo esc_attr( $field_required ? '1' : '0' ); ?>" class="required-field-input">
									</div>
									<div class="field-toggle-action">
										<span class="status-badge <?php echo esc_attr( $field_enabled ? 'added' : 'removed' ); ?>" 
												data-field="<?php echo esc_attr( $field_key ); ?>">
											<?php echo esc_html( $field_enabled ? 'ADDED' : 'REMOVED' ); ?>
										</span>
										<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>[form_field_<?php echo esc_attr( $field_key ); ?>]" value="<?php echo esc_attr( $field_enabled ? '1' : '0' ); ?>" class="field-visibility-input">
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Hidden field order input -->
					<input type="hidden" name="<?php echo esc_attr( $option_name ); ?>[field_order]" value="<?php echo esc_attr( implode( ',', array_keys( $ordered_fields ) ) ); ?>" id="field-order-input">
				</div>
				<?php endif; ?>
			</div>

			<div class="aiohm-form-customization-right">
				<?php if ( 'events' !== $form_type && 'tickets' !== $form_type ) : ?>
				<h4><?php esc_html_e( 'Live Form Preview', 'aiohm-booking-pro' ); ?></h4>
				<p class="aiohm-preview-subtitle"><?php esc_html_e( 'See your form changes in real-time as you customize fields and settings', 'aiohm-booking-pro' ); ?></p>

					<?php
					// Set preview mode to prevent form submission in admin
					$GLOBALS['aiohm_booking_preview_mode'] = true;

					// Set up the same context as the actual shortcode handler
					if ( strpos( $shortcode_preview, 'enable_accommodations="true"' ) !== false && strpos( $shortcode_preview, 'enable_tickets="false"' ) !== false ) {
						// Accommodations-only preview mode
						$GLOBALS['aiohm_booking_shortcode_override'] = array(
							'enable_accommodations' => true,
							'enable_tickets'        => false,
						);

						// Set accommodations context with default attributes
						global $aiohm_booking_accommodations_context;
						$aiohm_booking_accommodations_context = array(
							'style'       => 'compact',
							'button_text' => __( 'Book Now', 'aiohm-booking-pro' ),
							'show_prices' => 'true',
						);

						// Set shortcode type for template context
						$shortcode_type = 'accommodations';
					}

					// Render the actual shortcode
					echo do_shortcode( $shortcode_preview );

					// Clean up context
					if ( strpos( $shortcode_preview, 'enable_accommodations="true"' ) !== false && strpos( $shortcode_preview, 'enable_tickets="false"' ) !== false ) {
						unset( $GLOBALS['aiohm_booking_shortcode_override'] );
						unset( $GLOBALS['aiohm_booking_accommodations_context'] );
						unset( $shortcode_type );
					}

					// Unset preview mode
					unset( $GLOBALS['aiohm_booking_preview_mode'] );
					?>
				<?php else : ?>
				<h4><?php esc_html_e( 'Live Form Preview', 'aiohm-booking-pro' ); ?></h4>
				<p class="aiohm-preview-subtitle"><?php esc_html_e( 'See your event cards in real-time as you configure them below', 'aiohm-booking-pro' ); ?></p>

					<?php
					// Set preview mode to prevent form submission in admin
					$GLOBALS['aiohm_booking_preview_mode'] = true;

					// Set up context for events preview
					if ( strpos( $shortcode_preview, 'enable_tickets="true"' ) !== false && strpos( $shortcode_preview, 'enable_accommodations="false"' ) !== false ) {
						// Events-only preview mode
						$GLOBALS['aiohm_booking_shortcode_override'] = array(
							'enable_accommodations' => false,
							'enable_tickets'        => true,
						);

						// Set shortcode type for template context
						$shortcode_type = 'events';
					}

					// Render the actual shortcode
					echo do_shortcode( $shortcode_preview );

					// Clean up context
					if ( strpos( $shortcode_preview, 'enable_tickets="true"' ) !== false && strpos( $shortcode_preview, 'enable_accommodations="false"' ) !== false ) {
						unset( $GLOBALS['aiohm_booking_shortcode_override'] );
						unset( $shortcode_type );
					}

					// Unset preview mode
					unset( $GLOBALS['aiohm_booking_preview_mode'] );
					?>
				<?php endif; ?>
			</div>
		</div>

		<div class="aiohm-card-footer">
			<div class="aiohm-card-footer-actions">
				<button type="button" class="button button-primary aiohm-form-customization-save-btn" id="aiohm-form-customization-save-<?php echo esc_attr( $form_type ); ?>">
					<span class="dashicons dashicons-yes-alt"></span>
					<?php esc_html_e( 'Save Settings', 'aiohm-booking-pro' ); ?>
				</button>
			</div>
		</div>
	</div>
</form>