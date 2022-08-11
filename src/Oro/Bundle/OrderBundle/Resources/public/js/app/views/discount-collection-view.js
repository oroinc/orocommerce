define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const tools = require('oroui/js/tools');
    const LoadingMask = require('oroui/js/app/views/loading-mask-view');
    const NumberFormatter = require('orolocale/js/formatter/number');

    const DiscountCollectionView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            discountType: null,
            totalType: null,
            dialogAlias: 'add-order-discount-dialog',
            percentType: null,
            selectors: {
                discountsSumSelector: '[data-ftid=oro_order_type_discountsSum]',
                deleteButton: 'a[data-role="remove"]',
                hiddenCollection: '[data-role="hidden-collection"]',
                hiddenInputsForIndex: 'input[name*="[INDEX]"]',
                parentForLoadingMask: '.responsive-section',
                formFields: {
                    description: '[data-role=description]',
                    type: '[data-role=type]',
                    percent: '[data-role=percent]',
                    amount: '[data-role=amount]',
                    value: '[data-role=value]'
                }
            }
        },

        listen: {
            'totals:update mediator': 'updateSumAndValidators',
            'widget_initialize mediator': 'attachDialogListeners',
            'entry-point:order:load mediator': 'refreshCollectionBlock'
        },

        /**
         * @inheritdoc
         */
        constructor: function DiscountCollectionView(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            DiscountCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        events: function() {
            const events = {};
            events['click ' + this.options.selectors.deleteButton] = 'onDeleteClick';

            return events;
        },

        /**
         * @param {Object} response
         */
        refreshCollectionBlock: function(response) {
            if ('discounts' in response) {
                const collectionBlockHtml = $(response.discounts).html();
                this.$el.html(collectionBlockHtml);
                this.$el.trigger('content:changed');
            }
            this._hideLoading();
        },

        /**
         * @param {Object} subtotals
         */
        updateSumAndValidators: function(subtotals) {
            const $discountsSumElement = this.$el.closest('form').find(this.options.selectors.discountsSumSelector);
            const dataValidation = $discountsSumElement.data('validation');
            let discountsSum = 0;
            let total = 0;

            const self = this;
            _.each(subtotals.subtotals, function(subtotal) {
                if (subtotal.type === self.options.discountType) {
                    discountsSum += subtotal.amount;
                }

                if (subtotal.type === self.options.totalType) {
                    total = subtotal.amount;
                }
            });

            discountsSum = NumberFormatter.formatDecimal(discountsSum);
            $discountsSumElement.val(discountsSum);

            if (dataValidation && !_.isEmpty(dataValidation.Range)) {
                dataValidation.Range.max = NumberFormatter.formatDecimal(total);
            }

            const validator = $($discountsSumElement.closest('form')).validate();
            if (validator) {
                validator.element($discountsSumElement);
            }
        },

        /**
         * @param {Object} widget
         */
        attachDialogListeners: function(widget) {
            const self = this;
            if ('add-order-discount-dialog' === widget.getAlias()) {
                widget.on('contentLoad', function() {
                    widget.$el.on('submit', self.onAddSubmit.bind(self, widget));
                });
            } else if ('edit-order-discount-dialog' === widget.getAlias()) {
                widget.on('contentLoad', function() {
                    self._populateDialogForm(this);
                    widget.$el.on('submit', self.onEditSubmit.bind(self, widget));
                });
            }
        },

        /**
         * Handler of "Add" dialog "submit" event.
         *
         * @param {Object} widget
         * @param {Event} event
         */
        onAddSubmit: function(widget, event) {
            this._createInputsFromSubmission(widget.form);
            this._showLoading();
            widget.remove();
            mediator.trigger('entry-point:order:trigger');
            event.preventDefault();
        },

        /**
         * Handler of "Edit" dialog "submit" event.
         *
         * @param {Object} widget
         * @param {Event} event
         */
        onEditSubmit: function(widget, event) {
            this._updateInputsFromSubmission(widget);
            this._showLoading();
            widget.remove();
            mediator.trigger('entry-point:order:trigger');
            event.preventDefault();
        },

        /**
         * Handler of click on "delete" action
         *
         * @param {Event} event
         */
        onDeleteClick: function(event) {
            const collectionElementIndex = $(event.target).data('element-index');
            this._getSelectedHiddenInputs(collectionElementIndex).remove();
            this._showLoading();
            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * Set values from submission to the corresponding fields in $newInputs.
         *
         * @param {HTMLElement} form
         * @param {jQuery} $newInputs
         * @private
         */
        _populateCollectionInputsWithSubmission: function(form, $newInputs) {
            _.each(this.options.selectors.formFields, (fieldSelector, fieldType) => {
                if ('value' !== fieldType) {
                    const submissionInputVal = $(fieldSelector, form).val();
                    $newInputs.filter(fieldSelector).attr('value', submissionInputVal);
                }
            }, form);
        },

        /**
         * Create inputs in hidden collection based on the submission from dialog form.
         *
         * @param {HTMLElement} form
         * @private
         */
        _createInputsFromSubmission: function(form) {
            const $newInputs = this._createNewHiddenCollectionInputs();
            this._populateCollectionInputsWithSubmission(form, $newInputs);
            this.$(this.options.selectors.hiddenCollection).append($newInputs);
        },

        /**
         * Update inputs in hidden collection based on the submission from dialog form.
         *
         * @param {Object} widget
         * @private
         */
        _updateInputsFromSubmission: function(widget) {
            const $hiddenInputs = this._getSelectedHiddenInputs(widget.options.dialogOptions.collectionElementIndex);
            this._populateCollectionInputsWithSubmission(widget.form, $hiddenInputs);
        },

        /**
         * Based on the form's prototype and last index create inputs for new row.
         *
         * @returns {jQuery}
         * @private
         */
        _createNewHiddenCollectionInputs: function() {
            const inputsPrototypeString = this.$(this.options.selectors.hiddenCollection).data('prototype');
            const prototypeName = this.$(this.options.selectors.hiddenCollection).data('prototype-name');
            const lastIndex = this.$(this.options.selectors.hiddenCollection).data('last-index');
            const newInputsHtml = inputsPrototypeString.replace(tools.safeRegExp(prototypeName, 'ig'), lastIndex);

            return $(newInputsHtml);
        },

        /**
         * Based on the selected element's info populate dialog form's inputs.
         *
         * @param {Object} widget
         * @private
         */
        _populateDialogForm: function(widget) {
            const $hiddenInputs = this._getSelectedHiddenInputs(widget.options.dialogOptions.collectionElementIndex);
            this._setDialogFormInputs($hiddenInputs, widget);
            this._triggerFormValueWidgetRefresh(widget);
        },

        /**
         * Based on the passed element index get related row's inputs.
         *
         * @param {int} collectionElementIndex
         * @returns {jQuery}
         * @private
         */
        _getSelectedHiddenInputs: function(collectionElementIndex) {
            const inputsSelector = this.options.selectors.hiddenInputsForIndex.replace(
                tools.safeRegExp('INDEX', 'ig'),
                collectionElementIndex
            );

            return this.$el.find(inputsSelector);
        },

        /**
         * Set dialog form inputs based on the received $hiddenInputs, for editing.
         *
         * @param {jQuery} $hiddenInputs
         * @param {Object} widget
         * @private
         */
        _setDialogFormInputs: function($hiddenInputs, widget) {
            _.each(this.options.selectors.formFields, (fieldSelector, fieldType) => {
                let hiddenInputVal;
                if ('value' === fieldType) {
                    const selectedType = widget.$el
                        .find(this.options.selectors.formFields.type).val();
                    if (this.options.percentType === selectedType) {
                        hiddenInputVal = $($hiddenInputs)
                            .filter(this.options.selectors.formFields.percent).val();
                    } else {
                        hiddenInputVal = $($hiddenInputs)
                            .filter(this.options.selectors.formFields.amount).val();
                    }
                    widget.$el.find(fieldSelector).val(hiddenInputVal);
                } else {
                    hiddenInputVal = $($hiddenInputs).filter(fieldSelector).val();
                    widget.$el.find(fieldSelector).val(hiddenInputVal);
                }
            }, widget);
        },

        /**
         * Trigger refresh of form inputs widget, that listen to "change".
         *
         * @param {Object} widget
         * @private
         */
        _triggerFormValueWidgetRefresh: function(widget) {
            widget.$el.find(this.options.selectors.formFields.type).trigger('change');
        },

        /**
         * @private
         */
        _showLoading: function() {
            this._ensureLoadingMaskLoaded();

            if (!this.subview('loadingMask').isShown()) {
                this.subview('loadingMask').show();
            }
        },

        /**
         * @private
         */
        _hideLoading: function() {
            this._ensureLoadingMaskLoaded();

            if (this.subview('loadingMask').isShown()) {
                this.subview('loadingMask').hide();
            }
        },

        /**
         * Add subview with loadingMask if not already.
         *
         * @private
         */
        _ensureLoadingMaskLoaded: function() {
            if (!this.subview('loadingMask')) {
                this.subview('loadingMask', new LoadingMask({
                    container: this.$el.parents(this.options.selectors.parentForLoadingMask)
                }));
            }
        }
    });

    return DiscountCollectionView;
});
