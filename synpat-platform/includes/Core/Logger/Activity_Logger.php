<?php
/**
 * Activity logging for audit trail.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core\Logger;

use SynPat\Core\Database\Query_Builder;

/**
 * Activity_Logger Class
 */
class Activity_Logger {
	
	/**
	 * Log an activity
	 *
	 * @param string $action      Action performed
	 * @param string $entity_type Entity type (optional)
	 * @param int    $entity_id   Entity ID (optional)
	 * @param string $description Description (optional)
	 * @param array  $meta_data   Additional metadata (optional)
	 * @return int|false Log ID or false on failure
	 */
	public function log( $action, $entity_type = null, $entity_id = null, $description = null, $meta_data = array() ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return false;
		}
		
		$data = array(
			'user_id'     => $user_id,
			'action'      => sanitize_text_field( $action ),
			'entity_type' => $entity_type ? sanitize_text_field( $entity_type ) : null,
			'entity_id'   => $entity_id ? absint( $entity_id ) : null,
			'description' => $description ? sanitize_textarea_field( $description ) : null,
			'meta_data'   => ! empty( $meta_data ) ? wp_json_encode( $meta_data ) : null,
			'ip_address'  => $this->get_ip_address(),
			'user_agent'  => $this->get_user_agent(),
		);
		
		$query = new Query_Builder( 'activity_log' );
		return $query->insert( $data );
	}
	
	/**
	 * Get activity logs
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_logs( $args = array() ) {
		$defaults = array(
			'user_id'     => null,
			'action'      => null,
			'entity_type' => null,
			'entity_id'   => null,
			'limit'       => 50,
			'offset'      => 0,
			'order_by'    => 'created_at',
			'order'       => 'DESC',
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		$query = new Query_Builder( 'activity_log' );
		
		if ( $args['user_id'] ) {
			$query->where( 'user_id', $args['user_id'] );
		}
		
		if ( $args['action'] ) {
			$query->where( 'action', $args['action'] );
		}
		
		if ( $args['entity_type'] ) {
			$query->where( 'entity_type', $args['entity_type'] );
		}
		
		if ( $args['entity_id'] ) {
			$query->where( 'entity_id', $args['entity_id'] );
		}
		
		$query->order_by( $args['order_by'], $args['order'] )
		      ->limit( $args['limit'] )
		      ->offset( $args['offset'] );
		
		return $query->get();
	}
	
	/**
	 * Get activity count
	 *
	 * @param array $args Query arguments
	 * @return int
	 */
	public function count_logs( $args = array() ) {
		$query = new Query_Builder( 'activity_log' );
		
		if ( isset( $args['user_id'] ) && $args['user_id'] ) {
			$query->where( 'user_id', $args['user_id'] );
		}
		
		if ( isset( $args['action'] ) && $args['action'] ) {
			$query->where( 'action', $args['action'] );
		}
		
		if ( isset( $args['entity_type'] ) && $args['entity_type'] ) {
			$query->where( 'entity_type', $args['entity_type'] );
		}
		
		if ( isset( $args['entity_id'] ) && $args['entity_id'] ) {
			$query->where( 'entity_id', $args['entity_id'] );
		}
		
		return $query->count();
	}
	
	/**
	 * Delete old logs
	 *
	 * @param int $days Number of days to keep
	 * @return int|false Number of deleted rows or false
	 */
	public function cleanup( $days = 90 ) {
		global $wpdb;
		
		$table = $wpdb->prefix . 'synpat_activity_log';
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
		
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				$cutoff
			)
		);
	}
	
	/**
	 * Get user IP address
	 *
	 * @return string
	 */
	private function get_ip_address() {
		$ip = '';
		
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		
		return $ip;
	}
	
	/**
	 * Get user agent
	 *
	 * @return string
	 */
	private function get_user_agent() {
		return ! empty( $_SERVER['HTTP_USER_AGENT'] ) 
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: '';
	}
}
