define(function(require) {
    'use strict';

    var FrontendLineItemView;
    var $ = require('jquery');
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
         * @inheritDoc
         */
        initialize: function() {
            this.options = $.extend(true, {
                selectors: {
                    priceValueText: 'input.order-line-item-price-value'
                }
            }, this.options);

            FrontendLineItemView.__super__.initialize.apply(this, arguments);
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
                this.$priceValueText.val(
                    NumberFormatter.formatCurrency(this.getMatchedPriceValue(), this.options.currency)
                );
            }

            this.renderTierPrices();
        }
    });

    return FrontendLineItemView;
});
