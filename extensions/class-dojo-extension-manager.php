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
        $this->register_action_handlers( array (
            'dojo_update',
            array( 'upgrader_process_complete', 10, 2 ),
        ) );

        $this->register_filters( array (
            'pre_set_site_transient_update_plugins',
        ) );

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


    /**** Action Handlers ****/

    public function handle_dojo_update() {

        /*
        $response = $this->call_dojosource( 'get_extension_info' );

        if ( $response instanceof WP_Error ) {
            return;
        }

        $settings = Dojo_Settings::instance();

        // make sure extensions that are supposed to be there are installed
        // they could have been nuked on a core update
        foreach ( $response['extensions'] as $extension_id => $title ) {
            error_log( 'checking ' . $extension_id );
            $class = 'Dojo_' . ucfirst( $extension_id );
            if ( $settings->get( 'enable_extension_' . $class ) && ! class_exists( $class ) ) {
                error_log( 'installing' );
                $this->install_extension( $extension_id );
            }
        }
        */
/*
        // check core version
        $core_info = get_plugin_data( Dojo::instance()->path_of( 'dojo.php' ), false, false );
        if ( $response['versions']['core'] != $core_info['Version'] ) {

            // create release info for dojo
            $release = new stdClass();
            $release->package = 'https://s3.amazonaws.com/dojosource/release/dojo.zip';
            $release->slug = 'dojo';
            $release->plugin = 'dojo/dojo.php';
            $release->new_version = $response['versions']['core'];
            $release->url = 'https://dojosource.com';

            // get wordpress plugin update info
            $current = get_site_transient( 'update_plugins' );
            error_log(print_r($current, true));
            if ( ! is_object( $current ) ) {
                $current = new stdClass;
            }

            // add release info to wordpress plugin update info
            if ( ! isset( $current->response ) || ! is_array( $current->response ) ) {
                $current->response = array();
            }
            $current->response['dojo/dojo.php'] = $release;
            set_site_transient( 'update_plugins', $current );
        }
*/
        return true;
    }

    public function handle_upgrader_process_complete( $upgrader, $extra ) {
        error_log(print_r($extra, true));
        if ( class_exists( 'Plugin_Upgrader' ) && $upgrader instanceof Plugin_Upgrader ) {
            if ( isset( $extra['plugins']['dojo/dojo.php'] ) ) {
                error_log('check updates here');
            }
        }
    }


    /**** Filters ****/

    public function filter_pre_set_site_transient_update_plugins( $value ) {

        // inject a check for dojo core updates
        $response = $this->call_dojosource( 'get_extension_info' );

        if ( ! ( $response instanceof WP_Error ) ) {
            $release = new stdClass();
            $release->package = 'https://s3.amazonaws.com/dojosource/release/dojo.zip';
            $release->slug = 'dojo';
            $release->plugin = 'dojo/dojo.php';
            $release->new_version = $response['versions']['core'];
            $release->url = 'https://dojosource.com';

            $value->response['dojo/dojo.php'] = $release;
        }

        return $value;
    }


    /**** Utilities ****/

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

        return $this->install_extension( $extension );
    }

    public function api_update_extension() {
        // for now, same thing
        $this->api_install_extension();
    }

    public function api_remove_extension() {
        if ( ! current_user_can( 'update_plugins' ) ) {
            return 'Access denied';
        }

        if ( ! class_exists( 'WP_Upgrader' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        // make sure extension exists
        $extension = $_POST['extension'];

        return $this->remove_extension( $extension );
    }


    /**** Utility ****/

    private function remove_folder( $path ) {
        $files = array_diff( scandir( $path ), array( '.', '..' ) );
        foreach ( $files as $file ) {
            ( is_dir( "$path/$file" ) ) ? $this->remove_folder( "$path/$file" ) : unlink( "$path/$file" );
        }
        return rmdir( $path );
    }

    private function install_extension( $extension ) {
        $response = $this->call_dojosource( 'get_extension', array(
            'extension'  => $extension,
        ) );

        if ( $response instanceof WP_Error ) {
            return 'Error: ' . esc_html( $response->get_error_message() );
        }

        // use built-in upgrader
        $upgrader = new WP_Upgrader();
        $upgrader->init();
        $result = $upgrader->run( array(
            'package'           => $response['url'],
            'destination'       => plugin_dir_path( __FILE__ ) . 'dojo-' . $extension,
            'clear_destination' => true,
            'clear_working'     => true,
        ) );

        if ( false === $result ) {
            return 'Error: Unable to connect to the file system';
        }
        if ( $result instanceof WP_Error ) {
            return 'Error: ' . esc_html( $result->get_error_message() );
        }

        $settings = Dojo_Settings::instance();
        $class = 'Dojo_' . ucfirst( $extension );
        $settings->set( 'enable_extension_' . $class, true );

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
        $this->remove_folder( plugin_dir_path( __FILE__ ) . 'dojo-' . $extension );

        // setting this false will prevent autoloading on core plugin update
        $settings = Dojo_Settings::instance();
        $settings->set( 'enable_extension_' . $class, false );

        return 'process_success';
    }
}


