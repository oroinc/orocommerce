define(function(require) {
    'use strict';

    var DiscountItemView;
    var $ = require('jquery');
    var _ = require('underscore');
    var TotalsListener = require('orob2bpricing/js/app/listener/totals-listener');
    var BaseView = require('oroui/js/app/views/base/view');

    DiscountItemView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            valueInput: '[data-ftid$=value]',
            typeInput: '[data-ftid$=type]',
            percentInput: '[data-ftid$=percent]',
            amountInput: '[data-ftid$=amount]'
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

            this.$valueInputElement = this.$el.find(this.options.valueInput);
            this.$typeInputElement = this.$el.find(this.options.typeInput);
            this.$percentInputElement = this.$el.find(this.options.percentInput);
            this.$amountInputElement = this.$el.find(this.options.amountInput);

            this.delegate('click', '.removeDiscountItem', this.removeRow);
            this.$el.on('change', this.options.valueInput, _.bind(this.onValueInputChange, this));
            this.$el.on('change', this.options.typeInput, _.bind(this.onValueInputChange, this));

            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        /**
         * @param {jQuery|Array} $fields
         */
        subtotalFields: function($fields) {
            TotalsListener.listen($fields);
        },

        /**
         * @inheritDoc
         */
        handleLayoutInit: function() {
            this.$form = this.$el.closest('form');
            this.$fields = this.$el.find(':input[name]');

            this.fieldsByName = {};
            this.$fields.each(_.bind(function(i, field) {
                this.fieldsByName[this.formFieldName(field)] = $(field);
            }, this));

            this.fieldsByName.currency = this.$form
                .find(':input[data-ftid="' + this.$form.attr('name') + '_currency"]');

            this.subtotalFields([
                this.fieldsByName.amount,
                this.fieldsByName.currency,
                this.fieldsByName.description,
                this.fieldsByName.percent,
                this.fieldsByName.type,
                this.fieldsByName.value
            ]);
        },

        /**
         * @param {Object} field
         * @returns {String}
         */
        formFieldName: function(field) {
            var name = '';
            var nameParts = field.name.replace(/.*\[[0-9]+\]/, '').replace(/[\[\]]/g, '_').split('_');
            var namePart;

            for (var i = 0, iMax = nameParts.length; i < iMax; i++) {
                namePart = nameParts[i];
                if (!namePart.length) {
                    continue;
                }
                if (name.length === 0) {
                    name += namePart;
                } else {
                    name += namePart[0].toUpperCase() + namePart.substr(1);
                }
            }
            return name;
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
        },

        removeRow: function() {
            this.$el.trigger('content:remove');
            this.remove();
            TotalsListener.updateTotals();
        }
    });

    return DiscountItemView;
});
