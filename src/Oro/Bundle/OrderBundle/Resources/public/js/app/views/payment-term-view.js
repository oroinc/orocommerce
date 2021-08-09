define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const BasePaymentTermView = require('oropaymentterm/js/app/views/payment-term-view');

    /**
     * @export oroorder/js/app/views/payment-term-view
     * @extends oropayment/js/app/views/PaymentTermView
     * @class oroorder.app.views.PaymentTermView
     */
    const PaymentTermView = BasePaymentTermView.extend({
        /**
         * @inheritdoc
         */
        constructor: function PaymentTermView(options) {
            PaymentTermView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            PaymentTermView.__super__.initialize.call(this, options);

            mediator.on('order:loaded:related-data', this.loadedRelatedData, this);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:loaded:related-data', this.loadedRelatedData, this);

            PaymentTermView.__super__.dispose.call(this);
        }
    });

    return PaymentTermView;
});
