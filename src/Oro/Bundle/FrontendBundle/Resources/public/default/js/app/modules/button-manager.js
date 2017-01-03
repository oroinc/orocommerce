define(function(require) {
    'use strict';

    var _ = require('underscore');
    var ButtonManager = require('oroaction/js/button-manager');

    _.extend(ButtonManager.prototype, {
        confirmComponent: 'orofrontend/js/app/components/delete-confirmation'
    });
});
