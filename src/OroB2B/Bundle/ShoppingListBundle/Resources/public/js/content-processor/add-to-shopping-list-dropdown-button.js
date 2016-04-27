define([
    'oroui/js/content-processor/pinned-dropdown-button',
    'underscore',
    'oroui/js/mediator'
], function($, _, mediator) {
    'use strict';

    $.widget('oroui.addToShoppingListDropdownButtonProcessor', $.oroui.pinnedDropdownButtonProcessor, {
        options: {
            intention: {
                new: 'new'
            },
            createNewLabel: 'orob2b.shoppinglist.widget.add_to_new_shopping_list',
            addToShoppingListButtonSelector: '.add-to-shopping-list-button',
            addButtonEvent: 'shopping-list:created',
            updateButtonEvent: 'shopping-list:updated',
            updateButtonLabel: true,
            buttonTemplate: '',
            buttonRemoveTemplate: '',
            defaultClass: '',
            addedClass: ''
        },

        keyPreffix: 'add-to-shopping-list-dropdown-button-processor-',

        product: null,

        remove: null,

        _create: function() {
            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
                mediator.on(this.options.addButtonEvent, this._addShoppingList, this);
                mediator.on(this.options.updateButtonEvent, this._updateShoppingList, this);
            }

            if (this.options.buttonRemoveTemplate) {
                this.options.buttonRemoveTemplate = _.template(this.options.buttonRemoveTemplate);
            }

            this.product = this.element.data('product');

            this._super();
        },

        _updateShoppingList: function(shoppingList, product) {
            if (!this.product || !product ||
                product.id !== this.product.id ||
                shoppingList.id !== this.main.data('shoppinglist').id) {
                return;
            }
            this.product.lineItems = product.lineItems;

            var $button = $(this.options.buttonTemplate(shoppingList));
            var $filteredButton = this._collectButtons($button).data('shoppinglist', shoppingList);

            var $mainButton = this._prepareMainButton($filteredButton);
            this.main.replaceWith($mainButton);
            this.main = $mainButton;

            this.dropdown.children(':first').replaceWith(this._prepareButtons($filteredButton));
            this.toggleRemoveButton();
        },

        _addShoppingList: function(shoppingList, product) {
            if (this.product) {
                product = product || {
                    id: 0,
                    lineItems: {}
                };

                if (product.id && product.id !== this.product.id) {
                    product.lineItems = {};
                }

                this.product.lineItems = product.lineItems;
            }

            var $button = $(this.options.buttonTemplate(shoppingList));
            var $filteredButton = this._collectButtons($button).data('shoppinglist', shoppingList);

            if (this.element.find(this.options.addToShoppingListButtonSelector).length === 1) {
                this.transformCreateNewButton();
                this.element.prepend($button);
                this._renderButtons();
            } else {
                var $oldMainButton = $(this.options.buttonTemplate(this.main.data('shoppinglist')));
                $oldMainButton = this._collectButtons($oldMainButton).data('shoppinglist', shoppingList);
                this.dropdown.children(':first').remove();
                this.dropdown.children(':last').before(this._prepareButtons($oldMainButton));

                var $mainButton = this._prepareMainButton($filteredButton);
                this.main.replaceWith($mainButton);
                this.main = $mainButton;

                this.dropdown.prepend(this._prepareButtons($filteredButton));

                this.toggleRemoveButton();
            }
        },

        transformCreateNewButton: function() {
            var $button = this.element.find(this.options.addToShoppingListButtonSelector)
                .filter('[data-id=""]').not('[data-intention="' + this.options.intention.new + '"]');
            if ($button.length) {
                var label = _.__(this.options.createNewLabel);
                $button.attr('data-intention', this.options.intention.new);
                $button.html(label);
                $button.attr('title', label);
            }
        },

        _prepareMainButton: function($main) {
            this.toggleButtonsClass();
            this.setButtonLabel($main);
            return this._super($main);
        },

        _renderButtons: function() {
            this._super();
            this.toggleRemoveButton();
        },

        toggleButtonsClass: function() {
            if (!this.product || !this.options.updateButtonLabel) {
                return;
            }
            if (_.isEmpty(this.product.lineItems)) {
                this.group.removeClass(this.options.addedClass).addClass(this.options.defaultClass);
            } else {
                this.group.removeClass(this.options.defaultClass).addClass(this.options.addedClass);
            }
        },

        setButtonLabel: function($main) {
            var self = this;
            if (!this.product || !this.options.updateButtonLabel) {
                return;
            }
            var shoppingList = $main.data('shoppinglist');
            var label;

            if (_.isEmpty(this.product.lineItems)) {
                label =  _.__('orob2b.shoppinglist.actions.add_to_shopping_list');
            } else {
                var lineItems = '';
                if (_.size(this.product.lineItems) === 1) {
                    _.each(this.product.lineItems, function(count, unit) {
                        if (_.size(self.product.units) > 1) {
                            lineItems = _.__(
                                'orob2b.product.product_unit.' + unit + '.value.short',
                                {'count': count},
                                count
                            );
                        } else {
                            lineItems = count;
                        }
                    });
                }
                label =  _.__('orob2b.shoppinglist.actions.added_to_shopping_list')
                    .replace('{{ lineItems }}', lineItems);
            }

            label = label.replace('{{ shoppingList }}', shoppingList.label);
            $main.attr('title', label).html(label);
        },

        toggleRemoveButton: function() {
            if (!this.product || !this.options.updateButtonLabel) {
                return;
            }

            var shoppingList = this.main.data('shoppinglist');

            if (!this.remove && !_.isEmpty(this.product.lineItems)) {
                var $button = $(this.options.buttonRemoveTemplate(shoppingList));
                var $filteredButton = this._collectButtons($button).data('shoppinglist', shoppingList);

                this.remove = this._prepareButtons($filteredButton);

                this.dropdown.append(this.remove);

                this.remove.insertAfter(this.dropdown.children(':first'));
            } else if (this.remove && _.isEmpty(this.product.lineItems)) {
                this.remove.remove();
                delete(this.remove);
            }
        }
    });

    return $;
});
