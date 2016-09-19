<?php
/*
 * Plugin Name:	LSX Search
 * Plugin URI:	https://bitbucket.org/feedmycode/lsx-search
 * Description:	{plugin-description}
 * Author:		LightSpeed
 * Version: 	1.0.0
 * Author URI: 	https://www.lsdev.biz/products/
 * License: 	GPL2+
 * Text Domain: lsx-search
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('LSX_SEARCH_PATH',  plugin_dir_path( __FILE__ ) );
define('LSX_SEARCH_CORE',  __FILE__ );
define('LSX_SEARCH_URL',  plugin_dir_url( __FILE__ ) );
define('LSX_SEARCH_VER',  '1.0.0' );

/**
 * Runs once when the plugin is activated.
 */
function lsx_search_activate_plugin() {
	//Insert code here
}
register_activation_hook( __FILE__, 'lsx_search_activate_plugin' );

/* ======================= Below is the Plugin Class init ========================= */

require_once( LSX_SEARCH_PATH . '/classes/class-lsx-search.php' );