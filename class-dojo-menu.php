<?php
/**
 * Dojo menu
 */

if ( !defined( 'ABSPATH' ) ) {  die( '-1' ); }

class Dojo_Menu {
    private static $instance;

    const DASHBOARD_TOP     = 'top';
    const DASHBOARD_LEFT    = 'left';
    const DASHBOARD_RIGHT   = 'right';
    const DASHBOARD_BOTTOM  = 'bottom';

    private $dashboard_blocks;

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'handle_admin_menu' ) );
    }

    /**
     * Get singleton instance
     * 
     * @return Dojo_Menu
     */
    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Add a new menu page to the dojo menus. Called from plugins handling dojo_register_menus action.
     *
     * @param string $title Menu title as it will appear in the side bar to select the menu.
     * @param Object $owner The owning object that will be implementing render_menu_$title 
     *
     * @return void
     */
    public function add_menu( $title, $owner ) {
        add_submenu_page(
            'dojo-admin',
            'Dojo ' . $title,
            $title,
            'manage_options',
            'dojo-' . strtolower( str_replace( ' ', '-', $title ) ),
            array( $owner, 'render_menu_' . strtolower( str_replace( ' ', '_', $title ) ) )
        );
    }

    /**
     * Adds a block to be displayed on the dashboard menu
     *
     * @param string $block_name Unique name to identify the block
     * @param string $location Use constant definitions DASHBOARD_*
     * @param object $owner
     *
     * @return void
     */
    public function add_dashboard_block( $block_name, $location, $owner ) {
        $this->dashboard_blocks[ $location ][ $block_name ] = $owner;
    }

    /**** Action Handlers ****/

    public function handle_admin_menu() {
        // create root menu option
        add_menu_page(
            'My Dojo',
            'My Dojo',
            'manage_options',
            'dojo-admin',
            array( $this, 'render_menu_dashboard' ),
            'dashicons-groups',
            5
        );

        // dashboard (root) submenu
        add_submenu_page(
            'dojo-admin',
            'Dojo Dashboard',
            'Dashboard',
            'manage_options',
            'dojo-admin',
            array( $this, 'render_menu_dashboard' )
        );

        // action hook for plugins to add menus
        do_action( 'dojo_register_menus', $this );

        // settings submenu
        $this->add_menu( 'Settings', Dojo_Settings::instance() );
    }

    /**** Render Menus ****/

    public function render_menu_dashboard() {
        $this->dashboard_blocks = array(
            self::DASHBOARD_TOP     => array(),
            self::DASHBOARD_LEFT    => array(),
            self::DASHBOARD_RIGHT   => array(),
            self::DASHBOARD_BOTTOM  => array(),
        );

        /*
         * When handling this action, call add_dashboard_block to register
         */
        do_action( 'dojo_add_dashboard_blocks', $this );

        ?>
        <div class="dojo-container wrap">
            <h1>Dojo Dashboard</h1>
            <div class="dojo-clear-space"></div>
            <?php foreach ( $this->dashboard_blocks[ self::DASHBOARD_TOP ] as $block_name => $owner ) : ?>
            <div class="dojo-dashboard-block">
                <?php $owner->render_dashboard_block( $block_name ) ?>
            </div>
            <?php endforeach; ?>
            <div class="dojo-row">
                <div class="dojo-col-md-6">
                    <?php foreach ( $this->dashboard_blocks[ self::DASHBOARD_LEFT ] as $block_name => $owner ) : ?>
                    <div class="dojo-dashboard-block">
                        <?php $owner->render_dashboard_block( $block_name ) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="dojo-col-md-6">
                    <?php foreach ( $this->dashboard_blocks[ self::DASHBOARD_RIGHT ] as $block_name => $owner ) : ?>
                    <div class="dojo-dashboard-block">
                        <?php $owner->render_dashboard_block( $block_name ) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php foreach ( $this->dashboard_blocks[ self::DASHBOARD_BOTTOM ] as $block_name => $owner ) : ?>
            <div class="dojo-dashboard-block">
                <?php $owner->render_dashboard_block( $block_name ) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php 
    }
}

