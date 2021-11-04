define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const SinglePageCheckoutSubmitButtonView = BaseView.extend({
        events: {
            mouseover: 'onHover',
            mouseout: 'onHoverOut'
        },

        isHoveredFlag: false, // Marks submit button hovered if true

        /**
         * @inheritdoc
         */
        constructor: function SinglePageCheckoutSubmitButtonView(options) {
            SinglePageCheckoutSubmitButtonView.__super__.constructor.call(this, options);
        },

        onToggleState: function() {
            this.$el.prop('disabled', 'disabled');
        },

        onEnableState: function() {
            this.$el.prop('disabled', false);
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
