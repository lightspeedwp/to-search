<?php
/**
 * LSX_TO_Search Frontend Main Class
 */

class LSX_TO_Search_FacetWP extends LSX_TO_Search {

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
		add_action( 'init', array( $this, 'set_vars' ) );
		add_action( 'init', array( $this, 'set_facetwp_vars' ) );

		add_filter( 'facetwp_indexer_row_data', array( $this, 'facetwp_index_row_data' ), 10, 2 );
		add_filter( 'facetwp_index_row', array( $this, 'facetwp_index_row' ), 10, 2 );

		add_filter( 'facetwp_sort_options', array( $this, 'facet_sort_options' ), 10, 2 );

		add_filter( 'facetwp_pager_html', array( $this, 'facetwp_pager_html' ), 10, 2 );
		add_filter( 'facetwp_result_count', array( $this, 'facetwp_result_count' ), 10, 2 );

		add_filter( 'facetwp_facet_html', array( $this, 'destination_facet_html' ), 10, 2 );
		add_filter( 'facetwp_facet_html', array( $this, 'slide_facet_html' ), 10, 2 );
		add_filter( 'facetwp_facet_html', array( $this, 'search_facet_html' ), 10, 2 );
		add_filter( 'facetwp_load_css', array( $this, 'facetwp_load_css' ), 10, 1 );

