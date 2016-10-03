<?php
/**
 * Dojo extension manager
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Extension_Manager extends Dojo_WP_Base {
    private static $instance;

    private $extensions = array();
    private $active_extensions = array();
    private $disabled_extensions = array();
    private $core_extensions;

    private function __construct() {
        $settings = Dojo_Settings::instance();

        // core list of extensions will always be enabled
        if ( ! defined( 'DOJO_NO_CORE' ) || ! DOJO_NO_CORE ) {
            $this->core_extensions = array(
                'Dojo_Membership' => 'Dojo_Membership',
            );
        }

        // sort out extensions
        $dirs = glob( plugin_dir_path( __FILE__ ) . 'dojo-*', GLOB_ONLYDIR );
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

            // tell auto loader about this extension class
            Dojo_Loader::add_extension( $class );

            // if this is a core extension or enabled extension add it to the active extension list
            if ( isset( $this->core_extensions[ $class ] ) || $settings->get( 'enable_extension_' . $class ) ) {
                $this->active_extensions[ $class ] = $class;
            } else {
                $this->disabled_extensions[ $class ] = $class;
            }
        }

        // load active extensions
        foreach ( $this->active_extensions as $extension_class ) {
            $this->get_instance( $extension_class );
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
     * Get list of active extensions
     *
     * @return array(string)
     */
    public function active_extensions() {
        return $this->active_extensions;
    }

    /**
     * Get list of disabled extensions
     *
     * @return array(string)
     */
    public function disabled_extensions() {
        return $this->disabled_extensions;
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

    public function check_for_updates() {

    }

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


    /**** Ajax Handlers ****/

    public function api_get_management_view() {
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

    public function api_install_extension() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return 'Access denied';
        }

        if ( ! class_exists( 'WP_Upgrader' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        $extension = $_POST['extension'];
        $response = $this->call_dojosource( 'get_extension', array(
            'extension'  => $extension,
        ) );

        if ( $response instanceof WP_Error ) {
            return '<div class="dojo-danger">Error: ' . esc_html( $response->get_error_message() ) . '</div>';
        }

        // current path not resolving symlink
        $path =  plugin_dir_path( WP_PLUGIN_DIR . '/' . plugin_basename(__FILE__) );

        $upgrader = new WP_Upgrader();
        $upgrader->init();
        $result = $upgrader->run( array(
            'package'           => $response['url'],
            'destination'       => $path . 'dojo-' . $extension,
 			'clear_destination' => false,
			'clear_working'     => true,
        ) );

        if ( false === $result ) {
            return '<div class="dojo-danger">Error: Unable to connect to the file system</div>';
        }
        if ( $result instanceof WP_Error ) {
            return '<div class="dojo-danger">Error: ' . esc_html( $response->get_error_message() ) . '</div>';
        }

        return 'success';
    }

    public function api_update_extension() {

    }
}


