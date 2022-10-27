define(function(require) {
    'use strict';

    const TransitionButtonComponent = require('orocheckout/js/app/components/transition-button-component');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const ShippingTransitionButtonComponent = TransitionButtonComponent.extend(/** @lends ShippingTransitionButtonComponent.prototype */{
        /**
         * @inheritdoc
         */
        constructor: function ShippingTransitionButtonComponent(options) {
            ShippingTransitionButtonComponent.__super__.constructor.call(this, options);
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
                        shippingForm: '[data-content="shipping_method_form"]',
                        shippingMethodTypeSelector: '[name$="shippingMethodType"]',
                        shippingMethodTypeSelectorAbsolute: '[data-content="shipping_method_form"]' +
                            ' [name$="shippingMethodType"]',
                        checkoutRequire: '[data-role="checkout-require"]',
                        shippingMethod: '[name$="[shipping_method]"]',
                        shippingMethodType: '[name$="[shipping_method_type]"]'
                    }
                }
            );

            ShippingTransitionButtonComponent.__super__.initialize.call(this, options);

            mediator.on('checkout:shipping-method:rendered', this.onShippingMethodRendered, this);
        },

        onShippingMethodRendered: function() {
            this.getContent().on(
                'change',
                this.options.selectors.shippingMethodTypeSelectorAbsolute,
                this.onShippingMethodTypeChange.bind(this)
            );

            this.initShippingMethod();
        },

        initShippingMethod: function() {
            const selectedTypeValue = this.getShippingMethodTypeElement().val();
            const selectedMethodValue = this.getShippingMethodElement().val();
            if (this.getShippingMethodTypeSelector().length && selectedTypeValue && selectedMethodValue) {
                const selectedEl = this
                    .getShippingMethodTypeSelector()
                    .filter('[data-shipping-type="' + selectedTypeValue + '"]')
                    .filter('[data-shipping-method="' + selectedMethodValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            } else {
                const selectedType = this.getShippingMethodTypeSelector().filter(':checked');
                if (selectedType.val()) {
                    const method = $(selectedType).data('shipping-method');
                    const type = $(selectedType).data('shipping-type');
                    this.setElementsValue(type, method);
                } else {
                    this.setElementsValue(null, null);
                }
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getContent().off('change', this.options.selectors.shippingMethodTypeSelectorAbsolute);

            ShippingTransitionButtonComponent.__super__.dispose.call(this);
        },

        /**
         *
         * @param {string} type
         * @param {string} method
         */
        setElementsValue: function(type, method) {
            this.getShippingMethodTypeElement().val(type);
            this.getShippingMethodElement().val(method);
        },

        /**
         * @param {Event} event
         */
        onShippingMethodTypeChange: function(event) {
            mediator.trigger('checkout:shipping-method:changed');
            const methodType = $(event.target);
            const method = methodType.data('shipping-method');
            const type = methodType.data('shipping-type');
            this.setElementsValue(type, method);
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
        getShippingForm: function() {
            return $(this.options.selectors.shippingForm);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodTypeSelector: function() {
            return this.getShippingForm().find(this.options.selectors.shippingMethodTypeSelector);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodTypeElement: function() {
            return this.getContent().find(this.options.selectors.shippingMethodType);
        },

        onFail: function() {
            this.$el.removeClass('btn--info');
            this.$el.prop('disabled', true);
            this.$el.closest(this.defaults.selectors.checkoutContent)
                .find(this.defaults.selectors.checkoutRequire)
                .addClass('hidden');

            mediator.trigger('transition:failed');
            ShippingTransitionButtonComponent.__super__.onFail.call(this);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodElement: function() {
            return this.getContent().find(this.options.selectors.shippingMethod);
        }
    });

    return ShippingTransitionButtonComponent;
});
