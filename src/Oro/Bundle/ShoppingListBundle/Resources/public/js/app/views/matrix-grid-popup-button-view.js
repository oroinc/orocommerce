define(function(require) {
    'use strict';

    var MatrixGridPopupButtonView;
    var ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    var MatrixGridOrderWidget = require('oro/matrix-grid-order-widget');

    MatrixGridPopupButtonView = ProductAddToShoppingListView.extend({
        /**
         * @inheritDoc
         */
        constructor: function MatrixGridPopupButtonView() {
            MatrixGridPopupButtonView.__super__.constructor.apply(this, arguments);
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
