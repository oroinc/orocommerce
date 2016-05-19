/** @lends PaymentTransitionButtonComponent */
define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orob2bcheckout/js/app/components/transition-button-component');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    var PaymentTransitionButtonComponent;
    PaymentTransitionButtonComponent = TransitionButtonComponent.extend(/** @exports PaymentTransitionButtonComponent.prototype */{
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.defaults.selectors.paymentForm = '[data-content="payment_method_form"]';
            this.defaults.selectors.paymentMethodSelector = '[name="paymentMethod"]';
            this.defaults.selectors.paymentMethod = '[name$="[payment_method]"]';

            PaymentTransitionButtonComponent.__super__.initialize.call(this, options);

            this.initPaymentMethod();
            this.getPaymentMethodSelector().on('change', $.proxy(this.onPaymentMethodChange, this));
        },

        initPaymentMethod: function() {
            var filledForm = this.getContent().next(this.options.selectors.paymentForm);
            var selectedValue = this.getPaymentMethodElement().val();
            if (filledForm.length > 0) {
                if (selectedValue) {
                    mediator.trigger('checkout:payment:before-restore-filled-form', filledForm);
                    filledForm.removeClass('hidden');
                    this.getPaymentForm().replaceWith(filledForm);
                    delete this.$paymentForm;
                } else {
                    filledForm.remove();
                }
            } else {
                if (selectedValue) {
                    var selectedEl = this.getPaymentMethodSelector().filter('[value="' + selectedValue + '"]');
                    selectedEl.prop('checked', 'checked');
                    selectedEl.trigger('change');
                } else {
                    this.getPaymentMethodElement().val(this.getPaymentMethodSelector().filter(':checked').val());
                }
            }
        },

        /**
         * @inheritDoc
         */
        transit: function(e, data) {
            var paymentMethod = this.getPaymentMethodElement().val();
            var eventData = {stopped: false, data: {paymentMethod: paymentMethod}};

            mediator.trigger('checkout:payment:before-transit', eventData);
            if (eventData.stopped) {
                e.preventDefault();
                return;
            }

            var filledForm = this.getPaymentForm();
            mediator.trigger('checkout:payment:before-hide-filled-form', filledForm);
            filledForm
                .addClass('hidden')
                .insertAfter(this.getContent());

            PaymentTransitionButtonComponent.__super__.transit.call(this, e, data);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getPaymentMethodSelector().off('change', $.proxy(this.onPaymentMethodChange, this));

            PaymentTransitionButtonComponent.__super__.dispose.call(this);
        },

        /**
         * @param {Event} event
         */
        onPaymentMethodChange: function(event) {
            var target = $(event.target);
            this.getPaymentMethodElement().val(target.val());
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getContent: function() {
            if (!this.hasOwnProperty('$content')) {
                this.$content = $(this.options.selectors.checkoutContent);
            }

            return this.$content;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentForm: function() {
            if (!this.hasOwnProperty('$paymentForm')) {
                this.$paymentForm = this.getContent().find(this.options.selectors.paymentForm);
            }

            return this.$paymentForm;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentMethodSelector: function() {
            if (!this.hasOwnProperty('$paymentMethodSelector')) {
                this.$paymentMethodSelector = this.getPaymentForm().find(this.options.selectors.paymentMethodSelector);
            }

            return this.$paymentMethodSelector;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentMethodElement: function() {
            if (!this.hasOwnProperty('$paymentMethodElement')) {
                this.$paymentMethodElement = this.getContent().find(this.options.selectors.paymentMethod);
            }

            return this.$paymentMethodElement;
        }
    });

    return PaymentTransitionButtonComponent;
});
