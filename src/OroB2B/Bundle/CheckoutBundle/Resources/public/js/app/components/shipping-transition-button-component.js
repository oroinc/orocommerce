/** @lends PaymentTransitionButtonComponent */
define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orob2bcheckout/js/app/components/transition-button-component');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    var ShippingTransitionButtonComponent;
    ShippingTransitionButtonComponent = TransitionButtonComponent.extend(/** @exports ShippingTransitionButtonComponent.prototype */{
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.defaults.selectors.paymentForm = '[data-content="shipping_method_form"]';
            this.defaults.selectors.shippingMethodSelector = '[name="shippingMethod"]';
            this.defaults.selectors.shippingMethod = '[name$="[shipping_method]"]';

            ShippingTransitionButtonComponent.__super__.initialize.call(this, options);

            this.initShippingMethod();
            this.getShippingMethodSelector().on('change', $.proxy(this.onShippingMethodChange, this));
        },

        initShippingMethod: function() {
            var filledForm = this.getContent().next(this.options.selectors.paymentForm);
            var selectedValue = this.getShippingMethodElement().val();
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
                    var selectedEl = this.getShippingMethodSelector().filter('[value="' + selectedValue + '"]');
                    selectedEl.prop('checked', 'checked');
                    selectedEl.trigger('change');
                } else {
                    this.getShippingMethodElement().val(this.getShippingMethodSelector().filter(':checked').val());
                }
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

            var shippingMethod = this.getShippingMethodElement().val();
            var eventData = {stopped: false, data: {shippingMethod: shippingMethod}};

            mediator.trigger('checkout:payment:before-transit', eventData);
            if (eventData.stopped) {
                return;
            }

            var filledForm = this.getPaymentForm();
            mediator.trigger('checkout:payment:before-hide-filled-form', filledForm);
            filledForm
                .addClass('hidden')
                .insertAfter(this.getContent());

            ShippingTransitionButtonComponent.__super__.transit.call(this, e, data);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getShippingMethodSelector().off('change', $.proxy(this.onShippingMethodChange, this));

            ShippingTransitionButtonComponent.__super__.dispose.call(this);
        },

        /**
         * @param {Event} event
         */
        onShippingMethodChange: function(event) {
            var target = $(event.target);
            this.getShippingMethodElement().val(target.val());
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
        getShippingMethodSelector: function() {
            if (!this.hasOwnProperty('$shippingMethodSelector')) {
                this.$shippingMethodSelector = this.getPaymentForm().find(this.options.selectors.shippingMethodSelector);
            }

            return this.$shippingMethodSelector;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodElement: function() {
            if (!this.hasOwnProperty('$shippingMethodElement')) {
                this.$shippingMethodElement = this.getContent().find(this.options.selectors.shippingMethod);
            }

            return this.$shippingMethodElement;
        }
    });

    return ShippingTransitionButtonComponent;
});
