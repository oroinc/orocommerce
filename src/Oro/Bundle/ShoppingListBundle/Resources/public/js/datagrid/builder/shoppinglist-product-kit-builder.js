import ShoppingListProductKitSubItemRow from '../row/shopping-list-product-kit-sub-item-row';
import ProductKitInShoppingListRefreshPlugin
    from 'oroshoppinglist/js/datagrid/plugins/product-kit-in-shopping-list-refresh-plugin';

import {addClass} from './utils';

const useKitSubItemRow = item => {
    if (!item.isMessage && !item.isAuxiliary) {
        item.rowView = ShoppingListProductKitSubItemRow;
    }
};

const productKitData = data => data.map(item => {
    item._isKitItemLineItem = false;

    if (item.isKit) {
        addClass(item, 'group-row-product-kit');
    }

    if (!item.sku) {
        addClass(item, 'no-product-sku-row');
    }

    item._hasKitItemLineItems = item.isKit && item.ids && item.ids.length;

    if (item._groupId && typeof item.id === 'string' && item.id.startsWith('productkititemlineitem:')) {
        item._isKitItemLineItem = true;
        addClass(item, 'sub-row-product-kit');

        if (item.row_class_name.split(' ').includes('sub-row-last')) {
            addClass(item, 'sub-row-last-product-kit');
        }
        useKitSubItemRow(item);
    }

    return item;
});

const shoppinglistProductKitBuilder = {
    processDatagridOptions(deferred, options) {
        const {
            parseResponseModels
        } = options.metadata.options;

        Object.assign(options.metadata.options, {
            parseResponseModels: data => {
                if (parseResponseModels) {
                    data = parseResponseModels(data);
                }

                return productKitData(data);
            }
        });

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }
        options.metadata.plugins.push(ProductKitInShoppingListRefreshPlugin);

        return deferred.resolve();
    },

    init(deferred, options) {
        options.gridPromise.done(grid => {
            const isGrouped = collection => collection.some(model => model.get('isKit') || model.get('isConfigurable'));

            grid.$el.toggleClass('grid-has-grouped-rows', isGrouped(grid.collection));
            grid.collection.on('reset',
                collection => grid.$el.toggleClass('grid-has-grouped-rows', isGrouped(collection))
            );
        });

        return deferred.resolve();
    }
};

export default shoppinglistProductKitBuilder;
