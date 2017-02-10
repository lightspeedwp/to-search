LSX_TO_Search = {
	initThis: function() {
		this.fixMapVisual();
	},

	fixMapVisual: function() {
		jQuery('a[data-toggle="tab"][href="#to-search-map"]').on('shown.bs.tab', function(e) {
			if (undefined !== LSX_TO_Maps) {
				LSX_TO_Maps.resizeThis();
				LSX_TO_Maps.setBounds();
			}
		});
	},
};

jQuery(document).ready( function() {
	LSX_TO_Search.initThis();
});
