<?php
/**
 * Event extension model
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Event_Model extends Dojo_Model_Base {
    private static $instance;

    // table names
    private $registrants;

    protected function __construct() {
        global $wpdb;

        $this->registrants  = $wpdb->prefix . 'dojo_event_registrants';
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Create a new event registrant.
     *
     * @param int $event_id
     * @param array $params
     *
     * @return int Registrant id
     */
    public function create_registrant( $event_id, $params ) {
        global $wpdb;

        // filter parameters
        $insert_params = $this->filter_params( $params, array(
            'user_id',
            'student_id',
            'guest_id',
        ) );

        $insert_params['event_id'] = $event_id;

        $wpdb->insert( $this->registrants, $insert_params );
        return $wpdb->insert_id;
    }

    /**
     * Get all registrants for a given event
     *
     * @param int $event_id
     *
     * @return array( object )
     */
    public function get_event_registrants( $event_id ) {
        global $wpdb;

        $students_table = Dojo_Membership::instance()->model()->students;

        $sql = $wpdb->prepare( "SELECT * FROM $this->registrants r
            LEFT JOIN $students_table s ON r.student_id = s.ID
            WHERE event_id = %d", $event_id );
        return $wpdb->get_results( $sql );
    }

    /**
     * Get all event registrants for a given user
     *
     * @param int $event_id
     * @param int $user_id
     *
     * @return array( object )
     */
    public function get_event_user_registrants( $event_id, $user_id ) {
        global $wpdb;

        $sql = $wpdb->prepare( "SELECT * FROM $this->registrants WHERE event_id = %d AND user_id = %d", $event_id, $user_id );
        return $wpdb->get_results( $sql );
    }
}
