define([
    'oropayment/js/normalizer/credit-card-number',
    'oropayment/js/validator/credit-card-luhn'
], function(normalizer, luhnValidator) {
    'use strict';

    /**
     * @export oropayment/js/validator/credit-card-china-union-pay
     */
    return {
        validate: function(number) {
            const numNormalized = normalizer.normalize(number);
            const isLengthValid = /^62[0-9]{14,17}$/.test(numNormalized);
            const isLuhnValid = luhnValidator.validate(numNormalized);

            return {
                card_type: 'china_union_pay',
                length_valid: isLengthValid,
                luhn_valid: isLuhnValid,
                valid: isLengthValid && isLuhnValid
            };
        }
    };
});
