define(['underscore', 'orotranslation/js/translator', 'jquery', 'jquery.validate'
], function(_, __, $) {
    'use strict';

    const defaultParam = {
        message: 'oro.payment.validation.expiration_date',
        formSelector: '[data-credit-card-form], form',
        monthSelector: '[data-expiration-date-month]',
        yearSelector: '[data-expiration-date-year]'
    };

    /**
     * @export oropayment/js/validator/credit-card-expiration-date-not-blank
     */
    return [
        'credit-card-expiration-date-not-blank',
        function(value, element, param) {
            param = _.extend({}, defaultParam, param);
            const form = $(element).closest(param.formSelector);
            const year = form.find(param.yearSelector).val();
            const month = form.find(param.monthSelector).val();

            return (year.length > 0 && month.length > 0);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
