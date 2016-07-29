/*jshint bitwise: false*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oropaypal/js/adapter/credit-card-validator-adapter'
], function(_, __, creditCardValidator) {
    'use strict';

    var defaultParam = {
        message: 'oro.paypal.validation.credit_card'
    };

    /**
     * @export oropaypal/js/validator/credit-card-number
     */
    return [
        'credit-card-number',
        function(value, element) {
            return creditCardValidator.validate(element);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
