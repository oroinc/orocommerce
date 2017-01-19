/** @lends ShippingTransitionButtonComponent */
define(function(require) {
    'use strict';

    var TransitionButtonComponent = require('orocheckout/js/app/components/transition-button-component');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    var ShippingTransitionButtonComponent;
    ShippingTransitionButtonComponent = TransitionButtonComponent.extend(/** @exports ShippingTransitionButtonComponent.prototype */{
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.defaults.selectors.shippingForm = '[data-content="shipping_method_form"]';
            this.defaults.selectors.shippingMethodTypeSelector = '[name$="shippingMethodType"]';
            this.defaults.selectors.shippingMethod = '[name$="[shipping_method]"]';
            this.defaults.selectors.shippingMethodType = '[name$="[shipping_method_type]"]';

            ShippingTransitionButtonComponent.__super__.initialize.call(this, options);

            mediator.on('checkout:shipping-method:rendered', this.onShippingMethodRendered, this);
        },

        onShippingMethodRendered: function() {
            this.getShippingMethodTypeSelector().on('change', $.proxy(this.onShippingMethodTypeChange, this));
            this.initShippingMethod();
        },

        initShippingMethod: function() {
            var selectedTypeValue = this.getShippingMethodTypeElement().val();
            var selectedMethodValue = this.getShippingMethodElement().val();
            if (this.getShippingMethodTypeSelector().length && selectedTypeValue && selectedMethodValue) {
                var selectedEl = this
                  .getShippingMethodTypeSelector()
                  .filter('[value="' + selectedTypeValue + '"]')
                  .filter('[data-shipping-method="' + selectedMethodValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            } else {
                var selectedType = this.getShippingMethodTypeSelector().filter(':checked');
                if (selectedType.val()) {
                    var method = $(selectedType).data('shipping-method');
                    this.setElementsValue(selectedType.val(), method);
                } else {
                    this.setElementsValue(null, null);
                }
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.getShippingMethodTypeSelector().off('change', $.proxy(this.onShippingMethodTypeChange, this));

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
            var methodType = $(event.target);
            var method = methodType.data('shipping-method');
            this.setElementsValue(methodType.val(), method);
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
        getShippingForm: function() {
            if (!this.hasOwnProperty('$shippingForm')) {
                this.$shippingForm = $(this.options.selectors.shippingForm);
            }

            return this.$shippingForm;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodTypeSelector: function() {
            if (!this.hasOwnProperty('$shippingMethodTypeSelector')) {
                this.$shippingMethodTypeSelector = this.getShippingForm().find(this.options.selectors.shippingMethodTypeSelector);
            }

            return this.$shippingMethodTypeSelector;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodTypeElement: function() {
            if (!this.hasOwnProperty('$shippingMethodTypeElement')) {
                this.$shippingMethodTypeElement = this.getContent().find(this.options.selectors.shippingMethodType);
            }

            return this.$shippingMethodTypeElement;
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
            if (!this.hasOwnProperty('$shippingMethodElement')) {
                this.$shippingMethodElement = this.getContent().find(this.options.selectors.shippingMethod);
            }

            return this.$shippingMethodElement;
        }
    });

    return ShippingTransitionButtonComponent;
});
