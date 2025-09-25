<?php
/**
 * Help Template
 *
 * @package AIOHM Booking
 * @since  2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$current_user   = wp_get_current_user();
$user_email     = $current_user->user_email ?? '';
$site_url       = get_site_url();
$plugin_version = defined( 'AIOHM_BOOKING_VERSION' ) ? AIOHM_BOOKING_VERSION : '1.2.3';
$wp_version     = get_bloginfo( 'version' );
$php_version    = PHP_VERSION;
$settings       = class_exists( 'AIOHM_BOOKING_Settings' ) ? AIOHM_BOOKING_Settings::get_all() : array();

$enabled_modules  = array();
$disabled_modules = array();
$total_modules    = 0;

if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
	$registry    = AIOHM_BOOKING_Module_Registry::instance();
	$all_modules = $registry->get_all_modules();

	foreach ( $all_modules as $module_id => $module ) {
		++$total_modules;
		if ( method_exists( $module, 'is_enabled' ) && $module->is_enabled() ) {
			$enabled_modules[] = ucfirst( str_replace( '_', ' ', $module_id ) );
		} else {
			$disabled_modules[] = ucfirst( str_replace( '_', ' ', $module_id ) );
		}
	}
}

$enabled_count        = count( $enabled_modules );
$disabled_count       = count( $disabled_modules );
$enabled_modules_str  = $enabled_count ? implode( ', ', $enabled_modules ) : esc_html__( 'None', 'aiohm-booking-pro' );
$disabled_modules_str = $disabled_count ? implode( ', ', $disabled_modules ) : esc_html__( 'None', 'aiohm-booking-pro' );

// Helper function to get accommodation plural name.
function get_accommodation_plural_name() {
	$settings           = class_exists( 'AIOHM_BOOKING_Settings' ) ? AIOHM_BOOKING_Settings::get_all() : array();
	$accommodation_type = $settings['accommodation_type'] ?? 'room';
	return aiohm_booking_get_accommodation_plural_name( $accommodation_type );
}
?>

<div class="wrap aiohm-booking-admin">

	<div class="aiohm-booking-admin-header">
	<div class="aiohm-booking-admin-header-content">
		<div class="aiohm-booking-admin-logo">
		<img src="<?php echo esc_url( AIOHM_BOOKING_URL . 'assets/images/aiohm-booking-OHM_logo-black.svg' ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">
		</div>
		<div class="aiohm-booking-admin-header-text">
		<h1><?php esc_html_e( 'AIOHM Booking — Help & Support', 'aiohm-booking-pro' ); ?></h1>
		<p class="aiohm-booking-admin-tagline"><?php esc_html_e( 'Get help, support resources, and system information', 'aiohm-booking-pro' ); ?></p>
		</div>
	</div>
	</div>

	<div class="aiohm-booking-help-container">

	<div class="aiohm-booking-help-main aiohm-booking-help-responsive-main">

		<div class="aiohm-booking-help-card">
		<div class="aiohm-booking-help-card-header">
			<h3><?php esc_html_e( 'System Information', 'aiohm-booking-pro' ); ?></h3>
		</div>
		<div class="aiohm-booking-help-card-content">
			<p><strong><?php esc_html_e( 'Plugin Version:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $plugin_version ); ?></p>
			<p><strong><?php esc_html_e( 'WordPress Version:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $wp_version ); ?></p>
			<p><strong><?php esc_html_e( 'PHP Version:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $php_version ); ?></p>
			<p><strong><?php esc_html_e( 'Modules:', 'aiohm-booking-pro' ); ?></strong>
			<?php
			/* translators: %1$d: number of enabled modules, %2$d: number of disabled modules, %3$d: total number of modules */
			printf( esc_html__( '%1$d enabled • %2$d disabled • %3$d total', 'aiohm-booking-pro' ), esc_html( $enabled_count ), esc_html( $disabled_count ), esc_html( $total_modules ) );
			?>
			</p>
			<p><strong><?php esc_html_e( 'Enabled Modules:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $enabled_modules_str ); ?></p>
			<p><strong><?php esc_html_e( 'Disabled Modules:', 'aiohm-booking-pro' ); ?></strong> <?php echo esc_html( $disabled_modules_str ); ?></p>
		</div>
		</div>

		<div class="aiohm-booking-help-card">
		<div class="aiohm-booking-help-card-header">
			<h2><?php esc_html_e( 'Report Issue', 'aiohm-booking-pro' ); ?></h2>
		</div>
		<div class="aiohm-booking-help-card-content">
			<form id="aiohm-support-form">
			<?php wp_nonce_field( 'aiohm_send_support_request', 'aiohm_support_nonce' ); ?>
			<div class="aiohm-booking-help-support-form-section">
				<h3><?php esc_html_e( 'Request Type', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-booking-help-form-row">
				<select id="support-type" name="type" required>
					<option value=""><?php esc_html_e( 'Select request type...', 'aiohm-booking-pro' ); ?></option>
					<option value="bug"><?php esc_html_e( '🐛 Bug Report', 'aiohm-booking-pro' ); ?></option>
					<option value="feature"><?php esc_html_e( '✨ Feature Request', 'aiohm-booking-pro' ); ?></option>
					<option value="support"><?php esc_html_e( '🆘 Support Request', 'aiohm-booking-pro' ); ?></option>
					<option value="other"><?php esc_html_e( '❓ Other', 'aiohm-booking-pro' ); ?></option>
				</select>
				</div>
			</div>

			<!-- Title Section -->
			<div class="aiohm-booking-help-support-form-section">
				<h3><?php esc_html_e( 'Title', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-booking-help-form-row">
				<input type="text" id="support-title" name="title" placeholder="Brief description of the issue or feature..." required>
				</div>
			</div>

			<!-- Description Section -->
			<div class="aiohm-booking-help-support-form-section">
				<h4><?php esc_html_e( 'Detailed Description', 'aiohm-booking-pro' ); ?></h4>
				<div class="aiohm-booking-help-form-row">
				<textarea id="support-description" name="description" rows="6" required placeholder="For Bug Reports:
- What you were trying to do
- What happened instead
- Any error messages you saw
- Steps to reproduce the issue

For Feature Requests:
- What problem would this solve?
- How would it work?
- Who would benefit from this feature?
- Any examples or references?"></textarea>
				</div>
			</div>

			<!-- Debug Information Section -->
			<div class="aiohm-booking-help-support-form-section">
				<h4><?php esc_html_e( 'Debug Information', 'aiohm-booking-pro' ); ?></h4>
				<div class="aiohm-booking-help-form-row">
				<textarea id="debug-information" name="debug_info" rows="6" readonly><?php esc_html_e( 'This will be automatically filled when you click the "Collect Debug Information" button below.', 'aiohm-booking-pro' ); ?></textarea>
				</div>

				<div class="aiohm-booking-help-form-row aiohm-booking-help-checkbox-row aiohm-booking-help-checkbox-with-padding">
				<label class="aiohm-booking-help-checkbox-label">
					<input type="checkbox" id="include-debug-info" name="include_debug" checked>
					<?php esc_html_e( 'Include debug information with this report', 'aiohm-booking-pro' ); ?>
				</label>
				</div>

				<!-- Action Buttons -->
				<div class="aiohm-booking-help-form-actions-section">
				<div class="aiohm-booking-help-form-actions aiohm-booking-help-inline-buttons">
					<button id="collect-debug-info" type="button" class="button button-secondary aiohm-booking-help-debug-action-btn">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Collect Debug Info', 'aiohm-booking-pro' ); ?>
					</button>
					<button type="submit" class="button button-primary aiohm-booking-help-send-report-btn">
					<span class="dashicons dashicons-email-alt"></span>
					<?php esc_html_e( 'Send Support Report', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
				</div>

				<!-- Debug Output Area -->
				<div id="debug-output" class="aiohm-booking-help-debug-output-area">
				<h4><?php esc_html_e( 'Debug Information', 'aiohm-booking-pro' ); ?></h4>
				<textarea id="debug-text" rows="12" readonly></textarea>
				<div class="aiohm-booking-help-debug-actions-bottom">
					<button id="copy-debug-info" class="button button-secondary">
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Copy to Clipboard', 'aiohm-booking-pro' ); ?>
					</button>
					<button id="download-debug-info" class="button button-secondary">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Download as File', 'aiohm-booking-pro' ); ?>
					</button>
				</div>
				</div>
			</div>
			</form>
		</div>
		</div>

	</div>

	<aside class="aiohm-booking-help-sidebar">
		<!-- User Journey Card -->
		<div class="aiohm-booking-help-journey-card aiohm-booking-help-mb-5">
		<div class="aiohm-booking-help-journey-header">
			<h2><?php esc_html_e( 'Your AIOHM Booking Journey', 'aiohm-booking-pro' ); ?></h2>
			<p><?php esc_html_e( 'From basic setup to seamless booking experiences - your path to booking transformation', 'aiohm-booking-pro' ); ?></p>
		</div>

		<ol class="aiohm-booking-help-journey-steps">
			<li class="aiohm-booking-help-journey-step">
			<div class="aiohm-booking-help-step-content">
				<h3><?php esc_html_e( 'Installation & Setup', 'aiohm-booking-pro' ); ?></h3>
				<p><?php esc_html_e( 'Install plugin and configure basic settings.', 'aiohm-booking-pro' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ); ?>" class="aiohm-booking-help-step-button"><?php esc_html_e( 'Get Started', 'aiohm-booking-pro' ); ?></a>
			</div>
			</li>

			<li class="aiohm-booking-help-journey-step">
			<div class="aiohm-booking-help-step-content">
				<h3><?php esc_html_e( 'Choose Booking Mode', 'aiohm-booking-pro' ); ?></h3>
				<p><?php esc_html_e( 'Configure accommodation system for your business needs.', 'aiohm-booking-pro' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ); ?>" class="aiohm-booking-help-step-button"><?php esc_html_e( 'Configure', 'aiohm-booking-pro' ); ?></a>
			</div>
			</li>

			<li class="aiohm-booking-help-journey-step">
			<div class="aiohm-booking-help-step-content">
				<h3><?php esc_html_e( 'Set Up Inventory', 'aiohm-booking-pro' ); ?></h3>
				<p>
				<?php
				/* translators: %s: plural accommodation name (e.g. rooms, houses, etc.) */
				echo esc_html( sprintf( __( 'Configure %s, events, and capacity settings.', 'aiohm-booking-pro' ), get_accommodation_plural_name() ) );
				?>
				</p>
				<?php if ( ! empty( $settings['enable_accommodations'] ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-accommodations' ) ); ?>" class="aiohm-booking-help-step-button"><?php esc_html_e( 'Manage', 'aiohm-booking-pro' ); ?></a>
				<?php else : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-settings' ) ); ?>" class="aiohm-booking-help-step-button"><?php esc_html_e( 'Enable First', 'aiohm-booking-pro' ); ?></a>
				<?php endif; ?>
			</div>
			</li>

			<li class="aiohm-booking-help-journey-step">
			<div class="aiohm-booking-help-step-content">
				<h3><?php esc_html_e( 'Deploy Shortcodes', 'aiohm-booking-pro' ); ?></h3>
				<p><?php esc_html_e( 'Add booking shortcodes and manage reservations.', 'aiohm-booking-pro' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aiohm-booking-orders' ) ); ?>" class="aiohm-booking-help-step-button"><?php esc_html_e( 'Start Booking', 'aiohm-booking-pro' ); ?></a>
			</div>
			</li>
		</ol>
		</div>

		<!-- Translation Instructions -->
		<div class="aiohm-booking-help-card aiohm-booking-help-translation-card-spaced aiohm-booking-help-translation-card">
		<div class="aiohm-booking-help-card-header">
			<h2><span class="dashicons dashicons-translation"></span> <?php esc_html_e( 'Translation Instructions', 'aiohm-booking-pro' ); ?></h2>
		</div>
		<div class="aiohm-booking-help-card-content">
			<div class="aiohm-booking-help-translation-intro">
			<p><?php esc_html_e( 'Help make AIOHM Booking available in your language! Follow these simple steps to create a translation:', 'aiohm-booking-pro' ); ?></p>
			</div>

			<div class="aiohm-booking-help-translation-grid">
			<div class="aiohm-booking-help-translation-column">
				<h3><?php esc_html_e( 'Quick Translation Steps', 'aiohm-booking-pro' ); ?></h3>
				<ol class="aiohm-booking-help-translation-steps">
				<li>
					<strong><?php esc_html_e( 'Download Poedit (Free)', 'aiohm-booking-pro' ); ?></strong><br>
					<?php esc_html_e( 'Get the free translation tool from', 'aiohm-booking-pro' ); ?>
					<a href="https://poedit.net/" target="_blank" rel="noopener">poedit.net</a>
				</li>
				<li>
					<strong><?php esc_html_e( 'Locate the Template File', 'aiohm-booking-pro' ); ?></strong><br>
					<code><?php echo esc_html( plugin_dir_path( __DIR__ ) . 'languages/aiohm-booking.pot' ); ?></code>
				</li>
				<li>
					<strong><?php esc_html_e( 'Create Your Language File', 'aiohm-booking-pro' ); ?></strong><br>
					<?php esc_html_e( 'Copy and rename to your language code, e.g.,', 'aiohm-booking-pro' ); ?>
					<code>aiohm-booking-de_DE.po</code> <?php esc_html_e( 'for German', 'aiohm-booking-pro' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Translate in Poedit', 'aiohm-booking-pro' ); ?></strong><br>
					<?php esc_html_e( 'Open your .po file and translate all text strings', 'aiohm-booking-pro' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Save & Upload', 'aiohm-booking-pro' ); ?></strong><br>
					<?php esc_html_e( 'Poedit creates both .po and .mo files. Upload both to the languages folder', 'aiohm-booking-pro' ); ?>
				</li>
				</ol>
			</div>

			<div class="aiohm-booking-help-translation-column">
				<h3><?php esc_html_e( 'Language Code Examples', 'aiohm-booking-pro' ); ?></h3>
				<div class="aiohm-booking-help-language-examples">
				<div class="aiohm-booking-help-lang-example">
					<strong>🇩🇪 German:</strong> <code>de_DE</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇫🇷 French:</strong> <code>fr_FR</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇪🇸 Spanish:</strong> <code>es_ES</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇮🇹 Italian:</strong> <code>it_IT</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇵🇹 Portuguese:</strong> <code>pt_BR</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇳🇱 Dutch:</strong> <code>nl_NL</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇷🇴 Romanian:</strong> <code>ro_RO</code>
				</div>
				<div class="aiohm-booking-help-lang-example">
					<strong>🇯🇵 Japanese:</strong> <code>ja</code>
				</div>
				</div>
			</div>
			</div>

			<div class="aiohm-booking-help-translation-footer">
			<div class="aiohm-booking-help-translation-tip">
				<span class="dashicons dashicons-lightbulb"></span>
				<strong><?php esc_html_e( 'Pro Tip:', 'aiohm-booking-pro' ); ?></strong>
				<?php esc_html_e( 'You can test your translation immediately by changing your WordPress language in Settings → General.', 'aiohm-booking-pro' ); ?>
			</div>
			</div>
		</div>
		</div>

	</aside>
	</div>

	<!-- Success/Error Messages -->
	<div id="support-messages" class="aiohm-booking-help-support-messages"></div>
</div>

<!-- Hidden fields for system information -->
<input type="hidden" id="system-info" value="
<?php
echo esc_attr(
	wp_json_encode(
		array(
			'plugin_version'  => $plugin_version,
			'wp_version'      => $wp_version,
			'php_version'     => $php_version,
			'site_url'        => $site_url,
			'enabled_modules' => $enabled_modules_str,
			'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'Unknown',
		)
	)
);
?>
">
