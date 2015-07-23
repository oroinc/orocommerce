/*jslint nomen:true*/
/*global define*/
define([
    'oro/datagrid/action/dialog-action'
], function(DialogAction) {
    'use strict';

    var RequestChangeStatusDialogAction;

    /**
     * @export oro/datagrid/action/request-change-status-dialog-action
     * @class oro.datagrid.action.RequestChangeStatusDialogAction
     * @extends oro.datagrid.action.DialogAction
     */
    RequestChangeStatusDialogAction = DialogAction.extend();

    return RequestChangeStatusDialogAction;
});
