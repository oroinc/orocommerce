define(function(require) {
    'use strict';

    var ShoppingListsMultipleEditWidget;
    var ContentWidget = require('orob2bshoppinglist/js/app/widget/content-widget');
    var ProductQuantityView = require('orob2bproduct/js/app/views/product-quantity-editable-view');
    var DeleteItemComponent = require('orob2bfrontend/js/app/components/delete-item-component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    ShoppingListsMultipleEditWidget = ContentWidget.extend({
        elements: {
            controlsContainer: '[data-name="shopping-lists-controls-container"]',
            unitsContainer: '[data-name="shopping-lists-units"]',
            modifyContainer: '[data-name="shopping-lists-modify"]',
            staticContainer: '[data-name="shopping-lists-static"]',
            accept: '[data-name="shopping-list-accept"]',
            decline: '[data-name="shopping-list-decline"]'
        },

        events: {
            'click [data-name="shopping-list-edit"]': 'edit',
            'click [data-name="shopping-list-delete"]': 'delete',
            'click [data-name="shopping-list-decline"]': 'decline',
            'click [data-name="shopping-lists-close"]': 'close'
        },

        template: '',

        quantityComponentOptions: null,
        deleteLineOptions: {
            removeClass: 'shopping-lists-units',
            confirmMessage: null,
            hasOwnTrigger: true
        },

        initialize: function(options) {
            if (!this.model) {
                return;
            }
            this.template = options.template;
            this.route = options.quantityComponentOptions.save_api_accessor.route;
            this.quantityComponentOptions = options.quantityComponentOptions;
            this.deleteLineOptions = _.extend(options.deleteLineOptions, this.deleteLineOptions);

            options.title = this.model.get('name');
            options.preventToRemoveModel = true;
            options.dialogOptions = {
                'modal': true,
                'resizable': false,
                'width': 580,
                'autoResize': true
            };

            mediator.on('frontend:item:delete',  this.onLineItemDelete, this);
            this.model.on('change:shopping_lists', this.render, this);

            ShoppingListsMultipleEditWidget.__super__.initialize.apply(this, arguments);
        },

        dispose: function() {
            ShoppingListsMultipleEditWidget.__super__.dispose.apply(this, arguments);
        },

        edit: function(e) {
            var $target = $(e.currentTarget);
            var $units = $target
                            .closest(this.elements.controlsContainer)
                            .find(this.elements.unitsContainer);

            _.each($units, function(unit) {
                var lineItemId = $(unit).data('line-item-id');
                var shoppingListId = $(unit).data('shopping-list-id');

                this.quantityComponentOptions.save_api_accessor = {
                    default_route_parameters: {
                        id: lineItemId
                    },
                    route: this.route
                };

                var productQuantityView = new ProductQuantityView(_.extend({
                    el: $(unit),
                    model: this.model,
                    $trigger: $units.find(this.elements.accept)
                }, this.quantityComponentOptions));

                this.listenTo(productQuantityView, 'product:quantity-unit:update',
                    _.bind(this.onLineItemUpdate(e, lineItemId, shoppingListId), this));
            }, this);

            this.toggleEditMode(e, 'enable');
        },

        onLineItemUpdate: function(e, lineItemId, shoppingListId) {
            return function(response) {
                _.extend(response, {'line_item_id': lineItemId});
                var oldShoppingLists = this.model.get('shopping_lists');
                var newShoppingLists = this.updateShoppingLists(oldShoppingLists, shoppingListId, lineItemId, response);

                this.model.set('shopping_lists', newShoppingLists);
                this.model.trigger('change:shopping_lists');
                this.toggleEditMode(e, 'disable');
            };
        },

        updateShoppingLists: function(shoppingLists, shoppingListId, lineItemId, newLineItem) {
            return _.map(shoppingLists, function(list) {
                if (list.hasOwnProperty('shopping_list_id') && list.shopping_list_id === shoppingListId) {
                    list.line_items = this.updateLineItems(list.line_items, lineItemId, newLineItem);
                }
                return list;
            }, this);
        },

        updateLineItems: function(lineItems, lineItemId, newLineItem) {
            return _.map(lineItems, function(item) {
                if (item.hasOwnProperty('line_item_id') && item.line_item_id === lineItemId) {
                    item.unit = newLineItem.unit;
                    item.quantity = newLineItem.quantity;
                }
                return item;
            });
        },

        delete: function(e) {
            var $target = $(e.currentTarget);
            var lineItemId = $target.data('line-item-id');

            this.deleteLineOptions._sourceElement = $target;
            this.deleteLineOptions.lineItemId = lineItemId;
            this.deleteLineOptions.url = routing.generate('orob2b_api_shopping_list_frontend_delete_line_item', {'id': lineItemId});
            new DeleteItemComponent(this.deleteLineOptions).deleteItem();
        },

        onLineItemDelete: function(data) {
            var shoppingLists = this.model.get('shopping_lists');

            _.each(shoppingLists, function(list, key) {
                if (!_.isEmpty(list.line_items) &&
                    !_.isEmpty(this.deleteLineItems(list.line_items, data))) {
                    list.line_items = this.deleteLineItems(list.line_items, data);
                } else {
                    shoppingLists.splice(key, 1);
                }
            }, this);

            this.model.set('shopping_lists', shoppingLists);
            this.model.trigger('change:shopping_lists');

            if (_.isEmpty(shoppingLists)) {
                this.close();
            }
        },

        deleteLineItems: function(items, data) {
            return _.reject(items, function(item) {
                return item.line_item_id === data.lineItemId
            });
        },

        toggleEditMode: function(e, key) {
            var $target = $(e.currentTarget);

            if (_.isString(key) && key === 'enable') {
                $target
                    .closest(this.elements.controlsContainer)
                    .find(this.elements.staticContainer)
                    .addClass('hidden');
                $target
                    .closest(this.elements.controlsContainer)
                    .find(this.elements.modifyContainer)
                    .removeClass('hidden');
            }

            if (_.isString(key) && key === 'disable') {
                $target
                    .closest(this.elements.controlsContainer)
                    .find(this.elements.modifyContainer)
                    .addClass('hidden');
                $target
                    .closest(this.elements.controlsContainer)
                    .find(this.elements.staticContainer)
                    .removeClass('hidden');
            }
        },

        setLabels: function(shoppingLists) {
            return _.map(shoppingLists, function(list) {
                list.line_items = this.setLineItemsLabels(list.line_items);
                return list;
            }, this);
        },

        setLineItemsLabels: function(lineItems) {
            return _.map(lineItems, function(item) {
                var label = __(
                    'orob2b.product.product_unit.' + item.unit + '.value.short',
                    {'count': item.quantity},
                    item.quantity);
                item.trans_unit = label.split(' ').pop();
                return item;
            });
        },

        decline: function(e) {
            this.toggleEditMode(e, 'disable');
        },
        
        close: function() {
            this.remove();
        },

        getPopupContent: function() {
            var popupData = {};

            popupData.shoppingLists = this.setLabels(this.model.get('shopping_lists'));
            popupData.productUnits = this.model.get('product_units');
            return this.template({popupData: popupData});
        },

        render: function() {
            this.options.content = this.getPopupContent();
            ShoppingListsMultipleEditWidget.__super__.render.apply(this, arguments);
        }
    });

    return ShoppingListsMultipleEditWidget;
});
