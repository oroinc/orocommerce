/*global define*/
define(['underscore', 'orotranslation/js/translator', 'orolocale/js/formatter/number'
], function (_, __, numberFormatter) {
    'use strict';

    var defaultParam = {
        message: 'This value should be greater than {{ compared_value }}.'
    };

    /**
     * @export oroform/js/validator/range
     */
    return [
        'OroB2B\\Bundle\\ValidationBundle\\Validator\\Constraints\\GreaterThanZero',
        function (value, element) {
            value = numberFormatter.unformat(value);
            return this.optional(element) || value > 0;
        },
        function (param, element) {
            var value = this.elementValue(element),
                placeholders = { compared_value: 0 };
            param = _.extend({}, defaultParam, param);
            placeholders.value = value;
            return __(param.message, placeholders);
        }
    ];
});
