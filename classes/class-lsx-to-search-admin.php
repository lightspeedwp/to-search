<?php
/**
 * LSX Search Main Class
 */

class LSX_TO_Search_Admin extends LSX_TO_Search {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'set_vars' ) );
		add_action( 'init', array( $this, 'set_facetwp_vars' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_filter( 'lsx_customizer_colour_selectors_body', array( $this, 'customizer_to_search_body_colours_handler' ), 15, 2 );
		add_filter( 'lsx_customizer_colour_selectors_main_menu', array( $this, 'customizer_to_search_main_menu_colours_handler' ), 15, 2 );
	}

	/**
	 * The Admin Init action, to setup variables before anything runs.
	 *
	 */
	public function admin_init() {
		add_action( 'lsx_to_framework_dashboard_tab_content', array( $this, 'general_settings' ), 50, 1 );
		add_action( 'lsx_to_framework_display_tab_content', array( $this, 'display_settings' ), 50, 1 );

		foreach ( $this->post_types as $pt => $pv ) {
			add_action( 'lsx_to_framework_' . $pt . '_tab_content', array( $this, 'display_settings' ), 50, 2 );
			add_action( 'lsx_to_framework_' . $pt . '_tab_archive_settings_bottom', array( $this, 'archive_settings' ), 10, 1 );
		}
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function general_settings( $post_type = '', $tab = null ) {
		if ( null === $tab ) {
			$tab = $post_type;
			$post_type = 'general';
		}
		if ( 'search' === $tab ) :
		?>
			<?php
				$post_types = get_post_types( array(
					'public' => true,
				) );

				$key = array_search( 'attachment', $post_types, true );

				if ( false !== $key ) {
					unset( $post_types[ $key ] );
				}
			?>
			<tr class="form-field-wrap">
				<th scope="row">
					<label for="search_post_types"><?php esc_html_e( 'Post types', 'to-search' ); ?></label>
				</th>
				<td>
					<ul>
						<?php
							$active_post_types = array();

							if ( isset( $this->options[ $post_type ]['search_post_types'] ) && is_array( $this->options[ $post_type ]['search_post_types'] ) ) {
								$active_post_types = $this->options[ $post_type ]['search_post_types'];
							}

							foreach ( $post_types as $key => $value ) {
								?>
								<li>
									<input type="checkbox" <?php if ( array_key_exists( $key, $active_post_types ) ) { echo 'checked="checked"'; } ?> name="search_post_types[<?php echo esc_attr( $key ); ?>]" /> <label><?php echo esc_html( ucwords( $key ) ); ?></label>
								</li>
								<?php
							}
						?>
					</ul>
				</td>
			</tr>
		<?php
		endif;
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function display_settings( $post_type = '', $tab = null ) {
		if ( null === $tab ) {
			$tab = $post_type;
			$post_type = 'display';
		}
		if ( 'search' === $tab ) :
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_search"><?php esc_html_e( 'Enable Search', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_search}} checked="checked" {{/if}} name="enable_search" />
				<?php if ( 'display' === $post_type ) { ?>
					<small><?php esc_html_e( 'This adds the facet shortcodes to the search results template.', 'to-search' ); ?></small>
				<?php } else { ?>
					<small><?php esc_html_e( 'This adds the facet shortcodes to the post type archive and taxonomy templates.', 'to-search' ); ?></small>
				<?php } ?>
			</td>
		</tr>
		<?php if ( 'display' !== $post_type ) { ?>
			<tr class="form-field-wrap">
				<th scope="row">
					<label><?php esc_html_e( 'Grid/list layout', 'tour-operator' ); ?></label>
				</th>
				<td>
					<select value="{{search_grid_list_layout}}" name="search_grid_list_layout">
						<option value="" {{#is search_grid_list_layout value=""}}selected="selected"{{/is}}><?php esc_html_e( 'List', 'tour-operator' ); ?></option>
						<option value="grid" {{#is search_grid_list_layout value="grid"}} selected="selected"{{/is}}><?php esc_html_e( 'Grid', 'tour-operator' ); ?></option>
					</select>
				</td>
			</tr>
		<?php } ?>
		<?php if ( 'display' === $post_type ) : ?>
			<tr class="form-field">
				<th scope="row">
					<label for="enable_search_pt_label"><?php esc_html_e( 'Enable Post Type Label', 'tour-operator' ); ?></label>
				</th>
				<td>
					<input type="checkbox" {{#if enable_search_pt_label}} checked="checked" {{/if}} name="enable_search_pt_label" />
					<small><?php esc_html_e( 'This enables the post type label from entries on search results page.', 'tour-operator' ); ?></small>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<label for="enable_search_continent_filter"><?php esc_html_e( 'Enable Continent Filter', 'tour-operator' ); ?></label>
				</th>
				<td>
					<input type="checkbox" {{#if enable_search_continent_filter}} checked="checked" {{/if}} name="enable_search_continent_filter" />
					<small><?php esc_html_e( 'This enables the continent filter in FacetWP destinations filter.', 'tour-operator' ); ?></small>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<label for="enable_search_region_filter"><?php esc_html_e( 'Enable Continental Regions', 'tour-operator' ); ?></label>
				</th>
				<td>
					<input type="checkbox" {{#if enable_search_region_filter}} checked="checked" {{/if}} name="enable_search_region_filter" />
					<small><?php esc_html_e( 'This disable continents and enabled the sub regions.', 'tour-operator' ); ?></small>
				</td>
			</tr>
		<?php endif; ?>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="search_layout"><?php esc_html_e( 'Columns Layout', 'to-search' ); ?></label>
			</th>
			<td>
				<select value="{{search_layout}}" name="search_layout">
					<option value="" {{#is search_layout value=""}}selected="selected"{{/is}}><?php esc_html_e( 'Follow the theme layout', 'to-search' ); ?></option>
					<option value="1c" {{#is search_layout value="1c"}} selected="selected"{{/is}}><?php esc_html_e( '1 column', 'to-search' ); ?></option>
					<option value="2cr" {{#is search_layout value="2cr"}} selected="selected"{{/is}}><?php esc_html_e( '2 columns / Content on right', 'to-search' ); ?></option>
					<option value="2cl" {{#is search_layout value="2cl"}} selected="selected"{{/is}}><?php esc_html_e( '2 columns / Content on left', 'to-search' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="layout_map"><?php esc_html_e( 'Results Layout', 'to-search' ); ?></label>
			</th>
			<td>
				<select value="{{layout_map}}" name="layout_map">
					<option value="" {{#is layout_map value=""}}selected="selected"{{/is}}><?php esc_html_e( 'Only List', 'to-search' ); ?></option>
					<option value="list_and_map" {{#is layout_map value="list_and_map"}} selected="selected"{{/is}}><?php esc_html_e( 'List and Map', 'to-search' ); ?></option>
				</select>
			</td>
		</tr>
		<?php if ( 'display' === $post_type ) : ?>
			<tr class="form-field-wrap">
				<th scope="row">
					<label for="search_grid_list_layout"><?php esc_html_e( 'Grid/list layout', 'to-search' ); ?></label>
				</th>
				<td>
					<select value="{{search_grid_list_layout}}" name="search_grid_list_layout">
						<option value="" {{#is search_grid_list_layout value=""}}selected="selected"{{/is}}><?php esc_html_e( 'List', 'to-search' ); ?></option>
						<option value="grid" {{#is search_grid_list_layout value="grid"}} selected="selected"{{/is}}><?php esc_html_e( 'Grid', 'to-search' ); ?></option>
					</select>
				</td>
			</tr>
			<tr class="form-field-wrap">
				<th scope="row">
					<label><?php esc_html_e( 'List layout images', 'to-search' ); ?></label>
				</th>
				<td>
					<select value="{{list_layout_image_style}}" name="list_layout_image_style">
						<option value="" {{#is list_layout_image_style value=""}}selected="selected"{{/is}}><?php esc_html_e( 'Full-height', 'to-search' ); ?></option>
						<option value="max-height" {{#is list_layout_image_style value="max-height"}} selected="selected"{{/is}}><?php esc_html_e( 'Max-height', 'to-search' ); ?></option>
					</select>
				</td>
			</tr>
		<?php endif; ?>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="disable_per_page"><?php esc_html_e( 'Per Page', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if disable_per_page}} checked="checked" {{/if}} name="disable_per_page" /> <label for="facets"><?php esc_html_e( 'Disable Per Page', 'to-search' ); ?></label>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="enable_collapse"><?php esc_html_e( 'Collapse', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_collapse}} checked="checked" {{/if}} name="enable_collapse" /> <label for="facets"><?php esc_html_e( 'Enable collapsible filters on search results', 'to-search' ); ?></label>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label><?php esc_html_e( 'Sorting', 'to-search' ); ?></label>
			</th>
			<td>
				<ul>
					<li><input type="checkbox" {{#if disable_all_sorting}} checked="checked" {{/if}} name="disable_all_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Sorting', 'to-search' ); ?></label></li>
					<li><input type="checkbox" {{#if disable_az_sorting}} checked="checked" {{/if}} name="disable_az_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Title (A-Z)', 'to-search' ); ?></label></li>
					<li><input type="checkbox" {{#if disable_date_sorting}} checked="checked" {{/if}} name="disable_date_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Date', 'to-search' ); ?></label></li>
					<?php if ( 'tour' === $post_type || 'accommodation' === $post_type || 'display' === $post_type ) { ?>
						<li><input type="checkbox" {{#if disable_price_sorting}} checked="checked" {{/if}} name="disable_price_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Price', 'to-search' ); ?></label></li>
					<?php } ?>
				</ul>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="display_result_count"><?php esc_html_e( 'Display Result Count', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_result_count}} checked="checked" {{/if}} name="display_result_count" />
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="az_pagination"><?php esc_html_e( 'Alphabetical Facet', 'to-search' ); ?></label>
			</th>
			<td>
				<?php
					if ( is_array( $this->facet_data ) && ! empty( $this->facet_data ) ) {
						$active_facet = $this->options[ $post_type ]['az_pagination'];
						?>
						<select id="az_pagination" name="az_pagination">
							<option <?php if ( empty( $active_facet ) ) { echo 'selected="selected"'; } ?> value=""><?php esc_html_e( 'None', 'to-search' ); ?></option>

							<?php foreach ( $this->facet_data as $facet ) {
								if ( isset( $facet['type'] ) && 'alpha' === $facet['type'] ) { ?>
									<option <?php if ( $active_facet === $facet['name'] ) { echo 'selected="selected"'; } ?> value="<?php echo esc_attr( $facet['name'] ); ?>"><?php echo esc_html( $facet['label'] ) . ' (' . esc_html( $facet['name'] ) . ')'; ?></option>
								<?php }
							} ?>
						</select>
						<?php
					} else {
						esc_html_e( 'You have no Facets setup.', 'to-search' );
					}
				?>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="facets"><?php esc_html_e( 'Facets', 'to-search' ); ?></label>
			</th>
			<td>
				<ul>
					<?php
					if ( is_array( $this->facet_data ) && ! empty( $this->facet_data ) ) {
						$active_facets = array();

						if ( isset( $this->options[ $post_type ]['facets'] ) && is_array( $this->options[ $post_type ]['facets'] ) ) {
							$active_facets = $this->options[ $post_type ]['facets'];
						}

						foreach ( $this->facet_data as $facet ) {
							if ( isset( $facet['type'] )  && 'alpha' !== $facet['type'] ) { ?>
								<li>
									<input type="checkbox" <?php if ( array_key_exists( $facet['name'], $active_facets ) ) { echo 'checked="checked"'; } ?> name="facets[<?php echo esc_attr( $facet['name'] ); ?>]" /> <label for="facets"><?php echo esc_html( $facet['label'] ) . ' (' . esc_html( $facet['name'] ) . ')'; ?></label>
								</li>
							<?php }
						}
					} else {
						?>
							<li><?php esc_html_e( 'You have no Facets setup.', 'to-search' ); ?></li>
						<?php
					}
					?>
				</ul>
			</td>
		</tr>
		<?php
		endif;
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function archive_settings( $post_type ) {
		?>
		<tr class="form-field">
			<th scope="row" colspan="2"><label><h3><?php esc_html_e( 'Search Settings', 'to-search' ); ?></h3></label></th>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_facets"><?php esc_html_e( 'Enable Filtering', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_facets}} checked="checked" {{/if}} name="enable_facets" />
				<small><?php esc_html_e( 'This adds the facet shortcodes to the post type archive and taxonomy templates.', 'to-search' ); ?></small>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="archive_layout"><?php esc_html_e( 'Columns Layout', 'to-search' ); ?></label>
			</th>
			<td>
				<select value="{{archive_layout}}" name="archive_layout">
					<option value="" {{#is archive_layout value=""}}selected="selected"{{/is}}><?php esc_html_e( 'Follow the theme layout', 'to-search' ); ?></option>
					<option value="1c" {{#is archive_layout value="1c"}} selected="selected"{{/is}}><?php esc_html_e( '1 column', 'to-search' ); ?></option>
					<option value="2cr" {{#is archive_layout value="2cr"}} selected="selected"{{/is}}><?php esc_html_e( '2 columns / Content on right', 'to-search' ); ?></option>
					<option value="2cl" {{#is archive_layout value="2cl"}} selected="selected"{{/is}}><?php esc_html_e( '2 columns / Content on left', 'to-search' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="archive_layout_map"><?php esc_html_e( 'Results Layout', 'to-search' ); ?></label>
			</th>
			<td>
				<select value="{{archive_layout_map}}" name="archive_layout_map">
					<option value="" {{#is archive_layout_map value=""}}selected="selected"{{/is}}><?php esc_html_e( 'Only List', 'to-search' ); ?></option>
					<option value="list_and_map" {{#is archive_layout_map value="list_and_map"}} selected="selected"{{/is}}><?php esc_html_e( 'List and Map', 'to-search' ); ?></option>
				</select>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="disable_archive_per_page"><?php esc_html_e( 'Per Page', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if disable_archive_per_page}} checked="checked" {{/if}} name="disable_archive_per_page" /> <label for="facets"><?php esc_html_e( 'Disable Per Page', 'to-search' ); ?></label>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label><?php esc_html_e( 'Sorting', 'to-search' ); ?></label>
			</th>
			<td>
				<ul>
					<li><input type="checkbox" {{#if disable_archive_all_sorting}} checked="checked" {{/if}} name="disable_archive_all_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Sorting', 'to-search' ); ?></label></li>
					<li><input type="checkbox" {{#if disable_archive_az_sorting}} checked="checked" {{/if}} name="disable_archive_az_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Title (A-Z)', 'to-search' ); ?></label></li>
					<li><input type="checkbox" {{#if disable_archive_date_sorting}} checked="checked" {{/if}} name="disable_archive_date_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Date', 'to-search' ); ?></label></li>
					<?php if ( 'tour' === $post_type || 'accommodation' === $post_type || 'display' === $post_type ) { ?>
					<li><input type="checkbox" {{#if disable_archive_price_sorting}} checked="checked" {{/if}} name="disable_archive_price_sorting" /> <label for="facets"><?php esc_html_e( 'Disable Price', 'to-search' ); ?></label></li>
					<?php } ?>

				</ul>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="display_archive_result_count"><?php esc_html_e( 'Display Result Count', 'to-search' ); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_archive_result_count}} checked="checked" {{/if}} name="display_archive_result_count" />
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="archive_az_pagination"><?php esc_html_e( 'Alphabetical Facet', 'to-search' ); ?></label>
			</th>
			<td>
				<?php
					if ( is_array( $this->facet_data ) && ! empty( $this->facet_data ) ) {
						$active_facet = $this->options[ $post_type ]['archive_az_pagination'];
						?>
						<select id="archive_az_pagination" name="archive_az_pagination">
							<option <?php if ( empty( $active_facet ) ) { echo 'selected="selected"'; } ?> value=""><?php esc_html_e( 'None', 'to-search' ); ?></option>

							<?php foreach ( $this->facet_data as $facet ) {
								if ( isset( $facet['type'] ) && 'alpha' === $facet['type'] ) { ?>
									<option <?php if ( $active_facet === $facet['name'] ) { echo 'selected="selected"'; } ?> value="<?php echo esc_attr( $facet['name'] ); ?>"><?php echo esc_html( $facet['label'] ) . ' (' . esc_html( $facet['name'] ) . ')'; ?></option>
								<?php }
							} ?>
						</select>
						<?php
					} else {
						esc_html_e( 'You have no Facets setup.', 'to-search' );
					}
				?>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="facets"><?php esc_html_e( 'Facets', 'to-search' ); ?></label>
			</th>
			<td>
				<ul>
					<?php
					if ( is_array( $this->facet_data ) && ! empty( $this->facet_data ) ) {
						$active_facets = array();

						if ( isset( $this->options[ $post_type ]['archive_facets'] ) && is_array( $this->options[ $post_type ]['archive_facets'] ) ) {
							$active_facets = $this->options[ $post_type ]['archive_facets'];
						}

						foreach ( $this->facet_data as $facet ) {
							if ( isset( $facet['type'] ) && 'alpha' !== $facet['type'] ) {
								?>
								<li>
									<input type="checkbox" <?php if ( array_key_exists( $facet['name'], $active_facets ) ) { echo 'checked="checked"'; } ?> name="archive_facets[<?php echo esc_attr( $facet['name'] ); ?>]" /> <label for="facets"><?php echo esc_html( $facet['label'] ) . ' (' . esc_html( $facet['name'] ) . ')'; ?></label>
								</li>
								<?php
							}
						}
					} else {
						?>
							<li><?php esc_html_e( 'You have no Facets setup.', 'to-search' ); ?></li>
						<?php
					}
					?>
				</ul>
			</td>
		</tr>
		<?php
	}

	/**
	 * Handle body colours that might be change by LSX Customiser
	 */
	public function customizer_to_search_body_colours_handler( $css, $colors ) {
		$css .= '
			@import "' . LSX_TO_SEARCH_PATH . '/assets/css/scss/customizer-to-search-body-colours";

			/**
			 * LSX Customizer - Body (TO Search)
			 */
			@include customizer-to-search-body-colours (
				$bg:       	' . $colors['background_color'] . ',
				$breaker:   ' . $colors['body_line_color'] . ',
				$color:    	' . $colors['body_text_color'] . ',
				$link:    	' . $colors['body_link_color'] . ',
				$hover:    	' . $colors['body_link_hover_color'] . ',
				$small:    	' . $colors['body_text_small_color'] . '
			);
		';

		return $css;
	}

	/**
	 * Handle body colours that might be change by LSX Customiser
	 */
	public function customizer_to_search_main_menu_colours_handler( $css, $colors ) {
		$css .= '
			@import "' . LSX_TO_SEARCH_PATH . '/assets/css/scss/customizer-to-search-main-menu-colours";

			/**
			 * LSX Customizer - Main Menu (TO Search)
			 */
			@include customizer-to-search-main-menu-colours (
				$hover:                ' . $colors['main_menu_link_hover_color'] . ',
				$dropdown:            ' . $colors['main_menu_dropdown_background_color'] . ',
				$dropdown-link:       ' . $colors['main_menu_dropdown_link_color'] . '
			);
		';

		return $css;
	}

}

new LSX_TO_Search_Admin();
