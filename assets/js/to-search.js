'use strict';

var LSX_TO_Search = {

	facetWpLoadFirstTime: false,
	
	initThis: function() {
		this.onChangeTab_Map();
		this.onFacetWpLoad();
	},

	onChangeTab_Map: function() {
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

	reloadMap: function() {
		if (undefined !== LSX_TO_Maps) {
			LSX_TO_Maps.initThis();
			LSX_TO_Maps_Styles.changeMapStyles();
		}
	},

};

jQuery(function() {
	LSX_TO_Search.initThis();
});
