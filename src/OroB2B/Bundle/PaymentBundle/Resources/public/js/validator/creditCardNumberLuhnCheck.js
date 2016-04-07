define(['underscore', 'orotranslation/js/translator', 'jquery.validate'
], function(_, __) {
    'use strict';

    // Combination of ShirtlessKirk and Plotnitzky
    var defaultParam = {
        message: 'orob2b.payment.validation.credit_card'
    };

    /**
     * @export oroform/js/validator/open-range
     */
    return [
        'creditCardNumberLuhnCheck',
        function(value, element, param) {
            var len = value.length;
            var ca, sum = 0;
            var mul = 1;
            while (len--)
            {
                ca = parseInt(value.charAt(len),10);
                sum += mul ? ca : ca < 9 ? ca*2%9 : 9;
                mul ^= 1; // 1 or 0 swich.
            }

            return (sum%10 === 0);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
