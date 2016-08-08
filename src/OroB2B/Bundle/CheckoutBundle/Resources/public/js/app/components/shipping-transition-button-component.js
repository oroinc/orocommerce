/** @lends ShippingTransitionButtonComponent */
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
            this.defaults.selectors.shippingForm = '[data-content="shipping_method_form"]';
            this.defaults.selectors.shippingMethodSelector = '[name$="shippingMethod"]';
            this.defaults.selectors.shippingMethodTypeSelector = '[name$="shippingMethodType"]';
            this.defaults.selectors.shippingMethod = '[name$="[shipping_method]"]';
            this.defaults.selectors.shippingMethodType = '[name$="[shipping_method_type]"]';
            this.defaults.selectors.shippingRuleConfig = '[name$="[shipping_rule_config]"]';

            ShippingTransitionButtonComponent.__super__.initialize.call(this, options);
            
            this.getShippingMethodSelector().on('change', $.proxy(this.onShippingMethodChange, this));
            this.getShippingMethodTypeSelector().on('change', $.proxy(this.onShippingMethodTypeChange, this));
            this.initShippingMethod();
        },

        initShippingMethod: function() {
            var selectedTypeValue = this.getShippingMethodTypeElement().val();
            var selectedMethodValue = this.getShippingMethodElement().val();
            if (this.getShippingMethodTypeSelector().length && selectedTypeValue) {
                var selectedEl = this.getShippingMethodTypeSelector().filter('[value="' + selectedTypeValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            }else if(this.getShippingMethodSelector().length && selectedMethodValue){
                var selectedEl = this.getShippingMethodSelector().filter('[value="' + selectedMethodValue + '"]');
                selectedEl.prop('checked', 'checked');
                selectedEl.trigger('change');
            } else {
                var selectedType = this.getShippingMethodTypeSelector().filter(':checked');
                var selectedMethod = this.getShippingMethodSelector().filter(':checked');
                if (selectedType.val()) {
                    var method = $(selectedType).data('shipping-method');
                    var shippingRuleConfig = $(selectedType).data('shipping-rule-config');
                    this.setElementsValue(selectedType.val(), method, shippingRuleConfig);
                } else if (selectedMethod.val()) {
                    var shippingRuleConfig = $(selectedMethod).data('shipping-rule-config');
                    this.setElementsValue(null, selectedMethod.val(), shippingRuleConfig);
                } else {
                    this.setElementsValue(null, null, null);
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

            this.getShippingMethodSelector().off('change', $.proxy(this.onShippingMethodChange, this));
            this.getShippingMethodTypeSelector().off('change', $.proxy(this.onShippingMethodTypeChange, this));

            ShippingTransitionButtonComponent.__super__.dispose.call(this);
        },

        /**
         *
         * @param {string} type
         * @param {string} method
         * @param {int} config
         */
        setElementsValue: function (type, method, config) {
            this.getShippingMethodTypeElement().val(type);
            this.getShippingMethodElement().val(method);
            this.getShippingRuleConfigElement().val(config);
        },

        /**
         * @param {Event} event
         */
        onShippingMethodTypeChange: function(event) {
            var method_type = $(event.target);
            var method = method_type.data('shipping-method');
            var shippingRuleConfig = method_type.data('shipping-rule-config');
            this.setElementsValue(method_type.val(), method, shippingRuleConfig);
        },

        /**
         * @param {Event} event
         */
        onShippingMethodChange: function(event) {
            var method = $(event.target);
            var shippingRuleConfig = method.data('shipping-rule-config');
            this.setElementsValue(null, method.val(), shippingRuleConfig);
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
        getShippingMethodSelector: function() {
            if (!this.hasOwnProperty('$shippingMethodSelector')) {
                this.$shippingMethodSelector = this.getShippingForm().find(this.options.selectors.shippingMethodSelector);
            }

            return this.$shippingMethodSelector;
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

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingMethodElement: function() {
            if (!this.hasOwnProperty('$shippingMethodElement')) {
                this.$shippingMethodElement = this.getContent().find(this.options.selectors.shippingMethod);
            }

            return this.$shippingMethodElement;
        },

        /**
         * @returns {jQuery|HTMLElement}
         */
        getShippingRuleConfigElement: function() {
            if (!this.hasOwnProperty('$shippingRuleConfigElement')) {
                this.$shippingRuleConfigElement = this.getContent().find(this.options.selectors.shippingRuleConfig);
            }

            return this.$shippingRuleConfigElement;
        }
    });

    return ShippingTransitionButtonComponent;
});
