/** @lends PaymentTransitionButtonComponent */
define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orob2bcheckout/js/app/components/transition-button-component');
    var $ = require('jquery');
    var _ = require('underscore');

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

            this.getPaymentMethodSelector().on('change', _.bind(this.onPaymentMethodChange, this));
            this.getPaymentMethodSelector().trigger('change');
        },

        /**
         * @inheritDoc
         */
        transit: function(e, data) {
            this.getPaymentForm()
                .addClass('hidden')
                .insertAfter($(this.defaults.selectors.checkoutContent));

            PaymentTransitionButtonComponent.__super__.transit.call(this, e, data);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getPaymentMethodSelector().off('change', _.bind(this.onPaymentMethodChange, this));

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
            return $(this.defaults.selectors.checkoutContent);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentForm: function() {
            return this.getContent().find(this.defaults.selectors.paymentForm);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentMethodSelector: function() {
            return this.getPaymentForm().find(this.defaults.selectors.paymentMethodSelector);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getPaymentMethodElement: function() {
            return this.getContent().find(this.defaults.selectors.paymentMethod);
        }
    });

    return PaymentTransitionButtonComponent;
});
