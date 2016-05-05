/** @lends LineItemView */
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var BaseModel = require('oroui/js/app/models/base/model');
    var PricesComponent = require('orob2bpricing/js/app/components/products-prices-component');
    var ProductQuantityComponent = require('orob2bproduct/js/app/components/product-quantity-editable-component');
    var NumberFormatter = require('orolocale/js/formatter/number');

    var LineItemView;

    LineItemView = BaseView.extend(/** @exports LineItemView.prototype */{

        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});
            this.LineItemModel = new BaseModel({prices: this.options.allPrices});
            this.$priceContainer = this.$(this.options.priceComponentOptions.priceContainer);

            // TODO: in BB-2895 replace this component /base-product-prices-view.js
            this.pricesComponent = new PricesComponent({
                _sourceElement: this.$el,
                matchedPricesRoute: this.options.priceComponentOptions.matchedPricesRoute
            });

            var productQuantityComponent = new ProductQuantityComponent(
                _.extend({}, this.options.quantityComponentOptions, {$parentView: this.$el}),
                this.LineItemModel
            );

            this.listenTo(productQuantityComponent, 'product:quantity-unit:update', this.onQuantityUnitChange);
        },

        onQuantityUnitChange: function(data) {
            var item = {
                product: this.options.product,
                currency: this.options.currency,
                qty: data.quantity,
                unit: data.unit
            };

            this.pricesComponent.loadLineItemsMatchedPrices([item], _.bind(this.updateMatchedPrice, this));

            mediator.trigger('frontend:shopping-list-item-quantity:update', data);

            this.trigger('unit-changed', {
                lineItemId: this.options.lineItemId,
                product: this.options.product,
                unit: data.unit
            });
        },

        updateMatchedPrice: function(matchedPrices) {
            var matchedPrice = 0.0;
            matchedPrices = _.values(matchedPrices);
            if (matchedPrices.length) {
                matchedPrice = matchedPrices[0];
            }

            this.$priceContainer.html(NumberFormatter.formatCurrency(matchedPrice.value, matchedPrice.currency));
        }
    });

    return LineItemView;
});
