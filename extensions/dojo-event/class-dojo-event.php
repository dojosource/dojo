<?php
/**
 * dojo event custom post type
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Event extends Dojo_Extension {
    private static $instance;

    protected $head_complete = false;

    public $singular = 'event';
    public $plural = 'events';

    public $post;

    protected function __construct() {
        parent::__construct( 'Events' );

        $this->register_action_handlers( array(
            'init',
            'add_meta_boxes',
            'admin_enqueue_scripts',
            'dojo_register_settings',
            'dojo_settings_updated',
            'dojo_invoice_line_item_paid',
            array( 'wp_enqueue_scripts', 20 ),
            array( 'save_post', 1, 2 ),
        ) );

        $this->register_filters( array(
            'template_include',
            'manage_edit-dojo_event_columns',
            array( 'wp_head', 0xFFFF ),
        ) );
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**** Action Handlers ****/

    public function handle_init() {
        $settings = Dojo_Settings::instance();
        $slug = $settings->get( 'events_slug' );
        if ( '' == $slug ) {
            $slug = 'events';
        }

        $labels = array(
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'menu_name'          => 'Dojo Events',
            'name_admin_bar'     => 'Event',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Event',
            'new_item'           => 'New Event',
            'edit_item'          => 'Edit Event',
            'view_item'          => 'View Event',
            'all_items'          => 'All Events',
            'search_items'       => 'Search Events',
            'not_found'          => 'No events found.',
            'not_found_in_trash' => 'No events found in Trash.',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'rewrite' => array( 'slug' => $slug, 'with_front' => false ),
            'supports' => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
            ),
            'taxonomies' => array( 'post_tag' ),
            'map_meta_cap' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-calendar',
            'menu_position' => 4
        );

        register_post_type( 'dojo_event', $args );
    }

    public function handle_add_meta_boxes() {
        add_meta_box( 'event-schedule', 'Event Schedule', array( $this, 'render_edit_event_schedule' ), 'dojo_event', 'normal', 'high' );
        add_meta_box( 'event-registration', 'Event Registration', array( $this, 'render_edit_event_registration' ), 'dojo_event', 'normal', 'high' );
    }

    public function handle_admin_enqueue_scripts() {
        // register plugin scripts
        wp_register_script( 'dojo-event-edit', $this->url( 'js/event-edit.js' ) );

        // TODO - Move these into the views so they only enqueue when needed
        // enqueue scripts
        wp_enqueue_script( 'dojo-event-edit' );
        wp_enqueue_script( 'jquery-ui-datepicker' );

        // enqueue styles
        wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
    }

    public function handle_wp_enqueue_scripts() {
        // register plugin scripts
        wp_register_script( 'dojo-event', plugins_url( 'js/event.js', __FILE__ ) );

        // register plugin styles
        wp_register_style( 'dojo-event', plugins_url( 'css/dojo-event.css', __FILE__ ) );

        // TODO - Move these into the views so they only enqueue when needed
        // enqueue scripts
        wp_enqueue_script( 'dojo-event' );

        // enqueue styles
        wp_enqueue_style( 'dojo-event' );
    }

    public function handle_save_post( $post_id, $post ) {
        // verify contains expected elements
        if ( ! isset( $_POST['dojo_event_nonce'] ) || ! isset( $_POST['schedule'] ) || ! isset( $_POST['registration'] ) ) {
            return;
        }

        // verify nonce
        if ( ! wp_verify_nonce( $_POST['dojo_event_nonce'], basename( __FILE__ ) ) ) {
            return;
        }

        // verify post type
        if ( $_POST['post_type'] != 'dojo_event' ) {
            return;
        }

        $expected = array(
            'schedule' => array(
                'date',
                'start_hour',
                'start_minute',
                'start_is_pm',
                'end_hour',
                'end_minute',
                'end_is_pm',
            ),
            'registration' => array(
                'enable',
                'enable_guest',
                'enable_limit',
                'limit',
                'enable_payment',
            ),
        );

        foreach ( $expected as $section => $keys ) {
            if ( isset( $_POST[ $section ] ) ) {
                foreach ( $keys as $key ) {
                    if ( isset( $_POST[ $section ][ $key ] ) ) {
                        $value = $_POST[ $section ][ $key ];
                    } else {
                        $value = '';
                    }
                    update_post_meta( $post_id, $section . '_' . $key, $value );
                }
            }
        }

        $price_plan = new Dojo_Price_Plan();
        $price_plan->handle_post();
        update_post_meta( $post_id, 'registration_price', (string) $price_plan );
    }

    public function filter_wp_head() {
        $this->head_complete = true;
    }

    public function handle_loop_start( $query ) {
        if ( $query->is_main_query() && $this->head_complete ) {
            // override the post with an empty placeholder
            add_action( 'the_post', array( $this, 'handle_the_post' ) );

            // inject event view into the content
            add_filter( 'the_content', array( $this, 'filter_the_content_override' ) );

            // only execute once
            remove_action( 'loop_start', array( $this, 'handle_loop_start' ) );
        }
    }

    public function handle_the_post() {
        // set current post to an empty post
        $empty_post = ( object ) array(
            'ID'                    => 0,
            'post_status'           => 'draft',
            'post_author'           => 0,
            'post_parent'           => 0,
            'post_type'             => 'page',
            'post_date'             => 0,
            'post_date_gmt'         => 0,
            'post_modified'         => 0,
            'post_modified_gmt'     => 0,
            'post_content'          => '',
            'post_title'            => '',
            'post_excerpt'          => '',
            'post_content_filtered' => '',
            'post_mime_type'        => '',
            'post_password'         => '',
            'post_name'             => '',
            'guid'                  => '',
            'menu_order'            => 0,
            'pinged'                => '',
            'to_ping'               => '',
            'ping_status'           => '',
            'comment_status'        => 'closed',
            'comment_count'         => 0,
            'is_404'                => false,
            'is_page'               => false,
            'is_single'             => false,
            'is_archive'            => false,
            'is_tax'                => false,
        );
        $this->post = $GLOBALS[ 'post' ];
        $GLOBALS[ 'post' ] = $empty_post;

        // unregister handler so we only do this once
        remove_action( 'the_post', array( $this, 'handle_the_post' ) );
    }

    public function handle_dojo_register_settings( $settings ) {
        $settings->register_section(
            'dojo_event_section',   // section id
            'Events',               // section title
            ''                      // section subtitle
        );

        $settings->register_option( 'dojo_event_section', 'events_slug', 'Events Slug', $this );
    }

    public function handle_dojo_settings_updated( $settings ) {
        global $wp_rewrite;

        // re-initialize with new settings
        $this->handle_init();

        // refresh rewrite rules
        $wp_rewrite->flush_rules( false );
    }

    public function handle_dojo_invoice_line_item_paid( $line_item ) {
         $meta = unserialize( $line_item->meta );

        if ( is_array( $meta ) ) {
            if ( isset( $meta['is_event_registration'] ) && $meta['is_event_registration'] ) {
                $post = get_post( $meta['event_id'] );
                if ( 'dojo_event' == $post->post_type ) {
                    $student = Dojo_Membership::instance()->model()->get_student( $meta['student_id'] );
                    if ( $student ) {
                        $meta['user_id'] = $student->user_id;
                        $this->model()->create_registrant( $meta['event_id'], $meta );
                    }
                }
            }
        }
    }

    /**
     * Filter content by complete override
     *
     * @return string
     */
    public function filter_the_content_override() {
        // unregister filter so we only do this once
        remove_filter( 'the_content', array( $this, 'filter_the_content_override' ) );

        // render view
        return $this->render( 'content-dojo-event' );
    }

    /**
     * Filter content by extending it
     *
     * @param string $content
     *
     * @return string
     */
    public function filter_the_content_extend( $content ) {
        $user = wp_get_current_user();
        $this->students = Dojo_Membership::instance()->get_current_students();
        $this->registrants = $this->model()->get_event_registrants( $this->post->ID );
        $this->user_registrants = $this->model()->get_event_user_registrants( $this->post->ID, $user->ID );

        if ( current_user_can( 'manage_options' ) ) {
            $this->is_manager = true;
        } else {
            $this->is_manager = false;
        }

        // render extended content
        return $content . $this->render( 'extend-content' );
    }

    public function filter_template_include( $template ) {
        global $wp_query;

        // todo - option to select method of replacement
        $mode = 'extend';

        if ( isset( $wp_query->query['post_type'] ) && 'dojo_event' == $wp_query->query['post_type'] ) {
           
            if ( 'full' == $mode ) {
                // use our own template in place of the theme template
                $template = $this->path( 'views/single-dojo-event.php' );
            } elseif ( 'inject' == $mode ) {
                // handle loop start to hijack and inject event render into content of theme template
                add_action( 'loop_start', array( $this, 'handle_loop_start' ) );
            } elseif ( 'extend' == $mode ) {
                // theme handles the render, we just append extra content to title and page content
                add_filter( 'the_content', array( $this, 'filter_the_content_extend' ) );

                // make post render as a page
                $post = $GLOBALS[ 'post' ];
                $post->post_type = 'page';

                // inject date  and start time into title
                $post->post_title .= '<br><span class="dojo-event-start" style="font-size:.6em;">' . self::get_formatted_start_time( $post ) . '</span>';

                $this->post = $post;
            }
        }

        return $template;
    }

    public function filter_manage_edit_dojo_event_columns( $columns ) {
        return array(
            'cb'            => '<input type="checkbox" />',
            'title'         => 'Title',
            'registrants'   => 'Registered',
            'reg_limit'     => 'Limit',
            'date'          => 'Date',
        );
    }

    /**** Render Options ****/

    public function render_option_events_slug() {
        $slug = Dojo_Settings::instance()->get( 'events_slug' );
        if ( '' == $slug ) {
            $slug = 'events';
        }
        $base_url = site_url( $slug . '/' );
        $this->render_option_regular_text( 'events_slug', 'All event pages are under ' . esc_html( $base_url ) );
    }


    /**** Ajax Handlers ****/

    public function api_get_line_items( $is_admin ) {
        // set post context
        $GLOBALS['post'] = get_post( $_POST['post_id'] );

        $user = wp_get_current_user();
        $user_registrants = $this->model()->get_event_user_registrants( $_POST['post_id'], $user->ID );

        $line_items = array();

        if ( is_array( $_POST['students'] ) ) {
            $price_plan = new Dojo_Price_Plan( $this->get_meta( 'registration_price' ) );

            // account for family members already registered in pricing
            $person = 1 + count( $user_registrants );

            foreach ( $_POST['students'] as $student_id ) {
                $student = Dojo_Membership::instance()->model()->get_student( $student_id );
                $price = $price_plan->get_price( $person ++ );
                $line_item = array(
                    'amount_cents'  => $price * 100,
                    'description'   => $student->first_name . ' ' . $student->last_name,
                    'id'            => $student->ID,
                );
                $line_items[] = $line_item;
            }

        }
        return $line_items;
    }

    public function api_register( $is_admin ) {
        // set post context
        $GLOBALS['post'] = get_post( $_POST['post_id'] );

        // todo registration without invoice+payments extensions
    }


    /**** Utility ****/

    protected function get_meta( $key, $default = '' ) {
        $post = $GLOBALS['post'];

        $keys = get_post_custom_keys( $post->ID );
        if ( ! is_array( $keys ) || ! in_array( $key, $keys ) ) {
            return $default;
        }
        return get_post_meta( $post->ID, $key, true );
    }

    /**
     * Get start time of an event post with nice formatting. If no event specified will use global $post.
     *
     * @param object $event
     *
     * @return string
     */
    public static function get_formatted_start_time( $event = null ) {
        global $post;
        if ( null === $event ) {
            $event = $post;
        }

        $date = strtotime( get_post_meta( $event->ID, 'schedule_date', true ) );
        $hour = get_post_meta( $event->ID, 'schedule_start_hour', true );
        $min = get_post_meta( $event->ID, 'schedule_start_minute', true );
        $mer = get_post_meta( $event->ID, 'schedule_start_is_pm', true ) ? 'pm' : 'am';
        if ( 1 == strlen( $min ) ) {
            $min = '0' . $min;
        }

        return date( 'l, M jS', $date ) . " @$hour:$min $mer";
    }

    public function render_edit_event_schedule() {
        wp_nonce_field( basename( __FILE__ ), 'dojo_event_nonce' );
        include $this->path( 'views/edit-event-schedule.php' );
    }

    public function render_edit_event_registration() {
        include $this->path( 'views/edit-event-registration.php' );
    }
}


