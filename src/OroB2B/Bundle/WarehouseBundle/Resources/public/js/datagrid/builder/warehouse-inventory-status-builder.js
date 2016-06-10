define(['jquery', 'underscore'], function ($, _) {
    'use strict';

    var InventoryStatus = function() {
        this.initialize.apply(this, arguments);
    };

    _.extend(InventoryStatus.prototype, {
        /**
         * @property {Grid}
         */
        datagrid: null,

        /**
         * @property {Object}
         */
        statusMetadata: null,

        /**
         * @property {Object}
         */
        options: {
            statusColumnName: 'inventory_status',
            productKey: 'productId'
        },

        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function(options) {
            var self = this;

            self.datagrid = options.grid;
            self.statusMetadata = _.find(options.options.metadata.columns, function (column) {
                return column.name === self.options.statusColumnName;
            });

            this._reloadInventoryStatus();
        },

        /**
         * Reload inventory statuses when one of them changes.
         */
        _reloadInventoryStatus: function() {
            var self = this;
            var statusColumn = self.options.statusColumnName;

            _.each(this.datagrid.collection.models, function(model) {
                model.on('change:' + statusColumn, function(model, value) {
                    self._updateInventoryStatus(model, value);
                });
           });
        },

        /**
         * Update all inventory statuses with the same options.productkey as the model.
         * @param {Object} model
         * @param {string} value
         */
        _updateInventoryStatus: function(model, value) {
            var self = this;
            var columnValue = self.statusMetadata.choices[value];

            if (typeof columnValue !== 'undefined') {
                _.each(this.datagrid.body.rows, function(row) {
                    if (row.model.get(self.options.productKey) === model.get(self.options.productKey)
                        && row.model.get(self.options.statusColumnName) != columnValue
                    ) {
                        row.model.set(self.options.statusColumnName, columnValue);
                        row.render();
                    }
                })
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
                var validation = new InventoryStatus({
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
