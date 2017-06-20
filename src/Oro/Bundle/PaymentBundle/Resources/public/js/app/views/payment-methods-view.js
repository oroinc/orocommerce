define(function(require) {
    'use strict';

    var PaymentMethodsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    PaymentMethodsView = BaseView.extend({
        autoRender: true,

        render: function() {
            mediator.trigger('checkout:payment-method:rendered');
        },
    });

    return PaymentMethodsView;
});
