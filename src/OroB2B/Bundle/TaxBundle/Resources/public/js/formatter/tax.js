define(['underscore', 'orolocale/js/formatter/number', 'orolocale/js/locale-settings'],
    function(_, NumberFormatter, localeSettings) {
        'use strict';

        /**
         * Tax Formatter
         *
         * @export orob2btax/js/formatter/tax
         * @name   orob2btax.formatter.tax
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
                    return {
                        includingTax: formatElement(item.includingTax, item.currency),
                        excludingTax: formatElement(item.excludingTax, item.currency),
                        taxAmount: formatElement(item.taxAmount, item.currency)
                    };
                },

                /**
                 * @param {Object} item
                 */
                formatTax: function(item) {
                    return {
                        tax: item.tax,
                        taxAmount: formatElement(item.taxAmount, item.currency),
                        taxableAmount: formatElement(item.taxableAmount, item.currency),
                        rate: NumberFormatter.formatPercent(item.rate)
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
