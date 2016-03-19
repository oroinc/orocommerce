define(['orolocale/js/formatter/number'], function(NumberFormatter) {
    'use strict';

    /**
     * Tax Formatter
     *
     * @export orob2btax/js/formatter/tax
     * @name   orob2btax.formatter.tax
     */
    return {
        /**
         * @param {Object}item
         */
        formatItem: function(item) {
            return {
                includingTax: NumberFormatter.formatCurrency(item.includingTax, item.currency),
                excludingTax: NumberFormatter.formatCurrency(item.excludingTax, item.currency),
                taxAmount: NumberFormatter.formatCurrency(item.taxAmount, item.currency)
            };
        },

        /**
         * @param {Object}item
         */
        formatTax: function(item) {
            return {
                tax: item.tax,
                taxAmount: NumberFormatter.formatCurrency(item.taxAmount, item.currency),
                taxableAmount: NumberFormatter.formatCurrency(item.taxableAmount, item.currency),
                rate: NumberFormatter.formatPercent(item.rate)
            };
        }
    };
});
