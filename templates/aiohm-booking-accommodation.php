<?php
/**
 * Accommodation Management Page Template
 *
 * This template provides the user interface for managing accommodations.
 * It uses the `aiohm_accommodation` Custom Post Type for data storage.
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// The variables used in this template are prepared by the AIOHM_BOOKING_Module_Accommodation class.
// Example variables passed from the class:.
// $settings, $global_settings, $accommodation_posts, $product_names, $currency.

$singular = $product_names['singular_cap'] ?? 'Accommodation';
$plural   = $product_names['plural_cap'] ?? 'Accommodations';
?>
<div class="wrap aiohm-booking-admin">
	<!-- Header with Logo and Title. -->
	<?php if ( isset( $settings_saved ) && $settings_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully!', 'aiohm-booking-pro' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['accommodation_added'] ) && sanitize_text_field( wp_unslash( $_GET['accommodation_added'] ) ) === '1' ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only success message, no sensitive operations ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'New accommodation added successfully!', 'aiohm-booking-pro' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="aiohm-booking-admin-header">
		<div class="aiohm-booking-admin-header-content">
			<div class="aiohm-booking-admin-logo">
				<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
			</div>
			<div class="aiohm-booking-admin-header-text">
				<h1><?php echo esc_html( $singular ); ?> Management</h1>
				<p class="aiohm-booking-admin-tagline">Manage your accommodation offerings - from individual <?php echo esc_html( strtolower( $plural ) ); ?> to entire properties.</p>
			</div>
		</div>
	</div>

	<!-- Stats Cards -->
	<div class="aiohm-booking-admin-card">
		<h3><?php echo esc_html( $singular ); ?> Statistics</h3>
		<div class="aiohm-booking-orders-stats">
			<?php
			// Calculate today's occupancy stats dynamically.
			$today           = current_time( 'Y-m-d' );
			$calendar_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'calendar' );

			if ( $calendar_module && method_exists( $calendar_module, 'get_unit_breakdown' ) ) {
				$today_breakdown = $calendar_module->get_unit_breakdown( $today );
				$occupied_today  = $today_breakdown['booked'] + $today_breakdown['pending'] + $today_breakdown['blocked'] + $today_breakdown['external'];
				$vacant_today    = $today_breakdown['total'] - $occupied_today;
			} else {
				// Fallback to static calculation using accommodation service.
				if ( class_exists( 'AIOHM_BOOKING_Accommodation_Service' ) ) {
					$stats          = AIOHM_BOOKING_Accommodation_Service::get_statistics();
					$occupied_today = 0;
					$vacant_today   = $stats['total_accommodations'] ?? count( $accommodation_data ?? array() );
				} else {
					$occupied_today = 0;
					$vacant_today   = count( $accommodation_data ?? array() );
				}
			}
			?>
			<div class="aiohm-booking-orders-stat">
				<div class="number">
				<?php
				if ( isset( $today_breakdown ) ) {
					echo esc_html( $today_breakdown['total'] );
				} else {
					// Fallback to accommodation service or accommodation data.
					if ( class_exists( 'AIOHM_BOOKING_Accommodation_Service' ) ) {
						$stats = AIOHM_BOOKING_Accommodation_Service::get_statistics();
						echo esc_html( $stats['total_accommodations'] ?? count( $accommodation_data ?? array() ) );
					} else {
						echo esc_html( count( $accommodation_data ?? array() ) );
					}
				}
				?>
				</div>
				<div class="label">Total <?php echo esc_html( $plural ); ?></div>
			</div>
			<div class="aiohm-booking-orders-stat">
				<div class="number"><?php echo esc_html( $occupied_today ); ?></div>
				<div class="label">Occupied Today</div>
			</div>
			<div class="aiohm-booking-orders-stat">
				<div class="number"><?php echo esc_html( $vacant_today ); ?></div>
				<div class="label">Available <?php echo esc_html( $plural ); ?></div>
			</div>
			<?php if ( isset( $today_breakdown ) ) : ?>
			<div class="aiohm-booking-orders-stat">
				<div class="number"><?php echo esc_html( $today_breakdown['pending'] ); ?></div>
				<div class="label">Pending Today</div>
			</div>
			<div class="aiohm-booking-orders-stat">
				<div class="number"><?php echo esc_html( $today_breakdown['external'] ); ?></div>
				<div class="label">External Today</div>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Booking Settings Section - Priority Configuration -->
	<div class="aiohm-booking-admin-card aiohm-booking-settings-priority">
		<div class="aiohm-booking-settings-header">
			<h3>Booking Settings</h3>
		</div>

		<form method="post" action="" class="aiohm-booking-settings-form" id="aiohm-booking-settings-form">
			<?php wp_nonce_field( 'aiohm_booking_save_settings', 'aiohm_booking_settings_nonce' ); ?>
			<input type="hidden" name="form_submit" value="1">

			<div class="aiohm-booking-settings-grid aiohm-booking-settings-grid--large">
				<?php
				// Get current settings.
				$global_settings = AIOHM_BOOKING_Settings::get_all();
				?>

				<div class="aiohm-booking-setting-item">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-money-alt"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Deposit Percentage</label>
						<div class="aiohm-booking-setting-description">Percentage required as deposit for bookings</div>
						<div class="aiohm-booking-setting-input">
							<input type="number" name="aiohm_booking_settings[deposit_percentage]" value="<?php echo esc_attr( $global_settings['deposit_percentage'] ?? '0' ); ?>" min="0" max="100" step="1" placeholder="0">
							<span class="aiohm-booking-setting-unit">%</span>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Enable Early Bird Feature</label>
						<div class="aiohm-booking-setting-description">Activate early bird pricing for accommodations</div>
						<div class="aiohm-booking-setting-input">
							<label class="aiohm-toggle-switch">
								<input type="hidden" name="aiohm_booking_settings[enable_early_bird_accommodation]" value="0">
								<input type="checkbox" name="aiohm_booking_settings[enable_early_bird_accommodation]" id="enable_early_bird_toggle" value="1" <?php checked( $global_settings['enable_early_bird_accommodation'] ?? '0', '1' ); ?>>
								<span class="aiohm-toggle-slider"></span>
							</label>
							<span class="toggle-text"><?php echo esc_html__( 'Disabled', 'aiohm-booking-pro' ); ?></span>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item early-bird-setting" style="display: none;">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-calendar-alt"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Early Bird Window</label>
						<div class="aiohm-booking-setting-description">Days before check-in for early bird pricing</div>
						<div class="aiohm-booking-setting-input">
							<input type="number" name="aiohm_booking_settings[early_bird_days_accommodation]" value="<?php echo esc_attr( $global_settings['early_bird_days_accommodation'] ?? '30' ); ?>" min="1" max="365" step="1" placeholder="30">
							<span class="aiohm-booking-setting-unit">days</span>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-admin-users"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Minimum Age Requirement</label>
						<div class="aiohm-booking-setting-description">Minimum age for booking guests</div>
						<div class="aiohm-booking-setting-input">
							<input type="number" name="aiohm_booking_settings[minimum_age]" value="<?php echo esc_attr( $global_settings['minimum_age'] ?? '18' ); ?>" min="0" max="99" step="1" placeholder="18">
							<span class="aiohm-booking-setting-unit">years</span>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-building"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Default Accommodation Type</label>
						<div class="aiohm-booking-setting-description">Default type for new accommodations</div>
						<div class="aiohm-booking-setting-input">
							<select name="aiohm_booking_settings[accommodation_type]">
								<option value="room" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'room' ); ?>>🏠 Room</option>
								<option value="house" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'house' ); ?>>🏘️ House</option>
								<option value="apartment" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'apartment' ); ?>>🏢 Apartment</option>
								<option value="villa" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'villa' ); ?>>🏰 Villa</option>
								<option value="bungalow" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'bungalow' ); ?>>🏕️ Bungalow</option>
								<option value="cabin" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'cabin' ); ?>>🏔️ Cabin</option>
								<option value="cottage" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'cottage' ); ?>>🏡 Cottage</option>
								<option value="suite" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'suite' ); ?>>🛏️ Suite</option>
								<option value="studio" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'studio' ); ?>>🎨 Studio</option>
								<option value="unit" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'unit' ); ?>>🏗️ Unit</option>
								<option value="space" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'space' ); ?>>🌌 Space</option>
								<option value="venue" <?php selected( $global_settings['accommodation_type'] ?? 'unit', 'venue' ); ?>>🎭 Venue</option>
							</select>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-plus-alt"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Active Accommodations</label>
						<div class="aiohm-booking-setting-description">Number of accommodations to activate</div>
						<div class="aiohm-booking-setting-input">
							<input type="number" name="aiohm_booking_settings[available_accommodations]" value="<?php echo esc_attr( $global_settings['available_accommodations'] ?? '1' ); ?>" min="1" max="50" step="1" placeholder="1">
							<span class="aiohm-booking-setting-unit">units</span>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-money-alt"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Default Price</label>
						<div class="aiohm-booking-setting-description">Default price for accommodations when no specific price is set</div>
						<div class="aiohm-booking-setting-input">
							<input type="number" name="aiohm_booking_settings[default_price]" value="<?php echo esc_attr( $global_settings['default_price'] ?? '0' ); ?>" min="0" step="0.01" placeholder="0">
							<span class="aiohm-booking-setting-unit"><?php echo esc_html( $currency ); ?></span>
						</div>
					</div>
				</div>

				<div class="aiohm-booking-setting-item early-bird-setting" style="display: none;">
					<div class="aiohm-booking-setting-icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<div class="aiohm-booking-setting-content">
						<label class="aiohm-booking-setting-label">Default Early Bird Price</label>
						<div class="aiohm-booking-setting-description">Default early bird price for accommodations when no specific price is set</div>
						<div class="aiohm-booking-setting-input">
							<?php
							// Use the accommodation-specific field name
							$current_value = $global_settings['aiohm_booking_accommodation_early_bird_price'] ?? '0';
							?>
							<input type="text" name="aiohm_booking_settings[aiohm_booking_accommodation_early_bird_price]" value="<?php echo esc_attr( $current_value ); ?>" placeholder="0">
							<span class="aiohm-booking-setting-unit"><?php echo esc_html( $currency ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</form>

		<div class="aiohm-card-footer">
			<div class="aiohm-card-footer-actions">
				<button type="submit" class="button button-primary aiohm-booking-settings-save-btn" form="aiohm-booking-settings-form">
					<span class="dashicons dashicons-yes-alt"></span>
					Save Booking Settings
				</button>
			</div>
		</div>
	</div>

	<!-- Two-Column Section for Accommodation Details -->
	<div class="aiohm-accommodation-section-wrapper">
		<form method="post" action="">
		<?php wp_nonce_field( 'aiohm_booking_save_accommodation_details', 'aiohm_accommodation_details_nonce' ); ?>
		<div class="aiohm-booking-admin-card">
			<h3><?php echo esc_html( $plural ); ?> Details</h3>
			<p>Configure the details for each of your <?php echo esc_html( strtolower( $plural ) ); ?>.</p>

			<div class="aiohm-booking-modules">
				<div class="aiohm-events-grid">
				<?php if ( ! empty( $accommodation_data ) ) : ?>
					<?php foreach ( $accommodation_data as $accommodation ) : ?>
					<div class="aiohm-module-card">
						<div class="aiohm-accommodation-header">
							<h4><?php echo esc_html( $accommodation['title'] ); ?></h4>
							<div class="aiohm-header-controls">
								<div class="aiohm-type-selector">
									<label>Type:</label>
									<select name="aiohm_accommodations[<?php echo esc_attr( $accommodation['id'] ); ?>][type]" class="accommodation-individual-type-select">
										<?php
										$current_type        = $accommodation['type'] ?? 'unit';
										$accommodation_types = AIOHM_BOOKING_Module_Accommodation::get_accommodation_types_for_select();
										foreach ( $accommodation_types as $value => $label ) :
											?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<button type="button" class="aiohm-individual-save-btn" data-post-id="<?php echo esc_attr( $accommodation['id'] ); ?>">
									<span class="dashicons dashicons-yes-alt"></span>
									Save
								</button>
							</div>
						</div>
						
						<input type="hidden" name="aiohm_accommodations[<?php echo esc_attr( $accommodation['id'] ); ?>][post_id]" value="<?php echo esc_attr( $accommodation['id'] ); ?>">
						<!-- Form Fields -->
						<div class="aiohm-setting-row">
							<label>Title</label>
							<input type="text" name="aiohm_accommodations[<?php echo esc_attr( $accommodation['id'] ); ?>][title]" value="<?php echo esc_attr( $accommodation['custom_title'] ); ?>" placeholder="<?php echo esc_attr( $accommodation['default_title'] ); ?>" data-default-title="<?php echo esc_attr( $accommodation['default_title'] ); ?>">
							<small class="field-description">Leave blank to use default: "<?php echo esc_html( $accommodation['default_title'] ); ?>"</small>
						</div>
						<div class="aiohm-setting-row">
							<label>Description</label>
							<textarea name="aiohm_accommodations[<?php echo esc_attr( $accommodation['id'] ); ?>][description]" rows="3" placeholder="Brief description of this accommodation..."><?php echo esc_textarea( $accommodation['description'] ); ?></textarea>
						</div>
						<div class="aiohm-setting-row-inline">
							<div class="aiohm-setting-row">
								<label>Early Bird Price (<?php echo esc_html( $currency ); ?>)</label>
								<input type="text" name="aiohm_accommodations[<?php echo esc_attr( $accommodation['id'] ); ?>][earlybird_price]" value="<?php echo esc_attr( $accommodation['earlybird_price'] ); ?>" placeholder="<?php echo esc_attr( $accommodation_price ); ?>">
							</div>
							<div class="aiohm-setting-row">
								<label>Standard Price (<?php echo esc_html( $currency ); ?>)</label>
								<input type="text" name="aiohm_accommodations[<?php echo esc_attr( $accommodation['id'] ); ?>][price]" value="<?php echo esc_attr( $accommodation['price'] ); ?>" placeholder="<?php echo esc_attr( $accommodation_price ); ?>">
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No accommodations found. Click "Add New" to get started.', 'aiohm-booking-pro' ); ?></p>
				<?php endif; ?>

				<!-- Add More Banner (conditional - only show for odd numbers to fill empty grid space) -->
				<?php if ( count( $accommodation_data ) < 20 && ( count( $accommodation_data ) % 2 ) === 1 ) : ?>
					<div class="aiohm-add-more-banner">
						<div class="aiohm-add-more-content">
							<h4>Need More <?php echo esc_html( $plural ); ?>?</h4>
							<p>Add more <?php echo esc_html( strtolower( $plural ) ); ?> to your property</p>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=aiohm-booking-accommodations&action=add_new_accommodation' ), 'aiohm_booking_add_accommodation' ) ); ?>" class="button button-primary aiohm-add-more-btn">
								<span class="dashicons dashicons-plus-alt"></span>
								Add New <?php echo esc_html( $singular ); ?>
							</a>
							<br><small>unlimited</small>
						</div>
					</div>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</form>
	</div>

	<!-- Accommodation Form Customization - Direct Implementation -->
	<?php $this->render_form_customization_template(); ?>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Initialize toggle switches after a short delay to ensure all scripts are loaded
			setTimeout(function() {
				if (typeof AIOHM_Booking_Settings_Admin !== 'undefined') {
					AIOHM_Booking_Settings_Admin.init();
				}

				// Handle early bird settings visibility
				function updateEarlyBirdSettingsVisibility() {
					var isEnabled = $('#enable_early_bird_toggle').is(':checked');
					var $earlyBirdSettings = $('.early-bird-setting');

					if (isEnabled) {
						$earlyBirdSettings.slideDown(300, function() {
							$earlyBirdSettings.find('input, select').prop('disabled', false);
						});
					} else {
						$earlyBirdSettings.find('input, select').prop('disabled', true);
						$earlyBirdSettings.slideUp(300);
					}
				}

				// Initial check on page load
				updateEarlyBirdSettingsVisibility();

				// Also handle initial state for disabled inputs
				var isEnabled = $('#enable_early_bird_toggle').is(':checked');
				if (!isEnabled) {
					$('.early-bird-setting input, .early-bird-setting select').prop('disabled', true);
				}

				// Listen for changes to the early bird toggle
				$('#enable_early_bird_toggle').on('change', function() {
					updateEarlyBirdSettingsVisibility();
				});

			}, 100);
		});
	</script>

	<!-- Booking Settings Section - Priority Configuration -->
</div>
