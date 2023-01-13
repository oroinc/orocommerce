define(function(require) {
    'use strict';

    const DialogWidget = require('oro/dialog-widget');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const UnitsUtil = require('oroproduct/js/app/units-util');
    const ApiAccessor = require('oroui/js/tools/api-accessor');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const _ = require('underscore');
    const $ = require('jquery');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');

    const ProductShoppingListsWidget = DialogWidget.extend(_.extend({}, ElementsHelper, {
        options: $.extend(true, {}, DialogWidget.prototype.options, {
            actionWrapperTemplate: _.template('<div class="action-wrapper"/>'),
            preventModelRemoval: true,
            template: '',
            dialogOptions: {
                modal: true,
                resizable: false,
                width: '100%',
                maxWidth: 580,
                autoResize: true
            },
            update_api_accessor: {
                http_method: 'PUT',
                route: 'oro_api_shopping_list_frontend_put_line_item'
            },
            singleUnitMode: false,
            singleUnitModeCodeVisible: false,
            configDefaultUnit: ''
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

        shoppingListCollection: null,

        /**
         * @inheritdoc
         */
        constructor: function ProductShoppingListsWidget(options) {
            ProductShoppingListsWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, [
                'dialogOptions',
                'template',
                'quantityComponentOptions',
                'singleUnitMode',
                'singleUnitModeCodeVisible',
                'configDefaultUnit'
            ]));

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.options.title = this.model.get('name');
            this.options.url = options.url = false;
            this.options.template = options.template = _.template(this.options.template);

            mediator.on('frontend:item:delete', this.onLineItemDelete, this);
            mediator.on('product:quantity-unit:update', this.onLineItemUpdate, this);

            ShoppingListCollectionService.shoppingListCollection.done((function(collection) {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this.render);
                ProductShoppingListsWidget.__super__.initialize.call(this, options);
            }).bind(this));
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
            if (this.disposed) {
                return;
            }

            this.disposeElements();
            delete this.shoppingListCollection;
            mediator.off(null, null, this);
            ProductShoppingListsWidget.__super__.dispose.call(this);
        },

        delegateEvents: function(events) {
            ProductShoppingListsWidget.__super__.delegateEvents.call(this, events);
            this.delegateElementsEvents();
        },

        undelegateEvents: function() {
            this.undelegateElementsEvents();
            return ProductShoppingListsWidget.__super__.undelegateEvents.call(this);
        },

        render: function() {
            this.clearElementsCache();

            const shoppingLists = this.model.get('shopping_lists').map((function(item) {
                const model = this.shoppingListCollection.get(item.id);
                if (!model) {
                    return false;
                }
                return _.extend({}, {
                    line_items: item.line_items,
                    href: routing.generate('oro_shopping_list_frontend_view', {id: item.id})
                }, model.toJSON());
            }).bind(this)).filter(function(item) {
                return !!item;
            });

            if (_.isEmpty(shoppingLists) || !this.shoppingListCollection) {
                this.dispose();
                return;
            }

            this.setElement($(this.options.template({
                shoppingLists: shoppingLists,
                shoppingListsCollection: this.shoppingListCollection,
                productUnits: UnitsUtil.getUnitsLabel(this.model),
                productUnitPrecisions: this.model.get('product_units'),
                unit: this.model.get('unit'),
                precision: this.model.get('product_units')[this.model.get('unit')],
                singleUnitMode: this.options.singleUnitMode,
                singleUnitModeCodeVisible: this.options.singleUnitModeCodeVisible,
                isProductApplySingleUnitMode: this.isProductApplySingleUnitMode.bind(this),
                QuantityHelper: QuantityHelper
            })));

            return ProductShoppingListsWidget.__super__.render.call(this);
        },

        isProductApplySingleUnitMode: function(productUnits) {
            if (this.options.singleUnitMode && productUnits.length === 1) {
                return productUnits[0] === this.options.configDefaultUnit;
            }

            return false;
        },

        onLineItemDelete: function(deleteData) {
            let shoppingLists = this.model.get('shopping_lists');
            shoppingLists = _.filter(shoppingLists, function(shoppingList, key) {
                shoppingList.line_items = _.filter(shoppingList.line_items, function(lineItem) {
                    return lineItem.id !== deleteData.lineItemId;
                });
                return !_.isEmpty(shoppingList.line_items);
            }, this);

            this.model.set('shopping_lists', shoppingLists, {silent: true});
            this.model.trigger('change:shopping_lists');

            this.shoppingListCollection.trigger('change');
        },

        onLineItemUpdate: function(updateData) {
            const updatedShoppingLists = this.updateShoppingLists(
                this.model.get('shopping_lists'),
                updateData.shoppingListId,
                updateData.lineItemId,
                updateData.value
            );

            this.model.set('shopping_lists', updatedShoppingLists, {silent: true});
            this.model.trigger('change:shopping_lists');
            this.shoppingListCollection.trigger('change', {
                refresh: true
            });

            if (updateData.event) {
                this.toggleEditMode(updateData.event, 'disable');
            }
        },

        onAddFormReset: function() {
            const $form = this.getElement('addForm');

            $form[0].reset();
            $form.find('select').inputWidget('refresh');
        },

        onAddFormShoppingListChange: function(e) {
            const $addFormQty = this.getElement('addFormQty');
            const selectedShoppingList = this.getSelectedShoppingList();
            const selectedUnit = this.getSelectedUnit();
            let quantity = this.getMinimumQuantity(selectedUnit);

            if (selectedShoppingList && selectedShoppingList.line_items) {
                quantity = selectedShoppingList.line_items[0].quantity;
                this.setSelectedUnit(selectedShoppingList.line_items[0].unit);
            }

            $addFormQty.val(QuantityHelper.formatQuantity(quantity));
        },

        onAddFormUnitChange: function(e) {
            const $addFormQty = this.getElement('addFormQty');
            const selectedShoppingList = this.getSelectedShoppingList();
            const selectedUnit = this.getSelectedUnit();
            let quantity = this.getMinimumQuantity(selectedUnit);

            if (selectedShoppingList && selectedShoppingList.line_items) {
                const selectedLineItem = _.findWhere(selectedShoppingList.line_items, {unit: selectedUnit});

                if (selectedLineItem && selectedLineItem.quantity) {
                    quantity = selectedLineItem.quantity;
                }
            }

            const precision = this.model.get('product_units')[selectedUnit] || 0;

            $addFormQty.data('precision', precision).inputWidget('refresh');
            $addFormQty.val(QuantityHelper.formatQuantity(quantity));
        },

        onAddFormAccept: function() {
            const $addFormQty = this.getElement('addFormQty');
            const selectedShoppingList = this.getSelectedShoppingList();
            const selectedUnit = this.getSelectedUnit();

            if (!selectedShoppingList) {
                return false;
            }

            const parsedValue = QuantityHelper.getQuantityNumberOrDefaultValue($addFormQty.val());
            if (selectedShoppingList.line_items) {
                const selectedLineItem = _.findWhere(selectedShoppingList.line_items, {unit: selectedUnit});

                if (selectedLineItem) {
                    this.updateLineItem(selectedLineItem, selectedShoppingList.id, parsedValue);
                } else {
                    this.saveLineItem(selectedShoppingList.id, this.getSelectedUnit(), parsedValue);
                }
            } else {
                this.saveLineItem(selectedShoppingList.id, this.getSelectedUnit(), parsedValue);
            }
        },

        onSaveError: function(jqXHR) {
            const errorCode = 'responseJSON' in jqXHR ? jqXHR.responseJSON.code : jqXHR.status;

            const errors = [];
            switch (errorCode) {
                case 400:
                    const jqXHRerrors = jqXHR.responseJSON.errors.children;
                    for (const i in jqXHRerrors) {
                        if (jqXHRerrors.hasOwnProperty(i) && jqXHRerrors[i].errors) {
                            errors.push(..._.values(jqXHRerrors[i].errors));
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
            const self = this;
            const urlOptions = {};
            const formData = this.getElement('addForm').serialize();

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
                        const isSuccessful = response.hasOwnProperty('successful') && response.successful;
                        mediator.execute(
                            'showFlashMessage',
                            isSuccessful ? 'success' : 'error',
                            response.message,
                            isSuccessful ? {namespace: 'shopping_list'} : {}
                        );
                    }

                    if (response.product && response.product.shopping_lists) {
                        self.model.set('shopping_lists', response.product.shopping_lists, {silent: true});
                        self.model.trigger('change:shopping_lists');
                        self.shoppingListCollection.trigger('change', {
                            refresh: true
                        });
                    }
                },
                error: function(xhr) {
                    mediator.execute('hideLoading');
                }
            });
        },

        updateLineItem: function(lineItem, shoppingListId, newQty) {
            const updateApiAccessor = new ApiAccessor(_.extend(this.options.update_api_accessor, {
                default_route_parameters: {
                    id: lineItem.id
                }
            }));

            const modelData = {
                quantity: newQty,
                unit: lineItem.unit
            };

            const modelDataInBackendFormat = _.clone(modelData);
            /**
             * We need to provide formatted quantity value to the backend
             * @see \Oro\Bundle\ProductBundle\Form\Type\QuantityType
             * @see \Oro\Bundle\ProductBundle\Form\DataTransformer\QuantityTransformer
             */
            modelDataInBackendFormat.quantity = QuantityHelper.formatQuantity(
                modelDataInBackendFormat.quantity,
                null,
                true
            );

            const updatePromise = updateApiAccessor.send(
                modelDataInBackendFormat,
                {oro_product_frontend_line_item: modelDataInBackendFormat},
                {},
                {
                    processingMessage: this.messages.processingMessage,
                    preventWindowUnload: this.messages.preventWindowUnload
                }
            );

            updatePromise
                .done(this.onLineItemUpdate.bind(this, {
                    shoppingListId: shoppingListId,
                    lineItemId: lineItem.id,
                    value: modelData
                }))
                .fail(this.onSaveError.bind(this));
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
            const $target = $(e.currentTarget);

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
            const properties = {
                id: this.getSelectedShoppingListId()
            };

            if (!properties.id) {
                return;
            }

            return _.findWhere(this.model.get('shopping_lists'), properties) ||
                this.shoppingListCollection.find(properties);
        },

        getSelectedUnit: function() {
            return this.getElement('addFormUnit').val();
        },

        setSelectedUnit: function(unit) {
            this.getElement('addFormUnit').val(unit).inputWidget('refresh');
        },

        getMinimumQuantity: function(unit) {
            let quantity = 1;
            const prices = _.filter(this.model.get('prices') || {}, function(price) {
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
