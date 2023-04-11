import ProductAddToShoppingListView from 'oroshoppinglist/js/app/views/product-add-to-shopping-list-view';

const ProductKitsAddToShoppingListView = ProductAddToShoppingListView.extend({
    /**
     * @inheritdoc
     */
    constructor: function ProductKitsAddToShoppingListView(options) {
        ProductKitsAddToShoppingListView.__super__.constructor.call(this, options);
    },

    /**
     * @param {jQuery.Event} e
     */
    onClick: function(e) {
        e.preventDefault();

        if (this.validateForm()) {
            return;
        }

        ProductKitsAddToShoppingListView.__super__.onClick.call(this, e);
    },

    /**
     * Validates the form, returns true if it is valid, false otherwise
     * @returns {boolean}
     */
    validateForm: function() {
        return !this.$form.validate().form();
    }
});

export default ProductKitsAddToShoppingListView;
