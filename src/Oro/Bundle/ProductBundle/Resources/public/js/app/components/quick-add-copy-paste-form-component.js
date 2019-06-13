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
         * @property {Array.<string>} - contains IDs of request that were launched after form submit
         */
        requests: null,

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
            mediator.on('autocomplete:requestProductBySku', function(data) {
                this.registerRequest(data.requestId);
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
            mediator.off('autocomplete:requestProductBySku', null, this);
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

            if (!this.validator.form()) {
                return false;
            }

            this.disableForm();
            this.requests = [];
            this.errorCount = 0;
            this._prepareFieldItems();

            mediator.trigger('quick-add-copy-paste-form:submit', this.parsedItems);
        },

        /**
         * Blocks form from user interaction
         */
        disableForm: function() {
            this.disabled = true;
            this.$field.attr('disabled', true);
            this.bindRowEvents();
            this._toggleSubmitButton(true);
        },

        /**
         * Enable form to user interaction
         */
        enableForm: function() {
            this.disabled = false;
            this.$field.removeAttr('disabled');
            this.unbindRowEvents();
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
                    existItem.raw.concat(product.raw);
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

            function matcher(query, parsedItem) {
                if (parsedItem.sku !== query.sku) {
                    return false;
                } else if (parsedItem.unit === void 0) {
                    return true;
                } else {
                    return query.units.indexOf(parsedItem.unit) !== -1;
                }
            }

            return _.partial(matcher, query);
        },

        /**
         * @param {object} data
         */
        onProductUpdate: function(data) {
            this._updateField(data.item);

            if (!this.hasUnresolvedItems()) {
                this.enableForm();
            }
        },

        /**
         * @param {object} item
         */
        _updateField: function(item) {
            var index = _.findIndex(this.parsedItems, this._rowMatcher(item));

            if (index === -1) {
                return;
            }

            this.fieldItemsLines = _.difference(this.fieldItemsLines, this.parsedItems[index].raw);
            this.$field.val(this.fieldItemsLines.join('\n'));
            this.parsedItems.splice(index, 1);
        },

        /**
         * @param {object} data
         */
        onAutocompleteSuccess: function(data) {
            if (!this.isOwnRequest(data.requestId)) {
                return;
            }

            this.unregisterRequest(data.requestId);

            if (!this.hasUnresolvedItems()) {
                this.enableForm();
            }
        },

        /**
         * @param {object} data
         */
        onAutocompleteError: function(data) {
            if (!this.isOwnRequest(data.requestId)) {
                return;
            }

            data.$el.closest('[data-role="row"]').find('[data-role="row-remove"]').click();
            $('.add-list-item').data('row-add-only-one', true).click();
            this._showErrorMessage();

            this.unregisterRequest(data.requestId);
            this.errorCount++;

            if (!this.hasUnresolvedItems()) {
                this.enableForm();
            }
        },

        /**
         * @param {object} data
         */
        onItemSuccess: function(data) {
            this._updateField(data.item);

            if (!this.hasUnresolvedItems()) {
                this.enableForm();
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
                    this.enableForm();
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
         * Chacks if request ID present in internal list
         *
         * @param {string} requestId
         * @return {boolean}
         */
        isOwnRequest: function(requestId) {
            return this.requests.indexOf(requestId) !== -1;
        },

        /**
         * Adds request ID to internal list to be aware in listeners on request complete if that is own one
         *
         * @param {string} requestId
         */
        registerRequest: function(requestId) {
            this.requests.push(requestId);
        },

        /**
         * Removes request ID from internal list
         *
         * @param {string} requestId
         */
        unregisterRequest: function(requestId) {
            this.requests = _.without(this.requests, requestId);
        },

        /**
         * Checks if component completely processed all items
         *
         * @return {boolean}
         */
        hasUnresolvedItems: function() {
            return this.parsedItems.length > this.errorCount || !_.isEmpty(this.requests);
        },

        dispose: function() {
            if (!this.disposed) {
                return;
            }

            this.$form.off('submit', this._onSubmit);
            this.$field.off('keyup focusout', this.onFieldChange);
            this.unbindRowEvents();

            delete this.validator;
            delete this.requests;
            delete this.fieldItemsLines;
            delete this.parsedItems;

            QuickAddCopyPasteFormComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddCopyPasteFormComponent;
});
