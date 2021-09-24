define(function(require) {
    'use strict';

    const _ = require('underscore');
    const FullScreenFiltersAction = require('orofrontend/js/app/datafilter/actions/fullscreen-filters-action');
    const FrontendFiltersTogglePlugin = require('orofrontend/js/app/datafilter/plugins/frontend-filters-plugin');
    const viewportManager = require('oroui/js/viewport-manager');

    function isApplicableWithViewport() {
        return viewportManager.isApplicable({
            screenType: 'mobile-landscape'
        });
    }

    const FrontendCustomFiltersTogglePlugin = FrontendFiltersTogglePlugin.extend({
        /**
         * @inheritdoc
         */
        constructor: function FrontendCustomFiltersTogglePlugin(main, options) {
            FrontendCustomFiltersTogglePlugin.__super__.constructor.call(this, main, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(main, options) {
            if (this.changeBehavior()) {
                this.filtersActions = {
                    'mobile-landscape': FullScreenFiltersAction
                };
            }

            FrontendFiltersTogglePlugin.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        enable: function() {
            if (!this.changeBehavior() || isApplicableWithViewport()) {
                FrontendFiltersTogglePlugin.__super__.enable.call(this);
            } else {
                this.disable();
            }
        },

        changeBehavior: function() {
            return !_.isUndefined(this.main.$el.parent().attr('data-server-render'));
        }
    }, {
        isApplicable: function(options) {
            return options.metadata.options.frontend !== true || isApplicableWithViewport();
        }
    });
    return FrontendCustomFiltersTogglePlugin;
});
