define(function (require) {
    'use strict';

    var TotalsListener;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ValueChangingListener = require('orob2bpricing/js/app/listener/value-changing-listener');

    /**
     * @export orob2bpricing/js/app/listener/totals-listener
     * @class orob2bpricing.app.listener.TotalsListener
     */
    TotalsListener = {
        /**
         * Listen fields change
         *
         * @param {jQuery|Array} $fields
         */
        listen: function ($fields) {
            ValueChangingListener.listen('total-target:changing', $fields);
            _.each($fields, _.bind(function (field) {
                $(field).change(_.bind(this.updateTotals, this));
            }, this));
        },

        /**
         * Trigger subtotals update
         */
        updateTotals: function (e) {
            mediator.trigger('line-items-totals:update', e);
        }
    };

    return TotalsListener;
});
