import OverlayPopupView from 'orofrontend/default/js/app/views/overlay-popup-view';

const CheckoutOverlayPopupView = OverlayPopupView.extend({
    listen: {
        'checkout-content:updated mediator': 'close'
    },

    constructor: function CheckoutOverlayPopupView(options) {
        CheckoutOverlayPopupView.__super__.constructor.call(this, options);
    }
});

export default CheckoutOverlayPopupView;
