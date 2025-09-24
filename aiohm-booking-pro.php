<?php

/**
 * Main plugin file for AIOHM Booking
 *
 * @package AIOHM_Booking_PRO
 */
// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: AIOHM Booking Pro
 * Plugin URI:  https://wordpress.org/plugins/aiohm-booking/
 * Description: Professional event booking and accommodation management system. Streamlined booking experience for events and accommodations with secure Stripe payments and comprehensive utilities.
 * Version:     2.0.3
 * Author:      OHM Events Agency
 * Author URI:  https://www.ohm.events
 * Text Domain: aiohm-booking-pro
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @fs_premium_only /includes/modules/payments/stripe/
 *
 * @package AIOHM_Booking_PRO
 */
/**
 * Main plugin file for AIOHM Booking
 * Handles accommodation booking with rooms and deposit management
 *
 * @package AIOHM_Booking_PRO
 * @since   1.2.6
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'aiohm_booking_fs' ) ) {
    aiohm_booking_fs()->set_basename( true, __FILE__ );
} else {
    /**
     * DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE
     * `function_exists` CALL ABOVE TO PROPERLY WORK.
     */
    if ( !function_exists( 'aiohm_booking_fs' ) ) {
        /**
         * Create a helper function for easy SDK access.
         */
        function aiohm_booking_fs() {
            global $aiohm_booking_fs;

            if ( ! isset( $aiohm_booking_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                $aiohm_booking_fs = fs_dynamic_init( array(
                    'id'                  => '20270',
                    'slug'                => 'aiohm-booking',
                    'premium_slug'        => 'aiohm-booking-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_e8bc3aa6b961943f254a79f14b973',
                    'is_premium'          => true,
                    'premium_suffix'      => 'PRO',
                    // If your plugin is a serviceware, set this option to false.
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    // Automatically removed in the free version. If you're not using the
                    // auto-generated free version, delete this line before uploading to wp.org.
                    'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                    'menu'                => array(
                        'slug'           => 'aiohm-booking-pro',
                        'first-path'     => 'admin.php?page=aiohm-booking-pro',
                        'contact'        => false,
                        'support'        => false,
                    ),
                ) );
            }

            return $aiohm_booking_fs;
        }

        // Init Freemius.
        aiohm_booking_fs();
        // Signal that SDK was initiated.
        do_action( 'aiohm_booking_fs_loaded' );
        
        // Auto-skip opt-in if not already done to prevent blocking modals
        if ( ! aiohm_booking_fs()->is_registered() && ! aiohm_booking_fs()->is_pending_activation() ) {
            aiohm_booking_fs()->skip_connection();
        }
        
        // Prevent blocking modals but keep pricing functionality
        aiohm_booking_fs()->add_filter( 'show_first_time_trial_promotion', '__return_false' );
        aiohm_booking_fs()->add_filter( 'enable_anonymous_functionality', '__return_true' );
        aiohm_booking_fs()->add_filter( 'show_opt_in', '__return_false' );
        aiohm_booking_fs()->add_filter( 'enable_blocking_mode', '__return_false' );
        
        // Add custom styling for Freemius pricing page
        add_action( 'admin_head', function() {
            $screen = get_current_screen();
            if ( $screen && strpos( $screen->id, 'aiohm-booking-pro-pricing' ) !== false ) {
                ?>
                <style>
                :root {
                    --ohm-primary: #457d59;
                    --ohm-primary-light: #5d9671;
                    --ohm-primary-dark: #2d5a40;
                    --ohm-secondary: #2c5aa0;
                    --ohm-white: #ffffff;
                    --ohm-gray-50: #f9fafb;
                    --ohm-gray-100: #f3f4f6;
                    --ohm-gray-200: #e5e7eb;
                    --ohm-gray-300: #d1d5db;
                    --ohm-gray-600: #4b5563;
                    --ohm-gray-800: #1f2937;
                }

                /* Override Freemius pricing page colors */
                #fs_pricing_wrapper,
                #fs_pricing_app {
                    --fs-ds-theme-primary-accent-color: var(--ohm-primary) !important;
                    --fs-ds-theme-primary-accent-color-hover: var(--ohm-primary-dark) !important;
                    --fs-ds-theme-button-primary-background-color: var(--ohm-primary) !important;
                    --fs-ds-theme-button-primary-background-hover-color: var(--ohm-primary-dark) !important;
                    --fs-ds-theme-button-primary-border-color: var(--ohm-primary-dark) !important;
                    --fs-ds-theme-button-primary-border-hover-color: var(--ohm-primary-dark) !important;
                }

                /* Add consistent header styling like other plugin pages */
                .wrap.fs-full-size-wrapper {
                    margin-top: 0 !important;
                }
                
                .wrap.fs-full-size-wrapper::before {
                    content: '';
                    display: block;
                    background: var(--ohm-white);
                    border-bottom: 1px solid var(--ohm-gray-200);
                    margin: 0 -20px 20px;
                    padding: 0;
                }
                
                .wrap.fs-full-size-wrapper .aiohm-booking-admin-header {
                    background: var(--ohm-white);
                    border-bottom: 1px solid var(--ohm-gray-200);
                    margin: 0 -20px 20px;
                    padding: 20px;
                }
                
                .wrap.fs-full-size-wrapper .aiohm-booking-admin-header-content {
                    display: flex;
                    align-items: center;
                    max-width: 1200px;
                    margin: 0 auto;
                }
                
                .wrap.fs-full-size-wrapper .aiohm-booking-admin-logo {
                    margin-right: 20px;
                }
                
                .wrap.fs-full-size-wrapper .aiohm-booking-admin-header-logo {
                    width: 48px;
                    height: 48px;
                }
                
                .wrap.fs-full-size-wrapper .aiohm-booking-admin-header-text h1 {
                    color: var(--ohm-gray-800) !important;
                    font-size: 24px !important;
                    font-weight: 600 !important;
                    margin: 0 0 5px !important;
                    line-height: 1.2 !important;
                }
                
                .wrap.fs-full-size-wrapper .aiohm-booking-admin-tagline {
                    color: var(--ohm-gray-600) !important;
                    font-size: 14px !important;
                    margin: 0 !important;
                    font-weight: 400 !important;
                }

                /* Style the pricing cards */
                #fs_pricing_wrapper .fs-package-card {
                    border-radius: 12px !important;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
                    transition: transform 0.3s ease, box-shadow 0.3s ease !important;
                    background: var(--ohm-white) !important;
                }

                #fs_pricing_wrapper .fs-package-card:hover {
                    transform: translateY(-5px) !important;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
                }

                /* Style the popular plan */
                #fs_pricing_wrapper .fs-package-card.fs-popular {
                    border: 3px solid var(--ohm-primary) !important;
                    transform: scale(1.02) !important;
                }

                #fs_pricing_wrapper .fs-package-card.fs-popular::before {
                    content: 'Most Popular';
                    position: absolute;
                    top: -12px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: var(--ohm-primary);
                    color: var(--ohm-white);
                    padding: 6px 20px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    z-index: 10;
                }

                /* Style pricing buttons */
                #fs_pricing_wrapper .fs-button-primary {
                    background: var(--ohm-primary) !important;
                    border-color: var(--ohm-primary-dark) !important;
                    border-radius: 8px !important;
                    padding: 12px 24px !important;
                    font-weight: 600 !important;
                    transition: all 0.3s ease !important;
                }

                #fs_pricing_wrapper .fs-button-primary:hover {
                    background: var(--ohm-primary-dark) !important;
                    border-color: var(--ohm-primary-dark) !important;
                    transform: translateY(-2px) !important;
                    box-shadow: 0 5px 15px rgba(69, 125, 89, 0.4) !important;
                }

                /* Style price amounts and currency */
                #fs_pricing_wrapper .fs-price .fs-amount {
                    color: var(--ohm-primary) !important;
                    font-weight: 700 !important;
                }
                
                /* Style price amounts */
                #fs_pricing_wrapper .fs-price .fs-amount {
                    color: var(--ohm-primary) !important;
                    font-weight: 700 !important;
                }

                /* Style plan titles */
                #fs_pricing_wrapper .fs-package-title {
                    color: var(--ohm-gray-800) !important;
                    font-weight: 700 !important;
                }

                /* Style feature lists */
                #fs_pricing_wrapper .fs-package-features li {
                    color: var(--ohm-gray-600) !important;
                }

                #fs_pricing_wrapper .fs-package-features li::before {
                    content: '✓';
                    background: #27ae60;
                    color: var(--ohm-white);
                    width: 16px;
                    height: 16px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    font-weight: 600;
                    margin-right: 8px;
                }

                /* Style FAQ section headers with OHM green background */
                #fs_pricing_wrapper .fs-section--faq-item h3,
                #fs_pricing_wrapper .fs-section.fs-section--faq-item h3 {
                    background: var(--ohm-primary) !important;
                    color: var(--ohm-white) !important;
                    font-weight: 600 !important;
                    padding: 15px 20px !important;
                    margin: 0 !important;
                    border-radius: 8px !important;
                }

                /* Replace plugin logo with OHM logo */
                #fs_pricing_wrapper .fs-plugin-logo,
                #fs_pricing_wrapper object.fs-plugin-logo,
                #fs_pricing_wrapper img.fs-plugin-logo {
                    display: none !important;
                }
                
                #fs_pricing_wrapper .fs-plugin-title-and-logo::before,
                #fs_pricing_wrapper .fs-app-header .fs-plugin-title-and-logo::before {
                    content: '';
                    display: inline-block;
                    width: 64px;
                    height: 64px;
                    background-image: url('<?php echo esc_url( AIOHM_BOOKING_URL . "assets/images/aiohm-booking-OHM_logo-black.svg" ); ?>');
                    background-size: contain;
                    background-repeat: no-repeat;
                    background-position: center;
                    vertical-align: middle;
                    margin-right: 15px;
                }

                /* Hide Freemius branding if needed */
                #fs_pricing_wrapper .fs-powered-by {
                    display: none !important;
                }

                /* Hide only custom guarantee sections, keep Freemius 7-day guarantee */
                #fs_pricing_wrapper .fs-section:last-child::after {
                    display: none !important;
                }
                
                #fs_pricing_wrapper::after {
                    display: none !important;
                }

                /* Responsive adjustments */
                @media (max-width: 768px) {
                    #fs_pricing_wrapper::after {
                        font-size: 20px;
                        padding: 30px 15px;
                    }
                    
                    #fs_pricing_wrapper .fs-package-card.fs-popular {
                        transform: none !important;
                    }
                }
                </style>
                
                <script>
                jQuery(document).ready(function($) {
                    // Add consistent header to pricing page
                    var headerHTML = '<div class="aiohm-booking-admin-header">' +
                        '<div class="aiohm-booking-admin-header-content">' +
                            '<div class="aiohm-booking-admin-logo">' +
                                '<img src="<?php echo esc_url( AIOHM_BOOKING_URL . "assets/images/aiohm-booking-OHM_logo-black.svg" ); ?>" alt="AIOHM" class="aiohm-booking-admin-header-logo">' +
                            '</div>' +
                            '<div class="aiohm-booking-admin-header-text">' +
                                '<h1>Upgrade to AIOHM Booking Pro</h1>' +
                                '<p class="aiohm-booking-admin-tagline">Unlock premium features with secure payments and AI analytics</p>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                    
                    // Insert header at the beginning of the pricing wrapper
                    if ($('#fs_pricing_wrapper').length > 0) {
                        $('#fs_pricing_wrapper').prepend(headerHTML);
                    } else if $('.wrap.fs-full-size-wrapper').length > 0) {
                        $('.wrap.fs-full-size-wrapper').prepend(headerHTML);
                    }
                    
                    // Also try after a delay in case elements load later
                    setTimeout(function() {
                        if ($('.aiohm-booking-admin-header').length === 0) {
                            if ($('#fs_pricing_wrapper').length > 0) {
                                $('#fs_pricing_wrapper').prepend(headerHTML);
                            } else if $('.wrap.fs-full-size-wrapper').length > 0) {
                                $('.wrap.fs-full-size-wrapper').prepend(headerHTML);
                            }
                        }
                    }, 1000);
                });
                </script>
                
                <?php
            }
        });
        // Clear module cache when license status changes
        aiohm_booking_fs()->add_action( 'after_license_change', function () {
            if ( class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry' ) ) {
                AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
            }
        } );
        // Clear module cache after account connection.
        aiohm_booking_fs()->add_action( 'after_account_connection', function () {
            if ( class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry' ) ) {
                AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
            }
        } );
        // Clear module cache on init to ensure fresh discovery.
        add_action( 'init', function () {
            if ( class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry' ) ) {
                AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
            }
            // Handle Stripe payment return URLs.
            if ( isset( $_GET['aiohm_booking_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public return URL from payment processor
                $action = sanitize_text_field( wp_unslash( $_GET['aiohm_booking_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public return URL validation
                if ( 'success' === $action && isset( $_GET['session_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Stripe session validation
                    // Handle successful payment return from Stripe.
                    add_action( 'wp', function () {
                        if ( !is_admin() ) {
                            include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-success.php';
                            exit;
                        }
                    } );
                } elseif ( 'cancelled' === $action ) {
                    // Handle cancelled payment return from Stripe.
                    add_action( 'wp', function () {
                        if ( !is_admin() ) {
                            include AIOHM_BOOKING_DIR . 'templates/aiohm-booking-cancelled.php';
                            exit;
                        }
                    } );
                }
            }
        }, 5 );

        // Add custom license activation message with purchase link
        add_filter( 'fs_message_above_input_field_aiohm-booking', function( $message ) {
            if ( function_exists( 'aiohm_booking_fs' ) && ! aiohm_booking_fs()->is_paying() ) {
                $upgrade_url = 'https://checkout.freemius.com/plugin/20270/plan/33657/';
                $message .= '<br><br><div style="background: #f0f8ff; border: 1px solid #b3d9ff; padding: 10px; border-radius: 4px; margin-top: 10px;">';
                $message .= '<strong>' . esc_html__( 'Don\'t have a license yet?', 'aiohm-booking-pro' ) . '</strong><br>';
                $message .= esc_html__( 'Purchase a Pro license to unlock all premium features including Stripe payments.', 'aiohm-booking-pro' );
                $message .= '<br><a href="' . esc_url( $upgrade_url ) . '" target="_blank" style="color: #007cba; text-decoration: none; font-weight: bold; margin-top: 5px; display: inline-block;">' . esc_html__( 'Buy Pro License →', 'aiohm-booking-pro' ) . '</a>';
                $message .= '</div>';
            }
            return $message;
        } );

        // Hook uninstall cleanup to Freemius after_uninstall action.
        aiohm_booking_fs()->add_action( 'after_uninstall', 'aiohm_booking_fs_uninstall_cleanup' );
        // Set up activation redirect to Dashboard.
        aiohm_booking_fs()->add_filter( 'connect_url', 'aiohm_booking_fs_custom_connect_url' );
        aiohm_booking_fs()->add_filter( 'after_skip_url', 'aiohm_booking_fs_after_skip_url' );
        aiohm_booking_fs()->add_filter( 'after_connect_url', 'aiohm_booking_fs_after_connect_url' );
        aiohm_booking_fs()->add_filter( 'after_pending_connect_url', 'aiohm_booking_fs_after_pending_connect_url' );
        /**
         * Custom connect URL to prevent external redirects
         *
         * @return string The admin URL for the plugin dashboard.
         */
        function aiohm_booking_fs_custom_connect_url() {
            return admin_url( 'admin.php?page=aiohm-booking-pro' );
        }

        /**
         * Get the URL to redirect to after skipping Freemius setup.
         *
         * @return string The admin URL for the plugin dashboard.
         */
        function aiohm_booking_fs_after_skip_url() {
            return admin_url( 'admin.php?page=aiohm-booking-pro' );
        }

        /**
         * Get the URL to redirect to after connecting Freemius account.
         *
         * @return string The admin URL for the plugin dashboard.
         */
        function aiohm_booking_fs_after_connect_url() {
            return admin_url( 'admin.php?page=aiohm-booking-pro' );
        }

        /**
         * Get the URL to redirect to after pending connection to Freemius.
         *
         * @return string The admin URL for the plugin dashboard.
         */
        function aiohm_booking_fs_after_pending_connect_url() {
            return admin_url( 'admin.php?page=aiohm-booking-pro' );
        }

    }
}
define( 'AIOHM_BOOKING_VERSION', '2.0.3' );
define( 'AIOHM_BOOKING_FILE', __FILE__ );
define( 'AIOHM_BOOKING_DIR', __DIR__ . '/' );
define( 'AIOHM_BOOKING_URL', plugins_url( '', __FILE__ ) . '/' );
/**
 * Check if a payment module exists and is available
 *
 * This function allows making payment integrations truly modular.
 * If you remove a payment module folder, all references to it disappear.
 *
 * @param string $module_name Module name (e.g., 'stripe').
 * @return bool True if module file exists
 */
function aiohm_booking_payment_module_exists(  $module_name  ) {
    // Payment modules are always available - premium checks happen at runtime.
    // Manual override: if user is paying but not detected as premium, allow payment modules.
    if ( in_array( $module_name, array('stripe'), true ) ) {
        if ( function_exists( 'aiohm_booking_fs' ) ) {
            $fs = aiohm_booking_fs();
            // If user is paying (has paid license) but not detected as premium, allow access.
            if ( $fs->is_paying() ) {
                return true;
            }
        }
    }
    // Use the new conditional loading system.
    if ( class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Utilities' ) ) {
        return \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Utilities::is_module_available( $module_name );
    }
    // Fallback for backwards compatibility during initialization.
    $new_module_file = AIOHM_BOOKING_DIR . "includes/modules/payments/{$module_name}/class-aiohm-booking-module-{$module_name}.php";
    if ( file_exists( $new_module_file ) ) {
        return true;
    }
    $old_module_file = AIOHM_BOOKING_DIR . "includes/modules/payments/class-aiohm-booking-module-{$module_name}.php";
    return file_exists( $old_module_file );
}

// phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Initialize plugin components
 *
 * @package AIOHM_Booking_PRO
 * @since   1.2.6
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events */
class AIOHM_Booking {
    /**
     * Singleton instance
     *
     * @var AIOHM_Booking
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return AIOHM_Booking
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize plugin hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array($this, 'init_components') );
        add_action( 'init', array($this, 'load_textdomain') );
        add_action( 'plugins_loaded', array($this, 'increase_memory_limit') );
        add_action( 'admin_init', array($this, 'check_database_tables') );
        add_action( 'send_headers', array($this, 'add_security_headers') );
        // Register activation and deactivation hooks.
        register_activation_hook( __FILE__, array($this, 'activate') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );
    }

    /**
     * Add security headers to all responses
     */
    public function add_security_headers() {
        if ( class_exists( 'AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Security_Config' ) ) {
            $headers = \AIOHM_Booking_PRO\Core\AIOHM_Booking_PROCoreAIOHM_Booking_PROCoreAIOHM_BOOKING_Security_Config::get_security_headers();
            foreach ( $headers as $header => $value ) {
                header( $header . ': ' . $value );
            }
        }
    }

    /**
     * Load plugin text domain for translations
     * Note: WordPress automatically loads translations from wp-content/languages/plugins/
     */
    public function load_textdomain() {
        // Load translations based on plugin language setting.
        $settings = get_option( 'aiohm_booking_settings', array() );
        $plugin_language = $settings['plugin_language'] ?? 'en';
        // Only apply custom locale if not English.
        if ( 'en' !== $plugin_language ) {
            add_filter( 'locale', array($this, 'set_plugin_locale') );
            add_filter(
                'plugin_locale',
                array($this, 'set_plugin_locale'),
                10,
                2
            );
        }
        // WordPress automatically loads plugin textdomains since 4.6.
        // The textdomain is loaded from the /languages/ directory automatically.
    }

    /**
     * Set custom locale for plugin translations
     *
     * @param string $locale Current locale.
     * @return string Modified locale.
     */
    public function set_plugin_locale( $locale ) {
        $settings = get_option( 'aiohm_booking_settings', array() );
        $plugin_language = $settings['plugin_language'] ?? 'en';
        if ( 'ro' === $plugin_language ) {
            return 'ro_RO';
        }
        return $locale;
    }

    /**
     * Increase memory limit for complex operations like calendar rendering.
     * This is hooked to 'plugins_loaded' to run at an appropriate time.
     */
    public function increase_memory_limit() {
        if ( function_exists( 'ini_set' ) ) {
            $current_limit_hr = ini_get( 'memory_limit' );
            $current_limit_bytes = wp_convert_hr_to_bytes( $current_limit_hr );
            $wp_memory_limit_bytes = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
            // Increase to at least 256MB if both current and WP limits are lower.
            // Respects higher limits set in wp-config.php.
            if ( $current_limit_bytes < 268435456 && $wp_memory_limit_bytes < 268435456 ) {
                // 256MB
                wp_raise_memory_limit();
            }
        }
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        require_once AIOHM_BOOKING_DIR . 'vendor/autoload.php';
        // Migrate settings if needed.
        $this->migrate_settings();
        // Initialize core components needed for dashboard.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Admin' ) ) {
            \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Admin::init();
        }
        // Initialize assets management.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Assets' ) ) {
            new \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Assets();
        }
        // Initialize module registry.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Module_Registry' ) ) {
            \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Module_Registry::instance();
        }
        // Initialize field renderer factory.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_Booking_Field_Renderer_Factory' ) ) {
            \AIOHM_Booking_PRO\Core\AIOHM_Booking_Field_Renderer_Factory::init();
        }
        // Initialize REST API.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_REST_API' ) ) {
            \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_REST_API::init();
        }
        // Initialize error handling.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Error_Handler' ) ) {
            \AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Error_Handler::init();
        }
        // Initialize enhanced security.
        if ( class_exists( '\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Security_Config' ) ) {
            add_action( 'init', array('\AIOHM_Booking_PRO\Core\AIOHM_BOOKING_Security_Config', 'init_security') );
        }
        // Initialize upsells for free version.
        if ( class_exists( '\AIOHM_Booking_PRO\Admin\AIOHM_BOOKING_Upsells' ) ) {
            \AIOHM_Booking_PRO\Admin\AIOHM_BOOKING_Upsells::init();
        }
    }

    /**
     * Migrate old settings to new format
     */
    private function migrate_settings() {
        $settings = get_option( 'aiohm_booking_settings', array() );
        $updated = false;
        // Migrate enable_accommodation to enable_accommodations.
        if ( isset( $settings['enable_accommodation'] ) && !isset( $settings['enable_accommodations'] ) ) {
            $settings['enable_accommodations'] = $settings['enable_accommodation'];
            unset($settings['enable_accommodation']);
            $updated = true;
        }
        // Remove deprecated Google Calendar and Maps settings (v1.2.6).
        $deprecated_settings = array(
            'google_calendar_enabled',
            'google_calendar_id',
            'maps_default_zoom',
            'maps_default_location'
        );
        foreach ( $deprecated_settings as $deprecated_setting ) {
            if ( isset( $settings[$deprecated_setting] ) ) {
                unset($settings[$deprecated_setting]);
                $updated = true;
            }
        }
        if ( $updated ) {
            update_option( 'aiohm_booking_settings', $settings );
        }
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Load helper functions first.
        require_once AIOHM_BOOKING_DIR . 'includes/helpers/aiohm-booking-functions.php';
        require_once AIOHM_BOOKING_DIR . 'includes/helpers/class-aiohm-booking-early-bird-helper.php';
        // Load abstract classes.
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-module.php';
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-settings-module.php';
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-payment-module.php';
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/interface-aiohm-booking-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-field-renderer.php';
        // Load core classes.
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-admin.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-assets.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-ajax-response.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-rest-api.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-security-config.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-security-helper.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-settings.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-module-registry.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-template-helper.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-form.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-form-settings-handler.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-validation.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-error-handler.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-module-error-handler.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-accommodation-counter.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-date-range-validator.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-calendar-rules.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-module-settings-manager.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-utilities.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-checkout-ajax.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-accommodation-service.php';
        // Load field renderer system.
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-text-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-number-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-checkbox-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-select-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-textarea-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-hidden-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-radio-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-color-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/field-renderers/class-custom-field-renderer.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-field-renderer-factory.php';
        // Load admin handler classes.
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-admin-menu.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-admin-ajax.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-admin-settings.php';
        require_once AIOHM_BOOKING_DIR . 'includes/core/class-aiohm-booking-admin-modules.php';
        require_once AIOHM_BOOKING_DIR . 'includes/admin/class-aiohm-booking-upsells.php';
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables.
        $this->create_database_tables();
        // Set default settings if not already set.
        $settings = get_option( 'aiohm_booking_settings', array() );
        if ( empty( $settings ) ) {
            $default_settings = array(
                'enable_accommodations' => true,
                'enable_notifications'  => true,
                'enable_tickets'        => true,
                'enable_orders'         => true,
                'enable_calendar'       => true,
                'enable_css_manager'    => true,
                'currency'              => 'RON',
                'currency_position'     => 'before',
                'decimal_separator'     => '.',
                'thousand_separator'    => ',',
                'plugin_language'       => 'en',
                'deposit_percentage'    => 0,
                'min_age'               => 18,
            );
            update_option( 'aiohm_booking_settings', $default_settings );
        }
        // Basic activation tasks.
        flush_rewrite_rules();
        // Set transient to redirect to dashboard after activation..
        set_transient( 'aiohm_booking_activation_redirect', true, 30 );
    }

    /**
     * Create all required database tables
     */
    private function create_database_tables() {
        // Create orders table.
        if ( class_exists( '\AIOHM_Booking_PRO\Modules\Booking\AIOHM_BOOKING_Module_Orders' ) && method_exists( '\AIOHM_Booking_PRO\Modules\Booking\AIOHM_BOOKING_Module_Orders', 'on_activation' ) ) {
            \AIOHM_Booking_PRO\Modules\Booking\AIOHM_BOOKING_Module_Orders::on_activation();
        }
        // Create calendar data table.
        if ( class_exists( '\AIOHM_Booking_PRO\Modules\Booking\AIOHM_BOOKING_Module_Calendar' ) && method_exists( '\AIOHM_Booking_PRO\Modules\Booking\AIOHM_BOOKING_Module_Calendar', 'on_activation' ) ) {
            \AIOHM_Booking_PRO\Modules\Booking\AIOHM_BOOKING_Module_Calendar::on_activation();
        }
        // Create email logs table.
        if ( class_exists( '\AIOHM_Booking_PRO\Modules\Notifications\AIOHM_BOOKING_Module_Notifications' ) && method_exists( '\AIOHM_Booking_PRO\Modules\Notifications\AIOHM_BOOKING_Module_Notifications', 'on_activation' ) ) {
            \AIOHM_Booking_PRO\Modules\Notifications\AIOHM_BOOKING_Module_Notifications::on_activation();
        }
    }

    /**
     * Ensure database tables exist (for upgrades or manual creation)
     */
    public static function ensure_database_tables() {
        $instance = self::get_instance();
        $instance->create_database_tables();
    }

    /**
     * Check if database tables exist and create them if missing
     */
    public function check_database_tables() {
        global $wpdb;
        $tables_to_check = array($wpdb->prefix . 'aiohm_booking_order', $wpdb->prefix . 'aiohm_booking_calendar_data', $wpdb->prefix . 'aiohm_booking_email_logs');
        $missing_tables = array();
        foreach ( $tables_to_check as $table ) {
            $table_exists = wp_cache_get( 'aiohm_booking_table_exists_' . $table, 'aiohm_booking' );
            if ( false === $table_exists ) {
                $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for plugin initialization table check
                wp_cache_set( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is a caching operation, not a direct query
                    'aiohm_booking_table_exists_' . $table,
                    $table_exists,
                    'aiohm_booking',
                    3600
                );
            }
            if ( !$table_exists ) {
                $missing_tables[] = $table;
            }
        }
        if ( !empty( $missing_tables ) ) {
            $this->create_database_tables();
            // Show admin notice.
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>AIOHM Booking:</strong> Database tables have been created successfully.</p>';
                echo '</div>';
            } );
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

}

