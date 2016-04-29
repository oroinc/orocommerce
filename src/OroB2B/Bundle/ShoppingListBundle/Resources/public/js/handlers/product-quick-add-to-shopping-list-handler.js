define(function(require) {
    'use strict';

    var ProductQuickAddToShoppingListHandler;
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

    ProductQuickAddToShoppingListHandler = {
        onClick: function(view, $button) {
            if ($button.data('intention') === 'new') {
                this._createNewShoppingList($button);
            } else {
                var shoppingList = $button.data('shoppinglist');
                this._addProductToShoppingList($button, shoppingList.id);
            }
        },

        /**
         * @param {jQuery} $button
         */
        _createNewShoppingList: function($button) {
            var dialog = new ShoppingListWidget({});
            dialog.on('formSave', _.bind(function(response) {
                this._addProductToShoppingList($button, response);
            }, this));
            dialog.render();
        },

        /**
         * @param {jQuery} $button
         * @param {Integer} shoppingListId
         */
        _addProductToShoppingList: function($button, shoppingListId) {
            var options = $button.data('options');
            mediator.trigger(
                options.quickAddComponentPrefix + ':submit',
                'orob2b_shopping_list_quick_add_processor',
                shoppingListId
            );
        }
    };

    return ProductQuickAddToShoppingListHandler;
});
