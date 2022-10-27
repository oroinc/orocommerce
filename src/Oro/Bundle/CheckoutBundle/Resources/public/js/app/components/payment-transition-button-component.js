define(function(require) {
    'use strict';

    const TransitionButtonComponent = require('orocheckout/js/app/components/transition-button-component');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const PaymentTransitionButtonComponent = TransitionButtonComponent.extend({
        /**
         * @inheritdoc
         */
        constructor: function PaymentTransitionButtonComponent(options) {
            PaymentTransitionButtonComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.defaults = $.extend(
                true,
                {},
                this.defaults,
                {
                    selectors: {
                        paymentForm: '[data-content="payment_method_form"]',
                        paymentMethodSelectorAbsolute: '[data-content="payment_method_form"] [name="paymentMethod"]',
                        paymentMethodSelector: '[name="paymentMethod"]',
                        paymentMethod: '[name$="[payment_method]"]'
                    }
                }
            );

            PaymentTransitionButtonComponent.__super__.initialize.call(this, options);

            this.onPaymentMethodRendered();
        },

        onPaymentMethodRendered: function() {
            this.getContent().on(
                'change',
                this.options.selectors.paymentMethodSelectorAbsolute,
                this.onPaymentMethodChange.bind(this)
            );
            this.initPaymentMethod();
        },

        initPaymentMethod: function() {
            const filledForm = this.getContent().next(this.options.selectors.paymentForm);
            const selectedValue = this.getPaymentMethodElement().val();
            if (filledForm.length > 0) {
                if (selectedValue) {
                    mediator.trigger('checkout:payment:before-restore-filled-form', filledForm);
                    filledForm.removeClass('hidden');
                    this.getPaymentForm().replaceWith(filledForm);
                    delete this.$paymentForm;
                } else {
                    mediator.trigger('checkout:payment:remove-filled-form', filledForm);
                    filledForm.remove();
                }
            }

            if (selectedValue) {
                const selectedEl = this.getPaymentMethodSelector().filter('[value="' + selectedValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            } else {
                this.getPaymentMethodElement().val(this.getPaymentMethodSelector().filter(':checked').val());
            }
        },

        /**
         * @inheritdoc
         */
        transit: function(e, data) {
            e.preventDefault();
            if (!this.options.enabled) {
                return;
            }

            const paymentMethod = this.getPaymentMethodElement().val();
            const eventData = {
                stopped: false,
                resume: this.continueTransit.bind(this, e, data),
                data: {paymentMethod: paymentMethod}
            };

            mediator.trigger('checkout:payment:before-transit', eventData);
            if (eventData.stopped) {
                return;
            }

            this.continueTransit(e, data);
        },

        continueTransit: function(e, data) {
            const filledForm = this.getPaymentForm();
            mediator.trigger('checkout:payment:before-hide-filled-form', filledForm);
            filledForm
                .addClass('hidden')
                .insertAfter(this.getContent());

            PaymentTransitionButtonComponent.__super__.transit.call(this, e, data);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getContent().off('change', this.options.selectors.paymentMethodSelectorAbsolute);

            PaymentTransitionButtonComponent.__super__.dispose.call(this);
        },

        /**
         * @param {Event} event
         */
        onPaymentMethodChange: function(event) {
            const target = $(event.target);
            this.getPaymentMethodElement().val(target.val());
        },

        /**
         * @param {Event} event
         */
        onSubmit: function(event) {
            const paymentMethod = this.getPaymentMethodElement().val();
            const eventData = {paymentMethod: paymentMethod};
            mediator.trigger('checkout:payment:before-form-serialization', eventData);

            PaymentTransitionButtonComponent.__super__.onSubmit.call(this, event);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getContent: function() {
            return $(this.options.selectors.checkoutContent);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentForm: function() {
            return this.getContent().find(this.options.selectors.paymentForm);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentMethodSelector: function() {
            return this.getPaymentForm().find(this.options.selectors.paymentMethodSelector);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentMethodElement: function() {
            return this.getContent().find(this.options.selectors.paymentMethod);
        }
    });

    return PaymentTransitionButtonComponent;
});
