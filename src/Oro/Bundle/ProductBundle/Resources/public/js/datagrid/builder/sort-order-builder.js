define(['jquery', 'underscore', 'oroui/js/mediator', 'orolocale/js/formatter/number'
], function($, _, mediator, NumberFormatter) {
    'use strict';

    const SortOrder = function(options) {
        this.initialize(options);
    };

    _.extend(SortOrder.prototype, {
        /**
         * @property {Grid}
         */
        grid: null,

        /**
         * @property {Object}
         */
        constraintNames: {
            decimal: 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Decimal',
            greaterThan0: 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\GreaterThanZero'
        },

        /**
         * @property {Object}
         */
        options: {
            sortOrderColumnName: 'sortOrder',
            inCategoryColumnName: 'inCategory',
            sortOrderConstraints: {}
        },

        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function(options) {
            this.grid = options.grid;
            this.grid.refreshAction.execute();

            const inputSelector = options.options.metadata.options.cellSelection.selector;
            const sortOrderValidationOptions = $(inputSelector).first().data('sort-order-options');
            this.options = _.defaults(sortOrderValidationOptions || {}, this.options);

            this._formatInitialSortOrder();
            this._applyValidationToGrid();

            const sortOrderColumn = this.options.sortOrderColumnName;

            this.grid.collection.on(`change:${sortOrderColumn}`, (model, value) => {
                // validate input
                this._applyValidationToModel(model);
                // convert to numeric value to support correct grid sorting
                if (!isNaN(value)) {
                    model.set(sortOrderColumn, NumberFormatter.unformat(value), {silent: true});
                }
                const row = this.grid.body.rows.find(row => row.model === model);
                if (row) {
                    const cell = row.cells.find(cell => cell.column.get('name') === sortOrderColumn);
                    if (cell && cell.currentEditor) {
                        cell.currentEditor.render();
                    }
                }
            });

            this.grid.collection.on('sync', this._formatInitialSortOrder.bind(this));
            this.grid.collection.on('sync', this._applyValidationToGrid.bind(this));
            this.grid.collection.on('reset', this._applyValidationToGrid.bind(this));
            this.grid.collection.on('backgrid:selected', this._applyValidationToGrid.bind(this));
        },

        /**
         * Format sort order value to emulate number cell behaviour and value
         */
        _formatInitialSortOrder: function() {
            const sortOrderColumn = this.options.sortOrderColumnName;
            // apply rounding & validation
            _.each(this.grid.collection.models, function(model) {
                model.set(sortOrderColumn, NumberFormatter.formatDecimal(model.get(sortOrderColumn), {
                    grouping_used: false
                }));
            }, this);
        },

        _validateInput: function(cell) {
            const sortOrderConstraints = this.options.sortOrderConstraints;
            const constraintNames = this.constraintNames;
            const editorInput = cell.$el.find(':input').first();

            const constraints = {};
            if (sortOrderConstraints[constraintNames.decimal]) {
                constraints[constraintNames.decimal] = sortOrderConstraints[constraintNames.decimal];
            }
            if (sortOrderConstraints[constraintNames.greaterThan0]) {
                constraints[constraintNames.greaterThan0] = sortOrderConstraints[constraintNames.greaterThan0];
            }

            editorInput.data('validation', constraints);
            editorInput.valid();
        },

        /**
         * Set validation to all rows and apply it to defined values
         */
        _applyValidationToGrid: function() {
            _.each(this.grid.body.rows, function(row) {
                _.each(row.cells, function(cell) {
                    if (cell.column.get('name') === this.options.sortOrderColumnName) {
                        this._applyValidationToCell(cell);
                    }
                }, this);
            }, this);
        },

        /**
         * Set validation to current model
         */
        _applyValidationToModel: function(model) {
            _.each(this.grid.body.rows, function(row) {
                _.each(row.cells, function(cell) {
                    if (cell.column.get('name') === this.options.sortOrderColumnName && cell.model.cid === model.cid) {
                        this._applyValidationToCell(cell);
                    }
                }, this);
            }, this);
        },

        /**
         * @param {Backgrid.Cell} cell
         */
        _applyValidationToCell: function(cell) {
            const editorInput = cell.$el.find(':input').first();

            if (!cell.model.has(this.options.inCategoryColumnName)
                || cell.model.get(this.options.inCategoryColumnName)) {
                editorInput.show();
                editorInput.prop( "disabled", false );
                editorInput.parent().addClass('controls').removeClass('editable');
                editorInput.attr('name', 'sortOrder_' + cell.model.cid);
                this._validateInput(cell);
            } else {
                editorInput.hide();
                editorInput.prop( "disabled", true );
                editorInput.parent().addClass('controls').removeClass('editable');
                // editorInput.val(false);
                // cell.model.set(this.options.sortOrderColumnName, false);
                editorInput.attr('name', 'disabledSortOrder_' + cell.model.cid);
            }
        }
    });

    return {
        /**
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         */
        init: function(deferred, options) {
            options.gridPromise.done(function(grid) {
                const validation = new SortOrder({
                    grid: grid,
                    options: options
                });
                deferred.resolve(validation);
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
