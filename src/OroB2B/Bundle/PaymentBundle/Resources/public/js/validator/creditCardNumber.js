/*jshint bitwise: false*/
define([
    'underscore',
    'orotranslation/js/translator',
    'jquery',
    'jquery.validate',
    'orob2bpayment/js/lib/jquery-credit-card-validator'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.credit_card'
    };

    /**
     * @export oroform/js/validator/open-range
     */
    return [
        'creditCardNumber',
        function(value, element) {
            var result = $(element).validateCreditCard();
            return result.valid;

        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
