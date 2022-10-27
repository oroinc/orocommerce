define(function(require) {
    'use strict';

    const _ = require('underscore');
    const messenger = require('oroui/js/messenger');
    const BaseTreeManageView = require('oroui/js/app/views/jstree/base-tree-manage-view');

    /**
     * @export orocatalog/js/app/views/tree-manage-view
     * @extends oroui.app.components.BaseTreeManageView
     * @class orocatalog.app.components.TreeManageView
     */
    const TreeManageView = BaseTreeManageView.extend({
        /**
         * @inheritdoc
         */
        constructor: function TreeManageView(options) {
            TreeManageView.__super__.constructor.call(this, options);
        },

        /**
         * Triggers after page move
         *
         * @param {Object} e
         * @param {Object} data
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            if (data.parent === '#') {
                this.rollback(data);
                messenger.notificationFlashMessage('warning', _.__('oro.catalog.jstree.add_new_root_warning'));
                return;
            }

            TreeManageView.__super__.onMove.call(this, e, data);
        }
    });

    return TreeManageView;
});
