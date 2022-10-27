define(function(require) {
    'use strict';

    const NumberFormatter = require('orolocale/js/formatter/number');
    const localeSettings = require('orolocale/js/locale-settings');

    /**
     * @export  oroproduct/js/app/quantity-helper
     * @class   QuantityHelper
     */
    return {
        /**
         * @param {Number|String} quantity
         * @param {Number|String} precision
         * @param {Boolean}       skipGrouping
         * @param {Boolean}       enforceAddFractionDigits
         *
         * @return {String}
         */
        formatQuantity: function(quantity, precision, skipGrouping, enforceAddFractionDigits) {
            if (!quantity) {
                return quantity;
            }

            const options = {grouping_used: !skipGrouping};
            if (precision && typeof parseInt(precision) == 'number') {
                const precisionInt = parseInt(precision);
                options['max_fraction_digits'] = precisionInt;
                if (enforceAddFractionDigits) {
                    options['min_fraction_digits'] = precisionInt;
                }
            }

            return NumberFormatter.formatDecimal(quantity, options);
        },

        /**
         * @param {String}        quantity
         * @param {String|Number} defaultValue
         *
         * @return {Number|Boolean}
         */
        getQuantityNumberOrDefaultValue: function(quantity, defaultValue) {
            if (defaultValue === undefined) {
                defaultValue = quantity;
            }

            const formattedQuantity = this.getNumberFromFormattedQuantityString(quantity);
            if (formattedQuantity === false) {
                return defaultValue;
            }

            return formattedQuantity;
        },

        /**
         * @param {String} quantity
         *
         * @return {Boolean}
         */
        isQuantityLocalizedValueValid: function(quantity) {
            const numberValue = this.getNumberFromFormattedQuantityString(quantity);
            return numberValue !== false;
        },

        /**
         * @param {String} quantity
         *
         * @return {Number|Boolean}
         */
        getNumberFromFormattedQuantityString: function(quantity) {
            if (!quantity || quantity === '') {
                return false;
            }

            const quantityNumber = NumberFormatter.unformatStrict(quantity);
            if (isNaN(quantityNumber)) {
                return false;
            }

            return quantityNumber;
        },

        /**
         * @returns {number}
         */
        getDefaultMaxFractionDigits() {
            return localeSettings.settings.format.number.decimal.max_fraction_digits;
        }
    };
});
