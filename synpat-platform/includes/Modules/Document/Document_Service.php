<?php
/**
 * Document Service
 *
 * @package SynPatPlatform\Modules\Document
 */

namespace SynPat\Modules\Document;

use SynPat\Modules\Document\Storage\Storage_Interface;
use WP_Error;

/**
 * Document Service Class
 */
class Document_Service {

	/**
	 * Storage handler
	 *
	 * @var Storage_Interface
	 */
	private $storage;

	/**
	 * Document repository
	 *
	 * @var Document_Repository
	 */
	private $repository;

	/**
	 * Allowed file types
	 *
	 * @var array
	 */
	private $allowed_types = array( 'pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png' );

	/**
	 * Max file size in bytes (10MB)
	 *
	 * @var int
	 */
	private $max_file_size = 10485760;

	/**
	 * Constructor
	 *
	 * @param Storage_Interface   $storage    Storage handler.
	 * @param Document_Repository $repository Document repository.
	 */
	public function __construct( Storage_Interface $storage, Document_Repository $repository ) {
		$this->storage    = $storage;
		$this->repository = $repository;
	}

	/**
	 * Upload a document
	 *
	 * @param array  $file        File data from $_FILES.
	 * @param string $entity_type Entity type (patent, portfolio, etc.).
	 * @param int    $entity_id   Entity ID.
	 * @return array|WP_Error Document data or error.
	 */
	public function upload( $file, $entity_type, $entity_id ) {
		// Validate file.
		$validation = $this->validate_file( $file );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Store file.
		$storage_result = $this->storage->store( $file );
		if ( is_wp_error( $storage_result ) ) {
			return $storage_result;
		}

		// Save to repository.
		$document_data = array(
			'filename'    => $file['name'],
			'filepath'    => $storage_result['path'],
			'filesize'    => $file['size'],
			'mime_type'   => $file['type'],
			'entity_type' => $entity_type,
			'entity_id'   => $entity_id,
			'uploaded_by' => get_current_user_id(),
			'uploaded_at' => current_time( 'mysql' ),
		);

		$document_id = $this->repository->create( $document_data );

		if ( is_wp_error( $document_id ) ) {
			$this->storage->delete( $storage_result['path'] );
			return $document_id;
		}

		return $this->repository->get( $document_id );
	}

	/**
	 * Get a document by ID
	 *
	 * @param int $document_id Document ID.
	 * @return array|WP_Error Document data or error.
	 */
	public function get( $document_id ) {
		$document = $this->repository->get( $document_id );

		if ( ! $document ) {
			return new WP_Error( 'document_not_found', __( 'Document not found', 'synpat-platform' ) );
		}

		return $document;
	}

	/**
	 * Delete a document
	 *
	 * @param int $document_id Document ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete( $document_id ) {
		$document = $this->repository->get( $document_id );

		if ( ! $document ) {
			return new WP_Error( 'document_not_found', __( 'Document not found', 'synpat-platform' ) );
		}

		// Delete physical file.
		$this->storage->delete( $document['filepath'] );

		// Delete from repository.
		return $this->repository->delete( $document_id );
	}

	/**
	 * Get documents by entity
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @return array List of documents.
	 */
	public function get_by_entity( $entity_type, $entity_id ) {
		return $this->repository->get_by_entity( $entity_type, $entity_id );
	}

	/**
	 * Validate uploaded file
	 *
	 * @param array $file File data.
	 * @return bool|WP_Error True if valid, error otherwise.
	 */
	private function validate_file( $file ) {
		if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_file', __( 'Invalid file upload', 'synpat-platform' ) );
		}

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', __( 'File upload error', 'synpat-platform' ) );
		}

		if ( $file['size'] > $this->max_file_size ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds limit', 'synpat-platform' ) );
		}

		$file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $file_ext, $this->allowed_types, true ) ) {
			return new WP_Error( 'invalid_file_type', __( 'File type not allowed', 'synpat-platform' ) );
		}

		return true;
	}
}
