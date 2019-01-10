define(function(require) {
    'use strict';

    var CreditCardComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    require('jquery.validate');

    CreditCardComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null,
            allowedCreditCards: [],
            selectors: {
                month: '[data-expiration-date-month]',
                year: '[data-expiration-date-year]',
                hiddenDate: 'input[name="EXPDATE"]',
                form: '[data-credit-card-form]',
                expirationDate: '[data-expiration-date]',
                cvv: '[data-card-cvv]',
                cardNumber: '[data-card-number]',
                validation: '[data-validation]',
                saveForLater: '[data-save-for-later]'
            }
        },

        /**
         * @property {Boolean}
         */
        paymentValidationRequiredComponentState: true,

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
         * @property {Boolean}
         */
        disposable: true,

        /**
         * @inheritDoc
         */
        constructor: function CreditCardComponent() {
            CreditCardComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);

            $.validator.loadMethod('oropayment/js/validator/credit-card-number');
            $.validator.loadMethod('oropayment/js/validator/credit-card-type');
            $.validator.loadMethod('oropayment/js/validator/credit-card-expiration-date');
            $.validator.loadMethod('oropayment/js/validator/credit-card-expiration-date-not-blank');

            this.$el = this.options._sourceElement;

            this.$form = this.$el.find(this.options.selectors.form);

            this.$el
                .on('change', this.options.selectors.month, $.proxy(this.collectMonthDate, this))
                .on('change', this.options.selectors.year, $.proxy(this.collectYearDate, this))
                .on(
                    'focusout',
                    this.options.selectors.cardNumber,
                    $.proxy(this.validate, this, this.options.selectors.cardNumber)
                )
                .on('focusout', this.options.selectors.cvv, $.proxy(this.validate, this, this.options.selectors.cvv))
                .on('change', this.options.selectors.saveForLater, $.proxy(this.onSaveForLaterChange, this));

            mediator.on('checkout:place-order:response', this.handleSubmit, this);
            mediator.on('checkout:payment:method:changed', this.onPaymentMethodChanged, this);
            mediator.on('checkout:payment:before-transit', this.beforeTransit, this);
            mediator.on('checkout:payment:before-hide-filled-form', this.beforeHideFilledForm, this);
            mediator.on('checkout:payment:before-restore-filled-form', this.beforeRestoreFilledForm, this);
            mediator.on('checkout:payment:remove-filled-form', this.removeFilledForm, this);
            mediator.on('checkout-content:initialized', this.refreshPaymentMethod, this);
        },

        refreshPaymentMethod: function() {
            mediator.trigger('checkout:payment:method:refresh');
        },

        /**
         * @param {Object} eventData
         */
        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;

                var resolvedEventData = _.extend(
                    {
                        SECURETOKEN: false,
                        SECURETOKENID: false,
                        returnUrl: '',
                        errorUrl: '',
                        formAction: '',
                        paymentMethodSupportsValidation: false
                    },
                    eventData.responseData
                );

                if (resolvedEventData.paymentMethodSupportsValidation) {
                    mediator.execute('redirectTo', {url: resolvedEventData.returnUrl}, {redirect: true});

                    return;
                }

                var data = this.$el.find('[data-gateway]').serializeArray();
                data.push({name: 'SECURETOKENID', value: resolvedEventData.SECURETOKENID});
                data.push({name: 'SECURETOKEN', value: resolvedEventData.SECURETOKEN});

                if (resolvedEventData.formAction && resolvedEventData.SECURETOKEN) {
                    this.postUrl(resolvedEventData.formAction, data);

                    return;
                }

                mediator.execute('redirectTo', {url: resolvedEventData.errorUrl}, {redirect: true});
            }
        },

        /**
         * @param {String} formAction
         * @param {Object} data
         */
        postUrl: function(formAction, data) {
            var $form = $('<form action="' + formAction + '" method="POST" data-nohash="true">');
            _.each(data, function(field) {
                var $field = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', field.name)
                    .val(field.value);

                $form.append($field);
            });

            $('body').append($form);

            $form.submit();
        },

        /**
         * @param {jQuery.Event} e
         */
        collectMonthDate: function(e) {
            this.month = e.target.value;

            this.setExpirationDate();
            this.validateIfMonthAndYearNotBlank();
        },

        /**
         * @param {jQuery.Event} e
         */
        collectYearDate: function(e) {
            this.year = e.target.value;
            this.setExpirationDate();
            this.validateIfMonthAndYearNotBlank();
        },

        validateIfMonthAndYearNotBlank: function() {
            this.validate(this.options.selectors.expirationDate);
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
            if (this.disposed || !this.disposable) {
                return;
            }

            this.$el.off();

            mediator.off('checkout-content:initialized', this.refreshPaymentMethod, this);
            mediator.off('checkout:place-order:response', this.handleSubmit, this);
            mediator.off('checkout:payment:method:changed', this.onPaymentMethodChanged, this);
            mediator.off('checkout:payment:before-transit', this.beforeTransit, this);
            mediator.off('checkout:payment:before-hide-filled-form', this.beforeHideFilledForm, this);
            mediator.off('checkout:payment:before-restore-filled-form', this.beforeRestoreFilledForm, this);
            mediator.off('checkout:payment:remove-filled-form', this.removeFilledForm, this);

            CreditCardComponent.__super__.dispose.call(this);
        },

        /**
         * @param {String} elementSelector
         */
        validate: function(elementSelector) {
            var appendElement;
            if (elementSelector) {
                var element = this.$form.find(elementSelector);
                var parentForm = element.closest('form');

                if (elementSelector !== this.options.selectors.expirationDate && parentForm.length) {
                    return this._validateFormField(this.$el, element);
                }

                appendElement = element.clone();
            } else {
                appendElement = this.$form.clone();
            }

            var virtualForm = $('<form>');
            virtualForm.append(appendElement);

            var self = this;
            var validator = virtualForm.validate({
                ignore: '', // required to validate all fields in virtual form
                errorPlacement: function(error, element) {
                    var $el = self.$form.find('#' + $(element).attr('id'));
                    var parentWithValidation = $el.parents(self.options.selectors.validation);

                    $el.addClass('error');

                    if (parentWithValidation.length) {
                        error.appendTo(parentWithValidation.first());
                    } else {
                        error.appendTo($el.parent());
                    }
                }
            });

            virtualForm.find('select').each(function(index, item) {
                // set new select to value of old select
                // http://stackoverflow.com/questions/742810/clone-isnt-cloning-select-values
                $(item).val(self.$form.find('select').eq(index).val());
            });

            // Add validator to form
            $.data(virtualForm, 'validator', validator);

            this._addCardTypeValidationRule(virtualForm);

            var errors;

            if (elementSelector) {
                errors = this.$form.find(elementSelector).parent();
            } else {
                errors = this.$form;
            }

            errors.find(validator.settings.errorElement + '.' + validator.settings.errorClass).remove();
            errors.parent().find('.error').removeClass('error');

            return validator.form();
        },

        /**
         * @param {jQuery} form
         * @param {jQuery} element
         */
        _validateFormField: function(form, element) {
            this._addCardTypeValidationRule(form);

            return element.validate().form();
        },

        /**
         * @param {jQuery} form
         */
        _addCardTypeValidationRule: function(form) {
            // Add CC type validation rule
            var cardNumberField = form.find(this.options.selectors.cardNumber);
            var cardNumberValidation = cardNumberField.data('validation');
            var creditCardTypeValidator = cardNumberField.data('credit-card-type-validator');

            if (creditCardTypeValidator && creditCardTypeValidator in cardNumberValidation) {
                _.extend(cardNumberValidation[creditCardTypeValidator],
                    {allowedCreditCards: this.options.allowedCreditCards}
                );
            }
        },

        /**
         * @param {Boolean} state
         */
        setGlobalPaymentValidate: function(state) {
            this.paymentValidationRequiredComponentState = state;
            mediator.trigger('checkout:payment:validate:change', state);
        },

        /**
         * @returns {Boolean}
         */
        getGlobalPaymentValidate: function() {
            var validateValueObject = {};
            mediator.trigger('checkout:payment:validate:get-value', validateValueObject);
            return validateValueObject.value;
        },

        /**
         * @returns {jQuery}
         */
        getSaveForLaterElement: function() {
            if (!this.hasOwnProperty('$saveForLaterElement')) {
                this.$saveForLaterElement = this.$form.find(this.options.selectors.saveForLater);
            }

            return this.$saveForLaterElement;
        },

        /**
         * @returns {Boolean}
         */
        getSaveForLaterState: function() {
            return this.getSaveForLaterElement().prop('checked');
        },

        setSaveForLaterBasedOnForm: function() {
            mediator.trigger('checkout:payment:save-for-later:change', this.getSaveForLaterState());
        },

        /**
         * @param {Object} eventData
         */
        onPaymentMethodChanged: function(eventData) {
            if (eventData.paymentMethod === this.options.paymentMethod) {
                this.onCurrentPaymentMethodSelected();
            }
        },

        onCurrentPaymentMethodSelected: function() {
            this.setGlobalPaymentValidate(this.paymentValidationRequiredComponentState);
            this.setSaveForLaterBasedOnForm();
        },

        /**
         * @param {Object} e
         */
        onSaveForLaterChange: function(e) {
            var $el = $(e.target);
            mediator.trigger('checkout:payment:save-for-later:change', $el.prop('checked'));
        },

        /**
         * @param {Object} eventData
         */
        beforeTransit: function(eventData) {
            if (eventData.data.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = !this.validate();
            }
        },

        beforeHideFilledForm: function() {
            this.disposable = false;
        },

        beforeRestoreFilledForm: function() {
            if (this.disposable) {
                this.dispose();
            }
        },

        removeFilledForm: function() {
            // Remove hidden form js component
            if (!this.disposable) {
                this.disposable = true;
                this.dispose();
            }
        }
    });

    return CreditCardComponent;
});
