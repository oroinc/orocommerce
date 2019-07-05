define(function(require) {
    'use strict';

    var QuickAddCopyPasteFormComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    require('jquery.validate');

    QuickAddCopyPasteFormComponent = BaseComponent.extend({
        /**
         * @property
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
         * @property {jQuery.validator}
         */
        validator: null,

        /**
         * @property {number} - contains ID of request that was launched after form submit
         */
        requestId: null,

        /**
         * @property {number} - number items that can't be added to order
         */
        errorCount: 0,

        /**
         * @property {boolean} - can block interface from user interaction during form submit processing
         */
        disabled: false,

        /**
         * @inheritDoc
         */
        constructor: function QuickAddCopyPasteFormComponent() {
            this._onSubmit = this._onSubmit.bind(this);

            // Use debounce to give time to apply jquery.validate changing
            this.onFieldChange = _.debounce(this.onFieldChange.bind(this), 50);

            QuickAddCopyPasteFormComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$form = this.options._sourceElement;
            this.$submitButton = this.$form.find('button:submit');
            this.$field = this.$form.find(this.field);
            this.validator = this.$form.validate();

            // Run element validation to make it validated immediately on input (i.e. don't wait for form submit)
            this.validator.element(this.$field[0]);

            this.$form.on('submit', this._onSubmit);
            // Listen the same events what used by jquery.validate
            this.$field.on('keyup focusout', this.onFieldChange);

            var regexParts = this.$field.data('item-parse-pattern').match(/^\/(.*?)\/(g?i?m?y?)$/);

            if (regexParts === null || regexParts.length < 2) {
                throw new Error('The field must must have a data attribute with valid RegExp string');
            }

            this.itemParseRegex = new RegExp(regexParts[1], regexParts[2]);
        },

        /**
         * Binds listeners of events related to adding item to quick order form
         */
        bindRowEvents: function() {
            mediator.on('quick-add-form:requestProductsBySku', function(data) {
                this.requestId = data.requestId;
            }, this);
            mediator.on('quick-add-form:successProductsBySku', function(data) {
                if (data.requestId !== this.requestId) {
                    return;
                }

                this.requestId = null;

                if (!this.hasUnresolvedItems()) {
                    this.onSubmitComplete();
                }
            }, this);
            mediator.on('quick-add-form:failProductsBySku', function(data) {
                if (data.requestId !== this.requestId) {
                    return;
                }

                this.requestId = null;
                this.onSubmitComplete();
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
            }, this);
            mediator.on('autocomplete:productFound', this.onAutocompleteSuccess, this);
            mediator.on('autocomplete:productNotFound', this.onAutocompleteError, this);
            mediator.on('quick-add-form-item:item-valid', this.onItemSuccess, this);
            mediator.on('quick-add-form-item:unit-invalid', this.onUnitError, this);
            mediator.on('quick-add-copy-paste-form:update-product', this.onProductUpdate, this);
        },

        /**
         * Unbinds listeners of events related to adding item to quick order form
         */
        unbindRowEvents: function() {
            mediator.off('quick-add-form:requestProductsBySku', null, this);
            mediator.off('quick-add-form:successProductsBySku', null, this);
            mediator.off('quick-add-form:failProductsBySku', null, this);
            mediator.off('autocomplete:productFound', this.onAutocompleteSuccess, this);
            mediator.off('autocomplete:productNotFound', this.onAutocompleteError, this);
            mediator.off('quick-add-form-item:item-valid', this.onItemSuccess, this);
            mediator.off('quick-add-form-item:unit-invalid', this.onUnitError, this);
            mediator.off('quick-add-copy-paste-form:update-product', this.onProductUpdate, this);
        },

        onFieldChange: function() {
            this._toggleSubmitButton(this.disabled || this.$field.hasClass('error'));
        },

        /**
         * @param {boolean} disable
         * @private
         */
        _toggleSubmitButton: function(disable) {
            var disabled = disable || this.isEmptyField();

            this.$submitButton.attr('disabled', disabled);
        },

        _onSubmit: function(e) {
            e.preventDefault();

            if (!this.validator.element(this.$field[0])) {
                return false;
            }

            this.disableForm();
            this.bindRowEvents();
            this.requestId = null;
            this.errorCount = 0;
            this._prepareFieldItems();

            // Since trigger of the following event leads to high loading of the page
            // lets give some time for disabled elements to update them appearance
            _.delay(function() {
                mediator.trigger('quick-add-copy-paste-form:submit', this.parsedItems);
            }.bind(this), 50);
        },

        onSubmitComplete: function() {
            this.unbindRowEvents();
            this.updateFieldValue();
            this.enableForm();
        },

        /**
         * Blocks form from user interaction
         */
        disableForm: function() {
            this.disabled = true;
            this.$field.attr('disabled', true);
            this._toggleSubmitButton(true);
        },

        /**
         * Enable form to user interaction
         */
        enableForm: function() {
            this.disabled = false;
            this.$field.removeAttr('disabled');
            this._toggleSubmitButton(false);
        },

        /**
         * Parses text in field, creates an array of items, and merges items that have the same sku and unit
         *
         * @private
         */
        _prepareFieldItems: function() {
            this.parsedItems = [];
            this.fieldItemsLines = _.compact(this.$field.val().split('\n'));

            _.each(this.fieldItemsLines, function(line) {
                var parts = line.match(this.itemParseRegex);

                if (!parts || parts.length < 3) {
                    // row must match the pattern and contains SKU and quantity

                    return;
                }

                var product = {
                    raw: [parts[0]],
                    sku: parts[1].toUpperCase(),
                    quantity: parseFloat(parts[2]),
                    unit: parts[3] ? parts[3].toLowerCase() : void 0
                };

                var existItem = _.findWhere(this.parsedItems, {sku: product.sku, unit: product.unit});

                if (existItem) {
                    existItem.raw = existItem.raw.concat(product.raw);
                    existItem.quantity += product.quantity;
                } else {
                    this.parsedItems.push(product);
                }
            }, this);
        },

        /**
         * Generates function to compare item with predefined object
         *
         * @param {Object} productInfo - object that items will be compared with
         * @return {function}
         * @private
         */
        _rowMatcher: function(productInfo) {
            var query = {
                sku: productInfo.sku ? productInfo.sku.toUpperCase() : '',
                units: [
                    productInfo.unit ? productInfo.unit.toLowerCase() : '',
                    productInfo.unit_deferred ? productInfo.unit_deferred.toLowerCase() : ''
                ]
            };

            return _.partial(QuickAddCopyPasteFormComponent.matcher, query);
        },

        /**
         * @param {object} data
         */
        onProductUpdate: function(data) {
            this.updateParsedItems(data.item);

            if (!this.hasUnresolvedItems()) {
                this.onSubmitComplete();
            }
        },

        /**
         * @param {object} item
         */
        updateParsedItems: function(item) {
            var index = _.findIndex(this.parsedItems, this._rowMatcher(item));

            if (index === -1) {
                return;
            }

            this.fieldItemsLines = _.difference(this.fieldItemsLines, this.parsedItems[index].raw);
            this.parsedItems.splice(index, 1);
        },

        /**
         * Sets actual value to field
         */
        updateFieldValue: function() {
            this.$field.val(this.fieldItemsLines.join('\n'));
        },

        /**
         * @param {object} data
         */
        onAutocompleteSuccess: function(data) {
            if (data.requestId !== this.requestId) {
                return;
            }

            if (!this.hasUnresolvedItems()) {
                this.onSubmitComplete();
            }
        },

        /**
         * @param {object} data
         */
        onAutocompleteError: function(data) {
            if (data.requestId !== this.requestId) {
                return;
            }

            data.$el.closest('[data-role="row"]').find('[data-role="row-remove"]').click();
            $('.add-list-item').data('row-add-only-one', true).click();
            this._showErrorMessage();

            this.errorCount++;

            if (!this.hasUnresolvedItems()) {
                this.onSubmitComplete();
            }
        },

        /**
         * @param {object} data
         */
        onItemSuccess: function(data) {
            this.updateParsedItems(data.item);

            if (!this.hasUnresolvedItems()) {
                this.onSubmitComplete();
            }
        },

        /**
         * @param {object} data
         */
        onUnitError: function(data) {
            if (_.some(this.parsedItems, this._rowMatcher(data.item))) {
                data.$el.closest('[data-role="row"]').find('[data-role="row-remove"]').click();
                $('.add-list-item').data('row-add-only-one', true).click();
                this._showErrorMessage();
                this.errorCount++;

                if (!this.hasUnresolvedItems()) {
                    this.onSubmitComplete();
                }
            }
        },

        _showErrorMessage: function() {
            var _errorField = this.$field.attr('name');
            var _customError = {};

            _customError[_errorField] = {errors: [__('oro.product.frontend.quick_add.copy_paste.error')]};

            if (!this.isEmptyField()) {
                this.validator.showBackendErrors(_customError);
            }
        },

        isEmptyField: function() {
            return this.$field.val().length === 0;
        },

        /**
         * Checks if component completely processed all items
         *
         * @return {boolean}
         */
        hasUnresolvedItems: function() {
            return this.parsedItems.length > this.errorCount || this.requestId !== null;
        },

        dispose: function() {
            if (!this.disposed) {
                return;
            }

            this.$form.off('submit', this._onSubmit);
            this.$field.off('keyup focusout', this.onFieldChange);
            this.unbindRowEvents();

            delete this.validator;
            delete this.fieldItemsLines;
            delete this.parsedItems;

            QuickAddCopyPasteFormComponent.__super__.dispose.call(this);
        }
    }, {
        matcher: function(query, parsedItem) {
            if (parsedItem.sku !== query.sku) {
                return false;
            } else if (parsedItem.unit === void 0) {
                return true;
            } else {
                return query.units.indexOf(parsedItem.unit) !== -1;
            }
        }
    });

    return QuickAddCopyPasteFormComponent;
});
