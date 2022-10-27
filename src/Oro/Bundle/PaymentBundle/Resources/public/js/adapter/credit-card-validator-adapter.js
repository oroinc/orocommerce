define([
    'underscore',
    'orotranslation/js/translator',
    'oropayment/js/validator/credit-card-china-union-pay',
    'jquery',
    'jquery.validate',
    'oropayment/js/lib/jquery-credit-card-validator'
], function(_, __, chinaUnionPayValidator, $) {
    'use strict';

    const defaultOptions = {
        allowedCreditCards: []
    };

    return {
        validate: function(element, options) {
            options = _.extend({}, defaultOptions, options);
            const allowedCCTypes = _.values(options.allowedCreditCards);
            const validateOptions = {};

            const customValidators = [];
            if (allowedCCTypes.length) {
                const amexIndex = allowedCCTypes.indexOf('american_express');
                if (amexIndex !== -1) {
                    allowedCCTypes[amexIndex] = 'amex';
                }

                const dinersClubIndex = allowedCCTypes.indexOf('diners_club');
                if (dinersClubIndex !== -1) {
                    allowedCCTypes.splice(
                        dinersClubIndex,
                        1,
                        'diners_club_carte_blanche',
                        'diners_club_international'
                    );
                }

                const chinaUnionPayIndex = allowedCCTypes.indexOf('china_union_pay');
                if (chinaUnionPayIndex !== -1) {
                    allowedCCTypes.splice(chinaUnionPayIndex, 1);
                    customValidators.push(chinaUnionPayValidator);
                }

                validateOptions.accept = allowedCCTypes;
            } else {
                customValidators.push(chinaUnionPayValidator);
            }
            let isValid = $(element).validateCreditCard(validateOptions).valid;
            if (false === isValid && customValidators.length > 0) {
                const number = $(element).val();
                for (let i = 0; i < customValidators.length; i++) {
                    const validator = customValidators[i];
                    const customResult = validator.validate(number);
                    if (true === customResult.valid) {
                        isValid = true;
                    }
                }
            }

            return isValid;
        }
    };
});
