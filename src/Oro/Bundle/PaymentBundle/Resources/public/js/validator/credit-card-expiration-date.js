define(['underscore', 'orotranslation/js/translator', 'jquery', 'jquery.validate'
], function(_, __, $) {
    'use strict';

    const defaultParam = {
        message: 'oro.payment.validation.month',
        formSelector: '[data-credit-card-form], form',
        monthSelector: '[data-expiration-date-month]',
        yearSelector: '[data-expiration-date-year]'
    };

    /**
     * @export oropayment/js/validator/credit-card-expiration-date
     */
    return [
        'credit-card-expiration-date',
        function(value, element, param) {
            param = _.extend({}, defaultParam, param);
            const form = $(element).closest(param.formSelector);
            const year = form.find(param.yearSelector).val();
            const month = form.find(param.monthSelector).val();
            const now = new Date();

            if (year.length) {
                if (parseInt(year, 10) % 100 === now.getFullYear() % 100) {
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
