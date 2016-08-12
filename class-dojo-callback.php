<?php
/**
 * Dojo callbacks. Configure callbacks to a function or method with a parameter set
 * then execute the callback later by id.
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Callback extends Dojo_WP_Base {
    
    private function __construct() {
    }

    protected static function callbacks_table() {
        global $wpdb;
        return $wpdb->prefix . 'dojo_callbacks';
    }

    /**
     * Register a callback. To execute later call execute with the id returned.
     * Only specify a class if the class implements singleton pattern
     *
     * @param string $class Optional class name when calling a method.
     * @param string $function
     * @param array $args Optional arguments to pass to callback function
     *
     * @return int Callback id
     */
    public static function register( $class, $function, $args = array() ) {
        global $wpdb;

        $args = serialize( $args );
        $wpdb->insert( self::callbacks_table(), array(
            'class' => $class,
            'function' => $function,
            'args' => $args,
        ) );

        return $wpdb->insert_id;
    }

    /**
     * Execute a saved callback.
     *
     * @param int Callback id returned from register
     *
     * @return void
     */
    public static function execute( $callback_id ) {
        global $wpdb;

        $table = self::callbacks_table();
        $sql = $wpdb->prepare( "SELECT * FROM $table WHERE ID = %d", $callback_id );
        $record = $wpdb->get_row( $sql );

        // if record exists and callback hasn't been executed yet
        if ( null !== $record && null === $record->callback_executed ) {
            $target = null;
            $response = null;
            if ( empty( $record->class ) ) {
                // simple function callback
                $target = $record->function;
            } else {
                if ( method_exists( $record->class, 'instance' ) ) {
                    // get singleton instance to call
                    $obj = call_user_func( array( $record->class, 'instance' ) );
                    $target = array( $obj, $record->function );
                } elseif ( method_exists( $record->class, $record->function ) ) {
                    // try as static method call
                    $target = array( $record->class, $record->function );
                }
            }
            if ( null !== $target ) {
                try {
                    $args = unserialize( $record->args );
                    if ( is_array( $args ) ) {
                        // callback with args
                        $response = call_user_func_array( $target, $args );
                    } else {
                        // no args
                        $response = call_user_func( $target );
                    }
                } catch ( Exception $ex ) {
                    $response = 'Exception (' . get_class( $ex ) . '): ' . $ex->get_message();
                }
            }
            else {
                $response = 'Unable to execute callback, invalid target';
            }

            $where = array( 'ID' => $callback_id );
            $params = array(
                'callback_executed' => current_time( 'mysql' ),
                'callback_response' => $response,
            );
            $wpdb->update( $table, $params, $where );
        }
    }
}


