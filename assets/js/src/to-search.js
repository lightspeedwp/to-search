'use strict';

var LSX_TO_Search = {

	facetWpLoadFirstTime: false,
	currentForm: false,

	initThis: function(windowWidth) {
		this.currentForm = jQuery('.to-search-form');

		this.onChangeTab_Map();
		this.onFacetWpLoad();

		if (windowWidth < 768) {
			this.mobileFilters();
		}

		if (undefined != this.currentForm) {
			this.watchSubmit();

			if (undefined != this.currentForm.find('.search-field')) {
				this.watchSearchInput();
			}

			if (undefined != this.currentForm.find('.btn-dropdown')) {
				this.watchDropdown();
			}
		}
	},

	onChangeTab_Map: function() {
		jQuery('a[data-toggle="tab"][href="#to-search-map"]').on('shown.bs.tab', LSX_TO_Search.reloadMap);
	},

	onFacetWpLoad: function() {
		this.facetWpLoadFirstTime = false;

		jQuery(window).on('facetwp-loaded', function() {
			jQuery('body').removeClass('facetwp-loading-body');

			jQuery('#secondary, #primary').css({
				'opacity': '',
				'pointer-events': ''
			});

			if (FWP.build_query_string() == '') {
				jQuery('.facetwp-results-clear-btn').addClass('hidden');
			} else {
				jQuery('.facetwp-results-clear-btn').removeClass('hidden');
			}

			jQuery.each(FWP.settings, function(key, val) {
				if ('price' === key || 'duration' === key) {
					var $parent = jQuery('.facetwp-facet-' + key).closest('.facetwp-item');
					(val.range.min === val.range.max) ? $parent.addClass('hidden') : $parent.removeClass('hidden');
				}
			});

			jQuery.each(FWP.settings.num_choices, function(key, val) {
				var $parent = jQuery('.facetwp-facet-' + key).closest('.facetwp-item');
				(0 === val) ? $parent.addClass('hidden') : $parent.removeClass('hidden');
			});

			if ( 0 < jQuery( '.facetwp-filters-wrap .row .facetwp-item').not('.hidden').length ) {
				if ( 0 < jQuery( '.facetwp-filters-wrap .row .facetwp-item').not('.hidden').first().length ) {
					jQuery( '.facetwp-filters-wrap .row .facetwp-item').not('.hidden').find('button.facetwp-collapse').first().attr('aria-expanded','true');
					jQuery( '.facetwp-filters-wrap .row .facetwp-item').not('.hidden').find('.collapse').first().addClass('in');
				}
			}

			jQuery('.facetwp-checkbox.checked').each(function() {
				var parent = jQuery(this).parents('.facetwp-item');
				parent.find('button.facetwp-collapse').attr('aria-expanded','true');
				parent.find('.collapse').addClass('in');
			});
			

			if (true === LSX_TO_Search.facetWpLoadFirstTime) {
				if ( undefined !== FWP && undefined !== FWP.settings && undefined !== FWP.settings.pager && undefined !== FWP.settings.pager.total_rows ) {
					if ( 0 === FWP.settings.pager.total_rows ) {
						jQuery('body').addClass('search-no-results');
					}
				}
				return;
			}
			LSX_TO_Search.facetWpLoadFirstTime = true;

			LSX_TO_Search.checkForFacets();

			FWP.fetch_data();
			if ( undefined !== FWP && undefined !== FWP.settings && undefined !== FWP.settings.pager && undefined !== FWP.settings.pager.total_rows ) {
				if ( 0 === FWP.settings.pager.total_rows ) {
					jQuery('body').addClass('search-no-results');
				}
			}

			if ( undefined !== lsx_to_search_params && true === lsx_to_search_params.scrollOnLoad ) {
				var scrollTop = jQuery('.facetwp-facet').length > 0 ? jQuery('.facetwp-facet').offset().top : jQuery('.facetwp-template').offset().top;
				scrollTop -= 250;
				jQuery('html, body').animate({scrollTop: scrollTop}, 400);
			}

			LSX_TO_Search.onChangeTab_Map();

			if ('' === jQuery('.lsx-map-preview').html()) {
				LSX_TO_Search.reloadMap();
			}
		});

		jQuery(window).on('facetwp-refresh', function() {
			jQuery('body').addClass('facetwp-loading-body');

			jQuery('#secondary, #primary').css({
				'opacity': 0.5,
				'pointer-events': 'none'
			});
		});
	},

	checkForFacets: function() {
		if ( undefined !== lsx_to_search_params && false !== lsx_to_search_params.facets && '' !== lsx_to_search_params.facets ) {
			for (var key in lsx_to_search_params.facets) {
				if (lsx_to_search_params.facets.hasOwnProperty(key)) {
					if ( undefined !== FWP.facets[key] ) {
						console.log(lsx_to_search_params.facets[key]);
						FWP.facets[key] = lsx_to_search_params.facets[key];
					}
				}
			}
		}
	},

	mobileFilters: function() {
		if (jQuery('.facetwp-template').length > 0) {
			jQuery('.facetwp-filters-wrap').slideAndSwipe();

			FWP.auto_refresh = false;

			jQuery('.ssm-close-btn').on('click', function() {
				FWP.is_refresh = true;
				jQuery(document).trigger('facetwp-refresh');
				FWP.fetch_data();
				FWP.is_refresh = false;
			});

			jQuery('.ssm-apply-btn').on('click', function() {
				FWP.refresh();
			});

			jQuery(document).on('facetwp-refresh', function() {
				jQuery('.facetwp-filters-wrap').each(function() {
					jQuery(this).data('plugin_slideAndSwipe').hideNavigation();
				});
			});
		}
	},

	reloadMap: function() {
		if (undefined !== LSX_TO_Maps) {
			LSX_TO_Maps.initThis();
		}
	},

	watchDropdown: function() {
		var $this = this;

		jQuery(this.currentForm).find('.dropdown-menu').on('click','a',function(event) {
			event.preventDefault();

			jQuery(this).parents('.dropdown').find('.btn-dropdown').attr('data-selection',jQuery(this).attr('data-value'));
			jQuery(this).parents('.dropdown').find('.btn-dropdown').html(jQuery(this).html()+' <span class="caret"></span>');

			if (jQuery(this).hasClass('default')) {
				jQuery(this).parent('li').hide();
			} else {
				jQuery(this).parents('ul').find('.default').parent('li').show();
			}

			if (jQuery(this).parents('.field').hasClass('combination-dropdown')) {
				$this.switchDropDown(jQuery(this).parents('.dropdown'));
			}

			if (jQuery(this).parents('.field').hasClass('engine-select')) {
				$this.switchEngine(jQuery(this).parents('.dropdown'));
			}
		});
	},

	watchSubmit: function() {
		var currentForm = this.currentForm;

		jQuery(this.currentForm).on('submit',function(event) {
			var has_facets = false;

			if (undefined != jQuery(this).find('.btn-dropdown:not(.btn-combination)')) {
				has_facets = true;

				jQuery(this).find('.btn-dropdown:not(.btn-combination)').each(function() {
					var value = jQuery(this).attr('data-selection');

					if (0 != value || '0' != value) {
						var input = jQuery("<input>")
							.attr("type", "hidden")
							.attr("name", jQuery(this).attr('id'))
							.val(value);

						jQuery(currentForm).append(jQuery(input));
					}
				});
			}

			//Check if there is a keyword.
			/*if (false == has_facets && undefined != jQuery(this).find('.search-field') && '' == jQuery(this).find('.search-field').val()) {
				jQuery(this).find('.search-field').addClass('error');
				event.preventDefault();
			}*/
		});
	},

	watchSearchInput: function() {
		jQuery(this.currentForm).find('.search-field').on('keyup',function(event) {
			if (jQuery(this).hasClass('error')) {
				jQuery(this).removeClass('error');
			}
		});
	},

	switchDropDown: function(dropdown) {
		var id = dropdown.find('button').attr('data-selection');

		if (dropdown.parents('form').find('.combination-toggle.selected').length > 0) {
			dropdown.parents('form').find('.combination-toggle.selected button').attr('data-selection','0');
			var default_title = dropdown.parents('form').find('.combination-toggle.selected a.default').html();
			dropdown.parents('form').find('.combination-toggle.selected button').html(default_title+' <span class="caret"></span>');
			dropdown.parents('form').find('.combination-toggle.selected').removeClass('selected').addClass('hidden');
		}

		dropdown.parents('form').find('#'+id).parents('.combination-toggle').removeClass('hidden').addClass('selected');
	},

	switchEngine: function(dropdown) {
		var id = dropdown.find('button').attr('data-selection');

		if (dropdown.parents('form').find('.combination-toggle.selected').length > 0) {
			dropdown.parents('form').find('.combination-toggle.selected button').attr('data-selection','0');
			var default_title = dropdown.parents('form').find('.combination-toggle.selected a.default').html();
			dropdown.parents('form').find('.combination-toggle.selected button').html(default_title+' <span class="caret"></span>');
			dropdown.parents('form').find('.combination-toggle.selected').removeClass('selected').addClass('hidden');
		}

		dropdown.parents('form').attr('engine');
	}

};

jQuery(function() {
	var windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
	LSX_TO_Search.initThis(windowWidth);
});
