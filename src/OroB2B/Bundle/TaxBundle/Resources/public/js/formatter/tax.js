define(['underscore', 'orolocale/js/formatter/number', 'orolocale/js/locale-settings'],
    function(_, NumberFormatter, localeSettings) {
        'use strict';

        /**
         * Tax Formatter
         *
         * @export orotax/js/formatter/tax
         * @name   orotax.formatter.tax
         */
        var taxFormatter = function() {
            var formatElement = function(value, currency) {
                if (_.isUndefined(currency)) {
                    currency = localeSettings.defaults.currency;
                }

                return NumberFormatter.formatCurrency(value, currency);
            };

            return {
                /**
                 * @param {Object} item
                 */
                formatItem: function(item) {
                    var localItem = _.extend({
                        includingTax: 0,
                        excludingTax: 0,
                        taxAmount: 0,
                        currency: localeSettings.defaults.currency
                    }, item);

                    return {
                        includingTax: formatElement(localItem.includingTax, localItem.currency),
                        excludingTax: formatElement(localItem.excludingTax, localItem.currency),
                        taxAmount: formatElement(localItem.taxAmount, localItem.currency)
                    };
                },

                /**
                 * @param {Object} item
                 */
                formatTax: function(item) {
                    var localItem = _.extend({
                        taxAmount: 0,
                        taxableAmount: 0,
                        rate: 0,
                        tax: '',
                        currency: localeSettings.defaults.currency
                    }, item);

                    return {
                        tax: localItem.tax,
                        taxAmount: formatElement(localItem.taxAmount, localItem.currency),
                        taxableAmount: formatElement(localItem.taxableAmount, localItem.currency),
                        rate: NumberFormatter.formatPercent(localItem.rate)
                    };
                },

                /**
                 * @param {String} value
                 * @param {String} currency
                 */
                formatElement: function(value, currency) {
                    return formatElement(value, currency);
                }
            };
        };

        return taxFormatter();
    });
