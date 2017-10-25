define([
    'oro/datagrid/action/mass-action',
    'oroui/js/mediator',
    'underscore'
], function(MassAction, mediator, _) {
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
         * @inheritdoc
         */
        initialize: function() {
            AddProductsAction.__super__.initialize.apply(this, arguments);

            this.listenTo(
                mediator,
                'widget_success:add_products_to_new_shopping_list_mass_action',
                this._onSuccess,
                this
            );
        },

        _onSuccess: function() {
            mediator.trigger('datagrid:doRefresh:' + this.datagrid.name);
        },

        /**
         * Get action parameters
         *
         * @returns {Object}
         * @private
         */
        getActionParameters: function() {
            var selectionState = this.datagrid.getSelectionState();
            var collection = this.datagrid.collection;
            var stateKey = collection.stateHashKey();

            var unitsAndQuantities = {};
            _.each(selectionState.selectedIds, function(productModel) {
                var attributes = productModel.attributes;
                unitsAndQuantities[attributes.sku] = {};
                unitsAndQuantities[attributes.sku][attributes.unit] = attributes.quantity;
            });

            var selectedIds = _.map(selectionState.selectedIds, function(productModel) {
                return productModel.id;
            });

            var params = {
                inset: selectionState.inset ? 1 : 0,
                values: selectedIds.join(','),
                units_and_quantities: JSON.stringify(unitsAndQuantities)
            };

            params[stateKey] = collection.stateHashValue();
            params = collection.processFiltersParams(params, null, 'filters');

            return params;
        },

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
