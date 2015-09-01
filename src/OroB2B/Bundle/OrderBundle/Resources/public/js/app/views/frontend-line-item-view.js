define(function(require) {
    'use strict';

    var FrontendLineItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');

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
         * @property {Boolean}
         */
        initialized: false,

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.options = $.extend(true, {
                unitLoaderRouteName: 'orob2b_pricing_frontend_units_by_pricelist',
                selectors: {
                    priceValueText: 'div.order-line-item-price-value'
                }
            }, this.options);

            FrontendLineItemView.__super__.initialize.apply(this, arguments);

            var currencyRouteParameter = {currency: this.options.currency};
            var unitLoaderOptions = {
                routingParams: currencyRouteParameter
            };
            if (_.has(this.options, 'unitLoaderRouteName') && this.options.unitLoaderRouteName) {
                unitLoaderOptions.routeName = this.options.unitLoaderRouteName;
            }
            this.initializeUnitLoader(unitLoaderOptions);

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
                //skip product, productUnit always changed after product change
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit
            ]);

            this.initialized = true;
        },

        /**
         * @inheritdoc
         */
        setMatchedPrices: function(matchedPrices) {
            FrontendLineItemView.__super__.setMatchedPrices.apply(this, arguments);

            if (!this.options.disabled && this.initialized) {
                var matchedPrice = this.getMatchedPriceValue();
                var price = this.$priceValueText.data('price');

                if (!matchedPrice && price.length !== 0) {
                    matchedPrice = price;
                }

                this.$priceValueText.text(NumberFormatter.formatCurrency(matchedPrice, this.options.currency));
            }

            this.renderTierPrices();
        }
    });

    return FrontendLineItemView;
});
