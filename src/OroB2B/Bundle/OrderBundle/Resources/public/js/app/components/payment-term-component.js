define(function(require) {
    'use strict';

    var PaymentTermComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orob2border/js/app/components/payment-term-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.PaymentTermComponent
     */
    PaymentTermComponent = BaseComponent.extend({
        /**
         * @property {jQuery}
         */
        $input: null,

        /**
         * @property {jQuery}
         */
        inputChanged: false,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$input = options._sourceElement.find('input.select2');

            var self = this;
            this.$input.change(function() {
                self.inputChanged = true;
            });

            mediator.on('order:loaded:related-data', _.bind(this.loadedRelatedData, this));
        },

        loadedRelatedData: function(response) {
            var paymentTerm = response.paymentTerm || null;
            if (!paymentTerm || this.inputChanged) {
                return;
            }

            this.$input.select2('val', paymentTerm);
        }
    });

    return PaymentTermComponent;
});
