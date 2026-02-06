<?php
/**
 * Local Storage Implementation
 *
 * @package SynPatPlatform\Modules\Document\Storage
 */

namespace SynPat\Modules\Document\Storage;

use WP_Error;

/**
 * Local Storage Class
 */
class Local_Storage implements Storage_Interface {

	/**
	 * Upload directory path
	 *
	 * @var string
	 */
	private $upload_dir;

	/**
	 * Upload directory URL
	 *
	 * @var string
	 */
	private $upload_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$upload_info      = wp_upload_dir();
		$this->upload_dir = $upload_info['basedir'] . '/synpat-documents';
		$this->upload_url = $upload_info['baseurl'] . '/synpat-documents';

		$this->ensure_directory_exists();
	}

	/**
	 * Store a file
	 *
	 * @param array $file File data from $_FILES.
	 * @return array|WP_Error Array with 'path' and 'url' keys or error.
	 */
	public function store( $file ) {
		$filename      = $this->generate_unique_filename( $file['name'] );
		$file_path     = $this->upload_dir . '/' . $filename;
		$relative_path = 'synpat-documents/' . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $file_path ) ) {
			return new WP_Error( 'storage_error', __( 'Failed to store file', 'synpat-platform' ) );
		}

		chmod( $file_path, 0644 );

		return array(
			'path' => $relative_path,
			'url'  => $this->upload_url . '/' . $filename,
		);
	}

	/**
	 * Delete a file
	 *
	 * @param string $path File path.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $path ) {
		$upload_info = wp_upload_dir();
		$full_path   = $upload_info['basedir'] . '/' . $path;

		if ( file_exists( $full_path ) ) {
			return unlink( $full_path );
		}

		return false;
	}

	/**
	 * Get file URL
	 *
	 * @param string $path File path.
	 * @return string File URL.
	 */
	public function get_url( $path ) {
		$upload_info = wp_upload_dir();
		return $upload_info['baseurl'] . '/' . $path;
	}

	/**
	 * Check if file exists
	 *
	 * @param string $path File path.
	 * @return bool True if exists, false otherwise.
	 */
	public function exists( $path ) {
		$upload_info = wp_upload_dir();
		$full_path   = $upload_info['basedir'] . '/' . $path;
		return file_exists( $full_path );
	}

	/**
	 * Ensure upload directory exists
	 */
	private function ensure_directory_exists() {
		if ( ! file_exists( $this->upload_dir ) ) {
			wp_mkdir_p( $this->upload_dir );

			// Add .htaccess for security.
			$htaccess_file = $this->upload_dir . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				file_put_contents( $htaccess_file, 'Options -Indexes' );
			}

			// Add index.php for security.
			$index_file = $this->upload_dir . '/index.php';
			if ( ! file_exists( $index_file ) ) {
				file_put_contents( $index_file, '<?php // Silence is golden' );
			}
		}
	}

	/**
	 * Generate unique filename
	 *
	 * @param string $original_name Original filename.
	 * @return string Unique filename.
	 */
	private function generate_unique_filename( $original_name ) {
		$extension = pathinfo( $original_name, PATHINFO_EXTENSION );
		$basename  = pathinfo( $original_name, PATHINFO_FILENAME );
		$basename  = sanitize_file_name( $basename );

		$unique_id = uniqid() . '-' . wp_generate_password( 8, false );
		return $basename . '-' . $unique_id . '.' . $extension;
	}
}
