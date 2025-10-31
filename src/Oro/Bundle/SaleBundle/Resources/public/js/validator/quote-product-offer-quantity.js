import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

const defaultParam = {
    blankOfferMessage: 'oro.sale.quoteproductoffer.configurable.offer.blank'
};

export default [
    'AllowedQuoteDemandQuantity',
    function(value, element) {
        const valid = $(element).data('valid');

        if (valid === undefined) {
            return true;
        }

        return Boolean(valid);
    },
    function(param) {
        param = _.extend({}, defaultParam, param);
        return __(param.blankOfferMessage);
    }
];
