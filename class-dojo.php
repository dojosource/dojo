<?php
/**
 * Main dojo plugin class
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

final class Dojo extends Dojo_WP_Base {
    private static $instance;

    private $custom_pages = array();
    private $custom_page_content;
    private $head_complete = false;

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

        $this->register_action_handlers( array(
            'plugins_loaded',
            'wp_enqueue_scripts',
            'admin_enqueue_scripts',
            'generate_rewrite_rules',
            'wp_ajax_dojo',
            'wp_ajax_nopriv_dojo',
            'dojo_register_settings',
        ) );

        $this->register_filters( array(
            'body_class',
            'query_vars',
            'posts_results',
            array( 'plugin_action_links', 10, 2 ),
            array( 'wp_head', 0xFFFF ),
        ) );

        $this->register_shortcodes( array(
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
     * Get model instance for plugin
     *
     * @return object
     */
    public function model() {
        return Dojo_Model::instance();
    }

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
     * Log an event with optional user and/or extension context
     *
     * @param string $event
     * @param int $user_id
     * @param string $extension
     *
     * @return void
     */
    public function log_event( $event, $user_id = null, $extension = null ) {
        $this->model()->create_event_log( $event, $user_id, $extension );
    }

    /**
     * Register custom pages 
     *
     * @param string $slug Root level slug
     * @param mixed $render_callback
     * @param mixed $title_callback
     *
     * @return void
     */
    public function register_custom_page( $slug, $render_callback, $title_callback ) {
        $this->custom_pages[ $slug ] = array (
            'render_callback'   => $render_callback,
            'title_callback'    => $title_callback,
        );
    }

    /**
     * Get the dummy page used as a placeholder for dynamic pages. This is used instead
     * of an ID 0 page so theme options can be applied. If the dummy page has not been created
     * or has been deleted, a new page is created.
     *
     * @return object
     */
    public function get_dummy_page() {
        $page_id = (int) get_option( 'dojo_dummy_page' );
        if ( 0 != $page_id ) {
            $page = get_post( $page_id );
            if ( $page instanceof WP_Post ) {
                return $page;
            }
        }

        // need to create dummy page
        ob_start();
        include $this->path_of( 'views/dummy-page-content.php' );
        $dummy_page_content = ob_get_clean();

        $page_id = wp_insert_post( array(
            'post_content'          => $dummy_page_content,
            'post_title'            => 'zzz Dojo Dummy Page zzz',
            'post_type'             => 'page',
        ) );

        update_option( 'dojo_dummy_page', $page_id );

        return get_post( $page_id );
    }

    /**
     * Initiates a full uninstall of the plugin and every extension.
     */
    public function uninstall() {
        Dojo_Extension_Manager::instance()->uninstall();
        Dojo_Installer::instance()->uninstall();
    }


    /**** Action Handlers ****/

    public static function handle_activate() {
        Dojo_Installer::instance()->activate();
        Dojo_Extension_Manager::instance()->handle_activate();

        Dojo_Model::instance()->create_event_log( 'Plugin activated' );
    }

    public static function handle_deactivate() {
        // clear out custom pages so permalinks will get cleared properly
        self::instance()->custom_pages = array();

        Dojo_Installer::instance()->deactivate();
        Dojo_Extension_Manager::instance()->handle_deactivate();

        Dojo_Model::instance()->create_event_log( 'Plugin deactivated' );
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
     * If the method returns an array or object it will be converted to JSON and echoed.
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
                    } elseif ( is_array( $result ) || is_object( $result ) ) {
                        echo json_encode( $result );
                    }

                    // prevent default '0' response appended from wordpress
                    die();
                }
            }
        } elseif (isset( $_GET['method'] ) ) {
            // target is this main dojo class
            $method = 'api_' . $_GET['method'];
            if ( method_exists( $this, $method ) ) {
                $result = call_user_func( array( $this, $method ), $is_admin );
                if ( is_string( $result ) ) {
                    echo $result;
                } elseif ( is_array( $result ) || is_object( $result ) ) {
                    echo json_encode( $result );
                }
                die();
            }
        }
    }

    public function handle_wp_ajax_nopriv_dojo() {
        $this->handle_wp_ajax_dojo( 'nopriv' );
    }

    public function handle_dojo_register_settings( $settings ) {
        $settings = Dojo_Settings::instance();
        $key_set = ( '' != $settings->get( 'site_key' ) );
        if ( ! $key_set ) {
            $adon_subtitle = '
            <div class="dojo-info" style="font-size:16px">
                Register at <a href="https://dojosource.com" target="_blank">dojosource.com</a> for Pro Add-Ons.<br /><br />
                Online Payments, Events and more!
            </div>
            <div style="border:1px solid #aaa;margin:10px 0;padding:10px;max-width:340px;">
                <div class="dojo-large-icon dojo-left">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                </div>
                <div class="dojo-left" style="font-size:24px;margin-top:10px;">
                    Invoices
                </div>
                <div class="dojo-clear"></div>
                Invoice add-on available FREE from <a href="https://dojosource.com" target="_blank">dojosource.com</a>.
            </div>
            ';
        } else {
            $adon_subtitle = '';
        }

        // setup settings for managing extensions
        $settings->register_section(
            'dojo_extension_section',                       // section id
            'Add-Ons',                                      // section title
            $adon_subtitle                                  // section subtitle
        );

        $settings->register_option( 'dojo_extension_section', 'site_key', 'Site Key', $this );

        if ( $key_set ) {
            $settings->register_option( 'dojo_extension_section', 'manage_extensions', 'Manage Add-Ons', $this );
        }
    }


    /**** Filters ****/

    public function filter_plugin_action_links( $links, $file ) {
        if ( $file == plugin_basename( dirname( __FILE__ ) . '/dojo.php') ) {
            $links[] = '<a href="' . admin_url('admin.php?page=dojo-settings') . '">Settings</a>';
        }
        return $links;
    }

    public function filter_body_class( $classes ) {
        $classes[] = 'dojo';
        return $classes;
    }

    public function filter_query_vars( $qvars ) {
        // add query var for each custom page slug
        foreach ( $this->custom_pages as $slug => $callbacks ) {
            $qvars[] = 'dojo-' . $slug;
        }
        return $qvars;
    }

    public function filter_posts_results( $posts ) {
        global $wp_query;

        // TODO - NOT confident about this fix, need to be sure about how to filter out queries to side bar
        // recent posts etc...
        if ( $this->head_complete ) {
            return $posts;
        }

        // collect custom page titles and links to supply to title filter later
        $this->page_titles = array();
        $this->page_links = array();
        
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

                // get dummy page to work with
                $page = $this->get_dummy_page();

                // override content of dummy page
                $page->post_content     = '[dojo_page]';
                $page->post_title       = $title;
                $page->post_status      = 'publish';
                $page->comment_status   = 'closed';
                $page->ping_status      = 'closed';
                $page->post_name        = 'dojo-' . $slug;
                $page->guid             = get_site_url() . '/' . $slug . $path;
                $page->post_type        = 'page';

                // configure wp_query
                $wp_query->is_home = false;
                $wp_query->is_page = true;
                $wp_query->queried_object = $page;
                $wp_query->max_num_pages = 1;

                // add to cache
                wp_cache_add( $page->ID, $page, 'posts' );
                $this->page_titles[ $page->ID ] = $title;
                $this->page_links[ $page->ID ] = get_site_url() . '/' . $slug . $path;

                // generate ancestors list with titles
                $parts = explode( '/', $path );
                $ancestors = array();
                $ancestor_index = 1;
                if ( count( $parts ) > 1 ) {
                    $ancestor_path = '';
                    foreach ( $parts as $part ) {
                        if ( '' != $part ) {
                            $ancestor_path .= '/' . $part;
                        }
                        if ( count( $ancestors ) >= count( $parts ) - 1 ) {
                            break;
                        }
                        $ancestor_title = call_user_func( $callbacks['title_callback'], $ancestor_path );
                        $ancestor_index ++;
                        $ancestor_id = '' . ( $page->ID + ( $ancestor_index / 10 ) );
                        $ancestor = get_post( (object) array(
                            'post_title'    => $ancestor_title,
                        ) );
                        $ancestor->ID = $ancestor_id;
                        $ancestors[] = $ancestor;
                        $this->page_titles[ $ancestor_id ] = $ancestor_title;
                        $this->page_links[ $ancestor_id ] = get_site_url() . '/' . $slug . $ancestor_path;
                    }
                }
                $parent = end( $ancestors );
                if ( $parent ) {
                    $page->post_parent = $parent->ID;
                }
                $page->ancestors = array_reverse( $ancestors );

                // hook filters to make sure calls to get_permalink and get_the_title return the right thing
                add_filter( 'the_title', array( $this, 'filter_the_title' ), 10, 2 );
                add_filter( 'post_link', array( $this, 'filter_post_link' ), 10, 2 );

                return array( $page );
            }
        }

        // not us, don't do anything
        return $posts;
    }

    public function filter_the_title( $title, $id ) {
        if ( isset( $this->page_titles[ $id ] ) ) {
            return $this->page_titles[ $id ];
        }
        return $title;
    }

    public function filter_post_link( $link, $post ) {
        if ( isset( $this->page_links[ $post->ID ] ) ) {
            return $this->page_links[ $post->ID ];
        }
        return $link;
    }

    public function filter_wp_head() {
        $this->head_complete = true;
    }


    /**** Shortcodes ****/

    public function shortcode_dojo_page() {
        echo $this->custom_page_content;
    }


    /**** Render Options ****/

    public function render_option_site_key() {
        $settings = Dojo_Settings::instance();
        $site_key = $settings->get( 'site_key' );
        ?>
        Enter key received from <a href="https://dojosource.com" target="_blank">Dojo Source</a><br />
        <input type="text" id="site_key" name="dojo_options[site_key]" class="regular-text" value="<?php echo esc_attr( $site_key ) ?>" />
        <?php
    }

    public function render_option_manage_extensions() {
        include 'views/manage-extensions.php';

        /*
        $settings = Dojo_Settings::instance();
        $extensions = Dojo_Extension_Manager::instance()->extensions();
        $core_extensions = Dojo_Extension_Manager::instance()->core_extensions();
        $available_extensions = array();

        foreach ( $extensions as $extension_class ) {
            if ( isset( $core_extensions[ $extension_class ] ) ) {
                // no configuration necessary for core extensions, they are always enabled
                continue;
            }
            $available_extensions[] = $extension_class;
        }

        if ( 0 == count( $available_extensions ) ) {
            ?>
            <div class="dojo-warn">No add-ons installed.</div>
            <?php
        } else {
            foreach ( $available_extensions as $extension_class ) {
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
        */
    }
}



