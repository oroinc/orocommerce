define(function(require) {
    'use strict';

    var DiscountCollectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var tools = require('oroui/js/tools');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');
    var NumberFormatter = require('orolocale/js/formatter/number');

    DiscountCollectionView = BaseView.extend({
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

        /**
         * @inheritDoc
         */
        constructor: function DiscountCollectionView(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            DiscountCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        events: function() {
            var events = {};
            events['click ' + this.options.selectors.deleteButton] = 'onDeleteClick';

            return events;
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            var handlers = {};
            handlers['totals:update'] = this.updateSumAndValidators;
            handlers.widget_initialize = this.attachDialogListeners;
            handlers['entry-point:order:load'] = this.refreshCollectionBlock;

            this.listenTo(mediator, handlers);
        },

        /**
         * @param {Object} response
         */
        refreshCollectionBlock: function(response) {
            if ('discounts' in response) {
                var collectionBlockHtml = $(response.discounts).html();
                this.$el.html(collectionBlockHtml);
                this.$el.trigger('content:changed');
            }
            this._hideLoading();
        },

        /**
         * @param {Object} subtotals
         */
        updateSumAndValidators: function(subtotals) {
            var $discountsSumElement = this.$el.closest('form').find(this.options.selectors.discountsSumSelector);
            var dataValidation = $discountsSumElement.data('validation');
            var discountsSum = 0;
            var total = 0;

            var self = this;
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

            var validator = $($discountsSumElement.closest('form')).validate();
            if (validator) {
                validator.element($discountsSumElement);
            }
        },

        /**
         * @param {Object} widget
         */
        attachDialogListeners: function(widget) {
            var self = this;
            if ('add-order-discount-dialog' === widget.getAlias()) {
                widget.on('contentLoad', function() {
                    widget.$el.on('submit', _.bind(self.onAddSubmit, self, widget));
                });
            } else if ('edit-order-discount-dialog' === widget.getAlias()) {
                widget.on('contentLoad', function() {
                    self._populateDialogForm(this);
                    widget.$el.on('submit', _.bind(self.onEditSubmit, self, widget));
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
            var collectionElementIndex = $(event.target).data('element-index');
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
            _.each(this.options.selectors.formFields, _.bind(function(fieldSelector, fieldType) {
                if ('value' !== fieldType) {
                    var submissionInputVal = $(fieldSelector, form).val();
                    $newInputs.filter(fieldSelector).attr('value', submissionInputVal);
                }
            }, this), form);
        },

        /**
         * Create inputs in hidden collection based on the submission from dialog form.
         *
         * @param {HTMLElement} form
         * @private
         */
        _createInputsFromSubmission: function(form) {
            var $newInputs = this._createNewHiddenCollectionInputs();
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
            var $hiddenInputs = this._getSelectedHiddenInputs(widget.options.dialogOptions.collectionElementIndex);
            this._populateCollectionInputsWithSubmission(widget.form, $hiddenInputs);
        },

        /**
         * Based on the form's prototype and last index create inputs for new row.
         *
         * @returns {jQuery}
         * @private
         */
        _createNewHiddenCollectionInputs: function() {
            var inputsPrototypeString = this.$(this.options.selectors.hiddenCollection).data('prototype');
            var prototypeName = this.$(this.options.selectors.hiddenCollection).data('prototype-name');
            var lastIndex = this.$(this.options.selectors.hiddenCollection).data('last-index');
            var newInputsHtml = inputsPrototypeString.replace(tools.safeRegExp(prototypeName, 'ig'), lastIndex);

            return $(newInputsHtml);
        },

        /**
         * Based on the selected element's info populate dialog form's inputs.
         *
         * @param {Object} widget
         * @private
         */
        _populateDialogForm: function(widget) {
            var $hiddenInputs = this._getSelectedHiddenInputs(widget.options.dialogOptions.collectionElementIndex);
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
            var inputsSelector = this.options.selectors.hiddenInputsForIndex.replace(
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
            _.each(this.options.selectors.formFields, _.bind(function(fieldSelector, fieldType) {
                var hiddenInputVal;
                if ('value' === fieldType) {
                    var selectedType = widget.$el
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
            }, this), widget);
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
