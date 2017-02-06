/*global define*/
define([
    'underscore', 'orotranslation/js/translator', 'orolocale/js/locale-settings'
], function(_, __, localeSettings) {
    'use strict';

    var options = localeSettings.getNumberFormats('decimal');
    var decimalSeparator = options.decimal_separator_symbol;

    return [
        'decimal-precision',
        function(value, element, param) {
            if (!_.contains(value, decimalSeparator)) {
                return true;
            }
            var floatValue = parseFloat(value);

            return floatValue.toFixed(param.precision) == floatValue;
        },
        function(param, element) {
            var placeholders = {};

            placeholders.precision = param.precision;

            return __(param.message, placeholders);
        }
    ];
});
