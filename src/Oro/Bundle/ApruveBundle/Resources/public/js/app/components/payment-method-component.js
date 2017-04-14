define(function(require) {
    'use strict';

    var PaymentMethodComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oropayment/js/app/components/payment-method-component');

    PaymentMethodComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            orderIdParamName: 'apruveOrderId',
            apruvejsUri: '',
            paymentMethod: null
        },

        apruve: null,

        returnUrl: '',
        errorUrl: '',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.apruve = require(this.options.apruvejsUri);

            this.apruve.registerApruveCallback(this.apruve.APRUVE_COMPLETE_EVENT, _.bind(this.handleApruveComplete, this));
            this.apruve.registerApruveCallback(this.apruve.APRUVE_CLOSED_EVENT, _.bind(this.handleApruveClose, this));

            mediator.on('checkout:place-order:response', this.handleSubmit, this);
        },

        /**
         * @param {Object} eventData
         */
        handleSubmit: function(eventData) {
            if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
                eventData.stopped = true;

                var responseData = _.extend({successUrl: this.getSuccessUrl()}, eventData.responseData);

                if (!responseData.apruveOrder) {
                    return;
                }

                console.log(responseData);

                this.returnUrl = responseData.returnUrl;
                this.errorUrl = responseData.errorUrl;

                // Provide order object and secure hash to Apruve.
                this.apruve.setOrder(responseData.apruveOrder, responseData.apruveOrderSecureHash);

                mediator.execute('hideLoading');
                this.apruve.startCheckout();
            }
        },

        /**
         * @param {String} orderId
         */
        handleApruveComplete: function (orderId) {
            mediator.execute('showLoading');
            mediator.execute('redirectTo', {url: this.returnUrl + '?' + this.options.orderIdParamName + '=' + orderId}, {redirect: true});
        },

        handleApruveClose: function () {
            mediator.execute('showLoading');
            mediator.execute('redirectTo', {url: this.errorUrl}, {redirect: true});
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout:place-order:response', this.handleSubmit, this);

            PaymentMethodComponent.__super__.dispose.call(this);
        }
    });

    return PaymentMethodComponent;
});
