define(function(require) {
    'use strict';

    var CheckoutContentView;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    require('jquery.cookie');

    CheckoutContentView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function() {
            mediator.on('checkout-content:updated', this._onContentUpdated, this);
            mediator.on('checkout-content:before-update', this._onBeforeContentUpdate, this);
            this.initTabs();
        },

        _onContentUpdated: function() {
            this.initLayout();
        },

        _onBeforeContentUpdate: function() {
            this.disposePageComponents();
        },

        initTabs: function() {
            var cookieName = 'order-tab:state';
            var $container = this.$el;

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
         * @inheritDoc
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
