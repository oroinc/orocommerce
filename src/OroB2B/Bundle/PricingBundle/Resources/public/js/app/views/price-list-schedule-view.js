define(function(require) {
    'use strict';

    var PriceListScheduleView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');
    var $ = require('jquery');

    PriceListScheduleView = BaseView.extend({
        options: {
            selectors: {
                'row': '[data-role="schedule-row"]',
                'rowError': '[data-role="schedule-row-error"]'
            }
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el = options.el;
            this.$el.on('change content:remove', _.bind(this._handleErrors, this));
        },

        _handleErrors: function () {
            this.$el.find(this.options.selectors.rowError).remove();
            this.$el.find(this.options.selectors.row).removeClass("has-row-error")
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.off('change content:remove', _.bind(this._handleErrors, this));
        }
    });

    return PriceListScheduleView;
});
