/*global define*/
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');

    var defaultParam = {
        message: 'All product prices should be unique.'
    };

    /**
     * @param {HTMLElement} element
     * @return {string}
     */
    function getStringifiedValues(element) {
        var price = $(element);

        var priceList = price.find('input[name$="[priceList]"]').val();
        var quantity  = price.find('input[name$="[quantity]"]').val();
        var unit      = price.find('select[name$="[unit]"] option:selected').val();
        var currency  = price.find('select[name$="[currency]"] option:selected').val();

        if (
            !priceList || !priceList.trim() ||
            !quantity || !quantity.trim() ||
            !unit || !unit.trim() ||
            !currency || !currency.trim()
        ) {
            return '';
        }

        return [priceList, parseFloat(quantity), unit, currency].join(':');
    }

    /**
     * @export oropricing/js/validator/unique-product-prices
     */
    return [
        'Oro\\Bundle\\PricingBundle\\Validator\\Constraints\\UniqueProductPrices',
        function(value, element) {
            var processedPrices = [];
            var $container = $(element).closest('.oro-item-collection');

            var duplicate = _.find($container.find('.oro-multiselect-holder'), function(price) {
                var stringifiedPrice = getStringifiedValues(price);

                if (stringifiedPrice === '') {
                    return;
                }
                if (processedPrices.indexOf(stringifiedPrice) !== -1) {
                    // duplicates are found
                    return true;
                } else {
                    processedPrices.push(stringifiedPrice);
                }
            });

            return duplicate === void 0;
        },
        function(param) {
            param = _.extend({}, defaultParam, param);
            messenger.notificationFlashMessage('error', __(param.message, {}));

            return false;
        }
    ];
});
