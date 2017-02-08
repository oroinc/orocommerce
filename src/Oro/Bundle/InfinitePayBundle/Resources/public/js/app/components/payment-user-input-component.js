/** @lends PaymentUserInputComponent */
define(function(require) {
    'use strict';

    var PaymentUserInputComponent;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

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
            paymentMethod: 'infinite_pay',
            selectors: {
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
            this.$el = $(options._sourceElement);
            this.$userInput = this.getUserInputElement();
            mediator.on('checkout:payment:before-form-serialization', this.beforeTransit, this);
            mediator.on('checkout:payment:before-restore-filled-form', this.updateDebtorDataFormIdentifier, this);
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
            if (eventData.data.paymentMethod === this.options.paymentMethod) {

                var email = this.getEmailElement().val();
                var legalForm = this.getLegalFormElement().val();

                this.getUserInputStorage('email').val(email);
                this.getUserInputStorage('legalForm').val(legalForm);
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
        }
    });

    return PaymentUserInputComponent;
});

