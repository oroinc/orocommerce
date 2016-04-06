define(['underscore', 'orotranslation/js/translator', 'jquery', 'jquery.validate'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.expiration_date',
        monthSelector: '.checkout__form__select_exp-month select',
        yearSelector: '.checkout__form__select_exp-year select'
    };

    return [
        'creditCardExpirationDateNotBlank',
        function(value, element, param) {
            param = _.extend({}, defaultParam, param);
            var year = $(param.yearSelector).val();
            var month = $(param.monthSelector).val();

            return (year.length > 0 && month.length > 0)
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ];
});
