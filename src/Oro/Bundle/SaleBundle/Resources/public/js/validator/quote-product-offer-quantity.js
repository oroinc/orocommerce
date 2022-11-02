define([
    'jquery', 'underscore', 'orotranslation/js/translator'
], function($, _, __) {
    'use strict';

    const defaultParam = {
        blankOfferMessage: 'oro.sale.quoteproductoffer.configurable.offer.blank'
    };

    return [
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
});
