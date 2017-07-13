define(function(require) {
    'use strict';

    var SinglePageCHeckoutView;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/views/base/view');

    SinglePageCHeckoutView = BaseComponent.extend({
        initialize: function() {
            mediator.trigger('checkout:transition-button:enable');
        }
    });

    return SinglePageCHeckoutView;
});
