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
}


