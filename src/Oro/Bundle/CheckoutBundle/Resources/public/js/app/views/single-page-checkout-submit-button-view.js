define(function(require) {
    'use strict';

    var SinglePageCheckoutSubmitButtonView;
    var BaseView = require('oroui/js/app/views/base/view');

    SinglePageCheckoutSubmitButtonView = BaseView.extend({
        events: {
            mouseover: 'onHover',
            mouseout: 'onHoverOut'
        },

        isHoveredFlag: false, // Marks submit button hovered if true

        /**
         * @inheritDoc
         */
        constructor: function SinglePageCheckoutSubmitButtonView() {
            SinglePageCheckoutSubmitButtonView.__super__.constructor.apply(this, arguments);
        },

        onToggleState: function() {
            this.$el.prop('disabled', 'disabled');
        },

        onHover: function() {
            this.isHoveredFlag = true;
        },

        onHoverOut: function() {
            this.isHoveredFlag = false;
        },

        isHovered: function() {
            return this.isHoveredFlag;
        }
    });

    return SinglePageCheckoutSubmitButtonView;
});
