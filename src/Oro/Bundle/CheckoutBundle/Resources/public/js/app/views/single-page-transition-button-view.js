define(function(require) {
    'use strict';

    var SinglePageTransitionButtonView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');

    /**
     * This view makes able to reload button block without disposing related single-page-transition-button-component.
     */
    SinglePageTransitionButtonView = BaseView.extend({
        defaults: {
            enabled: false
        },

        /**
         * @inheritDoc
         */
        constructor: function SinglePageTransitionButtonView() {
            SinglePageTransitionButtonView.__super__.constructor.apply(this, arguments);
        },

        initialize: function(options) {
            SinglePageTransitionButtonView.__super__.initialize.apply(this, arguments);
            this.options = $.extend(true, {}, this.defaults, options);

            this.$el.on('click', function() {
                mediator.trigger('single-page:transition-button:submit');
            });

            mediator.on('checkout:transition-button:enable', this.onEnable, this);
            mediator.on('checkout:transition-button:disable', this.onDisable, this);

            mediator.trigger('single-page:transition-button:initialized');
        },

        onEnable: function() {
            if (this.options.enabled) {
                this.$el.prop('disabled', false);
            }
        },

        onDisable: function() {
            this.$el.prop('disabled', 'disabled');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.off();
            mediator.off(null, null, this);
            SinglePageTransitionButtonView.__super__.dispose.call(this);
        }
    });

    return SinglePageTransitionButtonView;
});
