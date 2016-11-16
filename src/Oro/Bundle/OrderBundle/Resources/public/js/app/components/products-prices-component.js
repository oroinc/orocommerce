define(function(require) {
    'use strict';

    var ProductsPricesComponent;
    var mediator = require('oroui/js/mediator');
    var BaseProductsPricesComponent = require('oropricing/js/app/components/products-prices-component');

    /**
     * @export oroorder/js/app/components/entry-point-component
     * @extends oropricing.app.components.ProductsPricesComponent
     * @class oroorder.app.components.ProductsPricesComponent
     */
    ProductsPricesComponent = BaseProductsPricesComponent.extend({
        /**
         * @param {Array} products
         * @param {Function} callback
         */
        loadProductsTierPrices: function(products, callback) {
            mediator.once('entry-point:order:load', function(response) {
                callback(response.tierPrices || {});
            });
        },

        reloadPrices: function() {
            ProductsPricesComponent.__super__.reloadPrices.apply(this, arguments);

            mediator.trigger('entry-point:order:trigger');
        }
    });

    return ProductsPricesComponent;
});
