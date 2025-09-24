<?php
/**
 * Settings Page Template - Main Plugin Settings
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Get current settings.
$settings = AIOHM_BOOKING_Settings::get_all();

// Get AI Analytics settings.
$ai_analytics_settings = get_option( 'aiohm_booking_ai_analytics_settings', array() );
if ( is_string( $ai_analytics_settings ) ) {
	$ai_analytics_settings = json_decode( $ai_analytics_settings, true ) ?: array();
}
$ai_analytics_settings = is_array( $ai_analytics_settings ) ? $ai_analytics_settings : array();

// Apply defaults if settings are empty or missing keys.
$ai_analytics_defaults = array(
	'default_ai_provider'       => 'gemini',
	'enable_order_analytics'    => true,
	'enable_calendar_analytics' => true,
	'enable_ai_event_import'    => true,
	// Note: enable_ai_analytics is handled by main settings toggle system
);
$ai_analytics_settings = array_merge( $ai_analytics_defaults, $ai_analytics_settings );

// AI Analytics enable/disable is now handled by the main toggle system in primary modules

// Merge AI Analytics settings into main settings for easier access (except enable_ai_analytics which comes from main settings)
$ai_analytics_settings_to_merge = $ai_analytics_settings;
unset( $ai_analytics_settings_to_merge['enable_ai_analytics'] ); // Don't override main settings toggle
$settings = array_merge( $settings, $ai_analytics_settings_to_merge );

// Helper function to safely check settings values (handles both scalar and array values)..
/**
 * Safely check settings values for both scalar and array values.
 *
 * @param mixed $value The value to check.
 * @return bool True if the value is not empty, false otherwise.
 */
function safe_setting_check( $value ) {
	if ( is_array( $value ) ) {
		return ! empty( $value ) && ! in_array( '', $value, true );
	}
	return ! empty( $value );
}

// Get available modules.
$module_registry   = AIOHM_BOOKING_Module_Registry::instance();
$available_modules = $module_registry->get_all_modules_info();

// Mark PRO modules as premium
foreach ( $available_modules as $module_id => $module_info ) {
	if ( AIOHM_BOOKING_Utilities::is_pro_module( $module_id ) ) {
		$available_modules[ $module_id ]['is_premium'] = true;
	}
}

// Process save success/error messages.
$success_msg = get_transient( 'aiohm_booking_save_success' );
if ( $success_msg ) {
	delete_transient( 'aiohm_booking_save_success' );
}
$error_msg = get_transient( 'aiohm_booking_save_error' );
if ( $error_msg ) {
	delete_transient( 'aiohm_booking_save_error' );
}

// Data preparation.
$data = $settings; // Use current settings as base data..

// Enable states (adapting previous structure to current plugin).
$enable_accommodations         = safe_setting_check( $settings['enable_accommodations'] ?? true ); // Default to true..
$data['enable_accommodations'] = $enable_accommodations;
$data['enable_notifications']  = safe_setting_check( $settings['enable_notifications'] ?? true ); // Default to true..
$data['enable_tickets']        = safe_setting_check( $settings['enable_tickets'] ?? true ); // Default to true..
$data['enable_orders']         = safe_setting_check( $settings['enable_orders'] ?? true ); // Default to true..

// Module order for sortable functionality.
$module_order                = isset( $settings['module_order'] ) && is_array( $settings['module_order'] ) ? $settings['module_order'] : array();
$data['enable_calendar']     = safe_setting_check( $settings['enable_calendar'] ?? true ); // Default to true..
$data['enable_ai_analytics'] = safe_setting_check( $settings['enable_ai_analytics'] ?? true ); // Default to true..
$data['enable_css_manager']  = safe_setting_check( $settings['enable_css_manager'] ?? true ); // Default to true..

// Settings defaults and compatibility..
$data['available_rooms'] = $settings['available_rooms'] ?? $settings['available_accommodations'] ?? 1;
$data['allow_private']   = safe_setting_check( $settings['allow_private_all'] ?? null );

// Form customization settings..
$data['form_primary_color'] = $settings['form_primary_color'] ?? $settings['brand_color'] ?? '#457d59';
$data['form_text_color']    = $settings['form_text_color'] ?? $settings['font_color'] ?? '#333333';
$data['deposit']            = $settings['deposit_percentage'] ?? $settings['deposit_percent'] ?? 0;

// Early bird settings.
$earlybird_days = isset( $settings['earlybird_days'] ) ? absint( $settings['earlybird_days'] ) : 30;

?>

