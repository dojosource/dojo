<?php
/**
 * Dojo class autoloader
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

Dojo_Loader::instance();

class Dojo_Loader {
	private static $instance;
	private static $plugin_path;

	// initialize class locations
	private static $class_paths = array(
		'Dojo'                      => '/',
		'Dojo_Extension'            => '/extensions/',
		'Dojo_Extension_Manager'    => '/extensions/',
	);

	// interfaces are all pre-defined here
	private static $interface_paths = array(
		'Dojo_Payment'  => '/extensions/',
	);

	private function __construct() {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Called by the extension manager to add extensions to loader
	 *
	 * @param $class
	 * @param $path
	 *
	 * @return void
	 */
	public static function add_extension( $class, $path = null ) {
		if ( null === $path ) {
			$path = '/extensions/' . str_replace( '_', '-', strtolower( $class )) . '/';
		}

		self::$class_paths[ $class ]                = $path;
		self::$class_paths[ $class . '_Installer' ] = $path;
		self::$class_paths[ $class . '_Model' ]     = $path;
	}

	/**
	 * Get singleton instance
	 *
	 * @return Dojo_Loader
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Check if class is available. Primarily used to check extension availability.
	 *
	 * @param string $class The class name
	 *
	 * @return bool true if class is available
	 */
	public static function available( $class ) {
		return isset( self::$class_paths[ $class ] );
	}

	/**
	 * Get the root plugin path
	 *
	 * @return string
	 */
	public static function plugin_path() {
		if ( ! self::$plugin_path ) {
			self::$plugin_path = dirname( __FILE__ );
		}
		return self::$plugin_path;
	}

	/**
	 * registered autoloader
	 */
	public function autoload( $class ) {
		// change format Class_Name to class-name
		$path_format = str_replace( '_', '-', strtolower( $class ));
		$plugin_path = self::plugin_path();

		if ( isset( self::$class_paths[ $class ] ) ) {
			$path = $plugin_path . self::$class_paths[ $class ] . 'class-' . $path_format . '.php';
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		} elseif (isset( self::$interface_paths[ $class ] ) ) {
			$path = $plugin_path . self::$interface_paths[ $class ] . 'interface-' . $path_format . '.php';
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		} else {
			// default to root directory but only if class prefix is correct
			if ( 0 === strpos( $path_format, 'dojo-' ) ) {
				$path = $plugin_path . '/class-' . $path_format . '.php';
				if ( file_exists( $path ) ) {
					require_once $path;
				}
			}
		}
	}
}

