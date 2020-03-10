<?php
/**
 * LSX_TO_Search Frontend Main Class
 */

class LSX_TO_Search_Frontend extends LSX_TO_Search {

	/**
	 * Holds the current search slug, if any
	 *
	 * @var      string
	 */
	public $search_slug = false;

	public $facet_data = false;

	/**
	 * Determine weather or not search is enabled for this page.
	 *
	 * @var boolean
	 */
	public $search_enabled = false;

	public $search_core_suffix = false;

	public $search_prefix = false;

	public $facet_counter = 0;

	/**
	 * If the search keyword matches a term then it will be stored here.
	 *
	 * @var boolean
	 */
	public $preselected_facet = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'set_vars' ) );
		add_action( 'init', array( $this, 'set_facetwp_vars' ) );
		add_action( 'init', array( $this, 'remove_posts_and_pages_from_search' ), 99 );

		add_action( 'lsx_to_settings_current_tab', array( $this, 'set_settings_current_tab' ) );
		add_filter( 'body_class', array( $this, 'body_class' ), 15, 1 );

		add_filter( 'lsx_to_the_title_end', array( $this, 'add_label_to_title' ) );

		add_action( 'wp_head', array( $this, 'wp_head' ), 11 );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 5 );

		// Redirects.
		add_filter( 'template_include', array( $this, 'search_template_include' ), 99 );
		add_action( 'template_redirect', array( $this, 'pretty_search_redirect' ) );
		add_filter( 'pre_get_posts', array( $this, 'pretty_search_parse_query' ) );

		// Layout Filter.
		add_filter( 'lsx_layout', array( $this, 'lsx_layout' ), 20, 1 );
		add_filter( 'lsx_layout_selector', array( $this, 'lsx_layout_selector' ), 10, 4 );
		add_filter( 'lsx_to_archive_layout', array( $this, 'lsx_to_search_archive_layout' ), 10, 2 );

		add_action( 'lsx_search_sidebar_top', array( $this, 'search_sidebar_top' ) );
		add_action( 'pre_get_posts', array( $this, 'price_sorting' ), 100 );

		add_shortcode( 'lsx_search_form', array( $this, 'search_form' ) );
		add_filter( 'searchwp_short_circuit', array( $this, 'searchwp_short_circuit' ), 10, 2 );
		add_filter( 'get_search_query', array( $this, 'get_search_query' ) );
		add_filter( 'body_class', array( $this, 'to_add_search_url_class' ), 20 );

		add_filter( 'facetwp_preload_url_vars', array( $this, 'preload_url_vars' ), 10, 1 );
		add_filter( 'wpseo_json_ld_search_url', array( $this, 'change_json_ld_search_url' ), 10, 1 );
	}

	/**
	 * Sets the FacetWP variables.
	 */
	public function set_facetwp_vars() {
		if ( class_exists( 'FacetWP' ) ) {
			$facet_data = FWP()->helper->get_facets();
		}

		$this->facet_data = array();

		$this->facet_data['search_form'] = array(
			'name' => 'search_form',
			'label' => esc_html__( 'Search Form', 'lsx-search' ),
		);

		if ( ! empty( $facet_data ) && is_array( $facet_data ) ) {
			foreach ( $facet_data as $facet ) {
				$this->facet_data[ $facet['name'] ] = $facet;
			}
		}
	}

	/**
	 * Remove posts and pages from search
	 *
	 */
	public function remove_posts_and_pages_from_search() {
		global $wp_post_types;
		$wp_post_types['post']->exclude_from_search = true;
		$wp_post_types['page']->exclude_from_search = true;
	}

	/**
	 * Sets the current tab selected.
	 */
	public function set_settings_current_tab( $settings_tab ) {
		if ( is_search() ) {
			$engine = get_query_var( 'engine' );

			if ( ! empty( $engine ) && 'default' !== $engine ) {
				$settings_tab = $engine;
			} else {
				$settings_tab = 'display';
			}
		}

		return $settings_tab;
	}

	/**
	 * Add a some classes so we can style.
	 */
	public function body_class( $classes ) {
		if ( is_search() ) {
			$classes[] = 'archive-tour-operator';
		}
		if ( true === $this->search_enabled ) {
			$classes[] = 'lsx-to-search-enabled';
		}
		return $classes;
	}

	/**
	 * Add post type label to the title.
	 */
	public function add_label_to_title( $id ) {
		if ( is_search() ) {
			$engine = get_query_var( 'engine' );
			if ( ! empty( $this->options['display']['enable_search_pt_label'] ) && ( 'default' === $engine || '' === $engine ) ) {
				echo wp_kses_post( ' <span class="label label-default lsx-label-post-type">' . ucwords( get_post_type() ) . '</span>' );
			}
		}
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function wp_head() {
		$search_slug = false;

		if ( is_search() ) {
			$search_slug = 'display';
			$engine = get_query_var( 'engine' );
			if ( false !== $engine && 'default' !== $engine && '' !== $engine ) {
				$search_slug = $engine;
			}
			$option_slug_1 = 'search';
			$option_slug_2 = 'search';
		} elseif ( is_post_type_archive( array_keys( $this->post_types ) ) || is_tax( array_keys( $this->taxonomies ) ) ) {
			$search_slug = get_post_type();
			$option_slug_1 = 'facets';
			$option_slug_2 = 'archive';
		}

		if ( false !== $search_slug && false !== $this->options && isset( $this->options[ $search_slug ][ 'enable_' . $option_slug_1 ] ) ) {
			$this->search_slug = $search_slug;

			remove_action( 'lsx_content_bottom', array( 'lsx\legacy\Frontend', 'lsx_default_pagination' ) );

			add_action( 'lsx_content_top', array( $this, 'lsx_content_top' ) );
			add_action( 'lsx_content_bottom', array( $this, 'lsx_content_bottom' ) );

			if ( isset( $this->options[ $this->search_slug ][ $option_slug_2 . '_layout' ] ) && '1c' !== $this->options[ $this->search_slug ][ $option_slug_2 . '_layout' ] ) {
				add_action( 'lsx_content_wrap_before', array( $this, 'search_sidebar' ), 150 );
				add_filter( 'lsx_sidebar_enable', array( $this, 'lsx_sidebar_enable' ), 10, 1 );
			} elseif ( '1c' === $this->options[ $this->search_slug ][ $option_slug_2 . '_layout' ] ) {
				add_action( 'lsx_content_wrap_before', array( $this, 'search_sidebar' ), 150 );
			}
			add_action( 'lsx_content_bottom', array( $this, 'facet_bottom_bar' ) );
		}
	}

	/**
	 * Enques the assets.
	 */
	public function assets() {
		add_filter( 'lsx_defer_parsing_of_js', array( $this, 'skip_js_defer' ), 10, 4 );

		$prefix = '.min';
		$src    = '';
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			$prefix = '';
			$src    = 'src/';
		}
		wp_enqueue_script( 'touchSwipe', LSX_TO_SEARCH_URL . 'assets/js/vendor/jquery.touchSwipe.min.js', array( 'jquery' ), LSX_TO_SEARCH_VER, true );
		wp_enqueue_script( 'slideandswipe', LSX_TO_SEARCH_URL . 'assets/js/vendor/jquery.slideandswipe.min.js', array( 'jquery', 'touchSwipe' ), LSX_TO_SEARCH_VER, true );
		wp_enqueue_script( 'lsx_to_search', LSX_TO_SEARCH_URL . 'assets/js/' . $src . 'to-search' . $prefix . '.js', array( 'jquery', 'touchSwipe', 'slideandswipe' ), LSX_TO_SEARCH_VER, true );

		$params = apply_filters( 'lsx_to_search_js_params', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'facets'       => $this->preselected_facet,
			'scrollOnLoad' => true,
		));
		wp_localize_script( 'lsx_to_search', 'lsx_to_search_params', $params );
		wp_enqueue_style( 'lsx_to_search', LSX_TO_SEARCH_URL . 'assets/css/to-search.css', array(), LSX_TO_SEARCH_VER );
	}

	public function get_facet_name_by_value( $value = '' ) {
		global $wpdb;
		// @codingStandardsIgnoreStart
		$return = $wpdb->get_var( "SELECT `facet_name`, `id` FROM `{$wpdb->prefix}facetwp_index` WHERE `facet_value` = '{$value}'" );
		// @codingStandardsIgnoreEnd
		return $return;
	}

	/**
	 * Adds the to-search.min.js and the to-search.js
	 *
	 * @param boolean $should_skip
	 * @param string  $tag
	 * @param string  $handle
	 * @param string  $href
	 * @return boolean
	 */
	public function skip_js_defer( $should_skip, $tag, $handle, $href ) {
		if ( ! is_admin() && ( false !== stripos( $href, 'to-search.min.js' ) || false !== stripos( $href, 'to-search.js' ) ) ) {
			$should_skip = true;
		}
		return $should_skip;
	}

	/**
	 * Redirect wordpress to the search template located in the plugin
	 *
	 * @param	$template
	 * @return	$template
	 */
	public function search_template_include( $template ) {
		if ( is_main_query() && is_search() ) {
			if ( file_exists( LSX_TO_SEARCH_PATH . 'templates/search.php' ) ) {
				$template = LSX_TO_SEARCH_PATH . 'templates/search.php';
			}
		}

		return $template;
	}

	/**
	 * Rewrite the search URL
	 */
	public function pretty_search_redirect() {
		global $wp_rewrite,$wp_query;

		if ( ! isset( $wp_rewrite ) || ! is_object( $wp_rewrite ) || ! $wp_rewrite->using_permalinks() ) {
			return;
		}

		$search_base = $wp_rewrite->search_base;

		if ( is_search() && ! is_admin() && strpos( $_SERVER['REQUEST_URI'], "/{$search_base}/" ) === false ) {
			$search_query = get_query_var( 's' );
			$engine = '';

			// If the search was triggered by a supplemental engine.
			if ( isset( $_GET['engine'] ) && 'default' !== $_GET['engine'] ) {
				$engine = $_GET['engine'];
				set_query_var( 'engine', $engine );
				$engine = array_search( $engine,$this->post_type_slugs ) . '/';
			}

			$get_array = $_GET;

			if ( is_array( $get_array ) && ! empty( $get_array ) ) {
				$vars_to_maintain = array();

				foreach ( $get_array as $ga_key => $ga_value ) {
					if ( false !== strpos( $ga_key, 'fwp_' ) ) {
						$vars_to_maintain[] = $ga_key . '=' . $ga_value;
					}
				}
			}

			$redirect_url = home_url( "/{$search_base}/" . $engine . urlencode( $search_query ) );

			if ( ! empty( $vars_to_maintain ) ) {
				$redirect_url .= '?' . implode( '&', $vars_to_maintain );
			}

			wp_redirect( $redirect_url );
			exit();
		}
	}

	/**
	 * Parse the Query and trigger a search
	 */
	public function pretty_search_parse_query( $query ) {
		if ( is_search() && ! is_admin() && $query->is_main_query() ) {
			$search_query = $query->get( 's' );
			$keyword_test = explode( '/', $search_query );

			if ( isset( $this->post_type_slugs[ $keyword_test[0] ] ) ) {
				$engine = $this->post_type_slugs[ $keyword_test[0] ];

				// check if our search preselects a facet.
				$s_query = $search_query;
				if ( isset( $keyword_test[1] ) ) {
					$s_query = $keyword_test[1];
				}
				$has_matching_facet = false;
				$s_query            = str_replace( ' ', '-', $s_query );
				$s_query            = str_replace( '+', '-', $s_query );
				$matching_facet     = $this->get_facet_name_by_value( $s_query );

				if ( isset( $this->options[ $engine ]['facets'] ) && ! empty( $this->options[ $engine ]['facets'] ) ) {
					$option_slug = 'search_';
					// Check if the matching term has an active facet displaying
					if ( array_key_exists( $matching_facet, $this->options[ $engine ]['facets'] ) ) {
						$has_matching_facet = true;
						$this->preselected_facet = array();
						$this->preselected_facet[ $matching_facet ] = array( $s_query );
					}
				}

				$query->set( 'post_type', $engine );
				$query->set( 'engine', $engine );

				if ( false === $has_matching_facet ) {
					if ( count( $keyword_test ) > 1 ) {
						$query->set( 's', $keyword_test[1] );
					} elseif ( post_type_exists( $engine ) ) {
						$query->set( 's', '' );
					}
				} else {
					$query->set( 's', '' );
				}
			} else {
				if ( isset( $this->options['general']['search_post_types'] ) && is_array( $this->options['general']['search_post_types'] ) ) {
					$post_types = array_keys( $this->options['general']['search_post_types'] );
					$query->set( 'post_type', $post_types );
				}
			}
		}

		return $query;
	}

	/**
	 * Change the search slug to /search/ for the JSON+LD output in Yoast SEO
	 *
	 * @return url
	 */
	public function change_json_ld_search_url() {
		return trailingslashit( home_url() ) . 'search/{search_term_string}';
	}

	/**
	 * A filter to set the layout to 2 column.
	 */
	public function lsx_to_search_archive_layout( $archive_layout, $settings_tab ) {
		if ( is_search() && false !== $this->options && isset( $this->options[ $settings_tab ]['enable_search'] ) && isset( $this->options[ $settings_tab ] ) ) {
			$archive_layout = $this->options[ $settings_tab ]['search_grid_list_layout'];
			if ( '' === $archive_layout ) {
				$archive_layout = 'list';
			}
		}
		return $archive_layout;
	}

	/**
	 * A filter to set the layout to 2 column.
	 */
	public function lsx_layout( $layout ) {
		global $wp_query;

		if ( false !== $this->search_slug /*&& count( $wp_query->posts ) > 0*/ ) {
			if ( is_search() ) {
				if ( 'search' === $this->search_slug ) {
					$slug = 'search';
					$id = 'search';
				} else {
					$slug = 'facets';
					$id = 'archive';
				}
			} else {
				$slug = 'facets';
				$id = 'archive';
			}

			if ( false !== $this->options && isset( $this->options[ $this->search_slug ][ 'enable_' . $slug ] ) && isset( $this->options[ $this->search_slug ][ $id . '_layout' ] ) && '' !== $this->options[ $this->search_slug ][ $id . '_layout' ] ) {
				$layout = $this->options[ $this->search_slug ][ $id . '_layout' ];
			}
		}

		return $layout;
	}

	/**
	 * Outputs the Search Title Facet
	 */
	public function search_sidebar_top() {
		if ( ! is_search() ) {
			$option_slug = 'archive_';
			if ( isset( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] ) ) {
				foreach ( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] as $facet => $facet_useless ) {
					if ( isset( $this->facet_data[ $facet ] ) && isset( $this->facet_data[ $facet ]['type'] ) && 'search' === $this->facet_data[ $facet ]['type'] ) {
						echo wp_kses_post( '<div class="row">' );
						$this->display_facet_default( $facet, false );
						echo wp_kses_post( '</div>' );
						unset( $this->options[ $this->search_slug ][ $option_slug . 'facets' ][ $facet ] );
					}
				}
			}
		} else {
			echo wp_kses_post( '<div class="row">' );
			$this->display_facet_search();
			echo wp_kses_post( '</div>' );
			$option_slug = 'search_';
			// unset any other search facets.
			if ( isset( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] ) ) {
				foreach ( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] as $facet => $facet_useless ) {
					if ( isset( $this->facet_data[ $facet ] ) && 'search' === $this->facet_data[ $facet ]['type'] ) {
						unset( $this->options[ $this->search_slug ][ $option_slug . 'facets' ][ $facet ] );
					}
				}
			}
		}
	}

	/**
	 * Change the primary and secondary column classes.
	 */
	public function lsx_layout_selector( $return_class, $class, $layout, $size ) {
		global $wp_query;

		if ( false !== $this->search_slug /*&& count( $wp_query->posts ) > 0*/ ) {
			if ( is_search() ) {
				$slug = 'search';
				$id = 'search';
			} else {
				$slug = 'facets';
				$id = 'archive';
			}

			if ( false !== $this->options && isset( $this->options[ $this->search_slug ][ 'enable_' . $slug ] ) && isset( $this->options[ $this->search_slug ][ $id . '_layout' ] ) && '' !== $this->options[ $this->search_slug ][ $id . '_layout' ] ) {
				$layout = $this->options[ $this->search_slug ][ $id . '_layout' ];

				if ( is_search() || ( is_post_type_archive( tour_operator()->get_active_post_types() ) ) || ( is_tax( array_keys( tour_operator()->get_taxonomies() ) ) ) ) {
					if ( '2cl' === $layout || '2cr' === $layout ) {
						$main_class    = 'col-sm-8 col-md-9';
						$sidebar_class = 'col-sm-4 col-md-3';

						if ( '2cl' === $layout ) {
							$main_class    .= ' col-sm-pull-4 col-md-pull-3';
							$sidebar_class .= ' col-sm-push-8 col-md-push-9';
						}

						if ( 'main' === $class ) {
							return $main_class;
						}

						if ( 'sidebar' === $class ) {
							return $sidebar_class;
						}
					}
				}
			}
		}

		return $return_class;
	}

	/**
	 * Shortcircuit the main search if need be
	 */
	public function searchwp_short_circuit( $maybe_short_circuit, $obj ) {
		$search_query = get_query_var( 's' );
		$engine = get_query_var( 'engine' );

		if ( false !== $engine && '' !== $engine && 'default' !== $engine ) {
			$maybe_short_circuit = true;
		}

		return $maybe_short_circuit;
	}

	/**
	 * Filters the travel style main query
	 */
	public function price_sorting( $query ) {
		$search_slug = false;
		$option_slug = false;

		if ( is_search() ) {
			$option_slug = '';
			$engine = get_query_var( 'engine' );

			if ( false !== $engine && 'default' !== $engine && '' !== $engine ) {
				$search_slug = $engine;
			} else {
				$search_slug = 'display';
			}
		} elseif ( is_post_type_archive( array_keys( $this->post_types ) ) || is_tax( array_keys( $this->taxonomies ) ) ) {
			$search_slug = get_post_type();
			$option_slug = 'archive_';
		}

		if ( ! is_admin() && $query->is_main_query() && false !== $search_slug && false !== $this->options ) {
			/*if (isset($this->options[$search_slug]['enable_search']) && 'on' === $this->options[$search_slug]['enable_search']) {

				$query->set('posts_per_page', -1);
				$query->set('nopaging', true);
			}*/

			if ( isset( $this->options[ $search_slug ][ 'enable_' . $option_slug . 'price_sorting' ] ) && 'on' === $this->options[ $search_slug ][ 'enable_' . $option_slug . 'price_sorting' ] ) {

				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'order', 'DESC' );
				$query->set( 'meta_key', 'price' );

				if ( isset( $_GET['sort'] ) ) {
					$query->set( 'order', ucwords( $_GET['sort'] ) );
				}
			}
		}

		return $query;
	}

	/**
	 * Outputs Search Sidebar.
	 */
	public function lsx_content_top() {
		if ( is_search() ) {
			$option_slug = '';
		} elseif ( is_post_type_archive( array_keys( $this->post_types ) ) || is_tax( array_keys( $this->taxonomies ) ) ) {
			$option_slug = 'archive_';
		}

		$show_map = false;

		if ( isset( $this->options[ $this->search_slug ][ $option_slug . 'layout_map' ] ) && ! empty( $this->options[ $this->search_slug ][ $option_slug . 'layout_map' ] ) ) {
			$show_map = true;
		}
		?>
		<?php do_action( 'lsx_to_search_top' ); ?>

		<div class="facetwp-template">
		<?php
		if ( true === $show_map ) {
			echo '<div class="tab-content">';
			echo '<div id="to-search-list" class="tab-pane fade in active">';
		}
	}

	/**
	 * Outputs Search Sidebar.
	 */
	public function lsx_content_bottom() {
		if ( is_search() ) {
			$option_slug = '';
		} elseif ( is_post_type_archive( array_keys( $this->post_types ) ) || is_tax( array_keys( $this->taxonomies ) ) ) {
			$option_slug = 'archive_';
		}

		$show_map = false;

		if ( isset( $this->options[ $this->search_slug ][ $option_slug . 'layout_map' ] ) && ! empty( $this->options[ $this->search_slug ][ $option_slug . 'layout_map' ] ) ) {
			$show_map = true;
		}

		if ( true === $show_map ) {
			echo '</div>';
			echo '<div id="to-search-map" class="tab-pane fade in">';
			$this->display_map();
			echo '</div>';
			echo '</div>';
		}
		?>
		</div>
		<?php //do_action( 'lsx_to_search_bottom' ); ?>
	<?php
	}

	/**
	 * Outputs Map.
	 */
	public function display_map() {
		global $wp_query;

		if ( count( $wp_query->posts ) > 0 ) {

			$map_query_args = $wp_query->query;
			$map_query_args['post_per_page'] = -1;
			$map_query_args['posts_per_archive_page'] = -1;
			$map_query_args['nopagin'] = true;
			$map_query_args['fields'] = 'ids';
			$map_query_args['suppress_filters'] = true;
			$map_query = new WP_Query( $map_query_args );

			if ( $map_query->have_posts() ) {
				$args = array(
					'connections' => $map_query->posts,
					'type' => 'cluster',
					'content' => 'excerpt',
				);
				echo wp_kses_post( tour_operator()->frontend->maps->map_output( false, $args ) );
			}
		}
	}

	/**
	 * Outputs Search Sidebar.
	 */
	public function search_sidebar() {
		global $wp_query;

		if ( false !== $this->search_slug /*&& count( $wp_query->posts ) > 0*/ ) {
			if ( is_search() ) {
				$option_slug = '';
			} elseif ( is_post_type_archive( array_keys( $this->post_types ) ) || is_tax( array_keys( $this->taxonomies ) ) ) {
				$option_slug = 'archive_';
			}
			?>
				<div id="secondary" class="facetwp-sidebar widget-area <?php echo esc_attr( lsx_sidebar_class() ); ?>" role="complementary">

					<div class="container-search">
						<?php do_action( 'lsx_search_sidebar_top' ); ?>
					</div>

					<?php if ( isset( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] ) && is_array( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] ) ) { ?>
						<div class="row container-facets facetwp-row to-search-filer-area">
							<h3 class="facetwp-filter-title"><?php echo esc_html_e( 'Refine by', 'lsx-search' ); ?></h3>
							<div class="col-xs-12 facetwp-item facetwp-filters-button hidden-sm hidden-md hidden-lg">
								<button class="ssm-toggle-nav btn btn-block filter-mobile" rel="to-search-filters"><?php esc_html_e( 'Filter (', 'to-search' ); ?><?php echo do_shortcode( '[facetwp counts="true"]' ); ?><?php esc_html_e( ') results', 'to-search' ); ?> <i class="fa fa-chevron-down" aria-hidden="true"></i></button>
							</div>
							<div class="ssm-overlay ssm-toggle-nav" rel="to-search-filters"></div>
							<div class="col-xs-12 facetwp-item-wrap facetwp-filters-wrap" rel="to-search-filters">
								<div class="row hidden-sm hidden-md hidden-lg ssm-row-margin-bottom">
									<div class="col-xs-12 facetwp-item facetwp-filters-button">
										<button class="ssm-close-btn ssm-toggle-nav btn btn-block" rel="to-search-filters"><?php esc_html_e( 'Close Filters', 'to-search' ); ?> <i class="fa fa-times" aria-hidden="true"></i></button>
									</div>
								</div>
								<div class="container-search-mobile hidden-sm hidden-md hidden-lg ssm-row-margin-bottom">
									<?php do_action( 'lsx_search_sidebar_top' ); ?>
								</div>
								
								<div class="row">
									<?php
										// Slider
										foreach ( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] as $facet => $facet_useless ) {
											if ( isset( $this->facet_data[ $facet ] ) && 'search_form' !== $facet && 'slider' === $this->facet_data[ $facet ]['type'] ) {
												$this->display_facet_default( $facet );
											}
										}
									?>
									<?php
										// Others
										foreach ( $this->options[ $this->search_slug ][ $option_slug . 'facets' ] as $facet => $facet_useless ) {
											if ( isset( $this->facet_data[ $facet ] ) && 'search_form' !== $facet && ! in_array( $this->facet_data[ $facet ]['type'], array( 'alpha', 'slider' ) ) ) {
												$this->display_facet_default( $facet );
											}
										}
									?>
								</div>
								<div class="row hidden-sm hidden-md hidden-lg ssm-row-margin-top">
									<div class="col-xs-12 facetwp-item facetwp-filters-button">
										<button class="ssm-apply-btn ssm-toggle-nav btn btn-block" rel="to-search-filters"><?php esc_html_e( 'Apply Filters', 'to-search' ); ?> <i class="fa fa-check" aria-hidden="true"></i></button>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>

			<?php
		}
	}

	/**
	 * Display facet search.
	 */
	public function display_facet_search() {
		?>
		<div class="col-xs-12 facetwp-item facetwp-form">
			<form class="search-form to-search-form" action="/" method="get">
				<div class="input-group">
					<div class="field">
						<input class="search-field form-control" name="s" type="search" placeholder="<?php esc_html_e( 'Search', 'to-search' ); ?>..." autocomplete="off" value="<?php echo get_search_query() ?>">
					</div>
					<div class="field submit-button">
						<button class="search-submit btn" type="submit"><?php esc_html_e( 'Search', 'to-search' ); ?></button>
					</div>
					<?php if ( 'display' !== $this->search_slug ) { ?>
						<input name="engine" type="hidden" value="<?php echo esc_attr( $this->search_slug ); ?>">
					<?php } ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Display facet default.
	 */
	public function display_facet_default( $facet, $display_title = true ) {
		if ( ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) ) {
			$continent_class = 'continent-visible';
		} else {
			$continent_class = 'no-continent-visible';
		}
		?>
		<?php
		global $lsx_to_search;
		$show_collapse        = ! isset( $lsx_to_search->options[ $lsx_to_search->search_slug ]['enable_collapse'] ) || 'on' !== $lsx_to_search->options[ $lsx_to_search->search_slug ]['enable_collapse'];
		$collapse_class       = '';
		$expanded_title       = 'false';
		if ( 0 === $this->facet_counter || false === $this->facet_counter ) {
			$collapse_class = 'in';
			$expanded_title = 'true';
		}
		?>
		<div class="col-xs-12 facetwp-item <?php echo esc_attr( $continent_class ); ?>">
			<?php if ( ( true === $display_title ) && ( ! $show_collapse ) ) { ?>
				<div class="facetwp-collapsed">
					<h3 class="lsx-to-search-title"><?php echo wp_kses_post( $this->facet_data[ $facet ]['label'] ); ?></h3>
					<button title="<?php echo esc_html_e( 'Click to Expand', 'to-search' ); ?>" class="facetwp-collapse" type="button" data-toggle="collapse" data-target="#collapse-<?php echo esc_html( $facet ); ?>" aria-expanded="<?php echo esc_attr( $expanded_title ); ?>" aria-controls="collapse-<?php echo esc_html( $facet ); ?>"></button>
				</div>
				<div id="collapse-<?php echo esc_html( $facet ); ?>" class="collapse <?php echo esc_attr( $collapse_class ); ?>">
					<?php echo do_shortcode( '[facetwp facet="' . $facet . '"]' ); ?>
				</div>
			<?php } elseif ( true === $display_title ) { ?>
				<h3 class="lsx-to-search-title"><?php echo wp_kses_post( $this->facet_data[ $facet ]['label'] ); ?></h3>
				<?php echo do_shortcode( '[facetwp facet="' . $facet . '"]' ); ?>
			<?php } else { ?>
				<?php echo do_shortcode( '[facetwp facet="' . $facet . '"]' ); ?>
			<?php } ?>
		</div>
		<?php
		$this->facet_counter++;
	}

	/**
	 * Outputs the appropriate search form
	 */
	public function search_form( $atts = array() ) {
		$classes = 'search-form to-search-form ';

		if ( isset( $atts['class'] ) ) {
			$classes .= $atts['class'];
		}

		$placeholder = __( 'Where do you want to go?', 'to-search' );

		if ( isset( $atts['placeholder'] ) ) {
			$placeholder = $atts['placeholder'];
		}

		$action = '/';

		if ( isset( $atts['action'] ) ) {
			$action = $atts['action'];
		}

		$method = 'get';

		if ( isset( $atts['method'] ) ) {
			$method = $atts['method'];
		}

		$button_label = __( 'Search', 'to-search' );

		if ( isset( $atts['button_label'] ) ) {
			$button_label = $atts['button_label'];
		}

		$button_class = 'btn cta-btn ';

		if ( isset( $atts['button_class'] ) ) {
			$button_class .= $atts['button_class'];
		}

		$engine = false;

		if ( isset( $atts['engine'] ) ) {
			$engine = $atts['engine'];
		}

		$engine_select = false;

		if ( isset( $atts['engine_select'] ) ) {
			$engine_select = true;
		}

		$display_search_field = true;

		if ( isset( $atts['search_field'] ) ) {
			$display_search_field = (boolean) $atts['search_field'];
		}

		$facets = false;

		if ( isset( $atts['facets'] ) ) {
			$facets = $atts['facets'];
		}

		$combo_box = false;

		if ( isset( $atts['combo_box'] ) ) {
			$combo_box = true;
		}

		$return = '';

		ob_start(); ?>

			<?php do_action( 'lsx_search_form_before' ); ?>

			<form class="<?php echo esc_attr( $classes ); ?>" action="<?php echo esc_attr( $action ); ?>" method="<?php echo esc_attr( $method ); ?>">

				<?php do_action( 'lsx_search_form_top' ); ?>

				<div class="input-group">
					<?php if ( true === $display_search_field ) : ?>
						<div class="field">
							<input class="search-field form-control" name="s" type="search" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off">
						</div>
					<?php endif; ?>

					<?php if ( false !== $engine_select && false !== $engine && 'default' !== $engine ) :
						$engines = explode( '|',$engine ); ?>
						<div class="field engine-select">
							<div class="dropdown">
								<?php
									$plural = 's';
									if ( 'accommodation' === $engine[0] ) {
										$plural = '';
									}
								?>
								<button id="engine" data-selection="<?php echo esc_attr( $engines[0] ); ?>" class="btn border-btn btn-dropdown dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo esc_html( ucwords( str_replace( '_', ' ',$engines[0] ) ) . $plural ); ?> <span class="caret"></span></button>
								<ul class="dropdown-menu">
									<?php
									foreach ( $engines as $engine ) {
										$plural = 's';
										if ( 'accommodation' === $engine ) {
											$plural = '';
										}
										echo '<li><a data-value="' . esc_attr( $engine ) . '" href="#">' . esc_html( ucfirst( str_replace( '_', ' ',$engine ) ) . $plural ) . '</a></li>';
									}
									?>
								</ul>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( false !== $facets ) {
						$facets = explode( '|',$facets );

						if ( ! is_array( $facets ) ) {
							$facets = array( $facets );
						}

						$field_class = 'field';

						if ( false !== $combo_box ) {
							$this->combo_box( $facets );
							$field_class .= ' combination-toggle hidden';
						}

						foreach ( $facets as $facet ) {
							?>
							<div class="<?php echo wp_kses_post( $field_class ); ?>">
								<?php
									$facet = FWP()->helper->get_facet_by_name( $facet );
									$values = $this->get_form_facet( $facet['name'] );
									$this->display_form_field( 'select',$facet,$values,$combo_box );
								?>
							</div>
							<?php
						}
					} ?>

					<div class="field submit-button">
						<button class="<?php echo esc_attr( $button_class ); ?>" type="submit"><?php echo wp_kses_post( $button_label ); ?></button>
					</div>

					<?php if ( false === $engine_select && false !== $engine && 'default' !== $engine ) : ?>
						<input name="engine" type="hidden" value="<?php echo esc_attr( $engine ); ?>">
					<?php endif; ?>
				</div>

				<?php do_action( 'lsx_search_form_bottom' ); ?>

			</form>

			<?php do_action( 'lsx_search_form_after' ); ?>
		<?php
		$return = ob_get_clean();

		$return = preg_replace( '/[\n]+/', ' ', $return );
		$return = preg_replace( '/[\t]+/', ' ', $return );

		return $return;
	}

	/**
	 * Outputs bottom.
	 */
	public function facet_bottom_bar() {

		if ( is_search() ) {
			$option_slug = '';
		} elseif ( is_post_type_archive( array_keys( $this->post_types ) ) || is_tax( array_keys( $this->taxonomies ) ) ) {
			$option_slug = 'archive_';
		} else {
			return '';
		}
		$pagination_visible  = false;

		$show_pagination     = ! isset( $this->options[ $this->search_slug ][ 'disable_' . $option_slug . 'pagination' ] ) || 'on' !== $this->options[ $this->search_slug ][ 'disable_' . $option_slug . 'pagination' ];

		$show_sort_combo     = ! isset( $this->options[ $this->search_slug ][ 'disable_' . $option_slug . 'all_sorting' ] ) || 'on' !== $this->options[ $this->search_slug ][ 'disable_' . $option_slug . 'all_sorting' ];
		$az_pagination       = $this->options[ $this->search_slug ][ $option_slug . 'az_pagination' ];

		$show_pagination     = apply_filters( 'lsx_to_search_bottom_show_pagination', $show_pagination );
		$pagination_visible  = apply_filters( 'lsx_to_search_bottom_pagination_visible', $pagination_visible );

		if ( $show_pagination || ! empty( $az_pagination ) ) {
			?>
			<div id="facetwp-bottom">
				<div class="row facetwp-bottom-row-1">
					<div class="col-xs-12">
						<?php do_action( 'lsx_search_facetwp_bottom_row' ); ?>

						<?php if ( $show_pagination ) { ?>
							<?php echo do_shortcode( '[facetwp pager="true"]' ); ?>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php
		}

	}

	/**
	 * Outputs closing facetWP div.
	 */
	public function lsx_sidebar_enable( $return ) {
		global $wp_query;

		if ( false !== $this->search_slug /*&& count( $wp_query->posts ) > 0*/ ) {
			$return = 0;
		}

		return $return;
	}

	/**
	 * Grabs the Values for the Facet in Question.
	 */
	protected function get_form_facet( $facet_source = false ) {
		global $wpdb;

		$values = array();
		$select = 'f.facet_value, f.facet_display_value';
		$from = "{$wpdb->prefix}facetwp_index f";
		$where = "f.facet_name = '{$facet_source}'";

		//Check if the current facet is showing destinations.
		if ( stripos( $facet_source, 'destination_to' ) ) {
			$from .= " INNER JOIN {$wpdb->posts} p ON f.facet_value = p.ID";
			$where .= " AND p.post_parent = '0'";

		}
		$response = $wpdb->get_results( "SELECT {$select} FROM {$from} WHERE {$where}" ); // WPCS: unprepared SQL OK.
		if ( ! empty( $response ) ) {
			foreach ( $response as $re ) {
				$values[ $re->facet_value ] = $re->facet_display_value;
			}
		}

		asort( $values );
		return $values;
	}


	/**
	 * Change FaceWP pagination HTML to be equal main pagination (WP-PageNavi)
	 */
	public function display_form_field( $type = 'select', $facet = array(), $values = array(), $combo = false ) {
		if ( empty( $facet ) ) {
			return;
		}

		$source = 'fwp_' . $facet['name'];

		switch ( $type ) {

			case 'select':?>
				<div class="dropdown <?php if ( true === $combo ) { echo 'combination-dropdown'; } ?>">
					<button data-selection="0" class="btn border-btn btn-dropdown dropdown-toggle" type="button" id="<?php echo wp_kses_post( $source ); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<?php esc_attr_e( 'Select', 'to-search' ); ?> <?php echo wp_kses_post( $facet['label'] ); ?>
						<span class="caret"></span>
					</button>
					<ul class="dropdown-menu" aria-labelledby="<?php echo wp_kses_post( $source ); ?>">
						<?php if ( ! empty( $values ) ) { ?>

							<li style="display: none;"><a class="default" data-value="0" href="#"><?php esc_attr_e( 'Select ', 'to-search' ); ?> <?php echo wp_kses_post( $facet['label'] ); ?></a></li>

							<?php foreach ( $values as $key => $value ) { ?>
								<li><a data-value="<?php echo wp_kses_post( $key ); ?>" href="#"><?php echo wp_kses_post( $value ); ?></a></li>
							<?php } ?>
						<?php } else { ?>
							<li><a data-value="0" href="#"><?php esc_attr_e( 'Please re-index your facets.', 'to-search' ); ?></a></li>
						<?php } ?>
					</ul>
				</div>
			<?php
				break;
		}

		?>

	<?php }

	/**
	 * Outputs the combination selector
	 */
	public function combo_box( $facets ) {
		?>
		<div class="field combination-dropdown">
			<div class="dropdown">
				<button data-selection="0" class="btn border-btn btn-dropdown dropdown-toggle btn-combination" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
					<?php esc_attr_e( 'Select', 'to-search' ); ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu">

					<li style="display: none;"><a class="default" data-value="0" href="#"><?php esc_attr_e( 'Select ', 'to-search' ); ?></a></li>

					<?php foreach ( $facets as $facet ) {
						$facet = FWP()->helper->get_facet_by_name( $facet );
						?>
						<li><a data-value="fwp_<?php echo wp_kses_post( $facet['name'] ); ?>" href="#"><?php echo wp_kses_post( $facet['label'] ); ?></a></li>
					<?php } ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Change FaceWP result count HTML
	 */
	public function get_search_query( $keyword ) {
		$engine = get_query_var( 'engine_keyword' );

		if ( false !== $engine && '' !== $engine && 'default' !== $engine ) {
			$keyword = $engine;
		}

		$keyword = str_replace( '+', ' ', $keyword );
		return $keyword;
	}

	/**
	 * Overrides the search facet HTML
	 * @param $output
	 * @param $params
	 *
	 * @return string
	 */
	public function search_facet_html( $output, $params ) {
		if ( 'search' == $params['facet']['type'] ) {

			$value = (array) $params['selected_values'];
			$value = empty( $value ) ? '' : stripslashes( $value[0] );
			$placeholder = isset( $params['facet']['placeholder'] ) ? $params['facet']['placeholder'] : __( 'Search...', 'lsx-search' );
			$placeholder = facetwp_i18n( $placeholder );

			ob_start();
			?>
			<div class="col-xs-12 facetwp-item facetwp-form">
				<div class="search-form to-search-form">
					<div class="input-group facetwp-search-wrap">
						<div class="field">
							<input class="facetwp-search search-field form-control" type="text" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" value="<?php echo esc_attr( $value ); ?>">
						</div>

						<div class="field submit-button">
							<button class="search-submit btn facetwp-btn" type="submit"><?php esc_html_e( 'Search2', 'lsx-search' ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php
			$output = ob_get_clean();
		}
		return $output;
	}

	/**
	 * Add an additional class to body if is tours, destinations or accommodation search.
	 *
	 * @param [type] $classes
	 * @return $classes
	 */
	public function to_add_search_url_class( $classes ) {
		global $wp;
		$url_search_path = add_query_arg( $wp->query_vars );
		if ( strpos( $url_search_path, '/search/tours/' ) !== false ) {
			$classes[] = 'tours-search-page';
		} elseif ( strpos( $url_search_path, '/search/accommodation/' ) !== false ) {
			$classes[] = 'accommodation-search-page';
		} elseif ( strpos( $url_search_path, '/search/destinations/' ) !== false ) {
			$classes[] = 'destinations-search-page';
		}
		if ( is_search() ) {
			$classes[] = 'search-results';
			$key = array_search( 'search-no-results', $classes );
			if ( false !== $key ) {
				unset( $classes[ $key ] );
			}
		}
		return $classes;
	}

	/**
	 * Checks for a preselcted facet and preselects them.
	 * @param $url_vars
	 *
	 * @return mixed
	 */
	public function preload_url_vars( $url_vars ) {
		/*if ( strpos( FWP()->helper->get_uri(), 'search/' ) !== false ) {
			$url_vars['fwp_types'] = array( 'lodge' );
			if ( empty( $url_vars['fwp_content_type'] ) && ! isset( $_GET['fwp_content_type'] ) ) {
				$url_vars['content_type'] = array( 'post' );
			}
		}*/
		return $url_vars;
	}

}

global $lsx_to_search;
$lsx_to_search = new LSX_TO_Search_Frontend();
