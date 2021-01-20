<?php
/*
 * Plugin Name: LSX Tour Operator Search
 * Plugin URI:  https://www.lsdev.biz/product/tour-operator-search/
 * Description: The Tour Operator Search extension adds robust search functionality to sites, allowing filterable search by post type, category and more.
 * Version:     1.4.2
 * Author:      LightSpeed
 * Author URI:  https://www.lsdev.biz/
 * License:     GPL3+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: to-search
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'LSX_TO_SEARCH_PATH', plugin_dir_path( __FILE__ ) );
define( 'LSX_TO_SEARCH_CORE', __FILE__ );
define( 'LSX_TO_SEARCH_URL', plugin_dir_url( __FILE__ ) );
define( 'LSX_TO_SEARCH_VER', '1.4.2' );

/* ======================= Below is the Plugin Class init ========================= */

require_once LSX_TO_SEARCH_PATH . '/classes/class-lsx-to-search.php';
require_once LSX_TO_SEARCH_PATH . '/includes/pluggable.php';

/**
 * Block Initializer.
 */
require_once LSX_TO_SEARCH_PATH . 'src/init.php';
