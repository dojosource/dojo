<?php
/**
 * Dojo Uninstall
 *
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { die(); }

// include loader
require_once plugin_dir_path( __FILE__ ) . 'class-dojo-loader.php';

// tell plugin to uninstall
Dojo::instance()->uninstall();
