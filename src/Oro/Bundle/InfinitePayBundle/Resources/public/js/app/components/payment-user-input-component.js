/** @lends PaymentUserInputComponent */
define(function(require) {
    'use strict';

    var PaymentUserInputComponent;
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');
    require('jquery.validate');

    var BaseComponent = require('oroui/js/app/components/base/component');

    PaymentUserInputComponent = BaseComponent.extend(/** @exports PaymentUserInputComponent.prototype */ {
        /**
         * @property {jQuery}
         */
        $inputForm: null,

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {jQuery}
         */
        $userInput: null,

        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null,
            selectors: {
                form: '.infinitepay-form',
                fieldEmail: '[name$="oro_infinite_pay_debtor_data[email]"]',
                fieldLegalform: '[name$="oro_infinite_pay_debtor_data[legal_form]"]',
                collectionUserInput: '[data-name="field__user-input"]',
                inputField: '[name$="[user_input][__index__]"]'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            this.$el = $(options._sourceElement);

            this.$userInput = this.getUserInputElement();
            mediator.on('checkout:payment:before-form-serialization', this.beforeTransit, this);
            mediator.on('checkout:payment:before-restore-filled-form', this.updateDebtorDataFormIdentifier, this);

            this.getForm()
                .on('focusout', 'input,textarea', $.proxy(this.validate, this))
                .on('change', 'select', $.proxy(this.validate, this));

            mediator.on('checkout:payment:method:changed', this.onPaymentMethodChanged, this);
            mediator.on('checkout:payment:before-transit', this.validateBeforeTransit, this);
        },

        /**
         * @param {Object} filledForm
         */
        updateDebtorDataFormIdentifier: function(filledForm) {
            this.$el = filledForm;
        },

        /**
         * @param {Object} eventData
         */
        beforeTransit: function(eventData) {
            if (eventData.paymentMethod === this.options.paymentMethod) {

                var email = this.getEmailElement().val();
                var legalForm = this.getLegalFormElement().val();

                this.getUserInputStorage('email').val(email);
                this.getUserInputStorage('legalForm').val(legalForm);
            }
        },

        /**
         * @param {Object} eventData
         */
        validateBeforeTransit: function(eventData) {
            if (eventData.data.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = !this.validate();
            }
        },

        /**
         * @param name
         * @returns {jQuery|HTMLElement}
         */
        getUserInputStorage: function(name) {
            var selectorInputField = this.options.selectors.inputField.replace(/__index__/g, name);
            var inputField = this.$userInput.find(selectorInputField);
            if (inputField.length === 0) {
                var namedPrototype = this.getNamedPrototype(name);
                this.$userInput.append(namedPrototype);
                inputField = this.$userInput.find(selectorInputField);
            }

            return inputField;
        },

        /**
         * @returns {string}
         */
        getNamedPrototype: function(name) {
            return this.$userInput.data('prototype').replace(/__name__/g, name);
        },

        getUserInputElement: function() {
            return $(this.options.selectors.collectionUserInput);
        },
        /**
         * @returns {jQuery|HTMLElement}
         */
        getEmailElement: function() {
            return this.$el.find(this.options.selectors.fieldEmail);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getLegalFormElement: function() {
            return this.$el.find(this.options.selectors.fieldLegalform);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            PaymentUserInputComponent.__super__.dispose.call(this);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getForm: function() {
            return $(this.options.selectors.form);
        },

        /**
         * @param {Boolean} state
         */
        setGlobalPaymentValidate: function(state) {
            this.paymentValidationRequiredComponentState = state;
            mediator.trigger('checkout:payment:validate:change', state);
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
        },

        /**
         * @param {Object} [event]
         *
         * @returns {Boolean}
         */
        validate: function(event) {
            var virtualForm = $('<form>');

            var appendElement;
            if (event !== undefined && event.target) {
                appendElement = $(event.target).clone();
            } else {
                appendElement = this.getForm().clone();
            }

            virtualForm.append(appendElement);

            var self = this;
            var validator = virtualForm.validate({
                ignore: '', // required to validate all fields in virtual form
                errorPlacement: function(error, element) {
                    var $el = self.getForm().find('#' + $(element).attr('id'));
                    var parentWithValidation = $el.parents('[data-validation]');

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
                $(item).val(self.getForm().find('select').eq(index).val());
            });

            // Add validator to form
            $.data(virtualForm, 'validator', validator);

            var errors;

            if (event) {
                errors = $(event.target).parent();
            } else {
                errors = this.getForm();
            }

            errors.find(validator.settings.errorElement + '.' + validator.settings.errorClass).remove();
            errors.parent().find('.error').removeClass('error');

            return validator.form();
        }
    });

    return PaymentUserInputComponent;
});

