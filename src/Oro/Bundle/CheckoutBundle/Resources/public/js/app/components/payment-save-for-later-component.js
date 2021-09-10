define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');

    const BaseComponent = require('oroui/js/app/components/base/component');

    const PaymentSaveForLaterComponent = BaseComponent.extend(/** @lends PaymentSaveForLaterComponent.prototype */ {
        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {Object}
         */
        selectors: {
            saveForLaterSelector: '[name$="[payment_save_for_later]"]'
        },

        /**
         * @property {Boolean}
         */
        defaultState: true,

        /**
         * @inheritdoc
         */
        constructor: function PaymentSaveForLaterComponent(options) {
            PaymentSaveForLaterComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);
            this.defaultState = this.getCheckboxState();

            mediator.on('checkout:payment:save-for-later:change', this.onChange, this);
            mediator.on('checkout:payment:save-for-later:restore-default', this.onRestoreDefault, this);
        },

        /**
         * @param {Boolean} state
         */
        onChange: function(state) {
            this.setCheckboxState(state);
        },

        onRestoreDefault: function() {
            this.setCheckboxState(this.defaultState);
        },

        /**
         * @param {Boolean} state
         */
        setCheckboxState: function(state) {
            this.getPaymentSaveForLaterElement()
                .prop('checked', state)
                .trigger('change');
        },

        /**
         * @returns {Boolean}
         */
        getCheckboxState: function() {
            return this.getPaymentSaveForLaterElement().prop('checked');
        },

        /**
         * @returns {jQuery}
         */
        getPaymentSaveForLaterElement: function() {
            if (!this.hasOwnProperty('$paymentSaveForLaterElement')) {
                this.$paymentSaveForLaterElement = this.$el.find(this.selectors.saveForLaterSelector);
            }

            return this.$paymentSaveForLaterElement;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout:payment:save-for-later:change', this.onChange, this);
            mediator.off('checkout:payment:save-for-later:restore-default', this.onRestoreDefault, this);

            PaymentSaveForLaterComponent.__super__.dispose.call(this);
        }
    });

    return PaymentSaveForLaterComponent;
});
