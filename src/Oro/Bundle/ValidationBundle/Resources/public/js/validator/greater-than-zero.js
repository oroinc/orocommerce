import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import numberFormatter from 'orolocale/js/formatter/number';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

const defaultParam = {
    message: 'This value should be greater than {{ compared_value }}.'
};

/**
 * @export oroform/js/validator/range
 */
export default [
    'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\GreaterThanZero',
    function(value, element) {
        value = numberFormatter.unformat(value);
        return this.optional(element) || value > 0;
    },
    function(param, element) {
        const value = this.elementValue(element);
        const placeholders = {compared_value: 0};
        param = _.extend({}, defaultParam, param, config);
        placeholders.value = value;
        return __(param.message, placeholders);
    }
];
