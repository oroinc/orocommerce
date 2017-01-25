define(function(require) {
    'use strict';

    var CustomerGrid;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var Grid = require('orodatagrid/js/datagrid/grid');
    var RefreshCollectionAction = require('oro/datagrid/action/refresh-collection-action');
    var ResetCollectionAction = require('oro/datagrid/action/reset-collection-action');

    CustomerGrid = Grid.extend({
        initialize: function(options) {
            _.extend(options.toolbarOptions, {
                actionsPanel: {
                    className: 'btn-group'
                }
            });
            _.extend(this.defaults.actionOptions.refreshAction.launcherOptions, {
                className: 'btn btn--default btn--size-s',
                icon: 'repeat',
                iconHideText: true
            });
            _.extend(this.defaults.actionOptions.resetAction.launcherOptions, {
                className: 'btn btn--default btn--size-s',
                icon: 'refresh',
                iconHideText: true
            });
            CustomerGrid.__super__.initialize.apply(this, arguments);
        }
    });

    return CustomerGrid;
});
