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
                .on('change', this.productVariantFieldsSelector, this.onVariantFieldChange.bind(this));

            this.updateVisibilityChange();
            this.listenTo(mediator, 'grid_load:complete', function(collection) {
                if (collection.inputName === this.options.datagridName) {
                    this.updateVisibilityChange();
                }
            });
        },

        onVariantFieldChange: function() {
            let variantFields = [];
            this.variantFieldCheckboxes.each((idx, el) => {
                if (el.checked) {
                    variantFields.push(this.getFieldName(el));
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
            this.updateVisibilityChange();
            const gridName = this.options.datagridName;
            mediator.trigger(`datagrid:setParam:${gridName}`, 'selectedVariantFields', selectedFields);
            mediator.trigger(`datagrid:setParam:${gridName}`, 'gridDynamicLoad', 1);
            mediator.trigger(`datagrid:doReset:${gridName}`);
        },

        updateVisibilityChange: function() {
            if (this.variantFieldCheckboxes.length > 0) {
                const gridName = this.options.datagridName;
                this.variantFieldCheckboxes.each((idx, el) => {
                    const columnName = this.getFieldName(el);
                    mediator.trigger(`datagrid:changeColumnParam:${gridName}`, columnName, 'renderable', el.checked);

                    const {grid} = this.productVariantsGridComponent;
                    if (grid) {
                        const {[columnName]: column} = grid.collection.initialState.columns;
                        if (column) {
                            // patch initial state to make variant column renderable even after grid reset
                            grid.collection.patchInitialState({
                                columns: {
                                    ...grid.collection.initialState.columns,
                                    [columnName]: {...column, renderable: el.checked}
                                }
                            });
                        }
                    }
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
