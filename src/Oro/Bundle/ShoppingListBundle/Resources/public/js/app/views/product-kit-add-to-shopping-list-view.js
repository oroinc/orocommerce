import ProductAddToShoppingListView from 'oroshoppinglist/js/app/views/product-add-to-shopping-list-view';
import ProductKitLineItemWidget from 'oro/product-kit-line-item-widget';
import routing from 'routing';

const ProductKitAddToShoppingListView = ProductAddToShoppingListView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ProductKitAddToShoppingListView(options) {
        ProductKitAddToShoppingListView.__super__.constructor.call(this, options);
    },

    _openProductKitPopup: function(url, urlOptions, formData) {
        this.subview('popup', new ProductKitLineItemWidget({
            url: routing.generate(url, urlOptions),
            model: this.model
        }));
        this.subview('popup').render();
    },

    _saveLineItem: function(url, urlOptions, formData) {
        this._openProductKitPopup(url, urlOptions, formData);
    },

    _addLineItem: function(url, urlOptions, formData) {
        this._openProductKitPopup(url, urlOptions, formData);
    }
});

export default ProductKitAddToShoppingListView;
