define(function(require) {
    'use strict';

    var LineItemPricesListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    /**
     * @export orob2border/js/app/listener/line-item-prices-listener
     * @class orob2border.app.listener.LineItemPricesListener
     */
    LineItemPricesListener = {
        /**
         * Listen fields change
         *
         * @param {jQuery|Array} $fields
         */
        listen: function($fields) {
            var self = this;
            _.each($fields, function(field) {
                $(field).change(_.bind(self.updatePrices, self));
            });
        },

        /**
         * Trigger prices update
         */
        updatePrices: function() {
            mediator.trigger('order-line-item-prices:update');
        }
    };

    return LineItemPricesListener;
});