/**
 * Freemius uninstall cleanup function
 * Called after Freemius reports the uninstall event
 */
function aiohm_booking_fs_uninstall_cleanup() {
    global $wpdb;
    // Remove custom tables.
    $order_table = $wpdb->prefix . 'aiohm_booking_order';
    $calendar_data_table = $wpdb->prefix . 'aiohm_booking_calendar_data';
    $email_logs_table = $wpdb->prefix . 'aiohm_booking_email_logs';
    // Validate table names are safe (only contain allowed characters).
    $tables_to_drop = array($order_table, $calendar_data_table, $email_logs_table);
    foreach ( $tables_to_drop as $table ) {
        if ( preg_match( '/^[a-zA-Z0-9_]+$/', $table ) ) {
            $wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Plugin uninstall cleanup, schema changes allowed
        }
    }
    // Remove all booking events.
    $events = get_posts( array(
        'post_type'   => 'aiohm_booking_event',
        'numberposts' => -1,
        'post_status' => 'any',
    ) );
    foreach ( $events as $event ) {
        wp_delete_post( $event->ID, true );
    }
    // Remove all plugin options.
    $options_to_delete = array(
        'aiohm_booking_settings',
        'aiohm_booking_blocked_dates',
        'aiohm_booking_accommodations_details',
        'aiohm_booking_calendar_colors',
        'aiohm_booking_calendar_disable_demo',
        'aiohm_booking_private_events',
        'aiohm_booking_cell_statuses',
        'aiohm_booking_css_settings',
        'aiohm_booking_module_list',
        'aiohm_booking_activation_redirect',
        // Module-specific settings.
        'aiohm_booking_shareai_settings',
        'aiohm_booking_ollama_settings',
        'aiohm_booking_gemini_settings',
        'aiohm_booking_openai_settings',
        'aiohm_booking_calendar_settings',
        'aiohm_booking_orders_settings',
        'aiohm_booking_notifications_settings',
        'aiohm_booking_css_manager_settings',
        'aiohm_booking_help_settings',
        'aiohm_booking_global_settings_settings',
        'aiohm_booking_stripe_settings',
        'aiohm_booking_tickets_settings',
    );
    foreach ( $options_to_delete as $option ) {
        delete_option( $option );
    }
    // Remove transients.
    delete_transient( 'aiohm_booking_activation_redirect' );
    delete_transient( 'aiohm_booking_module_list' );
    // Clear any cached data.
    wp_cache_flush();
}

