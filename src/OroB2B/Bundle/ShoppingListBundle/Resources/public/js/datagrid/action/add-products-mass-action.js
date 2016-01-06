/*jslint nomen:true*/
/*global define*/
define([
    'oro/datagrid/action/mass-action',
    'oroui/js/mediator'
], function(MassAction, mediator) {
    'use strict';

    var AddProductsAction;

    /**
     * Add products to shopping list
     *
     * @export  oro/datagrid/action/add-products-mass-action
     * @class   oro.datagrid.action.AddProductsAction
     * @extends oro.datagrid.action.MassAction
     */
    AddProductsAction = MassAction.extend({

        /**
         * @private
         */
        _checkSelectionState: function() {
            var selectionState = this.datagrid.getSelectionState();
            var models = selectionState.selectedModels;
            var length = 0;
            var reason;

            for (var key in models) {
                if (models.hasOwnProperty(key)) {
                    length++;
                }
            }
            if (!length) {
                reason = AddProductsAction.__super__.defaultMessages.empty_selection;
            }

            mediator.trigger('frontend:shoppinglist:add-widget-requested-response', {cnt: length, reason: reason});
        }
    });

    return AddProductsAction;
});
