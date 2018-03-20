<?php
/**
 * Class TO_Search_Destination_Facet
 *
 * This facet Combines the continent country and the region
 */

/**
 * FacetWP registration hook
 */
function fwp_destination_facet( $facet_types ) {
	$facet_types['destinations'] = new TO_Search_Destination_Facet();
	return $facet_types;
}


class TO_Search_Destination_Facet {
	function __construct() {
		$this->label = __( 'Destinations', 'fwp' );
	}

	/**
	 * Load the available choices
	 */
	function load_values( $params ) {
		global $wpdb;

		$facet = $params['facet'];
		$from_clause = $wpdb->prefix . 'facetwp_index f';
		$where_clause = $params['where_clause'];

		// Count setting.
		$limit = ctype_digit( $facet['count'] ) ? $facet['count'] : 10;

		$from_clause = apply_filters( 'facetwp_facet_from', $from_clause, $facet );
		$where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

		$sql = "
		SELECT f.facet_value, f.facet_display_value, f.term_id, f.parent_id, f.depth, COUNT(DISTINCT f.post_id) AS counter
		FROM $from_clause
		WHERE f.facet_name = '{$facet['name']}' $where_clause
		GROUP BY f.facet_value
		ORDER BY f.depth, counter DESC, f.facet_display_value ASC
		LIMIT $limit";

		return $wpdb->get_results( $sql, ARRAY_A ); // WPCS: unprepared SQL OK.
	}


