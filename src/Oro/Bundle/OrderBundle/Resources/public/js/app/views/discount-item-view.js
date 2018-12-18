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
        discountItemHint: require('tpl!./../templates/discount-item-hint.html'),

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
            discountType: null
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
        constructor: function DiscountItemView() {
            DiscountItemView.__super__.constructor.apply(this, arguments);
        },

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
            this._updateValidatorRules();

            var validator = this.$valueInputElement.closest('form').validate();
            validator.element(this.$valueInputElement);

            if (!validator.numberOfInvalids()) {
                this._updateAmounts(NumberFormatter.unformatStrict(value));
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
         */
        _updateValidatorRules: function() {
            var rules = this.$valueInputElement.rules();

            var rangeRules = _.result(rules, 'Range', []);
            var total = this._getTotal();
            for (var index = 0; index < rangeRules.length; ++index) {
                var rangeRule = rangeRules[index];
                rangeRule.max = total;
            }
        },

        /**
         * @private
         * @returns {Float}
         */
        _getTotal: function() {
            var totalsData = {};
            mediator.trigger('order:totals:get:current', totalsData);

            var totals = totalsData.result;
            var total = 0;

            var self = this;
            _.each(totals.subtotals, function(subtotal) {
                if (subtotal.type === self.options.totalType) {
                    total = parseFloat(subtotal.amount);
                }
            });

            return total;
        },

        /**
         * @private
         * @param {Number} value
         */
        _updateAmounts: function(value) {
            if (!value) {
                return;
            }

            var amount = 0;
            var percent = 0;
            var total = this._getTotal();

            if (this.$typeInputElement.val() === this.options.percentTypeValue) {
                amount = (total * value / 100).toFixed(2);
                percent = value;
            } else {
                amount = value;
                percent = total > 0 ? (value / total * 100).toFixed(2) : 0;
            }
            this.$el.find(this.options.valueCalculatedSelector).html(this.discountItemHint({
                NumberFormatter: NumberFormatter,
                amount: amount,
                currency: this.options.currency,
                percent: percent
            }));

            this.$amountInputElement.val(NumberFormatter.formatDecimal(amount, {grouping_used: false}));
            this.$percentInputElement.val(NumberFormatter.formatDecimal(percent, {grouping_used: false}));
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
