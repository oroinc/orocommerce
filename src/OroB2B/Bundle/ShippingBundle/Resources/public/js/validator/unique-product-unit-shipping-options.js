/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'jquery'
], function(_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'All product units should be unique.'
    };

    /**
     * @param {Element} element
     */
    function getRealElement(element) {
        return $(element).closest('.product-shipping-options-collection');
    }

    /**
     * @param {Element} element
     */
    function getShippingOptionValues(element) {
        var shippingOption = $(element);
        var unit = shippingOption.find('select[name$="[productUnit]"] option:selected');

        return {
            unit: unit ? unit.val() : undefined
        };
    }

    /**
     * @param {Array} shippingOptions
     * @param {Object} search
     * @returns {Object}
     */
    function findDuplication(shippingOptions, search) {
        return _.find(shippingOptions, function(obj) {
            return obj.unit === search.unit;
        });
    }

    /**
     * @export orob2bshipping/js/validator/unique-product-unit-shipping-options
     */
    return [
        'OroB2B\\Bundle\\ShippingBundle\\Validator\\Constraints\\UniqueProductUnitShippingOptions',

        /**
         * @param {String} value
         * @param {Element} element
         * @returns {Boolean}
         */
        function(value, element) {
            var noDuplicationFound = true;
            var processedShippingOptions = [];

            _.each(getRealElement(element).find('.list-item'), function(shippingOption) {
                var data = getShippingOptionValues(shippingOption);

                if (_.isEmpty(data.unit.trim())) {
                    return;
                }

                if (findDuplication(processedShippingOptions, data) === undefined) {
                    processedShippingOptions.push(data);
                } else {
                    noDuplicationFound = false;
                }
            });

            return noDuplicationFound;
        },

        /**
         * @param {Object} param
         * @returns {String}
         */
        function(param) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
