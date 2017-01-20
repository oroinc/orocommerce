define(function(require) {
    'use strict';

    var ContentSliderComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var tools = require('oroui/js/tools');
    var $ = require('jquery');
    var _ = require('underscore');
    require('slick');

    ContentSliderComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            mobileEnabled: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay: false,
            autoplaySpeed: 2000,
            arrows: !tools.isMobile(),
            dots: false,
            infinite: false
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options._sourceElement;

            if (this.options.mobileEnabled) {
                $(this.$el).slick(this.options);
            }

            this.onCreate();
            this.onChange();
        },

        onCreate: function() {
            var self = this;
            this.$el.find('.slick-slide').on('zoom-widget:created', 'img', function() {
                var nextSlide = $(self.$el).slick('slickCurrentSlide');
                self.changeHandler(self.$el, nextSlide, 'slider:currentImage');
            });
        },

        onChange: function() {
            var self = this;
            this.$el.on('beforeChange', function(event, slick, currentSlide, nextSlide) {
                self.changeHandler(this, nextSlide, 'slider:activeImage');
            });
        },

        changeHandler: function(slick, nextSlide, eventName) {
            var $activeImage = $(slick).find('.slick-slide[data-slick-index=' + nextSlide + '] img');
            $(slick).find('.slick-slide img').trigger(eventName, $activeImage.get(0));
        }
    });

    return ContentSliderComponent;
});
