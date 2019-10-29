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
         * @property {Boolean}
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
        _initializeSelectWidget: function() {
            if (!this.enableMultiselectWidget) {
                this.$(this.filterSelector).hide();
                return;
            }
            FrontendCollectionFiltersManager.__super__._initializeSelectWidget.call(this);
        },

        /**
         * @inheritDoc
         */
        _refreshSelectWidget: function() {
            if (this.enableMultiselectWidget) {
                FrontendCollectionFiltersManager.__super__._refreshSelectWidget.call(this);
            }
        },

        /**
         * @inheritDoc
         */
        _onChangeFilterSelect: function(filters) {
            if (this.enableMultiselectWidget) {
                FrontendCollectionFiltersManager.__super__._onChangeFilterSelect.call(this, filters);
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
