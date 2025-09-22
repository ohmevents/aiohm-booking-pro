<?php
/**
 * Abstract Settings Module Class
 * Base class for modules that provide settings sections
 *
 * @package AIOHM_Booking
 * @since 1.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class AIOHM_BOOKING_Settings_Module_Abstract extends AIOHM_BOOKING_Module_Abstract {


	protected $settings_section_id = '';
	protected $settings_page_title = '';
	protected $settings_tab_title  = '';
	protected $has_quick_settings  = false;
	protected $admin_page_slug     = '';


	/**
	 * Check if module has an admin page
	 */
	public function has_admin_page() {
		return ! empty( $this->admin_page_slug );
	}

	/**
	 * Get admin page URL
	 */
	public function get_admin_url() {
		if ( ! $this->has_admin_page() ) {
			return '';
		}

		return admin_url( 'admin.php?page=' . $this->admin_page_slug );
	}

	/**
	 * Get access level for this module
	 */
	public function get_access_level() {
		$registry    = AIOHM_BOOKING_Module_Registry::instance();
		$module_info = $registry->get_module_info( $this->get_module_id() );
		return $module_info['access_level'] ?? 'free';
	}

	/**
	 * Check if module should be visible in settings
	 */
	public function is_visible_in_settings() {
		$registry    = AIOHM_BOOKING_Module_Registry::instance();
		$module_info = $registry->get_module_info( $this->get_module_id() );

		// Check access level permissions.
		$access_level = $module_info['access_level'] ?? 'free';
		if ( ! $this->user_has_access_level( $access_level ) ) {
			return false;
		}

		return $module_info['visible_in_settings'] ?? true;
	}

	/**
	 * Check if module has settings section
	 */
	public function has_settings_section() {
		$registry    = AIOHM_BOOKING_Module_Registry::instance();
		$module_info = $registry->get_module_info( $this->get_module_id() );
		return $module_info['settings_section'] ?? false;
	}

	/**
	 * Get module category for grouping
	 */
	public function get_module_category() {
		$registry    = AIOHM_BOOKING_Module_Registry::instance();
		$module_info = $registry->get_module_info( $this->get_module_id() );
		return $module_info['category'] ?? 'other';
	}

	/**
	 * Get module priority for ordering
	 */
	public function get_module_priority() {
		$registry    = AIOHM_BOOKING_Module_Registry::instance();
		$module_info = $registry->get_module_info( $this->get_module_id() );
		return $module_info['priority'] ?? 999;
	}

	/**
	 * Check if user has access to specific access level
	 */
	protected function user_has_access_level( $access_level ) {
		switch ( $access_level ) {
			case 'free':
				return true;

			case 'pro':
				// Check if user has pro access.
				return $this->is_pro_user();

			case 'private':
				// Check if user has private access.
				return $this->is_private_user();

			case 'developer':
				// Developer modules are hidden from users.
				return $this->is_developer_mode();

			default:
				return true;
		}
	}

	/**
	 * Check if current user is a pro user
	 */
	protected function is_pro_user() {
		// Implementation depends on your licensing system.
		// For now, return true for admin users.
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if current user is a private user
	 */
	protected function is_private_user() {
		// Implementation depends on your licensing system.
		// For now, return true for admin users.
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if developer mode is enabled
	 */
	protected function is_developer_mode() {
		// Show developer modules only if explicitly enabled.
		return defined( 'AIOHM_BOOKING_DEVELOPER_MODE' ) && AIOHM_BOOKING_DEVELOPER_MODE;
	}

	/**
	 * Get access level label
	 */
	public function get_access_level_label() {
		$access_level = $this->get_access_level();

		$labels = array(
			'free'      => __( 'Free', 'aiohm-booking-pro' ),
			'pro'       => __( 'Pro', 'aiohm-booking-pro' ),
			'private'   => __( 'Private', 'aiohm-booking-pro' ),
			'developer' => __( 'Developer', 'aiohm-booking-pro' ),
		);

		return $labels[ $access_level ] ?? __( 'Unknown', 'aiohm-booking-pro' );
	}

	/**
	 * Get access level CSS class
	 */
	public function get_access_level_class() {
		$access_level = $this->get_access_level();
		return 'access-level-' . $access_level;
	}


	/**
	 * Save module settings (override in child classes)
	 */
	public function save_module_settings( $data ) {
		// Override in child classes.
		return true;
	}

	/**
	 * Validate module settings (override in child classes)
	 */
	public function validate_module_settings( $data ) {
		// Override in child classes.
		return $data;
	}

	/**
	 * Get module status info
	 */
	public function get_module_status_info() {
		$info = array(
			'enabled'          => $this->is_enabled(),
			'loaded'           => $this->is_loaded(),
			'available'        => $this->is_available(),
			'has_dependencies' => ! empty( $this->get_dependencies() ),
			'dependencies_met' => $this->check_dependencies(),
			'access_level'     => $this->get_access_level(),
			'has_access'       => $this->user_has_access_level( $this->get_access_level() ),
		);

		return $info;
	}
}
