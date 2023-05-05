define(function(require) {
    'use strict';

    const TransitionButtonComponent = require('orocheckout/js/app/components/transition-button-component');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const MultiShippingTransitionButtonComponent = TransitionButtonComponent.extend(/** @lends ShippingTransitionButtonComponent.prototype */{
        selectedShippingMethods: null,

        defaults: $.extend(true, {}, TransitionButtonComponent.prototype.defaults, {
            selectors: {
                checkoutRequire: '[data-role="checkout-require"]', // Required field label
                shippingMethod: '[name$="[line_items_shipping_methods]"]'
            }
        }),

        listen: {
            'multi-shipping-method:changed mediator': 'onShippingMethodTypeChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function MultiShippingTransitionButtonComponent(options) {
            MultiShippingTransitionButtonComponent.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize(options) {
            MultiShippingTransitionButtonComponent.__super__.initialize.call(this, options);
            this.initSelectedMethods();
        },

        initSelectedMethods() {
            const selectedMethodValue = this.getShippingMethodElement().val();
            this.selectedShippingMethods = selectedMethodValue ? JSON.parse(selectedMethodValue) : {};
        },

        /**
         * @param {string} type
         * @param {string} method
         * @param {string} itemId
         */
        setElementsValue(type, method, itemId) {
            this.selectedShippingMethods[itemId] = {method, type};
            this.getShippingMethodElement().val(JSON.stringify(this.selectedShippingMethods));
        },

        /**
         * @param {string} itemId
         * @param {string} method
         * @param {string} type
         */
        onShippingMethodTypeChange(itemId, method, type) {
            this.setElementsValue(type, method, itemId);
            mediator.trigger('checkout:shipping-method:changed');
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getContent() {
            return $(this.options.selectors.checkoutContent);
        },

        onFail() {
            this.$el.removeClass('btn--info');
            this.$el.prop('disabled', true);
            this.$el.closest(this.defaults.selectors.checkoutContent)
                .find(this.defaults.selectors.checkoutRequire)
                .addClass('hidden');

            mediator.trigger('transition:failed');
            MultiShippingTransitionButtonComponent.__super__.onFail.call(this);
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodElement() {
            return this.getContent().find(this.options.selectors.shippingMethod);
        }
    });

    return MultiShippingTransitionButtonComponent;
});
