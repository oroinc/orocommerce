define([
    'oroui/js/app/views/base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var FrontendHistoryView;

    FrontendHistoryView = PageRegionView.extend({
        template: function(data) {
            return data.frontend_history;
        },
        pageItems: ['frontend_history'],

        render: function() {
            // does not update view if data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return this;
            }

            return FrontendHistoryView.__super__.render.call(this);
        }
    });

    return FrontendHistoryView;
});
