define([
    'oroui/js/app/views/base/page-region-view'
], function(PageRegionView) {
    'use strict';

    var FrontendMostViewedView;

    FrontendMostViewedView = PageRegionView.extend({
        template: function(data) {
            return data.frontend_mostviewed;
        },
        pageItems: ['frontend_mostviewed'],

        render: function() {
            // does not update view is data is from cache
            if (!this.actionArgs || this.actionArgs.options.fromCache === true) {
                return this;
            }

            return FrontendMostViewedView.__super__.render.call(this);
        }
    });

    return FrontendMostViewedView;
});
