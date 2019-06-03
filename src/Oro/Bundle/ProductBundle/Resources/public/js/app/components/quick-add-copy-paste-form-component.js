define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ProductHelper = require('oroproduct/js/app/product-helper');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    QuickAddCopyPasteFormComponent = BaseComponent.extend({
        /**
         * {@inheritDoc}
         */
        field: 'textarea',

        /**
         * @property {Array}
         */
        parsedItems: [],

        /**
         * @property {Array}
         */
        fieldItemsLines: [],

        /**
         * {@inheritDoc}
         */
        fieldEvent: 'change blur keyup',

        validator: null,

        /**
         * @inheritDoc
         */
        constructor: function QuickAddCopyPasteFormComponent() {
            QuickAddCopyPasteFormComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$form = this.options._sourceElement;
            var $field = this.$form.find(this.field);

            this.$form.on('submit', _.bind(this._onSubmit, this));
            $field.on(this.fieldEvent, _.bind(this._handleFieldEvent, this));

            this.validator = this.$form.validate();
            delete this.validator.settings.onkeyup; // validate only on change/blur/submit

            mediator.on('quick-add-form-item:item-valid', this.onAutocompleteSuccess, this);
            mediator.on('quick-add-form-item:unit-invalid', this.onUnitError, this);
            mediator.on('autocomplete:productNotFound', this.onAutocompleteError, this);
            mediator.on('quick-add-copy-paste-form:update-product', this.onProductUpdate, this);
        },

        _handleFieldEvent: function(e) {
            if (e.type === 'keyup') {
                this._toggleSubmitButton();
            } else {
                var val = $(e.target).val();
                $(e.target).val(ProductHelper.trimAllWhiteSpace(val));
            }
        },

        _toggleSubmitButton: function() {
            this.$form.validate();
            var disabled = !this.$form.valid() || $(this.field, this.$form).val() === '';
            $('button:submit', this.$form)
                .toggleClass('btn--primary btn--disabled', disabled)
                .toggleClass('btn--info', !disabled);
        },

        _onSubmit: function(e) {
            e.preventDefault();

            var form = $(e.target);

            form.validate();

            if (!form.valid()) {
                return false;
            }

            this._prepareFieldItems(form);
            mediator.trigger('quick-add-copy-paste-form:submit', this.parseInput($(this.field, form).val()));
        },

        _prepareFieldItems: function(form) {
            this.fieldItemsLines = _.map($(this.field, form).val().trim().split('\n'), function(itemLine) {
                return {processed: false, line: itemLine};
            });
        },

        parseInput: function(inputValue) {
            this.parsedItems = [];

            _.each(inputValue.split('\n'), function(valueRow, index) {
                var values = valueRow.split(/[;, \t]+/);
                var product = {
                    sku: values[0] ? values[0].trim().toUpperCase() : undefined,
                    quantity: values[1] ? parseFloat(values[1].trim()) : undefined,
                    unit: values[2] ? values[2].trim() : undefined,
                    index: index
                };

                var skuUnitMatcher = _.matcher({sku: product.sku, unit: product.unit});
                var existingProductIndex = _.findIndex(this.parsedItems, skuUnitMatcher);

                if (existingProductIndex === -1) {
                    this.addParsedItem(product);
                    return;
                }

                var existingProduct = _.find(this.parsedItems, skuUnitMatcher);
                this.updateParsedItem(existingProduct, existingProductIndex, product);
            }, this);

            return this.parsedItems;
        },

        addParsedItem: function(data) {
            this.parsedItems.push({
                sku: data.sku,
                quantity: data.quantity,
                unit: data.unit,
                index: [data.index]
            });
        },

        updateParsedItem: function(existingProduct, existingProductIndex, data) {
            existingProduct.index.push(data.index);

            this.parsedItems[existingProductIndex] = {
                sku: data.sku,
                quantity: existingProduct.quantity + data.quantity,
                unit: data.unit,
                index: existingProduct.index
            };
        },

        _rowMatcher: function(data) {
            var dataItem = {
                sku: data.item.sku || '',
                unit: data.item.unit || '',
                unit_deferred: data.item.unit_deferred || ''
            };

            return function(parsedItem) {
                var parsedSku = parsedItem.sku.toUpperCase();
                var parsedUnit = (parsedItem.unit || '').toLowerCase();

                if (parsedUnit) {
                    return parsedSku === dataItem.sku.toUpperCase() && (
                        parsedUnit === dataItem.unit.toLowerCase() ||
                        parsedUnit === dataItem.unit_deferred.toLowerCase()
                    );
                } else {
                    return parsedSku === dataItem.sku.toUpperCase();
                }
            };
        },

        /**
         * @param {object} data
         */
        onAutocompleteSuccess: function(data) {
            this._updateField(data);
        },

        /**
         * @param {object} data
         */
        onProductUpdate: function(data) {
            this._updateField(data);
        },

        _updateField: function(data) {
            var form = this.$form;
            var newInputValueLines = [];
            var itemIndex = _.findIndex(this.parsedItems, this._rowMatcher(data));

            if (itemIndex === -1) {
                return;
            }

            _.each(this.fieldItemsLines, function(itemLine, i) {
                if (itemLine.processed === true) {
                    return;
                }

                if (this.parsedItems[itemIndex].index.indexOf(i) !== -1) {
                    this.fieldItemsLines[i].processed = true;
                    return;
                }

                newInputValueLines.push(itemLine.line);
            }, this);

            this.parsedItems.splice(itemIndex, 1);
            $(this.field, form).val(newInputValueLines.join('\n')).trigger('keyup');
        },

        /**
         * @param {object} data
         * @param {boolean} forceRemove
         */
        onAutocompleteError: function(data) {
            this._showErrorMessage();
        },

        onUnitError: function(data) {
            this.onAutocompleteError(data);
        },

        _showErrorMessage: function() {
            var _errorField = $(this.field, this.$form).attr('name');
            var _customError = [];
            _customError[_errorField] = __('oro.product.frontend.quick_add.copy_paste.error');

            if ($(this.field, this.$form).val().length > 0) {
                this.validator.showErrors(_customError);
            }
        },

        dispose: function() {
            if (!this.disposed) {
                return;
            }

            delete this.validator;
            QuickAddCopyPasteFormComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddCopyPasteFormComponent;
});
