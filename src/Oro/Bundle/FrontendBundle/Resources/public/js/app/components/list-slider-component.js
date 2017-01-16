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
                $(this.options._sourceElement).slick(this.options);
            }

            this.onChange();
        },

        onChange: function() {
            this.$el.on('beforeChange', function(event, slick, currentSlide, nextSlide) {
                var $activeImage = $(this).find('.slick-slide[data-slick-index=' + nextSlide + '] img');
                var $images = $(this).find('.slick-slide img');

                $images.trigger('slider:activeImage', $activeImage.get(0));
                $images.trigger('slider:beforeChange', $activeImage.get(0));
            });
        }
    });

    return ContentSliderComponent;
});
