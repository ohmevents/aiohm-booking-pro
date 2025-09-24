<?php

namespace AIOHM_Booking_PRO;

class Plugin {
    /**
     * Singleton instance
     *
     * @var Plugin
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Plugin
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
        add_action( 'plugins_loaded', array( $this, 'init_components' ) );
    }

    /**
     * Initialize plugin components
     */
    public function init_components() {
        // I will move the component initialization logic here from the AIOHM_Booking class.
    }
}
