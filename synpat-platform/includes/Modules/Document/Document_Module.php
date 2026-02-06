<?php
/**
 * Document Module
 *
 * @package SynPatPlatform\Modules\Document
 */

namespace SynPat\Modules\Document;

use SynPat\Modules\Document\Storage\Local_Storage;

/**
 * Document Module Class
 */
class Document_Module {

	/**
	 * Document Service instance
	 *
	 * @var Document_Service
	 */
	private $service;

	/**
	 * Document Repository instance
	 *
	 * @var Document_Repository
	 */
	private $repository;

	/**
	 * Initialize the module
	 */
	public function __construct() {
		$this->init_dependencies();
		$this->init_hooks();
	}

	/**
	 * Initialize module dependencies
	 */
	private function init_dependencies() {
		$storage          = new Local_Storage();
		$this->repository = new Document_Repository();
		$this->service    = new Document_Service( $storage, $this->repository );
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'wp_ajax_synpat_upload_document', array( $this, 'ajax_upload_document' ) );
		add_action( 'wp_ajax_synpat_delete_document', array( $this, 'ajax_delete_document' ) );
	}

	/**
	 * Register document custom post type
	 */
	public function register_post_type() {
		register_post_type(
			'synpat_document',
			array(
				'labels'              => array(
					'name'          => __( 'Documents', 'synpat-platform' ),
					'singular_name' => __( 'Document', 'synpat-platform' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'capability_type'     => 'post',
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * AJAX handler for document upload
	 */
	public function ajax_upload_document() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$entity_type = sanitize_text_field( $_POST['entity_type'] ?? '' );
		$entity_id   = absint( $_POST['entity_id'] ?? 0 );

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded', 'synpat-platform' ) ) );
		}

		$result = $this->service->upload( $_FILES['file'], $entity_type, $entity_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'document' => $result ) );
	}

	/**
	 * AJAX handler for document deletion
	 */
	public function ajax_delete_document() {
		check_ajax_referer( 'synpat_nonce', 'nonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'synpat-platform' ) ) );
		}

		$document_id = absint( $_POST['document_id'] ?? 0 );

		$result = $this->service->delete( $document_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Document deleted', 'synpat-platform' ) ) );
	}

	/**
	 * Get the document service instance
	 *
	 * @return Document_Service
	 */
	public function get_service() {
		return $this->service;
	}
}
