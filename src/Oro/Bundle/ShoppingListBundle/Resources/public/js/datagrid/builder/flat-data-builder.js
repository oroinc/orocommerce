const flattenData = data => {
    return data.reduce((flatData, item) => {
        const {subData, ...rest} = item;
        if (!subData) {
            flatData.push(rest);
        } else if (subData.length === 1) {
            item.row_class_name = 'grid-row__configurable-product--single';
            flatData.push(item); // do not split single item into separate grid row
        } else {
            rest.row_class_name = 'grid-row__configurable-product--set';
            flatData.push(rest);
            subData.forEach(subItem =>
                Object.assign(subItem, {
                    row_class_name: 'grid-row__configurable-product--item'
                }));
            flatData.push(...subData);
        }
        return flatData;
    }, []);
};

const flatDataBuilder = {
    processDatagridOptions: function(deferred, options) {
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                if (resp.metadata) {
                    resp.metadata.rowActions = []; // @todo remove `if` block
                }
                return 'data' in resp ? flattenData(resp.data) : resp;
            }
        });

        options.data.data = flattenData(options.data.data);
        options.metadata.rowActions = []; // @todo remove line

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

export default flatDataBuilder;
