define(function(require) {
    'use strict';

    var AddProductsAction;
    var MassAction = require('oro/datagrid/action/mass-action');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

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
         * @inheritDoc
         */
        initialize: function(options) {
            AddProductsAction.__super__.initialize.apply(this, arguments);

            this.datagrid.on('action:add-products-mass:shopping-list', this._onAddProducts, this);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            this.datagrid.off(null, null, this);
            return AddProductsAction.__super__.dispose.apply(this, arguments);
        },

        /**
         * Override function to change URL
         *
         * @inheritDoc
         */
        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }
            this.frontend_options = this.frontend_options || {};
            this.frontend_options.url = this.getLink();
            this.frontend_options.title = this.frontend_options.title || this.label;

            require(['oro/' + this.frontend_handle + '-widget'], _.bind(this._createHandleWidget, this));
        },

        _createHandleWidget: function(WidgetType) {
            var widget = new WidgetType(this.frontend_options);
            widget.render();

            var datagrid = this.datagrid;
            var selectionState = datagrid.getSelectionState();

            widget.on('formSave', _.bind(function(response) {
                datagrid.resetSelectionState(selectionState);
                this.listenToOnce(datagrid.massActions, 'reset', function() {
                    this._onSaveHandleWidget(response, datagrid);
                });
            }, this));
        },

        _onSaveHandleWidget: function(response, datagrid) {
            datagrid.trigger('action:add-products-mass:shopping-list', response.savedId);
        },

        _onAddProducts: function(shoppingListId) {
            if (this.route_parameters.shoppingList === shoppingListId) {
                this.run({});
            }
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

        _handleAjax: function() {
            if (this.dispatched) {
                return;
            }

            mediator.execute('showLoading');
            this._doAjaxRequest();
        },

        _onAjaxSuccess: function(data, textStatus, jqXHR) {
            var datagrid = this.datagrid;

            var models = _.map(data.products, function(product) {
                return datagrid.collection.get(product.id);
            });

            datagrid.resetSelectionState();

            mediator.trigger('shopping-list:line-items:update-response', models, data);
            mediator.execute('hideLoading');
        }
    });

    return AddProductsAction;
});
