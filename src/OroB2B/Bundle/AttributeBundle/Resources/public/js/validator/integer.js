/*global define*/
define([
    'underscore', 'oroform/js/validator/regex', 'orolocale/js/locale-settings'
], function (_, regexConstraint, localeSettings) {
    'use strict';

    var options = localeSettings.getNumberFormats('decimal'),
        groupingSeparator = options.grouping_separator_symbol;

    return [
        'OroB2B\\Bundle\\AttributeBundle\\Validator\\Constraints\\Integer',
        function(value, element, param) {
            param.pattern = '/^[0-9\\+\\-\\' + groupingSeparator + ']*$/';
            return regexConstraint[1].call(this, value, element, param);
        },
        regexConstraint[2]
    ];
});
