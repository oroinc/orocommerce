define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DeleteConfirmation = require('orofrontend/js/app/components/delete-confirmation');
    var DeleteAction = require('oro/datagrid/action/delete-action');

    _.extend(DeleteAction.prototype, {
        confirmModalConstructor: DeleteConfirmation
    });
});
