<?php
if (!class_exists( 'LSX_Search' ) ) {
	/**
	 * LSX Search Main Class
	 */
	class LSX_Search {
		
		/** @var string */
		public $plugin_slug = 'lsx-search';

		/**
		 * Constructor
		 */
		public function __construct() {
			require_once(LSX_SEARCH_PATH . '/classes/class-lsx-search-admin.php');
			require_once(LSX_SEARCH_PATH . '/classes/class-lsx-search-frontend.php');
		}		
	}
	new LSX_Search();
}