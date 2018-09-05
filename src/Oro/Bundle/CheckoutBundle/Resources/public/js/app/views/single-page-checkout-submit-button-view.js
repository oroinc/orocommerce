define(function(require) {
    'use strict';

    var SinglePageCheckoutSubmitButtonView;
    var BaseView = require('oroui/js/app/views/base/view');

    SinglePageCheckoutSubmitButtonView = BaseView.extend({
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
