define(function (require) {
    'use strict';

    var SubtotalsListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ValueChangingListener = require('orob2bpricing/js/app/listener/value-changing-listener');

    /**
     * @export orob2bpricing/js/app/listener/subtotals-listener
     * @class orob2bpricing.app.listener.SubtotalsListener
     */
    SubtotalsListener = {
        /**
         * Listen fields change
         *
         * @param {jQuery|Array} $fields
         */
        listen: function ($fields) {
            ValueChangingListener.listen('subtotal-target:changing', $fields);
            _.each($fields, _.bind(function (field) {
                $(field).change(_.bind(this.updateSubtotals, this));
            }, this));
        },

        /**
         * Trigger subtotals update
         */
        updateSubtotals: function (e) {
            mediator.trigger('line-items-subtotals:update', e);
        }
    };

    return SubtotalsListener;
});
