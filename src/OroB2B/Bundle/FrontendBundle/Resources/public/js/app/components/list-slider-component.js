define(function(require) {
    'use strict';

    var ContentSliderComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    require('slick');

    ContentSliderComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            mobileEnabled: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 2000,
            arrows: true,
            dots: false
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            if (this.options.mobileEnabled) {
                $(this.options._sourceElement).slick(this.options);
            }
        }
    });

    return ContentSliderComponent;
});
