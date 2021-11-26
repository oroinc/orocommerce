define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const CollectionFiltersManager = require('orofrontend/js/app/datafilter/frontend-collection-filters-manager');
    let config = require('module-config').default(module.id);
    config = _.extend({
        enableMultiselectWidget: true
    }, config);

    const FrontendCollectionFiltersManager = CollectionFiltersManager.extend({
        /**
         * @inheritdoc
         */
        enableMultiselectWidget: config.enableMultiselectWidget,

        preinitialize(options) {
            // Launcher might be hidden by default so by default Filter Manager should be open
            options.defaultFiltersViewMode = CollectionFiltersManager.MANAGE_VIEW_MODE;
        },

        /**
         * @inheritdoc
         */
        isFiltersStateViewNeeded: function(options) {
            return false;
        }
    });

    return FrontendCollectionFiltersManager;
});
