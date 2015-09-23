/*global define*/
define([
    'jquery', 'underscore', 'orotranslation/js/translator'
], function($, _, __) {
    'use strict';

    var defaultParam = {
        lessQuantityMessage: 'Quantity should be grater than or equal to offered quantity.'
    };

    return [
        'OroB2B\\Bundle\\SaleBundle\\Validator\\Constraints\\ConfigurableQuoteProductOffer',
        function(value, element) {
            var quantity = $(element).data('quantity');

            if (quantity === undefined) {
                return true;
            }

            return parseFloat(value) >= parseFloat(quantity);
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            return __(param.lessQuantityMessage);
        }
    ];
});
