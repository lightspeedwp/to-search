<?php
/*
 * Plugin Name: Tour Operator Search 
 * Plugin URI:  https://www.lsdev.biz/product/tour-operator-search/
 * Description: The Tour Operator Search extension adds robust search functionality to sites, allowing filterable search by post type, category and more.
 * Version:     1.0
 * Author:      LightSpeed WordPress Development
 * Author URI:  https://www.lsdev.biz/
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: lsx-search
 * Domain Path: /languages
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
    $lsx_to_password = get_option('lsx_api_instance',false);
    if(false === $lsx_to_password){
    	update_option('lsx_api_instance',LSX_API_Manager::generatePassword());
    }
}
register_activation_hook( __FILE__, 'lsx_search_activate_plugin' );

/* ======================= The API Classes ========================= */
if(!class_exists('LSX_API_Manager')){
	require_once('classes/class-lsx-api-manager.php');
}

/** 
 *	Grabs the email and api key from the LSX Search Settings.
 */ 
function lsx_search_options_pages_filter($pages){
	$pages[] = 'to-settings';
	return $pages;
}
add_filter('lsx_api_manager_options_pages','lsx_search_options_pages_filter',10,1);

function lsx_search_api_admin_init(){
	$options = get_option('_to_settings',false);
	$data = array('api_key'=>'','email'=>'');

	if(false !== $options && isset($options['general'])){
		if(isset($options['general']['lsx-search_api_key']) && '' !== $options['general']['lsx-search_api_key']){
			$data['api_key'] = $options['general']['lsx-search_api_key'];
		}
		if(isset($options['general']['lsx-search_email']) && '' !== $options['general']['lsx-search_email']){
			$data['email'] = $options['general']['lsx-search_email'];
		}		
	}

	$api_array = array(
		'product_id'	=>		'LSX Search',
		'version'		=>		'1.0.0',
		'instance'		=>		get_option('lsx_api_instance',false),
		'email'			=>		$data['email'],
		'api_key'		=>		$data['api_key'],
		'file'			=>		'lsx-search.php'
	);

	$lsx_search_api_manager = new LSX_API_Manager($api_array);
}
add_action('admin_init','lsx_search_api_admin_init');

/* ======================= Below is the Plugin Class init ========================= */

require_once( LSX_SEARCH_PATH . '/classes/class-lsx-search.php' );