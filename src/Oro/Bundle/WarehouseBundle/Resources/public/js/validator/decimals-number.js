/*global define*/
define([
    'underscore', 'orotranslation/js/translator', 'orolocale/js/locale-settings'
], function (_, __, localeSettings) {
    'use strict';

    var options = localeSettings.getNumberFormats('decimal'),
        decimalSeparator = options.decimal_separator_symbol,
        defaultParam = {
            message: 'This value should have {{ limit }} or less decimal digits.'
        };

    return [
        'DecimalsNumber',
        function(value, element, param) {
            if (!_.include(value, decimalSeparator)) {
                return true;
            }

            if (!_.isNumber(param.decimals)) {
                return true;
            }

            var decimals = value.split(decimalSeparator).pop();
            decimals.replace(' ', '');

            return isNaN(decimals) || decimals.length <= param.decimals;
        },
        function (param, element) {
            var value = this.elementValue(element);
            var placeholders = {};
            param = _.extend({}, defaultParam, param);

            placeholders.limit = param.decimals;
            placeholders.field = value;

            return __(param.message, placeholders);
        }
    ];
});
