define(function(require) {
    'use strict';

    var PriceListScheduleView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    PriceListScheduleView = BaseView.extend({
        options: {
            selectors: {
                'row': '[data-role="schedule-row"]',
                'rowError': '[data-role="schedule-row-error"]'
            }
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.delegate('change content:remove', this._handleErrors);
        },

        _handleErrors: function() {
            this.$(this.options.selectors.rowError).remove();
            this.$(this.options.selectors.row).removeClass('has-row-error');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.undelegate('change content:remove', this._handleErrors);
        }
    });

    return PriceListScheduleView;
});
