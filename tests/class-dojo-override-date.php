<?php
/**
 * Class Dojo_Override_Date
 *
 * Utility class used in tests to override current time on date and time functions
 */

if ( !defined( 'ABSPATH' ) ) {  die(); }

class Dojo_Override_Date {
    private static $override_timestamp = null;
    private static $override_active = false;

    public static function set_override( $unix_timestamp ) {
        self::$override_timestamp = $unix_timestamp;

        if ( ! self::$override_active ) {
            runkit_function_redefine( 'date', '$format, $timestamp = NULL', 'return Dojo_Override_Date::date($format, $timestamp);' );
            runkit_function_redefine( 'time', '', 'return Dojo_Override_Date::time();' );
            self::$override_active = true;
        }
    }

    public static function date( $format, $timestamp ) {
        if ( null === $timestamp ) {
            $timestamp = self::time();
        }
        if ( self::$override_active ) {
            return __date__( $format, $timestamp );
        }
        return date( $format, $timestamp );
    }

    public static function time() {
        if ( self::$override_active ) {
            if ( null === self::$override_timestamp ) {
                return __time__();
            }
            return self::$override_timestamp;
        }
        return time();
    }
}
