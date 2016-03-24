define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DeleteConfirmation = require('orob2bfrontend/js/app/components/delete-confirmation');
    var ActionManager = require('oroaction/js/action-manager');

    _.extend(ActionManager.prototype, {
        confirmComponent: 'orob2bfrontend/js/app/components/delete-confirmation'
    });
});
