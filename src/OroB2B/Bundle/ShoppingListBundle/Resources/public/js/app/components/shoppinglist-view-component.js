/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');

    return BaseComponent.extend({
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            mediator.on('shopping-list:created', function() {
                mediator.execute('redirectTo', {
                    url: routing.generate('orob2b_shopping_list_frontend_view')
                }, {
                    redirect: true
                });
            });
        }
    });
});
