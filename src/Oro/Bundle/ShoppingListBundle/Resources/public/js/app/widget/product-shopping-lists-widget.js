define(function(require) {
    'use strict';

    var ProductShoppingListsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var Error = require('oroui/js/error');
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
            },
            update_api_accessor: {
                http_method: 'PUT',
                route: 'oro_api_shopping_list_frontend_put_line_item'
            }
        }),

        messages: {
            processingMessage: __('oro.form.inlineEditing.saving_progress'),
            preventWindowUnload: __('oro.form.inlineEditing.inline_edits')
        },

        elements: {
            edit: '[data-role="edit"]',
            decline: '[data-role="decline"]',
            lineItem: '[data-role="line-item"]',
            lineItemEdit: '[data-role="line-item-edit"]',
            lineItemView: '[data-role="line-item-view"]',
            addForm: '[data-role="add-form"]',
            addFormShoppingList: '[data-role="add-form-shopping-list"]',
            addFormQty: '[data-role="add-form-qty"]',
            addFormUnit: '[data-role="add-form-unit"]',
            addFormAccept: '[data-role="add-form-accept"]',
            addFormReset: '[data-role="add-form-reset"]'
        },

        elementsEvents: {
            edit: ['click', 'edit'],
            decline: ['click', 'decline'],
            addFormShoppingList: ['change', 'onAddFormShoppingListChange'],
            addFormUnit: ['change', 'onAddFormUnitChange'],
            addFormAccept: ['click', 'onAddFormAccept'],
            addFormReset: ['click', 'onAddFormReset']
        },

        modelAttr: {
            shopping_lists: []
        },

        modelEvents: {
            shopping_lists: ['change', 'render']
        },

        shoppingLists: [],

        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, [
                'dialogOptions', 'template', 'quantityComponentOptions'
            ]));

            this.shoppingLists = options.shoppingLists || [];

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
            mediator.off(null, null, this);
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
                shoppingListsCollection: this.shoppingLists,
                productUnits: this.model.get('product_units')
            })));

            return ProductShoppingListsWidget.__super__.render.apply(this, arguments);
        },

        onLineItemDelete: function(deleteData) {
            var shoppingLists = this.model.get('shopping_lists');
            shoppingLists = _.filter(shoppingLists, function(shoppingList, key) {
                shoppingList.line_items = _.filter(shoppingList.line_items, function(lineItem) {
                    return lineItem.id != deleteData.lineItemId;
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

            if (updateData.event) {
                this.toggleEditMode(updateData.event, 'disable');
            }
        },

        onAddFormReset: function() {
            var $form = this.getElement('addForm');

            $form[0].reset();
            $form.find('select').inputWidget('refresh');
        },

        onAddFormShoppingListChange: function(e) {
            var $addFormQty = this.getElement('addFormQty');
            var selectedShoppingList = this.getSelectedShoppingList();
            var selectedUnit = this.getSelectedUnit();
            var quantity = this.getMinimumQuantity(selectedUnit);

            if (selectedShoppingList && selectedShoppingList.line_items) {
                quantity = selectedShoppingList.line_items[0].quantity;
                this.setSelectedUnit(selectedShoppingList.line_items[0].unit);
            }

            $addFormQty.val(quantity);
        },

        onAddFormUnitChange: function(e) {
            var $addFormQty = this.getElement('addFormQty');
            var selectedShoppingList = this.getSelectedShoppingList();
            var selectedUnit = this.getSelectedUnit();
            var quantity = this.getMinimumQuantity(selectedUnit);

            if (selectedShoppingList && selectedShoppingList.line_items) {
                var selectedLineItem = _.findWhere(selectedShoppingList.line_items, {unit: selectedUnit});

                if(selectedLineItem && selectedLineItem.quantity) {
                    quantity = selectedLineItem.quantity;
                }
            }

            $addFormQty.val(quantity);
        },

        onAddFormAccept: function() {
            var $addFormQty = this.getElement('addFormQty');
            var selectedShoppingList = this.getSelectedShoppingList();
            var selectedUnit = this.getSelectedUnit();

            if (!selectedShoppingList) {
                return false;
            }
            
            if (selectedShoppingList.line_items) {
                var selectedLineItem = _.findWhere(selectedShoppingList.line_items, {unit: selectedUnit});

                if (selectedLineItem) {
                    this.updateLineItem(selectedLineItem, selectedShoppingList.id, parseInt($addFormQty.val(), 10));
                } else {
                    this.saveLineItem(selectedShoppingList.id, this.getSelectedUnit(), parseInt($addFormQty.val(), 10));
                }
            } else {
                this.saveLineItem(selectedShoppingList.id, this.getSelectedUnit(), parseInt($addFormQty.val(), 10));
            }
        },

        onSaveError: function(jqXHR) {
            var errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;

            var errors = [];
            switch (errorCode) {
                case 400:
                    var jqXHRerrors = jqXHR.responseJSON.errors.children;
                    for (var i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push.apply(errors, _.values(jqXHRerrors[i].errors));
                        }
                    }
                    if (!errors.length) {
                        errors.push(__('oro.ui.unexpected_error'));
                    }
                    break;
                case 403:
                    errors.push(__('You do not have permission to perform this action.'));
                    break;
                default:
                    errors.push(__('oro.ui.unexpected_error'));
            }

            _.each(errors, function(value) {
                mediator.execute('showFlashMessage', 'error', value);
            });
        },

        updateShoppingLists: function(shoppingLists, shoppingListId, lineItemId, newLineItem) {
            return _.map(shoppingLists, function(list) {
                if (list.id === parseInt(shoppingListId, 10)) {
                    list.line_items = this.updateLineItems(list.line_items, lineItemId, newLineItem);
                }
                return list;
            }, this);
        },

        saveLineItem: function(shoppingListId, lineItemUnit, newQty) {
            var urlOptions = {};
            var formData = this.getElement('addForm').serialize();
            
            if (this.model) {
                urlOptions.productId = this.model.get('id');
            }
            urlOptions.shoppingListId = shoppingListId;
            
            mediator.execute('showLoading');
            $.ajax({
                type: 'POST',
                url: routing.generate('oro_shopping_list_frontend_add_product', urlOptions),
                data: formData,
                success: function(response) {
                    mediator.execute('hideLoading');
                    if (response && response.message) {
                        mediator.execute(
                            'showFlashMessage', (response.hasOwnProperty('successful') ? 'success' : 'error'),
                            response.message
                        );
                    }
                    mediator.trigger('shopping-list:updated', response.shoppingList, response.product);
                },
                error: function(xhr) {
                    mediator.execute('hideLoading');
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        updateLineItem: function(lineItem, shoppingListId, newQty) {
            var updateApiAccessor = new ApiAccessor(_.extend(this.options.update_api_accessor, {
                default_route_parameters: {
                    id: lineItem.id
                }
            }));

            var modelData = {
                quantity: newQty,
                unit: lineItem.unit
            };
            
            var updatePromise = updateApiAccessor.send(modelData, {oro_product_frontend_line_item: modelData}, {}, {
                processingMessage: this.messages.processingMessage,
                preventWindowUnload: this.messages.preventWindowUnload
            });

            updatePromise
                .done(_.bind(this.onLineItemUpdate, this, {
                    shoppingListId: shoppingListId,
                    lineItemId: lineItem.id,
                    value: modelData
                }))
                .fail(_.bind(this.onSaveError, this));
        },

        updateLineItems: function(lineItems, lineItemId, newLineItem) {
            return _.map(lineItems, function(item) {
                if (item.id === parseInt(lineItemId, 10)) {
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
                    .closest(this.elements.lineItem)
                    .find(this.elements.lineItemView)
                    .addClass('hidden');
                $target
                    .closest(this.elements.lineItem)
                    .find(this.elements.lineItemEdit)
                    .removeClass('hidden');
            } else {
                $target
                    .closest(this.elements.lineItem)
                    .find(this.elements.lineItemEdit)
                    .addClass('hidden');
                $target
                    .closest(this.elements.lineItem)
                    .find(this.elements.lineItemView)
                    .removeClass('hidden');
            }
        },

        getSelectedShoppingListId: function() {
            return parseInt(this.getElement('addFormShoppingList').val(), 10) || 0;
        },

        getSelectedShoppingList: function() {
            var properties = {
                id: this.getSelectedShoppingListId()
            };

            if (!properties.id) {
                return;
            }

            return _.findWhere(this.model.get('shopping_lists'), properties) || _.findWhere(this.shoppingLists, properties);
        },

        getSelectedUnit: function() {
            return this.getElement('addFormUnit').val();
        },

        setSelectedUnit: function(unit) {
            this.getElement('addFormUnit').val(unit).inputWidget('refresh');
        },

        getMinimumQuantity: function(unit) {
            var quantity = 1;
            var prices = _.filter(this.model.get('prices') || {}, function(price) {
                return price.unit === unit;
            });
            if (prices.length) {
                quantity = _.min(prices, 'quantity').quantity;
            }
            return quantity;
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
