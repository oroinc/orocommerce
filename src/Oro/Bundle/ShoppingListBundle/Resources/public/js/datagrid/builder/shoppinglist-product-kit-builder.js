import ShoppingListProductKitRow from 'oroshoppinglist/js/datagrid/row/shopping-list-product-kit-row';
import ShoppingListProductKitSubItemRow from '../row/shopping-list-product-kit-sub-item-row';
import ProductKitInShoppingListRefreshPlugin
    from 'oroshoppinglist/js/datagrid/plugins/product-kit-in-shopping-list-refresh-plugin';
import ProductKitExpandCollapseItems
    from 'oroshoppinglist/js/datagrid/plugins/product-kit-expand-collapse-items';
import {addClass, removeClass, isError, isHighlight} from './utils';

const useKitSubItemRow = item => {
    if (!item.isMessage && !item.isAuxiliary) {
        item.rowView = ShoppingListProductKitSubItemRow;
    }
};

const productKitData = data => data.map(item => {
    item._isKitItemLineItem = false;

    if (item.isKit) {
        addClass(item, 'grid-row-product-kit');
        item.rowView = ShoppingListProductKitRow;

        if (isError(item) || isHighlight(item)) {
            addClass(item, 'grid-row-product-kit-error');
            removeClass(item, 'group-row');
        }
    }

    if (item.kitHasGeneralError) {
        addClass(item, 'product-kit-general-error');

        if (Array.isArray(item.ids) && item.ids.length) {
            const kitLineItemById = Object.fromEntries(item.ids.map(id => [id, true]));

            data.forEach(kitLineItem => {
                if (kitLineItemById[kitLineItem.id]) {
                    kitLineItem.kitHasGeneralError = true;
                    addClass(kitLineItem, 'product-kit-general-error');
                }
            });
        }
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
        options.metadata.plugins.push(ProductKitInShoppingListRefreshPlugin, ProductKitExpandCollapseItems);

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
