define(function(require) {
    'use strict';

    var BackendSelectHeaderCell;
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var template = require('tpl!oroproduct/templates/datagrid/backend-action-header-cell.html');
    var SelectHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    var ActionsPanel = require('oroproduct/js/app/datagrid/backend-actions-panel');
    var config = require('module').config();

    var shoppingListAddAction = config.shoppingListAddAction || {
        type: 'addproducts',
        data_identifier: 'product.id',
        frontend_type: 'add-products-mass',
        handler: 'oro_shopping_list.mass_action.add_products_handler',
        is_current: false,
        label: 'oro.shoppinglist.actions.add_to_shopping_list',
        name: 'oro_shoppinglist_frontend_addlineitemlist',
        route: 'oro_shopping_list_add_products_massaction',
        route_parameters: {},
        frontend_handle: 'ajax',
        confirmation: false,
        launcherOptions: {
            iconClassName: 'fa-shopping-cart'
        }
    };

    BackendSelectHeaderCell = SelectHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /**
         * @inheritDoc
         */
        actionsPanel: ActionsPanel,

        shoppingListCollection: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            BackendSelectHeaderCell.__super__.initialize.apply(this, arguments);
            this.selectState = options.selectState;
            this.massActionsInSticky = options.massActionsInSticky;
            this.listenTo(this.selectState, 'change', _.bind(_.debounce(this.canUse, 50), this));

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', _.bind(this._onShoppingListsRefresh, this));

                this._onShoppingListsRefresh();
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            delete this.shoppingListCollection;
            return BackendSelectHeaderCell.__super__.dispose.apply(this, arguments);
        },

        canUse: function(selectState) {
            this[(selectState.isEmpty() && selectState.get('inset')) ? 'disable' : 'enable' ]();
        },

        _onShoppingListsRefresh: function() {
            var datagrid = this.column.get('datagrid');
            datagrid.resetSelectionState();

            $.ajax({
                method: 'GET',
                url: routing.generate('oro_shopping_list_frontend_get_mass_actions'),
                success: function(availableMassActions) {
                    var newMassActions = {};

                    _.each(availableMassActions, function(massAction, title) {
                        newMassActions[title] = $.extend(true, {}, shoppingListAddAction, massAction, {
                            name: title
                        });
                    });

                    datagrid.setMassActions(newMassActions);
                }
            });

            this.render();
        },

        getTemplateData: function() {
            var data = BackendSelectHeaderCell.__super__.getTemplateData.call(this);

            data.massActionsInSticky = this.massActionsInSticky;
            data.actionsLength = this.subview('actionsPanel').actions.length;
            return data;
        },

        render: function() {
            this.$el.empty();
            this.renderActionsPanel();
            return this;
        },

        renderActionsPanel: function() {
            var panel = this.subview('actionsPanel');

            panel.massActionsInSticky =  this.massActionsInSticky;
            if (panel.haveActions()) {
                this.$el.append(this.getTemplateFunction()(this.getTemplateData()));
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
            }
        }
    });

    return BackendSelectHeaderCell;
});
