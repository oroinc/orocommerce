const isHighlight = item => item.isUpcoming || (item.errors && item.errors.length);

const flattenData = data => {
    return data.reduce((flatData, item) => {
        const {subData, ...rest} = item;
        const itemClassName = [];

        if (isHighlight(rest)) {
            itemClassName.push('highlight');
        }

        if (!subData) {
            rest.row_class_name = itemClassName.join(' ');
            flatData.push(rest);
        } else {
            itemClassName.push('group-row');
            rest.row_class_name = itemClassName.join(' ');
            rest._hasVariants = true;
            flatData.push(rest);
            subData.forEach((subItem, index) => {
                const className = ['sub-row'];

                if (subData.length - 1 === index) {
                    className.push('sub-row-last');
                }

                if (isHighlight(subItem)) {
                    className.push('highlight');
                }

                subItem._isVariant = true;
                subItem.row_class_name = className.join(' ');
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
