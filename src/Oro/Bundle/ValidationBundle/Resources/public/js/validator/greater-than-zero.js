define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const numberFormatter = require('orolocale/js/formatter/number');
    const config = require('module-config').default(module.id);

    const defaultParam = {
        message: 'This value should be greater than {{ compared_value }}.'
    };

    /**
     * @export oroform/js/validator/range
     */
    return [
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
});