// Demo mode detection for WordPress Playground
add_action( 'init', function() {
    // Check if we're in demo mode (WordPress Playground or explicitly set)
    $is_demo = get_option( 'aiohm_booking_demo_mode' ) || 
               (isset( $_SERVER['HTTP_HOST'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ), 'playground.wordpress.net' ) !== false);
    
    if ( $is_demo ) {
        // Enable Pro features for demo
        add_filter( 'aiohm_booking_is_pro_active', '__return_true' );
        add_filter( 'aiohm_booking_demo_mode', '__return_true' );
        
        // Override Freemius can_use_premium_code check
        add_filter( 'fs_can_use_premium_code__aiohm_booking_pro', '__return_true' );
        
        // Set demo license data
        add_filter( 'pre_option_aiohm_booking_pro_license', function() {
            return array(
                'status' => 'demo',
                'key' => 'demo-key-playground',
                'expires' => strtotime( '+1 year' ),
                'features' => array( 'stripe', 'notifications', 'analytics', 'advanced_fields' )
            );
        });
        
        // Add demo notice in admin
        add_action( 'admin_notices', function() {
            if ( ! get_user_meta( get_current_user_id(), 'aiohm_demo_notice_dismissed', true ) ) {
                echo '<div class="notice notice-info is-dismissible" data-dismissible="aiohm-demo-notice">
                    <p><strong>🎯 Demo Mode Active:</strong> All Pro features are unlocked for demonstration. 
                    <a href="#" onclick="this.closest(\'.notice\').style.display=\'none\'; return false;">Dismiss</a></p>
                </div>';
            }
        });
        
        // Add demo watermark to frontend
        add_action( 'wp_footer', function() {
            if ( ! is_admin() ) {
                echo '<div style="position:fixed;bottom:10px;right:10px;background:var(--aiohm-brand-color,#457d59);color:white;padding:8px 12px;border-radius:6px;font-size:12px;z-index:9999;font-family:sans-serif;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                    ✨ AIOHM Pro Demo
                </div>';
            }
        });
        
        // Enable demo Stripe keys (test mode)
        add_filter( 'pre_option_aiohm_booking_stripe_settings', function() {
            return array(
                'enabled' => true,
                'test_mode' => true,
                'test_publishable_key' => 'pk_test_demo',
                'test_secret_key' => 'sk_test_demo',
                'demo_mode' => true
            );
        });
        
        // Initialize demo data on first load
        add_action( 'wp_loaded', function() {
            if ( ! get_option( 'aiohm_booking_demo_data_initialized' ) ) {
                aiohm_booking_initialize_demo_data();
                update_option( 'aiohm_booking_demo_data_initialized', true, false );
            }
        });
    }
}, 1 );

