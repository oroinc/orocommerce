define(function(require) {
    'use strict';

    var ProductsSelectStateModel;
    var _ = require('underscore');
    var SelectStateModel = require('orodatagrid/js/datagrid/select-state-model');

    ProductsSelectStateModel = SelectStateModel.extend({
        addRow: function(model) {
            this.set('rows', _.uniq(this.get('rows').concat(model)));
            return this;
        },

        removeRow: function(model) {
            this.set('rows', _.filter(this.get('rows'), function(item) {
                return item.id !== model.get('id');
            }));
            return this;
        },

        hasRow: function(model) {
            var rows = this.get('rows');

            if (rows.length) {
                return _.find(rows, function(item) {
                    return _.isEqual(item.get('id'), model.get('id'));
                }, this) !== undefined;
            } else {
                return false;
            }
        }

    });

    return ProductsSelectStateModel;
});
