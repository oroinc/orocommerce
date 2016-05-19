define([
    'oroui/js/content-processor/pinned-dropdown-button',
    'underscore',
    'oroui/js/mediator',
    'oroui/js/tools',
    'oroui/js/app/components/base/component-container-mixin'
], function($, _, mediator, tools, componentContainerMixin) {
    'use strict';

    $.widget(
        'oroui.productAddToDropdownButtonProcessor',
        $.oroui.pinnedDropdownButtonProcessor,
        _.extend(componentContainerMixin, {
            keyPreffix: 'product-add-to-dropdown-button-processor-',

            modules: [],

            getLayoutElement: function() {
                return this.element;
            },

            _create: function() {
                var args = arguments;
                var _super = this._super;
                this.initPageComponents({
                    dropdownWidget: this,
                    productModel: this.options.productModel
                }).done(_.bind(function(modules) {
                    this.modules = modules;
                    _super.apply(this, args);
                }, this));
            },

            _destroy: function() {
                delete this.modules;
                this.disposePageComponents();
                this._super();
            },

            _renderButtons: function() {
                this._super.apply(this, arguments);
                _.each(this.modules, function(module) {
                    if (_.isFunction(module._afterRenderButtons)) {
                        module._afterRenderButtons();
                    }
                });
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
        })
    );

    return $;
});