/**
 * Initialize demo data for WordPress Playground
 */
function aiohm_booking_initialize_demo_data() {
    // Sample events data
    $events_data = array(
        array(
            'title' => 'Yoga Retreat Weekend',
            'event_date' => gmdate('Y-m-d', strtotime('+15 days')),
            'event_time' => '09:00',
            'event_end_date' => gmdate('Y-m-d', strtotime('+17 days')),
            'event_end_time' => '17:00',
            'price' => 150,
            'early_bird_price' => 120,
            'early_bird_date' => gmdate('Y-m-d', strtotime('+5 days')),
            'available_seats' => 25,
            'description' => 'A rejuvenating weekend yoga retreat in the mountains with meditation and wellness activities.',
            'event_type' => 'Retreat',
            'teachers' => array(
                array('name' => 'Sarah Johnson', 'photo' => ''),
                array('name' => 'Michael Chen', 'photo' => '')
            )
        ),
        array(
            'title' => 'Photography Workshop',
            'event_date' => gmdate('Y-m-d', strtotime('+25 days')),
            'event_time' => '10:00',
            'event_end_time' => '16:00',
            'price' => 80,
            'early_bird_price' => 65,
            'early_bird_date' => gmdate('Y-m-d', strtotime('+10 days')),
            'available_seats' => 15,
            'description' => 'Learn professional photography techniques from award-winning photographers.',
            'event_type' => 'Workshop',
            'teachers' => array(
                array('name' => 'David Martinez', 'photo' => '')
            )
        ),
        array(
            'title' => 'Cooking Masterclass',
            'event_date' => gmdate('Y-m-d', strtotime('+30 days')),
            'event_time' => '14:00',
            'event_end_time' => '18:00',
            'price' => 95,
            'available_seats' => 20,
            'description' => 'Master the art of Italian cuisine with our expert chefs.',
            'event_type' => 'Masterclass',
            'teachers' => array(
                array('name' => 'Chef Giuseppe', 'photo' => '')
            )
        )
    );
    
    // Sample accommodations data
    $accommodations_data = array(
        array(
            'name' => 'Mountain Villa',
            'type' => 'villa',
            'price' => 200,
            'description' => 'Luxury villa with mountain views and private garden. Perfect for couples or small families.',
            'amenities' => array('WiFi', 'Kitchen', 'Balcony', 'Garden', 'Parking'),
            'capacity' => 4
        ),
        array(
            'name' => 'Cozy Studio',
            'type' => 'studio',
            'price' => 80,
            'description' => 'Comfortable studio apartment in the city center with all modern amenities.',
            'amenities' => array('WiFi', 'Kitchenette', 'TV', 'AC'),
            'capacity' => 2
        ),
        array(
            'name' => 'Family Suite',
            'type' => 'suite',
            'price' => 150,
            'description' => 'Spacious suite perfect for families with separate living area and bedroom.',
            'amenities' => array('WiFi', 'Kitchen', 'TV', 'Balcony', 'Washing Machine'),
            'capacity' => 6
        ),
        array(
            'name' => 'Rustic Cabin',
            'type' => 'cabin',
            'price' => 120,
            'description' => 'Charming wooden cabin surrounded by nature. Great for a peaceful getaway.',
            'amenities' => array('WiFi', 'Fireplace', 'Kitchen', 'Garden'),
            'capacity' => 4
        )
    );
    
    // Default settings for demo
    $demo_settings = array(
        'currency' => 'EUR',
        'company_name' => 'AIOHM Demo Resort',
        'enable_stripe' => true,
        'enable_notifications' => true,
        'enable_early_bird' => true,
        'early_bird_days' => 30,
        'deposit_percentage' => 50,
        'number_of_events' => 5,
        'demo_mode' => true,
        'form_primary_color' => '#457d59',
        'form_text_color' => '#333333'
    );
    
    // Save data to WordPress options
    update_option( 'aiohm_booking_accommodations_data', $accommodations_data, false );
    update_option( 'aiohm_booking_settings', $demo_settings, false );
    
    // Enable key modules for demo
    $modules_to_enable = array(
        'enable_tickets' => '1',
        'enable_accommodations' => '1', 
        'enable_notifications' => '1',
        'enable_orders' => '1'
    );
    
    foreach ( $modules_to_enable as $module => $value ) {
        $demo_settings[$module] = $value;
    }
    
    update_option( 'aiohm_booking_settings', $demo_settings, false );
}

// Initialize the plugin.
AIOHM_Booking::get_instance();