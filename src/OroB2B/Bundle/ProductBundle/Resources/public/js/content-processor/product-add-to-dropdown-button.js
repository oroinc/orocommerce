define([
    'oroui/js/content-processor/pinned-dropdown-button',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/tools'
], function($, _, mediator, tools) {
    'use strict';

    $.widget('oroui.productAddToDropdownButtonProcessor', $.oroui.pinnedDropdownButtonProcessor, {
        keyPrefix: 'product-add-to-dropdown-button-processor-',

        model: null,

        handlers: null,

        _create: function() {
            this.model = this.options.productModel;
            this.handlers = {};
            this.element.on('click', '[data-product-add-to-button]', _.bind(this.onClick, this));
            this._super();
        },

        _destroy: function() {
            delete this.model;
            delete this.handlers;
            this._super();
        },

        _renderButtons: function() {
            var args = arguments;
            var _super = this._super;
            this.initButtons(_.bind(function() {
                _super.apply(this, args);
            }, this));
        },

        initButtons: function(callback) {
            var loadHandlers = {};
            var $buttons = this.element.find('[data-product-add-to-button]');
            $buttons.each(_.bind(function(i, button) {
                var $button = $(button);
                var handler = $button.data('product-add-to-button');
                $button.removeData('product-add-to-button')
                    .data('product-add-to-handler', handler);
                if (!this.handlers[handler]) {
                    this.handlers[handler] = loadHandlers[handler] = handler;
                }
            }, this));

            tools.loadModules(loadHandlers, function() {
                _.extend(this.handlers, loadHandlers);

                $buttons.each(_.bind(function(i, button) {
                    var $button = $(button);
                    var handler = $button.data('product-add-to-handler');
                    $button.data('product-add-to-handler', this.handlers[handler]);
                }, this));

                callback();
            }, this);
        },

        onClick: function(e) {
            var $button = $(e.currentTarget);
            var buttonHandler = $button.data('product-add-to-handler');
            if (!buttonHandler) {
                return;
            }

            buttonHandler.onClick(this, $button);
        }
    });

    return $;
});
