<?php
/**
 * Main installer
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }


class Dojo_Installer extends Dojo_Installer_Base {
	private static $instance;

	// table names
	private $event_log;

	protected function __construct() {
		global $wpdb;

		parent::__construct( __CLASS__ );

		$this->event_log    = $wpdb->prefix . 'dojo_event_log';
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function check_integrity() {
		$rev = $this->get_rev();

		if ( $rev >= 1 ) {
			$rev1_tables = array(
				$this->event_log,
			);

			if ( ! $this->table_exists( $rev1_tables ) ) {
				$this->set_rev( 0 );
			}
		}
	}

	public function activate() {
		parent::activate();

		// make sure we have a dummy page to use as a placeholder
		Dojo::instance()->get_dummy_page();

		// make sure scheduler has us for hourly updates
		if ( false === wp_next_scheduled( 'dojo_update' ) ) {
			wp_schedule_event( time(), 'hourly', 'dojo_update' );
		}
	}

	public function deactivate() {
		parent::deactivate();

		wp_clear_scheduled_hook( 'dojo_update' );
	}

	public function uninstall() {
		global $wpdb;

		parent::uninstall();

		// drop tables
		$this->drop_tables( array(
			$this->event_log,
		) );

		// remove dummy page
		wp_trash_post( get_option( 'dojo_dummy_page' ) );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'dojo_dummy_page'" );

		// remove plugin options
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name = 'dojo_options'" );
	}

	public function rev_1() {
		global $wpdb;

		$wpdb->query( '
			CREATE TABLE ' . $this->event_log . ' (
			ID INT NOT NULL AUTO_INCREMENT,
			timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
			user_id INT NULL,
			extension VARCHAR(255) NULL,
			event TEXT NULL,
			PRIMARY KEY (ID),
			KEY user_id (user_id),
			KEY extension (extension));
		' );

		return true;
	}
}

