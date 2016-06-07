define(['underscore', 'orotranslation/js/translator', 'jquery', 'jquery.validate'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.month',
        monthSelector: '[data-expiration-date-month]',
        yearSelector: '[data-expiration-date-year]'
    };

    /**
     * @export orob2bpayment/js/validator/credit-card-expiration-date
     */
    return [
        'credit-card-expiration-date',
        function(value, element, param) {
            param = _.extend({}, defaultParam, param);
            var form = $(element).parents('form');
            var year = form.find(param.yearSelector).val();
            var month = form.find(param.monthSelector).val();
            var now = new Date();

            if (year.length) {
                if (parseInt(year, 10) === now.getFullYear() % 100) {
                    return parseInt(month, 10) >= now.getMonth() + 1;
                }
            }
            return true;
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
