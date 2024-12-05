import _ from 'underscore';
import FilteredProductVariantsPlugin from 'oroshoppinglist/js/datagrid/plugins/filtered-product-variants-plugin';
import ShoppingListRefreshPlugin from 'oroshoppinglist/js/datagrid/plugins/shopping-list-refresh-plugin';
import HighlightRelatedRowsPlugin from 'oroshoppinglist/js/datagrid/plugins/highlight-related-rows-plugin';
import quantityHelper from 'oroproduct/js/app/quantity-helper';
import ShoppingListRow from 'oroshoppinglist/js/datagrid/row/shopping-list-row';
import {isHighlight, isError, messageModel} from './utils';

export const flattenData = data => {
    const errorColumn = shoppingListFlatDataBuilder.errorColumn;
    return data.reduce((flatData, rawData) => {
        const {subData, ...item} = rawData;
        const itemClassName = [];

        item.productUID = _.uniqueId('');

        if (isHighlight(item)) {
            itemClassName.push('highlight');
        }

        if (isError(item)) {
            itemClassName.push('highlight-error');
        }

        if (!item.sku) {
            itemClassName.push('no-product-sku-row');
        }

        if (
            subData === null ||
            (Array.isArray(subData) && subData.length === 0)
        ) {
            itemClassName.push('single-row');
            item.row_class_name = itemClassName.join(' ');
            flatData.push(item);
            item._hasVariants = false;
            item._isVariant = false;

            if (isError(item) || isHighlight(item)) {
                flatData.push(messageModel(item, errorColumn));
            }
        } else {
            let filteredOutVariants = 0;
            const precisions = [];
            let lastFiltered = item;

            itemClassName.push('group-row');

            if (subData.length) {
                itemClassName.push('group-row-has-children');
            }

            if (item.isConfigurable) {
                itemClassName.push('group-row-configurable');
            }

            item.row_class_name = itemClassName.join(' ');
            item.ids = [];
            item._hasVariants = item.isConfigurable || false;
            item._isVariant = false;

            flatData.push(item);

            const flatSubData = subData.reduce((subDataCollection, subItem, index) => {
                const className = ['sub-row'];

                subItem.productUID = _.uniqueId('');

                if (subItem.units && subItem.units[item.unit]) {
                    precisions.push(subItem.units[item.unit].precision);
                }

                if (isHighlight(subItem)) {
                    className.push('highlight');
                }

                if (!subItem.sku) {
                    className.push('no-product-sku-row');
                }

                if (isHighlight(item)) {
                    className.push('parent-row-has-highlight');
                }

                if (isError(subItem)) {
                    className.push('highlight-error');
                }

                if (isError(item)) {
                    className.push('parent-row-has-highlight-error');
                }

                if (subData.length - 1 === index) {
                    className.push('sub-row-last');
                }

                if (subItem.filteredOut) {
                    filteredOutVariants++;
                    className.push('hide');
                } else {
                    lastFiltered = subItem;
                }

                item.ids.push(subItem.id);
                subItem._isVariant = item._hasVariants || false;
                subItem._groupId = item.productId;
                subItem.row_class_name = className.join(' ');
                subItem.row_attributes = {
                    ...(subItem.row_attributes ?? {}),
                    'data-product-group': subItem._groupId
                };

                subDataCollection.push(subItem);

                if ((isError(subItem) && subItem.sku) || isHighlight(subItem)) {
                    subDataCollection.push(messageModel(subItem, 'item'));
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
            if (isError(item) || isHighlight(item)) {
                const itemMessageModel = messageModel(item, errorColumn);
                flatData.push(itemMessageModel);
            }
        }

        return flatData;
    }, []);
};

const shoppingListFlatDataBuilder = {
    /**
     * @property {string}
     */
    columnForMainError: 'sku',

    /**
     * A column to which general errors are aligned
     */
    errorColumn: 'item',

    /**
     * @param {Object} columns
     */
    setErrorColumn(columns) {
        const column = _.find(columns, column => {
            return column.name === this.columnForMainError;
        });

        if (column && column.renderable) {
            this.errorColumn = column.name;
        }
    },

    processDatagridOptions(deferred, options) {
        const {
            parseResponseModels,
            parseResponseOptions
        } = options.metadata.options;

        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
                if (parseResponseModels) {
                    resp = parseResponseModels(resp);
                }
                return 'data' in resp ? flattenData(resp.data) : resp;
            },
            parseResponseOptions: (resp = {}) => {
                if (parseResponseOptions) {
                    resp = parseResponseOptions(resp);
                }
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
        options.metadata.plugins.push(
            FilteredProductVariantsPlugin,
            ShoppingListRefreshPlugin,
            HighlightRelatedRowsPlugin
        );

        this.setErrorColumn(options.metadata.columns);
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
    init(deferred, options) {
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
