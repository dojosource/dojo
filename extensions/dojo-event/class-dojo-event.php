<?php
/**
 * dojo event custom post type
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Event extends Dojo_Extension {
    private static $instance;

    protected $extension_folder;
    protected $views_folder;
    protected $head_complete = false;

    public $singular = 'event';
    public $plural = 'events';

    public $post;

    protected function __construct() {
        parent::__construct( 'Events' );

        $this->extension_folder = plugin_dir_path( __FILE__ );
        $this->views_folder = $this->extension_folder . 'views/';
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function register() {
        add_action( 'init', array( $this, 'handle_init' ) );
        add_action( 'add_meta_boxes', array ( $this, 'handle_add_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array ( $this, 'handle_admin_enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array ( $this, 'handle_enqueue_scripts' ), 20 );
        add_action( 'save_post', array ( $this, 'handle_save_post' ), 1, 2 );

        // filter to do full replacement or injuection of event template
        add_filter( 'template_include', array ( $this, 'filter_template_include' ) );

        // add action to end of head processing to note when it is done rendering
        add_action( 'wp_head', array( $this, 'handle_wp_head_complete' ), 0xFFFF );

    }

    public function get_event_cost_description() {
        $price = array();
        $price_count = array();
        $num_rules = 1;

        // get all the price rules
        for ( $rule = 1; $rule <= 5; $rule ++ ) {
            $price[ $rule ]       = $this->get_meta( 'registration_price' . $rule );
            $price_count[ $rule ] = $this->get_meta( 'registration_price_count' . $rule );
            if ( $price_count[ $rule ] > 0 ) {
                $num_rules ++;
            } else {
                break;
            }
        }

        // construct human readable version of cost rules
        if ( 0 == $num_rules) {
            return 'There is no cost to register for this ' . $this->singular . '.';
        } elseif ( 1 == $num_rules ) {
            return 'Registration is $' . $price[ 1 ] . ' per person.';
        } else {
            $text = 'Registration is $';
            for ( $rule = 1; $rule <= 5 ; $rule ++ ) {
                if ( $rule > 1 ) {
                    if ( 0 == $price[ $rule ] ) {
                        $text .= ', then no cost for ';
                    } else {
                        $text .= ', then $';
                    }
                }

                if ( 0 != $price[ $rule ] ) {
                    $text .= $price[ $rule ] . ' for ';
                }

                if ( $price_count[ $rule ] > 1 ) {
                    $text .= 'the ' . ( 1 == $rule ? 'first ' : 'next ' ) . $price_count[ $rule ] . ' family members';
                } elseif ( 1 == $price_count[ $rule ] ) {
                    $text .= 'the ' . ( 1 == $rule ? 'first ' : 'next ' ) . 'family member';
                } else {
                    $text .= 'each additional family member.';
                    break;
                }
            }
            return $text;
        }

    }

    public function handle_init() {
        $labels = array(
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'menu_name'          => 'Events',
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
            'rewrite' => array( 'slug' => 'dojo-event', 'with_front' => false ),
            'supports' => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
            ),
            'taxonomies' => array( 'post_tag' ),
            'map_meta_cap' => true,
            'has_archive' => true,
        );

        register_post_type( 'dojo_event', $args );
    }

    public function handle_add_meta_boxes() {
        add_meta_box( 'event-schedule', 'Event Schedule', array( $this, 'render_edit_event_schedule' ), 'dojo_event', 'normal', 'high' );
        add_meta_box( 'event-registration', 'Event Registration', array( $this, 'render_edit_event_registration' ), 'dojo_event', 'normal', 'high' );
    }

    public function handle_admin_enqueue_scripts() {
        // register plugin scripts
        wp_register_script( 'dojo-event-edit', plugins_url( 'js/event-edit.js', __FILE__ ) );

        // enqueue scripts
        wp_enqueue_script( 'dojo-event-edit' );
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'dojo-event-edit' );

        // enqueue styles
        wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
    }

    public function handle_enqueue_scripts() {
        // register plugin scripts
        wp_register_script( 'dojo-event', plugins_url( 'js/event.js', __FILE__ ) );

        // register plugin styles
        wp_register_style( 'dojo-event', plugins_url( 'css/dojo-event.css', __FILE__ ) );

        // enqueue scripts
        wp_enqueue_script( 'dojo-event' );

        // enqueue styles
        wp_enqueue_style( 'dojo-event' );
    }

    public function handle_save_post($post_id, $post) {
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
                'price1',
                'price_count1',
                'price2',
                'price_count2',
                'price3',
                'price_count3',
                'price4',
                'price_count4',
                'price5',
                'price_count5',
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
    }

    public function handle_wp_head_complete() {
        $this->head_complete = true;
    }

    public function handle_loop_start( $query ) {
        if ($query->is_main_query() && $this->head_complete) {
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

    public function filter_the_content_override() {
        // unregister filter so we only do this once
        remove_filter( 'the_content', array( $this, 'filter_the_content_override' ) );

        // render view
        $post = $this->post;
        ob_start();
        include $this->views_folder . 'content-dojo-event.php';
        return ob_get_clean();
    }

    public function filter_the_content_extend( $content ) {
        $post = $GLOBALS[ 'post' ];

        // render extended content
        ob_start();
        include $this->views_folder . 'extend-content.php';

        // return extended content appended to original content
        return $content . ob_get_clean();
    }

    public function filter_template_include( $template ) {
        global $wp_query;

        // todo - option to select method of replacement
        $mode = 'extend';

        if ( $wp_query->query[ 'post_type' ] == 'dojo_event') {
           
            if ( $mode == 'full' ) {
                $template = $this->views_folder . 'single-dojo-event.php';
            } elseif ( $mode == 'inject' ) {
                // handle loop start to hijack and inject event render into content of theme template
                add_action( 'loop_start', array( $this, 'handle_loop_start' ) );
            } elseif ( $mode == 'extend' ) {
                add_filter( 'the_content', array( $this, 'filter_the_content_extend' ) );

                $post = $GLOBALS[ 'post' ];

                // make post render as page which is usually without author and create date
                $post->post_type = 'page';

                // inject date  and start time into title
                $date = strtotime( $this->get_meta( 'schedule_date' ) );
                $hour = $this->get_meta( 'schedule_start_hour' );
                $min = $this->get_meta( 'schedule_start_minute' );
                $mer = $this->get_meta( 'schedule_start_is_pm' ) ? 'pm' : 'am';
                if ( 1 == strlen( $min ) ) {
                    $min = '0' . $min;
                }
                $post->post_title .= '<br><span class="dojo-event-start" style="font-size:.6em;">' . self::get_formatted_start_time( $post ) . '</span>';
            }
        }

        return $template;
    }

    protected function get_meta( $key, $default = '' ) {
        global $post;

        if ( ! in_array( $key, get_post_custom_keys( $post->ID ) ) ) {
            return $default;
        }
        return get_post_meta( $post->ID, $key, true );
    }

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

        return date( 'l, M nS', $date ) . " @$hour:$min $mer";
    }

    public function render_edit_event_schedule() {
        wp_nonce_field( basename( __FILE__ ), 'dojo_event_nonce');
        include $this->views_folder . 'edit_event_schedule.php';
    }

    public function render_edit_event_registration() {
        include $this->views_folder . 'edit_event_registration.php';
    }
}


