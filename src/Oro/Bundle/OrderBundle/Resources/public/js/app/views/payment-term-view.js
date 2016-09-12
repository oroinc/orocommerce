define(function(require) {
    'use strict';

    var PaymentTermView;
    var mediator = require('oroui/js/mediator');
    var BasePaymentTermView = require('oropayment/js/app/views/payment-term-view');

    /**
     * @export oroorder/js/app/views/payment-term-view
     * @extends oropayment/js/app/views/PaymentTermView
     * @class oroorder.app.views.PaymentTermView
     */
    PaymentTermView = BasePaymentTermView.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            PaymentTermView.__super__.initialize.apply(this, arguments);

            mediator.on('order:loaded:related-data', this.loadedRelatedData, this);
        },

        /**
         * @inheritDoc
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
