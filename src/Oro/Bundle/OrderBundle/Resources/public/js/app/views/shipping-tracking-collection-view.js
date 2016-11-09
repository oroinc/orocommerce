define(function(require) {
    'use strict';

    var ShippingTrackingCollectionView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroorder/js/app/views/shipping-tracking-collection-view
     * @extends oroui.app.views.base.View
     */
    ShippingTrackingCollectionView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$el
                .on('content:changed', _.bind(this.toggleTableVisibility, this))
                .on('content:remove', _.bind(this.toggleTableVisibility, this));
            this.$el.trigger('content:changed');
        },

        /**
         * Toggle Table visibility
         *
         * @param {jQuery.Event} e
         */
        toggleTableVisibility: function(e){
            var table = this.$el.find('table');
            var elements = this.$el.find('table tr[data-content*="shippingTrackings"]');

            if (elements.length < 1 || (elements.length == 1 && e.type == 'content:remove')) {
                table.hide();
            } else {
                table.show();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off('content:changed');
            this.$el.off('content:remove');

            ShippingTrackingCollectionView.__super__.dispose.call(this);
        }
    });

    return ShippingTrackingCollectionView ;
});
