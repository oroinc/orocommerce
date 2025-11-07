import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import ValueChangingListener from 'oropricing/js/app/listener/value-changing-listener';

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
            $(field).on('change', this.updateTotals.bind(this));
        });
    },

    /**
     * Trigger subtotals update
     */
    updateTotals: function(e) {
        mediator.trigger('line-items-totals:update', e);
    }
};

export default TotalsListener;
