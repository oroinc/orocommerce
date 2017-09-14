define(function(require) {
    'use strict';

    var FrontendCustomFiltersTogglePlugin;

    var FullScreenFiltersAction = require('orofrontend/js/app/datafilter/actions/fullscreen-filters-action');
    var FrontendFiltersTogglePlugin = require('orofrontend/js/app/datafilter/plugins/frontend-filters-plugin');

    FrontendCustomFiltersTogglePlugin = FrontendFiltersTogglePlugin.extend({
        /**
         * Object
         */
        filtersActions: {
            'mobile-landscape': FullScreenFiltersAction
        }
    });
    return FrontendCustomFiltersTogglePlugin;
});
