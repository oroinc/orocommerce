define(['underscore', 'orotranslation/js/translator', 'jquery', 'jquery.validate'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'orob2b.payment.validation.month',
        monthSelector: '.checkout__form__select_exp-month select',
        yearSelector: '.checkout__form__select_exp-year select'
    };

    return [
        'creditCardExpirationDate',
        function(value, element, param) {
            param = _.extend({}, defaultParam, param);
            var year = $(param.yearSelector).val();
            var month = $(param.monthSelector).val();
            var now = new Date();

            if (year.length) {
                if (year == now.getFullYear() % 100) {
                    return parseInt(month, 10) >= now.getMonth();
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
