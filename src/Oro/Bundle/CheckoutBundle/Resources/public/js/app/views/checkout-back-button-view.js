import BaseView from 'oroui/js/app/views/base/view';

const CheckoutBackButtonView = BaseView.extend({
    events: {
        click: 'onClick'
    },

    constructor: function CheckoutBackButtonView(...args) {
        CheckoutBackButtonView.__super__.constructor.apply(this, args);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        CheckoutBackButtonView.__super__.initialize.call(this, options);

        if (window.navigation.canGoBack) {
            this.el.classList.remove('hide');
        }
    },

    onClick(event) {
        event.stopPropagation();
        event.preventDefault();

        history.back();
        this.el.disabled = true;
    }
});

export default CheckoutBackButtonView;
