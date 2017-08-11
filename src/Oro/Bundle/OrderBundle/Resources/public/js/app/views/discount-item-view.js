define(function(require) {
    'use strict';

    var DiscountItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

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
            percentTypeValue: null,
            totalType: null,
            totals: '[data-totals-container]'
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

            this._initValueValidation();

            this.$el.on('change', this.options.valueInput, _.bind(this.onValueInputChange, this));
            this.$el.on('change', this.options.typeInput, _.bind(this.onValueInputChange, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onValueInputChange: function(e) {
            var value = this.$valueInputElement.val();

            if (this.$typeInputElement.val() === this.options.percentTypeValue) {
                this.$percentInputElement.val(value);
            } else {
                this.$amountInputElement.val(value);
            }

            this._initValueValidation();

            var validator = this.$valueInputElement.closest('form').validate();
            validator.element(this.$valueInputElement);

            if (this.$valueInputElement.closest('form').valid()) {
                this._updateAmountsAndValidators(parseFloat(value));
            }
        },

        /**
         * @private
         */
        _initValueValidation: function() {
            if (this.$typeInputElement.val() === this.options.percentTypeValue) {
                this.$valueInputElement.data('validation', this.$percentInputElement.data('validation'));
            } else {
                this.$valueInputElement.data('validation', this.$amountInputElement.data('validation'));
            }
        },

        /**
         * @private
         * @param {Number} value
         */
        _updateAmountsAndValidators: function(value) {
            if (!value) {
                return;
            }

            var totalsData = {};
            mediator.trigger('order:totals:get:current', totalsData);

            var totals = totalsData.result;
            var total = 0;
            var amount = 0;
            var percent = 0;

            var self = this;
            _.each(totals.subtotals, function(subtotal) {
                if (subtotal.type === self.options.totalType) {
                    total = parseFloat(subtotal.amount);
                }
            });

            if (this.$typeInputElement.val() === this.options.percentTypeValue) {
                amount = (total * value / 100).toFixed(2);
                percent = value;
            } else {
                amount = value;
                percent = total > 0 ? (value / total * 100).toFixed(2) : 0;
            }
            var formatedDiscountAmount = NumberFormatter.formatCurrency(amount, this.options.currency);
            this.$el.find(this.options.valueCalculatedSelector).html(formatedDiscountAmount + ' (' + percent + '%)');

            this.$amountInputElement.val(amount);
            this.$percentInputElement.val(percent);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            DiscountItemView.__super__.dispose.call(this);
        }
    });

    return DiscountItemView;
});
