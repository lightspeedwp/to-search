<?php
/**
 * LSX_Search Frontend Main Class
 */

class LSX_Search_Frontend extends LSX_Search{

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}				

	/**
	 * Enques the assets
	 */
	public function assets() {

		if(defined('WP_DEBUG') && true === WP_DEBUG){
			$min='';
		}else{
			$min = '.min';
		}
		wp_enqueue_script( 'lsx_search', LSX_SEARCH_URL.'/assets/js/lsx-search'.$min.'.js', array(
			'jquery',
		), '1.0.0', true );

		$params = apply_filters( 'lsx_search_js_params', array(
			'ajax_url'		=>		admin_url('admin-ajax.php'),
		));
		wp_localize_script( 'lsx_search', 'lsx_search_params', $params );		
	}
}
new LSX_Search_Frontend();