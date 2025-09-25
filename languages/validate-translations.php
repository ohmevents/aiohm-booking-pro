<?php
/**
 * AIOHM Booking Pro - Translation Validation Script
 * 
 * This script validates that translations are working correctly
 * Run this from WordPress admin or via WP-CLI
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Test translation functionality
 */
function aiohm_booking_test_translations() {
    // Test basic translation
    $test_strings = array(
        'Accommodation',
        'Accommodations', 
        'Settings saved successfully!',
        'Booking Settings',
        'Room',
        'Villa',
        'Security verification failed. Please refresh the page and try again.',
        'Add New %s',
        'Total %s'
    );
    
    echo "<h2>AIOHM Booking Pro - Translation Test</h2>\n";
    echo "<p><strong>Current Locale:</strong> " . get_locale() . "</p>\n";
    echo "<p><strong>Text Domain:</strong> aiohm-booking-pro</p>\n";
    
    // Test if textdomain is loaded
    $loaded_textdomains = get_loaded_textdomain( 'aiohm-booking-pro' );
    echo "<p><strong>Textdomain Loaded:</strong> " . ( $loaded_textdomains ? 'Yes' : 'No' ) . "</p>\n";
    
    echo "<h3>Translation Test Results:</h3>\n";
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Original String</th><th>Translated String</th><th>Status</th></tr>\n";
    
    foreach ( $test_strings as $string ) {
        $translated = __( $string, 'aiohm-booking-pro' );
        $is_translated = ( $string !== $translated );
        $status = $is_translated ? '✅ Translated' : '⚪ Original';
        
        echo "<tr>";
        echo "<td>" . esc_html( $string ) . "</td>";
        echo "<td>" . esc_html( $translated ) . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Test sprintf translations
    echo "<h3>Sprintf Translation Test:</h3>\n";
    $sprintf_tests = array(
        array( 'Add New %s', 'Room' ),
        array( 'Total %s', 'Accommodations' ),
        array( '%s Details', 'Accommodation' ),
        array( 'Failed to update accommodation ID %d: %s', 123, 'Test error' )
    );
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Template</th><th>Result</th></tr>\n";
    
    foreach ( $sprintf_tests as $test ) {
        $template = array_shift( $test );
        $result = sprintf( __( $template, 'aiohm-booking-pro' ), ...$test );
        
        echo "<tr>";
        echo "<td>" . esc_html( $template ) . "</td>";
        echo "<td>" . esc_html( $result ) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Available translation files
    $languages_dir = plugin_dir_path( __FILE__ );
    $mo_files = glob( $languages_dir . '*.mo' );
    
    echo "<h3>Available Translation Files:</h3>\n";
    if ( ! empty( $mo_files ) ) {
        echo "<ul>\n";
        foreach ( $mo_files as $mo_file ) {
            $basename = basename( $mo_file );
            $size = size_format( filesize( $mo_file ) );
            $locale = '';
            
            // Extract locale from filename
            if ( preg_match( '/aiohm-booking-pro-([a-z]{2}_[A-Z]{2})\.mo/', $basename, $matches ) ) {
                $locale = ' (' . $matches[1] . ')';
            }
            
            echo "<li>" . esc_html( $basename ) . $locale . " - " . $size . "</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>No .mo files found.</p>\n";
    }
}

// For WP-CLI usage
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'aiohm-booking test-translations', 'aiohm_booking_test_translations' );
}

// For admin usage (only for administrators)
if ( is_admin() && current_user_can( 'manage_options' ) ) {
    add_action( 'wp_ajax_aiohm_test_translations', function() {
        ob_start();
        aiohm_booking_test_translations();
        $output = ob_get_clean();
        wp_die( $output );
    });
    
    // Add admin notice with test link
    add_action( 'admin_notices', function() {
        if ( get_current_screen()->id === 'toplevel_page_aiohm-booking-pro' ) {
            $test_url = admin_url( 'admin-ajax.php?action=aiohm_test_translations' );
            echo '<div class="notice notice-info">';
            echo '<p><strong>AIOHM Booking Pro Translations:</strong> ';
            echo '<a href="' . esc_url( $test_url ) . '" target="_blank">Test Translation Status</a></p>';
            echo '</div>';
        }
    });
}