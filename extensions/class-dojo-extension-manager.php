<?php
/**
 * Dojo extension manager
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Extension_Manager extends Dojo_WP_Base {
	private static $instance;

	private $extensions = array();
	private $plugin_extensions = array();
	private $active_extensions = array();
	private $core_extensions = array();

	private function __construct() {
		$this->register_action_handlers( array (
			array( 'plugins_loaded', 1, 0 ),
//			array( 'upgrader_process_complete', 10, 2 ),
		) );

		$this->register_filters( array (
//			'pre_set_site_transient_update_plugins',
		) );

		// core list of extensions will always be enabled
		if ( ! defined( 'DOJO_NO_CORE' ) || ! DOJO_NO_CORE ) {
			$this->core_extensions = array(
				'Dojo_Membership' => 'Dojo_Membership',
			);
		}

		$this->find_extensions( plugin_dir_path( __FILE__ ) );

		// load active extensions
		foreach ( $this->active_extensions as $extension_class ) {
			$this->get_instance( $extension_class );
		}
	}

	private function find_extensions( $path, $loader_path = null ) {
		$dirs = glob( $path . 'dojo-*', GLOB_ONLYDIR );
		foreach ( $dirs as $dir ) {
			// get extension directories and convert to class names
			$extension = basename( $dir );
			$class = '';
			foreach ( explode( '-', $extension ) as $part ) {
				if ( '' != $class ) {
					$class .= '_';
				}
				$class .= ucfirst( $part );
			}
			$this->extensions[ $class ] = $class;
			$this->active_extensions[ $class ] = $class;

			// tell auto loader about this extension class
			Dojo_Loader::add_extension( $class, $loader_path );
		}
	}

	private function include_plugin_extension( $name ) {
		$path = Dojo_Loader::plugin_path() . '/dojo-' . $name . '/';
		$class = 'Dojo_' . ucfirst( $name );
		if ( file_exists( $path . 'dojo-' . $name . '.php' ) ) {
			$this->plugin_extensions[ $class ] = $class;
			$this->active_extensions[ $class ] = $class;
			Dojo_Loader::add_extension( $class, '/dojo-' . $name . '/' );
			$this->get_instance( $class );
		}
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Get list of all extensions
	 *
	 * @return array(string)
	 */
	public function extensions() {
		return $this->extensions;
	}

	/**
	 * Get list of all plugin extensions
	 *
	 * @return array(string)
	 */
	public function plugin_extensions() {
		return $this->plugin_extensions;
	}

	/**
	 * Get list of active extensions
	 *
	 * @return array(string)
	 */
	public function active_extensions() {
		return $this->active_extensions;
	}

	/**
	 * Get list of core extensions that are always enabled
	 *
	 * @return array(string)
	 */
	public function core_extensions() {
		return $this->core_extensions;
	}

	public function handle_activate() {
		foreach ( $this->extensions as $extension_class ) {
			$installer_class = $extension_class . '_Installer';
			if ( class_exists( $installer_class ) ) {
				$this->get_instance( $installer_class )->activate();
			}
		}
	}

	public function handle_deactivate() {
		foreach ( $this->extensions as $extension_class ) {
			$installer_class = $extension_class . '_Installer';
			if ( class_exists( $installer_class ) ) {
				$this->get_instance( $installer_class )->deactivate();
			}
		}
	}

	public function update() {
		foreach ( $this->extensions as $extension_class ) {
			$installer_class = $extension_class . '_Installer';
			if ( class_exists( $installer_class ) ) {
				$this->get_instance( $installer_class )->update();
			}
		}
	}

	public function uninstall() {
		foreach ( $this->extensions as $extension_class ) {
			$installer_class = $extension_class . '_Installer';
			if ( class_exists( $installer_class ) ) {
				$this->get_instance( $installer_class )->uninstall();
			}
		}
	}


	/**** Action Handlers ****/

	public function handle_plugins_loaded() {

		// get plugin extensions
		$plugin_extensions = apply_filters( 'dojo_register_extensions', array() );
		foreach ( $plugin_extensions as $name ) {
			$this->include_plugin_extension( $name );
		}
	}

	public function handle_upgrader_process_complete( $upgrader, $extra ) {
		// if this is the plugin upgrader at work
		if ( class_exists( 'Plugin_Upgrader' ) && $upgrader instanceof Plugin_Upgrader ) {

			// if this is an update and the dojo plugin is part of it
			if ( 'update' == $extra['action'] && false !== array_search( 'dojo/dojo.php', $extra['plugins'] ) ) {

				// get active extensions from dojo source
				$response = $this->call_dojosource( 'get_extension_info' );
				if ( $response instanceof WP_Error ) {
					return;
				}

				// make sure extensions that are supposed to be there are installed
				// they will have been nuked after a plugin update
				foreach ( $response['extensions'] as $extension_id => $title ) {
					$class = 'Dojo_' . ucfirst( $extension_id );
					$path = plugin_dir_path( __FILE__ ) . 'dojo-' . $extension_id;

					// if class exists but folder does not then it just got nuked
					if ( class_exists( $class ) && ! file_exists( $path ) ) {
						$this->install_extension( $extension_id );
					}
				}
			}
		}
	}


	/**** Filters ****/

	public function filter_pre_set_site_transient_update_plugins( $value ) {

		// don't bother if we don't have a key
		$settings = Dojo_Settings::instance();
		if ( '' == $settings->get( 'site_key' ) ) {
			return $value;
		}

		// inject a check for dojo core updates
		$response = $this->call_dojosource( 'get_extension_info' );

		if ( ! ( $response instanceof WP_Error ) ) {
			if ( $response['versions']['core'] != Dojo::instance()->version() ) {
				$release = new stdClass();
				$release->package = 'https://s3.amazonaws.com/dojosource/release/dojo.zip';
				$release->slug = 'dojo';
				$release->plugin = 'dojo/dojo.php';
				$release->new_version = $response['versions']['core'];
				$release->url = 'https://dojosource.com';

				$value->response['dojo/dojo.php'] = $release;
			}
		}

		return $value;
	}


	/**** Ajax Handlers ****/

	public function ajax_get_management_view() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return 'Access denied';
		}
		$response = $this->call_dojosource( 'get_extension_info' );

		if ( $response instanceof WP_Error ) {
			return '<div class="dojo-danger">Error: ' . esc_html( $response->get_error_message() ) . '</div>';
		}

		$this->extension_info = $response;
		ob_start();
		include 'views/manage-extensions.php';
		return ob_get_clean();
	}

	public function ajax_install_extension() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return 'Access denied';
		}

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$extension = $_POST['extension'];

		return $this->install_extension( $extension );
	}

	public function ajax_install_extension_cred() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return 'Access denied';
		}

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$extension = $_GET['dojo-install'];

		$result = $this->install_extension( $extension );
		if ( 'process_success' == $result ) {
			wp_redirect( admin_url( 'admin.php?page=dojo-settings' ) );
			return;
		}
		return $result;
	}

	public function ajax_activate_extension() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return 'Access denied';
		}

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$extension = $_POST['extension'];

		return $this->activate_extension( $extension );
	}

	public function ajax_update_extension() {
		// for now, same thing
		return $this->ajax_install_extension();
	}

	public function ajax_remove_extension() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return 'Access denied';
		}

		// make sure extension exists
		$extension = $_POST['extension'];

		return $this->remove_extension( $extension );
	}


	/**** Utility ****/

	private function call_dojosource( $method, $params = array() ) {
		$settings = Dojo_Settings::instance();

		// include an unmodified $wp_version
		include( ABSPATH . WPINC . '/version.php' );

		$url = $http_url = 'http://dojosource.com/wp-admin/admin-ajax.php?action=dojo&target=Server&method=' . urlencode( $method );
		if ( $ssl = wp_http_supports( array( 'ssl' ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}

		$parts = wp_parse_url( site_url() );
		$post_body = array(
			'domain'    => $parts['host'],
			'key'       => $settings->get( 'site_key' ),
		);
		$post_body = array_merge( $post_body, $params );

		$options = array(
			'timeout'       => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3 ),
			'user-agent'    => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
			'body'          => $post_body,
		);

		$response = wp_remote_post( $url, $options );
		if ( $ssl && is_wp_error( $response ) ) {
			$this->debug( 'Error making secure connection to host' );
			$response = wp_remote_post( $http_url, $options );
		}

		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'connect', 'Error connecting to Dojo Source' );
		}

		$body = $original_body = trim( wp_remote_retrieve_body( $response ) );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) || ! isset( $body['v'] ) ) {
			return new WP_Error( 'response', 'Dojo Source says ' . $original_body );
		}

		return $body;
	}

	public function extension_plugin_exists( $extension ) {
		return file_exists( Dojo_Loader::plugin_path() . '/dojo-' . $extension );
	}

	private function remove_folder( $path ) {
		if ( file_exists( $path ) ) {
			$files = array_diff( scandir( $path ), array( '.', '..' ) );
			foreach ( $files as $file ) {
				( is_dir( "$path/$file" ) ) ? $this->remove_folder( "$path/$file" ) : unlink( "$path/$file" );
			}
			return rmdir( $path );
		}
	}

	private function install_extension( $extension ) {
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$response = $this->call_dojosource( 'get_extension', array(
			'extension'  => $extension,
		) );

		if ( $response instanceof WP_Error ) {
			return 'Error: ' . esc_html( $response->get_error_message() );
		}

		// clear the destination
		$destination = Dojo_Loader::plugin_path() . '/dojo-' . $extension;
		$this->remove_folder( $destination );


		// capture credential form output if necessary
		$form = '';
		$url = $this->ajax( 'install_extension_cred' ) . '&dojo-install=' . urlencode( $extension );
		ob_start();
		// set up credentials to access the file system
		if ( false === ( $creds = request_filesystem_credentials( $url, '', false, false, array() ) ) ) {
			// no credentials to be found, a credential form has been generated
			$form = ob_get_clean();
		}  elseif ( ! WP_Filesystem( $creds ) ) {
			// credentials did not check out, generate the form with errors
			request_filesystem_credentials( $url, '', true, false, array() );
			$form = ob_get_clean();
		} else {
			ob_get_clean();
		}

		// if we need to prompt the user with a credential form for file access
		if ( '' != $form ) {

			// append script to prevent form from submitting the outer settings form
			$form .= "
			<script>
				jQuery(function($) {
					$('.request-filesystem-credentials-action-buttons .button').click(function(ev) {
						ev.preventDefault();
						$(this).closest('form').submit();
					});
				});
			</script>";
			return $form;
		}

		// use built-in upgrader
		ob_start();
		$upgrader = new WP_Upgrader( new Dojo_Silent_Upgrader_Skin() );
		$upgrader->init();
		$result = $upgrader->run( array(
			'package'           => $response['url'],
			'destination'       => $destination,
			'clear_destination' => false,
			'clear_working'     => true,
		) );
		ob_get_clean();

		if ( false === $result ) {
			return 'Error: Unable to connect to the file system';
		}
		if ( $result instanceof WP_Error ) {
			return 'Error: ' . esc_html( $result->get_error_message() );
		}

		$class = 'Dojo_' . ucfirst( $extension );

		return 'process_success';
	}

	private function activate_extension( $extension ) {
		$plugin = 'dojo-' . $extension . '/dojo-' . $extension . '.php';
		$result = activate_plugin( $plugin, '', is_network_admin() );

		if ( is_wp_error( $result ) ) {
			return 'Error: ' . esc_html( $result->get_error_message() );
		}

		return 'process_success';
	}

	private function remove_extension( $extension ) {
		$class = 'Dojo_' . ucfirst( $extension );
		if ( ! class_exists( $class ) ) {
			return 'Extension not found';
		}

		// uninstall extension
		$this->get_instance( $class . '_Installer' )->uninstall();

		// remove files
		$this->remove_folder( Dojo_Loader::plugin_path() . '/dojo-' . $extension );

		return 'process_success';
	}
}

if ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
} elseif ( file_exists( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';
} else {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

/**
 * Class Dojo_Silent_Upgrader_Skin
 *
 * Overrides the feedback method to prevent flushing messages to the output stream
 */
class Dojo_Silent_Upgrader_Skin extends WP_Upgrader_Skin {
	public function feedback( $string ) {
	}
}
