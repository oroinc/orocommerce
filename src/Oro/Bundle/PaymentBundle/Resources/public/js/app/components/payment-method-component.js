import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';
import routing from 'routing';

const PaymentMethodComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        paymentMethod: null,
        successUrl: ''
    },

    /**
     * @inheritdoc
     */
    constructor: function PaymentMethodComponent(options) {
        PaymentMethodComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);

        mediator.on('checkout:place-order:response', this.handleSubmit, this);
    },

    /**
     * @param {Object} eventData
     */
    handleSubmit: function(eventData) {
        if (eventData.responseData.paymentMethod === this.options.paymentMethod) {
            eventData.stopped = true;

            const responseData = _.extend({successUrl: this.getSuccessUrl()}, eventData.responseData);

            if (!responseData.successUrl) {
                return;
            }

            mediator.execute('redirectTo', {url: responseData.successUrl}, {redirect: true});
        }
    },

    getSuccessUrl: function() {
        if (this.options.successUrl) {
            return routing.generate(this.options.successUrl);
        }

        return null;
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        mediator.off('checkout:place-order:response', this.handleSubmit, this);

        PaymentMethodComponent.__super__.dispose.call(this);
    }
});

export default PaymentMethodComponent;
