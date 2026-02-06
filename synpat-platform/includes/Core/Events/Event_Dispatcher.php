<?php
/**
 * Custom event system for internal plugin communication.
 *
 * @package SynPatPlatform
 */

namespace SynPat\Core\Events;

/**
 * Event_Dispatcher Class
 */
class Event_Dispatcher {
	
	/**
	 * Event listeners
	 *
	 * @var array
	 */
	private $listeners = array();
	
	/**
	 * Add event listener
	 *
	 * @param string   $event    Event name
	 * @param callable $callback Callback function
	 * @param int      $priority Priority (default: 10)
	 */
	public function listen( $event, $callback, $priority = 10 ) {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			$this->listeners[ $event ] = array();
		}
		
		if ( ! isset( $this->listeners[ $event ][ $priority ] ) ) {
			$this->listeners[ $event ][ $priority ] = array();
		}
		
		$this->listeners[ $event ][ $priority ][] = $callback;
	}
	
	/**
	 * Dispatch event
	 *
	 * @param string $event Event name
	 * @param mixed  $data  Optional data to pass to listeners
	 * @return mixed
	 */
	public function dispatch( $event, $data = null ) {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			return $data;
		}
		
		// Sort by priority
		ksort( $this->listeners[ $event ] );
		
		foreach ( $this->listeners[ $event ] as $priority_listeners ) {
			foreach ( $priority_listeners as $callback ) {
				$data = call_user_func( $callback, $data );
			}
		}
		
		return $data;
	}
	
	/**
	 * Check if event has listeners
	 *
	 * @param string $event Event name
	 * @return bool
	 */
	public function has_listeners( $event ) {
		return isset( $this->listeners[ $event ] ) && ! empty( $this->listeners[ $event ] );
	}
	
	/**
	 * Remove event listener
	 *
	 * @param string   $event    Event name
	 * @param callable $callback Callback to remove
	 * @return bool
	 */
	public function remove_listener( $event, $callback ) {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			return false;
		}
		
		foreach ( $this->listeners[ $event ] as $priority => $callbacks ) {
			$key = array_search( $callback, $callbacks, true );
			if ( false !== $key ) {
				unset( $this->listeners[ $event ][ $priority ][ $key ] );
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Clear all listeners for an event
	 *
	 * @param string $event Event name
	 */
	public function clear_listeners( $event ) {
		if ( isset( $this->listeners[ $event ] ) ) {
			unset( $this->listeners[ $event ] );
		}
	}
}
