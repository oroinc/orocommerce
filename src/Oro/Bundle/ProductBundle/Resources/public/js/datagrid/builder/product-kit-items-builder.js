import OrderInputValidationEditor from 'oroproduct/js/datagrid/editor/order-input-validation-editor';
import DecimalFormatter from 'orodatagrid/js/datagrid/formatter/decimal-formatter';

export default {
    SORT_ORDER_COLUMN_NAME: 'sortOrder',

    processDatagridOptions(deferred, options) {
        const sortOrderColumnName = this.SORT_ORDER_COLUMN_NAME;

        const updateColumns = columns => {
            return columns.map(column => {
                if (column.name === sortOrderColumnName && column.editable) {
                    column.editor = OrderInputValidationEditor;
                    column.formatter = DecimalFormatter;
                }

                return column;
            });
        };

        options.metadata.columns = updateColumns(options.metadata.columns);

        const updateData = data => {
            return data.map((item, index, items) => {
                const maxSortOrder = Math.max(...items.map(_item =>
                    _item[sortOrderColumnName] ? Number(_item[sortOrderColumnName]) : 0
                ));
                item[sortOrderColumnName] = Number(item[sortOrderColumnName]) || maxSortOrder + 1;

                item.constraints = options.gridBuildersOptions?.sortOrderConstraints || {};
                return item;
            });
        };

        Object.assign(options.metadata.options, {
            comparator: sortOrderColumnName
        });

        options.data.data = updateData(options.data.data);
        Object.assign(options.metadata.options, {
            parseResponseModels: function(resp) {
                if ('data' in resp) {
                    // collection is bound as context to `parseResponseModels` function
                    const collection = this;
                    resp.data.forEach(item => {
                        const model = collection.get(item.id);
                        if (model) {
                            // restore current sortOrder value for existing models
                            item[sortOrderColumnName] = model.get(sortOrderColumnName);
                        }
                    });
                }
                return 'data' in resp ? updateData(resp.data) : resp;
            }
        });

        deferred.resolve();
        return deferred;
    },

    /**
     * Init() function is required
     * @param {jQuery.Deferred} deferred
     * @param {Object} options
     */
    init(deferred, options) {
        deferred.resolve();
    }
};
