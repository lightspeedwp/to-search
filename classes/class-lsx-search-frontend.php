<?php
/**
 * LSX_Search Frontend Main Class
 */

class LSX_Search_Frontend extends LSX_Search{

	/**
	 * Holds the current search slug, if any
	 *
	 * @var      string
	 */
	public $search_slug = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_head', array($this,'wp_head'));
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function wp_head() {

		$search_slug = false;
		if(is_search()){
			$search_slug = 'general';

			$engine = get_query_var('engine');
			if(false !== $engine && 'default' !== $engine && '' !== $engine){
				$search_slug = $engine;	
			}

			$option_slug_1 = $option_slug_2 = 'search';

		}elseif(is_post_type_archive($this->post_types) || is_tax($this->taxonomies)){
			$search_slug = get_post_type();

			$option_slug_1 = 'facets';
			$option_slug_2 = 'archive';
		}

		if(false !== $search_slug && false !== $this->options && isset($this->options[$search_slug]['enable_'.$option_slug_1])){
			$this->search_slug = $search_slug;

			add_action('lsx_content_top', array($this,'lsx_content_top'));
			add_action('lsx_content_bottom', array($this,'lsx_content_bottom'));

			if(isset($this->options[$this->search_slug][$option_slug_2.'_layout']) && '1c' !== $this->options[$this->search_slug][$option_slug_2.'_layout']){
				add_action('lsx_content_wrap_after', array($this,'search_sidebar'));		
				add_filter('lsx_sidebar_enable', array($this,'lsx_sidebar_enable'), 10, 1);		
			}elseif('1c' === $this->options[$this->search_slug][$option_slug_2.'_layout']){
				add_action('lsx_content_wrap_before', array($this,'search_sidebar'));
			}	
		}
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