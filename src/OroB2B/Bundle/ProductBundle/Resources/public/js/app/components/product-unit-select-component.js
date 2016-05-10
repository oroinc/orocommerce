/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductUnitSelectComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductUnitSelectComponent = BaseComponent.extend({

        defaultQuantity: 1,

        /**
         * @property {Object}
         */
        options: {
            prices: {}
        },

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.defaultQuantity = this.calculateDefaultQuantity();
            this.initSelect();
            this.initQuantityInput();
        },

        initSelect: function() {
            var productUnits = this.options._sourceElement.data('product-units');
            var select = this.options._sourceElement.find('select');
            select.empty();
            for (var productCode in productUnits) {
                select.append($('<option></option>').attr('value', productCode).text(productUnits[productCode]));
            }
            select.change();
        },

        initQuantityInput: function() {
            $('[data-name="field-quantity"]', this.options._sourceElement).val(this.defaultQuantity);
        },

        calculateDefaultQuantity: function() {
            var minimumQuantity;
            _.each(this.options.prices, function(price) {
                if (minimumQuantity === undefined || price.quantity < minimumQuantity) {
                    minimumQuantity = price.quantity;
                }
            });
            return minimumQuantity !== undefined ? minimumQuantity : 1;
        }
    });

    return ProductUnitSelectComponent;
});
