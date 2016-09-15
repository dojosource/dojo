<?php
/**
 * Base class for all dojo classes with utilities for working with wordpress api
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_WP_Base {
    private static $override_timestamp = NULL;

    /**
     * Used for testing purposes to override current time. The plugin uses the base
     * class date and time functions rather than the php versions directly.
     *
     * @param $unix_timestamp
     *
     * @return void
     */
    public static function set_timestamp_override( $unix_timestamp ) {
        self::$override_timestamp = $unix_timestamp;
    }

    /**
     * Wrapper function to php date(). Used by plugin to allow for unit testing.
     *
     * @param $format
     * @param $timestamp
     *
     * @return string
     */
    public function date( $format, $timestamp = NULL ) {
        if ( null === $timestamp ) {
            $timestamp = self::time();
        }
        return date( $format, $timestamp );
    }

    /**
     * Wrapper function to php time(). Used by plugin to allow for unit testing.
     * Supports optional type parameter for mysql format. Use to replace wordpress current_time().
     *
     * @param string $type Type of timestamp to return. Defaults to unix timestamp
     *
     * @return mixed
     */
    public function time( $type = 'unix' ) {
        if ( null === self::$override_timestamp ) {
            $timestamp = time();
        } else {
            $timestamp = self::$override_timestamp;
        }

        if ( 'mysql' === $type ) {
            return date( 'Y-m-d H:i:s', $timestamp );
        }
        return $timestamp;
    }

    /**
     * Output a debug message labeled with class::function(line numer)
     *
     * @param string $message
     *
     * @return void
     */
    protected function debug( $message ) {
        $trace = debug_backtrace();
        error_log( $trace[1]['class'] . '::' . $trace[1]['function'] . '(' . $trace[0]['line'] . ') - ' . $message );
    }

    /**
     * Registers one or more action handlers. Expects handler functions to be methods
     * of the form handle_$action
     * To include optional priority or accepted_args to just a single action handler
     * use an array for the action:
     * array( 'action_name', 10, 1 )
     *
     * @param mixed $actions Name of a single action or array of actions
     * @param int $priority
     * @param int $accepted_args
     *
     * @return void
     */
    protected function register_action_handlers( $actions, $priority = 10, $accepted_args = 1 ) {
        if ( ! is_array( $actions ) ) {
            $actions = array( array( $actions, $priority, $accepted_args ) );
        }
        foreach ( $actions as $action ) {
            if ( is_array( $action ) ) {
                $args = $action;
                $action = $args[0];
                $args[0] = array( $this, 'handle_' . str_replace( '-', '_', $action ) );
                array_unshift( $args, $action );
                call_user_func_array( 'add_action', $args );
            } else {
                add_action( $action, array( $this, 'handle_' . str_replace( '-', '_', $action ) ), $priority, $accepted_args );
            }
        }
    }

    /**
     * Registers one or more filters. Expects handler functions to be methods
     * of the form filter_$tag. 
     *
     * @param mixed $tags Name of a single tag or array of tags
     * @param int $priority Applied to all filters being registered
     * @param int $accepted_args Applied to all filters being registered
     *
     * @return void
     */
    protected function register_filters( $tags, $priority = 10, $accepted_args = 1 ) {
        if ( ! is_array( $tags ) ) {
            $tags = array( array( $tags, $priority, $accepted_args ) );
        }
        foreach ( $tags as $tag ) {
            if ( is_array( $tag ) ) {
                $args = $tag;
                $tag = $args[0];
                $args[0] = array( $this, 'filter_' . str_replace( '-', '_', $tag ) );
                array_unshift( $args, $tag );
                call_user_func_array( 'add_filter', $args );
            } else {
                add_filter( $tag, array( $this, 'filter_' . str_replace( '-', '_', $tag ) ), $priority, $accepted_args );
            }
        }
    }

    /**
     * Registers one or more shortcodes. Expects handler functions to be methods
     * of the form shortcode_$shortcode
     *
     * @param mixed $shortcodes Name of a single shortcode or array of shortcodes
     *
     * @return void
     */
    protected function register_shortcodes( $shortcodes ) {
        if ( ! is_array( $shortcodes ) ) {
            $shortcodes = array( $shortcodes );
        }
        foreach ( $shortcodes as $shortcode ) {
            add_shortcode( $shortcode, array( $this, 'shortcode_' . $shortcode ) );
        }
    }

    /**
     * Gets an instance of a singleton class by name
     *
     * @param string $class
     *
     * @return object
     */
    protected function get_instance( $class ) {
        return call_user_func( array( $class, 'instance' ) );
    }

    /**
     * Gets a dojo setting from the settings object
     *
     * @param string $option_id
     *
     * @return mixed
     */
    protected function get_setting( $option_id ) {
        return Dojo_Settings::instance()->get( $option_id );
    }

    /**
     * Sets a given default value for all missing parameters in an associative array
     *
     * @param mixed $default
     * @param array $params
     * @param array $names Names of expected parameters in $params array
     *
     * @return void
     */
    protected function default_missing( $default, $params, $names ) {
        foreach ( $names as $name ) {
            if ( ! isset( $params[ $name ] ) ) {
                $params[ $name ] = $default;
            }
        }
    }
}

