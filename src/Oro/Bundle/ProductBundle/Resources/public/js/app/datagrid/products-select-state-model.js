define(function(require) {
    'use strict';

    const _ = require('underscore');
    const SelectStateModel = require('orodatagrid/js/datagrid/select-state-model');

    const ProductsSelectStateModel = SelectStateModel.extend({
        /**
         * @inheritdoc
         */
        constructor: function ProductsSelectStateModel(attrs, options) {
            ProductsSelectStateModel.__super__.constructor.call(this, attrs, options);
        },

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
            const rows = this.get('rows');

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
