define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const BaseProductsPricesComponent = require('oropricing/js/app/components/products-prices-component');

    /**
     * @export oroorder/js/app/components/entry-point-component
     * @extends oropricing.app.components.ProductsPricesComponent
     * @class oroorder.app.components.ProductsPricesComponent
     */
    const ProductsPricesComponent = BaseProductsPricesComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function ProductsPricesComponent(options) {
            ProductsPricesComponent.__super__.constructor.call(this, options);
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

        reloadPrices: function() {
            ProductsPricesComponent.__super__.reloadPrices.call(this);

            mediator.trigger('entry-point:order:trigger');
        }
    });

    return ProductsPricesComponent;
});
