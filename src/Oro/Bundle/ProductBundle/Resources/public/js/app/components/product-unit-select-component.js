define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const UnitsAsRadioGroupView = require('oroproduct/js/app/views/units-as-radio-group-view').default;
    const UnitsUtil = require('oroproduct/js/app/units-util');

    const ProductUnitSelectComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            unitLabel: 'oro.product.product_unit.%s.label.full',
            singleUnitMode: false,
            singleUnitModeCodeVisible: false,
            configDefaultUnit: null,
            UNIT_COUNT_AS_GROUP: 2,
            UNIT_LENGTH_AS_GROUP: 5
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
            const $select = this.options._sourceElement.find('select');
            const {productModel} = this.options;

            this.initSelect();
            if (this.displayUnitsAsGroup()) {
                this.unitsAsRadioGroupView = new UnitsAsRadioGroupView({
                    autoRender: true,
                    model: productModel,
                    units: UnitsUtil.getUnitsLabel(productModel),
                    $select: $select
                });
                $select.after(this.unitsAsRadioGroupView.$el);
            } else {
                $select.removeClass('invisible');
            }
            this.options._sourceElement.removeClass('simple-placeholder');
            this.options._sourceElement.children().removeClass('simple-placeholder');
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
        },

        /**
         * Determines to show units as a radio group
         * @returns {boolean}
         */
        displayUnitsAsGroup() {
            const {productModel} = this.options;

            if (!productModel) {
                return false;
            }

            const units = UnitsUtil.getUnitsLabel(productModel);

            return Object.keys(units).length === this.options.UNIT_COUNT_AS_GROUP &&
                _.every(units, label => label.length <= this.options.UNIT_LENGTH_AS_GROUP);
        }
    });

    return ProductUnitSelectComponent;
});
