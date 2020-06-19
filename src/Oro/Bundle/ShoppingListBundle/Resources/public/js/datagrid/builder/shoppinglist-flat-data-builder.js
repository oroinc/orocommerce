import _ from 'underscore';

const isHighlight = item => item.isUpcoming || (item.errors && item.errors.length);
const flattenData = data => {
    return data.reduce((flatData, rawData) => {
        const {subData, ...item} = rawData;
        const itemClassName = [];
        const hideClassName = 'hide';

        if (isHighlight(item)) {
            itemClassName.push('highlight');
        }

        if (!subData) {
            item.row_class_name = itemClassName.join(' ');
            flatData.push(item);
        } else {
            const filteredOutElements = [];
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
                    const filteredOutClass = _.uniqueId('filtered-out-');

                    filteredOutElements.push(filteredOutClass);
                    className.push(filteredOutClass);
                    className.push(hideClassName);
                } else {
                    lastFiltered = subItem;
                }

                subItem._isVariant = true;
                subItem.row_class_name = className.join(' ');
            });

            if (filteredOutElements.length) {
                lastFiltered.filteredOutData = {
                    hideClass: hideClassName,
                    elements: filteredOutElements,
                    groupName: item.name
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
