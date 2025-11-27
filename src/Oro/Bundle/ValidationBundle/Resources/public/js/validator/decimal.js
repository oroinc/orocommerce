import regexConstraint from 'oroform/js/validator/regex';
import localeSettings from 'orolocale/js/locale-settings';

const options = localeSettings.getNumberFormats('decimal');
const groupingSeparator = options.grouping_separator_symbol;
const decimalSeparator = options.decimal_separator_symbol;

export default [
    'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Decimal',
    function(value, element, param) {
        param.pattern = '/^[0-9\\+\\-\\' + groupingSeparator + '\\' + decimalSeparator + ']*$/';
        return regexConstraint[1].call(this, value, element, param);
    },
    regexConstraint[2]
];
