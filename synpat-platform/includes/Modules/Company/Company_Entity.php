<?php
/**
 * Company entity class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Company;

/**
 * Company_Entity Class
 */
class Company_Entity {
	
	/**
	 * Company ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Company name
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 * Legal name
	 *
	 * @var string
	 */
	public $legal_name;
	
	/**
	 * Company type
	 *
	 * @var string
	 */
	public $type;
	
	/**
	 * Industry
	 *
	 * @var string
	 */
	public $industry;
	
	/**
	 * Website
	 *
	 * @var string
	 */
	public $website;
	
	/**
	 * Email
	 *
	 * @var string
	 */
	public $email;
	
	/**
	 * Phone
	 *
	 * @var string
	 */
	public $phone;
	
	/**
	 * Address line 1
	 *
	 * @var string
	 */
	public $address_line1;
	
	/**
	 * Address line 2
	 *
	 * @var string
	 */
	public $address_line2;
	
	/**
	 * City
	 *
	 * @var string
	 */
	public $city;
	
	/**
	 * State
	 *
	 * @var string
	 */
	public $state;
	
	/**
	 * Country
	 *
	 * @var string
	 */
	public $country;
	
	/**
	 * Postal code
	 *
	 * @var string
	 */
	public $postal_code;
	
	/**
	 * Notes
	 *
	 * @var string
	 */
	public $notes;
	
	/**
	 * Created by user ID
	 *
	 * @var int
	 */
	public $created_by;
	
	/**
	 * Created at timestamp
	 *
	 * @var string
	 */
	public $created_at;
	
	/**
	 * Updated at timestamp
	 *
	 * @var string
	 */
	public $updated_at;
	
	/**
	 * Constructor
	 *
	 * @param array $data Entity data
	 */
	public function __construct( $data = array() ) {
		if ( ! empty( $data ) ) {
			$this->fill( $data );
		}
	}
	
	/**
	 * Fill entity from array
	 *
	 * @param array $data Entity data
	 */
	private function fill( $data ) {
		$properties = array(
			'id', 'name', 'legal_name', 'type', 'industry', 'website',
			'email', 'phone', 'address_line1', 'address_line2', 'city',
			'state', 'country', 'postal_code', 'notes', 'created_by',
			'created_at', 'updated_at'
		);
		
		foreach ( $properties as $property ) {
			if ( isset( $data[ $property ] ) ) {
				$this->$property = $data[ $property ];
			}
		}
	}
	
	/**
	 * Convert entity to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'            => $this->id,
			'name'          => $this->name,
			'legal_name'    => $this->legal_name,
			'type'          => $this->type,
			'industry'      => $this->industry,
			'website'       => $this->website,
			'email'         => $this->email,
			'phone'         => $this->phone,
			'address_line1' => $this->address_line1,
			'address_line2' => $this->address_line2,
			'city'          => $this->city,
			'state'         => $this->state,
			'country'       => $this->country,
			'postal_code'   => $this->postal_code,
			'notes'         => $this->notes,
			'created_by'    => $this->created_by,
			'created_at'    => $this->created_at,
			'updated_at'    => $this->updated_at,
		);
	}
	
	/**
	 * Create entity from database object
	 *
	 * @param object $object Database object
	 * @return self
	 */
	public static function from_object( $object ) {
		if ( ! $object ) {
			return null;
		}
		
		return new self( (array) $object );
	}
	
	/**
	 * Get full address
	 *
	 * @return string
	 */
	public function get_full_address() {
		$parts = array_filter( array(
			$this->address_line1,
			$this->address_line2,
			$this->city,
			$this->state,
			$this->postal_code,
			$this->country,
		) );
		
		return implode( ', ', $parts );
	}
	
	/**
	 * Get display name
	 *
	 * @return string
	 */
	public function get_display_name() {
		return $this->legal_name ?: $this->name;
	}
}
