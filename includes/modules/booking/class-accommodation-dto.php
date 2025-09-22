<?php
/**
 * Accommodation Data Transfer Object
 * Handles validation and sanitization of accommodation data
 *
 * @package AIOHM_Booking
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data Transfer Object for Accommodation data
 * Handles validation and sanitization of accommodation data
 */
class AccommodationDTO {
	/**
	 * Accommodation ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Accommodation title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Accommodation description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Accommodation price.
	 *
	 * @var float
	 */
	public $price;

	/**
	 * Early bird price.
	 *
	 * @var float
	 */
	public $earlybird_price;

	/**
	 * Accommodation type.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Accommodation number.
	 *
	 * @var int
	 */
	public $accommodation_number;

	/**
	 * Create DTO from raw data with validation.
	 *
	 * @param array $data Raw accommodation data.
	 * @return AccommodationDTO
	 * @throws InvalidArgumentException If data is invalid.
	 */
	public static function from_array( $data ) {
		$dto = new self();

		$dto->id                   = isset( $data['id'] ) ? intval( $data['id'] ) : 0;
		$dto->title                = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$dto->description          = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
		$dto->price                = isset( $data['price'] ) ? floatval( $data['price'] ) : 0;
		$dto->earlybird_price      = isset( $data['earlybird_price'] ) ? floatval( $data['earlybird_price'] ) : 0;
		$dto->type                 = isset( $data['type'] ) ? sanitize_key( $data['type'] ) : 'unit';
		$dto->accommodation_number = isset( $data['accommodation_number'] ) ? intval( $data['accommodation_number'] ) : 0;

		// Validate required fields.
		if ( empty( $dto->title ) ) {
			throw new InvalidArgumentException( 'Accommodation title is required' );
		}

		// Validate price values.
		if ( $dto->price < 0 || $dto->earlybird_price < 0 ) {
			throw new InvalidArgumentException( 'Price values cannot be negative' );
		}

		// Validate accommodation type.
		$valid_types = array_keys( AIOHM_BOOKING_Module_Accommodation::get_accommodation_types() );
		if ( ! in_array( $dto->type, $valid_types, true ) ) {
			$dto->type = 'unit';
		}

		return $dto;
	}

	/**
	 * Convert DTO to array for database operations.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'                   => $this->id,
			'title'                => $this->title,
			'description'          => $this->description,
			'price'                => $this->price,
			'earlybird_price'      => $this->earlybird_price,
			'type'                 => $this->type,
			'accommodation_number' => $this->accommodation_number,
		);
	}

	/**
	 * Check if DTO has valid data for saving.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return ! empty( $this->title ) &&
				$this->price >= 0 &&
				$this->earlybird_price >= 0 &&
				! empty( $this->type );
	}
}
