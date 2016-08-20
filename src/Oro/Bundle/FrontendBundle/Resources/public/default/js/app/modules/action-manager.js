define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DeleteConfirmation = require('orofrontend/js/app/components/delete-confirmation');
    var ActionManager = require('oroaction/js/action-manager');

    _.extend(ActionManager.prototype, {
        confirmComponent: 'orofrontend/js/app/components/delete-confirmation'
    });
});
