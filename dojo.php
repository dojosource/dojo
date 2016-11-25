<?php
/*
Plugin Name: Dojo
Plugin URI: http://dojosource.com
Description: Manage and grow your martial arts school with easy to use tools for your students, teachers and you!
Author: Dojo Source
Text Domain: dojo
License: GPLv2 or later
Version: 0.23
*/

/*
Dojo is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Dojo is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Dojo. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) { die(); }

// include loader
require_once plugin_dir_path( __FILE__ ) . 'class-dojo-loader.php';

// register activate/deactivate hooks
register_activation_hook( __FILE__, array( 'Dojo', 'handle_activate' ) );
register_deactivation_hook( __FILE__, array( 'Dojo', 'handle_deactivate' ) );

// create plugin instance
Dojo::instance();

