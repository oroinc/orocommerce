import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import creditCardValidator from 'oropayment/js/adapter/credit-card-validator-adapter';

const defaultParam = {
    message: 'oro.payment.validation.credit_card'
};

export default [
    'credit-card-number',
    function(value, element) {
        return creditCardValidator.validate(element);
    },
    function(param) {
        param = _.extend({}, defaultParam, param);
        return __(param.message);
    }
];
