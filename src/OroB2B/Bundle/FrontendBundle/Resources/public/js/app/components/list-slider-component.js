/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    var $ = require('jquery');
    var Slick = require('orob2bfrontend/default/vendors/slick/slick');

    var ContentSliderComponent;

    ContentSliderComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            mobileEnabled: true,
            slidesCount: 4,
            slidesScroll: 1,
            autoplaySpeed: 2000,
            arrowsNav: true,
            dotsNav: false
        },

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            if (this.options.mobileEnabled) {
                $(this.options._sourceElement).slick({
                    slidesToShow: this.options.slidesCount,
                    slidesToScroll: this.options.slidesScroll,
                    autoplay: true,
                    autoplaySpeed: this.options.autoplaySpeed,
                    arrows: this.options.arrowsNav,
                    dots: this.options.dotsNav
                });
            }
        }
    });

    return ContentSliderComponent;
});
