define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const CollectionFiltersManager = require('orofrontend/js/app/datafilter/frontend-collection-filters-manager');
    const viewportManager = require('oroui/js/viewport-manager');
    let config = require('module-config').default(module.id);
    config = _.extend({
        enableMultiselectWidget: true
    }, config);

    const FrontendCollectionFiltersManager = CollectionFiltersManager.extend({
        /**
         * @inheritDoc
         */
        enableMultiselectWidget: config.enableMultiselectWidget,

        /**
         * @inheritDoc
         */
        _updateRenderMode: function() {
            if (viewportManager.isApplicable({
                screenType: ['strict-tablet', 'strict-tablet-small']
            })) {
                this.renderMode = 'collapse-mode';
            }

            if (viewportManager.isApplicable({
                screenType: 'mobile-landscape'
            })) {
                this.renderMode = 'toggle-mode';
            }
        },

        /**
         * @inheritDoc
         */
        isFiltersStateViewNeeded: function(options) {
            return false;
        }
    });

    return FrontendCollectionFiltersManager;
});
