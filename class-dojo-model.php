<?php
/**
 * Dojo model
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Dojo_Model extends Dojo_Model_Base {
	private static $instance;

	// table names
	private $event_log;

	protected function __construct() {
		global $wpdb;

		$this->event_log        = $wpdb->prefix . 'dojo_event_log';
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Create a new event log entry
	 *
	 * @param int $user_id
	 * @param string $extension
	 * @param int $event
	 *
	 * @return int
	 */
	public function create_event_log( $event, $user_id = null, $extension = null ) {
		global $wpdb;

		$insert_params = array(
			'event'     => $event,
			'user_id'   => $user_id,
			'extension' => $extension,
		);

		$wpdb->insert( $this->event_log, $insert_params );
		return $wpdb->insert_id;
	}
}


