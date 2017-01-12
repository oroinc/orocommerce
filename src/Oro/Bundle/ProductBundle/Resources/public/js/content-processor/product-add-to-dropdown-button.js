define([
    'oroui/js/content-processor/pinned-dropdown-button',
    'underscore',
    'oroui/js/mediator'
], function($, _, mediator) {
    'use strict';

    $.widget(
        'oroui.productAddToDropdownButtonProcessor',
        $.oroui.pinnedDropdownButtonProcessor,
        {
            keyPreffix: 'product-add-to-dropdown-button-processor-',

            modules: [],

            getLayoutElement: function() {
                return this.element;
            },

            _create: function() {
                var args = arguments;
                var _super = this._super;

                mediator.on('shopping-list-view:init:' + this.options.productModel.get('id'), function(obj) {
                    this.shoppingListView = obj.context;
                    if (_.isFunction(obj.callback)) {
                        obj.callback.call(this.shoppingListView, {dropdownWidget: this});
                    }

                    _super.apply(this, args);
                }, this);
            },

            _destroy: function() {
                delete this.shoppingListView;
                this._super();
            },

            _renderButtons: function() {
                this._super.apply(this, arguments);
                if (this.shoppingListView) {
                    this.shoppingListView._afterRenderButtons();
                }
            },

            _moreButton: function() {
                var $button = this._super();

                if (this.options.appendToBody === true) {
                    $button.data('container', 'body');
                }

                return $button;
            },

            _prepareMainButton: function($button) {
                var $mainButton = this._super($button);
                $mainButton.data('clone', $button);
                return $mainButton;
            },

            validateForm: function() {
                var $form = $(this.element).closest('form');

                return $form.data('validator') ? $form.valid() : true;
            }
        }
    );

    return $;
});
