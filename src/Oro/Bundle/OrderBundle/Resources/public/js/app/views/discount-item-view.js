define(function(require) {
    'use strict';

    var DiscountItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var TotalsListener = require('oropricing/js/app/listener/totals-listener');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroorder/js/app/views/discount-item-view
     * @extends oroui.app.views.base.View
     * @class oroorder.app.views.DiscountItemView
     */
    DiscountItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            valueInput: '[data-ftid$=value]',
            typeInput: '[data-ftid$=type]',
            percentInput: '[data-ftid$=percent]',
            amountInput: '[data-ftid$=amount]',
            descriptionInput: '[data-ftid$=description]',
            valueCalculatedSelector: '.discount-item-value-calculated',
            valuePercentSelector: '.discount-item-value-percent',
            percentTypeValue: null,
            totalType: null,
            discountType: null,
        },

        /**
         * @property {jQuery.Element}
         */
        $valueInputElement: null,

        /**
         * @property {jQuery.Element}
         */
        $typeInputElement: null,

        /**
         * @property {jQuery.Element}
         */
        $percentInputElement: null,

        /**
         * @property {jQuery.Element}
         */
        $amountInputElement: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$valueInputElement = this.$el.find(this.options.valueInput).attr('data-validation', '');
            this.$typeInputElement = this.$el.find(this.options.typeInput);
            this.$percentInputElement = this.$el.find(this.options.percentInput);
            this.$amountInputElement = this.$el.find(this.options.amountInput);

            this.initValueValidation();

            this.delegate('click', '.removeDiscountItem', this.removeRow);
            this.$el.on('change', this.options.valueInput, _.bind(this.onValueInputChange, this));
            this.$el.on('change', this.options.typeInput, _.bind(this.onValueInputChange, this));
            this.$el.on('change', this.options.descriptionInput, _.bind(this.updateTotals, this));
            mediator.on('totals:update', this.updateAmountsAndValidators, this);
        },

        /**
         * @param {jQuery.Event} e
         */
        onValueInputChange: function(e) {
            var value = this.$valueInputElement.val();

            if (this.$typeInputElement.val() == this.options.percentTypeValue) {
                this.$percentInputElement.val(value);
            } else {
                this.$amountInputElement.val(value);
            }

            this.initValueValidation();

            var validator = $(this.$valueInputElement.closest('form')).validate();
            validator.element(this.$valueInputElement);
            TotalsListener.updateTotals();
        },

        initValueValidation: function() {
            var validation = null;

            if (this.$typeInputElement.val() == this.options.percentTypeValue) {
                this.$valueInputElement.data('validation', this.$percentInputElement.data('validation'));
            } else {
                this.$valueInputElement.data('validation', this.$amountInputElement.data('validation'));
            }
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
            TotalsListener.updateTotals();
        },

        /**
         * @param {jQuery.Event} e
         */
        updateTotals: function(e) {
            TotalsListener.updateTotals();
        },

        updateAmountsAndValidators: function(subtotals) {
            var valueDataValidation = this.$valueInputElement.data('validation');
            var amountDataValidation = this.$amountInputElement.data('validation');
            var total = 0;
            var index = this.$el.index();
            var currentIndex = -1;
            var discountAmount = null;

            var self = this;
            _.each(subtotals.subtotals, function(subtotal) {
                if (subtotal.type === self.options.totalType) {
                    total = subtotal.amount;
                }

                if (subtotal.type === self.options.discountType) {
                    currentIndex++;
                }

                if (currentIndex === index && discountAmount === null) {
                    discountAmount = subtotal.amount;
                }
            });

            var percent = total > 0 ? (discountAmount / total * 100).toFixed(2) : 0;
            var formatedDiscountAmount = NumberFormatter.formatCurrency(discountAmount, this.options.currency);
            this.$el.find(this.options.valueCalculatedSelector).html(formatedDiscountAmount + ' (' + percent + '%)');

            this.$amountInputElement.val(discountAmount);
            this.$percentInputElement.val(percent);

            if (this.$typeInputElement.val() != this.options.percentTypeValue
                && valueDataValidation && !_.isEmpty(valueDataValidation.Range)
            ) {
                valueDataValidation.Range.max = total;
            }

            if (amountDataValidation && !_.isEmpty(amountDataValidation.Range)) {
                amountDataValidation.Range.max = total;
            }

            var validator = this.$valueInputElement.closest('form').validate();
            if (validator) {
                validator.element(this.$valueInputElement);
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('totals:update', this.updateAmountsAndValidators, this);
            DiscountItemView.__super__.dispose.call(this);
        }
    });

    return DiscountItemView;
});
