define(function(require) {
    'use strict';

    const _ = require('underscore');

    const PricesHelper = {
        preparePrices: function(prices) {
            prices = _.sortBy(prices, 'quantity');
            prices = prices.reverse();
            const pricesByUnit = {};
            _.each(prices, function(price) {
                const unit = price.unit;
                pricesByUnit[unit] = pricesByUnit[unit] || [];
                pricesByUnit[unit].push(price);
            });
            return pricesByUnit;
        },

        /**
         * Get price object

         * @param prices {Object}
         * @param unit {String}
         * @param quantity {Number|String}
         * @returns {Object}
         */
        findPrice: function(prices, unit, quantity) {
            if (_.isEmpty(prices) || isNaN(quantity) || quantity === '') {
                return null;
            }

            quantity = parseFloat(quantity);

            if (quantity < 0) {
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
            const price = this.findPrice(prices, unit, quantity);
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
