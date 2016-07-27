define(function(require) {
    'use strict';

    var ProductShoppingListsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var __ = require('orotranslation/js/translator');
    var _ = require('underscore');
    var $ = require('jquery');

    ProductShoppingListsWidget = DialogWidget.extend(_.extend({}, ElementsHelper, {
        options: $.extend(true, {}, DialogWidget.prototype.options, {
            preventModelRemoval: true,
            template: '',
            dialogOptions: {
                modal: true,
                resizable: false,
                width: 580,
                autoResize: true
            }
        }),

        elements: {
            edit: '[data-role="edit"]',
            decline: '[data-role="decline"]',
            controlsContainer: '[data-role="shopping-list"]',
            modifyContainer: '[data-role="shopping-lists-modify"]',
            staticContainer: '[data-role="shopping-lists-static"]'
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

            mediator.on('frontend:item:delete',  this.onLineItemDelete, this);
            mediator.on('product:quantity-unit:update', this.onLineItemUpdate, this);

            ProductShoppingListsWidget.__super__.initialize.apply(this, arguments);
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
            ProductShoppingListsWidget.__super__.dispose.apply(this, arguments);
        },

        delegateEvents: function() {
            ProductShoppingListsWidget.__super__.delegateEvents.apply(this, arguments);
            this.delegateElementsEvents();
        },

        undelegateEvents: function() {
            this.undelegateElementsEvents();
            return ProductShoppingListsWidget.__super__.undelegateEvents.apply(this, arguments);
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

            return ProductShoppingListsWidget.__super__.render.apply(this, arguments);
        },

        onLineItemDelete: function(deleteData) {
            var shoppingLists = this.model.get('shopping_lists');
            shoppingLists = _.filter(shoppingLists, function(shoppingList, key) {
                shoppingList.line_items = _.filter(shoppingList.line_items, function(lineItem) {
                    return lineItem.line_item_id != deleteData.lineItemId;
                });
                return !_.isEmpty(shoppingList.line_items);
            }, this);

            this.model.set('shopping_lists', shoppingLists, {silent: true});
            this.model.trigger('change:shopping_lists');
        },

        onLineItemUpdate: function(updateData) {
            var updatedShoppingLists = this.updateShoppingLists(
                this.model.get('shopping_lists'),
                updateData.shoppingListId,
                updateData.lineItemId,
                updateData.value
            );

            this.model.set('shopping_lists', updatedShoppingLists);
            this.model.trigger('change:shopping_lists');
            this.toggleEditMode(updateData.event, 'disable');
        },

        updateShoppingLists: function(shoppingLists, shoppingListId, lineItemId, newLineItem) {
            return _.map(shoppingLists, function(list) {
                if (list.shopping_list_id === parseInt(shoppingListId, 10)) {
                    list.line_items = this.updateLineItems(list.line_items, lineItemId, newLineItem);
                }
                return list;
            }, this);
        },

        updateLineItems: function(lineItems, lineItemId, newLineItem) {
            return _.map(lineItems, function(item) {
                if (item.line_item_id === parseInt(lineItemId, 10)) {
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

        edit: function(e) {
            this.toggleEditMode(e, 'enable');
        },

        decline: function(e) {
            this.toggleEditMode(e, 'disable');
        }
    }));

    return ProductShoppingListsWidget;
});
