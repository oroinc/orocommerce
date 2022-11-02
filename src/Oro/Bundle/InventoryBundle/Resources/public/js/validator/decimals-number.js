define([
    'underscore', 'orotranslation/js/translator', 'orolocale/js/locale-settings'
], function(_, __, localeSettings) {
    'use strict';

    const options = localeSettings.getNumberFormats('decimal');
    const decimalSeparator = options.decimal_separator_symbol;
    const defaultParam = {
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

            const decimals = value.split(decimalSeparator).pop();
            decimals.replace(' ', '');

            return isNaN(decimals) || decimals.length <= param.decimals;
        },
        function(param, element) {
            const value = this.elementValue(element);
            const placeholders = {};
            param = _.extend({}, defaultParam, param);

            placeholders.limit = param.decimals;
            placeholders.field = value;

            return __(param.message, placeholders);
        }
    ];
});
