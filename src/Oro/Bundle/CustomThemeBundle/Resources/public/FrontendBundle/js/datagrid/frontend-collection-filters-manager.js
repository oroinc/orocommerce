define(function(require) {
    'use strict';

    var FrontendCollectionFiltersManager;
    var CollectionFiltersManager = require('orofrontend/js/app/datafilter/frontend-collection-filters-manager');
    var viewportManager = require('oroui/js/viewport-manager');

    FrontendCollectionFiltersManager = CollectionFiltersManager.extend({
        /**
         * @inheritDoc
         * @private
         */
        _updateRenderMode: function() {
            switch (viewportManager.getViewport().type) {
                case 'tablet':
                case 'tablet-small':
                    this.renderMode = 'collapse-mode';
                    this.renderMode = '';
                    break;
                case 'mobile-landscape':
                case 'mobile':
                    this.renderMode = 'toggle-mode';
                    break;
            }
        }
    });

    return FrontendCollectionFiltersManager;
});
