define([
    'module',
    'underscore',
    'oroform/js/validator/regex'
], function(module, _, regexConstraint) {
    'use strict';

    var config = module.config();

    return [
        'Oro\\Bundle\\ProductBundle\\Validator\\Constraints\\SkuRegex',
        function(value, element, param) {
            param.pattern = String(config.pattern);
            return regexConstraint[1].call(this, value, element, param);
        },
        regexConstraint[2]
    ];
});
