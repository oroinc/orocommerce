import regexConstraint from 'oroform/js/validator/regex';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

export default [
    'Oro\\Bundle\\ProductBundle\\Validator\\Constraints\\SkuRegex',
    function(value, element, param) {
        param.pattern = String(config.pattern);
        return regexConstraint[1].call(this, value, element, param);
    },
    regexConstraint[2]
];