	/**
	 * Generate the facet HTML
	*/
	function render( $params ) {
		global $wpdb;

		$facet = $params['facet'];
		$from_clause = $wpdb->prefix . 'facetwp_index f';
		$where_clause = $params['where_clause'];

		$selected_values = (array) $params['selected_values'];

		// Orderby
		$orderby = 'counter DESC, f.facet_display_value ASC';
		if ( 'display_value' == $facet['orderby'] ) {
			$orderby = 'f.facet_display_value ASC';
		} elseif ( 'raw_value' == $facet['orderby'] ) {
			$orderby = 'f.facet_value ASC';
		}

		// Visible results.
		$num_visible = ctype_digit( $facet['count'] ) ? $facet['count'] : 10;

		$max_depth = 0;
		$facet_parent_id = 0;

		// Determine the parent_id and depth
		/* if ( ! empty( $selected_values[0] ) ) {

			$value = $selected_values[0];
			$customfield = str_replace( 'cf/', '', $facet['source'] );

			// Associate array of term IDs with term information
			$depths = $this->get_depths( $customfield );

			// Lookup the term ID from its slug
			$sql = "
			SELECT t.ID
			FROM {$wpdb->posts} t
			WHERE t.post_name = %s
			LIMIT 1";
			$facet_parent_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $customfield, $value ) );

			$max_depth = (int) $depths[ $facet_parent_id ]['depth'];
			$last_parent_id = $facet_parent_id;

			$prev_links = array();
			for ( $i = 0; $i <= $max_depth; $i++ ) {
				$prev_links[] = array(
					'value' => $depths[ $last_parent_id ]['slug'],
					'label' => $depths[ $last_parent_id ]['name'],
				);
				$last_parent_id = (int) $depths[ $last_parent_id ]['parent_id'];
			}

			$prev_links[] = array(
				'value' => '',
				'label' => __( 'Any', 'fwp' ),
			);

			// Reverse the navigation
			$prev_links = array_reverse( $prev_links );
			$num_links = count( $prev_links );

			foreach ( $prev_links as $counter => $prev_link ) {
				if ( $counter == ( $num_links - 1 ) ) {
					$active = ' checked';
				}
				else {
					$active = '';
					$prev_link['label'] = '&#8249; ' . $prev_link['label'];
				}
				if ( 0 < $counter ) {
					$output .= '<div class="facetwp-depth">';
				}
				$output .= '<div class="facetwp-link' . $active . '" data-value="' . $prev_link['value'] . '">' . $prev_link['label'] . '</div>';
			}
		}*/

		// Update the WHERE clause
		$where_clause .= " AND parent_id = '$facet_parent_id'";

		$orderby = apply_filters( 'facetwp_facet_orderby', $orderby, $facet );
		$from_clause = apply_filters( 'facetwp_facet_from', $from_clause, $facet );
		$where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

		$sql = "
		SELECT f.facet_value, f.facet_display_value, COUNT(*) AS counter
		FROM $from_clause
		WHERE f.facet_name = '{$facet['name']}' $where_clause
		GROUP BY f.facet_value
		ORDER BY $orderby";

		$results = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.

		$output .= print_r( $results, true );

		$key = 0;

		if ( ! empty( $prev_links ) ) {
			$output .= '<div class="facetwp-depth">';
		}

		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $result ) {
				if ( $key == (int) $num_visible ) {
					$output .= '<div class="facetwp-overflow facetwp-hidden">';
				}
				$output .= '<div class="facetwp-link" data-value="' . $result->facet_value . '">';
				$output .= $result->facet_display_value . ' <span class="facetwp-counter">(' . $result->counter . ')</span>';
				$output .= '</div>';
			}
		}

		if ( $num_visible <= $key ) {
			$output .= '</div>';
			$output .= '<a class="facetwp-toggle">' . __( 'See more', 'fwp' ) . '</a>';
			$output .= '<a class="facetwp-toggle facetwp-hidden">' . __( 'See less', 'fwp' ) . '</a>';
		}

		for ( $i = 0; $i <= $max_depth; $i++ ) {
			$output .= '</div>';
		}

		if ( ! empty( $prev_links ) ) {
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get an array of term information, including depth
	 * @param string $taxonomy The taxonomy name
	 * @return array Term information
	 * @since 0.9.0
	 */
	function get_depths( $taxonomy ) {

		$output = array();
		$parents = array();

		$destinations = $this->get_destinations();
		if ( empty( $destinations ) ) {
			return $output;
		}

		// Get term parents
		foreach ( $destinations as $destination ) {
			$parents[ $destination->ID ] = $destination->post_parent;
		}

		// Build the term array
		foreach ( $destinations as $destination ) {
			$output[ $destination->ID ] = array(
				'ID'       => $destination->ID,
				'name'          => $destination->post_title,
				'slug'          => $destination->post_name,
				'parent_id'     => $destination->post_parent,
				'depth'         => 0,
			);

			$current_parent = $destination->post_parent;
			while ( 0 < (int) $current_parent ) {
				$current_parent = $parents[ $current_parent ];
				$output[ $destination->ID ]['depth']++;

				// Prevent an infinite loop
				if ( 50 < $output[ $destination->ID ]['depth'] ) {
					break;
				}
			}
		}

		return $output;
	}

	/**
	 * Get an array of destination information, including depth
	 * @return array Destination information
	 */
	function get_destinations() {
		global $wpdb;
		$results = $wpdb->get_results("
			SELECT ID, post_title, post_name, post_parent
			FROM {$wpdb->posts}
			WHERE post_type = 'destination'
			AND post_status = 'publish'
		");
		return $results;
	}


	/**
	 * Filter the query based on selected values
	 */
	function filter_posts( $params ) {
		global $wpdb;

		$facet = $params['facet'];
		$selected_values = $params['selected_values'];
		$selected_values = implode( "','", $selected_values );

		$sql = "
		SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
		WHERE facet_name = '{$facet['name']}' AND facet_value IN ('$selected_values')";
		return $wpdb->get_col( $sql ); // WPCS: unprepared SQL OK
	}


	/**
	 * Output any admin scripts
	 */
	function admin_scripts() {
?>
<script>
(function($) {
	wp.hooks.addAction('facetwp/load/destinations', function($this, obj) {
		$this.find('.facet-source').val(obj.source);
		$this.find('.facet-orderby').val(obj.orderby);
		$this.find('.facet-count').val(obj.count);
	});

	wp.hooks.addFilter('facetwp/save/destinations', function($this, obj) {
		obj['source'] = $this.find('.facet-source').val();
		obj['orderby'] = $this.find('.facet-orderby').val();
		obj['count'] = $this.find('.facet-count').val();
		return obj;
	});
})(jQuery);
</script>
<?php
	}


	/**
	 * Output admin settings HTML
	 */
	function settings_html() {
?>
		<tr>
			<td><?php esc_attr_e( 'Sort by', 'fwp' ); ?>:</td>
			<td>
				<select class="facet-orderby">
					<option value="count"><?php esc_attr_e( 'Highest Count', 'fwp' ); ?></option>
					<option value="display_value"><?php esc_attr_e( 'Display Value', 'fwp' ); ?></option>
					<option value="raw_value"><?php esc_attr_e( 'Raw Value', 'fwp' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_attr_e( 'Count', 'fwp' ); ?>:
				<div class="facetwp-tooltip">
					<span class="icon-question">?</span>
					<div class="facetwp-tooltip-content"><?php esc_attr_e( 'The maximum number of facet choices to show', 'fwp' ); ?></div>
				</div>
			</td>
			<td><input type="text" class="facet-count" value="10" /></td>
		</tr>
<?php
	}
}


add_filter( 'facetwp_facet_types', function( $facet_types ) {
	$facet_types['links'] = new FacetWP_Facet_Links();
	return $facet_types;
});

class FacetWP_Facet_Links {

	function __construct() {
		$this->label = __( 'Links', 'fwp' );
	}


	/**
	 * Load the available choices
	 */
	function load_values( $params ) {
		global $wpdb;

		$facet = $params['facet'];
		$from_clause = $wpdb->prefix . 'facetwp_index f';
		$where_clause = $params['where_clause'];

		// Count setting
		$limit = ctype_digit( $facet['count'] ) ? $facet['count'] : 10;

		$from_clause = apply_filters( 'facetwp_facet_from', $from_clause, $facet );
		$where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

		$sql = "
		SELECT f.facet_value, f.facet_display_value, f.term_id, f.parent_id, f.depth, COUNT(DISTINCT f.post_id) AS counter
		FROM $from_clause
		WHERE f.facet_name = '{$facet['name']}' $where_clause
		GROUP BY f.facet_value
		ORDER BY f.depth, counter DESC, f.facet_display_value ASC
		LIMIT $limit";

		return $wpdb->get_results( $sql, ARRAY_A ); // WPCS: unprepared SQL OK.
	}


	/**
	 * Generate the output HTML
	 */
	function render( $params ) {
		global $wpdb;

		$output = '';
		$facet = $params['facet'];
		$values = (array) $params['values'];
		$selected_values = (array) $params['selected_values'];

		$key = 0;
		foreach ( $values as $key => $result ) {
			$selected = in_array( $result['facet_value'], $selected_values ) ? ' checked' : '';
			$selected .= ( 0 == $result['counter'] && '' == $selected ) ? ' disabled' : '';
			$output .= '<div class="facetwp-link' . $selected . '" data-value="' . esc_attr( $result['facet_value'] ) . '">';
			$output .= esc_html( $result['facet_display_value'] ) . ' <span class="facetwp-counter">(' . $result['counter'] . ')</span>';
			$output .= '</div>';

		}

		$results = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.

		$output .= print_r( $results, true );

		$key = 0;

		if ( ! empty( $prev_links ) ) {
			$output .= '<div class="facetwp-depth">';
		}

		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $result ) {
				if ( $key == (int) $num_visible ) {
					$output .= '<div class="facetwp-overflow facetwp-hidden">';
				}
				$output .= '<div class="facetwp-link" data-value="' . $result->facet_value . '">';
				$output .= $result->facet_display_value . ' <span class="facetwp-counter">(' . $result->counter . ')</span>';
				$output .= '</div>';
			}
		}

		if ( $num_visible <= $key ) {
			$output .= '</div>';
			$output .= '<a class="facetwp-toggle">' . __( 'See more', 'fwp' ) . '</a>';
			$output .= '<a class="facetwp-toggle facetwp-hidden">' . __( 'See less', 'fwp' ) . '</a>';
		}

		for ( $i = 0; $i <= $max_depth; $i++ ) {
			$output .= '</div>';
		}

		if ( ! empty( $prev_links ) ) {
			$output .= '</div>';
		}

		return $output;
		return $output;
	}


	/**
	 * Return array of post IDs matching the selected values
	 * using the wp_facetwp_index table
	 */
	function filter_posts( $params ) {
		global $wpdb;

		$output = array();
		$facet = $params['facet'];
		$selected_values = $params['selected_values'];

		$sql = $wpdb->prepare( "SELECT DISTINCT post_id
			FROM {$wpdb->prefix}facetwp_index
			WHERE facet_name = %s",
			$facet['name']
		);

		foreach ( $selected_values as $key => $value ) {
			$results = facetwp_sql( $sql . " AND facet_value IN ('$value')", $facet );
			$output = ( $key > 0 ) ? array_intersect( $output, $results ) : $results;

			if ( empty( $output ) ) {
				break;
			}
		}

		return $output;
	}


	/**
	 * Load and save facet settings
	 */
	function admin_scripts() {
		?>
		<script>
			(function($) {
				wp.hooks.addAction('facetwp/load/links', function($this, obj) {
					$this.find('.facet-source').val(obj.source);
					$this.find('.facet-count').val(obj.count);
				});

				wp.hooks.addFilter('facetwp/save/links', function($this, obj) {
					obj['source'] = $this.find('.facet-source').val();
					obj['count'] = $this.find('.facet-count').val();
					return obj;
				});
			})(jQuery);
		</script>
		<?php
	}


	/**
	 * Parse the facet selections + other front-facing handlers
	 */
	function front_scripts() {
		?>
		<script>
			(function($) {
				wp.hooks.addAction('facetwp/refresh/links', function($this, facet_name) {
					var selected_values = [];
					$this.find('.facetwp-link.checked').each(function() {
						selected_values.push($(this).attr('data-value'));
					});
					FWP.facets[facet_name] = selected_values;
				});

				wp.hooks.addFilter('facetwp/selections/links', function(output, params) {
					var choices = [];
					$.each(params.selected_values, function(idx, val) {
						var choice = params.el.find('.facetwp-link[data-value="' + val + '"]').clone();
						choice.find('.facetwp-counter').remove();
						choices.push({
							value: val,
							label: choice.text()
						});
					});
					return choices;
				});

				$(document).on('click', '.facetwp-type-links .facetwp-link:not(.disabled)', function() {
					$(this).toggleClass('checked');
					FWP.autoload();
				});
			})(jQuery);
		</script>
		<?php
	}


	/**
	 * Admin settings HTML
	 */
	function settings_html() {
		?>
		<tr>
			<td>
				<?php esc_attr_e( 'Count', 'fwp' ); ?>:
				<div class="facetwp-tooltip">
					<span class="icon-question">?</span>
					<div class="facetwp-tooltip-content"><?php esc_attr_e( 'The maximum number of facet choices to show', 'fwp' ); ?></div>
				</div>
			</td>
			<td><input type="text" class="facet-count" value="10" /></td>
		</tr>
		<?php
	}
}
