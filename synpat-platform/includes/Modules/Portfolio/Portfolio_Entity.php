<?php
/**
 * Portfolio entity class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Portfolio;

/**
 * Portfolio_Entity Class
 */
class Portfolio_Entity {
	
	/**
	 * Portfolio ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Portfolio title
	 *
	 * @var string
	 */
	public $title;
	
	/**
	 * Portfolio slug
	 *
	 * @var string
	 */
	public $slug;
	
	/**
	 * Portfolio description
	 *
	 * @var string
	 */
	public $description;
	
	/**
	 * Category ID
	 *
	 * @var int
	 */
	public $category_id;
	
	/**
	 * Company ID
	 *
	 * @var int
	 */
	public $company_id;
	
	/**
	 * Portfolio status
	 *
	 * @var string
	 */
	public $status;
	
	/**
	 * Portfolio visibility
	 *
	 * @var string
	 */
	public $visibility;
	
	/**
	 * Portfolio price
	 *
	 * @var float
	 */
	public $price;
	
	/**
	 * Currency code
	 *
	 * @var string
	 */
	public $currency;
	
	/**
	 * Asking price
	 *
	 * @var float
	 */
	public $asking_price;
	
	/**
	 * Portfolio valuation
	 *
	 * @var float
	 */
	public $valuation;
	
	/**
	 * Tags
	 *
	 * @var string
	 */
	public $tags;
	
	/**
	 * Meta data
	 *
	 * @var string
	 */
	public $meta_data;
	
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
	 * Published at timestamp
	 *
	 * @var string
	 */
	public $published_at;
	
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
			'id', 'title', 'slug', 'description', 'category_id', 'company_id',
			'status', 'visibility', 'price', 'currency', 'asking_price',
			'valuation', 'tags', 'meta_data', 'created_by', 'created_at',
			'updated_at', 'published_at'
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
			'id'           => $this->id,
			'title'        => $this->title,
			'slug'         => $this->slug,
			'description'  => $this->description,
			'category_id'  => $this->category_id,
			'company_id'   => $this->company_id,
			'status'       => $this->status,
			'visibility'   => $this->visibility,
			'price'        => $this->price,
			'currency'     => $this->currency,
			'asking_price' => $this->asking_price,
			'valuation'    => $this->valuation,
			'tags'         => $this->tags,
			'meta_data'    => $this->meta_data,
			'created_by'   => $this->created_by,
			'created_at'   => $this->created_at,
			'updated_at'   => $this->updated_at,
			'published_at' => $this->published_at,
		);
	}
	
	/**
	 * Create entity from database object
	 *
	 * @param object $object Database object
	 * @return self|null
	 */
	public static function from_object( $object ) {
		if ( ! $object ) {
			return null;
		}
		
		return new self( (array) $object );
	}
	
	/**
	 * Check if portfolio is published
	 *
	 * @return bool
	 */
	public function is_published() {
		return 'published' === $this->status;
	}
	
	/**
	 * Check if portfolio is draft
	 *
	 * @return bool
	 */
	public function is_draft() {
		return 'draft' === $this->status;
	}
	
	/**
	 * Check if portfolio is public
	 *
	 * @return bool
	 */
	public function is_public() {
		return 'public' === $this->visibility;
	}
	
	/**
	 * Get tags as array
	 *
	 * @return array
	 */
	public function get_tags_array() {
		if ( empty( $this->tags ) ) {
			return array();
		}
		
		if ( is_array( $this->tags ) ) {
			return $this->tags;
		}
		
		$tags = preg_split( '/[,;]/', $this->tags );
		return array_map( 'trim', $tags );
	}
	
	/**
	 * Get meta data as array
	 *
	 * @return array
	 */
	public function get_meta_array() {
		if ( empty( $this->meta_data ) ) {
			return array();
		}
		
		if ( is_array( $this->meta_data ) ) {
			return $this->meta_data;
		}
		
		$decoded = json_decode( $this->meta_data, true );
		return is_array( $decoded ) ? $decoded : array();
	}
	
	/**
	 * Get formatted price
	 *
	 * @return string
	 */
	public function get_formatted_price() {
		if ( empty( $this->price ) ) {
			return '';
		}
		
		$currency_symbol = $this->get_currency_symbol();
		return $currency_symbol . number_format( $this->price, 2 );
	}
	
	/**
	 * Get formatted asking price
	 *
	 * @return string
	 */
	public function get_formatted_asking_price() {
		if ( empty( $this->asking_price ) ) {
			return '';
		}
		
		$currency_symbol = $this->get_currency_symbol();
		return $currency_symbol . number_format( $this->asking_price, 2 );
	}
	
	/**
	 * Get formatted valuation
	 *
	 * @return string
	 */
	public function get_formatted_valuation() {
		if ( empty( $this->valuation ) ) {
			return '';
		}
		
		$currency_symbol = $this->get_currency_symbol();
		return $currency_symbol . number_format( $this->valuation, 2 );
	}
	
	/**
	 * Get currency symbol
	 *
	 * @return string
	 */
	private function get_currency_symbol() {
		$symbols = array(
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
			'CNY' => '¥',
		);
		
		$currency = $this->currency ?: 'USD';
		return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';
	}
	
	/**
	 * Get display title (truncated if needed)
	 *
	 * @param int $length Maximum length
	 * @return string
	 */
	public function get_display_title( $length = 100 ) {
		if ( empty( $this->title ) ) {
			return '';
		}
		
		if ( strlen( $this->title ) <= $length ) {
			return $this->title;
		}
		
		return substr( $this->title, 0, $length ) . '...';
	}
	
	/**
	 * Generate slug from title
	 *
	 * @param string $title Portfolio title
	 * @return string
	 */
	public static function generate_slug( $title ) {
		$slug = sanitize_title( $title );
		
		if ( empty( $slug ) ) {
			$slug = 'portfolio-' . time();
		}
		
		return $slug;
	}
}
