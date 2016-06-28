define(function(require) {
    'use strict';

    var ProductsPricesComponent;
    var mediator = require('oroui/js/mediator');
    var BaseProductsPricesComponent = require('orob2bpricing/js/app/components/products-prices-component');

    /**
     * @export orob2border/js/app/components/entry-point-component
     * @extends orob2bpricing.app.components.ProductsPricesComponent
     * @class orob2border.app.components.ProductsPricesComponent
     */
    ProductsPricesComponent = BaseProductsPricesComponent.extend({
        initFieldsListeners: function() {
            mediator.on('update:currency', this.setCurrency, this);
        },

        /**
         * @param {Array} products
         * @param {Function} callback
         */
        loadProductsTierPrices: function(products, callback) {
            mediator.once('entry-point:order:load', function(response) {
                callback(response.tierPrices || {});
            });
        },

        /**
         * @param {Array} items
         * @param {Function} callback
         */
        loadLineItemsMatchedPrices: function(items, callback) {
            mediator.once('entry-point:order:load', function(response) {
                callback(response.matchedPrices || {});
            });
        },

        reloadPrices: function() {
            ProductsPricesComponent.__super__.reloadPrices.apply(this, arguments);

            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('update:currency', this.setCurrency, this);

            ProductsPricesComponent.__super__.dispose.call(this);
        }
    });

    return ProductsPricesComponent;
});
