define(function(require) {
    'use strict';

    var FrontendCustomFiltersTogglePlugin;
    var _ = require('underscore');
    var FullScreenFiltersAction = require('orofrontend/js/app/datafilter/actions/fullscreen-filters-action');
    var FrontendFiltersTogglePlugin = require('orofrontend/js/app/datafilter/plugins/frontend-filters-plugin');
    var viewportManager = require('oroui/js/viewport-manager');

    function isApplicableWithViewport() {
        return viewportManager.isApplicable({
            screenType: 'mobile-landscape'
        });
    }

    FrontendCustomFiltersTogglePlugin = FrontendFiltersTogglePlugin.extend({
        /**
         * @inheritDoc
         */
        constructor: function FrontendCustomFiltersTogglePlugin() {
            FrontendCustomFiltersTogglePlugin.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(main, options) {
            if (this.changeBehavior()) {
                this.filtersActions = {
                    'mobile-landscape': FullScreenFiltersAction
                };
            }

            FrontendFiltersTogglePlugin.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
