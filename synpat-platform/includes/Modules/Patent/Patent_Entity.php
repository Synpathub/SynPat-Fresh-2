<?php
/**
 * Patent entity class.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Modules\Patent;

/**
 * Patent_Entity Class
 */
class Patent_Entity {
	
	/**
	 * Patent ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Patent number
	 *
	 * @var string
	 */
	public $patent_number;
	
	/**
	 * Application number
	 *
	 * @var string
	 */
	public $application_number;
	
	/**
	 * Patent title
	 *
	 * @var string
	 */
	public $title;
	
	/**
	 * Patent abstract
	 *
	 * @var string
	 */
	public $abstract;
	
	/**
	 * Patent type
	 *
	 * @var string
	 */
	public $patent_type;
	
	/**
	 * Patent status
	 *
	 * @var string
	 */
	public $status;
	
	/**
	 * Country code
	 *
	 * @var string
	 */
	public $country;
	
	/**
	 * Filing date
	 *
	 * @var string
	 */
	public $filing_date;
	
	/**
	 * Publication date
	 *
	 * @var string
	 */
	public $publication_date;
	
	/**
	 * Grant date
	 *
	 * @var string
	 */
	public $grant_date;
	
	/**
	 * Expiration date
	 *
	 * @var string
	 */
	public $expiration_date;
	
	/**
	 * Assignee
	 *
	 * @var string
	 */
	public $assignee;
	
	/**
	 * Inventors
	 *
	 * @var string
	 */
	public $inventors;
	
	/**
	 * Claims count
	 *
	 * @var int
	 */
	public $claims_count;
	
	/**
	 * Independent claims count
	 *
	 * @var int
	 */
	public $independent_claims;
	
	/**
	 * Priority date
	 *
	 * @var string
	 */
	public $priority_date;
	
	/**
	 * Patent family ID
	 *
	 * @var string
	 */
	public $patent_family_id;
	
	/**
	 * IPC classification
	 *
	 * @var string
	 */
	public $ipc_classification;
	
	/**
	 * CPC classification
	 *
	 * @var string
	 */
	public $cpc_classification;
	
	/**
	 * US classification
	 *
	 * @var string
	 */
	public $us_classification;
	
	/**
	 * Legal status
	 *
	 * @var string
	 */
	public $legal_status;
	
	/**
	 * Maintenance fee status
	 *
	 * @var string
	 */
	public $maintenance_fee_status;
	
	/**
	 * Forward citations count
	 *
	 * @var int
	 */
	public $forward_citations;
	
	/**
	 * Backward citations count
	 *
	 * @var int
	 */
	public $backward_citations;
	
	/**
	 * PDF URL
	 *
	 * @var string
	 */
	public $pdf_url;
	
	/**
	 * Source
	 *
	 * @var string
	 */
	public $source;
	
