<?php
/**
 * Sandwich Template Header Component
 *
 * Displays the header section of the unified booking form with:
 * - Brand color background and styling
 * - Dynamic CSS variables for color theming
 * - Clean header bar without custom title/subtitle
 *
 * @package AIOHM_Booking_PRO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get shared form settings based on context, but use unified brand color
if ( isset( $is_events_context ) && $is_events_context ) {
	$form_settings = get_option( 'aiohm_booking_tickets_form_settings', array() );
} else {
	$form_settings = get_option( 'aiohm_booking_form_settings', array() );
}

// Get unified brand color from main settings (shared across all contexts)
$main_settings       = get_option( 'aiohm_booking_settings', array() );
$unified_brand_color = $main_settings['brand_color'] ?? $main_settings['form_primary_color'] ?? null;

// Extract header data with fallbacks - use unified color if available
$brand_color = $unified_brand_color ?? $form_settings['form_primary_color'] ?? '#457d59';
$text_color  = $form_settings['form_text_color'] ?? '#ffffff';

// Ensure we have content to display.
$show_header = true; // Header enabled with brand colors
?>

<?php if ( $show_header ) : ?>
	<?php
	// Enqueue consolidated shortcodes CSS (includes sandwich header styles)
	wp_enqueue_style(
		'aiohm-booking-shortcodes',
		AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-shortcodes.css',
		array(),
		AIOHM_BOOKING_VERSION
	);

	// Add inline style for dynamic color variables
	$dynamic_css = "
:root {
	--aiohm-brand-color: {$brand_color};
	--aiohm-text-color: {$text_color};
}
.aiohm-booking-event-card:hover {
	border-color: {$brand_color};
}
.aiohm-booking-current-price {
	color: {$brand_color};
}
";
	wp_add_inline_style( 'aiohm-booking-sandwich-header', $dynamic_css );
	?>

<div class="aiohm-booking-sandwich-header" style="--aiohm-brand-color: <?php echo esc_attr( $brand_color ); ?>; --aiohm-text-color: <?php echo esc_attr( $text_color ); ?>;">
	<div class="aiohm-booking-header-content">
		<!-- Header bar with brand colors - custom title/subtitle removed -->
	</div>
</div>
<?php endif; ?>