<?php
/**
 * Cache manager with transient wrapper and tags.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core\Cache;

/**
 * Cache_Manager Class
 */
class Cache_Manager {
	
	/**
	 * Cache prefix
	 *
	 * @var string
	 */
	private $prefix = 'synpat_';
	
	/**
	 * Get cached value
	 *
	 * @param string $key Cache key
	 * @return mixed|false
	 */
	public function get( $key ) {
		return get_transient( $this->prefix . $key );
	}
	
	/**
	 * Set cached value
	 *
	 * @param string $key        Cache key
	 * @param mixed  $value      Value to cache
	 * @param int    $expiration Expiration in seconds (default: 1 hour)
	 * @param array  $tags       Optional tags for group invalidation
	 * @return bool
	 */
	public function set( $key, $value, $expiration = 3600, $tags = array() ) {
		$result = set_transient( $this->prefix . $key, $value, $expiration );
		
		if ( $result && ! empty( $tags ) ) {
			$this->add_tags( $key, $tags );
		}
		
		return $result;
	}
	
	/**
	 * Delete cached value
	 *
	 * @param string $key Cache key
	 * @return bool
	 */
	public function delete( $key ) {
		$this->remove_from_tags( $key );
		return delete_transient( $this->prefix . $key );
	}
	
	/**
	 * Clear cache by tag
	 *
	 * @param string $tag Tag to clear
	 * @return bool
	 */
	public function clear_tag( $tag ) {
		$keys = $this->get_keys_by_tag( $tag );
		
		foreach ( $keys as $key ) {
			$this->delete( $key );
		}
		
		delete_option( $this->prefix . 'tag_' . $tag );
		
		return true;
	}
	
	/**
	 * Clear all plugin cache
	 *
	 * @return bool
	 */
	public function clear_all() {
		global $wpdb;
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $this->prefix ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%'
			)
		);
		
		return true;
	}
	
	/**
	 * Remember (get or set)
	 *
	 * @param string   $key        Cache key
	 * @param callable $callback   Callback to generate value if not cached
	 * @param int      $expiration Expiration in seconds
	 * @param array    $tags       Optional tags
	 * @return mixed
	 */
	public function remember( $key, $callback, $expiration = 3600, $tags = array() ) {
		$value = $this->get( $key );
		
		if ( false === $value ) {
			$value = call_user_func( $callback );
			$this->set( $key, $value, $expiration, $tags );
		}
		
		return $value;
	}
	
	/**
	 * Add tags to a cache key
	 *
	 * @param string $key  Cache key
	 * @param array  $tags Tags array
	 */
	private function add_tags( $key, $tags ) {
		foreach ( $tags as $tag ) {
			$tag_keys = $this->get_keys_by_tag( $tag );
			$tag_keys[] = $key;
			$tag_keys = array_unique( $tag_keys );
			
			update_option( $this->prefix . 'tag_' . $tag, $tag_keys, false );
		}
	}
	
	/**
	 * Remove key from all tags
	 *
	 * @param string $key Cache key
	 */
	private function remove_from_tags( $key ) {
		global $wpdb;
		
		$tags = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				$wpdb->esc_like( $this->prefix . 'tag_' ) . '%'
			)
		);
		
		foreach ( $tags as $tag_option ) {
			$tag_keys = get_option( $tag_option, array() );
			$tag_keys = array_diff( $tag_keys, array( $key ) );
			update_option( $tag_option, $tag_keys, false );
		}
	}
	
	/**
	 * Get keys by tag
	 *
	 * @param string $tag Tag name
	 * @return array
	 */
	private function get_keys_by_tag( $tag ) {
		return get_option( $this->prefix . 'tag_' . $tag, array() );
	}
	
	/**
	 * Get cache statistics
	 *
	 * @return array
	 */
	public function get_stats() {
		global $wpdb;
		
		$transient_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $this->prefix ) . '%'
			)
		);
		
		$tag_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				$wpdb->esc_like( $this->prefix . 'tag_' ) . '%'
			)
		);
		
		return array(
			'transients' => absint( $transient_count ),
			'tags'       => absint( $tag_count ),
		);
	}
}
