<?php
/**
 * LSX Search Main Class
 */

class LSX_TO_Search_Admin extends LSX_TO_Search{	

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('init',array($this,'set_vars'));
		add_action('init',array($this,'set_facetwp_vars'));
		
		add_action('admin_init', array($this,'admin_init'));
	}

	/**
	 * The Admin Init action, to setup variables before anything runs.
	 *
	 */
	public function admin_init() {
		if(class_exists('FacetWP')){
			add_action('lsx_to_framework_display_tab_content', array($this,'display_settings'),50,1);

			foreach($this->post_types as $pt => $pv){
				add_action('lsx_to_framework_'.$pt.'_tab_content', array($this,'display_settings'),50,2);
				add_action('lsx_to_framework_'.$pt.'_tab_archive_settings_bottom', array($this,'archive_settings'),10,1);
			}
		}
	}	

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function display_settings($post_type='',$tab=null) {
		if ( null === $tab ) {
			$tab = $post_type;
			$post_type = 'display';
		}
		if('search' === $tab) :
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_search"><?php _e('Enable Search','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_search}} checked="checked" {{/if}} name="enable_search" />
				<?php if('general' === $tab) { ?>
					<small><?php _e('This adds the facet shortcodes to the search results template.','to-search'); ?></small>
				<?php }else{ ?>
					<small><?php _e('This adds the facet shortcodes to the post type archive and taxonomy templates.','to-search'); ?></small>
				<?php } ?>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="search_layout"><?php _e('Layout','to-search'); ?></label>
			</th>
			<td>
				<select value="{{search_layout}}" name="search_layout">
					<option value="" {{#is search_layout value=""}}selected="selected"{{/is}}><?php _e('Follow the theme layout','to-search'); ?></option>
					<option value="1c" {{#is search_layout value="1c"}} selected="selected"{{/is}}><?php _e('1 column','to-search'); ?></option>
					<option value="2cr" {{#is search_layout value="2cr"}} selected="selected"{{/is}}><?php _e('2 columns / Content on right','to-search'); ?></option>
					<option value="2cl" {{#is search_layout value="2cl"}} selected="selected"{{/is}}><?php _e('2 columns / Content on left','to-search'); ?></option>
				?>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="layout_map"><?php _e('Inner Layout (Results)','to-search'); ?></label>
			</th>
			<td>
				<select value="{{layout_map}}" name="layout_map">
					<option value="" {{#is layout_map value=""}}selected="selected"{{/is}}><?php _e('Only List','to-search'); ?></option>
					<option value="list_and_map" {{#is layout_map value="list_and_map"}} selected="selected"{{/is}}><?php _e('List and Map','to-search'); ?></option>
				?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_date_sorting"><?php _e('Enable Date Sorting','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_date_sorting}} checked="checked" {{/if}} name="enable_date_sorting" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_price_sorting"><?php _e('Enable Price Sorting','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_price_sorting}} checked="checked" {{/if}} name="enable_price_sorting" />
				<small><?php _e('WARNING, any item that doesnt have a price will not show. ','to-search'); ?></small>
			</td>
		</tr>		
		<tr class="form-field">
			<th scope="row">
				<label for="display_result_count"><?php _e('Display Result Count','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_result_count}} checked="checked" {{/if}} name="display_result_count" />
			</td>
		</tr>			
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="facets"><?php _e('Facets','to-search'); ?></label>
			</th>
			<td><ul>
			<?php
			if(is_array($this->facet_data) && !empty($this->facet_data)){
				$active_facets = array();
				if(isset($this->options[$post_type]['facets']) && is_array($this->options[$post_type]['facets'])){
					$active_facets = $this->options[$post_type]['facets'];
				}

				foreach( $this->facet_data as $facet){
					?>
					<li>
						<input type="checkbox" <?php if(array_key_exists($facet['name'],$active_facets)){ echo 'checked="checked"'; } ?> name="facets[<?php echo $facet['name']; ?>]" /> <label for="facets"><?php echo $facet['label'].' ('.$facet['name'].')'; ?></label> 
					</li>
				<?php }
			}else{
				?>
					<li><?php _e('You have no Facets setup.','to-search'); ?></li>
				<?php
			}
			?>
			</ul></td>
		</tr>
		<?php
		endif;
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function archive_settings($post_type) { 
		?>		
		<tr class="form-field">
			<th scope="row" colspan="2"><label><h3><?php _e('Search Settings','to-search'); ?></h3></label></th>
		</tr>		
		<tr class="form-field">
			<th scope="row">
				<label for="enable_facets"><?php _e('Enable Filtering','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_facets}} checked="checked" {{/if}} name="enable_facets" />
				<small><?php _e('This adds the facet shortcodes to the post type archive and taxonomy templates.','to-search'); ?></small>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="archive_layout"><?php _e('Layout','to-search'); ?></label>
			</th>
			<td>
				<select value="{{archive_layout}}" name="archive_layout">
					<option value="" {{#is archive_layout value=""}}selected="selected"{{/is}}><?php _e('Follow the theme layout','to-search'); ?></option>
					<option value="1c" {{#is archive_layout value="1c"}} selected="selected"{{/is}}><?php _e('1 column','to-search'); ?></option>
					<option value="2cr" {{#is archive_layout value="2cr"}} selected="selected"{{/is}}><?php _e('2 columns / Content on right','to-search'); ?></option>
					<option value="2cl" {{#is archive_layout value="2cl"}} selected="selected"{{/is}}><?php _e('2 columns / Content on left','to-search'); ?></option>
				?>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="archive_layout_map"><?php _e('Inner Layout (Results)','to-search'); ?></label>
			</th>
			<td>
				<select value="{{archive_layout_map}}" name="archive_layout_map">
					<option value="" {{#is archive_layout_map value=""}}selected="selected"{{/is}}><?php _e('Only List','to-search'); ?></option>
					<option value="list_and_map" {{#is archive_layout_map value="list_and_map"}} selected="selected"{{/is}}><?php _e('List and Map','to-search'); ?></option>
				?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_archive_date_sorting"><?php _e('Enable Date Sorting','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_archive_date_sorting}} checked="checked" {{/if}} name="enable_archive_date_sorting" />
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_archive_price_sorting"><?php _e('Enable Price Sorting','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_archive_price_sorting}} checked="checked" {{/if}} name="enable_archive_price_sorting" />
				<small><?php _e('WARNING, any item that doesnt have a price will not show. ','to-search'); ?></small>
			</td>
		</tr>		
		<tr class="form-field">
			<th scope="row">
				<label for="display_archive_result_count"><?php _e('Display Result Count','to-search'); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_archive_result_count}} checked="checked" {{/if}} name="display_archive_result_count" />
			</td>
		</tr>			
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="facets"><?php _e('Facets','to-search'); ?></label>
			</th>
			<td><ul>
			<?php 	
			if(is_array($this->facet_data) && !empty($this->facet_data)){

				$active_facets = array();
				if(isset($this->options[$post_type]['archive_facets']) && is_array($this->options[$post_type]['archive_facets'])){
					$active_facets = $this->options[$post_type]['archive_facets'];
				}

				foreach( $this->facet_data as $facet){
					?>
					<li>
						<input type="checkbox" <?php if(array_key_exists($facet['name'],$active_facets)){ echo 'checked="checked"'; } ?> name="archive_facets[<?php echo $facet['name']; ?>]" /> <label for="facets"><?php echo $facet['label'].' ('.$facet['name'].')'; ?></label> 
					</li>
				<?php }
			}else{
				?>
					<li><?php _e('You have no Facets setup.','to-search'); ?></li>
				<?php
			}
			?>
			</ul></td>
		</tr>
		<?php
	}

}
new LSX_TO_Search_Admin();