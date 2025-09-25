<?php
/**
 * Abstract Admin Module Class
 *
 * @package AIOHM_Booking_PRO
 * @since  2.0.0
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Abstract class file

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract AIOHM_Booking_Admin_Module class.
 *
 * @since  2.0.0
 * @author  OHM Events Agency
 * @author URI: https://www.ohm.events */
abstract class AIOHM_Booking_Admin_Module {

	/**
	 * The module ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * The module name.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The module description.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * The module icon.
	 *
	 * @var string
	 */
	public $icon = '🧩';

	/**
	 * The module category.
	 *
	 * @var string
	 */
	public $category = '';

	/**
	 * The module access level.
	 *
	 * @var string
	 */
	public $access_level = 'free';

	/**
	 * The module features.
	 *
	 * @var array
	 */
	public $features = array();

	/**
	 * Whether the module has settings.
	 *
	 * @var boolean
	 */
	public $has_settings = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'aiohm_booking_admin_menu', array( $this, 'register_menu' ) );
		add_action( 'aiohm_booking_admin_hooks_init', array( $this, 'register_hooks' ) );
		add_action( 'aiohm_booking_render_module_settings_' . $this->id, array( $this, 'render_settings' ) );
	}

	/**
	 * Register admin menu.
	 */
	public function register_menu() {
		// Implement in child class.
	}

	/**
	 * Register admin hooks.
	 */
	public function register_hooks() {
		// Implement in child class.
	}

	/**
	 * Render module settings.
	 */
	public function render_settings() {
		// Implement in child class.
	}

	/**
	 * Get module info.
	 *
	 * @return array
	 */
	public function get_module_info() {
		return array(
			'id'           => $this->id,
			'name'         => $this->name,
			'description'  => $this->description,
			'icon'         => $this->icon,
			'category'     => $this->category,
			'access_level' => $this->access_level,
			'features'     => $this->features,
			'has_settings' => $this->has_settings,
		);
	}
}
