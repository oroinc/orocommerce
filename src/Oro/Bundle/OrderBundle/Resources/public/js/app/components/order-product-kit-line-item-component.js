import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseComponent from 'oroui/js/app/components/base/component';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';

const OrderProductKitLineItemComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        // Required to find the related data in an order entry point response.
        fullName: ''
    },

    /**
     * @property {LoadingMaskView}
     */
    loadingMaskView: null,

    /**
     * @inheritdoc
     */
    constructor: function OrderProductKitLineItemComponent(options) {
        OrderProductKitLineItemComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.listenTo(mediator, {
            'entry-point:order:load:before': this.showLoadingMask,
            'entry-point:order:load': this.onOrderEntryPoint,
            'entry-point:order:load:after': this.hideLoadingMask
        });
        this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});
    },

    showLoadingMask: function() {
        this.loadingMaskView.show();
    },

    hideLoadingMask: function() {
        this.loadingMaskView.hide();
    },

    /**
     * @param {Object} response
     */
    onOrderEntryPoint: function(response) {
        this.options._sourceElement
            .html(response.kitItemLineItems[this.options.fullName] || '');
    }
});

export default OrderProductKitLineItemComponent;
