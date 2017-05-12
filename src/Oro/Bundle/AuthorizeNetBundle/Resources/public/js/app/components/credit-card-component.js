define(function(require) {
    'use strict';

    var CreditCardComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
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
                form: '[data-credit-card-form]',
                expirationDate: '[data-expiration-date]',
                month: '[data-expiration-date-month]',
                year: '[data-expiration-date-year]',
                cvv: '[data-card-cvv]',
                cardNumber: '[data-card-number]',
                validation: '[data-validation]'
            },
            messages: {
                communication_err: 'oro.authorize_net.errors.accept_js.communication_err'
            },
            clientKey: null,
            apiLoginID: null,
            testMode: null,
            acceptJsUrls: {
                test: 'https://jstest.authorize.net/v1/Accept.js',
                prod: 'https://js.authorize.net/v1/Accept.js'
            }
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {(Accept|null)}
         */
        acceptJs: null,

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
                .on(
                    'change',
                    this.options.selectors.expirationDate,
                    $.proxy(this.validate, this, this.options.selectors.expirationDate)
                )
                .on(
                    'focusout',
                    this.options.selectors.cardNumber,
                    $.proxy(this.validate, this, this.options.selectors.cardNumber)
                )
                .on('focusout', this.options.selectors.cvv, $.proxy(this.validate, this, this.options.selectors.cvv));

            mediator.on('checkout:payment:method:changed', this.onPaymentMethodChanged, this);
            mediator.on('checkout:payment:before-transit', this.beforeTransit, this);
            mediator.on('checkout-content:initialized', this.refreshPaymentMethod, this);
            mediator.on('checkout:place-order:response', this.placeOrderResponse, this);
        },

        refreshPaymentMethod: function() {
            mediator.trigger('checkout:payment:method:refresh');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            mediator.off('checkout-content:initialized', this.refreshPaymentMethod, this);
            mediator.off('checkout:payment:method:changed', this.onPaymentMethodChanged, this);
            mediator.off('checkout:payment:before-transit', this.beforeTransit, this);
            mediator.off('checkout:place-order:response', this.placeOrderResponse, this);

            CreditCardComponent.__super__.dispose.call(this);
        },

        /**
         * @param {String} elementSelector
         */
        validate: function(elementSelector) {
            var virtualForm = $('<form>');

            var appendElement;
            if (elementSelector) {
                appendElement = this.$form.find(elementSelector).clone();
            } else {
                appendElement = this.$form.clone();
            }

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
                //set new select to value of old select
                //http://stackoverflow.com/questions/742810/clone-isnt-cloning-select-values
                $(item).val(self.$form.find('select').eq(index).val());
            });

            // Add validator to form
            $.data(virtualForm, 'validator', validator);

            // Add CC type validation rule
            var cardNumberField = virtualForm.find(this.options.selectors.cardNumber);
            var cardNumberValidation = cardNumberField.data('validation');
            var creditCardTypeValidator = cardNumberField.data('credit-card-type-validator');

            if (creditCardTypeValidator && creditCardTypeValidator in cardNumberValidation) {
                _.extend(cardNumberValidation[creditCardTypeValidator],
                    {allowedCreditCards: this.options.allowedCreditCards}
                );
            }

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
         * @param {Object} eventData
         */
        beforeTransit: function(eventData) {
            if (eventData.data.paymentMethod !== this.options.paymentMethod || eventData.stopped) {
                return;
            }

            var self = this;
            eventData.stopped = true;
            if (this.validate()) {
                mediator.execute('showLoading');
                var cardData = {
                    cardNumber: this.$form.find(this.options.selectors.cardNumber).val(),
                    month: this.$form.find(this.options.selectors.month).val(),
                    year: this.$form.find(this.options.selectors.year).val()
                };
                var $cvv = this.$form.find(this.options.selectors.cvv);
                if ($cvv.length) {
                    cardData.cardCode = $cvv.val();
                }

                this.acceptJs.dispatchData({
                        authData: {
                            clientKey: this.options.clientKey,
                            apiLoginID: this.options.apiLoginID
                        },
                        cardData: cardData
                    }, function(response) {
                        mediator.execute('hideLoading');
                        self.acceptJsResponse.call(self, response, eventData);
                    }
                );
            }
        },

        /**
         * @param {Object} eventData
         */
        onPaymentMethodChanged: function(eventData) {
            if (eventData.paymentMethod === this.options.paymentMethod) {
                this.loadAcceptJsLibrary();
            }
        },

        loadAcceptJsLibrary: function() {
            var acceptJsUrl = this.options.testMode ? this.options.acceptJsUrls.test : this.options.acceptJsUrls.prod;
            var self = this;
            require([acceptJsUrl], function() {
                self.acceptJs = Accept; // jshint ignore:line
            });
        },

        /**
         * @param {Object} response
         * @param {Object} eventData
         */
        acceptJsResponse: function(response, eventData) {
            if (response.messages.resultCode !== 'Ok' || !response.opaqueData ||
                !response.opaqueData.dataDescriptor || !response.opaqueData.dataValue
            ) {
                mediator.execute('showFlashMessage', 'error', __(this.options.messages.communication_err));
                this.logError(response);
            } else {
                var additionalData = {
                    dataDescriptor: response.opaqueData.dataDescriptor,
                    dataValue: response.opaqueData.dataValue
                };

                mediator.trigger('checkout:payment:additional-data:set', JSON.stringify(additionalData));
                mediator.trigger('checkout:payment:validate:change', true);
                eventData.resume();
            }
        },

        /**
         * @param {Object} eventData
         */
        placeOrderResponse: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;
                mediator.execute('redirectTo', {url: eventData.responseData.successUrl}, {redirect: true});
            }
        },

        /**
         * @param {(string|Object)} message
         */
        logError: function(message) {
            if (typeof window.console === 'undefined') {
                // can not log error because console doesn't exist
                return;
            }

            window.console.error(message);
        }
    });

    return CreditCardComponent;
});
