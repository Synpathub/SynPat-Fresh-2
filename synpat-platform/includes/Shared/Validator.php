<?php
/**
 * Data validation utilities.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Shared;

/**
 * Validator Class
 */
class Validator {
	
	/**
	 * Validation errors
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * Validate required field
	 *
	 * @param mixed  $value Value to validate
	 * @param string $field Field name
	 * @return self
	 */
	public function required( $value, $field ) {
		if ( empty( $value ) && '0' !== $value && 0 !== $value ) {
			$this->errors[ $field ] = sprintf(
				/* translators: %s: Field name */
				__( '%s is required.', 'synpat-platform' ),
				$field
			);
		}
		return $this;
	}
	
	/**
	 * Validate email
	 *
	 * @param string $value Value to validate
	 * @param string $field Field name
	 * @return self
	 */
	public function email( $value, $field ) {
		if ( ! empty( $value ) && ! is_email( $value ) ) {
			$this->errors[ $field ] = sprintf(
				/* translators: %s: Field name */
				__( '%s must be a valid email address.', 'synpat-platform' ),
				$field
			);
		}
		return $this;
	}
	
	/**
	 * Validate URL
	 *
	 * @param string $value Value to validate
	 * @param string $field Field name
	 * @return self
	 */
	public function url( $value, $field ) {
		if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$this->errors[ $field ] = sprintf(
				/* translators: %s: Field name */
				__( '%s must be a valid URL.', 'synpat-platform' ),
				$field
			);
		}
		return $this;
	}
	
	/**
	 * Validate minimum length
	 *
	 * @param string $value  Value to validate
	 * @param int    $min    Minimum length
	 * @param string $field  Field name
	 * @return self
	 */
	public function min_length( $value, $min, $field ) {
		if ( ! empty( $value ) && mb_strlen( $value ) < $min ) {
			$this->errors[ $field ] = sprintf(
				/* translators: 1: Field name, 2: Minimum length */
				__( '%1$s must be at least %2$d characters long.', 'synpat-platform' ),
				$field,
				$min
			);
		}
		return $this;
	}
	
	/**
	 * Validate maximum length
	 *
	 * @param string $value  Value to validate
	 * @param int    $max    Maximum length
	 * @param string $field  Field name
	 * @return self
	 */
	public function max_length( $value, $max, $field ) {
		if ( ! empty( $value ) && mb_strlen( $value ) > $max ) {
			$this->errors[ $field ] = sprintf(
				/* translators: 1: Field name, 2: Maximum length */
				__( '%1$s must not exceed %2$d characters.', 'synpat-platform' ),
				$field,
				$max
			);
		}
		return $this;
	}
	
	/**
	 * Validate numeric value
	 *
	 * @param mixed  $value Value to validate
	 * @param string $field Field name
	 * @return self
	 */
	public function numeric( $value, $field ) {
		if ( ! empty( $value ) && ! is_numeric( $value ) ) {
			$this->errors[ $field ] = sprintf(
				/* translators: %s: Field name */
				__( '%s must be a number.', 'synpat-platform' ),
				$field
			);
		}
		return $this;
	}
	
	/**
	 * Validate date format
	 *
	 * @param string $value  Value to validate
	 * @param string $format Expected date format
	 * @param string $field  Field name
	 * @return self
	 */
	public function date( $value, $format, $field ) {
		if ( ! empty( $value ) ) {
			$date = \DateTime::createFromFormat( $format, $value );
			if ( ! $date || $date->format( $format ) !== $value ) {
				$this->errors[ $field ] = sprintf(
					/* translators: 1: Field name, 2: Date format */
					__( '%1$s must be a valid date in %2$s format.', 'synpat-platform' ),
					$field,
					$format
				);
			}
		}
		return $this;
	}
	
	/**
	 * Validate value is in array
	 *
	 * @param mixed  $value   Value to validate
	 * @param array  $options Allowed options
	 * @param string $field   Field name
	 * @return self
	 */
	public function in_array( $value, $options, $field ) {
		if ( ! empty( $value ) && ! in_array( $value, $options, true ) ) {
			$this->errors[ $field ] = sprintf(
				/* translators: 1: Field name, 2: Allowed options */
				__( '%1$s must be one of: %2$s.', 'synpat-platform' ),
				$field,
				implode( ', ', $options )
			);
		}
		return $this;
	}
	
	/**
	 * Check if validation passed
	 *
	 * @return bool
	 */
	public function passes() {
		return empty( $this->errors );
	}
	
	/**
	 * Check if validation failed
	 *
	 * @return bool
	 */
	public function fails() {
		return ! $this->passes();
	}
	
	/**
	 * Get validation errors
	 *
	 * @return array
	 */
	public function errors() {
		return $this->errors;
	}
	
	/**
	 * Get first error message
	 *
	 * @return string|null
	 */
	public function first_error() {
		return ! empty( $this->errors ) ? reset( $this->errors ) : null;
	}
	
	/**
	 * Clear errors
	 *
	 * @return self
	 */
	public function clear() {
		$this->errors = array();
		return $this;
	}
}
