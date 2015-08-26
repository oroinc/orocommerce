define(function(require) {
    'use strict';

    var SubtotalsListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    /**
     * @export orob2border/js/app/listener/subtotals-listener
     * @class orob2border.app.listener.SubtotalsListener
     */
    SubtotalsListener = {
        /**
         * Listen fields change
         *
         * @param {jQuery|Array} $fields
         */
        listen: function($fields) {
            var self = this;
            _.each($fields, function(field) {
                $(field).change(_.bind(self.updateSubtotals, self));
            });
        },

        /**
         * Trigger subtotals update
         */
        updateSubtotals: function() {
            mediator.trigger('order-subtotals:update');
        }
    };

    return SubtotalsListener;
});
