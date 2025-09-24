<?php
/**
 * Accommodation Settings Data Transfer Object
 * Handles validation and sanitization of accommodation settings
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Data Transfer Object
 * Handles validation and sanitization of accommodation settings
 */
class AccommodationSettingsDTO {
	/**
	 * Accommodation type.
	 *
	 * @var string
	 */
	public $accommodation_type;

	/**
	 * Number of available accommodations.
	 *
	 * @var int
	 */
	public $available_accommodations;

	/**
	 * Whether to allow private booking of all accommodations.
	 *
	 * @var bool
	 */
	public $allow_private_all;

	/**
	 * Default price per accommodation.
	 *
	 * @var float
	 */
	public $default_price;

	/**
	 * Default early bird price per accommodation.
	 *
	 * @var float
	 */
	public $default_earlybird_price;

	/**
	 * Create DTO from raw settings data.
	 *
	 * @param array $data Raw settings data.
	 * @return AccommodationSettingsDTO
	 */
	public static function from_array( $data ) {
		$dto = new self();

		$dto->accommodation_type       = isset( $data['accommodation_type'] ) ?
			sanitize_key( $data['accommodation_type'] ) : 'room';
		$dto->available_accommodations = isset( $data['available_accommodations'] ) ?
			intval( $data['available_accommodations'] ) : 1;
		$dto->allow_private_all        = ! empty( $data['allow_private_all'] );
		$dto->default_price            = isset( $data['default_price'] ) ?
			floatval( $data['default_price'] ) : 0;
		$dto->default_earlybird_price  = isset( $data['default_earlybird_price'] ) ?
			floatval( $data['default_earlybird_price'] ) : 0;

		// Validate accommodation type.
		$valid_types = array_keys( AIOHM_BOOKING_Module_Accommodation::get_accommodation_types() );
		if ( ! in_array( $dto->accommodation_type, $valid_types, true ) ) {
			$dto->accommodation_type = 'room';
		}

		// Validate accommodation count.
		if ( $dto->available_accommodations < AIOHM_BOOKING_Module_Accommodation::MIN_ACCOMMODATIONS ||
			$dto->available_accommodations > AIOHM_BOOKING_Module_Accommodation::MAX_ACCOMMODATIONS ) {
			$dto->available_accommodations = 1;
		}

		// Validate prices.
		if ( $dto->default_price < 0 ) {
			$dto->default_price = 0;
		}
		if ( $dto->default_earlybird_price < 0 ) {
			$dto->default_earlybird_price = 0;
		}

		return $dto;
	}

	/**
	 * Convert DTO to array for saving.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'accommodation_type'       => $this->accommodation_type,
			'available_accommodations' => $this->available_accommodations,
			'allow_private_all'        => $this->allow_private_all,
			'default_price'            => $this->default_price,
			'default_earlybird_price'  => $this->default_earlybird_price,
		);
	}
}
