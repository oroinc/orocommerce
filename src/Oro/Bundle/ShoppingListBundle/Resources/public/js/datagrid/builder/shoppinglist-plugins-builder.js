import FilteredProductVariantsPlugin from 'oroshoppinglist/js/datagrid/plugins/filtered-product-variants-plugin';
import ShoppingListRefreshPlugin from 'oroshoppinglist/js/datagrid/plugins/shopping-list-refresh-plugin';
import HighlightRelatedRowsPlugin from 'oroshoppinglist/js/datagrid/plugins/highlight-related-rows-plugin';

const shoppingListPluginsBuilder = {
    processDatagridOptions(deferred, options) {
        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        if (options.metadata.options?.disableRefreshPlugin !== true) {
            options.metadata.plugins.push(ShoppingListRefreshPlugin);
        }

        if (options.metadata.options?.disableFilteredProductVariantsPlugin !== true) {
            options.metadata.plugins.push(FilteredProductVariantsPlugin);
        }

        if (options.metadata.options?.disableHighlightRelatedRowsPlugin !== true) {
            options.metadata.plugins.push(HighlightRelatedRowsPlugin);
        }

        return deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init: deferred => deferred.resolve()
};

export default shoppingListPluginsBuilder;
