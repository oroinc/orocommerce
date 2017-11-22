define(function(require) {
    'use strict';

    var MatrixGridAddToShoppingListView;
    var ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');

    MatrixGridAddToShoppingListView = ProductAddToShoppingListView.extend({
        _saveLineItem: function(url, urlOptions, formData) {
            return this._addLineItem(url, urlOptions, formData);
        },

        _addLineItem: function(url, urlOptions, formData) {
            url = 'oro_shopping_list_frontend_matrix_grid_order';
            return MatrixGridAddToShoppingListView.__super__._addLineItem.call(this, url, urlOptions, formData);
        }
    });

    return MatrixGridAddToShoppingListView;
});
