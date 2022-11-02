define(function(require) {
    'use strict';

    const ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    const MatrixGridOrderWidget = require('oro/matrix-grid-order-widget');

    const MatrixGridPopupButtonView = ProductAddToShoppingListView.extend({
        /**
         * @inheritdoc
         */
        constructor: function MatrixGridPopupButtonView(options) {
            MatrixGridPopupButtonView.__super__.constructor.call(this, options);
        },

        _openMatrixGridPopup: function(shoppingListId) {
            this.subview('popup', new MatrixGridOrderWidget({
                model: this.model,
                shoppingListId: shoppingListId
            }));
            this.subview('popup').render();
        },

        _saveLineItem: function(url, urlOptions, formData) {
            this._openMatrixGridPopup(urlOptions.shoppingListId);
        },

        _addLineItem: function(url, urlOptions, formData) {
            this._openMatrixGridPopup(urlOptions.shoppingListId);
        }
    });

    return MatrixGridPopupButtonView;
});
