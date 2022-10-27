define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    require('jquery.cookie');

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

    return CheckoutContentView;
});
