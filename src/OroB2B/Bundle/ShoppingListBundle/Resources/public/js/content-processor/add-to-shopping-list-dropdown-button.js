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
            buttonTemplate: '',
            defaultClass: '',
            addedClass: ''
        },

        keyPreffix: 'add-to-shopping-list-dropdown-button-processor-',

        product: null,

        _create: function() {
            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
                mediator.on(this.options.addButtonEvent, this._addShoppingList, this);
                mediator.on(this.options.updateButtonEvent, this._updateShoppingList, this);
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
                var $mainButton = this._prepareMainButton($filteredButton);
                this.main.replaceWith($mainButton);
                this.main = $mainButton;

                var $createNewButton = this.dropdown.children(':last');
                this.dropdown.children(':first').insertBefore($createNewButton);

                this.dropdown.prepend(this._prepareButtons($filteredButton));
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
            this.toggleButtonsClass($main);
            this.setButtonLabel($main);
            return this._super($main);
        },

        toggleButtonsClass: function($main) {
            if (!this.product) {
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
            if (!this.product) {
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
                        lineItems = count;
                        if (_.size(self.product.units) > 1) {
                            lineItems += ' ' + _.__('orob2b.product.product_unit.' + unit + '.label.short');
                        }
                    });
                }
                label =  _.__('orob2b.shoppinglist.actions.added_to_shopping_list')
                    .replace('{{ lineItems }}', lineItems);
            }

            label = label.replace('{{ shoppingList }}', shoppingList.label);
            $main.attr('title', label).html(label);
        }
    });

    return $;
});
