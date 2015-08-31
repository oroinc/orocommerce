define(function(require) {
    'use strict';

    var FrontendLineItemView;
    var $ = require('jquery');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');
    var ProductUnitComponent = require('orob2bproduct/js/app/components/product-unit-component');

    /**
     * @export orob2border/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemView
     */
    FrontendLineItemView = LineItemAbstractView.extend({
        /**
         * @property {jQuery}
         */
        $priceValueText: null,

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.options = $.extend(true, {
                selectors: {
                    priceValueText: 'div.order-line-item-price-value'
                }
            }, this.options);

            FrontendLineItemView.__super__.initialize.apply(this, arguments);

            var currencyRouteParameter = {currency: this.options.currency};
            var productUnitComponent = new ProductUnitComponent({
                _sourceElement: this.$el,
                routingParams: currencyRouteParameter,
                routeName: 'orob2b_pricing_frontend_units_by_pricelist',
                productSelector: '.order-line-item-type-product input.select2',
                quantitySelector: '.order-line-item-quantity input',
                unitSelector: '.order-line-item-quantity select',
                loadingMaskEnabled: false
            });

            this.subview('productUnitComponent', productUnitComponent);

            this.$el.find('.order-line-item-type-product input.select2')
                .data('select2_query_additional_params', currencyRouteParameter);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$priceValueText = $(this.$el.find(this.options.selectors.priceValueText));

            FrontendLineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.subtotalFields([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit
            ]);
        },

        /**
         * @inheritdoc
         */
        setMatchedPrices: function(matchedPrices) {
            FrontendLineItemView.__super__.setMatchedPrices.apply(this, arguments);

            if (!this.options.disabled) {
                this.$priceValueText.text(
                    NumberFormatter.formatCurrency(this.getMatchedPriceValue(), this.options.currency)
                );
            }

            this.renderTierPrices();
        }
    });

    return FrontendLineItemView;
});
