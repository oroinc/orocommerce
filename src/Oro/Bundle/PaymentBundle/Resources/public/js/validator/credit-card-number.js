define([
    'underscore',
    'orotranslation/js/translator',
    'oropayment/js/adapter/credit-card-validator-adapter'
], function(_, __, creditCardValidator) {
    'use strict';

    const defaultParam = {
        message: 'oro.payment.validation.credit_card'
    };

    /**
     * @export oropayment/js/validator/credit-card-number
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
