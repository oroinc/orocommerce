define(function(require) {
    'use strict';

    const MassAction = require('oro/datagrid/action/mass-action');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');
    const loadModules = require('oroui/js/app/services/load-modules');

    /**
     * Add products to shopping list
     *
     * @export  oro/datagrid/action/add-products-mass-action
     * @class   oro.datagrid.action.AddProductsAction
     * @extends oro.datagrid.action.MassAction
     */
    const AddProductsAction = MassAction.extend({
        shoppingLists: null,

        /**
         * @inheritdoc
         */
        constructor: function AddProductsAction(options) {
            AddProductsAction.__super__.constructor.call(this, options);
            this.requestType = 'POST';
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            AddProductsAction.__super__.initialize.call(this, options);

            this.datagrid.on('action:add-products-mass:shopping-list', this._onAddProducts, this);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            this.datagrid.off(null, null, this);
            return AddProductsAction.__super__.dispose.call(this);
        },

        /**
         * Override function to change URL
         *
         * @inheritdoc
         */
        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }
            this.frontend_options = this.frontend_options || {};
            this.frontend_options.url = this.getLink();
            this.frontend_options.title = this.frontend_options.title || this.label;

            loadModules('oro/' + this.frontend_handle + '-widget', this._createHandleWidget.bind(this));
        },

        _createHandleWidget: function(WidgetType) {
            const widget = new WidgetType(this.frontend_options);
            widget.render();

            const datagrid = this.datagrid;
            const selectionState = datagrid.getSelectionState();

            widget.on('formSave', response => {
                datagrid.resetSelectionState(selectionState);
                this.listenToOnce(datagrid.massActions, 'reset', () => {
                    this._onSaveHandleWidget(response, datagrid);
                });
            });
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
            const selectionState = this.datagrid.getSelectionState();
            const collection = this.datagrid.collection;
            const stateKey = collection.stateHashKey();

            const unitsAndQuantities = {};
            _.each(selectionState.selectedIds, function(productModel) {
                const attributes = productModel.attributes;
                unitsAndQuantities[attributes.sku] = {};
                unitsAndQuantities[attributes.sku][attributes.unit] = attributes.quantity;
            });

            const selectedIds = _.map(selectionState.selectedIds, function(productModel) {
                return productModel.id;
            });

            let params = {
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
            const datagrid = this.datagrid;

            const models = _.reduce(data.products, function(newModels, product) {
                const productModel = datagrid.collection.get(product.id);

                if (productModel) {
                    newModels.push(productModel);
                }

                return newModels;
            }, []);

            datagrid.resetSelectionState();

            mediator.trigger('shopping-list:line-items:update-response', models, data);
            mediator.execute('hideLoading');
        }
    });

    return AddProductsAction;
});
