<?php
/**
 * dojo extension base class
 */

if ( ! defined( 'ABSPATH' ) ) { die(); }

class Dojo_Extension extends Dojo_WP_base {
    private $title;
    private $version;

    protected function __construct( $title, $version = '' ) {
        $this->title = $title;
        $this->version = $version;
    }
    
    /**
     * Get title of extension
     *
     * @return string
     */
    public function title() {
        return $this->title;
    }

    /**
     * Get version of extension
     *
     * @return string
     */
    public function version() {
        return $this->version;
    }

    /**
     * Get installer instance for this extension
     *
     * @return Dojo_Installer_Base derived class or null if installer doesn't exist
     */
    public function installer() {
        $class = get_class( $this ) . '_Installer';
        if ( class_exists( $class ) ) {
            return $this->get_instance( $class );
        }
        return null;
    }

    /**
     * Get model instance for this extension
     *
     * @return Dojo_Model_Base derived class or null if installer doesn't exist
     */
    public function model() {
        $class = get_class( $this ) . '_Model';
        if ( class_exists( $class ) ) {
            return $this->get_instance( $class );
        }
        return null;
    }

    /**
     * Log an event to the event log. If user_id not set will default to current user.
     *
     * @param string $event
     * @param int $user_id
     *
     * @return void
     */
    public function log_event( $event, $user_id = null ) {
        if ( null == $user_id ) {
            $user = wp_get_current_user();
            if ( $user ) {
                $user_id = $user->ID;
            }
        }

        Dojo::instance()->log_event( $event, $user_id, get_class( $this ) );
    }

    /**
     * Gets the path of the derived extension with trailing slash
     * @param string $file Optional relative path file to append
     *
     * @return string
     */
    protected function path( $file = '' ) {
        $class = get_class( $this );
        return plugin_dir_path( __FILE__ ) . str_replace( '_', '-', strtolower( $class )) . '/' . $file;
    }

    /**
     * Gets the url of the derived extension with trailing slash and optionally appended file
     * @param string $file Optional relative path file to append
     * 
     * @return string
     */
    protected function url( $file = '' ) {
        $class = get_class( $this );
        return plugin_dir_url( __FILE__ ) . str_replace( '_', '-', strtolower( $class ) ) . '/' . $file;
    }



    /**
     * Registers custom pages for this extension. Pages passed in as name => slug pairs where name is the name of
     * the registration and slug is the root level url slug. Two methods have to be implemented matching the name:
     * custom_page_<name>( $path ) should render the content of the page and return true if path is valid.
     * custom_page_title<name>( $path ) should return the title of the page.
     * The path passed into the callbacks is the url path following the root slug. The slug is not included in path.
     *
     * @param array( name => slug ) $pages
     *
     * @return void
     */
    protected function register_custom_pages( $pages ) {
        $dojo = Dojo::instance();
        foreach ( $pages as $name => $slug ) {
            $dojo->register_custom_page(
                $slug,
                array ( $this, 'custom_page_' . $name ),
                array ( $this, 'custom_page_title_' . $name )
            );
        }
    }

    /**
     * Renders a view from the views subfolder of the derived extension
     *
     * @param string $view Name of the view to render
     * @param array $data Optional array of parameters to include as $data in the view context
     *
     * @return string Rendered content
     */
    protected function render( $view, $data = array() ) {
        // add settings to view context
        $settings = Dojo_Settings::instance();
        $path = $this->path() . 'views/';
        ob_start();
        include $path . $view . '.php';
        return ob_get_clean();
    }

    /**
     * Renders a settings option for a basic checkbox
     *
     * @param string $id option id
     * @param string $label Text label to the right of the checkbox
     *
     * @return void
     */
    protected function render_option_checkbox( $id, $label ) {
        ?>
        <p>
            <label for="<?php echo esc_attr( $id ) ?>">
                <input type="checkbox" id="<?php echo esc_attr( $id ) ?>" name="dojo_options[<?php echo esc_attr( $id ) ?>]" value="1" <?php checked( $this->get_setting( $id ), '1' ) ?> />
                <?php echo $label ?>
            </label>
        </p>
        <?php
    }

    /**
     * Renders a settings option for a regular text field
     *
     * @param string $id option id
     * @param string $label
     *
     * @return void
     */
    protected function render_option_regular_text( $id, $label = '' ) {
        ?>
        <?php if ( '' != $label ) : ?>
            <?php echo $label ?><br />
        <?php endif; ?>
        <input type="text" id="<?php echo esc_attr( $id ) ?>" name="dojo_options[<?php echo esc_attr( $id ) ?>]" class="regular-text" value="<?php echo esc_attr( $this->get_setting( $id ) ) ?>" />
        <?php
    }
}
 
