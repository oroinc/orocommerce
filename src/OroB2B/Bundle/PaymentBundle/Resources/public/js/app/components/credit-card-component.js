define(function(require) {
    'use strict';

    var CreditCardComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    require('jquery.validate');

    CreditCardComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                month: '.checkout__form__select_exp-month',
                year: '.checkout__form__select_exp-year',
                hiddenDate: 'input[name="EXPDATE"]',
                form: '.checkout__form__payment-methods__form',
                expirationDate: '#credit-card-expiration-date',
                cvv: '.credit-card-cvv',
                cardNumber: '.credit-card-number'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property string
         */
        month: null,

        /**
         * @property string
         */
        year: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;

            this.$el.find(this.options.selectors.month).on('change', _.bind(this.collectMonthDate, this));
            this.$el.find(this.options.selectors.year).on('change', _.bind(this.collectYearDate, this));

            $.validator.loadMethod('orob2bpayment/js/validator/creditCardNumberLuhnCheck');
            $.validator.loadMethod('orob2bpayment/js/validator/creditCardExpirationDate');
            $.validator.loadMethod('orob2bpayment/js/validator/creditCardExpirationDateNotBlank');

            this.$form = this.$el.find(this.options.selectors.form);

            this.$el.find(this.options.selectors.cardNumber).on('focusout', _.bind(this.validateElement, this, this.options.selectors.cardNumber));
            this.$el.find(this.options.selectors.cvv).on('focusout', _.bind(this.validateElement, this, this.options.selectors.cvv));
        },

        collectMonthDate: function(e) {
            this.month = e.target.value;

            this.setExpirationDate();
            this.validateElement(this.options.selectors.expirationDate);
        },

        collectYearDate: function(e) {
            this.year = e.target.value;
            this.setExpirationDate();
            this.validateElement(this.options.selectors.expirationDate);
        },

        setExpirationDate: function() {
            var hiddenExpirationDate = this.$el.find(this.options.selectors.hiddenDate);
            if (this.month && this.year) {
                hiddenExpirationDate.val(this.month + this.year);
            } else {
                hiddenExpirationDate.val('');
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.find(this.options.selectors.month).off('change', _.bind(this.collectMonthDate, this));
            this.$el.find(this.options.selectors.year).off('change', _.bind(this.collectYearDate, this));

            CreditCardComponent.__super__.dispose.call(this);
        },
        
        validateElement: function (selector) {
            var virtualForm = $('<form>');
            var validator = virtualForm.append(this.$form.clone()).validate({ignore: ''});

            var staticRules = $.validator.staticRules;
            $.validator.staticRules = function() { return {}; };
            validator.element(virtualForm.find(selector));
            $.validator.staticRules = staticRules;

            this.clearError(validator);
            this.showError(validator)
        },

        showError: function(validator) {
            var self = this;
            $.each(validator.errorList, function (key, errorData) {
                errorData.element = self.$form.find('#' + $(errorData.element).attr('id'));
            });
            validator.showErrors();
        },

        clearError: function (validator) {
            this.$form.find(validator.settings.errorElement + '.' + validator.settings.errorClass).remove();
            this.$form.find('.error').removeClass('error');
        }
    });

    return CreditCardComponent;
});
