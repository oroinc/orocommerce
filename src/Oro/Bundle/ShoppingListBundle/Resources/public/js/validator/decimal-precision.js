import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import localeSettings from 'orolocale/js/locale-settings';

const options = localeSettings.getNumberFormats('decimal');
const decimalSeparator = options.decimal_separator_symbol;

export default [
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
