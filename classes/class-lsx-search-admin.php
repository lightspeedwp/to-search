<?php
/**
 * LSX Search Main Class
 */

class LSX_Search_Admin extends LSX_Search{	

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('admin_init', array($this,'admin_init'));
		add_action('lsx_framework_dashboard_tab_content',array($this,'settings'),11);
	}

	/**
	 * The Admin Init action, to setup variables before anything runs.
	 *
	 */
	public function admin_init() {
		if(class_exists('FacetWP')){
			foreach($this->post_types as $pt){
				add_action('lsx_framework_'.$pt.'_tab_single_settings_bottom', array($this,'search_settings'),50,1);
				add_action('lsx_framework_'.$pt.'_tab_archive_settings_bottom', array($this,'archive_settings'),10,1);
			}
		}
	}	

	/**
	 * Outputs the dashboard tabs settings
	 */
	public function settings() {
		?>	
			<tr class="form-field">
				<th scope="row" colspan="2"><label><h3>LSX Search</h3></label></th>
			</tr>	
			<tr class="form-field">
				<th scope="row">
					<label for="text"><?php _e('Text Input','lsx-tour-operators'); ?></label>
				</th>
				<td>
					<input type="text" {{#if text}} value="{{text}}" {{/if}} name="text" />
				</td>
			</tr>				
			<tr class="form-field">
				<th scope="row">
					<label for="checkbox"><?php _e('Checkbox','lsx-tour-operators'); ?></label>
				</th>
				<td>
					<input type="checkbox" {{#if checkbox}} checked="checked" {{/if}} name="checkbox" />
					<small><?php _e('An example of a checkbox',$this->plugin_slug); ?></small>
				</td>
			</tr>				
		<?php	
	}	
}
new LSX_Search_Admin();