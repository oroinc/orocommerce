define(function(require) {
    'use strict';

    var SinglePageCheckoutView;
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/views/base/view');

    SinglePageCheckoutView = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutView() {
            SinglePageCheckoutView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            mediator.trigger('checkout:shipping-method:rendered');
            mediator.trigger('checkout:transition-button:enable');
        }
    });

    return SinglePageCheckoutView;
});
