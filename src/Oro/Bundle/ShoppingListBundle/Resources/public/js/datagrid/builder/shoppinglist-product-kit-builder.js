import ShoppingListKitRow from 'oroshoppinglist/js/datagrid/row/shopping-list-kit-row';

const addClass = (item, classNames = []) => {
    const classes = item.row_class_name.split(' ');
    item.row_class_name = classes.concat(classNames).join(' ');
};

const useKitRow = item => {
    if (!item.isMessage && !item.isAuxiliary) {
        item.rowView = ShoppingListKitRow;
    }
};

const productKitData = data => data.map(item => {
    item._isKitItemLineItem = false;

    if (item.isKit) {
        addClass(item, 'group-row-product-kit');
        useKitRow(item);
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
        useKitRow(item);
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
