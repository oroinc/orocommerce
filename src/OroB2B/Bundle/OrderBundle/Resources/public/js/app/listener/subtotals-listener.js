define(function(require) {
    'use strict';

    var SubtotalsListener;
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
         * @param {jQuery} $fields
         */
        listen: function($fields) {
            $fields.change(_.bind(this.updateSubtotals, this));
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