<div class="wrap aiohm-booking-admin">
	<?php if ( ! empty( $success_msg ) ) : ?>
	<div class="notice notice-success is-dismissible">
		<p><?php echo esc_html( $success_msg ); ?></p>
	</div>
	<?php endif; ?>
	<?php if ( ! empty( $error_msg ) ) : ?>
	<div class="notice notice-error is-dismissible">
		<p><?php echo esc_html( $error_msg ); ?></p>
	</div>
	<?php endif; ?>

	<div class="aiohm-booking-admin-header">
		<div class="aiohm-booking-admin-header-content">
			<div class="aiohm-booking-admin-logo">
				<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
			</div>
			<div class="aiohm-booking-admin-header-text">
				<h1>AIOHM Booking - Event Tickets</h1>
				<p class="aiohm-booking-admin-tagline">Configure your primary booking modules and supporting tools for seamless event ticket management.</p>
			</div>
		</div>
	</div>

	<!-- AIOHM Booking Settings Template Loaded -->


	<form method="post" action="">
	<?php wp_nonce_field( 'aiohm_booking_save_settings', 'aiohm_booking_settings_nonce' ); ?>
	<?php wp_nonce_field( 'aiohm_booking_ai_analytics_settings', 'ai_analytics_nonce' ); ?>

	<div class="aiohm-booking-modules">
		
		<!-- Primary Booking Modules Section -->
		<div class="aiohm-section-header">
			<h2 class="aiohm-section-title aiohm-primary-title">Primary Booking Modules</h2>
			<p class="aiohm-section-description">Your core booking functionalities - Event Tickets, Accommodation, and AI Analytics</p>
		</div>
		
		<div class="aiohm-module-grid aiohm-sortable aiohm-primary-modules" id="sortable-primary-grid" data-sort-action="aiohm_save_module_order">
		<?php
		// Define PRO modules that should be moved to the PRO section
		$pro_modules     = AIOHM_BOOKING_Utilities::get_pro_modules();       // Define primary modules (main business purposes)..
		$primary_modules = array( 'accommodations', 'tickets', 'ai_analytics' );

		foreach ( $primary_modules as $module_id ) {
			if ( ! isset( $available_modules[ $module_id ] ) ) {
				continue;
			}

			$module_info = $available_modules[ $module_id ];
			// Skip hidden modules..
			if ( ! empty( $module_info['hidden_in_settings'] ) ) {
				continue;
			}

			// Check if module should be visible in settings..
			if ( isset( $module_info['visible_in_settings'] ) && ! $module_info['visible_in_settings'] ) {
				continue;
			}

			// Check actual enabled status for primary modules
			$is_enabled = true; // Default for other modules
			if ( $module_id === 'accommodations' ) {
				$is_enabled = isset( $settings['enable_accommodations'] ) ? (bool) $settings['enable_accommodations'] : true; // Default to enabled if not set
			} elseif ( $module_id === 'tickets' ) {
				$is_enabled = isset( $settings['enable_tickets'] ) ? (bool) $settings['enable_tickets'] : true; // Default to enabled if not set
			} elseif ( $module_id === 'ai_analytics' ) {
				$is_enabled = isset( $settings['enable_ai_analytics'] ) ? (bool) $settings['enable_ai_analytics'] : true; // Default to enabled if not set
			}

			$module_name        = $module_info['name'] ?? ucfirst( $module_id );
			$module_description = $module_info['description'] ?? '';
			$module_icon        = $module_info['icon'] ?? '⚙️';
			$is_premium         = ! empty( $module_info['is_premium'] );
			$has_admin_page     = ! empty( $module_info['has_admin_page'] );
			?>
			<div class="aiohm-booking-card aiohm-module-card <?php echo esc_attr( $is_enabled ? 'is-active' : 'is-inactive' ); ?> aiohm-primary-module" data-id="<?php echo esc_attr( $module_id ); ?>">
					<div class="aiohm-card-header aiohm-module-header">
						<div class="aiohm-card-header-title">
							<h3 class="aiohm-card-title">
								<span class="aiohm-card-icon aiohm-module-icon"><?php echo esc_html( $module_icon ); ?></span>
								<?php echo esc_html( $module_name ); ?>
								<?php if ( $is_premium ) : ?>
									<span class="aiohm-module-badge aiohm-premium-badge">PRO</span>
								<?php endif; ?>
							</h3>
						</div>
						<div class="aiohm-header-controls">
							<?php if ( $module_id === 'accommodations' || $module_id === 'tickets' || $module_id === 'ai_analytics' ) : ?>
								<!-- Clickable badge toggle for primary modules -->
								<span 
									class="aiohm-module-status aiohm-module-toggle-badge <?php echo esc_attr( $is_enabled ? 'enabled' : 'disabled' ); ?>" 
									data-module="<?php echo esc_attr( $module_id ); ?>"
									data-enabled="<?php echo esc_attr( $is_enabled ? '1' : '0' ); ?>"
									title="<?php esc_attr_e( 'Click to toggle module', 'aiohm-booking-pro' ); ?>"
								>
									<?php echo esc_html( $is_enabled ? __( 'ENABLED', 'aiohm-booking-pro' ) : __( 'DISABLED', 'aiohm-booking-pro' ) ); ?>
								</span>
							<?php else : ?>
								<!-- Static badge for other modules -->
								<span class="aiohm-module-status enabled">ENABLED</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="aiohm-card-content aiohm-module-settings">
						<?php if ( ! empty( $module_description ) ) : ?>
							<p class="aiohm-card-subtitle aiohm-module-description"><?php echo esc_html( $module_description ); ?></p>
						<?php endif; ?>
						<div class="aiohm-card-footer aiohm-module-actions">
				<?php
				$has_admin_page       = ! empty( $module_info['has_admin_page'] );
				$has_settings_section = ! empty( $module_info['has_settings'] ) && ! $has_admin_page;

				if ( $has_admin_page ) {
							$action_page_slug = ! empty( $module_info['admin_page_slug'] )
						? $module_info['admin_page_slug']
						: 'aiohm-booking-' . str_replace( '_', '-', $module_id );
					?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
			class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary"
			>
					<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
			class="aiohm-module-badge aiohm-page-badge aiohm-page-badge-inline"
			title="<?php esc_attr_e( 'Go to module page', 'aiohm-booking-pro' ); ?>">PAGE</a>
					<?php
				} elseif ( $has_settings_section ) {
							// Special case for shortcode module - it has settings but uses the instructions card..
					if ( 'shortcode' === $module_id ) {
						$target_id = 'aiohm-shortcode-instructions';
					} else {
						$target_id = 'aiohm-' . str_replace( '_', '-', $module_id ) . '-settings';
					}
					?>
		<button type="button"
				class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary"
				data-action="scroll"
				data-target="#<?php echo esc_attr( $target_id ); ?>"
				>
					<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
		</button>
					<?php
				}
				?>
						</div>
						<?php if ( $is_premium && AIOHM_BOOKING_Utilities::is_free_version() ) : ?>
							<?php echo wp_kses_post( AIOHM_BOOKING_Utilities::get_go_pro_notice( $module_name ) ); ?>
						<?php endif; ?>
					</div>
				</div>
				<?php
		}
		?>
	</div>
	</div>

	<!-- Supporting Modules Section -->
	<div class="aiohm-section-header aiohm-section-spacing">
		<h2 class="aiohm-section-title aiohm-supporting-title">Supporting Modules</h2>
		<p class="aiohm-section-description">Essential tools to manage and enhance your booking operations</p>
	</div>
	
	<div class="aiohm-module-grid aiohm-sortable aiohm-supporting-modules" id="sortable-supporting-grid" data-sort-action="aiohm_save_module_order">
		<?php
		// Define supporting modules..
		$supporting_modules = array( 'calendar', 'orders', 'notifications' );

		foreach ( $supporting_modules as $module_id ) {
			if ( ! isset( $available_modules[ $module_id ] ) ) {
				continue;
			}

			$module_info = $available_modules[ $module_id ];
			// Skip hidden modules..
			if ( ! empty( $module_info['hidden_in_settings'] ) ) {
				continue;
			}

			// Check if module should be visible in settings.
			if ( isset( $module_info['visible_in_settings'] ) && ! $module_info['visible_in_settings'] ) {
				continue;
			}

			// Check actual enabled status for supporting modules
			$is_enabled        = true; // Default for other modules
			$is_dependent      = false; // Track if this module depends on others
			$dependency_reason = '';

			if ( $module_id === 'calendar' ) {
				$accommodations_enabled = isset( $settings['enable_accommodations'] ) ? (bool) $settings['enable_accommodations'] : true;
				$is_enabled             = isset( $settings['enable_calendar'] ) ? (bool) $settings['enable_calendar'] : true;

				// Calendar depends on accommodations
				if ( ! $accommodations_enabled ) {
					$is_enabled        = false;
					$is_dependent      = true;
					$dependency_reason = 'Disabled because Accommodations module is disabled';
				}
			} elseif ( isset( $settings[ 'enable_' . $module_id ] ) ) {
				$is_enabled = (bool) $settings[ 'enable_' . $module_id ];
			}

			$module_name        = $module_info['name'] ?? ucfirst( $module_id );
			$module_description = $module_info['description'] ?? '';
			$module_icon        = $module_info['icon'] ?? '⚙️';
			$is_premium         = ! empty( $module_info['is_premium'] );
			$has_admin_page     = ! empty( $module_info['has_admin_page'] );
			?>
			<div class="aiohm-booking-card aiohm-module-card <?php echo esc_attr( $is_enabled ? 'is-active' : 'is-inactive' ); ?> <?php echo esc_attr( $is_dependent ? 'is-dependent' : '' ); ?> aiohm-supporting-module" data-id="<?php echo esc_attr( $module_id ); ?>">
				<div class="aiohm-card-header aiohm-module-header">
					<div class="aiohm-card-header-title">
						<h3 class="aiohm-card-title">
							<span class="aiohm-card-icon aiohm-module-icon"><?php echo esc_html( $module_icon ); ?></span>
							<?php echo esc_html( $module_name ); ?>
							<?php if ( $is_premium ) : ?>
								<span class="aiohm-module-badge aiohm-premium-badge">PRO</span>
							<?php endif; ?>
						</h3>
					</div>
					<div class="aiohm-header-controls">
						<?php if ( $is_dependent ) : ?>
							<!-- Dependent module - show as disabled with reason -->
							<span 
								class="aiohm-module-status aiohm-dependent-badge disabled" 
								title="<?php echo esc_attr( $dependency_reason ); ?>"
							>
								DISABLED
							</span>
						<?php else : ?>
							<!-- Clickable badge toggle for supporting modules -->
							<span 
								class="aiohm-module-status aiohm-module-toggle-badge <?php echo esc_attr( $is_enabled ? 'enabled' : 'disabled' ); ?>" 
								data-module="<?php echo esc_attr( $module_id ); ?>"
								data-enabled="<?php echo esc_attr( $is_enabled ? '1' : '0' ); ?>"
								title="<?php esc_attr_e( 'Click to toggle module', 'aiohm-booking-pro' ); ?>"
							>
								<?php echo esc_html( $is_enabled ? __( 'ENABLED', 'aiohm-booking-pro' ) : __( 'DISABLED', 'aiohm-booking-pro' ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
				<div class="aiohm-card-content aiohm-module-settings">
					<?php if ( ! empty( $module_description ) ) : ?>
						<p class="aiohm-card-subtitle aiohm-module-description"><?php echo esc_html( $module_description ); ?></p>
					<?php endif; ?>
					<div class="aiohm-card-footer aiohm-module-actions">
						<?php
						$has_admin_page       = ! empty( $module_info['has_admin_page'] );
						$has_settings_section = ! empty( $module_info['has_settings'] ) && ! $has_admin_page;

						if ( $has_admin_page ) {
							$action_page_slug = ! empty( $module_info['admin_page_slug'] )
								? $module_info['admin_page_slug']
								: 'aiohm-booking-' . str_replace( '_', '-', $module_id );
							?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
								class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary">
								<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
								class="aiohm-module-badge aiohm-page-badge aiohm-page-badge-inline"
								title="<?php esc_html_e( 'Go to module page', 'aiohm-booking-pro' ); ?>">PAGE</a>
							<?php
						}

						if ( $has_settings_section ) {
							if ( 'shortcode' === $module_id ) {
								$target_id = 'aiohm-shortcode-instructions';
							} else {
								$target_id = 'aiohm-' . str_replace( '_', '-', $module_id ) . '-settings';
							}
							?>
							<button type="button"
								class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary"
								data-action="scroll"
								data-target="#<?php echo esc_attr( $target_id ); ?>">
								<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
							</button>
							<?php
						}
						?>
					</div>
					<?php if ( $is_premium && AIOHM_BOOKING_Utilities::is_free_version() ) : ?>
						<?php echo wp_kses_post( AIOHM_BOOKING_Utilities::get_go_pro_notice( $module_name ) ); ?>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
		?>
	</div>

	<!-- Tools & Integration Section -->
	<div class="aiohm-section-header aiohm-section-spacing">
		<h2 class="aiohm-section-title aiohm-tools-title">Tools & Integration</h2>
		<p class="aiohm-section-description">Utilities and integration tools for your booking system</p>
	</div>
	
	<div class="aiohm-module-grid aiohm-sortable aiohm-tools-modules" id="sortable-tools-grid" data-sort-action="aiohm_save_module_order">
		<?php
		// Define tools modules - include admin modules and integration modules.
		$tools_modules = array( 'shortcode', 'help' );

		// Add all integration category modules to tools section.
		foreach ( $available_modules as $module_id => $module_info ) {
			if ( isset( $module_info['category'] ) && 'integration' === $module_info['category'] ) {
				$tools_modules[] = $module_id;
			}
		}

		foreach ( $tools_modules as $module_id ) {
			if ( ! isset( $available_modules[ $module_id ] ) ) {
				continue;
			}

			$module_info = $available_modules[ $module_id ];
			// Skip hidden modules.
			if ( ! empty( $module_info['hidden_in_settings'] ) ) {
				continue;
			}

			// Check if module should be visible in settings.
			if ( isset( $module_info['visible_in_settings'] ) && ! $module_info['visible_in_settings'] ) {
				continue;
			}

			// Check if module is enabled (tools modules are typically always enabled, but we'll check settings)
			$is_enabled         = isset( $settings[ 'enable_' . $module_id ] ) ? (bool) $settings[ 'enable_' . $module_id ] : true;
			$module_name        = $module_info['name'] ?? ucfirst( $module_id );
			$module_description = $module_info['description'] ?? '';
			$module_icon        = $module_info['icon'] ?? '⚙️';
			$is_premium         = ! empty( $module_info['is_premium'] );
			$has_admin_page     = ! empty( $module_info['has_admin_page'] );
			?>
			<div class="aiohm-booking-card aiohm-module-card <?php echo esc_attr( $is_enabled ? 'is-active' : 'is-inactive' ); ?> aiohm-tools-module" data-id="<?php echo esc_attr( $module_id ); ?>">
				<div class="aiohm-card-header aiohm-module-header">
					<div class="aiohm-card-header-title">
						<h3 class="aiohm-card-title">
							<span class="aiohm-card-icon aiohm-module-icon"><?php echo esc_html( $module_icon ); ?></span>
							<?php echo esc_html( $module_name ); ?>
							<?php if ( $is_premium ) : ?>
								<span class="aiohm-module-badge aiohm-premium-badge">PRO</span>
							<?php endif; ?>
						</h3>
					</div>
					<div class="aiohm-header-controls">
						<!-- Clickable badge toggle for tools modules -->
						<span 
							class="aiohm-module-status aiohm-module-toggle-badge <?php echo esc_attr( $is_enabled ? 'enabled' : 'disabled' ); ?>" 
							data-module="<?php echo esc_attr( $module_id ); ?>"
							data-enabled="<?php echo esc_attr( $is_enabled ? '1' : '0' ); ?>"
							title="<?php esc_attr_e( 'Click to toggle module', 'aiohm-booking-pro' ); ?>"
						>
							<?php echo esc_html( $is_enabled ? __( 'ENABLED', 'aiohm-booking-pro' ) : __( 'DISABLED', 'aiohm-booking-pro' ) ); ?>
						</span>
					</div>
				</div>
				<div class="aiohm-card-content aiohm-module-settings">
					<?php if ( ! empty( $module_description ) ) : ?>
						<p class="aiohm-card-subtitle aiohm-module-description"><?php echo esc_html( $module_description ); ?></p>
					<?php endif; ?>
					<div class="aiohm-card-footer aiohm-module-actions">
						<?php
						$has_admin_page       = ! empty( $module_info['has_admin_page'] );
						$has_settings_section = ! empty( $module_info['has_settings'] ) && ! $has_admin_page;

						if ( $has_admin_page ) {
							$action_page_slug = ! empty( $module_info['admin_page_slug'] )
								? $module_info['admin_page_slug']
								: 'aiohm-booking-' . str_replace( '_', '-', $module_id );
							?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
								class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary">
								<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
							</a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
								class="aiohm-module-badge aiohm-page-badge aiohm-page-badge-inline"
								title="<?php esc_html_e( 'Go to module page', 'aiohm-booking-pro' ); ?>">PAGE</a>
							<?php
						}

						if ( $has_settings_section ) {
							if ( 'shortcode' === $module_id ) {
								$target_id = 'aiohm-shortcode-instructions';
							} else {
								$target_id = 'aiohm-' . str_replace( '_', '-', $module_id ) . '-settings';
							}
							?>
							<button type="button"
								class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary"
								data-action="scroll"
								data-target="#<?php echo esc_attr( $target_id ); ?>">
								<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
							</button>
							<?php
						}
						?>
					</div>
					<?php if ( $is_premium && AIOHM_BOOKING_Utilities::is_free_version() ) : ?>
						<?php echo wp_kses_post( AIOHM_BOOKING_Utilities::get_go_pro_notice( $module_name ) ); ?>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
		?>
	</div>

	<!-- AIOHM Booking PRO Section -->
	<div class="aiohm-section-header aiohm-section-spacing">
		<h2 class="aiohm-section-title aiohm-pro-title">AIOHM Booking PRO</h2>
		<p class="aiohm-section-description">Premium modules for advanced payment processing, AI integrations, and enhanced functionality</p>
	</div>
	
	<div class="aiohm-module-grid aiohm-sortable" id="sortable-pro-module-grid">
		<?php
		// Render PRO modules.
		$pro_rendered = false;
		foreach ( $pro_modules as $module_id ) {
			if ( ! isset( $available_modules[ $module_id ] ) ) {
				continue;
			}

			$module_info = $available_modules[ $module_id ];
			// Skip hidden modules.
			if ( ! empty( $module_info['hidden_in_settings'] ) ) {
				continue;
			}

			// Check if module should be visible in settings.
			if ( isset( $module_info['visible_in_settings'] ) && ! $module_info['visible_in_settings'] ) {
				continue;
			}

			$pro_rendered = true;

			// Check actual enabled status for PRO modules
			$is_enabled        = true; // Default for most PRO modules
			$is_dependent      = false; // Track if this module depends on others
			$dependency_reason = '';

			if ( $module_id === 'ai_analytics' ) {
				// AI Analytics has special handling - check if it's enabled in its own settings
				$is_enabled = ! empty( $ai_analytics_settings['enable_ai_analytics'] );
			} elseif ( in_array( $module_id, array( 'ollama', 'openai', 'gemini', 'shareai' ) ) ) {
				// AI provider modules depend on AI Analytics being enabled
				$ai_analytics_enabled = isset( $settings['enable_ai_analytics'] ) ? (bool) $settings['enable_ai_analytics'] : true;
				$is_enabled           = isset( $settings[ 'enable_' . $module_id ] ) ? (bool) $settings[ 'enable_' . $module_id ] : false;

				// If AI Analytics is disabled, these modules should appear disabled and dependent
				if ( ! $ai_analytics_enabled ) {
					$is_enabled        = false;
					$is_dependent      = true;
					$dependency_reason = 'Disabled because AI Analytics module is disabled';
				}
			}

			$module_name            = $module_info['name'] ?? ucfirst( $module_id );
				$module_description = $module_info['description'] ?? '';
				$module_icon        = $module_info['icon'] ?? '⚙️';
				$is_premium         = true; // All modules in this section are premium.
				$has_admin_page     = ! empty( $module_info['has_admin_page'] );
			?>
				<div class="aiohm-booking-card aiohm-module-card <?php echo esc_attr( $is_enabled ? 'is-active' : 'is-inactive' ); ?> <?php echo esc_attr( $is_dependent ? 'is-dependent' : '' ); ?> aiohm-pro-module" data-id="<?php echo esc_attr( $module_id ); ?>">
					<div class="aiohm-card-header aiohm-module-header">
						<div class="aiohm-card-header-title">
							<h3 class="aiohm-card-title">
								<span class="aiohm-card-icon aiohm-module-icon"><?php echo esc_html( $module_icon ); ?></span>
								<?php echo esc_html( $module_name ); ?>
								<span class="aiohm-module-badge aiohm-premium-badge">PRO</span>
							</h3>
						</div>
						<div class="aiohm-header-controls">
							<?php if ( $is_dependent ) : ?>
								<!-- Dependent module - show as disabled with reason -->
								<span 
									class="aiohm-module-status aiohm-dependent-badge disabled" 
									title="<?php echo esc_attr( $dependency_reason ); ?>"
								>
									DISABLED
								</span>
							<?php else : ?>
								<!-- Clickable badge toggle for AI modules -->
								<span 
									class="aiohm-module-status aiohm-module-toggle-badge <?php echo esc_attr( $is_enabled ? 'enabled' : 'disabled' ); ?>" 
									data-module="<?php echo esc_attr( $module_id ); ?>"
									data-enabled="<?php echo esc_attr( $is_enabled ? '1' : '0' ); ?>"
									title="<?php esc_attr_e( 'Click to toggle module', 'aiohm-booking-pro' ); ?>"
								>
									<?php echo esc_html( $is_enabled ? __( 'ENABLED', 'aiohm-booking-pro' ) : __( 'DISABLED', 'aiohm-booking-pro' ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
					<div class="aiohm-card-content aiohm-module-settings">
						<?php if ( ! empty( $module_description ) ) : ?>
							<p class="aiohm-card-subtitle aiohm-module-description"><?php echo esc_html( $module_description ); ?></p>
						<?php endif; ?>
						<div class="aiohm-card-footer aiohm-module-actions">
				<?php
				$has_admin_page       = ! empty( $module_info['has_admin_page'] );
				$has_settings_section = ! empty( $module_info['has_settings'] ) && ! $has_admin_page;

				if ( $has_admin_page ) {
							$action_page_slug = ! empty( $module_info['admin_page_slug'] )
						? $module_info['admin_page_slug']
						: 'aiohm-booking-' . str_replace( '_', '-', $module_id );
					?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
			class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary"
			>
					<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $action_page_slug ) ); ?>"
			class="aiohm-module-badge aiohm-page-badge aiohm-page-badge-inline"
			title="<?php esc_html_e( 'Go to module page', 'aiohm-booking-pro' ); ?>">PAGE</a>
					<?php
				} elseif ( $has_settings_section ) {
					$target_id = 'aiohm-' . str_replace( '_', '-', $module_id ) . '-settings';
					?>
		<button type="button"
				class="aiohm-configure-btn aiohm-btn aiohm-btn--secondary"
				data-action="scroll"
				data-target="#<?php echo esc_attr( $target_id ); ?>"
				>
					<?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?>
		</button>
					<?php
				}
				?>
						</div>
					</div>
				</div>
				<?php
		}

		if ( ! $pro_rendered ) {
			echo '<div class="aiohm-module-card aiohm-pro-placeholder">';
			echo '<div class="aiohm-module-header">';
			echo '<h3><span class="aiohm-module-icon">🔒</span>Premium Modules</h3>';
			echo '<span class="aiohm-module-status">LOCKED</span>';
			echo '</div>';
			echo '<div class="aiohm-module-settings">';
			echo '<p class="aiohm-module-description">Upgrade to AIOHM Booking PRO to access advanced AI integrations and premium features.</p>';
			echo '<div class="aiohm-module-actions">';
			echo '<a href="#" class="aiohm-configure-btn aiohm-btn aiohm-btn--primary">Upgrade to PRO</a>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}
		?>
		</div>
	</div>

	<!-- Settings Section -->
	<div class="aiohm-settings-section">
		<div class="aiohm-settings-header">
			<h2>📋 Settings Configuration</h2>
			<p>Configure your booking system settings using the cards below.</p>
		</div>
		<div class="aiohm-settings-grid">
		<?php // Shortcode instructions - always show ?>
		<div class="aiohm-booking-card aiohm-card aiohm-mb-8" id="aiohm-shortcode-instructions">
		<!-- Removed drag handle to eliminate conflicts -->
		<div class="aiohm-card-header aiohm-card__header">
			<div class="aiohm-card-header-title">
				<h3 class="aiohm-card-title aiohm-card__title">
					<span class="aiohm-card-icon">📋</span>
					Shortcode Instructions
				</h3>
			</div>
			<div class="aiohm-header-controls">
				<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-shortcode-instructions">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
				</button>
			</div>
		</div>
		<div class="aiohm-card-content aiohm-card__content">
		<p class="aiohm-p">Use these shortcodes to embed booking functionality anywhere on your site.</p>
		
		<div class="aiohm-shortcode-list">
			<div class="aiohm-shortcode-item">
			<div class="aiohm-shortcode-header">
				<button type="button" class="aiohm-copy-btn" data-shortcode="[aiohm_booking]" title="Copy to clipboard">
				<span class="dashicons dashicons-clipboard"></span>
				</button>
				<code class="aiohm-shortcode-code" id="shortcode-main">[aiohm_booking]</code>
				<span class="aiohm-shortcode-badge">Main</span>
			</div>
			<p class="aiohm-shortcode-description">Unified booking form with intelligent tab-based navigation. Automatically adapts to show events and/or accommodations based on enabled modules. Includes complete booking flow: selection, contact details, and checkout.</p>
			</div>

			<div class="aiohm-shortcode-item">
			<div class="aiohm-shortcode-header">
				<button type="button" class="aiohm-copy-btn" data-shortcode="[aiohm_booking_success]" title="Copy to clipboard">
				<span class="dashicons dashicons-clipboard"></span>
				</button>
				<code class="aiohm-shortcode-code" id="shortcode-success">[aiohm_booking_success]</code>
				<span class="aiohm-shortcode-badge">Success</span>
			</div>
			<p class="aiohm-shortcode-description">Booking confirmation and success page display</p>
			</div>
			
		</div>

		</div>
	</div>
	<?php // End shortcode instructions ?>

	<?php
	// AI Settings Cards - Now handled by individual AI modules
	if ( aiohm_booking_is_module_enabled( 'ai_analytics' ) ) {
		// Render AI Analytics settings card
		if ( class_exists( 'AIOHM_BOOKING_Module_AI_Analytics' ) ) {
			AIOHM_BOOKING_Module_AI_Analytics::render_ai_analytics_settings_card();
		}

		// Render AI Provider settings cards
		if ( class_exists( 'AIOHM_BOOKING_Module_OpenAI' ) ) {
			AIOHM_BOOKING_Module_OpenAI::render_settings_card();
		}
		if ( class_exists( 'AIOHM_BOOKING_Module_Gemini' ) ) {
			AIOHM_BOOKING_Module_Gemini::render_settings_card();
		}
		if ( class_exists( 'AIOHM_BOOKING_Module_ShareAI' ) ) {
			AIOHM_BOOKING_Module_ShareAI::render_settings_card();
		}
		if ( class_exists( 'AIOHM_BOOKING_Module_Ollama' ) ) {
			AIOHM_BOOKING_Module_Ollama::render_settings_card();
		}
	}

	// Payment Module Settings Cards
		if ( function_exists( 'aiohm_booking_fs' ) && aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
		// Load payment modules
		$registry      = AIOHM_BOOKING_Module_Registry::instance();
		$stripe_module = $registry->get_module( 'stripe' );
		$paypal_module = $registry->get_module( 'paypal' );

		// Render Stripe settings (show even if disabled so users can enable it)
		if ( class_exists( 'AIOHM_BOOKING_Module_Stripe' ) ) {
			$stripe_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'stripe' );
			if ( $stripe_module && method_exists( $stripe_module, 'render_settings' ) ) {
				$stripe_module->render_settings();
			}
		}

		// Render PayPal settings (show even if disabled so users can enable it)
		if ( class_exists( 'AIOHM_BOOKING_Module_PayPal' ) ) {
			$paypal_module = AIOHM_BOOKING_Module_Registry::instance()->get_module( 'paypal' );
			if ( $paypal_module && method_exists( $paypal_module, 'render_settings' ) ) {
				$paypal_module->render_settings();
			}
		}
	}
	?>


	<!-- Facebook Integration Configuration Section -->
	<?php // Facebook Integration Configuration - always show ?>
	<div class="aiohm-booking-card aiohm-card aiohm-mb-8 aiohm-masonry-card" id="aiohm-facebook-settings" data-module="facebook">
		<div class="aiohm-masonry-drag-handle">
			<span class="dashicons dashicons-menu"></span>
		</div>
		<div class="aiohm-card-header aiohm-card__header">
			<div class="aiohm-card-header-title">
				<h3 class="aiohm-card-title aiohm-card__title">
					<span class="aiohm-card-icon">📘</span>
					Facebook Integration Settings
				</h3>
			</div>
			<div class="aiohm-header-controls">
				<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-facebook-settings">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
				</button>
			</div>
		</div>
		<div class="aiohm-card-content aiohm-card__content">
			<!-- Facebook App ID -->
			<div class="aiohm-facebook-section">
				<label class="aiohm-facebook-label">Facebook App ID</label>
				<input type="text" name="aiohm_booking_settings[facebook_app_id]" 
						value="<?php echo esc_attr( $settings['facebook_app_id'] ?? '' ); ?>" 
						class="aiohm-facebook-input">
				<p class="aiohm-facebook-field-description">
					You can view or create your Facebook Apps <a href="https://developers.facebook.com/apps/" target="_blank">from here</a>
				</p>
			</div>

			<!-- Facebook App Secret -->
			<div class="aiohm-facebook-section">
				<label class="aiohm-facebook-label">Facebook App secret</label>
				<input type="text" name="aiohm_booking_settings[facebook_app_secret]" 
						value="<?php echo esc_attr( $settings['facebook_app_secret'] ?? '' ); ?>" 
						class="aiohm-facebook-input">
				<p class="aiohm-facebook-field-description">
					You can view or create your Facebook Apps <a href="https://developers.facebook.com/apps/" target="_blank">from here</a>
				</p>
			</div>

			<!-- Save Button -->
			<div class="aiohm-facebook-save">
				<?php submit_button( 'Save Facebook Settings', 'primary', 'save_facebook_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--primary' ) ); ?>
			</div>

			<div style="margin: 20px 0;"></div>

			<!-- Facebook Setup Note -->
			<div class="aiohm-facebook-note">
				<strong>Note :</strong> You have to create a Facebook application before filling the following details. 
				<a href="https://developers.facebook.com/apps/" target="_blank">Click here</a> to create new Facebook application.
				<br>For detailed step by step instructions <a href="https://developers.facebook.com/docs/development/create-an-app" target="_blank">Click here</a>.
				<br><strong>Set the site url as :</strong> <?php echo esc_url( home_url() ); ?> 
				<button type="button" class="aiohm-copy-url-btn" data-url="<?php echo esc_attr( home_url() ); ?>" title="Copy to clipboard">
					📋
				</button>
				<br><strong>Set Valid OAuth redirect URI :</strong> <?php echo esc_url( home_url( '/wp-admin/admin-post.php?action=ife_facebook_authorize_callback' ) ); ?> 
				<button type="button" class="aiohm-copy-url-btn" data-url="<?php echo esc_attr( home_url( '/wp-admin/admin-post.php?action=ife_facebook_authorize_callback' ) ); ?>" title="Copy to clipboard">
					📋
				</button>
			</div>

			<!-- Facebook Authorization -->
			<div class="aiohm-facebook-section">
				<label class="aiohm-facebook-label">Facebook Authorization</label>
				<div class="aiohm-facebook-auth">
					<?php if ( ! empty( $settings['facebook_access_token'] ) ) : ?>
						<button type="button" class="aiohm-facebook-reauth-btn">Reauthorize</button>
						<span class="aiohm-facebook-auth-status">( Authorized as: Facebook User )</span>
					<?php else : ?>
						<button type="button" class="aiohm-facebook-auth-btn">Authorize</button>
					<?php endif; ?>
				</div>
				<p class="aiohm-facebook-auth-description">
					Please authorize your Facebook account for import Facebook events. Please authorize with account which you have used for create an Facebook app.
				</p>
			</div>
			
			<div class="aiohm-form-section">
				<h4>How to Import Events</h4>
				<ol class="aiohm-setup-steps">
					<li>Configure your Facebook Access Token above</li>
					<li>Go to <strong>Event Tickets → Event Tickets Manager</strong></li>
					<li>Click the <strong>📘 Import from Facebook</strong> button on any event</li>
					<li>Paste the Facebook event URL and import</li>
				</ol>
				
				<div class="aiohm-info-note">
					<strong>💡 Quick Setup:</strong> Get a temporary access token from 
					<a href="https://developers.facebook.com/tools/explorer/" target="_blank">Facebook Graph API Explorer</a> 
					with "events_read" permission for testing.
				</div>
			</div>
		</div>
	</div>
	<?php // End Facebook Integration Configuration ?>

	<!-- Global Booking Settings Section -->
	<?php // Global Booking Settings - always show ?>
	<div class="aiohm-booking-card aiohm-card aiohm-mb-8" id="aiohm-booking-settings">
		<!-- Removed drag handle to eliminate conflicts -->
		<div class="aiohm-card-header aiohm-card__header">
			<div class="aiohm-card-header-title">
				<h3 class="aiohm-card-title aiohm-card__title">
					<span class="aiohm-card-icon">⚙️</span>
					Global Settings
				</h3>
			</div>
			<div class="aiohm-header-controls">
				<button type="button" class="aiohm-card-toggle-btn" data-target="aiohm-booking-settings">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
				</button>
			</div>
		</div>
		<div class="aiohm-card-content aiohm-card__content">
		<p class="aiohm-p">Configure global booking-related settings, branding, and operational options that apply to all booking modules.</p>

		<div class="aiohm-grid aiohm-grid--2-cols">
			<div>
			<h4>General Settings</h4>

			<div class="aiohm-form-group">
				<label class="aiohm-form-label">Default Currency</label>
				<select name="aiohm_booking_settings[currency]" class="aiohm-form-select">
				<option value="USD" <?php selected( $settings['currency'] ?? 'USD', 'USD' ); ?>>🇺🇸 USD - US Dollar ($)</option>
				<option value="EUR" <?php selected( $settings['currency'] ?? 'USD', 'EUR' ); ?>>🇪🇺 EUR - Euro (€)</option>
				<option value="GBP" <?php selected( $settings['currency'] ?? 'USD', 'GBP' ); ?>>🇬🇧 GBP - British Pound (£)</option>
				<option value="JPY" <?php selected( $settings['currency'] ?? 'USD', 'JPY' ); ?>>🇯🇵 JPY - Japanese Yen (¥)</option>
				<option value="CAD" <?php selected( $settings['currency'] ?? 'USD', 'CAD' ); ?>>🇨🇦 CAD - Canadian Dollar (C$)</option>
				<option value="AUD" <?php selected( $settings['currency'] ?? 'USD', 'AUD' ); ?>>🇦🇺 AUD - Australian Dollar (A$)</option>
				<option value="CHF" <?php selected( $settings['currency'] ?? 'USD', 'CHF' ); ?>>🇨🇭 CHF - Swiss Franc (Fr)</option>
				<option value="CNY" <?php selected( $settings['currency'] ?? 'USD', 'CNY' ); ?>>🇨🇳 CNY - Chinese Yuan (¥)</option>
				<option value="SEK" <?php selected( $settings['currency'] ?? 'USD', 'SEK' ); ?>>🇸🇪 SEK - Swedish Krona (kr)</option>
				<option value="NZD" <?php selected( $settings['currency'] ?? 'USD', 'NZD' ); ?>>🇳🇿 NZD - New Zealand Dollar (NZ$)</option>
				<option value="MXN" <?php selected( $settings['currency'] ?? 'USD', 'MXN' ); ?>>🇲🇽 MXN - Mexican Peso ($)</option>
				<option value="SGD" <?php selected( $settings['currency'] ?? 'USD', 'SGD' ); ?>>🇸🇬 SGD - Singapore Dollar (S$)</option>
				<option value="HKD" <?php selected( $settings['currency'] ?? 'USD', 'HKD' ); ?>>🇭🇰 HKD - Hong Kong Dollar (HK$)</option>
				<option value="NOK" <?php selected( $settings['currency'] ?? 'USD', 'NOK' ); ?>>🇳🇴 NOK - Norwegian Krone (kr)</option>
				<option value="KRW" <?php selected( $settings['currency'] ?? 'USD', 'KRW' ); ?>>🇰🇷 KRW - South Korean Won (₩)</option>
				<option value="TRY" <?php selected( $settings['currency'] ?? 'USD', 'TRY' ); ?>>🇹🇷 TRY - Turkish Lira (₺)</option>
				<option value="RUB" <?php selected( $settings['currency'] ?? 'USD', 'RUB' ); ?>>🇷🇺 RUB - Russian Ruble (₽)</option>
				<option value="INR" <?php selected( $settings['currency'] ?? 'USD', 'INR' ); ?>>🇮🇳 INR - Indian Rupee (₹)</option>
				<option value="BRL" <?php selected( $settings['currency'] ?? 'USD', 'BRL' ); ?>>🇧🇷 BRL - Brazilian Real (R$)</option>
				<option value="ZAR" <?php selected( $settings['currency'] ?? 'USD', 'ZAR' ); ?>>🇿🇦 ZAR - South African Rand (R)</option>
				<option value="RON" <?php selected( $settings['currency'] ?? 'USD', 'RON' ); ?>>🇷🇴 RON - Romanian Leu</option>
				<option value="THB" <?php selected( $settings['currency'] ?? 'USD', 'THB' ); ?>>🇹🇭 THB - Thai Baht (฿)</option>
				</select>
				<small class="description">Choose your default currency for all bookings</small>
			</div>

			<div class="aiohm-form-group">
				<label class="aiohm-form-label">Plugin Language</label>
				<select name="aiohm_booking_settings[language]" class="aiohm-form-select">
				<option value="en" <?php selected( $settings['language'] ?? 'en', 'en' ); ?>>🇺🇸 English</option>
				<option value="ro" <?php selected( $settings['language'] ?? 'en', 'ro' ); ?>>🇷🇴 Română</option>
				<option value="es" <?php selected( $settings['language'] ?? 'en', 'es' ); ?>>🇪🇸 Español</option>
				<option value="fr" <?php selected( $settings['language'] ?? 'en', 'fr' ); ?>>🇫🇷 Français</option>
				</select>
				<small class="description">Select the language for the booking interface</small>
			</div>

			<div class="aiohm-form-group">
				<label class="aiohm-form-label">Timezone</label>
				<select name="aiohm_booking_settings[timezone]" class="aiohm-form-select">
				<!-- Europe -->
				<option value="Europe/Bucharest" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Bucharest' ); ?>>🇷🇴 Europe/Bucharest (GMT+2)</option>
				<option value="Europe/London" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/London' ); ?>>🇬🇧 Europe/London (GMT+0)</option>
				<option value="Europe/Paris" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Paris' ); ?>>🇫🇷 Europe/Paris (GMT+1)</option>
				<option value="Europe/Berlin" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Berlin' ); ?>>🇩🇪 Europe/Berlin (GMT+1)</option>
				<option value="Europe/Rome" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Rome' ); ?>>🇮🇹 Europe/Rome (GMT+1)</option>
				<option value="Europe/Madrid" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Madrid' ); ?>>🇪🇸 Europe/Madrid (GMT+1)</option>
				<option value="Europe/Amsterdam" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Amsterdam' ); ?>>🇳🇱 Europe/Amsterdam (GMT+1)</option>
				<option value="Europe/Zurich" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Zurich' ); ?>>🇨🇭 Europe/Zurich (GMT+1)</option>
				<option value="Europe/Vienna" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Vienna' ); ?>>🇦🇹 Europe/Vienna (GMT+1)</option>
				<option value="Europe/Stockholm" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Stockholm' ); ?>>🇸🇪 Europe/Stockholm (GMT+1)</option>
				<option value="Europe/Moscow" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Europe/Moscow' ); ?>>🇷🇺 Europe/Moscow (GMT+3)</option>
				
				<!-- Americas -->
				<option value="America/New_York" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/New_York' ); ?>>🇺🇸 America/New_York (GMT-5)</option>
				<option value="America/Los_Angeles" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Los_Angeles' ); ?>>🇺🇸 America/Los_Angeles (GMT-8)</option>
				<option value="America/Chicago" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Chicago' ); ?>>🇺🇸 America/Chicago (GMT-6)</option>
				<option value="America/Denver" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Denver' ); ?>>🇺🇸 America/Denver (GMT-7)</option>
				<option value="America/Toronto" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Toronto' ); ?>>🇨🇦 America/Toronto (GMT-5)</option>
				<option value="America/Vancouver" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Vancouver' ); ?>>🇨🇦 America/Vancouver (GMT-8)</option>
				<option value="America/Mexico_City" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Mexico_City' ); ?>>🇲🇽 America/Mexico_City (GMT-6)</option>
				<option value="America/Sao_Paulo" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Sao_Paulo' ); ?>>🇧🇷 America/Sao_Paulo (GMT-3)</option>
				<option value="America/Buenos_Aires" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'America/Buenos_Aires' ); ?>>🇦🇷 America/Buenos_Aires (GMT-3)</option>
				
				<!-- Asia -->
				<option value="Asia/Bangkok" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Bangkok' ); ?>>🇹🇭 Asia/Bangkok (GMT+7)</option>
				<option value="Asia/Tokyo" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Tokyo' ); ?>>🇯🇵 Asia/Tokyo (GMT+9)</option>
				<option value="Asia/Shanghai" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Shanghai' ); ?>>🇨🇳 Asia/Shanghai (GMT+8)</option>
				<option value="Asia/Hong_Kong" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Hong_Kong' ); ?>>🇭🇰 Asia/Hong_Kong (GMT+8)</option>
				<option value="Asia/Singapore" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Singapore' ); ?>>🇸🇬 Asia/Singapore (GMT+8)</option>
				<option value="Asia/Seoul" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Seoul' ); ?>>🇰🇷 Asia/Seoul (GMT+9)</option>
				<option value="Asia/Manila" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Manila' ); ?>>🇵🇭 Asia/Manila (GMT+8)</option>
				<option value="Asia/Jakarta" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Jakarta' ); ?>>🇮🇩 Asia/Jakarta (GMT+7)</option>
				<option value="Asia/Kuala_Lumpur" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Kuala_Lumpur' ); ?>>🇲🇾 Asia/Kuala_Lumpur (GMT+8)</option>
				<option value="Asia/Ho_Chi_Minh" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Ho_Chi_Minh' ); ?>>🇻🇳 Asia/Ho_Chi_Minh (GMT+7)</option>
				<option value="Asia/Kolkata" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Kolkata' ); ?>>🇮🇳 Asia/Kolkata (GMT+5:30)</option>
				<option value="Asia/Dubai" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Dubai' ); ?>>🇦🇪 Asia/Dubai (GMT+4)</option>
				<option value="Asia/Tel_Aviv" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Asia/Tel_Aviv' ); ?>>🇮🇱 Asia/Tel_Aviv (GMT+2)</option>
				
				<!-- Africa -->
				<option value="Africa/Cairo" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Africa/Cairo' ); ?>>🇪🇬 Africa/Cairo (GMT+2)</option>
				<option value="Africa/Johannesburg" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Africa/Johannesburg' ); ?>>🇿🇦 Africa/Johannesburg (GMT+2)</option>
				<option value="Africa/Lagos" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Africa/Lagos' ); ?>>🇳🇬 Africa/Lagos (GMT+1)</option>
				<option value="Africa/Casablanca" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Africa/Casablanca' ); ?>>🇲🇦 Africa/Casablanca (GMT+1)</option>
				
				<!-- Oceania -->
				<option value="Australia/Sydney" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Australia/Sydney' ); ?>>🇦🇺 Australia/Sydney (GMT+10)</option>
				<option value="Australia/Melbourne" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Australia/Melbourne' ); ?>>🇦🇺 Australia/Melbourne (GMT+10)</option>
				<option value="Australia/Perth" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Australia/Perth' ); ?>>🇦🇺 Australia/Perth (GMT+8)</option>
				<option value="Pacific/Auckland" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Pacific/Auckland' ); ?>>🇳🇿 Pacific/Auckland (GMT+12)</option>
				<option value="Pacific/Fiji" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Pacific/Fiji' ); ?>>🇫🇯 Pacific/Fiji (GMT+12)</option>
				<option value="Pacific/Honolulu" <?php selected( $settings['timezone'] ?? 'Europe/Bucharest', 'Pacific/Honolulu' ); ?>>🇺🇸 Pacific/Honolulu (GMT-10)</option>
				</select>
				<small class="description">Set the timezone for bookings and date calculations</small>
			</div>

			<div class="aiohm-form-group">
				<label class="aiohm-form-label">Date Format</label>
				<select name="aiohm_booking_settings[date_format]" class="aiohm-form-select">
				<option value="d/m/Y" <?php selected( $settings['date_format'] ?? 'd/m/Y', 'd/m/Y' ); ?>>DD/MM/YYYY (31/12/2023)</option>
				<option value="m/d/Y" <?php selected( $settings['date_format'] ?? 'd/m/Y', 'm/d/Y' ); ?>>MM/DD/YYYY (12/31/2023)</option>
				<option value="Y-m-d" <?php selected( $settings['date_format'] ?? 'd/m/Y', 'Y-m-d' ); ?>>YYYY-MM-DD (2023-12-31)</option>
				<option value="d.m.Y" <?php selected( $settings['date_format'] ?? 'd/m/Y', 'd.m.Y' ); ?>>DD.MM.YYYY (31.12.2023)</option>
				</select>
				<small class="description">Choose how dates are displayed throughout the booking system</small>
			</div>
			</div>
		</div>

		<div class="aiohm-form-actions">
			<?php submit_button( 'Save Global Settings', 'primary', 'save_booking_settings', false, array( 'class' => 'aiohm-btn aiohm-btn--primary aiohm-booking-settings-save-btn' ) ); ?>
			
			<div class="aiohm-reset-section" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
				<h4 style="color: #d63638; margin-bottom: 10px;">⚠️ Danger Zone</h4>
				<p style="margin-bottom: 15px; color: #666;">
					<strong>Reset Plugin Data:</strong> This will permanently delete all events, accommodations, bookings, and settings. 
					This action cannot be undone!
				</p>
				<button type="button" id="aiohm-reset-plugin-data" class="button button-secondary" 
						style="background: #d63638; color: white; border-color: #d63638;" 
						onclick="aiohm_confirm_reset_plugin_data()">
					🗑️ Reset All Plugin Data
				</button>
			</div>
		</div>
		</div>
	</div>
	<?php // End Global Booking Settings ?>


	</div> <!-- Close aiohm-settings-grid -->
	</div> <!-- Close aiohm-settings-section -->

	<input type="hidden" name="aiohm_booking_settings[module_order]" id="module_order_input" value="<?php echo esc_attr( implode( ',', $module_order ) ); ?>" />

	</form>
