<?php
/**
 * Pluggable functions for the Search, allowing you to change the layout.
 *
 * @package   	tour-operator
 * @subpackage 	layout
 * @license   	GPL3
 */

add_action( 'posts_selection', function() {
	global $wp_query;

	// if ( count( $wp_query->posts ) > 0 ) {
		add_action( 'lsx_to_search_top', 'lsx_to_search_top', 10 );
		add_action( 'lsx_to_search_bottom', 'lsx_to_search_bottom', 10 );
	// }
}, 10 );

/**
 * Adds the template tags to the bottom of the content-tour.php
 *
 * @package 	tour-operator
 * @subpackage	template-tag
 * @category 	tour
 */

function lsx_to_search_top() {
	global $lsx_to_search;

	if ( is_search() ) {
		$option_slug = '';
	} elseif ( is_post_type_archive( array_keys( $lsx_to_search->post_types ) ) || is_tax( array_keys( $lsx_to_search->taxonomies ) ) ) {
		$option_slug = 'archive_';
	} else {
		return '';
	}

	$show_results = isset( $lsx_to_search->options[ $lsx_to_search->search_slug ]['display_result_count'] );

	$show_sort_combo = ! isset( $lsx_to_search->options[ $lsx_to_search->search_slug ]['disable_all_sorting'] ) || 'on' !== $lsx_to_search->options[ $lsx_to_search->search_slug ]['disable_all_sorting'];

	$az_pagination = $lsx_to_search->options[ $lsx_to_search->search_slug ][ $option_slug . 'az_pagination' ];

	$show_map = false;
	if ( isset( $lsx_to_search->options[ $lsx_to_search->search_slug ][ $option_slug . 'layout_map' ] ) && ! empty( $lsx_to_search->options[ $lsx_to_search->search_slug ][ $option_slug . 'layout_map' ] ) ) {
		$show_map = true;
	}
	$facet_row_classes = apply_filters( 'lsx_to_search_top_facetwp_row_classes', '' );
	$pagination_visible  = false;
	?>
	<div id="facetwp-top">
		<?php if ( $show_sort_combo || $show_results ) { ?>
			<div class="row facetwp-top-row-1 hidden-xs">
				<div class="col-xs-12">

					<?php if ( $show_results ) { ?>
						<div class="row hidden-xs container-results">
							<div class="col-xs-12 facetwp-item facetwp-results">
								<h3 class="lsx-to-search-title lsx-to-search-title-results"><?php esc_html_e( 'Results ', 'to-search' ); ?><?php echo '(' . do_shortcode( '[facetwp counts="true"]' ) . ')'; ?></h3>
								<!--<button class="btn btn-md facetwp-results-clear-btn hidden" type="button" onclick="FWP.reset()"><?php esc_html_e( 'Clear', 'to-search' ); ?></button>-->
							</div>
						</div>
					<?php } ?>

					<?php if ( $show_sort_combo ) { ?>
						<?php echo do_shortcode( '[facetwp sort="true"]' ); ?>
					<?php } ?>

					<?php if ( ( true === $show_map ) && ( is_archive( 'accommodation' ) || is_search() ) ) { ?>
						<ul class="nav nav-tabs">
							<li class="active to-list-toggle"><a data-toggle="tab" href="#to-search-list"><?php echo esc_html__( 'List', 'to-search' ); ?></a></li>
							<li class="to-map-toggle"><a data-toggle="tab" href="#to-search-map"><?php echo esc_html__( 'Map', 'to-search' ); ?></a></li>
						</ul>
					<?php } ?>

				</div>
			</div>
		<?php } ?>
	</div>
	<?php
}

/**
 * Adds the template tags to the bottom of the content-tour.php
 *
 * @package 	tour-operator
 * @subpackage	template-tag
 * @category 	tour
 */
function lsx_to_search_bottom() {
	global $lsx_to_search;

	if ( is_search() ) {
		$option_slug = '';
	} elseif ( is_post_type_archive( array_keys( $lsx_to_search->post_types ) ) || is_tax( array_keys( $lsx_to_search->taxonomies ) ) ) {
		$option_slug = 'archive_';
	}

	$show_pagination = ! isset( $lsx_to_search->options[ $lsx_to_search->search_slug ][ 'disable_' . $option_slug . 'pagination' ] ) || 'on' !== $lsx_to_search->options[ $lsx_to_search->search_slug ][ 'disable_' . $option_slug . 'pagination' ];
	$az_pagination   = $lsx_to_search->options[ $lsx_to_search->search_slug ][ $option_slug . 'az_pagination' ];

	if ( $show_pagination || ! empty( $az_pagination ) ) { ?>
		<div id="facetwp-bottom">
			<div class="row facetwp-bottom-row-1">
				<div class="col-xs-12 col-lg-8 hidden-xs">
					<?php if ( ! empty( $az_pagination ) ) {
						echo do_shortcode( '[facetwp facet="' . $az_pagination . '"]' );
					} ?>
				</div>

				<?php if ( $show_pagination ) { ?>
					<div class="col-xs-12 col-lg-4">
						<?php echo do_shortcode( '[facetwp pager="true"]' ); ?>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php }
}
