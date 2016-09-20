<?php
if (!class_exists( 'LSX_Search' ) ) {
	/**
	 * LSX Search Main Class
	 */
	class LSX_Search {
		
		/**
		 * The plugins slug, used for the text domain
		 *
		 * @var      string
		 */
		public $plugin_slug = 'lsx-search';

		/**
		 * Holds the options
		 *
		 * @var      string
		 */
		public $options = false;

		/**
		 * Holds the post types that need search settings.
		 *
		 * @var      string
		 */
		public $post_types = array();	

		/**
		 * Holds the taxonomies that need search settings.
		 *
		 * @var      string
		 */
		public $taxonomies = array();

		/**
		 * Holds the current page instance
		 *
		 * @var      string
		 */
		public $search_slug = false;

		/**
		 * Holds the current themes layout
		 *
		 * @var      string
		 */
		public $layout = '2cr';	

		/**
		 * Holds the array of facets
		 *
		 * @var      array
		 */
		public $facet_data = false;	


		/**
		 * Constructor
		 */
		public function __construct() {
			require_once(LSX_SEARCH_PATH . '/classes/class-lsx-search-admin.php');
			require_once(LSX_SEARCH_PATH . '/classes/class-lsx-search-frontend.php');
		}

		/**
		 * Runs on the init action
		 */
		public function init(){
			if(class_exists('LSX_Tour_Operators')){
				$this->options = get_option('_lsx_lsx-settings',false);

				$this->post_types = apply_filters('lsx_search_post_types',array('general'=>__('General',$this->plugin_slug)));
				$this->taxonomies = apply_filters('lsx_search_taxonomies',array());

				$this->post_type_slugs = false;
				if(!empty($this->post_types)){
					foreach($this->post_types as $key => $value){
						$this->post_type_slugs[strtolower($value)] = $key;
					}
				}

				$facet_data = null;
				if(class_exists('FacetWP')){
					$facet_data = FWP()->helper->get_facets();
				}
				$this->facet_data['search_form'] = array('name'=>'search_form','label'=>__('Search Form',$this->plugin_slug));

				if(is_array($facet_data) && !empty($facet_data)){
					foreach($facet_data as $facet){
						$this->facet_data[$facet['name']] = $facet;
					}
				}				
			}
		}		

	}
	new LSX_Search();
}