/** @lends PaymentSaveForFutureComponent */
define(function(require) {
    'use strict';

    var PaymentSaveForFutureComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    var BaseComponent = require('oroui/js/app/components/base/component');

    PaymentSaveForFutureComponent = BaseComponent.extend(/** @exports PaymentSaveForFutureComponent.prototype */ {
        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @property {Object}
         */
        options: {
            saveForFutureSelector: '[name$="[payment_save_for_future]"]'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$el = $(options._sourceElement);
            mediator.on('checkout:payment:save-for-future:change', _.bind(this.onSaveForFutureChanged, this));
        },

        /**
         * @param {Boolean} state
         */
        onSaveForFutureChanged: function(state) {
            this.getPaymentSaveForFutureElement().prop('checked', state);
        },

        /**
         * @returns {jQuery}
         */
        getPaymentSaveForFutureElement: function() {
            if (!this.hasOwnProperty('$paymentSaveForFutureElement')) {
                this.$paymentSaveForFutureElement = this.$el.find(this.options.saveForFutureSelector);
            }

            return this.$paymentSaveForFutureElement;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout:payment:save-for-future:change', _.bind(this.onSaveForFutureChanged, this));

            PaymentSaveForFutureComponent.__super__.dispose.call(this);
        }
    });

    return PaymentSaveForFutureComponent;
});
