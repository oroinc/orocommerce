define(function(require) {
    'use strict';

    var WarehouseInventoryLevelsComponent;
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var NumberFormatter = require('orodatagrid/js/datagrid/formatter/number-formatter');

    WarehouseInventoryLevelsComponent = BaseComponent.extend({
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
            gridName: 'levels-grid',
            quantityColumnName: 'quantity',
            unitColumnName: 'code',
            unitPrecisions: {},
            quantityConstraints: {}
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.numberFormatter = new NumberFormatter();
            mediator.on('datagrid:rendered', this._onDatagridRendered, this);
        },

        /**
         * @param {Grid} grid
         */
        _onDatagridRendered: function(grid) {
            if (grid.name !== this.options.gridName) {
                return;
            }

            this.grid = grid;

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

            // do initial rounding
            _.each(this.grid.collection.fullCollection.models, function(model) {
                model.set(quantityColumn, this.numberFormatter.fromRaw(model.get(quantityColumn)));
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
            editorInput.attr('name', 'quantity_' + cell.model.cid);
            editorInput.data('validation', constraints);
            editorInput.valid();
        }
    });

    return WarehouseInventoryLevelsComponent;
});
