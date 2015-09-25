/*global define*/
define([
    'jquery', 'underscore', 'orotranslation/js/translator'
], function($, _, __) {
    'use strict';

    var defaultParam = {
        blankOfferMessage: 'Please enter valid quantity'
    };

    return [
        'OroB2B\\Bundle\\SaleBundle\\Validator\\Constraints\\ConfigurableQuoteProductOffer',
        function(value, element) {
            var valid = $(element).data('valid');

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
