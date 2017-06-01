<?php
/**
 * LSX_TO_Search Frontend Main Class
 */

class LSX_TO_Search_Frontend extends LSX_TO_Search{

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
		add_action('init',array($this,'set_vars'));
		add_action('init',array($this,'set_facetwp_vars'));
		add_action('init',array($this,'remove_posts_and_pages_from_search'),99);

		add_action('wp_head', array($this,'wp_head'), 11);
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );

		//Redirects
		add_filter( 'template_include', array( $this, 'search_template_include'), 99 );
		add_action( 'template_redirect', array($this,'pretty_search_redirect') ) ;
		add_filter( 'pre_get_posts',  array($this,'pretty_search_parse_query') ) ;

		//Layout Filter
		add_filter('lsx_layout', array($this,'lsx_layout'), 20,1);	

		add_filter( 'facetwp_sort_options', array( $this,'facet_sort_options'), 10, 2 );
		add_action('pre_get_posts', array($this,'price_sorting'),100);

		
		add_filter( 'facetwp_pager_html', array($this,'facetwp_pager_html'), 10, 2 );
		add_filter( 'facetwp_result_count', array($this,'facetwp_result_count'), 10, 2 );

		add_filter( 'facetwp_facet_html', array($this,'destination_facet_html'), 10, 2 );

		add_shortcode( 'lsx_search_form', array($this,'search_form') );

		add_filter( 'searchwp_short_circuit', array($this,'searchwp_short_circuit'), 10, 2 );

		add_filter( 'get_search_query', array($this,'get_search_query') );				
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
	 * A filter to set the layout to 2 column
	 *
	 */
	public function wp_head() {

		$search_slug = false;
		if(is_search()){
			$search_slug = 'display';

			$engine = get_query_var('engine');
			if(false !== $engine && 'default' !== $engine && '' !== $engine){
				$search_slug = $engine;	
			}

			$option_slug_1 = $option_slug_2 = 'search';

		}elseif(is_post_type_archive(array_keys($this->post_types)) || is_tax(array_keys($this->taxonomies))){
			$search_slug = get_post_type();

			$option_slug_1 = 'facets';
			$option_slug_2 = 'archive';
		}

		if(false !== $search_slug && false !== $this->options && isset($this->options[$search_slug]['enable_'.$option_slug_1])){
			$this->search_slug = $search_slug;

			remove_action( 'lsx_content_bottom', array( 'LSX_TO_Frontend', 'lsx_default_pagination' ) );

			add_action('lsx_content_top', array($this,'lsx_content_top'));
			add_action('lsx_content_bottom', array($this,'lsx_content_bottom'));

			if(isset($this->options[$this->search_slug][$option_slug_2.'_layout']) && '1c' !== $this->options[$this->search_slug][$option_slug_2.'_layout']){
				add_action('lsx_content_wrap_before', array($this,'search_sidebar'));		
				add_filter('lsx_sidebar_enable', array($this,'lsx_sidebar_enable'), 10, 1);		
			}elseif('1c' === $this->options[$this->search_slug][$option_slug_2.'_layout']){
				add_action('lsx_content_wrap_before', array($this,'search_sidebar'));
			}	
		}
	}

	/**
	 * Enques the assets.
	 */
	public function assets() {
		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$min = '';
		} else {
			$min = '.min';
		}

		wp_enqueue_script( 'lsx_to_search', LSX_TO_SEARCH_URL . 'assets/js/to-search' . $min . '.js', array( 'jquery' ), LSX_TO_SEARCH_VER, true );

		$params = apply_filters( 'lsx_to_search_js_params', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		));

		wp_localize_script( 'lsx_to_search', 'lsx_to_search_params', $params );

		wp_enqueue_style( 'lsx_to_search', LSX_TO_SEARCH_URL . 'assets/css/to-search.css', array(), LSX_TO_SEARCH_VER );
	}

	/**
	 * Redirect wordpress to the search template located in the plugin
	 *
	 * @param	$template
	 * @return	$template
	 */
	public function search_template_include( $template ) {
		
		if ( is_main_query() && is_search() ) {
			if ( /*'' == locate_template( array( 'search.php' ) ) &&*/ file_exists( LSX_TO_SEARCH_PATH.'templates/search.php' )) {
				$template = LSX_TO_SEARCH_PATH.'templates/search.php';
			}
		}
		return $template;
	}	

	/**
	 * Rewrite the search URL
	 */	
	public function pretty_search_redirect() {
		global $wp_rewrite,$wp_query;
		if ( !isset( $wp_rewrite ) || !is_object( $wp_rewrite ) || !$wp_rewrite->using_permalinks() )
			return;

		$search_base = $wp_rewrite->search_base;
		if ( is_search() && !is_admin() && strpos( $_SERVER['REQUEST_URI'], "/{$search_base}/" ) === false ) {
			$search_query = get_query_var( 's' );
			
			$engine = '';
			//If the search was triggered by a supplemental engine
			if(isset($_GET['engine']) && 'default' !== $_GET['engine']){
				$engine = $_GET['engine'];
				set_query_var('engine',$engine);
				$engine = array_search($engine,$this->post_type_slugs).'/';
			}

			$get_array = $_GET;

			if(is_array($get_array) && !empty($get_array)){

			    $vars_to_maintain = array();
			    foreach($get_array as $ga_key => $ga_value){
                    if(false !== strpos( $ga_key, 'fwp_' )){
						$vars_to_maintain[] = $ga_key.'='.$ga_value;
                    }
                }
            }

            $redirect_url = home_url( "/{$search_base}/". $engine . urlencode($search_query ));
            if(!empty($vars_to_maintain)){
                $redirect_url .= '?'.implode('&',$vars_to_maintain);
            }

			wp_redirect($redirect_url);
			exit();
		}
	}

	/**
	 * Parse the Query and trigger a search
	 */
	public function pretty_search_parse_query( $query ) {
		if ( is_search() && !is_admin() && $query->is_main_query() ) {
			$search_query = $query->get('s');

			$keyword_test = explode('/',$search_query);

			if(isset($this->post_type_slugs[$keyword_test[0]])) {
				$engine = $this->post_type_slugs[$keyword_test[0]];

				if (count($keyword_test) > 1) {

					$query->set('s', $keyword_test[1]);
					$query->set('engine', $engine);


					if (class_exists('SWP_Query')) {
						$additional_posts = new SWP_Query(
							array(
								's' => $keyword_test[1], // search query
								'engine' => $engine,
								'fields' => 'ids'
							)
						);

						if (!empty($additional_posts->posts)) {
							$query->set('s', '');
							$query->set('engine_keyword', $keyword_test[1]);
							$query->set('post__in', $additional_posts->posts);
						}
					} else {
						if ('default' !== $engine) {
							$query->set('post_type', $engine);
						}
					}
				} elseif (post_type_exists($engine)) {
					$query->set('post_type', $engine);
					$query->set('engine', $engine);
					$query->set('s', '');

				}
			}
		}
		return $query;
	}

	/**
	 * A filter to set the layout to 2 column
	 *
	 */
	public function lsx_layout($layout) {
		if(false !== $this->search_slug){
			if(is_search()){
				$slug = 'search';
				$id = 'search';
			}else{
				$slug='facets';
				$id = 'archive';
			}
			if(false !== $this->options && isset($this->options[$this->search_slug]['enable_'.$slug]) && isset($this->options[$this->search_slug][$id.'_layout']) && '' !== $this->options[$this->search_slug][$id.'_layout']){
				$layout = $this->options[$this->search_slug][$id.'_layout'];
			}
		}
		return $layout;
	}	

	/**
	 * Shortcircuit the main search if need be
	 */
	public function searchwp_short_circuit($maybe_short_circuit, $obj) {
		$search_query = get_query_var('s');
		$engine = get_query_var('engine');

		if(false !== $engine && '' !== $engine && 'default' !== $engine){
			$maybe_short_circuit = true;
		}	
		return $maybe_short_circuit;
	}	

	/**
	 * Register the global post types.
	 *
	 *
	 * @return    null
	 */	
	public function facet_sort_options( $options, $params ) {
		global $wp_query;
		
		unset($options['distance']);	

		$search_slug = false;
		$option_slug = false;
		
		if(is_search()){
			$option_slug = '';
			$engine = get_query_var('engine');
			
			if(false !== $engine && 'default' !== $engine && '' !== $engine){
				$search_slug = $engine;	
			} else {
				$search_slug = 'display';
			}
		}elseif(is_post_type_archive(array_keys($this->post_types))||is_tax(array_keys($this->taxonomies))){
			$search_slug = get_post_type();
			$option_slug = 'archive_';
		}

		if(('default' === $params['template_name'] || 'wp' === $params['template_name'])
			&& false !== $search_slug && false !== $this->options && isset($this->options[$search_slug]['enable_'.$option_slug.'price_sorting']) 
			&& 'on' === $this->options[$search_slug]['enable_'.$option_slug.'price_sorting']) {

			$options['price_asc'] = array(
					'label' => __( 'Price (Highest)', 'lsx' ),
					'query_args' => array(
							'orderby' => 'meta_value_num',
							'meta_key' => 'price',
							'order' => 'DESC',
					)
			);
		
			$options['price_desc'] = array(
					'label' => __( 'Price (Lowest)', 'lsx' ),
					'query_args' => array(
							'orderby' => 'meta_value_num',
							'meta_key' => 'price',						
							'order' => 'ASC',
					)
			);
		 
		}

		if(('default' === $params['template_name'] || 'wp' === $params['template_name'])
			&& false !== $search_slug && false !== $this->options && isset($this->options[$search_slug]['enable_'.$option_slug.'date_sorting']) 
			&& 'on' === $this->options[$search_slug]['enable_'.$option_slug.'date_sorting']) {

			// Do nothing
		 
		} else {
			unset($options['date_desc']);
			unset($options['date_asc']);
		}

		if(('default' === $params['template_name'] || 'wp' === $params['template_name'])
			&& false !== $search_slug && false !== $this->options && isset($this->options[$search_slug]['enable_'.$option_slug.'az_sorting'])
			&& 'on' === $this->options[$search_slug]['enable_'.$option_slug.'az_sorting']) {

			// Do nothing

		} else {
			unset($options['title_desc']);
			unset($options['title_asc']);
		}

		return $options;
	}		

	/**
	 * Filters the travel style main query
	 *
	 */
	public function price_sorting($query) {
		$search_slug = false;
		$option_slug = false;
		
		if(is_search()){
			$option_slug = '';
			$engine = get_query_var('engine');
			
			if(false !== $engine && 'default' !== $engine && '' !== $engine){
				$search_slug = $engine;	
			} else {
				$search_slug = 'display';
			}
		}elseif(is_post_type_archive(array_keys($this->post_types))||is_tax(array_keys($this->taxonomies))){
			$search_slug = get_post_type();
			$option_slug = 'archive_';
		}

		if (!is_admin() && $query->is_main_query() && false !== $search_slug && false !== $this->options) {


			/*if (isset($this->options[$search_slug]['enable_search']) && 'on' === $this->options[$search_slug]['enable_search']) {
				
				$query->set('posts_per_page', -1);
				$query->set('nopaging', true);
			}*/	

			if (isset($this->options[$search_slug]['enable_'.$option_slug.'price_sorting'])	&& 'on' === $this->options[$search_slug]['enable_'.$option_slug.'price_sorting']){

				$query->set('orderby', 'meta_value_num');
				$query->set('order', 'DESC');
				$query->set('meta_key', 'price');	
				
				if(isset($_GET['sort'])){
					$query->set('order', ucwords($_GET['sort']));
				}
			}			
		}

		return $query;
	}
	

	/**
	 * Outputs Search Sidebar.
	 *
	 */
	public function lsx_content_top() { 
		if(is_search()){
			$option_slug = '';
		}elseif(is_post_type_archive(array_keys($this->post_types)) || is_tax(array_keys($this->taxonomies))){
			$option_slug = 'archive_';
		}

		$show_map = false;

		if ( isset( $this->options[ $this->search_slug ][ $option_slug . 'layout_map' ] ) && ! empty( $this->options[ $this->search_slug ][ $option_slug . 'layout_map' ] ) ) {
			$show_map = true;
		}
		?>

        <?php do_action('lsx_to_search_top'); ?>

		<div class="facetwp-template">

		<?php
		if ( true === $show_map ) {
			echo '<ul class="nav nav-tabs">';
			echo '<li class="active"><a data-toggle="tab" href="#to-search-list"><i class="fa fa-list" aria-hidden="true"></i> ' . esc_html__( 'List', 'to-search' ) . '</a></li>';
			echo '<li><a data-toggle="tab" href="#to-search-map"><i class="fa fa-map-marker" aria-hidden="true"></i> ' . esc_html__( 'Map', 'to-search' ) . '</a></li>';
			echo '</ul>';
			echo '<div class="tab-content">';
			echo '<div id="to-search-list" class="tab-pane fade in active">';
		}
	}

	/**
	 * Outputs Search Sidebar.
	 *
	 */
	public function lsx_content_bottom() {
		if(is_search()){
			$option_slug = '';
		}elseif(is_post_type_archive(array_keys($this->post_types)) || is_tax(array_keys($this->taxonomies))){
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

		<?php do_action('lsx_to_search_bottom'); ?>
	<?php 
	}	

	/**
	 * Outputs Map.
	 */
	public function display_map() {
		global $lsx_to_maps_frontend;
		global $wp_query;
		
		if ( ! empty( $lsx_to_maps_frontend ) && count( $wp_query->posts ) > 0 ) {
			$ids = wp_list_pluck( $wp_query->posts, 'ID' );

			if ( ! empty( $ids ) ) {
				$args = array(
					'connections' => $ids,
					'type'        => 'cluster',
					'content'     => 'excerpt',
				);

				echo wp_kses_post( $lsx_to_maps_frontend->map_output( false, $args ) );
			}
		}
	}

	/**
	 * Outputs Search Sidebar.
	 *
	 */
	public function search_sidebar() {

		if(false !== $this->search_slug){
			if(is_search()){
				$option_slug = '';
				$facet_slug = '';
			}elseif(is_post_type_archive(array_keys($this->post_types)) || is_tax(array_keys($this->taxonomies))){
				$option_slug = 'archive_';
			}		
			?>
				<div id="secondary" class="facetwp-sidebar widget-area <?php echo esc_attr(lsx_sidebar_class()); ?>" role="complementary">

					<div class="row">

					<?php if(isset($this->options[$this->search_slug]['display_'.$option_slug.'result_count']) && 'on' === $this->options[$this->search_slug]['display_'.$option_slug.'result_count']) { ?>
						<div class="col-sm-12 col-xs-12 facetwp-results">
							<h3 class="title"><?php _e('Results','to-search'); ?> (<?php echo do_shortcode('[facetwp counts="true"]'); ?>) <button class="btn facetwp-results-clear-btn hidden" type="button" onclick="FWP.reset()">Clear</button></h3>
						</div>
					<?php } ?>
						
					<?php if(isset($this->options[$this->search_slug][$option_slug.'facets']) && is_array($this->options[$this->search_slug][$option_slug.'facets'])) { 

						foreach($this->options[$this->search_slug][$option_slug.'facets'] as $facet => $facet_useless) {
							if('a_z' === $facet) {continue; }
							if('search_form' === $facet){ ?>
								<div class="col-sm-12 col-xs-12 facetwp-form">
									<form class="banner-form" action="/" method="get">
										<div class="input-group">
											<input class="search-field form-control" name="s" type="search" placeholder="<?php _e('Search','to-search'); ?>..." autocomplete="off" value="<?php echo get_search_query() ?>">
											<?php if('display' !== $this->search_slug) { ?>
												<input name="engine" type="hidden" value="<?php echo $this->search_slug; ?>">
											<?php } ?>
											<span class="input-group-btn"><button class="search-submit btn cta-btn" type="submit"><?php _e('Search','to-search'); ?></button></span>
										</div>
									</form>	
								</div>
							<?php }elseif(isset($this->facet_data[$facet])){ ?>
								<div class="col-sm-12 col-xs-12">
									<h3 class="title"><?php echo $this->facet_data[$facet]['label']; ?></h3>
									<?php echo do_shortcode('[facetwp facet="'.$facet.'"]'); ?>
								</div>
							<?php }
						}
					} ?>	

					<?php if(isset($this->options[$this->search_slug]['display_'.$option_slug.'result_count']) && 'on' === $this->options[$this->search_slug]['display_'.$option_slug.'result_count'] && $this->options[$this->search_slug]['search_layout'] != '1c') { ?>
						<div class="col-sm-12 col-xs-12 facetwp-results">
							<h3 class="title"><?php _e('Results','to-search'); ?> (<?php echo do_shortcode('[facetwp counts="true"]'); ?>) <button class="btn facetwp-results-clear-btn hidden" type="button" onclick="FWP.reset()">Clear</button></h3>
						</div>
					<?php } ?>
					</div>
				</div>

			<?php
		}
	}

	/**
	 * Outputs the appropriate search form
	 */
	public function search_form( $atts = array() ){
		
		$classes = 'search-form to-search-form ';
		if(isset($atts['class'])){ $classes .= $atts['class']; }

		$placeholder = __('Where do you want to go?','to-search');
		if(isset($atts['placeholder'])){ $placeholder = $atts['placeholder']; }	

		$action = '/';
		if(isset($atts['action'])){ $action = $atts['action']; }	

		$method = 'get';
		if(isset($atts['method'])){ $method = $atts['method']; }	

		$button_label = __('Search','to-search');
		if(isset($atts['button_label'])){ $button_label = $atts['button_label']; }

		$button_class = 'btn cta-btn ';
		if(isset($atts['button_class'])){ $button_class .= $atts['button_class']; }	

		$engine = false;
		if(isset($atts['engine'])){ $engine = $atts['engine']; }

		$engine_select = false;
		if(isset($atts['engine_select'])){ $engine_select = true; }

		$display_search_field = true;
		if(isset($atts['search_field'])){ $display_search_field = (boolean) $atts['search_field']; }

		$facets = false;
		if(isset($atts['facets'])){ $facets = $atts['facets']; }

		$combo_box = false;
		if(isset($atts['combo_box'])){ $combo_box = true; }

		$return = '';

		ob_start(); ?>

			<?php do_action('lsx_search_form_before'); ?>

			<form class="<?php echo $classes; ?>" action="<?php echo $action; ?>" method="<?php echo $method; ?>">

			<?php do_action('lsx_search_form_top'); ?>

				<div class="input-group">

                    <?php if ( true === $display_search_field ) : ?>
                        <div class="field">
                            <input class="search-field form-control" name="s" type="search" placeholder="<?php echo $placeholder; ?>" autocomplete="off">
                        </div>
                    <?php endif; ?>

					<?php if (false !== $engine_select && false !== $engine && 'default' !== $engine ) :
						$engines = explode('|',$engine); ?>
                        <div class="field engine-select">
                            <div class="dropdown">
                                <?php
								$plural = 's';
								if('accommodation' === $engine[0]){
								    $plural = '';
								}
                                ?>
                                <button id="engine" data-selection="<?php echo $engines[0]; ?>" class="btn btn-dropdown dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo ucwords(str_replace('_',' ',$engines[0])).$plural; ?> <span class="caret"></span></button>
                                <ul class="dropdown-menu">
									<?php
									foreach($engines as $engine){

									    $plural = 's';
									    if('accommodation' === $engine){$plural = '';}
										echo '<li><a data-value="'.$engine.'" href="#">'.ucfirst(str_replace('_',' ',$engine)).$plural.'</a></li>';
									}
									?>
                                </ul>
                            </div>
                        </div>
					<?php endif; ?>

					<?php if(false !== $facets) {

					    $facets = explode("|",$facets);
                        if(!is_array($facets)){
							$facets = array($facets);
                        }

                        $field_class = 'field';

						if(false !== $combo_box){
							$this->combo_box($facets);
							$field_class .= ' combination-toggle hidden';
						}
                        foreach($facets as $facet){
							?>
                            <div class="<?php echo wp_kses_post($field_class); ?>">
								<?php
								$facet = FWP()->helper->get_facet_by_name( $facet );
								$values = $this->get_form_facet($facet['source']);
								$this->display_form_field('select',$facet,$values,$combo_box);
								?>
                            </div>
							<?php
                        }
                    } ?>

                    <div class="field">
                        <button class="<?php echo $button_class; ?>" type="submit"><?php echo $button_label; ?></button>
                    </div>

					<?php if (false === $engine_select && false !== $engine && 'default' !== $engine ) : ?>
						<input name="engine" type="hidden" value="<?php echo $engine; ?>">
					<?php endif; ?>
				</div>

			<?php do_action('lsx_search_form_bottom'); ?>	
				
			</form>	

			<?php do_action('lsx_search_form_after'); ?>
		<?php
		$return = ob_get_clean();

		return $return;
	}		

	/**
	 * Outputs closing facetWP div.
	 *
	 */
	public function lsx_sidebar_enable($return) {
		if(false !== $this->search_slug){
			$return = 0;
		}
		return $return;
	}

	/**
	 * Grabs the Values for the Facet in Question.
	 */
	protected function get_form_facet( $facet_source = false) {
	    global $wpdb;
	    $values = array();
		$response = $wpdb->get_results("
        SELECT  facet_value,facet_display_value
        FROM    {$wpdb->prefix}facetwp_index
        WHERE   facet_source = '{$facet_source}'
        ");

        if(!empty($response)){
            foreach($response as $re){
				$values[$re->facet_value] = $re->facet_display_value;
            }
        }
        asort($values);
        return $values;
	}


	/**
	 * Change FaceWP pagination HTML to be equal main pagination (WP-PageNavi)
	 */
	public function display_form_field( $type='select',$facet=array(), $values = array(), $combo = false ) {

	    if(empty($facet)){
	        return;
        }

		$source = 'fwp_'.$facet['name'];

	    switch($type){

            case 'select':?>
                <div class="dropdown <?php if(true === $combo) { echo 'combination-dropdown'; } ?>">
                    <button data-selection="0" class="btn btn-dropdown dropdown-toggle" type="button" id="<?php echo wp_kses_post($source); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
						<?php esc_attr_e('Select','to-search'); ?> <?php echo wp_kses_post($facet['label']); ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="<?php echo wp_kses_post($source); ?>">
                        <?php if(!empty($values)) { ?>

                            <li style="display: none;"><a class="default" data-value="0" href="#"><?php esc_attr_e('Select ','to-search'); ?> <?php echo wp_kses_post($facet['label']); ?></a></li>

                            <?php foreach($values as $key => $value) { ?>
                                <li><a data-value="<?php echo wp_kses_post($key); ?>" href="#"><?php echo wp_kses_post($value); ?></a></li>
                            <?php } ?>
                        <?php }else{ ?>
                            <li><a data-value="0" href="#"><?php esc_attr_e('Please re-index your facets.','to-search'); ?></a></li>
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
                <button data-selection="0" class="btn btn-dropdown dropdown-toggle btn-combination" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <?php esc_attr_e('Select','to-search'); ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">

                    <li style="display: none;"><a class="default" data-value="0" href="#"><?php esc_attr_e('Select ','to-search'); ?></a></li>

                    <?php foreach($facets as $facet) {
                        $facet = FWP()->helper->get_facet_by_name( $facet );
                        ?>
                        <li><a data-value="fwp_<?php echo wp_kses_post($facet['name']); ?>" href="#"><?php echo wp_kses_post($facet['label']); ?></a></li>
                    <?php } ?>
                </ul>
            </div>
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
	        $output .= '<div class="wp-pagenavi-wrapper facetwp-custom">';
	        $output .= '<div class="lsx-breaker"></div>';
	        $output .= '<div class="wp-pagenavi">';
	        $output .= '<span class="pages">Page '. $page .' of '. $total_pages .'</span>';

	        if ( 1 < $page ) {
	            $output .= '<a class="previouspostslink facetwp-page" rel="prev" data-page="'. ( $page - 1 ) .'">«</a>';
	        }

	        $temp = false;
	        
	        for ( $i = 1; $i <= $total_pages; $i++ ) {
	            if ( $i == $page ) {
	                $output .= '<span class="current">'. $i .'</span>';
	            } elseif ( ( $page - 2 ) < $i && ( $page + 2 ) > $i ) {
	                $output .= '<a class="page larger facetwp-page" data-page="'. $i .'">'. $i .'</a>';
	            } elseif ( ( $page - 2 ) >= $i && $page > 2 ) {
	                if ( ! $temp ) {
	                    $output .= '<span>...</span>';
	                    $temp = true;
	                }
	            } elseif ( ( $page + 2 ) <= $i && ( $page + 2 ) <= $total_pages ) {
	                $output .= '<span>...</span>';
	                break;
	            }
	        }

	        if ( $page < $total_pages ) {
	            $output .= '<a class="nextpostslink facetwp-page" rel="next" data-page="'. ( $page + 1 ) .'">»</a>';
	        }

	        $output .= '</div>';
	        $output .= '</div>';
	    }

	    return $output;
	}
	

	/**
	 * Change FaceWP result count HTML
	 */
	function facetwp_result_count( $output, $params ) {
		$output = $params['total'];
		return $output;
	}	


	/**
	 * Change FaceWP result count HTML
	 */
	function get_search_query( $keyword ) {
		$engine = get_query_var('engine_keyword');
		if(false !== $engine && '' !== $engine && 'default' !== $engine){
			$keyword = $engine;
		}
		$keyword = str_replace( '+', ' ', $keyword );
		return $keyword;
	}

	public function destination_facet_html( $output, $params ) {
	    $possible_keys = array('destination_to_accommodation','destination_to_tour','destination_to_special','destination_to_activity','destination','destinations','destination_to_review','destination_to_vehicle');

		if ( in_array($params['facet']['name'],$possible_keys) ) {
			$output = $this->destination_facet_render($params);
		}
		return $output;
	}

	/**
	 * Generate the facet HTML
	 */
	function destination_facet_render( $params ) {
		$facet = $params['facet'];

		$output = '';
		$values = (array) $params['values'];
		$selected_values = (array) $params['selected_values'];
		$soft_limit = empty( $facet['soft_limit'] ) ? 0 : (int) $facet['soft_limit'];

		$destination_ids = array();
		foreach($values as $key => $result){
			$destination_ids[] = $result['facet_value'];
        }
        $countries = apply_filters('lsx_to_parents_only',$destination_ids);

        $options = array();
        $sorted_values = array();

		/*if(!empty($selected_values)) {
			//sort the options so
			foreach ($values as $key => $result) {
				$sorted_values[$key] = $result;
		    }
			$values = $sorted_values;
		}*/

		$regions = $values;

		$key = 0;
		foreach ( $values as $key => $result ) {

			//Check to see if we should display the countries
			if (!in_array($result['facet_value'], $countries)) {
				continue;
			}

			$options[] = $this->format_single_facet($key,$result,$selected_values,$soft_limit);

			//if a country is selected, then run through and add in the regions.
			if(!empty($selected_values) && in_array( $result['facet_value'], $selected_values )){
                foreach($regions as $region_key => $region_value){
                    /*print_r(wp_get_post_parent_id($region_value['facet_value']));
					print_r(' - '.$result['facet_value']);
                    print_r('<br />');*/
					if((String)wp_get_post_parent_id($region_value['facet_value']) === (String)$result['facet_value']){
						$options[] = $this->format_single_facet($region_key,$region_value,$selected_values,$soft_limit,true);
                    }
                }
            }
		}

		if ( 0 < $soft_limit && $soft_limit <= $key ) {
			$output .= '</div>';
			$output .= '<a class="facetwp-toggle">' . __( 'See {num} more', 'fwp' ) . '</a>';
			$output .= '<a class="facetwp-toggle facetwp-hidden">' . __( 'See less', 'fwp' ) . '</a>';
		}

		$output = implode('',$options);

		return $output;
	}

	function format_single_facet($key,$result,$selected_values,$soft_limit,$region = false){
		$temp_html = '';
		if ( 0 < $soft_limit && $key == $soft_limit ) {
			$temp_html .= '<div class="facetwp-overflow facetwp-hidden">';
		}
		$selected = in_array( $result['facet_value'], $selected_values ) ? ' checked' : '';
		$selected .= ( 0 == $result['counter'] && '' == $selected ) ? ' disabled' : '';
		$selected .= $region ? ' region' : '';

		$temp_html .= '<div class="facetwp-checkbox' . $selected . '" data-value="' . $result['facet_value'] . '">';
		$temp_html .= $result['facet_display_value'] . ' <span class="facetwp-counter">(' . $result['counter'] . ')</span>';
		$temp_html .= '</div>';
		return $temp_html;
    }
}
global $lsx_to_search;
$lsx_to_search = new LSX_TO_Search_Frontend();