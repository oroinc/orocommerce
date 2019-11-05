define(['jquery', 'underscore', 'oroui/js/mediator', 'orolocale/js/formatter/number'
], function($, _, mediator, NumberFormatter) {
    'use strict';

    const LevelQuantity = function(options) {
        this.initialize(options);
    };

    _.extend(LevelQuantity.prototype, {
        /**
         * @property {Grid}
         */
        grid: null,

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
        },

        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function(options) {
            this.grid = options.grid;
            this.grid.refreshAction.execute();

            const inputSelector = options.options.metadata.options.cellSelection.selector;
            const quantityValidationOptions = $(inputSelector).first().data('level-quantity-options');
            this.options = _.defaults(quantityValidationOptions || {}, this.options);

            this._formatInitialQuantity();
            this._applyValidationToGrid();

            this.grid.collection.on('sync', _.bind(this._formatInitialQuantity, this));
            this.grid.collection.on('sync', _.bind(this._applyValidationToGrid, this));
            this.grid.collection.on('reset', _.bind(this._applyValidationToGrid, this));
        },

        /**
         * Format quantity to emulate number cell behaviour and value
         */
        _formatInitialQuantity: function() {
            const quantityColumn = this.options.quantityColumnName;
            // apply rounding
            _.each(this.grid.collection.fullCollection.models, function(model) {
                model.on('change:' + quantityColumn, function(model, value) {
                    // convert to numeric value to support correct grid sorting
                    if (!isNaN(value)) {
                        model.set(quantityColumn, NumberFormatter.unformat(value), {silent: true});
                    }
                });
                model.set(quantityColumn, NumberFormatter.formatDecimal(model.get(quantityColumn), {
                    grouping_used: false
                }));
            }, this);

            // render editable cells again to refresh data
            _.each(this.grid.body.rows, function(row) {
                _.each(row.cells, function(cell) {
                    if (cell.column.get('name') === quantityColumn) {
                        cell.render();
                    }
                }, this);
            }, this);
        },

        /**
         * Set validation to all rows and apply it to defined values
         */
        _applyValidationToGrid: function() {
            _.each(this.grid.body.rows, function(row) {
                _.each(row.cells, function(cell) {
                    if (cell.column.get('name') === this.options.quantityColumnName) {
                        this._applyValidationToCell(cell);
                    }
                }, this);
            }, this);
        },

        /**
         * @param {Backgrid.Cell} cell
         */
        _applyValidationToCell: function(cell) {
            const quantityConstraints = this.options.quantityConstraints;
            const constraintNames = this.constraintNames;
            const unitCode = cell.model.get(this.options.unitColumnName);
            const precision = this.options.unitPrecisions[unitCode] || 0;

            const constraints = {};
            if (quantityConstraints[constraintNames.range]) {
                constraints[constraintNames.range] = quantityConstraints[constraintNames.range];
            }
            if (precision > 0 && quantityConstraints[constraintNames.decimal]) {
                constraints[constraintNames.decimal] = quantityConstraints[constraintNames.decimal];
            } else if (quantityConstraints[this.constraintNames.integer]) {
                constraints[constraintNames.integer] = quantityConstraints[constraintNames.integer];
            }

            const editorInput = cell.$el.find(':input').first();
            editorInput.parent().addClass('controls').removeClass('editable');
            editorInput.attr('name', 'quantity_' + cell.model.cid);
            editorInput.data('validation', constraints);
            editorInput.valid();
        }
    });

    return {
        /**
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         */
        init: function(deferred, options) {
            options.gridPromise.done(function(grid) {
                const validation = new LevelQuantity({
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
