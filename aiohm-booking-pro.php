<?php

/**
 * Plugin Name: AIOHM Booking Pro
 * Plugin URI:  https://wordpress.org/plugins/aiohm-booking/
 * Description: Professional event booking and accommodation management system. Streamlined booking experience for events and accommodations with secure Stripe payments and comprehensive utilities.
 * Version:     2.0.4
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
 * @fs_premium_only /includes/modules/payments/
 * @fs_premium_only /includes/modules/ai/
 * @fs_premium_only /includes/modules/integrations/
 *
 * @package AIOHM_Booking_PRO
 * @since   2.0.0
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

        // WordPress Playground demo mode - unlock all Pro features for testing (premium only)
        if ( aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
            if ( defined( 'WP_ENV' ) && WP_ENV === 'playground' ) {
                add_filter( 'aiohm_booking_fs_is_premium', '__return_true' );
                add_filter( 'aiohm_booking_fs_can_use_premium_code', '__return_true' );
            }
        }
        
        // Enqueue custom assets for Freemius pricing page
        add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
            $screen = get_current_screen();
            if ( $screen && strpos( $screen->id, 'aiohm-booking-pro-pricing' ) !== false ) {
                // Enqueue pricing page styles (already included in admin CSS)
                wp_enqueue_style( 
                    'aiohm-booking-admin-css', 
                    AIOHM_BOOKING_URL . 'assets/css/aiohm-booking-admin.css', 
                    array(), 
                    AIOHM_BOOKING_VERSION 
                );
                
                // Enqueue pricing page JavaScript
                wp_enqueue_script( 
                    'aiohm-booking-freemius-pricing', 
                    AIOHM_BOOKING_URL . 'assets/js/aiohm-booking-freemius-pricing.js', 
                    array( 'jquery' ), 
                    AIOHM_BOOKING_VERSION, 
                    true 
                );
                
                // Localize script with plugin URL
                wp_localize_script( 'aiohm-booking-freemius-pricing', 'aiohm_booking_vars', array(
                    'plugin_url' => AIOHM_BOOKING_URL,
                ) );
            }
        } );
        // Clear module cache when license status changes
        aiohm_booking_fs()->add_action( 'after_license_change', function () {
            if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
                AIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
            }
        } );
        // Clear module cache after account connection.
        aiohm_booking_fs()->add_action( 'after_account_connection', function () {
            if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
                AIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
            }
        } );
        // Clear module cache on init to ensure fresh discovery.
        add_action( 'init', function () {
            if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
                AIOHM_BOOKING_Module_Registry::instance()->clear_module_cache();
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
            if ( function_exists( 'aiohm_booking_fs' ) && ! aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
                $upgrade_url = 'https://checkout.freemius.com/plugin/20270/plan/33657/';
                $message .= '<br><br><div class="aiohm-booking-license-message">';
                $message .= '<strong>' . esc_html__( 'Don\'t have a license yet?', 'aiohm-booking-pro' ) . '</strong><br>';
                $message .= esc_html__( 'Purchase a Pro license to unlock all premium features including Stripe payments.', 'aiohm-booking-pro' );
                $message .= '<br><a href="' . esc_url( $upgrade_url ) . '" target="_blank" class="aiohm-booking-upgrade-link">' . esc_html__( 'Buy Pro License →', 'aiohm-booking-pro' ) . '</a>';
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
define( 'AIOHM_BOOKING_VERSION', '2.0.4' );
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
            if ( $fs->can_use_premium_code__premium_only() ) {
                return true;
            }
        }
    }
    // Use the new conditional loading system.
    if ( class_exists( 'AIOHM_BOOKING_Utilities' ) ) {
        return AIOHM_BOOKING_Utilities::is_module_available( $module_name );
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
 * @since   2.0.0
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events
 */
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
        if ( class_exists( 'AIOHM_BOOKING_Security_Config' ) ) {
            $headers = AIOHM_BOOKING_Security_Config::get_security_headers();
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
        $this->load_dependencies();
        // Migrate settings if needed.
        $this->migrate_settings();
        // Initialize core components needed for dashboard.
        if ( class_exists( 'AIOHM_BOOKING_Admin' ) ) {
            AIOHM_BOOKING_Admin::init();
        }
        // Initialize assets management.
        if ( class_exists( 'AIOHM_BOOKING_Assets' ) ) {
            new AIOHM_BOOKING_Assets();
        }
        // Initialize module registry.
        if ( class_exists( 'AIOHM_BOOKING_Module_Registry' ) ) {
            AIOHM_BOOKING_Module_Registry::instance();
        }
        // Initialize field renderer factory.
        if ( class_exists( 'AIOHM_Booking_Field_Renderer_Factory' ) ) {
            AIOHM_Booking_Field_Renderer_Factory::init();
        }
        // Initialize REST API.
        if ( class_exists( 'AIOHM_BOOKING_REST_API' ) ) {
            AIOHM_BOOKING_REST_API::init();
        }
        // Initialize error handling.
        if ( class_exists( 'AIOHM_BOOKING_Error_Handler' ) ) {
            AIOHM_BOOKING_Error_Handler::init();
        }
        // Initialize enhanced security.
        if ( class_exists( 'AIOHM_BOOKING_Security_Config' ) ) {
            add_action( 'init', array('AIOHM_BOOKING_Security_Config', 'init_security') );
        }
        // Initialize upsells for free version.
        if ( class_exists( 'AIOHM_BOOKING_Upsells' ) ) {
            AIOHM_BOOKING_Upsells::init();
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
        // Load abstract classes required for modules.
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-module.php';
        require_once AIOHM_BOOKING_DIR . 'includes/abstracts/abstract-aiohm-booking-settings-module.php';
        // Load modules that have on_activation methods.
        require_once AIOHM_BOOKING_DIR . 'includes/modules/booking/class-aiohm-booking-module-orders.php';
        require_once AIOHM_BOOKING_DIR . 'includes/modules/booking/class-aiohm-booking-module-calendar.php';
        // Load notifications module only if it exists (conditional feature).
        $notifications_file = AIOHM_BOOKING_DIR . 'includes/modules/notifications/class-aiohm-booking-module-notifications.php';
        if ( file_exists( $notifications_file ) ) {
            require_once $notifications_file;
        }
        // Create orders table.
        if ( class_exists( 'AIOHM_BOOKING_Module_Orders' ) && method_exists( 'AIOHM_BOOKING_Module_Orders', 'on_activation' ) ) {
            AIOHM_BOOKING_Module_Orders::on_activation();
        }
        // Create calendar data table.
        if ( class_exists( 'AIOHM_BOOKING_Module_Calendar' ) && method_exists( 'AIOHM_BOOKING_Module_Calendar', 'on_activation' ) ) {
            AIOHM_BOOKING_Module_Calendar::on_activation();
        }
        // Create email logs table.
        if ( class_exists( 'AIOHM_BOOKING_Module_Notifications' ) && method_exists( 'AIOHM_BOOKING_Module_Notifications', 'on_activation' ) ) {
            AIOHM_BOOKING_Module_Notifications::on_activation();
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

// Demo mode detection for WordPress Playground (premium only)
if ( aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
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
                echo '<div class="aiohm-booking-demo-watermark">✨ AIOHM Pro Demo</div>';
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
}

/**
 * Initialize demo data for WordPress Playground (premium only)
 */
if ( aiohm_booking_fs()->can_use_premium_code__premium_only() ) {
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
}

// Initialize the plugin.
AIOHM_Booking::get_instance();
