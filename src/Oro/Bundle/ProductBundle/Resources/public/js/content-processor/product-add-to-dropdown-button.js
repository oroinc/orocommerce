define(function(require) {
    'use strict';

    const $ = require('oroui/js/content-processor/pinned-dropdown-button');
    const _ = require('underscore');
    const componentContainerMixin = require('oroui/js/app/components/base/component-container-mixin');
    const BUTTONS_ORDER = require('oroproduct/js/app/buttons-order').default;

    $.widget(
        'oroui.productAddToDropdownButtonProcessor',
        $.oroui.pinnedDropdownButtonProcessor,
        _.extend(componentContainerMixin, {
            options: {
                includeButtons: '.btn, .dropdown-menu li > a, .dropdown-search',
                excludeButtons: '.dropdown-toggle, [data-btn-processor="exclude"]',
                groupList: true,
                sortedList: true,
                moreButtonAttrs: {
                    'data-inherit-parent-width': 'strictly'
                }
            },

            keyPreffix: 'product-add-to-dropdown-button-processor-',

            modules: [],

            getLayoutElement: function() {
                return this.element;
            },

            _create: function(...args) {
                const modules = this.modules = [];
                $(this.element).trigger('deferredInitialize', {
                    dropdownWidget: this,
                    productModel: this.options.productModel,
                    callback: function(module) {
                        modules.push(module);
                    }
                });
                $(this.element).attr('data-product-dropdown-root', '');
                this._super(...args);
            },

            _destroy: function() {
                delete this.modules;
                this.disposePageComponents();
                this._super();
            },

            _renderButtons: function(...args) {
                this._super(...args);
                _.each(this.modules, function(module) {
                    if (_.isFunction(module._afterRenderButtons)) {
                        module._afterRenderButtons();
                    } else if (module.view && _.isFunction(module.view._afterRenderButtons)) {
                        module.view._afterRenderButtons();
                    }
                });

                if (this.$buttons.filter(':not(.dropdown-search)').length <= 1) {
                    this._removeDropdownMenu();
                }
            },

            _moreButton: function() {
                const $button = this._super();

                if (this.options.appendToBody === true) {
                    $button.data('container', 'body');
                }

                return $button;
            },

            _collectButtons: function($element) {
                this.$buttons = this._super($element);
                this.$buttons.each(function(i) {
                    const intention = !$(this).hasClass('direct-link')
                        ? $(this).data('intention') || 'add'
                        : 'direct';
                    $(this).attr('data-button-index', '')
                        .data('order', BUTTONS_ORDER[intention])
                        .data('button-index', i);
                });

                return this.$buttons;
            },

            _dropdownMenu($buttons) {
                if (!this.options.groupList) {
                    return this._super($buttons);
                }

                const $dropdown = $('<div></div>', {
                    'class': 'dropdown-menu',
                    'aria-labelledby': this._togglerId
                }).append(this._prepareButtons($buttons));

                $dropdown.find('.action-update, .action-remove, .action-new, .action-important')
                    .wrapAll('<ul class="items-group" role="menu"></ul>');
                $dropdown.find('li.action-add').wrapAll('<ul class="items-group" role="menu"></ul>');
                $dropdown.find('.items-group').wrapAll('<div class="item-container"></div>');

                return $dropdown;
            },

            _mainButtons: function($buttons) {
                const result = $buttons.filter(':not(div.dropdown-search)').get(0);

                return result ? $(result) : this._super($buttons);
            },

            _prepareMainButton: function($button) {
                const $mainButton = this._super($button);
                $mainButton.data('clone', $button);
                $mainButton.find('.fa').remove();
                return $mainButton;
            },

            validateForm: function() {
                const $form = $(this.element).closest('form');

                return $form.data('validator') ? $form.valid() : true;
            },

            _prepareButtons($buttons) {
                $buttons.addClass('dropdown-item');

                const $items = $buttons.filter('.btn')
                    .removeClass(function(index, css) {
                        return (css.match(/\bbtn(-\S+)?/g) || []).join(' ');
                    }).wrap(function() {
                        const intention = $(this).data('intention') || 'add';
                        if (intention === 'search') {
                            return `<div class="action-${intention}"></div>`;
                        }

                        return `<li role="menuitem" class="action-${intention}"></li>`;
                    }).parent();

                const $sorted = $items.toArray().sort((a, b) => {
                    return $(a).children().data('order') < $(b).children().data('order') ? -1 : 1;
                });

                return $($sorted);
            }
        })
    );

    return $;
});
