define(['jquery', 'underscore', 'oroui/js/mediator', 'orodatagrid/js/datagrid/formatter/number-formatter'
], function($, _, mediator, NumberFormatter) {
    'use strict';

    var LevelQuantity = function() {
        this.initialize.apply(this, arguments);
    };

    _.extend(LevelQuantity.prototype, {
        /**
         * @property {Grid}
         */
        grid: null,

        /**
         * @property {NumberFormatter}
         */
        numberFormatter: null,

        /**
         * @property {Object}
         */
        constraintNames: {
            range: 'Range',
            decimal: 'OroB2B\\Bundle\\ValidationBundle\\Validator\\Constraints\\Decimal',
            integer: 'OroB2B\\Bundle\\ValidationBundle\\Validator\\Constraints\\Integer'
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

            var inputSelector = options.options.metadata.options.cellSelection.selector;
            var quantityValidationOptions = $(inputSelector).first().data('level-quantity-options');
            this.options = _.defaults(quantityValidationOptions || {}, this.options);

            this.numberFormatter = new NumberFormatter();

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
            var quantityColumn = this.options.quantityColumnName;
            var numberFormatter = this.numberFormatter;

            // apply rounding
            _.each(this.grid.collection.fullCollection.models, function(model) {
                model.on('change:' + quantityColumn, function(model, value) {
                    // convert to numeric value to support correct grid sorting
                    if (!isNaN(value)) {
                        model.set(quantityColumn, numberFormatter.toRaw(value), {silent: true});
                    }
                });
                model.set(quantityColumn, numberFormatter.fromRaw(model.get(quantityColumn)));
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
            var quantityConstraints = this.options.quantityConstraints;
            var constraintNames = this.constraintNames;
            var unitCode = cell.model.get(this.options.unitColumnName);
            var precision = this.options.unitPrecisions[unitCode] || 0;

            var constraints = {};
            if (quantityConstraints[constraintNames.range]) {
                constraints[constraintNames.range] = quantityConstraints[constraintNames.range];
            }
            if (precision > 0 && quantityConstraints[constraintNames.decimal]) {
                constraints[constraintNames.decimal] = quantityConstraints[constraintNames.decimal];
            } else if (quantityConstraints[this.constraintNames.integer]) {
                constraints[constraintNames.integer] = quantityConstraints[constraintNames.integer];
            }

            var editorInput = cell.$el.find(':input').first();
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
                var validation = new LevelQuantity({
                    'grid': grid,
                    'options': options
                });
                deferred.resolve(validation);
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
