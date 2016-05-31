/*jshint bitwise: false*/
define([
    'underscore',
    'orotranslation/js/translator',
    'orob2bpayment/js/adapter/credit-card-validator-adapter'
], function(_, __, creditCardValidator) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.credit_card_type'
    };

    /**
     * @export orob2bpayment/js/validator/credit-card-type
     */
    return [
        'credit-card-type',
        function(value, element, param) {
            if (!param.hasOwnProperty('allowedCreditCards')) {
                return true;
            }

            return creditCardValidator.validate(element, param);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
