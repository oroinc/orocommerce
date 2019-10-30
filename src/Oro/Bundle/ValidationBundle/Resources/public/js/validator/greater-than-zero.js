define(function(require, exports, module) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var numberFormatter = require('orolocale/js/formatter/number');
    var config = require('module-config').default(module.id);

    var defaultParam = {
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
            var value = this.elementValue(element);
            var placeholders = {compared_value: 0};
            param = _.extend({}, defaultParam, param, config);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