	/**
	 * Source ID
	 *
	 * @var string
	 */
	public $source_id;
	
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
			'id', 'patent_number', 'application_number', 'title', 'abstract',
			'patent_type', 'status', 'country', 'filing_date', 'publication_date',
			'grant_date', 'expiration_date', 'assignee', 'inventors',
			'claims_count', 'independent_claims', 'priority_date',
			'patent_family_id', 'ipc_classification', 'cpc_classification',
			'us_classification', 'legal_status', 'maintenance_fee_status',
			'forward_citations', 'backward_citations', 'pdf_url', 'source',
			'source_id', 'meta_data', 'created_by', 'created_at', 'updated_at'
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
			'id'                     => $this->id,
			'patent_number'          => $this->patent_number,
			'application_number'     => $this->application_number,
			'title'                  => $this->title,
			'abstract'               => $this->abstract,
			'patent_type'            => $this->patent_type,
			'status'                 => $this->status,
			'country'                => $this->country,
			'filing_date'            => $this->filing_date,
			'publication_date'       => $this->publication_date,
			'grant_date'             => $this->grant_date,
			'expiration_date'        => $this->expiration_date,
			'assignee'               => $this->assignee,
			'inventors'              => $this->inventors,
			'claims_count'           => $this->claims_count,
			'independent_claims'     => $this->independent_claims,
			'priority_date'          => $this->priority_date,
			'patent_family_id'       => $this->patent_family_id,
			'ipc_classification'     => $this->ipc_classification,
			'cpc_classification'     => $this->cpc_classification,
			'us_classification'      => $this->us_classification,
			'legal_status'           => $this->legal_status,
			'maintenance_fee_status' => $this->maintenance_fee_status,
			'forward_citations'      => $this->forward_citations,
			'backward_citations'     => $this->backward_citations,
			'pdf_url'                => $this->pdf_url,
			'source'                 => $this->source,
			'source_id'              => $this->source_id,
			'meta_data'              => $this->meta_data,
			'created_by'             => $this->created_by,
			'created_at'             => $this->created_at,
			'updated_at'             => $this->updated_at,
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
	 * Get formatted patent number
	 *
	 * @return string
	 */
	public function get_formatted_number() {
		if ( empty( $this->country ) || 'US' === $this->country ) {
			return $this->patent_number;
		}
		
		return $this->country . ' ' . $this->patent_number;
	}
	
	/**
	 * Get inventors as array
	 *
	 * @return array
	 */
	public function get_inventors_array() {
		if ( empty( $this->inventors ) ) {
			return array();
		}
		
		// If already an array (from meta_data), return it
		if ( is_array( $this->inventors ) ) {
			return $this->inventors;
		}
		
		// Otherwise split by semicolon or comma
		$inventors = preg_split( '/[;,]/', $this->inventors );
		return array_map( 'trim', $inventors );
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
	 * Check if patent is active
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'active' === $this->status;
	}
	
	/**
	 * Check if patent is expired
	 *
	 * @return bool
	 */
	public function is_expired() {
		if ( 'expired' === $this->status ) {
			return true;
		}
		
		if ( empty( $this->expiration_date ) ) {
			return false;
		}
		
		$expiration = strtotime( $this->expiration_date );
		return $expiration && $expiration < time();
	}
	
	/**
	 * Get days until expiration
	 *
	 * @return int|null Number of days or null if no expiration date
	 */
	public function get_days_until_expiration() {
		if ( empty( $this->expiration_date ) ) {
			return null;
		}
		
		$expiration = strtotime( $this->expiration_date );
		if ( ! $expiration ) {
			return null;
		}
		
		$now = time();
		$diff = $expiration - $now;
		
		return (int) floor( $diff / DAY_IN_SECONDS );
	}
	
	/**
	 * Check if patent is expiring soon
	 *
	 * @param int $days Number of days threshold
	 * @return bool
	 */
	public function is_expiring_soon( $days = 90 ) {
		$days_left = $this->get_days_until_expiration();
		
		if ( null === $days_left ) {
			return false;
		}
		
		return $days_left > 0 && $days_left <= $days;
	}
	
	/**
	 * Get patent age in years
	 *
	 * @return float|null Patent age or null if no filing date
	 */
	public function get_age_years() {
		if ( empty( $this->filing_date ) ) {
			return null;
		}
		
		$filing = strtotime( $this->filing_date );
		if ( ! $filing ) {
			return null;
		}
		
		$now = time();
		$diff = $now - $filing;
		
		return round( $diff / YEAR_IN_SECONDS, 1 );
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
	 * Get classification codes as array
	 *
	 * @param string $type Classification type (ipc, cpc, us)
	 * @return array
	 */
	public function get_classification_array( $type = 'ipc' ) {
		$field = $type . '_classification';
		
		if ( empty( $this->$field ) ) {
			return array();
		}
		
		if ( is_array( $this->$field ) ) {
			return $this->$field;
		}
		
		// Split by semicolon or comma
		$codes = preg_split( '/[;,]/', $this->$field );
		return array_map( 'trim', $codes );
	}
}
