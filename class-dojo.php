<?php
/**
 * Main dojo plugin class
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

final class Dojo extends Dojo_WP_Base {
    private static $instance;

    private $custom_pages = array();
    private $custom_page_content;

    /**
     * Root path of plugin ending with slash
     *
     * @var string
     */
    public $plugin_path;

    /**
     * Root url of plugin ending with slash
     *
     * @var string
     */
    public $plugin_url;

    private function __construct() {
    }

    private function init() {
        $this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url = plugin_dir_url( __FILE__ );

        $this->register_action_handlers( array (
            'plugins_loaded',
            'wp_enqueue_scripts',
            'admin_enqueue_scripts',
            'generate_rewrite_rules',
            'wp_ajax_dojo',
            'wp_ajax_nopriv_dojo',
            'dojo_register_settings',
        ) );

        $this->register_filters( array (
            'query_vars',
            'posts_results',
        ) );

        $this->register_shortcodes( array (
            'dojo_page',
        ) );

        // create menu
        Dojo_Menu::instance();

        // load extension manager and active extensions
        Dojo_Extension_Manager::instance();
    }

    /**
     * Get singleton instance
     *
     * @return Dojo
     */
    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;

            // initialize outside constructor so other classes have access to singleton during init
            self::$instance->init();
        }
        return self::$instance;
    }


    /**** Utility ****/

    /**
     * Get fully qualified path of the given plugin file
     *
     * @param string $file Relative path of file
     *
     * @return string
     */
    public function path_of( $file ) {
        return $this->plugin_path . $file;
    }

    /**
     * Get fully qualified url of the given plugin file
     *
     * @param string $file Relative path of file
     *
     * @return string
     */
    public function url_of( $file ) {
        return $this->plugin_url . $file;
    }

    /**
     * Register custom pages 
     *
     * @param string $slug Root level slug
     * @param string $shortcode Shortcode that will render custom pages under the slug.
     */
    public function register_custom_page( $slug, $render_callback, $title_callback ) {
        $this->custom_pages[ $slug ] = array (
            'render_callback'   => $render_callback,
            'title_callback'    => $title_callback,
        );
    }


    /**** Action Handlers ****/

    public static function handle_activate() {
        Dojo_Installer::instance()->activate();
        Dojo_Extension_Manager::instance()->handle_activate();
    }

    public static function handle_deactivate() {
        Dojo_Installer::instance()->deactivate();
        Dojo_Extension_Manager::instance()->handle_deactivate();
    }

    public function handle_plugins_loaded() {
        Dojo_Installer::instance()->update();
        Dojo_Extension_Manager::instance()->update();
    }

    public function handle_wp_enqueue_scripts() {
        wp_register_style( 'dojo_style', $this->url_of( 'css/dojo-style.css' ) ); 
        wp_enqueue_style( 'dojo_style' );
    }

    public function handle_admin_enqueue_scripts() {
        wp_register_style( 'dojo_admin_style', $this->url_of( 'css/dojo-admin-style.css' ) );
        wp_enqueue_style( 'dojo_admin_style' );
    }

    public function handle_generate_rewrite_rules( $wp_rewrite ) {
        $new_rules = array();
        foreach ( $this->custom_pages as $slug => $callbacks ) {
            // rewrite rule to custom query var registered in filter_query_vars
            $new_rules[ '^' . $slug . '/?$' ] = 'index.php?dojo-' . $slug . '=';
            $new_rules[ '^' . $slug . '/(.+)' ] = 'index.php?dojo-' . $slug . '=' . $wp_rewrite->preg_index(1);
        }
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }

    /**
     * Ajax handling uses two get parameters for routing to a method on an object.
     * Get parameter, "target" will be prefixed with Dojo_ to resolve to a dojo class.
     * Get parameter, "method" will be prefixed with api_ to resolve to a method on that class.
     * A bool flag is passed to the method to indicate admin privs.
     * If the method returns a string the string will be echoed out in the response.
     * If the method returns an array it will be converted to JSON and echoed.
     *
     * See Dojo_Extension::ajax for example of generating ajax urls
     */
    public function handle_wp_ajax_dojo( $arg ) {
        $is_admin = 'nopriv' !== $arg;
        if ( isset( $_GET['target'] ) && isset( $_GET['method'] ) ) {
            $target = 'Dojo_' . $_GET['target'];
            if ( class_exists( $target ) ) {
                $method = 'api_' . $_GET['method'];
                $instance = $this->get_instance( $target );
                if ( method_exists( $instance, $method ) ) {
                    $result = call_user_func( array( $instance, $method ), $is_admin );
                    if ( is_string( $result ) ) {
                        echo $result;
                    } elseif ( is_array( $result ) ) {
                        echo json_encode( $result );
                    }

                    // prevent default '0' response appended from wordpress
                    die();
                }
            }
        }
    }

    public function handle_wp_ajax_nopriv_dojo() {
        $this->handle_wp_ajax_dojo( 'nopriv' );
    }

    public function handle_dojo_register_settings( $settings ) {
        // setup settings for managing extensions
        $settings->register_section(
            'dojo_extension_section',                       // section id
            'Add-Ons',                                      // section title
            'For more add-ons <a href="#">click here</a>'   // section subtitle
        );

        $settings->register_option( 'dojo_extension_section', 'enable_extensions', 'Enable Add-Ons', $this );
    }


    /**** Filters ****/

    public function filter_query_vars( $qvars ) {
        // add query var for each custom page slug
        foreach ( $this->custom_pages as $slug => $callbacks ) {
            $qvars[] = 'dojo-' . $slug;
        }
        return $qvars;
    }

    public function filter_posts_results( $posts ) {
        global $wp_query;

        // todo - use dedicated placeholder page instead of ID 0 page to allow for page template selection
        // and other possible meta detail
        
        foreach ( $this->custom_pages as $slug => $callbacks ) {
            if ( isset( $wp_query->query[ 'dojo-' . $slug ] ) ) {

                // get path following the slug
                $path = '/' . $wp_query->query[ 'dojo-' . $slug ];
                if ( '/' == $path ) {
                    $path = '';
                }

                // render content using callback
                // Note: content saved and issued to page via [dojo_page] shortcode later to avoid filtering
                ob_start();
                $page_exists = call_user_func( $callbacks['render_callback'], $path );
                $this->custom_page_content = ob_get_clean();

                // if render returned false then page at $path doesn't exist 
                if ( ! $page_exists ) {
                    return array();
                }

                // get title using callback
                $title = call_user_func( $callbacks['title_callback'], $path );
               
                // create custom post object to represent the page
                $page = get_post( (object) array(
                    'ID'                    => 0,
                    'post_content'          => '[dojo_page]',
                    'post_title'            => $title,
                    'post_status'           => 'publish',
                    'comment_status'        => 'closed',
                    'ping_status'           => 'closed',
                    'post_name'             => 'dojo-' . $slug,
                    'guid'                  => get_site_url() . '/' . $slug . $path,
                    'post_type'             => 'page',
                ) );

                return array ( $page );
            }
        }

        // not us, don't do anything
        return $posts;
    }


    /**** Shortcodes ****/

    public function shortcode_dojo_page() {
        echo $this->custom_page_content;
    }


    /**** Render Options ****/

    public function render_option_enable_extensions() {
        $settings = Dojo_Settings::instance();
        $extensions = Dojo_Extension_Manager::instance()->extensions();
        $core_extensions = Dojo_Extension_Manager::instance()->core_extensions();

        foreach ( $extensions as $extension_class ) {
            if ( isset( $core_extensions[ $extension_class ] ) ) {
                // no configuration necessary for core extensions, they are always enabled
                continue;
            }
            $instance = $this->get_instance( $extension_class );
            ?>
            <p>
                <label for="enable_<?php echo $extension_class ?>">
                    <input type="checkbox" id="enable_<?php echo $extension_class ?>" name="dojo_options[enable_extension_<?php echo $extension_class ?>]" value="1" <?php checked( $settings->get( 'enable_extension_' . $extension_class ), '1' ) ?> />
                    <?php echo $instance->title() ?>
                </label>
            </p>
            <?php
        }
    }
}



