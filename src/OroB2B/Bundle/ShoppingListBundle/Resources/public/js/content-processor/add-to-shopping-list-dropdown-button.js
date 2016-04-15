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
            buttonTemplate: ''
        },

        keyPreffix: 'add-to-shopping-list-dropdown-button-processor-',

        _create: function() {
            this._super();

            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
                mediator.on(this.options.addButtonEvent, this._addShoppingList, this);
            }
        },

        _addShoppingList: function(shoppingList) {
            var $button = $(this.options.buttonTemplate(shoppingList));
            if (this.element.find(this.options.addToShoppingListButtonSelector).length === 1) {
                this.transformCreateNewButton();
                this.element.prepend($button);
                this._renderButtons();
            } else {
                $button = this._collectButtons($button);

                var $mainButton = this._prepareMainButton($button);
                this.main.replaceWith($mainButton);
                this.main = $mainButton;

                var $createNewButton = this.dropdown.children(':last');
                this.dropdown.children(':first').insertBefore($createNewButton);

                this.dropdown.prepend(this._prepareButtons($button));
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
        }
    });

    return $;
});
