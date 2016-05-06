define(function(require) {
    'use strict';

    var ProductQuickAddToShoppingListView;
    var ProductAddToShoppingListView = require('orob2bshoppinglist/js/app/views/product-add-to-shopping-list-view');
    var mediator = require('oroui/js/mediator');

    ProductQuickAddToShoppingListView = ProductAddToShoppingListView.extend({
        initialize: function(options) {
            ProductQuickAddToShoppingListView.__super__.initialize.apply(this, arguments);
            this.options.quickAddComponentPrefix = options.quickAddComponentPrefix;
        },

        _addProductToShoppingList: function(url, urlOptions, formData) {
            mediator.trigger(
                this.options.quickAddComponentPrefix + ':submit',
                'orob2b_shopping_list_quick_add_processor',
                urlOptions.shoppingListId
            );
        }
    });

    return ProductQuickAddToShoppingListView;
});
