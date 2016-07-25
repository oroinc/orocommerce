/*jshint bitwise: false*/
define([
    'underscore',
    'orotranslation/js/translator',
    'jquery',
    'jquery.validate',
    'orob2bpayment/js/lib/jquery-credit-card-validator'
], function(_, __, $) {
    'use strict';

    var defaultOptions = {
        allowedCreditCards: []
    };

    return {
        validate: function(element, options) {
            options = _.extend({}, defaultOptions, options);
            var allowedCCTypes = _.values(options.allowedCreditCards);
            var validateOptions = {};

            if (allowedCCTypes.length) {
                var amexIndex = allowedCCTypes.indexOf('american_express');
                if (amexIndex !== -1) {
                    allowedCCTypes[amexIndex] = 'amex';
                }
                validateOptions.accept = allowedCCTypes;
            }

            return $(element).validateCreditCard(validateOptions).valid;
        }
    };
});
