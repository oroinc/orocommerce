define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const InputCellValidationEditor = require('orodatagrid/js/datagrid/editor/input-cell-validation-editor').default;
    const DecimalFormatter = require('orodatagrid/js/datagrid/formatter/decimal-formatter').default;
    const numberFormatter = require('orolocale/js/formatter/number');

    const defaultsParams = {
        /**
         * @property {Object}
         */
        constraintNames: {
            range: 'Range',
            decimal: 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Decimal',
            integer: 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Integer'
        },

        /**
         * @property {Object}
         */
        options: {
            quantityColumnName: 'quantity',
            unitColumnName: 'code',
            unitPrecisions: {},
            quantityConstraints: {}
        }
    };

    return {
        processDatagridOptions(deferred, options) {
            const inputSelector = options.metadata.options.cellSelection.selector;
            const params = _.defaults(
                $(inputSelector).first().data('level-quantity-options') || {},
                defaultsParams.options
            );
            const {constraintNames} = defaultsParams;
            const {quantityConstraints, quantityColumnName} = params;

            const updateData = data => {
                return data.map(item => {
                    const quantityValue = item[quantityColumnName];

                    if (quantityValue) {
                        item[quantityColumnName] = numberFormatter.formatDecimal(quantityValue, {
                            grouping_used: false
                        });
                    }

                    const constraints = {};
                    const precision = params.unitPrecisions[item.unitCode] || 0;

                    if (quantityConstraints[constraintNames.range]) {
                        constraints[constraintNames.range] = quantityConstraints[constraintNames.range];
                    }

                    if (precision > 0 && quantityConstraints[constraintNames.decimal]) {
                        constraints[constraintNames.decimal] = quantityConstraints[constraintNames.decimal];
                    } else if (quantityConstraints[constraintNames.integer]) {
                        constraints[constraintNames.integer] = quantityConstraints[constraintNames.integer];
                    }

                    item.constraints = constraints;

                    return item;
                });
            };
            const updateColumns = columns => {
                return columns.map(column => {
                    if (column.name === quantityColumnName && column.editable) {
                        column.editor = InputCellValidationEditor;
                        column.formatter = DecimalFormatter;
                    }

                    return column;
                });
            };

            options.data.data = updateData(options.data.data);
            options.metadata.columns = updateColumns(options.metadata.columns);
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
            options.gridPromise.done(grid => {
                grid.collection.models.forEach(model => model.trigger('gridIsReady'));
                deferred.resolve();
            }).fail(() => {
                deferred.reject();
            });
        }
    };
});
