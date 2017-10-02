/*
 *  Slide and swipe menu - v1.0.0
 *  https://github.com/nandotess/slide-and-swipe-menu
 *
 *  Made by Fernando Tessmann
 *  Under MIT License
 *
 *  Forked from: https://github.com/JoanClaret/slide-and-swipe-menu
 *  jQuery plugin development boilerplate: https://github.com/jquery-boilerplate/jquery-boilerplate
 */

;(function($, window, document, undefined) {

	'use strict';

	var pluginName = 'slideAndSwipe',
		defaults = {
			triggerOnTouchEnd: true,
			swipeStatus: function() {},
			allowPageScroll: 'vertical',
			threshold: 100,
			excludedElements: 'label, button, input, select, textarea, .noSwipe',
			speed: 250
		};

	function Plugin(element, options) {
		this._defaults = defaults;
		this._name = pluginName;
		this.element = element;
		this.settings = $.extend({}, defaults, options);
		this.init();
	}

	$.extend(Plugin.prototype, {

		/**
		 * Plugin initialiser
		 */
		init: function() {
			var self;

			this.nav = $(this.element);
			this.navWidth = - this.nav.outerWidth();
			this.transInitial = this.navWidth;
			this.navRel = this.nav.attr('rel');

			if (this.navRel) {
				this.navRel = '[rel="' + this.navRel + '"]';
			} else {
				this.navRel = '';
			}

			this.navCta = $('.ssm-toggle-nav' + this.navRel);
			this.overlay = $('.ssm-overlay' + this.navRel);
			this.closeCta = $('.ssm-close-btn' + this.navRel);

			this.settings.swipeStatus = this.swipeStatus;
			this.nav.swipe(this.settings);

			self = this;

			this.navCta.click(function(e) {
				if (self.nav.hasClass('ssm-nav-visible')) {
					self.hideNavigation();
				} else {
					self.showNavigation();
				}

				e.preventDefault();
			});

			$(window).on('resize', function() {
				self.navWidth = - self.nav.outerWidth();
				self.hideNavigation();
			});
		},

		/**
		 * Destroy the instance
		 */
		destroy: function() {
			this.nav.removeData('plugin_' + pluginName);
		},

		/**
		 * Browser detect: Safari
		 */
		isSafari: function() {
			return /Safari/.test(navigator.userAgent) && /Apple Computer/.test(navigator.vendor);
		},

		/**
		 * Browser detect: Chrome
		 */
		isChrome: function() {
			return /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
		},

		/**
		 * Catch each phase of the swipe.
		 * move : we drag the navigation
		 * cancel : open navigation
		 * end : close navigation
		 */
		swipeStatus: function(event, phase, direction, distance) {
			var self = this.data('plugin_' + pluginName);

			if (phase == 'start') {
				if (self.nav.hasClass('ssm-nav-visible')) {
					self.transInitial = 0;
				} else {
					self.transInitial = self.navWidth;
				}
			}

			var mDistance;

			if (phase == 'move' && (direction == 'left')) {
				if(self.transInitial < 0) {
					mDistance = self.transInitial - distance;
				} else {
					mDistance = - distance;
				}

				self.scrollNav(mDistance, 0);
			} else if (phase == 'move' && direction == 'right') {
				if (self.transInitial < 0) {
					mDistance = self.transInitial + distance;
				} else {
					mDistance = distance;
				}

				self.scrollNav(mDistance, 0);
			} else if (phase == 'cancel' && (direction == 'left') && self.transInitial === 0) {
				self.scrollNav(0, self.settings.speed);
			} else if (phase == 'end' && (direction == 'left')) {
				self.hideNavigation();
			} else if ((phase == 'end' || phase == 'cancel') && (direction == 'right')) {
				console.log('end');
			}
		},

		/**
		 * Manually update the position of the nav on drag
		 */
		scrollNav: function(distance, duration) {
			this.nav.css('transition-duration', (duration / 1000).toFixed(1) + 's');

			if (distance >= 0) {
				distance = 0;
			}

			if (distance <= this.navWidth) {
				distance = this.navWidth;
			}

			if (this.isSafari() || this.isChrome()) {
			   this.nav.css('-webkit-transform', 'translate(' + distance + 'px, 0)');
			} else{
			   this.nav.css('transform', 'translate(' + distance + 'px, 0)');
			}

			if (distance == '0') {
				this.navCta.addClass('ssm-nav-visible');
				$('html').addClass('ssm-nav-is-open');
				this.overlay.fadeIn();
				this.closeCta.fadeIn();
			}
		},

		/**
		 * Close by click on burger icon
		 */
		hideNavigation: function() {
			this.nav.removeClass('ssm-nav-visible');
			this.scrollNav(this.navWidth, this.settings.speed);
			$('html').removeClass('ssm-nav-is-open');
			this.overlay.fadeOut();
			this.closeCta.fadeOut();
		},

		/**
		 * Open by click on burger icon
		 */
		showNavigation: function() {
			this.nav.addClass('ssm-nav-visible');
			this.scrollNav(0, this.settings.speed);
		}

	});

	$.fn[pluginName] = function(options) {
		return this.each(function() {
			if (! $.data(this, 'plugin_' + pluginName)) {
				$.data(this, 'plugin_' + pluginName, new Plugin(this, options));
			}
		});
	};

})(jQuery, window, document);
