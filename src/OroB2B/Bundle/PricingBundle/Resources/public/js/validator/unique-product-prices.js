/*global define*/
define(['underscore', 'orotranslation/js/translator', 'jquery'
], function (_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'All product prices should be unique.'
    };

    /**
     * @param {Element} element
     */
    function getRealElement(element) {
        return $(element).parents('.oro-product-price-collection');
    }

    /**
     * @param {Element} element
     */
    function getPriceValues(element) {
        var price = $(element);

        var priceList = price.find('input[name$="[priceList]"]');
        var quantity  = price.find('input[name$="[quantity]"]');
        var unit      = price.find('select[name$="[unit]"] option:selected');
        var currency  = price.find('select[name$="[currency]"] option:selected');

        return {
            priceList: priceList ? priceList.val() : undefined,
            quantity: quantity ? quantity.val() : undefined,
            unit: unit ? unit.val() : undefined,
            currency: currency ? currency.val() : undefined
        };
    }

    /**
     * @param {array} array
     * @param {Object} search
     * @returns {bool}
     */
    function findDuplication(array, search) {
        return _.find(array, function (obj) {
            return obj.priceList == search.priceList &&
                obj.quantity == search.quantity &&
                obj.unit == search.unit &&
                obj.currency == search.currency;
        });
    }

    /**
     * @export orob2bpricing/js/validator/unique-product-prices
     */
    return [
        'OroB2B\\Bundle\\PricingBundle\\Validator\\Constraints\\UniqueProductPrices',
        function (value, element) {
            var noDuplicationFound = true,
                processed = [];

            getRealElement(element).find('.oro-multiselect-holder').each(function(index, price){
                var data = getPriceValues(price);

                if (_.isEmpty(data.priceList.trim()) ||
                    _.isEmpty(data.quantity.trim()) ||
                    _.isEmpty(data.unit.trim()) ||
                    _.isEmpty(data.currency.trim())
                ) {
                    return;
                }

                if (findDuplication(processed, data) == undefined) {
                    processed.push(data);
                } else {
                    noDuplicationFound = false;
                }
            });

            return noDuplicationFound;
        },
        function (param, element) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
