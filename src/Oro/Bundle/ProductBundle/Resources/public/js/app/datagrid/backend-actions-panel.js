define(function(require) {
    'use strict';

    var BackendActionsPanel;
    var _ = require('underscore');
    var ActionsPanel = require('orodatagrid/js/datagrid/actions-panel');

    BackendActionsPanel = ActionsPanel.extend({
        /**
         * Renders panel
         *
         * @return {*}
         */
        render: function() {
            _.each(this.launchers, function(launcher) {
                var $el = this.findContainer(launcher);
                this.addExtraClass(launcher);
                $el.append(launcher.render().$el);
                this.wrapLauncher(launcher);
            }, this);
            return this;
        },

        /**
         * @param {Object} launcher
         */
        findContainer: function(launcher) {
            var $el = this.$el;

            if (this.massActionsOnSticky && _.isObject(launcher)) {
                $el = this.$el.find(launcher.action.is_current ?
                    '[data-action-extra-panel]' :
                    '[data-action-main-panel]'
                );
            }

            return $el;
        },

        /**
         * @param {Object} launcher
         */
        addExtraClass: function(launcher) {
            if (this.massActionsOnSticky && launcher.action.is_current) {
                launcher.className = 'datagrid-massaction__action-trigger';
            }
        },

        /**
         * @param {Object} launcher
         */
        wrapLauncher: function(launcher) {
            if (!(this.massActionsOnSticky && launcher.action.is_current)) {
                launcher.$el.wrap('<li/>');
            }
        }
    });

    return BackendActionsPanel;
});
