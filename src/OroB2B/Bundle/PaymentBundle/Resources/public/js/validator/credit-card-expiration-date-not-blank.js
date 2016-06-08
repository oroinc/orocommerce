define(['underscore', 'orotranslation/js/translator', 'jquery', 'jquery.validate'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.expiration_date',
        monthSelector: '[data-expiration-date-month]',
        yearSelector: '[data-expiration-date-year]'
    };

    /**
     * @export orob2bpayment/js/validator/credit-card-expiration-date-not-blank
     */
    return [
        'credit-card-expiration-date-not-blank',
        function(value, element, param) {
            param = _.extend({}, defaultParam, param);
            var form = $(element).parents('form');
            var year = form.find(param.yearSelector).val();
            var month = form.find(param.monthSelector).val();

            return (year.length > 0 && month.length > 0);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
