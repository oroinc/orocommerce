import FilteredProductVariantsPlugin from 'oroshoppinglist/js/datagrid/plugins/filtered-product-variants-plugin';

const isHighlight = item => item.isUpcoming || (item.errors && item.errors.length);
const flattenData = data => {
    return data.reduce((flatData, rawData) => {
        const {subData, ...item} = rawData;
        const itemClassName = [];

        if (isHighlight(item)) {
            itemClassName.push('highlight');
        }

        if (!subData) {
            item.row_class_name = itemClassName.join(' ');
            flatData.push(item);
        } else {
            let filteredOutVariants = 0;
            let lastFiltered = item;

            itemClassName.push('group-row');
            item.row_class_name = itemClassName.join(' ');
            item._hasVariants = true;
            flatData.push(item);
            subData.forEach((subItem, index) => {
                const className = ['sub-row'];

                if (subData.length - 1 === index) {
                    className.push('sub-row-last');
                }

                if (isHighlight(subItem)) {
                    className.push('highlight');
                }

                if (subItem.filteredOut) {
                    filteredOutVariants++;
                    className.push('hide');
                } else {
                    lastFiltered = subItem;
                }

                subItem._isVariant = true;
                subItem.row_class_name = className.join(' ');
                subItem.row_attributes = {
                    'data-product-group': item.productId
                };
            });

            if (filteredOutVariants) {
                lastFiltered.filteredOutData = {
                    count: filteredOutVariants,
                    group: {
                        name: item.name,
                        id: item.productId
                    }
                };
            }

            flatData.push(...subData);
        }
        return flatData;
    }, []);
};

const shoppingListFlatDataBuilder = {
    processDatagridOptions: function(deferred, options) {
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                return 'data' in resp ? flattenData(resp.data) : resp;
            }
        });

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }
        options.metadata.plugins.push(FilteredProductVariantsPlugin);

        options.data.data = flattenData(options.data.data);

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     */
    init: function(deferred, options) {
        deferred.resolve();
    }
};

export default shoppingListFlatDataBuilder;
