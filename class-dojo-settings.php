<?php
/**
 * Dojo settings
 */

if ( !defined( 'ABSPATH' ) ) {  die(); }

class Dojo_Settings {
    private static $instance;

    private $options;
    private $options_meta = array();

    private function __construct() {
        $this->options = get_option( 'dojo_options' );

        add_action( 'admin_init', array( $this, 'handle_admin_init' ) );
        add_action( 'update_option_dojo_options', array( $this, 'handle_update_option_dojo_options' ) );
    }

    /**
     * Get singleton instance
     * 
     * @return Dojo_Settings
     */
    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Get option setting
     *
     * @param string $option_id
     *
     * @return mixed
     */
    public function get( $option_id ) {
        if ( isset( $this->options[ $option_id ] ) ) {
            return $this->options[ $option_id ];
        }
        return null;
    }

    /**
     * Set option setting
     *
     * @param $option_id
     * @param $value
     *
     * @return void;
     */
    public function set( $option_id, $value ) {
        $this->options[ $option_id ] = $value;
        set_option( 'dojo_options', $this->options );
    }

    /**
     * Register a new options section 
     * 
     *  @param string $section_id Section identifier
     *  @param string $title Section title
     *  @param string $subtitle Html string for subtitle
     *
     *  @return void
     */
    public function register_section( $section_id, $title, $subtitle ) { 
        $this->options_meta[ $section_id ]['id']        = $section_id;
        $this->options_meta[ $section_id ]['title']     = $title;
        $this->options_meta[ $section_id ]['subtitle']  = $subtitle;

        // It's possible options were added before section defined so only initialize options if not set
        if ( ! isset( $this->options_meta[ $section_id ]['options'] ) ) {
            $this->options_meta[ $section_id ]['options'] = array();
        }
    }

    /**
     * Register a settings option. Owner object must implement method render_option_$option_id() and
     * may optionally implement method sanitize_option_$option_id().
     *
     * @param string $section_id Section to place this option in
     * @param string $option_id Option identifier, unique within context of plugin, not globally.
     * @param string $option_name Name of option
     * @param Object $owner Instance of object requiring this option.  
     * 
     * @return void
     */
    public function register_option( $section_id, $option_id, $option_name, $owner ) {
        $this->options_meta[ $section_id ]['options'][ $option_id ] = array(
            'id'    => $option_id,
            'name'  => $option_name,
            'owner' => $owner,
        );
    }

    /**
     * Utility function for rendering text option
     *
     * @param string $id Option id
     * @param string $class Optional css class, default = regular-text
     *
     * @return void
     */
    public function render_text_option( $id, $class = 'regular-text' ) {
    }

    public function render_textarea_option( $id, $label = '', $cols = 0, $rows = 10, $class = 'large-text' ) {
        printf(
            '<label for="' . $id . '">' . esc_html( $label ) . '</label>
            <textarea id="' . $id . '" name="dojo_options[' . $id . ']"' . ( $cols > 0 ? ' cols="' . $cols . ':' : '' ) . ' rows="' . $rows . '" class="' . $class . '">%s</textarea>',
            isset( $this->options[ $id ] ) ? esc_html( $this->options[ $id ]) : ''
        );

    }

    public function handle_admin_init() {
        // action handlers should respond with calls to register_section and register_option
        do_action( 'dojo_register_settings', $this );

        // this one setting covers all the plugin options
        register_setting(
            'dojo_option_group',                // option group
            'dojo_options',                     // option name
            array( $this, 'sanitize_options' )  // sanitize callback
        );

        // register sections
        foreach ( $this->options_meta as $section ) {
            add_settings_section(
                $section['id'],
                $section['title'],
                array( $this, 'render_section_subtitle' ),
                'dojo-settings'
            );

            foreach ( $section['options'] as $option ) {
                add_settings_field(
                    $option['id'],
                    $option['name'],
                    array( $option['owner'], 'render_option_' . $option['id'] ),
                    'dojo-settings',
                    $section['id']
                );
            }
        }
    }

    public function handle_update_option_dojo_options() {
        // refresh options
        $this->options = get_option( 'dojo_options' );

        // let everyone know we have updated options
        do_action( 'dojo_settings_updated', $this );
    }

    public function render_menu_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied' );
        }

        ?>
        <div class="wrap">
            <h1>Dojo Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields( 'dojo_option_group' );
                do_settings_sections( 'dojo-settings' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function render_section_subtitle( $section ) {
        echo $this->options_meta[ $section['id'] ]['subtitle'];
    }

    public function sanitize_options( $input ) {
        return apply_filters( 'dojo_settings_sanitize', $input );
    }

}

