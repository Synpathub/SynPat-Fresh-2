<?php
/**
 * Storage Interface
 *
 * @package SynPatPlatform\Modules\Document\Storage
 */

namespace SynPat\Modules\Document\Storage;

use WP_Error;

/**
 * Storage Interface
 */
interface Storage_Interface {

	/**
	 * Store a file
	 *
	 * @param array $file File data from $_FILES.
	 * @return array|WP_Error Array with 'path' and 'url' keys or error.
	 */
	public function store( $file );

	/**
	 * Delete a file
	 *
	 * @param string $path File path.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $path );

	/**
	 * Get file URL
	 *
	 * @param string $path File path.
	 * @return string File URL.
	 */
	public function get_url( $path );

	/**
	 * Check if file exists
	 *
	 * @param string $path File path.
	 * @return bool True if exists, false otherwise.
	 */
	public function exists( $path );
}
