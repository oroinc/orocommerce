import _ from 'underscore';
import FilteredProductVariantsPlugin from 'oroshoppinglist/js/datagrid/plugins/filtered-product-variants-plugin';
import ShoppingListRefreshPlugin from 'oroshoppinglist/js/datagrid/plugins/shopping-list-refresh-plugin';
import quantityHelper from 'oroproduct/js/app/quantity-helper';
import ShoppingListRow from 'oroshoppinglist/js/datagrid/row/shopping-list-row';

const isHighlight = item => item.isUpcoming;
const isError = item => item.errors && item.errors.length;

const messageModel = item => {
    const messageItem = {
        ...item,
        id: item.id + _.uniqueId('-bind-'),
        notificationCell: 'item',
        row_class_name: item.row_class_name + ' notification-row',
        isMessage: true,
        isAuxiliary: true,
        row_attributes: {
            'aria-hidden': true
        }
    };

    item.messageModelId = messageItem.id;
    return messageItem;
};

export const flattenData = data => {
    return data.reduce((flatData, rawData) => {
        const {subData, ...item} = rawData;
        const itemClassName = [];

        if (isHighlight(item)) {
            itemClassName.push('highlight');
        }

        if (isError(item)) {
            itemClassName.push('highlight-error');
        }

        if (!subData) {
            itemClassName.push('single-row');
            item.row_class_name = itemClassName.join(' ');
            flatData.push(item);
            item._hasVariants = false;
            item._isVariant = false;

            if (isError(item) || isHighlight(item)) {
                flatData.push(messageModel(item));
            }
        } else {
            let filteredOutVariants = 0;
            const precisions = [];
            let lastFiltered = item;

            itemClassName.push('group-row');

            if (subData.length) {
                itemClassName.push('group-row-has-children');
            }

            item.row_class_name = itemClassName.join(' ');
            item.ids = [];
            item._hasVariants = true;
            item._isVariant = false;

            if (isError(item) || isHighlight(item)) {
                flatData.push(messageModel(item));
            }

            flatData.push(item);

            const flatSubData = subData.reduce((subDataCollection, subItem, index) => {
                const className = ['sub-row'];

                if (subItem.units && subItem.units[item.unit]) {
                    precisions.push(subItem.units[item.unit].precision);
                }

                if (subData.length - 1 === index) {
                    className.push('sub-row-last');
                }

                if (isHighlight(subItem)) {
                    className.push('highlight');
                }

                if (isError(subItem)) {
                    className.push('highlight-error');
                }

                if (subItem.filteredOut) {
                    filteredOutVariants++;
                    className.push('hide');
                } else {
                    lastFiltered = subItem;
                }

                item.ids.push(subItem.id);
                subItem._isVariant = true;
                subItem._groupId = item.productId;
                subItem.row_class_name = className.join(' ');
                subItem.row_attributes = {
                    'data-product-group': item.productId
                };

                subDataCollection.push(subItem);

                if (isError(subItem) || isHighlight(subItem)) {
                    subDataCollection.push(messageModel(subItem));
                }

                return subDataCollection;
            }, []);

            item.precision = precisions.length
                ? Math.max.apply(null, precisions)
                : quantityHelper.getDefaultMaxFractionDigits();

            if (filteredOutVariants) {
                lastFiltered.filteredOutData = {
                    count: filteredOutVariants,
                    group: {
                        name: item.name,
                        id: item.productId
                    }
                };

                lastFiltered.row_class_name += ' filtered-out';
            }

            flatData.push(...flatSubData);
        }

        return flatData;
    }, []);
};

const shoppingListFlatDataBuilder = {
    processDatagridOptions(deferred, options) {
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                return 'data' in resp ? flattenData(resp.data) : resp;
            },
            parseResponseOptions: (resp = {}) => {
                const {options = {}} = resp;
                return {
                    reset: false,
                    uniqueOnly: true,
                    wait: false,
                    ...options
                };
            }
        });

        if (!options.metadata.plugins) {
            options.metadata.plugins = [];
        }
        options.metadata.plugins.push(FilteredProductVariantsPlugin, ShoppingListRefreshPlugin);

        options.data.data = flattenData(options.data.data);

        options.themeOptions = {
            ...options.themeOptions,
            rowView: ShoppingListRow
        };

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     */
    init: (deferred, options) => {
        options.gridPromise.done(grid => {
            grid.collection.on('beforeRemove', (modelToRemove, collection, options) => {
                if (modelToRemove.get('_isVariant')) {
                    options.recountTotalRecords = false;
                }
            });
        });

        return deferred.resolve();
    }
};

export default shoppingListFlatDataBuilder;
