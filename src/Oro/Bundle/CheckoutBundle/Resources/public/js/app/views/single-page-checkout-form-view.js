define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const SinglePageCheckoutSubmitButtonView =
        require('orocheckout/js/app/views/single-page-checkout-submit-button-view');
    const SinglePageCheckoutAddressView = require('orocheckout/js/app/views/single-page-checkout-address-view');

    const SinglePageCheckoutFormView = BaseView.extend({
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
            stateTokenSelector: '[name$="[state_token]"]',
            entityId: null
        },

        /**
         * @inheritDoc
         */
        events: {
            change: 'onChange',
            forceChange: 'onForceChange',
            submit: 'onSubmit'
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
        constructor: function SinglePageCheckoutFormView(options) {
            this.onChange = _.debounce(this.onChange, this.timeout);
            SinglePageCheckoutFormView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options || {});

            this.subview('checkoutSubmitButton', new SinglePageCheckoutSubmitButtonView({
                el: this.$el.find(this.options.submitButtonSelector)
            }));

            this.subview('checkoutBillingAddress', new SinglePageCheckoutAddressView({
                el: this.$el.find(this.options.billingAddressSelector),
                entityId: this.options.entityId
            }));

            this.subview('checkoutShippingAddress', new SinglePageCheckoutAddressView({
                el: this.$el.find(this.options.shippingAddressSelector),
                entityId: this.options.entityId
            }));

            this._toggleShipTo();
            this._disableShippingAddress();
            this._changeShippingMethod();
            this._changePaymentMethod();

            SinglePageCheckoutFormView.__super__.initialize.call(this, options);
        },

        afterCheck: function($el, force) {
            if (!$el) {
                $el = this.$el;
            }
            const serializedData = this.getSerializedData();

            if (this.lastSerializedData === serializedData && !force) {
                return;
            }

            this.trigger('after-check-form', serializedData, $el);
            this.lastSerializedData = serializedData;
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

            this._toggleShipTo();
            this._disableShippingAddress();

            this._changeShippingMethod();
            this._changePaymentMethod();

            this.afterCheck($(event.target), false);
        },

        /**
         * @param {jQuery.Event} event
         */
        onForceChange: function(event) {
            this._toggleShipTo();
            this._disableShippingAddress();

            this._changeShippingMethod();
            this._changePaymentMethod();

            this.afterCheck($(event.target), true);
        },

        onBeforeSaveState: function() {
            this._disableShippingAddress();
            this.subview('checkoutSubmitButton').onToggleState();
        },

        onAfterSaveState: function() {
            this.subview('checkoutBillingAddress').setElement(this.$el.find(this.options.billingAddressSelector));
            this.subview('checkoutBillingAddress').onEnableState();

            this.subview('checkoutShippingAddress').setElement(this.$el.find(this.options.shippingAddressSelector));
            this.subview('checkoutShippingAddress').onEnableState();

            // Resets submit button element
            this.subview('checkoutSubmitButton').setElement(this.$el.find(this.options.submitButtonSelector));
            this.subview('checkoutSubmitButton').onEnableState();
        },

        /**
         * @param {jQuery.Event} event
         */
        onSubmit: function(event) {
            event.preventDefault();

            const validate = this.$el.validate();
            if (!validate.form()) {
                return;
            }

            const paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            const eventData = {
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
            this._changeShippingMethod();
            this._changePaymentMethod();

            const paymentMethod = this.$el.find(this.options.formPaymentMethodSelector).val();
            const eventData = {paymentMethod: paymentMethod};
            mediator.trigger('checkout:payment:before-form-serialization', eventData);

            this.subview('checkoutSubmitButton').onToggleState();

            this.trigger('submit-form', this.getSerializedData());
        },

        getSerializedData: function() {
            const $form = this.$el.closest('form');
            $form.find(this.options.stateTokenSelector).prop('disabled', false);

            return $form.find(this.options.transitionFormFieldSelector).serialize();
        },

        _disableShippingAddress: function() {
            const $element = this.$el.find(this.options.shipToSelector);
            const disable = $element.is(':visible') && $element.is(':checked');
            const $billingAddress = this.subview('checkoutBillingAddress').$el;
            const text = $billingAddress.find(':selected').text();

            this.subview('checkoutShippingAddress').onToggleState(disable, $billingAddress.val(), text);
        },

        _isAvailableShipTo: function() {
            return this.subview('checkoutBillingAddress').isAvailableShippingType('shipping');
        },

        _toggleShipTo: function() {
            const $element = this.$el.find(this.options.shipToSelector);
            const $container = $element.parent();
            if (this._isAvailableShipTo()) {
                $container.removeClass('hidden');
            } else {
                $element.prop('checked', false);
                $container.addClass('hidden');
            }
        },

        _changeShippingMethod: function() {
            const $selectedType = this.$el.find(this.options.originShippingMethodTypeSelector).filter(':checked');

            if (!$selectedType.val()) {
                return;
            }

            this.$el.find(this.options.formShippingMethodSelector).val($selectedType.data('shipping-method'));
            this.$el.find(this.options.formShippingMethodTypeSelector).val($selectedType.data('shipping-type'));
        },

        _changePaymentMethod: function() {
            const $selectedMethodVal = this.$el.find(this.options.originPaymentMethodSelector).filter(':checked').val();

            if (!$selectedMethodVal) {
                return;
            }

            this.$el.find(this.options.formPaymentMethodSelector).val($selectedMethodVal);
        }
    });

    return SinglePageCheckoutFormView;
});
