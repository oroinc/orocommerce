define(function(require) {
    'use strict';

    const _ = require('underscore');
    const numeral = require('numeral');

    const PricesHelper = {
        sortByLowQuantity: function(prices = []) {
            if (!_.isArray(prices) || prices.length < 2) {
                return prices;
            }

            return [...prices].sort((a, b) => {
                if (a['quantity'] < b['quantity']) {
                    return -1;
                }

                return 1;
            });
        },

        /**
         * @param pricesByUnit {Object<{unit: Array<Object>}>}
         * @returns {Object<{unit: Array<Object>}>}
         */
        sortUnitPricesByLowQuantity: function(pricesByUnit = {}) {
            const unit = Object.keys(pricesByUnit)[0];
            const prices = Object.values(pricesByUnit)[0];
            const data = {};

            if (unit) {
                data[unit] = PricesHelper.sortByLowQuantity(prices);
            }

            return data;
        },

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

        indexPrices: function(prices) {
            return Object.entries(prices).reduce((pricesIndex, [unit, unitPrices]) => {
                unitPrices.forEach(price => pricesIndex[`${unit}_${price.quantity}`] = price);
                return pricesIndex;
            }, {});
        },

        /**
         * Get price object
         *
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
            return numeral(this.findPriceValue(prices, unit, quantity)).multiply(quantity).value();
        }
    };

    return PricesHelper;
});
