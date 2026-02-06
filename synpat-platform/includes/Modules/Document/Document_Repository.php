<?php
/**
 * Document Repository
 *
 * @package SynPatPlatform\Modules\Document
 */

namespace SynPat\Modules\Document;

use WP_Error;

/**
 * Document Repository Class
 */
class Document_Repository {

	/**
	 * Create a document record
	 *
	 * @param array $data Document data.
	 * @return int|WP_Error Document ID or error.
	 */
	public function create( $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_documents';

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'filename'    => $data['filename'],
				'filepath'    => $data['filepath'],
				'filesize'    => $data['filesize'],
				'mime_type'   => $data['mime_type'],
				'entity_type' => $data['entity_type'],
				'entity_id'   => $data['entity_id'],
				'uploaded_by' => $data['uploaded_by'],
				'uploaded_at' => $data['uploaded_at'],
			),
			array( '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			return new WP_Error( 'db_error', __( 'Failed to create document record', 'synpat-platform' ) );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get a document by ID
	 *
	 * @param int $document_id Document ID.
	 * @return array|null Document data or null.
	 */
	public function get( $document_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_documents';

		$document = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$document_id
			),
			ARRAY_A
		);

		return $document;
	}

	/**
	 * Get documents by entity
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @return array List of documents.
	 */
	public function get_by_entity( $entity_type, $entity_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_documents';

		$documents = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE entity_type = %s AND entity_id = %d ORDER BY uploaded_at DESC",
				$entity_type,
				$entity_id
			),
			ARRAY_A
		);

		return $documents ?: array();
	}

	/**
	 * Delete a document
	 *
	 * @param int $document_id Document ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete( $document_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'synpat_documents';

		$deleted = $wpdb->delete(
			$table_name,
			array( 'id' => $document_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error( 'db_error', __( 'Failed to delete document record', 'synpat-platform' ) );
		}

		return true;
	}

	/**
	 * Get all documents
	 *
	 * @param array $args Query arguments.
	 * @return array List of documents.
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'  => 50,
			'offset' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = $wpdb->prefix . 'synpat_documents';

		$documents = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY uploaded_at DESC LIMIT %d OFFSET %d",
				$args['limit'],
				$args['offset']
			),
			ARRAY_A
		);

		return $documents ?: array();
	}
}
