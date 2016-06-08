/*jshint bitwise: false*/
define([
    'underscore',
    'orotranslation/js/translator',
    'orob2bpayment/js/adapter/credit-card-validator-adapter'
], function(_, __, creditCardValidator) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.credit_card'
    };

    /**
     * @export orob2bpayment/js/validator/credit-card-number
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
