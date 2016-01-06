/*jslint nomen:true*/
/*global define*/
define([
    'oro/datagrid/action/add-products-mass-action',
    'oroui/js/mediator',
    'oroui/js/widget-manager'
], function(AddProductsAction, mediator, widgetManager) {
    'use strict';

    /**
     * Add products to new shopping list
     *
     * @export  oro/datagrid/action/add-products-to-newmass-action
     * @class   oro.datagrid.action.AddProductsToNewAction
     * @extends oro/datagrid/action/add-products-mass-action
     */
    var AddProductsToNewAction = AddProductsAction.extend({

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            AddProductsToNewAction.__super__.initialize.apply(this, arguments);

            mediator.on('widget_success:add_prodiucts_to_new_shopping_list_mass_action', this._refreshGrid);
        },

        /**
         * @private
         */
        _refreshGrid: function() {
            widgetManager.getWidgetInstanceByAlias(
                'frontend-products-grid-widget',
                function(widget) {
                    widget.render();
                }
            );
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('widget_success:add_prodiucts_to_new_shopping_list_mass_action');

            AddProductsToNewAction.__super__.dispose.call(this);
        }
    });

    return AddProductsToNewAction;
});
