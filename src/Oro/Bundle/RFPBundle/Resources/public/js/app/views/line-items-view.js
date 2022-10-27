define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const ProductsPricesComponent = require('oropricing/js/app/components/products-prices-component');
    const mediator = require('oroui/js/mediator');

    /**
     * @export ororfp/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class ororfp.app.views.LineItemsView
     */
    const LineItemsView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @property {Object}
         */
        options: {
        },

        /**
         * @inheritdoc
         */
        constructor: function LineItemsView(options) {
            LineItemsView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            LineItemsView.__super__.initialize.call(this, options);

            this.subview('productsPricesComponent', new ProductsPricesComponent({
                _sourceElement: this.$el,
                tierPrices: this.options.tierPrices,
                currency: this.options.currency,
                tierPricesRoute: this.options.tierPricesRoute
            }));

            this.initializeSubviews();
        },

        handleLayoutInit: function() {
            this.$el.find('.add-lineitem').mousedown(function(e) {
                $(this).click();
            });

            mediator.trigger('line-items:show:before');

            this.$el.find('.view-loading').remove();
            this.$el.find('.request-form__content').show();
            this._resolveDeferredRender();
        },

        /**
         * @returns {Array} products
         */
        getProductsId: function() {
            let products = this.$el.find('input[data-ftid$="_product"]');
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
            const lineItems = this.$el.find('.order-line-item');
            const items = [];

            _.each(lineItems, function(lineItem) {
                const $lineItem = $(lineItem);
                const productId = $lineItem.find('input[data-ftid$="_product"]')[0].value;
                if (productId.length === 0) {
                    return;
                }

                const unitCode = $lineItem.find('select[data-ftid$="_productUnit"]')[0].value;
                const quantity = $lineItem.find('input[data-ftid$="_quantity"]')[0].value;

                items.push({product: productId, unit: unitCode, qty: quantity});
            });

            return items;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            LineItemsView.__super__.dispose.call(this);
        }
    }));

    return LineItemsView;
});
