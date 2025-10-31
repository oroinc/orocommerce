import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import creditCardValidator from 'oropayment/js/adapter/credit-card-validator-adapter';

const defaultParam = {
    message: 'oro.payment.validation.credit_card_type'
};

/**
 * @export oropayment/js/validator/credit-card-type
 */
export default [
    'credit-card-type',
    function(value, element, param) {
        if (!param.hasOwnProperty('allowedCreditCards')) {
            return true;
        }

        return creditCardValidator.validate(element, param);
    },
    function(param) {
        param = _.extend({}, defaultParam, param);
        return __(param.message);
    }
];
