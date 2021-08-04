define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const BaseComponent = require('oroui/js/app/components/base/component');

    const PaymentValidateComponent = BaseComponent.extend(/** @lends PaymentValidateComponent.prototype */ {
        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {Object}
         */
        selectors: {
            validateCheckboxSelector: '[name$="[payment_validate]"]'
        },

        /**
         * @inheritdoc
         */
        constructor: function PaymentValidateComponent(options) {
            PaymentValidateComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);

            mediator.on('checkout:payment:validate:get-value', this.onGetValue, this);
            mediator.on('checkout:payment:validate:change', this.onChange, this);
        },

        /**
         * @param {Boolean} state
         */
        onChange: function(state) {
            this.setCheckboxState(state);
        },

        /**
         * @param {Object} object
         */
        onGetValue: function(object) {
            object.value = this.getCheckboxState();
        },

        /**
         * @param {Boolean} state
         */
        setCheckboxState: function(state) {
            this.getValidateCheckboxElement()
                .prop('checked', state)
                .trigger('change');
        },

        /**
         * @returns {Boolean}
         */
        getCheckboxState: function() {
            return this.getValidateCheckboxElement().prop('checked');
        },

        /**
         * @returns {jQuery}
         */
        getValidateCheckboxElement: function() {
            if (!this.hasOwnProperty('$validateCheckboxElement')) {
                this.$validateCheckboxElement = this.$el.find(this.selectors.validateCheckboxSelector);
            }

            return this.$validateCheckboxElement;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout:payment:validate:get-value', this.onGetValue, this);
            mediator.off('checkout:payment:validate:change', this.onChange, this);

            PaymentValidateComponent.__super__.dispose.call(this);
        }
    });

    return PaymentValidateComponent;
});
