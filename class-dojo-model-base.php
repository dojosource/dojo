<?php
/**
 * Base class for model classes.
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Model_Base extends Dojo_WP_Base {

	/**
	 * Filter an array of parameters to a limited set. Any keys not in the filter will be removed.
	 *
	 * @param array $params
	 * @param array $filter
	 *
	 * @return array
	 */
	protected function filter_params( $params, $filter ) {
		$keys = array_flip( $filter );
		$response = array();

		foreach ( $params as $param => $value ) {
			if ( isset( $keys[ $param ] ) ) {
				$response[ $param ] = $value;
			}
		}
		return $response;
	}

	/**
	 * Fix dates in a parameter set to be mysql friendly
	 *
	 * @param array ( name => value ) $params
	 * @param array ( name ) $names Name of parameters to fix
	 *
	 * @return array Modified parameter set
	 */
	protected function fix_dates( $params, $names ) {
		foreach ( $names as $name ) {
			if ( isset( $params[ $name ] ) ) {
				// null is a valid value for a database parameter
				if ( null !== $params[ $name ] ) {
					$params[ $name ] = $this->date( 'Y-m-d 00:00:00', strtotime( $params[ $name ] ) );
				}
			}
		}
		return $params;
	}

	/**
	 * Fix a single date to be mysql friendly
	 *
	 * @param $date
	 * @return string
	 */
	protected function fix_date( $date ) {
		return $this->date( 'Y-m-d 00:00:00', strtotime( $date ) );
	}

	/**
	 * Escapes a string with quotes for direct placement in a query.
	 * Arrays are comma delimited for direct use in IN statements
	 * @param string $val
	 * @return string
	 */
	protected function esc_sql_quote( $val ) {
		if ( is_array( $val ) ) {
			return implode( ',', array_map( array( $this, 'esc_sql_quote' ), $val ) );
		}
		return "'" . esc_sql( $val ) . "'";
	}
}
 
