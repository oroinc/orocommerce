define(function(require) {
    'use strict';

    var AddProductsAction;
    var MassAction = require('oro/datagrid/action/mass-action');
    var mediator = require('oroui/js/mediator');
    var ShoppingListCreate = require('oro/shopping-list-create-widget');
    var  _ = require('underscore');
    var $  = require('jquery');

    /**
     * Add products to shopping list
     *
     * @export  oro/datagrid/action/add-products-mass-action
     * @class   oro.datagrid.action.AddProductsAction
     * @extends oro.datagrid.action.MassAction
     */
    AddProductsAction = MassAction.extend({
        shoppingLists: null,

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            AddProductsAction.__super__.initialize.apply(this, arguments);

            if (this.route_parameters.actionName === 'oro_shoppinglist_frontend_addlineitemnew') {
                this.listenTo(
                    mediator,
                    'widget_success:add_products_to_new_shopping_list_mass_action',
                    _.bind(this.onAddProductsToNewShoppingListMassActionSuccess, this),
                    this
                );
            }
        },

        /**
         * @param {object} data
         */
        onAddProductsToNewShoppingListMassActionSuccess: function(data) {
            data.label = $(data._sourceElement).find('.form-field-label').val();
            this._updateShoppingListsData(data);
        },

        /**
         * @param {object} data
         * @private
         */
        _updateShoppingListsData: function(data) {
            var widget = new ShoppingListCreate({});
            widget.onFormSave(data);
            widget.dispose();
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
        }
    });

    return AddProductsAction;
});
