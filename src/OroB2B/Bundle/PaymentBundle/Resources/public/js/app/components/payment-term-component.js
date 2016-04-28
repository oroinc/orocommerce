define(function(require) {
    'use strict';

    var PaymentTermComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');

    PaymentTermComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            paymentMethod: null
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            mediator.on('checkout:place-order:response', this.handleSubmit, this);
        },

        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;
                mediator.execute(
                    'redirectTo',
                    {
                        url: routing.generate(
                            'orob2b_checkout_frontend_checkout',
                            {
                                id: eventData.responseData.checkoutId,
                                transition: 'finish_checkout'
                            }
                        )
                    },
                    {redirect: true}
                );
            }
        },

        dispose: function() {
        }
    });

    return PaymentTermComponent;
});
