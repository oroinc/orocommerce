define(function(require) {
    'use strict';

    var CheckoutContentView;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');

    CheckoutContentView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function() {
            mediator.on('checkout-content:updated', _.bind(this._onContentUpdated, this));
            mediator.on('checkout-content:before-update', _.bind(this._onBeforeContentUpdate, this));
        },

        _onContentUpdated: function() {
            this.initLayout();
        },

        _onBeforeContentUpdate: function() {
            this.disposePageComponents();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('checkout-content:updated', _.bind(this._onContentUpdated, this));
            mediator.off('checkout-content:before-update', _.bind(this._onBeforeContentUpdate, this));

            CheckoutContentView.__super__.dispose.call(this);
        }
    });

    return CheckoutContentView;
});