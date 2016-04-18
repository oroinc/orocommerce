/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ProductUnitSelectComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductUnitSelectComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            defaultQuantity: 1
        },

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

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
            $('[data-role="field-quantity"]', this.options._sourceElement).val(this.options.defaultQuantity);
        }
    });

    return ProductUnitSelectComponent;
});
