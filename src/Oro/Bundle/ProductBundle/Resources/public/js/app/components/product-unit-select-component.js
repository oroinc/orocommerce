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
            unitLabel: 'oro.product.product_unit.%s.label.full',
            singleUnitMode: false,
            singleUnitModeCodeVisible: false,
            configDefaultUnit: null
        },

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.initSelect();
        },

        initSelect: function() {
            var model = this.options.productModel || null;
            if (!model) {
                return;
            }

            var productUnits = model.get('product_units');

            if (productUnits) {
                var select = this.options._sourceElement.find('select');

                if (this.isProductApplySingleUnitMode(productUnits)) {
                    if (this.options.singleUnitModeCodeVisible) {
                        select.parent().append('<span class="unit-label">' + productUnits[0] + '</span>');
                        select.remove();
                    }

                    return ;
                }

                var content = '';
                var length = productUnits.length;

                for (var i = 0; i < length; i++) {
                    var unitCode = productUnits[i];
                    content = content + '<option value=' + unitCode + '>' +
                        _.__(this.options.unitLabel.replace('%s', unitCode)) + '</option>';
                }

                select.html(content).change();
            }
        },

        isProductApplySingleUnitMode: function(productUnits) {
            if (this.options.singleUnitMode && productUnits.length === 1) {
                return productUnits[0] === this.options.configDefaultUnit;
            }

            return false;
        }
    });

    return ProductUnitSelectComponent;
});
