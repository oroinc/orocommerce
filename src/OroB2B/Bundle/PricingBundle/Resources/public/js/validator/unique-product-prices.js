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
        return $(element).closest('.oro-item-collection');
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
     * @param {Array} prices
     * @param {Object} search
     * @returns {Object}
     */
    function findDuplication(prices, search) {
        return _.find(prices, function (obj) {
            return obj.priceList === search.priceList &&
                parseFloat(obj.quantity) === parseFloat(search.quantity) &&
                obj.unit === search.unit &&
                obj.currency === search.currency;
        });
    }

    /**
     * @export orob2bpricing/js/validator/unique-product-prices
     */
    return [
        'OroB2B\\Bundle\\PricingBundle\\Validator\\Constraints\\UniqueProductPrices',
        function (value, element) {
            var noDuplicationFound = true,
                processedPrices = [];

            _.each(getRealElement(element).find('.oro-multiselect-holder'), function (price) {
                var data = getPriceValues(price);

                if (_.isEmpty(data.priceList.trim()) ||
                    _.isEmpty(data.quantity.trim()) ||
                    _.isEmpty(data.unit.trim()) ||
                    _.isEmpty(data.currency.trim())
                ) {
                    return;
                }

                if (findDuplication(processedPrices, data) === undefined) {
                    processedPrices.push(data);
                } else {
                    noDuplicationFound = false;
                }
            });

            return noDuplicationFound;
        },
        function (param) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
