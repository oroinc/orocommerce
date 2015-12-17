define(function(require) {
    'use strict';

    var SubtotalsListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ValueChangingListener = require('orob2bpricing/js/app/listener/value-changing-listener');

    /**
     * @export orob2border/js/app/listener/subtotals-listener
     * @class orob2border.app.listener.SubtotalsListener
     */
    SubtotalsListener = {
        /**
         * Listen fields change
         *
         * @param {String} event
         * @param {jQuery|Array} $fields
         *
         */
        listen: function(event, $fields) {
            ValueChangingListener.listen(event, $fields);
            _.each($fields, _.bind(function(field) {
                $(field).change(_.bind(this.updateSubtotals, this));
            }, this));
        },

        /**
         * Trigger subtotals update
         */
        updateSubtotals: function(e) {
            mediator.trigger('line-items-subtotals:update', e);
        }
    };

    return SubtotalsListener;
});
