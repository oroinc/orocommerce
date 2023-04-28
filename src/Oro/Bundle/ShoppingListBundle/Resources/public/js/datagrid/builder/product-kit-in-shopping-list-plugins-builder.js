import ProductKitInShoppingListRefreshPlugin
    from 'oroshoppinglist/js/datagrid/plugins/product-kit-in-shopping-list-refresh-plugin';


const ProductKitInShoppingListPluginsBuilder = {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }
        options.metadata.plugins.push(ProductKitInShoppingListRefreshPlugin);

        return deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init(deferred) {
        return deferred.resolve();
    }
};

export default ProductKitInShoppingListPluginsBuilder;
