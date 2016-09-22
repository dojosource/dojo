<?php
/**
 * Event extension installer
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Event_Installer extends Dojo_Installer_Base {
    private static $instance;

    // table names
    private $registrants;

    protected function __construct() {
        global $wpdb;

        parent::__construct( __CLASS__ );

        $this->registrants  = $wpdb->prefix . 'dojo_event_registrants';
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
                $this->registrants,
            );

            if ( ! $this->table_exists( $rev1_tables ) ) {
                $this->set_rev( 0 );
            }
        }
    }

    public function uninstall() {
        parent::uninstall();

        $this->drop_tables( array(
            $this->registrants,
        ) );
    }

    public function rev_1() {
        global $wpdb;

        $wpdb->query( '
            CREATE TABLE ' . $this->registrants . ' (
            ID INT NOT NULL AUTO_INCREMENT,
            timestamp TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            event_id INT NULL,
            user_id INT NULL,
            student_id INT NULL,
            guest_id INT NULL,
            PRIMARY KEY (ID),
            KEY event_id (event_id),
            KEY user_id (user_id),
            KEY student_id (student_id),
            KEY guest_id (guest_id));
        ' );

        return true;
    }
}

