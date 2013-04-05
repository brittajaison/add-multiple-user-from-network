<?php

/*
Plugin Name: Add Multiple Users From Network
Plugin URI: http://github.com/ubc/add-multiple-users-from-network
Description: This plugin allows you to add multiple user accounts to your Wordpress blog using a range of tools. Based on the work by HappyNuclear 
Version: 1.0.0
Author: Enej, CTLT-dev
Author URI: http://github.com/ubc
Text Domain: amulang
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/



//protect from direct call
if ( !function_exists( 'add_action' ) ) {
	echo "Access denied!";
	exit;
}
function amu_menu() {
	
	if ( is_multisite() ) {
	
		$my_admin_page = add_submenu_page('users.php',__('Add from Network','amulang'),__('Add from Network','amulang'),'manage_options','amuaddfromnet','amu_add_from_net');
		
		add_action('load-'.$my_admin_page, 'amu_add_help_tab');
	}
}

// <=========== RUN ACTIONS ===============================================================>

if ( is_multisite() ) {
     add_action( 'network_admin_menu', 'amu_network_menu' );
}
add_action( 'admin_menu', 'amu_menu' );

// <=========== INCLUDE FUNCTION FILES ====================================================>

include('functions/networkoptions.php');


// <=========== LOCALIZATION ====================================================>

load_plugin_textdomain('amulang', false, dirname(plugin_basename(__FILE__)) . '/lang');
?>