define(function(require) {
    'use strict';

    var ShoppingListsMultipleEditWidget;
    var DialogWidget = require('oro/dialog-widget');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var ProductQuantityView = require('orob2bproduct/js/app/views/product-quantity-editable-view');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    ShoppingListsMultipleEditWidget = DialogWidget.extend(_.extend({}, ElementsHelper, {
        options: $.extend(true, {}, DialogWidget.prototype.options, {
            preventModelRemoval: true,
            template: '',
            dialogOptions: {
                modal: true,
                resizable: false,
                width: 580,
                autoResize: true
            },
            quantityComponentOptions: {
                dataKey: '',
                enable: false,
                elements: {
                    quantity: '[name="product_qty"]',
                    unit: '[name="product_unit"]'
                },
                save_api_accessor: {
                    route: 'orob2b_api_shopping_list_frontend_put_line_item'
                },
                validation: {
                    showErrorsHandler: 'orob2bshoppinglist/js/shopping-list-item-errors-handler'
                }
            }
        }),

        elements: {
            edit: '[data-role="edit"]',
            decline: '[data-role="decline"]',
            accept: '[data-name="shopping-list-accept"]',
            controlsContainer: '[data-role="shopping-list"]',
            unitsContainer: '[data-name="shopping-lists-units"]',
            modifyContainer: '[data-name="shopping-lists-modify"]',
            staticContainer: '[data-name="shopping-lists-static"]'
        },

        elementsEvents: {
            edit: ['click', 'edit'],
            decline: ['click', 'decline']
        },

        modelAttr: {
            shopping_lists: []
        },

        modelEvents: {
            shopping_lists: ['change', 'render']
        },

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, [
                'dialogOptions', 'template', 'quantityComponentOptions'
            ]));

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.options.title = this.model.get('name');
            this.options.url = options.url = false;
            this.options.template = options.template = _.template(this.options.template);

            this.route = this.options.quantityComponentOptions.save_api_accessor.route;

            mediator.on('frontend:item:delete',  this.onLineItemDelete, this);

            ShoppingListsMultipleEditWidget.__super__.initialize.apply(this, arguments);
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function() {
            this.disposeElements();
            ShoppingListsMultipleEditWidget.__super__.dispose.apply(this, arguments);
        },

        delegateEvents: function() {
            ShoppingListsMultipleEditWidget.__super__.delegateEvents.apply(this, arguments);
            this.delegateElementsEvents();
        },

        undelegateEvents: function() {
            this.undelegateElementsEvents();
            return ShoppingListsMultipleEditWidget.__super__.undelegateEvents.apply(this, arguments);
        },

        render: function() {
            this.clearElementsCache();

            var shoppingLists = this.model.get('shopping_lists');
            if (_.isEmpty(shoppingLists)) {
                this.dispose();
                return;
            }

            this.setElement($(this.options.template({
                shoppingLists: shoppingLists,
                productUnits: this.model.get('product_units')
            })));

            return ShoppingListsMultipleEditWidget.__super__.render.apply(this, arguments);
        },

        onLineItemDelete: function(deleteData) {
            var shoppingLists = this.model.get('shopping_lists');

            _.each(shoppingLists, function(shoppingList, key) {
                shoppingList.line_items = _.filter(shoppingList.line_items, function(lineItem) {
                    return lineItem.line_item_id != deleteData.lineItemId;
                });
                if (_.isEmpty(shoppingList.line_items)) {
                    shoppingLists.splice(key, 1);
                }
            }, this);

            this.model.trigger('change:shopping_lists');
        },

        edit: function(e) {
            var $target = $(e.currentTarget);
            var $units = $target
                            .closest(this.elements.controlsContainer)
                            .find(this.elements.unitsContainer);

            _.each($units, function(unit) {
                var lineItemId = $(unit).data('line-item-id');
                var shoppingListId = $(unit).data('shopping-list-id');

                this.options.quantityComponentOptions.save_api_accessor = {
                    default_route_parameters: {
                        id: lineItemId
                    },
                    route: this.route
                };

                var productQuantityView = new ProductQuantityView(_.extend({
                    el: $(unit),
                    model: this.model,
                    $trigger: $units.find(this.elements.accept)
                }, this.options.quantityComponentOptions));

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

        toggleEditMode: function(e, key) {
            var $target = $(e.currentTarget);

            if (key === 'enable') {
                $target
                    .closest(this.elements.controlsContainer)
                    .find(this.elements.staticContainer)
                    .addClass('hidden');
                $target
                    .closest(this.elements.controlsContainer)
                    .find(this.elements.modifyContainer)
                    .removeClass('hidden');
            } else {
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

        decline: function(e) {
            this.toggleEditMode(e, 'disable');
        }
    }));

    return ShoppingListsMultipleEditWidget;
});
