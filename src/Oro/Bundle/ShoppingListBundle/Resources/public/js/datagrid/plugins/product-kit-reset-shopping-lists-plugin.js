import BasePlugin from 'oroui/js/app/plugins/base/plugin';
import ShoppingListCollectionService from 'oroshoppinglist/js/shoppinglist-collection-service';

const ProductKitResetShoppingListsPlugin = BasePlugin.extend({
    constructor: function ProductKitResetShoppingListsPlugin(...args) {
        ProductKitResetShoppingListsPlugin.__super__.constructor.apply(this, args);
    },

    initialize(main, options = {}) {
        this.productModel = options.productModel;

        if (this.productModel === void 0) {
            throw new Error('Option "productModel" is required');
        }

        ShoppingListCollectionService.shoppingListCollection.done(collection => {
            this.shoppingListCollection = collection;
        });
    },

    enable: function() {
        if (this.enabled) {
            return;
        }

        const {collection} = this.main;
        this.listenTo(collection, 'request', function(PageableCollection, xhr, options) {
            const {productModel, shoppingListCollection} = this;

            xhr.done(response => {
                if (collection && collection.length === 0) {
                    if (!productModel && productModel.disposed) {
                        return;
                    }

                    if (!shoppingListCollection && shoppingListCollection.disposed) {
                        return;
                    }
                    productModel.set('shopping_lists', [], {silent: true});
                    productModel.trigger('change:shopping_lists');
                    shoppingListCollection.trigger('change');
                }
            });
        });
        ProductKitResetShoppingListsPlugin.__super__.enable.call(this);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.productModel;
        delete this.shoppingListCollection;

        ProductKitResetShoppingListsPlugin.__super__.dispose.call(this);
    }
});

export default ProductKitResetShoppingListsPlugin;
