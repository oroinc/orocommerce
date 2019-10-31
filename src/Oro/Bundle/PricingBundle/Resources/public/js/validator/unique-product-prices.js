define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const messenger = require('oroui/js/messenger');

    const defaultParam = {
        message: 'All product prices should be unique.'
    };

    /**
     * @param {HTMLElement} element
     * @return {string}
     */
    function getStringifiedValues(element) {
        const price = $(element);

        const priceList = price.find('input[name$="[priceList]"]').val();
        const quantity = price.find('input[name$="[quantity]"]').val();
        const unit = price.find('select[name$="[unit]"] option:selected').val();
        const currency = price.find('select[name$="[currency]"] option:selected').val();

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
            const processedPrices = [];
            const $container = $(element).closest('.oro-item-collection');

            const duplicate = _.find($container.find('.oro-multiselect-holder'), function(price) {
                const stringifiedPrice = getStringifiedValues(price);

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
