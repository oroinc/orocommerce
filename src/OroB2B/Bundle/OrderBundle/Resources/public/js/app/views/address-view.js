define(function(require) {
    'use strict';

    var AddressView;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var SubtotalsListener = require('orob2border/js/app/listener/subtotals-listener');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2border/js/app/views/address-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.AddressView
     */
    AddressView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            type: '',
            selectors: {
                address: '',
                subtotalsFields: []
            }
        },

        /**
         * @property {jQuery}
         */
        $fields: null,

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, this.options, options || {});

            this.initLayout().done(_.bind(this.handleLayoutInit, this));

            this.loadingMaskView = new LoadingMaskView({container: this.$el});

            mediator.on('order:load:related-data', this.loadingStart, this);
            mediator.on('order:loaded:related-data', this.loadedRelatedData, this);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$address = this.$el.find(this.options.selectors.address);

            if (this.options.selectors.subtotalsFields.length > 0) {
                SubtotalsListener.listen(this.$el.find(this.options.selectors.subtotalsFields.join(', ')));
            }
        },

        /**
         * Show loading view
         */
        loadingStart: function() {
            this.loadingMaskView.show();
        },

        /**
         * Hide loading view
         */
        loadingEnd: function() {
            this.loadingMaskView.hide();
        },

        /**
         * Set account address choices from order related data
         *
         * @param {Object} response
         */
        loadedRelatedData: function(response) {
            var address = response[this.options.type + 'Address'] || null;
            if (!address) {
                this.loadingEnd();
                return;
            }

            var $oldAddress = this.$address;
            this.$address = $(address);

            $oldAddress.parent().trigger('content:remove');
            $oldAddress.select2('destroy')
                .replaceWith(this.$address);

            this.initLayout().done(_.bind(this.loadingEnd, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:load:related-data', this.loadingStart, this);
            mediator.off('order:loaded:related-data', this.loadedRelatedData, this);

            AddressView.__super__.dispose.call(this);
        }
    });

    return AddressView;
});
