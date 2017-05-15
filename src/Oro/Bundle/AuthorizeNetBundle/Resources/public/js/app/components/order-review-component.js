define(function(require) {
    'use strict';

    var OrderReviewComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    OrderReviewComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null
        },

        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            mediator.on('checkout:place-order:response', this.placeOrderResponse, this);
        },

        dispose: function() {
            mediator.off('checkout:place-order:response', this.placeOrderResponse, this);
            OrderReviewComponent.__super__.dispose.call(this);
        },

        placeOrderResponse: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                if (!eventData.responseData.purchaseSuccessful) {
                    eventData.stopped = true;
                    mediator.execute('redirectTo', {url: eventData.responseData.errorUrl}, {redirect: true});
                }
            }
        }
    });

    return OrderReviewComponent;
});
