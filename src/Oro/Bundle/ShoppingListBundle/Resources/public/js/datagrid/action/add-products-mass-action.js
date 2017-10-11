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
            var attributes = ['id', 'unit', 'quantity'];
            var selectedIds  = _.map(selectionState.selectedIds, function(productModel) {
                return _.pick(productModel.toJSON(), attributes);
            });
            var params = {
                inset: selectionState.inset ? 1 : 0,
                values: JSON.stringify(selectedIds)
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
