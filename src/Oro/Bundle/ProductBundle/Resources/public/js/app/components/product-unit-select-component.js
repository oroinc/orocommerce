define(function(require) {
    'use strict';

    var ProductUnitSelectComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var UnitsUtil = require('oroproduct/js/app/units-util');
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

            var $select = this.options._sourceElement.find('select');
            UnitsUtil.updateSelect(model, $select);

            var productUnits = _.keys(model.get('product_units'));
            if (this.isProductApplySingleUnitMode(productUnits)) {
                if (this.options.singleUnitModeCodeVisible) {
                    $select.parent().append('<span class="unit-label">' + productUnits[0] + '</span>');
                }
                $select.inputWidget('dispose');
                $select.addClass('no-input-widget').hide();
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
