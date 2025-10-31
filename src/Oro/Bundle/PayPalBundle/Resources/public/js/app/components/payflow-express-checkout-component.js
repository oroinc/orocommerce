import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';

const PayflowExpressCheckoutComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        paymentMethod: null
    },

    /**
     * @inheritdoc
     */
    constructor: function PayflowExpressCheckoutComponent(options) {
        PayflowExpressCheckoutComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.extend({}, this.options, options);

        mediator.on('checkout:place-order:response', this.handleSubmit, this);
    },

    /**
     * @param {Object} eventData
     */
    handleSubmit: function(eventData) {
        if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
            eventData.stopped = true;
            if (!eventData.responseData.purchaseRedirectUrl) {
                mediator.execute('redirectTo', {url: eventData.responseData.errorUrl}, {redirect: true});
                return;
            }

            window.location = eventData.responseData.purchaseRedirectUrl;
        }
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        mediator.off('checkout:place-order:response', this.handleSubmit, this);

        PayflowExpressCheckoutComponent.__super__.dispose.call(this);
    }
});

export default PayflowExpressCheckoutComponent;
