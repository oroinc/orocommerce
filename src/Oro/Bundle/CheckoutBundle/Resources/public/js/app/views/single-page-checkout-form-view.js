define(function(require) {
    'use strict';

    var SinglePageCheckoutFormView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    var SinglePageCheckoutSubmitButtonView =
        require('orocheckout/js/app/views/single-page-checkout-submit-button-view');
    var SinglePageCheckoutAddressView = require('orocheckout/js/app/views/single-page-checkout-address-view');

    SinglePageCheckoutFormView = BaseView.extend({
        /**
         * @property
         */
        options: {
            submitButtonSelector: '[type="submit"]',
            billingAddressSelector: 'select[data-role="checkout-billing-address"]',
            shippingAddressSelector: 'select[data-role="checkout-shipping-address"]',
            shipToSelector: '[data-role="checkout-ship-to"]',
            transitionFormFieldSelector: '[name*="oro_workflow_transition"]',
            originShippingMethodTypeSelector: '[name$="shippingMethodType"]',
            formShippingMethodSelector: '[name$="[shipping_method]"]',
            formShippingMethodTypeSelector: '[name$="[shipping_method_type]"]',
            originPaymentMethodSelector: '[name="paymentMethod"]',
            formPaymentMethodSelector: '[name$="[payment_method]"]',
            originPaymentFormSelector: '[data-content="payment_method_form"]',
            entityId: null
        },

        /**
         * @inheritDoc
         */
        events: {
            'change': 'onChange',
            'submit': 'onSubmit'
        },

        /**
         * @inheritDoc
         */
        listen: {
            'before-save-state': 'onBeforeSaveState',
            'after-save-state': 'onAfterSaveState'
        },

        /**
         * @property {string}
         */
        lastSerializedData: null,

        /**
         * @property {number}
         */
        timeout: 50,

        /**
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutFormView() {
            this.onChange = _.debounce(this.onChange, this.timeout);
            SinglePageCheckoutFormView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});

            this.subview('checkoutSubmitButton', new SinglePageCheckoutSubmitButtonView({
                    el: this.$el.find(this.options.submitButtonSelector)
                })
            );

            this.subview('checkoutBillingAddress', new SinglePageCheckoutAddressView({
                    el: this.$el.find(this.options.billingAddressSelector),
                    entityId: this.options.entityId
                })
            );

            this.subview('checkoutShippingAddress', new SinglePageCheckoutAddressView({
                    el: this.$el.find(this.options.shippingAddressSelector),
                    entityId: this.options.entityId
                })
            );

            this._disableShippingAddress();
            this._changeShippingMethod();
            this._changePaymentMethod();

            SinglePageCheckoutFormView.__super__.initialize.call(this, arguments);
        },

        /**
         * @param {jQuery.Event} event
         */
        onChange: function(event) {
            if (this.subview('checkoutSubmitButton').isHovered()) {
                return;
            }

            // Do not execute logic when hidden element (form) is refreshed
            if (!$(event.target).is(':visible')) {
                return;
            }

            var validate = this.$el.validate();
            if (!validate.checkForm()) {
                return;
            }

            this._disableShippingAddress();
            this._changeShippingMethod();
            this._changePaymentMethod();

            var serializedData = this.getSerializedData();

            if (this.lastSerializedData === serializedData) {
                return;
            }

            this.trigger('after-check-form', serializedData, $(event.target));
            this.lastSerializedData = serializedData;
        },

        onBeforeSaveState: function() {
            this._disableShippingAddress();
            this.subview('checkoutSubmitButton').onToggleState();
        },

        onAfterSaveState: function() {
            // Resets submit button element
            this.subview('checkoutSubmitButton').setElement(this.$el.find(this.options.submitButtonSelector));
        },

        /**
         * @param {jQuery.Event} event
         */
        onSubmit: function(event) {
            event.preventDefault();

            var validate = this.$el.validate();
            if (!validate.form()) {
                return;
            }

            var paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            var eventData = {
                stopped: false,
                resume: _.bind(this.transit, this),
                data: {paymentMethod: paymentMethod}
            };

            mediator.trigger('checkout:payment:before-transit', eventData);

            if (eventData.stopped) {
                return;
            }

            this.transit();
        },

        transit: function() {
            var paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            var eventData = {paymentMethod: paymentMethod};
            mediator.trigger('checkout:payment:before-form-serialization', eventData);

            this.subview('checkoutSubmitButton').onToggleState();

            this.trigger('submit-form', this.getSerializedData());
        },

        getSerializedData: function() {
            return this.$el.find(this.options.transitionFormFieldSelector).serialize();
        },

        _disableShippingAddress: function() {
            var $element = this.$el.find(this.options.shipToSelector);
            if ($element.is(this.options.shipToSelector)) {
                var $billingAddress = this.subview('checkoutBillingAddress').$el;
                this.subview('checkoutShippingAddress').onToggleState(
                    $element.is(':checked'),
                    $billingAddress.val(),
                    $billingAddress.find(':selected').text()
                );
            }
        },

        _changeShippingMethod: function() {
            var $selectedType = this.$el.find(this.options.originShippingMethodTypeSelector).filter(':checked');

            if (!$selectedType.val()) {
                return;
            }

            this.$el.find(this.options.formShippingMethodSelector).val($selectedType.data('shipping-method'));
            this.$el.find(this.options.formShippingMethodTypeSelector).val($selectedType.data('shipping-type'));
        },

        _changePaymentMethod: function() {
            var $selectedMethodVal = this.$el.find(this.options.originPaymentMethodSelector).filter(':checked').val();

            if (!$selectedMethodVal) {
                return;
            }

            this.$el.find(this.options.formPaymentMethodSelector).val($selectedMethodVal);
        }
    });

    return SinglePageCheckoutFormView;
});
