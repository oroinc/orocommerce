define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orocheckout/js/app/components/transition-button-component');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    var PaymentTransitionButtonComponent;
    PaymentTransitionButtonComponent = TransitionButtonComponent.extend({
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
                $.proxy(this.onPaymentMethodChange, this)
            );
            this.initPaymentMethod();
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
                    mediator.trigger('checkout:payment:remove-filled-form', filledForm);
                    filledForm.remove();
                }
            }

            if (selectedValue) {
                var selectedEl = this.getPaymentMethodSelector().filter('[value="' + selectedValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            } else {
                this.getPaymentMethodElement().val(this.getPaymentMethodSelector().filter(':checked').val());
            }
        },

        /**
         * @inheritDoc
         */
        transit: function(e, data) {
            e.preventDefault();
            if (!this.options.enabled) {
                return;
            }

            var paymentMethod = this.getPaymentMethodElement().val();
            var eventData = {
                stopped: false,
                resume: $.proxy(this.continueTransit, this, e, data),
                data: {paymentMethod: paymentMethod}
            };

            mediator.trigger('checkout:payment:before-transit', eventData);
            if (eventData.stopped) {
                return;
            }

            this.continueTransit(e, data);
        },

        continueTransit: function(e, data) {
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

            this.getContent().off('change', this.options.selectors.paymentMethodSelectorAbsolute);

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
         * @param {Event} event
         */
        onSubmit: function(event) {
            var paymentMethod = this.getPaymentMethodElement().val();
            var eventData = {paymentMethod: paymentMethod};
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
