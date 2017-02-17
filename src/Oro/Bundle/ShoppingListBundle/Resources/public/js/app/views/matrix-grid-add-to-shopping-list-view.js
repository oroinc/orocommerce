define(function(require) {
    'use strict';

    var MatrixGridAddToShoppingListView;
    var ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    var $ = require('jquery');

    MatrixGridAddToShoppingListView = ProductAddToShoppingListView.extend({
        _addProductToShoppingList: function(url, urlOptions) {
            var $shoppingList = this.$form.find('[name="shoppingListId"]');
            if (!$shoppingList.length) {
                $shoppingList = $('<input name="shoppingListId" type="hidden"/>');
                this.$form.append($shoppingList);
            }
            $shoppingList.val(urlOptions.shoppingListId);
            this.$form.submit();
        }
    });

    return MatrixGridAddToShoppingListView;
});