		if ( class_exists( 'LSX_Currencies' ) ) {
			add_filter( 'facetwp_render_output', array( $this, 'slide_price_lsx_currencies' ), 10, 2 );
		} else {
			add_filter( 'facetwp_render_output', array( $this, 'slide_price_to_currencies' ), 10, 2 );
		}
	}

	/**
	 *	Alter the rows and include extra facets rows for the continents
	 */
	function facetwp_index_row_data( $rows, $params ) {
		switch ( $params['facet']['source'] ) {
			case 'cf/destination_to_tour':
			case 'cf/destination_to_accommodation':
				$countries = array();

				foreach ( $rows as $r_index => $row ) {

					$parent = wp_get_post_parent_id( $row['facet_value'] );
					$rows[ $r_index ]['parent_id'] = $parent;

					if ( 0 === $parent || '0' === $parent ) {
						if ( ! isset( $countries[ $r_index ] ) ) {
							$countries[ $r_index ] = $row['facet_value'];
						}

						if ( ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) ) {
							$rows[ $r_index ]['depth'] = 1;
						} else {
							$rows[ $r_index ]['depth'] = 0;
						}
					} else {
						if ( ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) ) {
							$rows[ $r_index ]['depth'] = 2;
						} else {
							$rows[ $r_index ]['depth'] = 1;
						}
					}
				}
				if ( ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) ) {
					if ( ! empty( $countries ) ) {
						foreach ( $countries as $row_index => $country ) {
							$continents = wp_get_object_terms( $country, 'continent' );
							$continent_id = 0;

							if ( ! is_wp_error( $continents ) ) {
								$new_row = $params['defaults'];
								if ( ! is_array( $continents ) ) {
									$continents = array( $continents );
								}

								foreach ( $continents as $continent ) {
									$new_row['facet_value'] = $continent->slug;
									$new_row['facet_display_value'] = $continent->name;
									$continent_id = $continent->term_id;
									$new_row['depth'] = 0;
								}
								$rows[] = $new_row;
								$rows[ $row_index ]['parent_id'] = $continent_id;
							}
						}
					}
				}

				break;

			default:
				break;
		}

		return $rows;
	}

	/**
	 * Displays the destination specific settings
	 */
	public function facetwp_index_row( $params, $class ) {
		$custom_field = false;
		$meta_key = false;

		preg_match( '/cf\//', $class->facet['source'], $custom_field );
		preg_match( '/_to_/', $class->facet['source'], $meta_key );

		if ( ! empty( $custom_field ) && ! empty( $meta_key ) ) {

			if ( ( 'cf/destination_to_accommodation' === $class->facet['source'] || 'cf/destination_to_tour' === $class->facet['source'] ) && ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) && ( '0' === (string) $params['depth'] ) ) {
				$title = '';
			} else {
				$title = get_the_title( $params['facet_value'] );
				if ( '' !== $title ) {
					$params['facet_display_value'] = $title;
				}
				if ( '' === $title && ! empty( $meta_key ) ) {
					$params['facet_value'] = '';
				}
			}
		}

		// If its a price, save the value as a standard number.
		if ( 'cf/price' === $class->facet['source'] ) {
			$params['facet_value'] = preg_replace( '/[^0-9.]/', '', $params['facet_value'] );
			$params['facet_value'] = ltrim( $params['facet_value'], '.' );
			#$params['facet_value'] = number_format( (int) $params['facet_value'], 2 );
			$params['facet_display_value'] = $params['facet_value'];
		}

		// If its a duration, save the value as a standard number.
		if ( 'cf/duration' === $class->facet['source'] ) {
			$params['facet_value'] = preg_replace( '/[^0-9 ]/', '', $params['facet_value'] );
			$params['facet_value'] = trim( $params['facet_value'] );
			$params['facet_value'] = explode( ' ', $params['facet_value'] );
			$params['facet_value'] = $params['facet_value'][0];
			#$params['facet_value'] = (int) $params['facet_value'];
			$params['facet_display_value'] = $params['facet_value'];
		}

		return $params;
	}

	/**
	 * Register the global post types.
	 *
	 * @return    null
	 */
	public function facet_sort_options( $options, $params ) {
		global $wp_query;

		//unset($options['distance']);

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
			$obj = get_queried_object();
			if ( isset( $obj->name ) && (in_array( $obj->name,array_keys( $this->post_types ) ) || in_array( $obj->name,array_keys( $this->taxonomies ) )) ) {
				$search_slug = $obj->name;
				$option_slug = 'archive_';
			}
		}

		if ( ( 'default' === $params['template_name'] || 'wp' === $params['template_name'] )
			&& false !== $search_slug && false !== $this->options && isset( $this->options[ $search_slug ][ 'disable_' . $option_slug . 'price_sorting' ] )
			&& 'on' === $this->options[ $search_slug ][ 'disable_' . $option_slug . 'price_sorting' ] ) {
					null;
			// Do nothing

		} elseif ( 'tour' === $search_slug || 'accommodation' === $search_slug || 'display' === $search_slug ) {
			$options['price_asc'] = array(
				'label' => __( 'Price (Highest)', 'lsx' ),
				'query_args' => array(
					'orderby' => 'meta_value_num',
					'meta_key' => 'price',
					'order' => 'DESC',
				),
			);

			$options['price_desc'] = array(
				'label' => __( 'Price (Lowest)', 'lsx' ),
				'query_args' => array(
					'orderby' => 'meta_value_num',
					'meta_key' => 'price',
					'order' => 'ASC',
				),
			);
		}

		if ( ( 'default' === $params['template_name'] || 'wp' === $params['template_name'] )
			&& false !== $search_slug && false !== $this->options && isset( $this->options[ $search_slug ][ 'disable_' . $option_slug . 'date_sorting' ] )
			&& 'on' === $this->options[ $search_slug ][ 'disable_' . $option_slug . 'date_sorting' ] ) {

			unset( $options['date_desc'] );
			unset( $options['date_asc'] );
		}

		if ( ( 'default' === $params['template_name'] || 'wp' === $params['template_name'] )
			&& false !== $search_slug && false !== $this->options && isset( $this->options[ $search_slug ][ 'disable_' . $option_slug . 'az_sorting' ] )
			&& 'on' === $this->options[ $search_slug ][ 'disable_' . $option_slug . 'az_sorting' ] ) {

			unset( $options['title_desc'] );
			unset( $options['title_asc'] );
		}

		return $options;
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
						<input class="search-field form-control" name="s" type="search" placeholder="<?php esc_html_e( 'Search', 'to-search' ); ?>..." autocomplete="off" value="<?php echo get_search_query(); ?>">
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
	public function display_facet_default( $facet ) {
		?>
		<div class="col-xs-12 facetwp-item">
			<h3 class="lsx-to-search-title"><?php echo wp_kses_post( $this->facet_data[ $facet ]['label'] ); ?></h3>
			<?php echo do_shortcode( '[facetwp facet="' . $facet . '"]' ); ?>
		</div>
		<?php
	}

	/**
	 * Change FaceWP pagination HTML to be equal main pagination (WP-PageNavi)
	 */
	public function facetwp_pager_html( $output, $params ) {
		$output = '';
		$page = (int) $params['page'];
		$per_page = (int) $params['per_page'];
		$total_pages = (int) $params['total_pages'];

		if ( 1 < $total_pages ) {
			$output .= '<div class="lsx-pagination-wrapper facetwp-custom">';
			$output .= '<div class="lsx-pagination">';
			// $output .= '<span class="pages">Page '. $page .' of '. $total_pages .'</span>';

			if ( 1 < $page ) {
				$output .= '<a class="prev page-numbers facetwp-page" rel="prev" data-page="' . ( $page - 1 ) . '">«</a>';
			}

			$temp = false;

			for ( $i = 1; $i <= $total_pages; $i++ ) {
				if ( $i == $page ) {
					$output .= '<span class="page-numbers current">' . $i . '</span>';
				} elseif ( ( $page - 2 ) < $i && ( $page + 2 ) > $i ) {
					$output .= '<a class="page-numbers facetwp-page" data-page="' . $i . '">' . $i . '</a>';
				} elseif ( ( $page - 2 ) >= $i && $page > 2 ) {
					if ( ! $temp ) {
						$output .= '<span class="page-numbers dots">...</span>';
						$temp = true;
					}
				} elseif ( ( $page + 2 ) <= $i && ( $page + 2 ) <= $total_pages ) {
					$output .= '<span class="page-numbers dots">...</span>';
					break;
				}
			}

			if ( $page < $total_pages ) {
				$output .= '<a class="next page-numbers facetwp-page" rel="next" data-page="' . ( $page + 1 ) . '">»</a>';
			}

			$output .= '</div>';
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Change FaceWP result count HTML
	 */
	public function facetwp_result_count( $output, $params ) {
		$output = $params['total'];
		return $output;
	}

	/**
	 * Checks the facet source value and outputs the destination facet HTML if needed.
	 *
	 * @param  string  $output
	 * @param  array   $params
	 * @return string
	 */
	public function destination_facet_html( $output, $params ) {
		$possible_keys = array(
			'cf/destination_to_accommodation',
			'cf/destination_to_tour',
			'cf/destination_to_special',
			'cf/destination_to_activity',
			'cf/destination_to_review',
			'cf/destination_to_vehicle',
		);
		if ( in_array( $params['facet']['source'], $possible_keys ) ) {
			$output = $this->destination_facet_render( $params );
		}
		return $output;
	}

	/**
	 * Generate the facet HTML
	 */
	public function destination_facet_render( $params ) {
		$facet = $params['facet'];

		$output = '';
		$values = (array) $params['values'];
		$selected_values = (array) $params['selected_values'];
		$soft_limit = empty( $facet['soft_limit'] ) ? 0 : (int) $facet['soft_limit'];
		$countries = array();
		$continents = array();

		$continent_terms = get_terms(
			array(
				'taxonomy' => 'continent',
			)
		);

		if ( ! is_wp_error( $continent_terms ) ) {
			foreach ( $continent_terms as $continent ) {
				$continents[ $continent->term_id ] = $continent->slug;
			}
		}

		//Create a relationship of the facet value and the their depths
		$depths = array();
		$parents = array();
		foreach ( $values as $value ) {
			$depths[ $value['facet_value'] ] = (int) $value['depth'];
			$parents[ $value['facet_value'] ] = (int) $value['parent_id'];
		}

		//Determine the current depth and check if the selected values parents are in the selected array.
		$current_depth = 0;
		$additional_values = array();
		if ( ! empty( $selected_values ) ) {
			foreach ( $selected_values as $selected ) {
				if ( $depths[ $selected ] > $current_depth ) {
					$current_depth = $depths[ $selected ];
				}
			}
			$current_depth++;
		}

		if ( ! empty( $additional_values ) ) {
			$selected_values = array_merge( $selected_values, $additional_values );
		}

		// This is where the items are sorted by their depth
		$sorted_values = array();
		$stored = $values;

		//sort the options so
		foreach ( $values as $key => $result ) {
			if ( ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) ) {
				if ( in_array( $result['facet_value'], $continents ) ) {
					$sorted_values[] = $result;
					$destinations = $this->get_countries( $stored, $result['facet_value'], $continents, '1' );

					if ( ! empty( $destinations ) ) {
						foreach ( $destinations as $destination ) {
							$sorted_values[] = $destination;
						}
					}
				}
			} else {
				if ( '0' === $result['depth'] || 0 === $result['depth'] ) {
					$sorted_values[] = $result;
					$destinations = $this->get_regions( $stored, $result['facet_value'], '1' );

					if ( ! empty( $destinations ) ) {
						foreach ( $destinations as $destination ) {
							$sorted_values[] = $destination;
						}
					}
				}
			}
		}
		$values = $sorted_values;

		$continent_class = '';
		$country_class = '';

		// Run through each value and output the values.
		foreach ( $values as $key => $facet ) {
			$depth_type = '';

			if ( ! empty( tour_operator()->options['display']['enable_search_continent_filter'] ) ) {
				switch ( $facet['depth'] ) {
					case '0':
						$depth_type = '';
						$continent_class = in_array( $facet['facet_value'], $selected_values ) ? $depth_type .= ' continent-checked' : '';
						break;

					case '1':
						$depth_type = 'country' . $continent_class;
						$country_class = in_array( $facet['facet_value'], $selected_values ) ? $depth_type .= ' country-checked' : '';
						break;

					case '2':
						$depth_type = 'region' . $continent_class . $country_class;
						break;
				}
			} else {
				switch ( $facet['depth'] ) {
					case '0':
						$depth_type = 'country continent-checked';
						$country_class = in_array( $facet['facet_value'], $selected_values ) ? $depth_type .= ' country-checked' : '';
						break;

					case '1':
						$depth_type = 'region continent-checked' . $country_class;
						break;
				}
			}

			if ( $facet['depth'] <= $current_depth ) {
				$options[] = $this->format_single_facet( $key, $facet, $selected_values, $depth_type );
			}
		}

		if ( ! empty( $options ) ) {
			$output = implode( '', $options );
		}

		return $output;
	}

	/**
	 * Gets the direct countries from the array.
	 */
	public function get_countries( $values, $parent, $continents, $depth ) {
		$children = array();
		$stored = $values;

		foreach ( $values as $value ) {
			if ( isset( $continents[ $value['parent_id'] ] ) && $continents[ $value['parent_id'] ] === $parent && $value['depth'] === $depth ) {
				$children[] = $value;

				$destinations = $this->get_regions( $stored, $value['facet_value'], '2' );
				if ( ! empty( $destinations ) ) {
					foreach ( $destinations as $destination ) {
						$children[] = $destination;
					}
				}
			}
		}
		return $children;
	}

	/**
	 * Gets the direct regions from the array.
	 */
	public function get_regions( $values, $parent, $depth ) {
		$children = array();
		foreach ( $values as $value ) {
			if ( $value['parent_id'] === $parent && $value['depth'] === $depth ) {
				$children[] = $value;
			}
		}
		return $children;
	}

	public function format_single_facet( $key, $result, $selected_values, $region = '' ) {
		$temp_html = '';

		$selected = in_array( $result['facet_value'], $selected_values ) ? ' checked' : '';
		$selected .= ( 0 == $result['counter'] && '' == $selected ) ? ' disabled' : '';
		$selected .= ' ' . $region;

		$temp_html .= '<div class="facetwp-checkbox' . $selected . '" data-value="' . $result['facet_value'] . '">';
		$temp_html .= $result['facet_display_value'] . ' <span class="facetwp-counter">(' . $result['counter'] . ')</span>';
		$temp_html .= '</div>';

		return $temp_html;
	}

	public function slide_facet_html( $html, $args ) {
		if ( 'slider' === $args['facet']['type'] ) {
			$html = str_replace( 'class="facetwp-slider-reset"', 'class="btn btn-md facetwp-slider-reset"', $html );
		}

		return $html;
	}

	public function facetwp_load_css( $boolean ) {
		$boolean = false;
		return $boolean;
	}

	public function slide_price_lsx_currencies( $output, $params ) {
		// if ( ! empty( $output['settings']['price'] ) ) {
		// 	// @TODO
		// }

		return $output;
	}

	public function slide_price_to_currencies( $output, $params ) {
		if ( ! empty( $output['settings']['price'] ) ) {
			$currency = '';

			if ( ! empty( tour_operator()->options['general'] ) && is_array( tour_operator()->options['general'] ) ) {
				if ( ! empty( tour_operator()->options['general']['currency'] ) ) {
					$currency = tour_operator()->options['general']['currency'];
					$currency_symbol = apply_filters( 'lsx_to_search_slider_currency', $currency );
					$currency = '<span class="currency-icon ' . mb_strtolower( $currency ) . '">' . $currency_symbol . '</span>';
				}
			}

			if ( ! empty( $currency ) ) {
				$output['settings']['price']['prefix'] = $currency . '<span>';
				$output['settings']['price']['suffix'] = '</span>';
			}
		}

		return $output;
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
							<button class="search-submit btn facetwp-btn" type="submit"><?php esc_html_e( 'Search', 'lsx-search' ); ?></button>
						</div>
					</div>
				</div>
			</div>
			<?php
			$output = ob_get_clean();
		}
		return $output;
	}
}

global $lsx_to_search_fwp;
$lsx_to_search_fwp = new LSX_TO_Search_FacetWP();
