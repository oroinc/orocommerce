import regexConstraint from 'oroform/js/validator/regex';
import localeSettings from 'orolocale/js/locale-settings';

const options = localeSettings.getNumberFormats('decimal');
const groupingSeparator = options.grouping_separator_symbol;

export default [
    'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Integer',
    function(value, element, param) {
        param.pattern = '/^[0-9\\+\\-\\' + groupingSeparator + ']*$/';
        return regexConstraint[1].call(this, value, element, param);
    },
    regexConstraint[2]
];
