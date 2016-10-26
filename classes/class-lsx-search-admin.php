<?php
/**
 * LSX Search Main Class
 */

class LSX_Search_Admin extends LSX_Search{	

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('init',array($this,'init'));
		add_action('admin_init', array($this,'admin_init'));
	}

	/**
	 * The Admin Init action, to setup variables before anything runs.
	 *
	 */
	public function admin_init() {
		
		if(class_exists('FacetWP')){
			foreach($this->post_types as $pt => $pv){
				add_action('to_framework_'.$pt.'_tab_single_settings_bottom', array($this,'search_settings'),50,1);
				add_action('to_framework_'.$pt.'_tab_archive_settings_bottom', array($this,'archive_settings'),10,1);
			}
		}
	}	

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function search_settings($tab='general') { ?>
		<tr class="form-field">
			<th scope="row" colspan="2"><label><h3><?php _e('Search Settings',$this->plugin_slug); ?></h3></label></th>
		</tr>		
		<tr class="form-field">
			<th scope="row">
				<label for="enable_search"><?php _e('Enable Search',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_search}} checked="checked" {{/if}} name="enable_search" />
				<?php if('general' === $tab) { ?>
					<small><?php _e('This adds the facet shortcodes to the search results template.',$this->plugin_slug); ?></small>
				<?php }else{ ?>
					<small><?php _e('This adds the facet shortcodes to the post type archive and taxonomy templates.',$this->plugin_slug); ?></small>
				<?php } ?>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="search_layout"><?php _e('Layout',$this->plugin_slug); ?></label>
			</th>
			<td>
				<select value="{{search_layout}}" name="search_layout">
					<option value="" {{#is search_layout value=""}}selected="selected"{{/is}}><?php _e('Follow the theme layout',$this->plugin_slug); ?></option>
					<option value="1c" {{#is search_layout value="1c"}} selected="selected"{{/is}}>1 column</option>
					<option value="2cr" {{#is search_layout value="2cr"}} selected="selected"{{/is}}>2 columns / Content on right</option>
					<option value="2cl" {{#is search_layout value="2cl"}} selected="selected"{{/is}}>2 columns / Content on left</option>
				?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_price_sorting"><?php _e('Enable Price Sorting',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_price_sorting}} checked="checked" {{/if}} name="enable_price_sorting" />
				<small><?php _e('WARNING, any item that doesnt have a price will not show. ',$this->plugin_slug); ?></small>
			</td>
		</tr>		
		<tr class="form-field">
			<th scope="row">
				<label for="display_result_count"><?php _e('Display Result Count',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_result_count}} checked="checked" {{/if}} name="display_result_count" />
			</td>
		</tr>			
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="facets"><?php _e('Facets',$this->plugin_slug); ?></label>
			</th>
			<td><ul>
			<?php 	
			if(is_array($this->facet_data) && !empty($this->facet_data)){

				$active_facets = array();
				if(isset($this->options[$tab]['facets']) && is_array($this->options[$tab]['facets'])){
					$active_facets = $this->options[$tab]['facets'];
				}

				foreach( $this->facet_data as $facet){
					?>
					<li>
						<input type="checkbox" <?php if(array_key_exists($facet['name'],$active_facets)){ echo 'checked="checked"'; } ?> name="facets[<?php echo $facet['name']; ?>]" /> <label for="facets"><?php echo $facet['label'].' ('.$facet['name'].')'; ?></label> 
					</li>
				<?php }
			}else{
				?>
					<li><?php _e('You have no Facets setup.',$this->plugin_slug); ?></li>
				<?php
			}
			?>
			</ul></td>
		</tr>

		<?php
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function archive_settings($tab='general') { 
		if('general' !== $tab){
		?>		
		<tr class="form-field">
			<th scope="row">
				<label for="enable_facets"><?php _e('Enable Filtering',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_facets}} checked="checked" {{/if}} name="enable_facets" />
				<small><?php _e('This adds the facet shortcodes to the post type archive and taxonomy templates.',$this->plugin_slug); ?></small>
			</td>
		</tr>
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="archive_layout"><?php _e('Layout',$this->plugin_slug); ?></label>
			</th>
			<td>
				<select value="{{archive_layout}}" name="archive_layout">
					<option value="" {{#is archive_layout value=""}}selected="selected"{{/is}}><?php _e('Follow the theme layout',$this->plugin_slug); ?></option>
					<option value="1c" {{#is archive_layout value="1c"}} selected="selected"{{/is}}>1 column</option>
					<option value="2cr" {{#is archive_layout value="2cr"}} selected="selected"{{/is}}>2 columns / Content on right</option>
					<option value="2cl" {{#is archive_layout value="2cl"}} selected="selected"{{/is}}>2 columns / Content on left</option>
				?>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="enable_archive_price_sorting"><?php _e('Enable Price Sorting',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if enable_archive_price_sorting}} checked="checked" {{/if}} name="enable_archive_price_sorting" />
				<small><?php _e('WARNING, any item that doesnt have a price will not show. ',$this->plugin_slug); ?></small>
			</td>
		</tr>		
		<tr class="form-field">
			<th scope="row">
				<label for="display_archive_result_count"><?php _e('Display Result Count',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_archive_result_count}} checked="checked" {{/if}} name="display_archive_result_count" />
			</td>
		</tr>			
		<tr class="form-field-wrap">
			<th scope="row">
				<label for="facets"><?php _e('Facets',$this->plugin_slug); ?></label>
			</th>
			<td><ul>
			<?php 	
			if(is_array($this->facet_data) && !empty($this->facet_data)){

				$active_facets = array();
				if(isset($this->options[$tab]['archive_facets']) && is_array($this->options[$tab]['archive_facets'])){
					$active_facets = $this->options[$tab]['archive_facets'];
				}

				foreach( $this->facet_data as $facet){
					?>
					<li>
						<input type="checkbox" <?php if(array_key_exists($facet['name'],$active_facets)){ echo 'checked="checked"'; } ?> name="archive_facets[<?php echo $facet['name']; ?>]" /> <label for="facets"><?php echo $facet['label'].' ('.$facet['name'].')'; ?></label> 
					</li>
				<?php }
			}else{
				?>
					<li><?php _e('You have no Facets setup.',$this->plugin_slug); ?></li>
				<?php
			}
			?>
			</ul></td>
		</tr>
		<?php 
		}
	}		
}
new LSX_Search_Admin();