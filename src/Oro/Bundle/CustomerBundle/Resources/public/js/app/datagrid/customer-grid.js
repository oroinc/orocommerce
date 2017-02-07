define(function(require) {
    'use strict';

    var CustomerGrid;
    var _ = require('underscore');
    var Grid = require('orodatagrid/js/datagrid/grid');

    CustomerGrid = Grid.extend({
        /** @property {String} */
        className: 'oro-datagrid customer-datagrid',

        /** @property */
        template: require('tpl!orocustomer/templates/datagrid/grid.html'),

        /**
         * Initialize grid
         */
        initialize: function(options) {
            _.extend(options.toolbarOptions, {
                actionsPanel: {
                    className: 'btn-group not-expand customer-datagrid__panel'
                }
            });
            _.extend(options.themeOptions, {
                actionsDropdown: 'auto'
            });
            _.extend(this.defaults.actionOptions.refreshAction.launcherOptions, {
                className: 'btn btn--default btn--size-s',
                icon: 'repeat fa--no-offset',
                iconHideText: true
            });
            _.extend(this.defaults.actionOptions.resetAction.launcherOptions, {
                className: 'btn btn--default btn--size-s',
                icon: 'refresh fa--no-offset',
                iconHideText: true
            });
            CustomerGrid.__super__.initialize.apply(this, arguments);
        }
    });

    return CustomerGrid;
});
