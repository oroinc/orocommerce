define(function(require) {
    'use strict';

    const $ = require('jquery');
    const QuoteProductsPricesComponent = require('orosale/js/app/components/quote-products-prices-component').default;
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orosale/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class orosale.app.views.LineItemsView
     */
    const LineItemsView = BaseView.extend({
        /**
         * @inheritDoc
         */
        options: {
            tierPrices: null
        },

        constructor: function LineItemsView(options) {
            LineItemsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.subview('productsPricesComponent', new QuoteProductsPricesComponent({
                tierPrices: this.options.tierPrices
            }));

            this.initLayout();
        }
    });

    return LineItemsView;
});
