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
            var currentLauncherIsPresent = !!_.filter(this.launchers, function(launcher) {
                return launcher.action.is_current === true;
            }).length;

            _.each(this.launchers, function(launcher, index) {
                var $el = null;

                if (currentLauncherIsPresent) {
                    $el = this.findContainer(launcher, launcher.action.is_current);
                } else {
                    $el = this.findContainer(launcher, !index);
                }

                $el.append(launcher.render().$el);
            }, this);
            return this;
        },

        /**
         * @param {Object} launcher
         * @param {Boolean} pasteToExtraPanel
         */
        findContainer: function(launcher, pasteToExtraPanel) {
            var $el = this.$el;

            if (this.massActionsInSticky) {
                if (pasteToExtraPanel) {
                    $el = this.$el.find('[data-action-extra-panel]');
                    launcher.className = 'datagrid-massaction__action-trigger';
                } else {
                    $el = this.$el.find('[data-action-main-panel]');
                }
            }

            return $el;
        }
    });

    return BackendActionsPanel;
});
