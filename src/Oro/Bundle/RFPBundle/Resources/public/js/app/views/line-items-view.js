define(function(require) {
    'use strict';

    var LineItemsView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var ProductsPricesComponent = require('oropricing/js/app/components/products-prices-component');
    var mediator = require('oroui/js/mediator');

    /**
     * @export ororfp/js/app/views/line-items-view
     * @extends oroui.app.views.base.View
     * @class ororfp.app.views.LineItemsView
     */
    LineItemsView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @property {Object}
         */
        options: {
        },

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
    }));

    return LineItemsView;
});
