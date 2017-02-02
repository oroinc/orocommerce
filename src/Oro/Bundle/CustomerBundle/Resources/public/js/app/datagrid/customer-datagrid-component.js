define(function(require) {
    'use strict';

    var CustomerDataGidComponent;
    var DataGridComponent = require('orodatagrid/js/app/components/datagrid-component');
    var Grid = require('orocustomer/js/app/datagrid/customer-grid');

    CustomerDataGidComponent = DataGridComponent.extend({
        build: function(modules) {
            modules.GridView = Grid;
            CustomerDataGidComponent.__super__.build.apply(this, arguments);
        }
    });

    return CustomerDataGidComponent;
});
