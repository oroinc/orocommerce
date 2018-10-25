define([
    'underscore',
    'orotranslation/js/translator',
    'oropayment/js/validator/credit-card-china-union-pay',
    'jquery',
    'jquery.validate',
    'oropayment/js/lib/jquery-credit-card-validator'
], function(_, __, chinaUnionPayValidator, $) {
    'use strict';

    var defaultOptions = {
        allowedCreditCards: []
    };

    return {
        validate: function(element, options) {
            options = _.extend({}, defaultOptions, options);
            var allowedCCTypes = _.values(options.allowedCreditCards);
            var validateOptions = {};

            var customValidators = [];
            if (allowedCCTypes.length) {
                var amexIndex = allowedCCTypes.indexOf('american_express');
                if (amexIndex !== -1) {
                    allowedCCTypes[amexIndex] = 'amex';
                }

                var dinersClubIndex = allowedCCTypes.indexOf('diners_club');
                if (dinersClubIndex !== -1) {
                    allowedCCTypes.splice(
                        dinersClubIndex,
                        1,
                        'diners_club_carte_blanche',
                        'diners_club_international'
                    );
                }

                var chinaUnionPayIndex = allowedCCTypes.indexOf('china_union_pay');
                if (chinaUnionPayIndex !== -1) {
                    allowedCCTypes.splice(chinaUnionPayIndex, 1);
                    customValidators.push(chinaUnionPayValidator);
                }

                validateOptions.accept = allowedCCTypes;
            } else {
                customValidators.push(chinaUnionPayValidator);
            }
            var isValid = $(element).validateCreditCard(validateOptions).valid;
            if (false === isValid && customValidators.length > 0) {
                var number = $(element).val();
                for (var i = 0; i < customValidators.length; i++) {
                    var validator = customValidators[i];
                    var customResult = validator.validate(number);
                    if (true === customResult.valid) {
                        isValid = true;
                    }
                }
            }

            return isValid;
        }
    };
});
