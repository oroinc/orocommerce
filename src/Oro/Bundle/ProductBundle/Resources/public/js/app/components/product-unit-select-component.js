define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const UnitsUtil = require('oroproduct/js/app/units-util');
    const _ = require('underscore');

    const ProductUnitSelectComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function ProductUnitSelectComponent(options) {
            ProductUnitSelectComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.initSelect();
        },

        initSelect: function() {
            const model = this.options.productModel || null;
            if (!model) {
                return;
            }

            const $select = this.options._sourceElement.find('select');
            UnitsUtil.updateSelect(model, $select);

            const productUnits = _.keys(model.get('product_units'));
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
