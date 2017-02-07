define(function(require) {
    'use strict';

    var PricesHelper;
    var _ = require('underscore');

    PricesHelper = {
        preparePrices: function(prices) {
            prices = _.sortBy(prices, 'quantity');
            prices = prices.reverse();
            var pricesByUnit = {};
            _.each(prices, function(price) {
                var unit = price.unit;
                if (price.quantity === 1) {
                    price.quantity = 5;
                }
                pricesByUnit[unit] = pricesByUnit[unit] || [];
                pricesByUnit[unit].push(price);
            });
            return pricesByUnit;
        },

        /**
         * Get price object

         * @param prices {Object}
         * @param unit {String}
         * @param quantity {Number}
         * @returns {Object}
         */
        findPrice: function(prices, unit, quantity) {
            if (_.isEmpty(quantity) || _.isEmpty(prices)) {
                return null;
            }

            return _.find(prices[unit], function(price) {
                return price.quantity <= quantity;
            }) || null;
        },

        /**
         * Get price value

         * @param prices {Object}
         * @param unit {String}
         * @param quantity {Number}
         * @returns {Number}
         */
        findPriceValue: function(prices, unit, quantity) {
            var price = this.findPrice(prices, unit, quantity);
            return price ? price.price : 0;
        },

        /**
         * Calculate total price

         * @param prices {Object}
         * @param unit {String}
         * @param quantity {Number}
         * @returns {Number}
         */
        calcTotalPrice: function(prices, unit, quantity) {
            return this.findPriceValue(prices, unit, quantity) * quantity;
        }
    };

    return PricesHelper;
});
