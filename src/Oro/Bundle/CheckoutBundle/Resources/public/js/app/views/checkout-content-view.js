import _ from 'underscore';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';

import 'jquery.cookie';

const CheckoutContentView = BaseView.extend({
    /**
     * @inheritdoc
     */
    constructor: function CheckoutContentView(options) {
        CheckoutContentView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function() {
        mediator.on('checkout-content:updated', this._onContentUpdated, this);
        mediator.on('checkout-content:before-update', this._onBeforeContentUpdate, this);
        this.initTabs();
        this._onContentUpdated();
    },

    _onContentUpdated: function() {
        this.initLayout();
    },

    _onBeforeContentUpdate: function() {
        this.disposePageComponents();
    },

    initTabs: function() {
        const cookieName = 'order-tab:state';
        const $container = this.$el;

        $container.on('collapse:toggle', '[data-collapse-trigger]', function(event, params) {
            mediator.trigger('scrollable-table:reload');

            if (!_.isObject(params)) {
                return;
            }

            if (params.isOpen) {
                $.cookie(cookieName, true, {path: window.location.pathname});
            } else {
                $.cookie(cookieName, null, {path: window.location.pathname});
            }
        });
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        mediator.off('checkout-content:updated', this._onContentUpdated, this);
        mediator.off('checkout-content:before-update', this._onBeforeContentUpdate, this);

        CheckoutContentView.__super__.dispose.call(this);
    }
});

export default CheckoutContentView;
