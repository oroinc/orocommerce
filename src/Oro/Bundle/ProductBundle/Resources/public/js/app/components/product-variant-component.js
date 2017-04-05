define(function(require) {
    'use strict';

    var ProductVariantComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    ProductVariantComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            productVariantFieldsSelector: 'input[type=checkbox]',
            datagridName: 'product-product-variants-edit'
        },

        variantFieldCheckboxes: [],

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.variantFieldCheckboxes = this.options._sourceElement.find(this.options.productVariantFieldsSelector);

            this.options._sourceElement
                .on('change', this.productVariantFieldsSelector, _.bind(this.onVariantFieldChange, this));

            mediator.on('grid_load:complete', this.updateVisibilityChange, this);
        },

        onVariantFieldChange: function() {
            var variantFields = [];
            var self = this;
            this.variantFieldCheckboxes.each(function(idx, el) {
                if (el.checked) {
                    variantFields.push(self.getFieldName(el));
                }
            });

            // Set null value instead of empty array (empty array will not be sent)
            if (variantFields.length === 0) {
                variantFields = null;
            }

            this._updateProductVariantsGrid(variantFields);
        },

        /**
         *
         * @param selectedFields
         * @private
         */
        _updateProductVariantsGrid: function(selectedFields) {
            mediator.trigger('datagrid:setParam:' + this.options.datagridName, 'selectedVariantFields', selectedFields);
            mediator.trigger('datagrid:setParam:' + this.options.datagridName, 'gridDynamicLoad', 1);
            mediator.trigger('datagrid:doReset:' + this.options.datagridName);
        },

        updateVisibilityChange: function() {
            if (this.variantFieldCheckboxes.length > 0) {
                var self = this;
                var gridName = this.options.datagridName;
                this.variantFieldCheckboxes.each(function(idx, el) {
                    var columnName = self.getFieldName(el);
                    mediator.trigger('datagrid:changeColumnParam:' + gridName, columnName, 'renderable', el.checked);
                });
            }
        },

        /**
         *
         * @param el
         * @returns {string}
         */
        getFieldName: function(el) {
            return $(el).attr('data-original-name');
        },

        /**
         * {@inheritDoc}
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off('change');
            mediator.off('grid_load:complete', this.updateVisibilityChange, this);

            ProductVariantComponent.__super__.dispose.call(this);
        }
    });

    return ProductVariantComponent;
});
