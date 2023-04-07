import _ from 'underscore';
import OrderInputValidationEditor from 'oroproduct/js/datagrid/editor/order-input-validation-editor';
import DecimalFormatter from 'orodatagrid/js/datagrid/formatter/decimal-formatter';

export default {
    SORT_ORDER_COLUMN_NAME: 'sortOrder',

    processDatagridOptions(deferred, options) {
        const sortOrderColumnName = this.SORT_ORDER_COLUMN_NAME;

        const updateColumns = columns => {
            return columns.map(column => {
                if (column.name === sortOrderColumnName && column.editable) {
                    if (_.isDesktop()) {
                        // for desktop version sorting via drag n drop rows is enabled
                        // hide sort order column
                        Object.assign(column, {
                            editable: false,
                            renderable: false,
                            manageable: false
                        });
                    } else {
                        column.editor = OrderInputValidationEditor;
                        column.formatter = DecimalFormatter;
                    }
                }

                return column;
            });
        };

        options.metadata.columns = updateColumns(options.metadata.columns);

        const updateData = data => {
            let maxSortOrder = Math.max(
                ...data.map(item => item[sortOrderColumnName] ? Number(item[sortOrderColumnName]) : 0)
            );
            return data.map(item => {
                item[sortOrderColumnName] = Number(item[sortOrderColumnName]) || ++maxSortOrder;
                item.constraints = options.gridBuildersOptions?.sortOrderConstraints || {};
                return item;
            });
        };

        options.data.data = updateData(options.data.data);
        const {parseResponseModels} = options.metadata.options;
        Object.assign(options.metadata.options, {
            parseResponseModels: function(resp) {
                if (parseResponseModels) {
                    resp = parseResponseModels.call(this, resp);
                }
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
