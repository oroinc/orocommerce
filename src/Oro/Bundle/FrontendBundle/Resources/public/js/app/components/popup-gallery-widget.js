define(function(require) {
'use strict';

    var PopupGalleryWidget;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');
    require('slick');

    PopupGalleryWidget = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            bindWithSlider: '.product-view-media__gallery',
            imageOptions: {
                fade: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: true,
                lazyLoad: 'progressive',
                asNavFor: null,
                adaptiveHeight: false,
                dots: true,
                infinite: true
            },
            navOptions: {
                slidesToShow: 7,
                slidesToScroll: 7,
                asNavFor: null,
                centerMode: true,
                focusOnSelect: true,
                arrows: true,
                dots: false,
                variableWidth: true,
                infinite: true
            }
        },

        /**
         * @property {number}
         */
        animationDuration: 400,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options._sourceElement;
            this.$gallery = this.$el.find('[data-gallery-images]');
            this.$thumbnails = this.$el.find('[data-gallery-thumbnails]');

            if (!this.options.navOptions.asNavFor) {
                this.options.navOptions.asNavFor = '.' + this.$gallery.attr('class');
            }
            if (!this.options.imageOptions.asNavFor) {
                this.options.imageOptions.asNavFor = '.' + this.$thumbnails.attr('class');
            }

            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;
            $('[data-trigger-gallery-open]').on('click', function() {
                self.renderImages();
                self.renderThumbnails();
                self.setDependentSlide();
                $('body').addClass('gallery-popup-opened');
                self.$el.addClass('show-gallery-popup');

                self.onOpen();
            });
            $('[data-trigger-gallery-close]').on('click', function() {
                $('body').removeClass('gallery-popup-opened');
                self.$el.removeClass('show-gallery-popup');

                setTimeout(function() {
                    self.setDependentSlide();
                }, self.animationDuration);

                self.onClose();
            });
        },

        onOpen: function() {
            var self = this;
            $(document).on('keydown.popup-gallery-widget', function(e) {
                if (e.keyCode === 37) {
                    self.$gallery.slick('slickPrev');
                }
                if (e.keyCode === 39) {
                    self.$gallery.slick('slickNext');
                }
            });
        },

        onClose: function() {
            $(document).off('keydown.popup-gallery-widget');
        },

        renderImages: function() {
            this.$gallery.not('.slick-initialized').slick(
              this.options.imageOptions
            );
        },

        renderThumbnails: function() {
            var nav = this.$thumbnails;
            if (nav) {
                nav.not('.slick-initialized').slick(
                  this.options.navOptions
                );
                this.checkSlickNoSlide();
            }
            this.onResize();
        },

        setDependentSlide: function() {
            var dependentSlider = this.options.bindWithSlider;
            var dependentSliderItems = $(dependentSlider).find('.slick-slide');
            if (dependentSlider && dependentSliderItems.length) {
                var dependentSlide = $(dependentSlider).slick('slickCurrentSlide');
                this.$gallery.slick('slickGoTo', dependentSlide, true);
                this.$thumbnails.slick('slickGoTo', dependentSlide, true);
            }
        },

        checkSlickNoSlide: function() {
            var elm = this.$thumbnails;

            if (elm.length) {
                var getSlick = elm.slick('getSlick');
                if (elm && getSlick.slideCount <= getSlick.options.slidesToShow) {
                    elm.addClass('slick-no-slide');
                } else {
                    elm.removeClass('slick-no-slide');
                }
            }
        },

        refreshPositions: function() {
            this.$gallery.slick('setPosition');
            this.$thumbnails.slick('setPosition');
        },

        onResize: function() {
            var self = this;
            $(window).resize(function() {
                var wHeight =  $(this).height();
                var wWidth =  $(this).width();

                if (wWidth >= 993 && wHeight <= 730) {
                    self.refreshPositions();

                    //Delay before run refreshPositions need when user back to normal size of window
                    setTimeout(function() {
                        self.refreshPositions();
                    }, 500);
                }
            });
        }
    });

    return PopupGalleryWidget;
});
