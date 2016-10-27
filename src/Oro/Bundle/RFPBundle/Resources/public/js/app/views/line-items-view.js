define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var LineItemProductView = require('oroproduct/js/app/views/line-item-product-view');
    var ProductsPricesComponent = require('oropricing/js/app/components/products-prices-component');

    /**
     * @export ororfp/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class ororfp.app.views.LineItemsView
     */
    LineItemsView = LineItemProductView.extend({
        /**
         * @property {Object}
         */
        options: {
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            LineItemsView.__super__.initialize.apply(this, arguments);

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                _sourceElement: this.$el,
                tierPrices: this.options.tierPrices,
                currency: this.options.currency,
                tierPricesRoute: this.options.tierPricesRoute
            }));

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$el.find('.add-lineitem').mousedown(function(e) {
                $(this).click();
            });
        },

        /**
         * @returns {Array} products
         */
        getProductsId: function() {
            var products = this.$el.find('input[data-ftid$="_product"]');
            products = _.filter(products, function(product) {
                return product.value.length > 0;
            });
            products = _.map(products, function(product) {
                return product.value;
            });
            return products;
        },

        /**
         * @returns {Array} products
         */
        getItems: function() {
            var lineItems = this.$el.find('.order-line-item');
            var items = [];

            _.each(lineItems, function(lineItem) {
                var $lineItem = $(lineItem);
                var productId = $lineItem.find('input[data-ftid$="_product"]')[0].value;
                if (productId.length === 0) {
                    return;
                }

                var unitCode = $lineItem.find('select[data-ftid$="_productUnit"]')[0].value;
                var quantity = $lineItem.find('input[data-ftid$="_quantity"]')[0].value;

                items.push({'product': productId, 'unit': unitCode, 'qty': quantity});
            });

            return items;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            LineItemsView.__super__.dispose.call(this);
        }
    });

    return LineItemsView;
});
