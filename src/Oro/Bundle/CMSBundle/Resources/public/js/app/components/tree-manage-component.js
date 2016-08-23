define(function(require) {
    'use strict';

    var TreeManageComponent;
    var __ = require('orotranslation/js/translator');
    var messenger = require('oroui/js/messenger');
    var BasicTreeManageComponent = require('oroui/js/app/components/basic-tree-manage-component');

    /**
     * @export orocms/js/app/components/tree-manage-component
     * @extends oroui.app.components.BasicTreeManageComponent
     * @class orocms.app.components.TreeManageComponent
     */
    TreeManageComponent = BasicTreeManageComponent.extend({
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

            if (data.parent === '#' && data.old_parent === '#') {
                this.rollback(data);
                messenger.notificationFlashMessage('warning', __("oro.cms.jstree.move_root_page_warning"));
                return;
            }

            TreeManageComponent.__super__.onMove.call(this, e, data);
        }
    });

    return TreeManageComponent;
});
