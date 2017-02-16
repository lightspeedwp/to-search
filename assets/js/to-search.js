LSX_TO_Search = {

	facetWpLoadFirstTime: false,

	currentForm:false,
	
	initThis: function() {

		currentForm = jQuery('.to-search-form');

		this.onChangeTab_Map();
		this.onFacetWpLoad();

        console.log(currentForm);
		if(undefined != currentForm){

            this.watchSubmit();

            if(undefined != currentForm.find('.search-field')){
                this.watchSearchInput();
            }

            if(undefined != currentForm.find('.btn-dropdown')){
                this.watchDropdown();
            }

		}
	},

	onChangeTab_Map: function() {
		//jQuery('a[data-toggle="tab"][href="#to-search-map"]').on('shown.bs.tab', LSX_TO_Search.fixMapVisual);
		jQuery('a[data-toggle="tab"][href="#to-search-map"]').on('shown.bs.tab', LSX_TO_Search.reloadMap);
	},

	onFacetWpLoad: function() {
		if (typeof FWP !== 'undefined') {
			FWP.loading_handler = function() {
				jQuery('body').addClass('facetwp-loading-body');
				jQuery('#secondary, #primary').css('opacity', 0.5);
			};
		}

		this.facetWpLoadFirstTime = false;

		jQuery(document).on('facetwp-loaded', function() {
			jQuery('body').removeClass('facetwp-loading-body');
			jQuery('#secondary, #primary').css('opacity', '');

			if (! LSX_TO_Search.facetWpLoadFirstTime) {
				LSX_TO_Search.facetWpLoadFirstTime = true;
				return;
			}
			
			var scrollTop = jQuery('.facetwp-template').offset().top - 250;
			jQuery('html, body').animate({scrollTop: scrollTop}, 400);

			LSX_TO_Search.onChangeTab_Map();

			if ('' === jQuery('.lsx-map-preview').html()) {
				LSX_TO_Search.reloadMap();
			}
		});
	},

	//fixMapVisual: function() {
	//	if (undefined !== LSX_TO_Maps) {
	//		LSX_TO_Maps.resizeThis();
	//		LSX_TO_Maps.setBounds();
	//	}
	//},

	reloadMap: function() {
		if (undefined !== LSX_TO_Maps) {
			LSX_TO_Maps.initThis();
			LSX_TO_Maps_Styles.changeMapColors();
		}
	},

    watchDropdown: function() {
		jQuery(currentForm).find('.dropdown-menu').on('click','a',function(event){
			event.preventDefault();
			jQuery(this).parents('.dropdown').find('.btn-dropdown').attr('data-selection',jQuery(this).attr('data-value'));
            jQuery(this).parents('.dropdown').find('.btn-dropdown').html(jQuery(this).html()+' <span class="caret"></span>');

            if(jQuery(this).hasClass('default')){
                jQuery(this).parent('li').hide();
			}else{
                jQuery(this).parents('ul').find('.default').parent('li').show();
			}
		});
    },

    watchSubmit: function() {
        jQuery(currentForm).on('submit',function(event){

			if(undefined != jQuery(this).find('.btn-dropdown')){
                jQuery(this).find('.btn-dropdown').each(function(){
                	var value = jQuery(this).attr('data-selection');

                	if(0 != value || '0' != value){
                        var input = jQuery("<input>")
                            .attr("type", "hidden")
                            .attr("name", jQuery(this).attr('id'))
                            .val(value);

                        jQuery(currentForm).append(jQuery(input));
					}
				});
			}

			//Check if there is a keyword.
			if(undefined != jQuery(this).find('.search-field') && '' == jQuery(this).find('.search-field').val()){
                jQuery(this).find('.search-field').addClass('error');
                event.preventDefault();
			}
        });
    },
    watchSearchInput: function() {
        jQuery(currentForm).find('.search-field').on('keyup',function(event){
        	if(jQuery(this).hasClass('error')){
                jQuery(this).removeClass('error');
			}
        });
    }
};

jQuery(function() {
	LSX_TO_Search.initThis();
});
