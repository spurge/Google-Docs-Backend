<?php

/*
Plugin Name: Google Docs Backend
Plugin URI: https://github.com/spurge/google-docs-backend
Description: This plugin will hopefully display all your Google Docs items as posts.
Version: 0.1
Author: Klandestino
Author URI: http://klandestino.se
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
 * This plugin is not a stand-alone script. Fail if this is loaded
 * outside Wordpress.
 */
if ( ! function_exists( 'add_action' ) ) {
	echo "Hi there! I'm just a plugin, not much I can do when called directly.";
	exit;
}

/**
 * Define plugin constants
 */
define( 'GDOCSBACK_VERSION', '0.1' );
define( 'GDOCSBACK_PLUGIN_DIR_NAME', dirname( plugin_basename( $plugin ) ) );
define( 'GDOCSBACK_PLUGIN_URL', plugin_dir_url( plugin_basename( $plugin ) ) );
define( 'GDOCSBACK_TEMPLATE_DIR', dirname( __FILE__ ) . '/templates' );

/**
 * Inlude and define a global plugin object
 */
set_include_path( get_include_path() . PATH_SEPARATOR . dirname( __FILE__ ) . '/external/ZendGdata-1.11.11/library' );
require_once( 'Zend/Loader.php' );
Zend_Loader::loadClass( 'Zend_Http_Client' );
Zend_Loader::loadClass( 'Zend_Gdata' );
Zend_Loader::loadClass( 'Zend_Gdata_ClientLogin' );
Zend_Loader::loadClass( 'Zend_Gdata_Docs' );
Zend_Loader::loadClass( 'Zend_Gdata_Spreadsheets' );
require_once( dirname( __FILE__ ) . '/google-docs-backend.class.php' );

$google_docs_backend = new Google_Docs_Backend();
//$google_docs_backend->doc_list();
