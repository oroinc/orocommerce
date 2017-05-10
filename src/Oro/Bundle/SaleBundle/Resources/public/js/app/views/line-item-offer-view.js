define(function(require) {
    'use strict';

    var LineItemOfferView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
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
            mediator.on('product:unit:filter-values', _.bind(this.filterValues, this));
            this.elements.id = $(options.$.product);
            this.options = $.extend(true, {}, this.options, options || {});
            _.each(this.options.$, _.bind(function(selector, field) {
                this.options.$[field] = $(selector);
            }, this));

            LineItemOfferView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Remove options from all selects
         *
         * @param {Number} productId
         * @param {Array} values
         */
        filterValues: function(productId, values) {
            if (productId !== this.model.id) {
                return;
            }
            this.getElement('unit')
                .find('option')
                .filter(function() {
                    return !$(this).prop('selected') && (-1 === $.inArray(this.value, values));
                })
                .remove();
        }
    });

    return LineItemOfferView;
});
