<?php
/**
 * Base class for main installer and extension installers.
 *
 * Derived classes should implement revision methods rev_1, rev_2... etc starting with 1 and incrementing by 1.
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Installer_Base extends Dojo_WP_Base {
	private $installer_name;

	protected function __construct( $class ) {
		$this->installer_name = $class;
	}

	/**
	 * Get current revision
	 *
	 * @return int
	 */
	public function get_rev() {
		return (int) get_option( $this->installer_name . '_revision' );
	}

	protected function set_rev( $rev ) {
		update_option( $this->installer_name . '_revision', $rev );
	}

	/**
	 * Called when plugin or extension is activated. Does integrity check if rev is already > 0.
	 *
	 * @return void
	 */
	public function activate() {
		if ( $this->get_rev() > 0 ) {
			$this->check_integrity();
		}
		$this->update();
	}

	/**
	 * Called when plugin or extension is deactivated.
	 * Override in the derived class to handle any necessary cleanup.
	 *
	 * @return void
	 */
	public function deactivate() {
	}

	/**
	 * Called when the plugin is being uninstalled.
	 * Override to do full cleanup including removing tables.
	 */
	public function uninstall() {
		global $wpdb;

		// remove revision tracking option
		$sql = $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name = %s", $this->installer_name . '_revision' );
		$wpdb->query( $sql );
	}

	/**
	 * Override in derived class to at least make sure expected tables are present for current rev
	 *
	 * @return void
	 */
	public function check_integrity() {
	}

	/**
	 * Run updates on this installer.
	 *
	 * @return void
	 */
	public function update() {
		$current_rev = $this->get_rev();

		$next_rev = $current_rev + 1;
		while ( method_exists( $this, 'rev_' . $next_rev ) ) {
			$method = 'rev_' . $next_rev;

			error_log( $this->installer_name . ' - Updating rev ' . $next_rev );

			ob_start();
			if ( $this->$method() ) {
				$this->set_rev( $next_rev );
				$next_rev ++;
			} else {
				error_log( $this->installer_name . ' - Error updating rev ' . $next_rev );
				break;
			}
			$output = ob_get_clean();

			// send captured output to error log.
			if ( '' != $output ) {
				error_log( $this->installer_name . ' - captured output during rev update:' );
				error_log( $output );
			}
		}
	}

	/**
	 * Utility function to check if table exists in the database
	 *
	 * @param mixed $table Name of table or array of table names to check for
	 *
	 * @return bool True if all tables exist
	 */
	public function table_exists( $table ) {
		global $wpdb;

		if ( is_array( $table ) ) {
			foreach ( $table as $table_name ) {
				if ( ! $this->table_exists( $table_name ) ) {
					return false;
				}
			}
			return true;
		}

		$sql = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table );
		return null !== $wpdb->get_row( $sql );
	}

	/**
	 * Utility function to drop a set of tables in the database
	 *
	 * @param $tables
	 *
	 * @erturn void
	 */
	public function drop_tables( $tables ) {
		global $wpdb;

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS $table" );
		}
	}
}

