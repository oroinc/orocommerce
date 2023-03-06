import $ from 'jquery';
import {defaults} from 'underscore';
import OrderInputValidationEditor from 'oroproduct/js/datagrid/editor/order-input-validation-editor';
import DecimalFormatter from 'orodatagrid/js/datagrid/formatter/decimal-formatter';
import numberFormatter from 'orolocale/js/formatter/number';

const defaultsParams = {
    /**
     * @property {Object}
     */
    constraintNames: {
        decimal: 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Decimal',
        range: 'Range'
    },

    /**
     * @property {Object}
     */
    options: {
        sortOrderColumnName: 'categorySortOrder',
        inCategoryColumnName: 'inCategory',
        sortOrderConstraints: {}
    }
};

export default {
    processDatagridOptions(deferred, options) {
        const inputSelector = options.metadata.options.cellSelection.selector;
        const params = defaults(
            $(inputSelector).first().data('sort-order-options') || {},
            defaultsParams.options
        );
        const {sortOrderColumnName, sortOrderConstraints, inCategoryColumnName} = params;
        const {constraintNames} = defaultsParams;

        const constraints = {};
        if (sortOrderConstraints[constraintNames.decimal]) {
            constraints[constraintNames.decimal] = sortOrderConstraints[constraintNames.decimal];
        }
        if (sortOrderConstraints[constraintNames.range]) {
            constraints[constraintNames.range] = sortOrderConstraints[constraintNames.range];
        }

        const updateData = data => {
            return data.map(item => {
                const quantityValue = item[sortOrderColumnName];

                if (quantityValue) {
                    item[sortOrderColumnName] = numberFormatter.formatDecimal(quantityValue, {
                        grouping_used: false
                    });
                }
                item.constraints = constraints;
                item.columnName = inCategoryColumnName;
                return item;
            });
        };
        const updateColumns = columns => {
            return columns.map(colum => {
                if (colum.name === sortOrderColumnName && colum.editable && Object.keys(constraints).length) {
                    colum.editor = OrderInputValidationEditor;
                    colum.formatter = DecimalFormatter;
                }

                return colum;
            });
        };

        options.metadata.columns = updateColumns(options.metadata.columns);
        options.data.data = updateData(options.data.data);
        Object.assign(options.metadata.options, {
            parseResponseModels: resp => {
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
    init: function(deferred, options) {
        options.gridPromise.done(function(grid) {
            grid.collection.models.forEach(model => model.trigger('gridIsReady'));
            deferred.resolve();
        }).fail(function() {
            deferred.reject();
        });
    }
};

