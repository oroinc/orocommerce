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
            selectors: {
                month: '[data-expiration-date-month]',
                year: '[data-expiration-date-year]',
                hiddenDate: 'input[name="EXPDATE"]',
                form: '[data-credit-card-form]',
                expirationDate: '[data-expiration-date]',
                cvv: '[data-card-cvv]',
                cardNumber: '[data-card-number]',
                validation: '[data-validation]',
                paymentValidateRequired: '[name$="[payment_validate]"]',
                saveForLater: '[data-save-for-later]'
            },
            currentValidation: false
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            $.validator.loadMethod('orob2bpayment/js/validator/creditCardNumberLuhnCheck');
            $.validator.loadMethod('orob2bpayment/js/validator/creditCardExpirationDate');
            $.validator.loadMethod('orob2bpayment/js/validator/creditCardExpirationDateNotBlank');

            this.$el = this.options._sourceElement;

            this.$form = this.$el.find(this.options.selectors.form);

            this.$el
                .on('change', this.options.selectors.month, _.bind(this.collectMonthDate, this))
                .on('change', this.options.selectors.year, _.bind(this.collectYearDate, this))
                .on(
                    'focusout',
                    this.options.selectors.cardNumber,
                    _.bind(this.validate, this, this.options.selectors.cardNumber)
                )
                .on('focusout', this.options.selectors.cvv, _.bind(this.validate, this, this.options.selectors.cvv))
                .on('change', this.options.selectors.saveForLater, _.bind(this.onSaveForLaterChanged, this));

            mediator.on('checkout:place-order:response', _.bind(this.handleSubmit, this));
            mediator.on('checkout:payment:method:changed', _.bind(this.onPaymentMethodChanged, this));
            mediator.on('checkout:payment:before-transit', _.bind(this.beforeTransit, this));
        },

        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;
                var data = this.$el.find('[data-gateway]').serializeArray();
                var resolvedEventData = _.extend(
                    {
                        'SECURETOKEN': false,
                        'SECURETOKENID': false,
                        'errorUrl': false,
                        'returnUrl': false,
                        'formAction': false
                    },
                    eventData.responseData
                );

                data.push({name: 'SECURETOKENID', value: resolvedEventData.SECURETOKENID});
                data.push({name: 'SECURETOKEN', value: resolvedEventData.SECURETOKEN});
                data.push({name: 'RETURNURL', value: resolvedEventData.returnUrl});
                data.push({name: 'ERRORURL', value: resolvedEventData.errorUrl});

                if (!resolvedEventData.formAction || !resolvedEventData.SECURETOKEN) {
                    return this.postUrl(resolvedEventData.errorUrl, data);
                }

                return this.postUrl(resolvedEventData.formAction, data);
            }
        },

        postUrl: function(formAction, data) {
            var $form = $('<form action="' + formAction + '" method="POST">');
            _.each(data, function(field) {
                var $field = $('<input>')
                    .prop('type', 'hidden')
                    .prop('name', field.name)
                    .val(field.value);

                $form.append($field);
            });

            $form.submit();
        },

        collectMonthDate: function(e) {
            this.month = e.target.value;

            this.setExpirationDate();
            this.validate(this.options.selectors.expirationDate);
        },

        collectYearDate: function(e) {
            this.year = e.target.value;
            this.setExpirationDate();
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
            if (this.disposed) {
                return;
            }

            this.$el
                .off('change', this.options.selectors.month, _.bind(this.collectMonthDate, this))
                .off('change', this.options.selectors.year, _.bind(this.collectYearDate, this))
                .off(
                    'focusout',
                    this.options.selectors.cardNumber,
                    _.bind(this.validate, this, this.options.selectors.cardNumber)
                )
                .off('focusout', this.options.selectors.cvv, _.bind(this.validate, this, this.options.selectors.cvv))
                .off('change', this.options.selectors.saveForLater, _.bind(this.onSaveForLaterChanged, this));

            mediator.off('checkout:place-order:response', _.bind(this.handleSubmit, this));
            mediator.off('checkout:payment:method:changed', _.bind(this.onPaymentMethodChanged, this));
            mediator.off('checkout:payment:before-transit', _.bind(this.beforeTransit, this));

            CreditCardComponent.__super__.dispose.call(this);
        },

        validate: function(elementSelector) {
            var virtualForm = $('<form>');
            var clonedForm = this.$form.clone();

            var self = this;

            clonedForm.find('select').each(function(index, item) {
                //set new select to value of old select
                //http://stackoverflow.com/questions/742810/clone-isnt-cloning-select-values
                $(item).val(self.$form.find('select').eq(index).val());
            });

            var validator = virtualForm
                .append(clonedForm)
                .validate({
                    ignore: '',
                    errorPlacement: function(error, element) {
                        var $el = self.$form.find('#' + $(element).attr('id'));
                        var parentWithValidation = $el.parents(self.options.selectors.validation);

                        if (parentWithValidation.length) {
                            error.appendTo(parentWithValidation.first());
                        } else {
                            error.appendTo($el.parent());
                        }
                    }
                });

            var errors;

            if (elementSelector) {
                errors = this.$form.find(elementSelector).parent();
            } else {
                errors = this.$form;
            }

            errors.find(validator.settings.errorElement + '.' + validator.settings.errorClass).remove();
            errors.parent().find('.error').removeClass('error');

            var staticRules = $.validator.staticRules;
            $.validator.staticRules = function() { return {}; };

            var isValid;
            if (elementSelector) {
                isValid = validator.element(virtualForm.find(elementSelector));
            } else {
                isValid = validator.form();
            }

            $.validator.staticRules = staticRules;

            return isValid;
        },

        /**
         * @returns {jQuery}
         */
        getPaymentValidateElement: function() {
            if (!this.hasOwnProperty('$paymentValidateElement')) {
                this.$paymentValidateElement = $(this.options.selectors.paymentValidateRequired);
            }

            return this.$paymentValidateElement;
        },

        /**
         * @param {Boolean} state
         */
        setPaymentValidateRequired: function(state) {
            this.paymentValidationRequiredComponentState = state;
            this.getPaymentValidateElement()
                .prop('checked', state)
                .trigger('change');
        },

        /**
         * @returns {Boolean}
         */
        getPaymentValidateRequired: function() {
            return this.getPaymentValidateElement().prop('checked');
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
            this.setPaymentValidateRequired(this.paymentValidationRequiredComponentState);
            this.setSaveForLaterBasedOnForm();
        },

        /**
         * @param {Object} e
         */
        onSaveForLaterChanged: function(e) {
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
        }
    });

    return CreditCardComponent;
});
