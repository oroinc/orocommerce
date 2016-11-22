define(function(require) {
    'use strict';

    var LineItemProductPricesView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ProductPricesEditableView = require('oropricing/js/app/views/product-prices-editable-view');

    LineItemProductPricesView = ProductPricesEditableView.extend({
        elements: _.extend({}, ProductPricesEditableView.prototype.elements, {
            currency: '[data-name="field__currency"]'
        }),

        modelElements: _.extend({}, ProductPricesEditableView.prototype.modelElements, {
            currency: 'currency'
        }),

        modelEvents: _.extend({}, ProductPricesEditableView.prototype.modelEvents, {
            'id updateTierPrices': ['change', 'updateTierPrices'],
            'currency changePricesCurrency': ['change', 'changePricesCurrency']
        }),

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            LineItemProductPricesView.__super__.initialize.apply(this, arguments);

            mediator.on('pricing:collect:line-items', this.collectLineItems, this);
            mediator.on('pricing:refresh:products-tier-prices', this.setTierPrices, this);

            mediator.trigger('pricing:get:products-tier-prices', _.bind(this.setTierPrices, this));
        },

        updateTierPrices: function() {
            var productId = this.model.get('id');
            if (productId.length === 0) {
                this.setTierPrices({});
            } else {
                mediator.trigger(
                    'pricing:load:products-tier-prices',
                    [productId],
                    _.bind(this.setTierPrices, this)
                );
            }
        },

        changePricesCurrency: function() {
            this.setTierPrices(this.tierPrices);
        },

        /**
         * @param {Object} tierPrices
         */
        setTierPrices: function(tierPrices) {
            this.tierPrices = tierPrices;
            var prices = {};

            var productId = this.model.get('id');
            if (productId.length !== 0) {
                prices = tierPrices[productId] || {};
            }

            var currency = this.model.get('currency');
            if (currency) {
                prices = _.filter(prices, function(price) {
                    return price.currency === currency;
                });
            }

            this.model.set('prices', prices);
        },

        /**
         * @param {Array} items
         */
        collectLineItems: function(items) {
            var productId = this.model.get('id');
            if (productId.length) {
                items.push({
                    product: productId,
                    unit: this.model.get('unit'),
                    quantity: this.model.get('quantity'),
                    currency: this.model.get('currency')
                });
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(null, null, this);

            LineItemProductPricesView.__super__.dispose.call(this);
        }
    });

    return LineItemProductPricesView;
});
