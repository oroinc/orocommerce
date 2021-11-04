define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const $ = require('jquery');

    const ProductVariantComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            // grid is required to update variants columns
            productVariantsGridComponent: 'product-product-variants-edit'
        },

        /**
         * @property {Object}
         */
        options: {
            productVariantFieldsSelector: 'input[type=checkbox]',
            datagridName: 'product-product-variants-edit'
        },

        variantFieldCheckboxes: [],

        /**
         * @inheritdoc
         */
        constructor: function ProductVariantComponent(options) {
            ProductVariantComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.variantFieldCheckboxes = this.options._sourceElement.find(this.options.productVariantFieldsSelector);

            this.options._sourceElement
                .on('change', this.productVariantFieldsSelector, _.bind(this.onVariantFieldChange, this));

            this.updateVisibilityChange();
            this.listenTo(mediator, 'grid_load:complete', function(collection) {
                if (collection.inputName === this.options.datagridName) {
                    this.updateVisibilityChange();
                }
            });
        },

        onVariantFieldChange: function() {
            let variantFields = [];
            const self = this;
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
                const self = this;
                const gridName = this.options.datagridName;
                this.variantFieldCheckboxes.each(function(idx, el) {
                    const columnName = self.getFieldName(el);
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
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off('change');
            ProductVariantComponent.__super__.dispose.call(this);
        }
    });

    return ProductVariantComponent;
});
