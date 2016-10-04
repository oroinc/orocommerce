define(function(require) {
    'use strict';

    var CheckoutInnerContentView;
    var mediator = require('oroui/js/mediator');
    var CheckoutContentView = require('orocheckout/js/app/views/checkout-content-view');

    CheckoutInnerContentView = CheckoutContentView.extend({
        _onContentUpdated: function() {
            this.initLayout().then(function() {
                mediator.trigger('checkout-content:initialized');
            });
        }
    });

    return CheckoutInnerContentView;
});
