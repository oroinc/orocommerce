define(function(require) {
    'use strict';

    var RedirectComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var mediator = require('oroui/js/mediator');

    RedirectComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            targetUrl: null
        },

        /**
         * {@inheritDoc}
         */
        initialize: function(options) {
            var targetUrl = options.targetUrl || null;
            this.redirectTo(targetUrl);
        },

        redirectTo: function(targetUrl) {
            if (targetUrl) {
                mediator.execute('redirectTo', {url: targetUrl}, {redirect: true});
            }
        }
    });

    return RedirectComponent;
});
