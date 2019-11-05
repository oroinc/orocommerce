define([
    'underscore', 'orotranslation/js/translator', 'orolocale/js/locale-settings'
], function(_, __, localeSettings) {
    'use strict';

    const options = localeSettings.getNumberFormats('decimal');
    const decimalSeparator = options.decimal_separator_symbol;

    return [
        'decimal-precision',
        function(value, element, param) {
            if (!_.contains(value, decimalSeparator)) {
                return true;
            }
            const floatValue = parseFloat(value);

            return parseFloat(floatValue.toFixed(param.precision)) === floatValue;
        },
        function(param, element) {
            const placeholders = {};

            placeholders.precision = param.precision;

            return __(param.message, placeholders);
        }
    ];
});
