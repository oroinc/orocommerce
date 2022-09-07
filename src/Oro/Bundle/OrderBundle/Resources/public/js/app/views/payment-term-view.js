define(function(require) {
    'use strict';

    const BasePaymentTermView = require('oropaymentterm/js/app/views/payment-term-view');

    /**
     * @export oroorder/js/app/views/payment-term-view
     * @extends oropayment/js/app/views/PaymentTermView
     * @class oroorder.app.views.PaymentTermView
     */
    const PaymentTermView = BasePaymentTermView.extend({
        listen: {
            'order:loaded:related-data mediator': 'loadedRelatedData'
        },

        /**
         * @inheritdoc
         */
        constructor: function PaymentTermView(options) {
            PaymentTermView.__super__.constructor.call(this, options);
        }
    });

    return PaymentTermView;
});
