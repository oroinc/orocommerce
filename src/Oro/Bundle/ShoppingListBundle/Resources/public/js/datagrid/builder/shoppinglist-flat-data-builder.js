const flattenData = data => {
    return data.reduce((flatData, item) => {
        const {subData, ...rest} = item;
        if (!subData) {
            flatData.push(rest);
        } else {
            rest.row_class_name = 'group-row';
            flatData.push(rest);
            subData.forEach((subItem, index) => {
                let className = 'sub-row';

                if (subData.length -1 === index) {
                    className = className + ' sub-row-last';
                }

                Object.assign(subItem, {
                    row_class_name: className
                });
            });
            flatData.push(...subData);
        }
        return flatData;
    }, []);
};

const shoppinglistFlatDataBuilder = {
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

export default shoppinglistFlatDataBuilder;
