/** @lends LineItemView */
define(function(require) {
    'use strict';

    var BaseView = require('oroui/js/app/views/base/view');
    var PricesComponent = require('orob2bpricing/js/app/components/products-prices-component');
    var ProductQuantityComponent = require('orob2bproduct/js/app/components/product-quantity-editable-component');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    var LineItemView;

    LineItemView = BaseView.extend(/** @exports LineItemView.prototype */{
        $priceContainer: {},

        options: {
            currency: null,
            product: null,
            matchedPricesRoute: null,
            priceContainer: null,
            quantityContainer: null,
            quantityComponentOptions: {
                validation: {
                    showErrorsHandler: 'orob2bshoppinglist/js/shopping-list-item-errors-handler'
                }
            }
        },

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$priceContainer = this.$(options.priceContainer);

            this.pricesComponent = new PricesComponent({
                _sourceElement: this.$el,
                matchedPricesRoute: this.options.matchedPricesRoute
            });

            var productQuantityComponentInstance = new ProductQuantityComponent(
                _.extend({}, this.options.quantityComponentOptions, {_sourceElement: this.$(options.quantityContainer)})
            );
            this.listenTo(productQuantityComponentInstance, 'product:quantity-unit:update', this.onQuantityUnitChange);

            this.subview('productQuantity', productQuantityComponentInstance);
            this.subview('pricesComponent', this.pricesComponent);
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
