define(function(require, exports, module) {
    'use strict';

    const regexConstraint = require('oroform/js/validator/regex');
    const config = require('module-config').default(module.id);

    return [
        'Oro\\Bundle\\ProductBundle\\Validator\\Constraints\\SkuRegex',
        function(value, element, param) {
            param.pattern = String(config.pattern);
            return regexConstraint[1].call(this, value, element, param);
        },
        regexConstraint[2]
    ];
});
