<?php
/**
 * Main installer
 */


class Dojo_Installer extends Dojo_Installer_Base {
    private static $instance;

    // table names
    private $callbacks;
    private $event_log;

    protected function __construct() {
        global $wpdb;

        parent::__construct( __CLASS__ );

        $this->callbacks    = $wpdb->prefix . 'dojo_callbacks';
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
                $this->callbacks,
            );

            if ( ! $this->table_exists( $rev1_tables ) ) {
                $this->set_rev( 0 );
            }
        }
    }

    public function rev_1() {
        global $wpdb;

        $wpdb->query( '
            CREATE TABLE ' . $this->callbacks . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            class VARCHAR(255) NULL,
            function VARCHAR(255) NULL,
            args TEXT NULL,
            callback_executed DATETIME NULL,
            callback_response TEXT NULL,
            PRIMARY KEY (ID),
            KEY callback_executed (callback_executed));
        ' );

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

