<?php
/**
 * Base class for all dojo classes with utilities for working with wordpress api
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_WP_Base {
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
     *
     * @param mixed $actions Name of a single action or array of actions
     *
     * @return void
     */
    protected function register_action_handlers( $actions ) {
        if ( ! is_array( $actions ) ) {
            $actions = array( $actions );
        }
        foreach ( $actions as $action ) {
            add_action( $action, array( $this, 'handle_' . $action ) );
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
            $tags = array( $tags );
        }
        foreach ( $tags as $tag ) {
            add_filter( $tag, array( $this, 'filter_' . $tag ), $priority, $accepted_args );
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
     * @return $class
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

