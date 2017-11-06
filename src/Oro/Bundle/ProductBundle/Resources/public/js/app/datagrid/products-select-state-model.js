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
            return _.find(this.get('rows'), function(item) {
                return item.id !== model.get('id');
            }) !== undefined;
        }

    });

    return ProductsSelectStateModel;
});
