import _ from 'underscore';
import ShoppingListRow from 'oroshoppinglist/js/datagrid/row/shopping-list-row';
const SHIPPING_METHODS_COLUMN_NAME = 'shippingMethods';

const shippingMethodsModel = item => {
    const shippingMethodsItem = {
        ...item,
        lineItemId: item.id,
        id: item.id + _.uniqueId('-bind-'),
        renderColumnName: 'item',
        definitionColumnName: SHIPPING_METHODS_COLUMN_NAME,
        row_class_name: item.row_class_name + ' extension-row shipping-methods-row'
    };

    item.hippingMethodsModelId = shippingMethodsItem.id;
    return shippingMethodsItem;
};

export const flattenData = data => {
    return data.reduce((flatData, rawData) => {
        flatData.push(rawData);

        if (!rawData.isMessage) {
            flatData.push(shippingMethodsModel(rawData));
            rawData.row_class_name = rawData.row_class_name + ' group-row group-row-has-children';
        }

        return flatData;
    }, []);
};

const shippingMethodsFlatDataBuilder = {
    processDatagridOptions(deferred, options) {
        const shippingMethodsColumn = options.metadata.columns
            .find(column => column.name === SHIPPING_METHODS_COLUMN_NAME);
        if (shippingMethodsColumn) {
            Object.assign(shippingMethodsColumn, {
                manageable: false,
                renderable: false
            });
        }

        const {parseResponseModels} = options.metadata.options;
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                if (parseResponseModels) {
                    resp = parseResponseModels(resp);
                }
                return 'data' in resp ? flattenData(resp.data) : resp;
            }
        });

        options.data.data = flattenData(options.data.data);

        options.themeOptions = {
            ...options.themeOptions,
            rowView: ShoppingListRow
        };
        return deferred.resolve();
    },

    /**
     * Init() function is required
     */
    init(deferred) {
        return deferred.resolve();
    }
};

export default shippingMethodsFlatDataBuilder;
