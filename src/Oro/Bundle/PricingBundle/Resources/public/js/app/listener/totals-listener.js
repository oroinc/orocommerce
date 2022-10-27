define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const ValueChangingListener = require('oropricing/js/app/listener/value-changing-listener');

    /**
     * @export oropricing/js/app/listener/totals-listener
     * @class oropricing.app.listener.TotalsListener
     */
    const TotalsListener = {
        /**
         * Listen fields change
         *
         * @param {jQuery|Array} $fields
         */
        listen: function($fields) {
            ValueChangingListener.listen('total-target:changing', $fields);
            _.each($fields, field => {
                $(field).change(this.updateTotals.bind(this));
            });
        },

        /**
         * Trigger subtotals update
         */
        updateTotals: function(e) {
            mediator.trigger('line-items-totals:update', e);
        }
    };

    return TotalsListener;
});
