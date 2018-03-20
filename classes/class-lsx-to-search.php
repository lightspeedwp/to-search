<?php
if ( ! class_exists( 'LSX_TO_Search' ) ) {
	/**
	 * LSX Search Main Class
	 */
	class LSX_TO_Search {
		/**
		 * The plugins slug, used for the text domain
		 *
		 * @var      string
		 */
		public $plugin_slug = 'to-search';

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
			add_action( 'init', array( $this, 'set_vars' ) );
			add_action( 'init', array( $this, 'set_facetwp_vars' ) );

			// Make TO last plugin to load.
			add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			require_once( LSX_TO_SEARCH_PATH . '/classes/class-lsx-to-search-admin.php' );
			if ( ! is_admin() ) {
				require_once( LSX_TO_SEARCH_PATH . '/classes/class-lsx-to-search-frontend.php' );
			}
			require_once( LSX_TO_SEARCH_PATH . '/classes/class-lsx-to-search-facetwp.php' );

			// flush_rewrite_rules()
			register_activation_hook( LSX_TO_SEARCH_CORE, array( $this, 'register_activation_hook' ) );

			//require_once(LSX_TO_SEARCH_PATH . '/classes/class-to-search-destination-facet.php');
			//add_action( 'facetwp_facet_types', array( $this, 'register_facet' ) );
		}
		/**
		 * Sets the variables.
		 */
		public function set_vars() {
			$this->options = get_option( '_lsx-to_settings', false );

			$this->post_types = apply_filters( 'lsx_to_search_post_types', array() );
			$this->taxonomies = apply_filters( 'lsx_to_search_taxonomies', array() );

			$this->post_type_slugs = false;
			if ( ! empty( $this->post_types ) ) {
				foreach ( $this->post_types as $key => $value ) {
					$this->post_type_slugs[ strtolower( $value ) ] = $key;
				}
			}
		}
		/**
		 * Sets the facetwp variable.
		 */
		public function set_facetwp_vars() {
			$facet_data = null;
			if ( class_exists( 'FacetWP' ) ) {
				$facet_data = FWP()->helper->get_facets();
			}
			$this->facet_data['search_form'] = array(
				'name' => 'search_form',
				// @codingStandardsIgnoreLine
				'label' => __( 'Search Form', 'to-search' )
			);

			if ( is_array( $facet_data ) && ! empty( $facet_data ) ) {
				foreach ( $facet_data as $facet ) {
					$this->facet_data[ $facet['name'] ] = $facet;
				}
			}
		}

		function register_facet( $facet_types ) {
			$facet_types['destinations'] = new TO_Search_Destination_Facet();
			return $facet_types;
		}
		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'to-search', false, basename( LSX_TO_SEARCH_PATH ) . '/languages' );
		}
		/**
		 * Make TO last plugin to load.
		 */
		public function activated_plugin() {
			// @codingStandardsIgnoreLine
			if ( $plugins = get_option( 'active_plugins' ) ) {
				$search = preg_grep( '/.*\/tour-operator\.php/', $plugins );
				$key = array_search( $search, $plugins );

				if ( is_array( $search ) && count( $search ) ) {
					foreach ( $search as $key => $path ) {
						array_splice( $plugins, $key, 1 );
						array_push( $plugins, $path );
						update_option( 'active_plugins', $plugins );
					}
				}
			}
		}
		/**
		 * On plugin activation
		 */
		public function register_activation_hook() {
			if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {
				set_transient( '_tour_operators_search_flush_rewrite_rules', 1, 30 );
			}
		}
		/**
		 * On plugin activation (check)
		 */
		public function register_activation_hook_check() {
			if ( ! get_transient( '_tour_operators_search_flush_rewrite_rules' ) ) {
				return;
			}

			delete_transient( '_tour_operators_search_flush_rewrite_rules' );
			flush_rewrite_rules();
		}

	}
	new LSX_TO_Search();
}
