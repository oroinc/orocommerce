import ProductKitInShoppingListRefreshPlugin
    from 'oroshoppinglist/js/datagrid/plugins/product-kit-in-shopping-list-refresh-plugin';
import ProductKitResetShoppingListsPlugin
    from 'oroshoppinglist/js/datagrid/plugins/product-kit-reset-shopping-lists-plugin';
import ShoppingListMessageRow from '../row/shopping-list-message-row';
import {addClass, isError, isHighlight, isUpcoming, messageModel} from './utils';
import productKitItemMessage from 'tpl-loader!oroshoppinglist/templates/datagrid/cell/product-kit-item-message.html';

const rowsClassesModify = data => {
    return data.reduce((finishData, item) => {
        if (item.row_class_name === void 0) {
            item.row_class_name = '';
        }

        if (isHighlight(item)) {
            addClass(item, 'highlight');
        }

        if (isError(item)) {
            addClass(item, 'highlight-error');
        }

        if (isUpcoming(item)) {
            addClass(item, 'highlight-upcoming');
        }

        finishData.push(item);

        if (isError(item) || isHighlight(item) || isUpcoming(item)) {
            finishData.push(messageModel(item, 'kitConfiguration', {
                rowView: ShoppingListMessageRow,
                kitConfiguration: productKitItemMessage({obj: item})
            }));
        }

        return finishData;
    }, []);
};

const ProductKitInShoppingListPluginsBuilder = {
    processDatagridOptions(deferred, options) {
        const {
            parseResponseModels
        } = options.metadata.options;

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }

        options.metadata.plugins.push(
            ProductKitInShoppingListRefreshPlugin,
            {
                constructor: ProductKitResetShoppingListsPlugin,
                options: {
                    productModel: options.productModel
                }
            }
        );

        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                if (parseResponseModels) {
                    resp = parseResponseModels(resp);
                }

                return 'data' in resp ? rowsClassesModify(resp.data) : rowsClassesModify(resp);
            }
        });

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
