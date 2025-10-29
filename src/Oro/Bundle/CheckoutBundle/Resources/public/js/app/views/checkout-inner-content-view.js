import mediator from 'oroui/js/mediator';
import CheckoutContentView from 'orocheckout/js/app/views/checkout-content-view';

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

export default CheckoutInnerContentView;
