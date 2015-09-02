define(function(require) {
    'use strict';

    var SubtotalsListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ValueChangingListener = require('orob2border/js/app/listener/value-changing-listener');

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
            ValueChangingListener.listen('order:changing', $fields);
            _.each($fields, _.bind(function(field) {
                $(field).change(_.bind(this.updateSubtotals, this));
            }, this));
        },

        /**
         * Trigger subtotals update
         */
        updateSubtotals: function(e) {
            mediator.trigger('order-subtotals:update', e);
        }
    };

    return SubtotalsListener;
});
