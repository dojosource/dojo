<?php
/**
 * @package Dojo 
 * @version 1.0
 */
/*
Plugin Name: Dojo
Plugin URI: http://dojowordpress.com
Description: Manage and grow your martial arts school with easy to use tools for your students, teachers and you!
Author: David Mathis 
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) { die(); }

// include loader
require_once plugin_dir_path( __FILE__ ) . 'class-dojo-loader.php';

// register activate/deactivate hooks
register_activation_hook( __FILE__, array( 'Dojo', 'handle_activate' ) );
register_deactivation_hook( __FILE__, array( 'Dojo', 'handle_deactivate' ) );

// create plugin instance
Dojo::instance();

