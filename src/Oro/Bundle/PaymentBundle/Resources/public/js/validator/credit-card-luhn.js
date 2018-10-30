define(function() {
    'use strict';

    /**
     * @export oropayment/js/validator/credit-card-luhn
     */
    return {
        validate: function(number) {
            var digit;
            var n;
            var sum = 0;
            var _j;
            var _len1;
            var _ref1;
            _ref1 = number.split('').reverse();
            for (n = _j = 0, _len1 = _ref1.length; _j < _len1; n = ++_j) {
                digit = _ref1[n];
                digit = +digit;
                if (n % 2) {
                    digit *= 2;
                    if (digit < 10) {
                        sum += digit;
                    } else {
                        sum += digit - 9;
                    }
                } else {
                    sum += digit;
                }
            }
            return sum % 10 === 0;
        }
    };
});
