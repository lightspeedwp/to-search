<?php
/**
 * Pluggable functions for the Search, allowing you to change the layout.
 *
 * @package   	tour-operator
 * @subpackage 	layout
 * @license   	GPL3
 */


add_action('lsx_to_search_top','lsx_to_search_top',10);
add_action('lsx_to_search_bottom','lsx_to_search_bottom',10);

/**
 * Adds the template tags to the bottom of the content-tour.php
 *
 * @package 	tour-operator
 * @subpackage	template-tag
 * @category 	tour
 */

function lsx_to_search_top()
{
    global $lsx_to_search;
    if (is_search()) {
        $option_slug = '';
    } elseif (is_post_type_archive(array_keys($lsx_to_search->post_types)) || is_tax(array_keys($lsx_to_search->taxonomies))) {
        $option_slug = 'archive_';
    }else{
		return '';
    }
    ?>
    <div id="facetwp-top">

            <div class="row facetwp-top-row-1">
                <div class="col-md-12">
					<?php
                    if(!isset($lsx_to_search->options[$lsx_to_search->search_slug]['disable_'.$option_slug . 'all_sorting']) || 'on' !== $lsx_to_search->options[$lsx_to_search->search_slug]['disable_'.$option_slug . 'all_sorting']){ ?>
                        <?php echo do_shortcode('[facetwp sort="true"]'); ?>
                    <?php } ?>
                    <?php echo do_shortcode('[facetwp per_page="true"]'); ?>
                </div>
            </div>

        <div class="row facetwp-top-row-2">
            <div class="col-md-8">
                <?php if (isset($lsx_to_search->options[$lsx_to_search->search_slug][$option_slug . 'facets']) && is_array($lsx_to_search->options[$lsx_to_search->search_slug][$option_slug . 'facets']) && array_key_exists('a_z', $lsx_to_search->options[$lsx_to_search->search_slug][$option_slug . 'facets'])) {
                    echo do_shortcode('[facetwp facet="a_z"]');
                } ?>
            </div>
            <div class="col-md-4">
                <?php echo do_shortcode('[facetwp pager="true"]'); ?>
            </div>
        </div>
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
function lsx_to_search_bottom()
{
    global $lsx_to_search;
    if (is_search()) {
        $option_slug = '';
    } elseif (is_post_type_archive(array_keys($lsx_to_search->post_types)) || is_tax(array_keys($lsx_to_search->taxonomies))) {
        $option_slug = 'archive_';
    }
    ?>
    <div id="facetwp-bottom">
        <div class="row facetwp-bottom-row-1">
            <div class="col-md-8">
                <?php if (isset($lsx_to_search->options[$lsx_to_search->search_slug][$option_slug . 'facets']) && is_array($lsx_to_search->options[$lsx_to_search->search_slug][$option_slug . 'facets']) && array_key_exists('a_z', $lsx_to_search->options[$lsx_to_search->search_slug][$option_slug . 'facets'])) {
                    echo do_shortcode('[facetwp facet="a_z"]');
                } ?>
            </div>
            <div class="col-md-4">
                <?php echo do_shortcode('[facetwp pager="true"]'); ?>
            </div>
        </div>
    </div>
    <?php
}