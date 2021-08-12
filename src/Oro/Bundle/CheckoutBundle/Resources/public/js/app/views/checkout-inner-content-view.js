define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const CheckoutContentView = require('orocheckout/js/app/views/checkout-content-view');

    const CheckoutInnerContentView = CheckoutContentView.extend({
        /**
         * @inheritdoc
         */
        constructor: function CheckoutInnerContentView(options) {
            CheckoutInnerContentView.__super__.constructor.call(this, options);
        },

        _onContentUpdated: function() {
            this.initLayout().then(function() {
                mediator.trigger('checkout-content:initialized');
            });
        }
    });

    return CheckoutInnerContentView;
});
