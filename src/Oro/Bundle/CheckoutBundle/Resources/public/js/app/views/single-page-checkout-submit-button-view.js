define(function(require) {
    'use strict';

    var SinglePageCheckoutSubmitButtonView;
    var BaseComponent = require('oroui/js/app/views/base/view');

    SinglePageCheckoutSubmitButtonView = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutSubmitButtonView() {
            SinglePageCheckoutSubmitButtonView.__super__.constructor.apply(this, arguments);
        },

        onToggleState: function() {
            this.$el.prop('disabled', 'disabled');
        }
    });

    return SinglePageCheckoutSubmitButtonView;
});
