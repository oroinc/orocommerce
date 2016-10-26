define(function(require) {
    'use strict';

    var LineItemOfferView;
    var $ = require('jquery');
    var _ = require('underscore');
    var LineItemProductView = require('oroproduct/js/app/views/line-item-product-view');

    LineItemOfferView = LineItemProductView.extend({
        /**
         * @property {Object}
         */
        options: { 
            $: {
                product: ''
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.elements.id = $(options.$.product);
            this.options = $.extend(true, {}, this.options, options || {});
            _.each(this.options.$, _.bind(function(selector, field) {
                this.options.$[field] = $(selector);
            }, this));

            LineItemOfferView.__super__.initialize.apply(this, arguments);
        }
    });

    return LineItemOfferView;
});
