define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const routing = require('routing');
    const template = require('tpl-loader!oroproduct/templates/datagrid/backend-action-header-cell.html');
    const ActionHeaderCell = require('orodatagrid/js/datagrid/header-cell/action-header-cell');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    const ActionsPanel = require('oroproduct/js/app/datagrid/backend-actions-panel');
    const config = require('module-config').default(module.id);

    const shoppingListAddAction = config.shoppingListAddAction || {
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

    const BackendActionHeaderCell = ActionHeaderCell.extend({
        /** @property */
        autoRender: true,

        /** @property */
        className: 'product-action',

        /** @property */
        tagName: 'div',

        /** @property */
        template: template,

        /**
         * @inheritdoc
         */
        actionsPanel: ActionsPanel,

        shoppingListCollection: null,

        /**
         * @inheritdoc
         */
        constructor: function BackendSelectHeaderCell(options) {
            BackendSelectHeaderCell.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            BackendActionHeaderCell.__super__.initialize.call(this, options);
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
         * @inheritdoc
         */
        dispose: function() {
            delete this.shoppingListCollection;
            return BackendActionHeaderCell.__super__.dispose.call(this);
        },

        canUse: function(selectState) {
            this[(selectState.isEmpty() && selectState.get('inset')) ? 'disable' : 'enable']();
        },

        _onShoppingListsRefresh: function() {
            const datagrid = this.column.get('datagrid');
            datagrid.resetSelectionState();

            $.ajax({
                method: 'GET',
                url: routing.generate('oro_shopping_list_frontend_get_mass_actions'),
                success: function(availableMassActions) {
                    const newMassActions = {};

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
            const data = BackendActionHeaderCell.__super__.getTemplateData.call(this);

            data.massActionsInSticky = this.massActionsInSticky;
            data.actionsLength = this.subview('actionsPanel').actions.length;
            return data;
        },

        render: function() {
            this.$el.empty();
            this.renderActionsPanel();
            this.canUse(this.selectState);
            return this;
        },

        renderActionsPanel: function() {
            const panel = this.subview('actionsPanel');

            panel.massActionsInSticky = this.massActionsInSticky;
            if (panel.haveActions()) {
                this.$el.append(this.getTemplateFunction()(this.getTemplateData()));
                panel.setElement(this.$('[data-action-panel]'));
                panel.render();
            }
        }
    });

    return BackendActionHeaderCell;
});
