define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const ShoppingListCreateWidget = require('oro/shopping-list-create-widget');
    const ApiAccessor = require('oroui/js/tools/api-accessor');
    const routing = require('routing');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    /** @var QuantityHelper QuantityHelper **/
    const QuantityHelper = require('oroproduct/js/app/quantity-helper');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    const ProductAddToShoppingListView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            emptyMatrixAllowed: false,
            buttonTemplate: '',
            createNewButtonTemplate: '',
            removeButtonTemplate: '',
            shoppingListCreateEnabled: true,
            showSingleAddToShoppingListButton: true,
            buttonsSelector: '.add-to-shopping-list-button',
            quantityField: '[data-name="field__quantity"]',
            messages: {
                success: 'oro.frontend.shoppinglist.lineitem.updated.label'
            },
            save_api_accessor: {
                http_method: 'PUT',
                route: 'oro_api_shopping_list_frontend_put_line_item',
                form_name: 'oro_product_frontend_line_item'
            },
            shoppingListRoute: 'oro_shopping_list_frontend_update'
        },

        dropdownWidget: null,

        shoppingListCollection: null,

        modelAttr: {
            shopping_lists: []
        },

        rendered: false,

        /**
         * @inheritdoc
         */
        constructor: function ProductAddToShoppingListView(options) {
            ProductAddToShoppingListView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ProductAddToShoppingListView.__super__.initialize.call(this, options);
            this.deferredInitializeCheck(options, ['productModel', 'dropdownWidget']);
        },

        deferredInitialize: function(options) {
            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));

            this.dropdownWidget = options.dropdownWidget;
            this.$form = this.dropdownWidget.element.closest('form');

            this.initModel(options);

            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
            }
            if (this.options.removeButtonTemplate) {
                this.options.removeButtonTemplate = _.template(this.options.removeButtonTemplate);
            }
            if (this.options.createNewButtonTemplate) {
                this.options.createNewButtonTemplate = _.template(this.options.createNewButtonTemplate);
            }

            this.saveApiAccessor = new ApiAccessor(this.options.save_api_accessor);

            if (this.model) {
                this.model.on('change:unit', this._onUnitChanged, this);
                this.model.on('editLineItem', this._onEditLineItem, this);
            }

            this.$form.find(this.options.quantityField).on('keydown', this._onQuantityEnter.bind(this));

            ShoppingListCollectionService.shoppingListCollection.done(collection => {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this._onCollectionChange);
                this.render();
            });
        },

        initModel: function(options) {
            const modelAttr = _.each(options.modelAttr, function(value, attribute) {
                options.modelAttr[attribute] = value === 'undefined' ? undefined : value;
            }) || {};
            this.modelAttr = $.extend(true, {}, this.modelAttr, modelAttr);
            if (options.productModel) {
                this.model = options.productModel;
            }

            if (!this.model) {
                return;
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute) || modelAttr[attribute] !== undefined) {
                    this.model.set(attribute, value);
                }
            }, this);

            if (this.model.get('shopping_lists') === undefined) {
                this.model.set('shopping_lists', []);
            }
        },

        render: function() {
            this._setEditLineItem(null, true);

            const buttons = this._collectAllButtons();

            this._getContainer().html(buttons);
        },

        _onCollectionChange: function(...args) {
            if (args.length > 0 && args[0].shoppingListCreateEnabled !== undefined) {
                this.options.shoppingListCreateEnabled = args[0].shoppingListCreateEnabled;
            }
            this._setEditLineItem();

            const buttons = this._collectAllButtons();

            this._clearButtons();
            this._getContainer().prepend(buttons);
            this.dropdownWidget._renderButtons();
        },

        _clearButtons: function() {
            this._getContainer()
                .trigger('disposeLayout')
                .find(this.options.buttonsSelector)
                .remove();
        },

        _getContainer: function() {
            return this.dropdownWidget.element.find('.btn-group:first');
        },

        _collectAllButtons: function() {
            let buttons = [];

            if (!this.shoppingListCollection.length) {
                if (!this.options.showSingleAddToShoppingListButton) {
                    return [];
                }

                const $addNewButton = $(this.options.buttonTemplate({
                    id: null,
                    label: _.__('oro.shoppinglist.entity_label')
                })).find(this.options.buttonsSelector);

                return [$addNewButton];
            }

            const currentShoppingList = this.findCurrentShoppingList();
            this._addShippingListButtons(buttons, currentShoppingList);

            this.shoppingListCollection.sort();
            this.shoppingListCollection.each(function(model) {
                const shoppingList = model.toJSON();
                if (shoppingList.id === currentShoppingList.id) {
                    return;
                }

                this._addShippingListButtons(buttons, shoppingList);
            }, this);

            if (this.options.shoppingListCreateEnabled) {
                let $createNewButton = $(this.options.createNewButtonTemplate({id: null, label: ''}));
                $createNewButton = this.updateLabel($createNewButton, null);
                buttons.push($createNewButton);
            }

            if (buttons.length === 1) {
                const decoreClass = this.dropdownWidget.options.decoreClass || '';
                buttons = _.first(buttons).find(this.options.buttonsSelector).addClass(decoreClass);
            }

            return buttons;
        },

        _addShippingListButtons: function(buttons, shoppingList) {
            let $button = $(this.options.buttonTemplate(shoppingList));
            if (!this.model) {
                buttons.push($button);
                return;
            }
            const hasLineItems = this.findShoppingListByIdAndUnit(shoppingList, this.model.get('unit'));
            if (hasLineItems) {
                $button = this.updateLabel($button, shoppingList, hasLineItems);
            }
            buttons.push($button);

            if (hasLineItems) {
                const $removeButton = $(this.options.removeButtonTemplate(shoppingList));
                $removeButton.find('a, button').attr('data-intention', 'remove');
                buttons.push($removeButton);
            }
        },

        _afterRenderButtons: function() {
            this.initButtons();
            this.rendered = true;
        },

        initButtons: function() {
            const $buttons = this.findAllButtons();

            $buttons.each((i, btn) => {
                if (!$(btn).is('button')) {
                    $(btn).attr('role', 'button');
                }
            });
            $buttons
                .off('click' + this.eventNamespace())
                .on('click' + this.eventNamespace(), this.onClick.bind(this));
        },

        findDropdownButtons: function(filter) {
            const $el = this.dropdownWidget.element || this.dropdownWidget.dropdown;
            let $buttons = $el.find(this.options.buttonsSelector);
            if (filter) {
                $buttons = $buttons.filter(filter);
            }
            return $buttons;
        },

        findMainButton: function() {
            if (this.dropdownWidget.main && this.dropdownWidget.main.is(this.options.buttonsSelector)) {
                return this.dropdownWidget.main;
            }
            return $([]);
        },

        findAllButtons: function(filter) {
            let $buttons = this.findMainButton().add(this.findDropdownButtons());
            if (filter) {
                $buttons = $buttons.filter(filter);
            }
            return $buttons;
        },

        findShoppingListById: function(shoppingList) {
            if (!this.model) {
                return null;
            }
            return _.find(this.model.get('shopping_lists'), {id: shoppingList.id}) || null;
        },

        findShoppingListByIdAndUnit: function(shoppingList, unit) {
            const foundShoppingList = this.findShoppingListById(shoppingList);
            if (!foundShoppingList) {
                return null;
            }
            const hasUnits = _.find(foundShoppingList.line_items, {unit: unit});
            return hasUnits ? foundShoppingList : null;
        },

        findShoppingListLineItemByUnit: function(shoppingList, unit) {
            const foundShoppingList = this.findShoppingListById(shoppingList);
            if (!foundShoppingList) {
                return null;
            }
            return _.find(foundShoppingList.line_items, {unit: unit});
        },

        _onQuantityEnter: function(e) {
            const ENTER_KEY_CODE = 13;

            if (e.keyCode === ENTER_KEY_CODE) {
                this.model.set({
                    quantity: QuantityHelper.getQuantityNumberOrDefaultValue($(e.target).val(), NaN)
                });

                let mainButton = this.findMainButton();

                if (!mainButton.length) {
                    mainButton = this.findAllButtons();
                }

                mainButton.click();
            }
        },

        findCurrentShoppingList: function() {
            return this.shoppingListCollection.find({is_current: true}).toJSON() || null;
        },

        findShoppingList: function(id) {
            id = parseInt(id, 10);
            return this.shoppingListCollection.find({id: id}).toJSON() || null;
        },

        validate: function() {
            return this.dropdownWidget.validateForm();
        },

        onClick: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            if ($button.data('disabled')) {
                return false;
            }
            const url = $button.data('url');
            const intention = $button.data('intention');
            const formData = this.$form.serialize();

            const urlOptions = {
                shoppingListId: $button.data('shoppinglist').id
            };

            let isValidProduct = true;

            if (this.model) {
                urlOptions.productId = this.model.get('id');
                isValidProduct = urlOptions.productId !== 0;
                if (this.model.has('parentProduct')) {
                    urlOptions.parentProductId = this.model.get('parentProduct');
                }
            }

            if (!this.validate() || !isValidProduct) {
                return;
            }

            if (intention === 'new') {
                this._createNewShoppingList(url, urlOptions, formData);
            } else if (intention === 'update') {
                this._saveLineItem(url, urlOptions, formData);
            } else if (intention === 'remove') {
                this._removeLineItem(url, urlOptions, formData);
            } else {
                this._addLineItem(url, urlOptions, formData);
            }
        },

        updateLabel: function($button, shoppingList, hasLineItems) {
            let label;
            let intention;

            if (shoppingList && hasLineItems) {
                label = _.__('oro.shoppinglist.actions.update_shopping_list', {
                    shoppingList: shoppingList.label
                });
                intention = 'update';
            } else if (!shoppingList) {
                label = _.__('oro.shoppinglist.widget.add_to_new_shopping_list');
                intention = 'new';
            } else {
                label = _.__('oro.shoppinglist.actions.add_to_shopping_list', {
                    shoppingList: shoppingList.label
                });
                intention = 'add';
            }

            const $els = $button.find('a, button');
            const $icon = $button.find('.fa').clone();

            $els
                .text(label)
                .attr('data-intention', intention);

            if ($icon.length) {
                $els.prepend($icon);
            }

            return $button;
        },

        _setEditLineItem: function(lineItemId, setFirstLineItem) {
            this.editLineItem = null;

            if (!this.model || !this.shoppingListCollection.length) {
                return;
            }

            const currentShoppingListInCollection = this.findCurrentShoppingList();
            const currentShoppingListInModel = this.findShoppingListById(currentShoppingListInCollection);

            if (!currentShoppingListInModel) {
                return;
            }

            if (lineItemId) {
                this.editLineItem = _.findWhere(currentShoppingListInModel.line_items, {id: lineItemId});
            } else if (setFirstLineItem) {
                this.editLineItem = currentShoppingListInModel.line_items[0] || null;
            } else if (!this.model.get('quantity_changed_manually')) {
                this.editLineItem = _.findWhere(
                    currentShoppingListInModel.line_items, {unit: this.model.get('unit')}
                ) || null;
            }

            // Local variable used because this.editLineItem is set to null in model change:unit listener
            const editLineItem = this.editLineItem;
            if (editLineItem) {
                // quantity precision depend on unit, set unit first
                this.model.set('unit', editLineItem.unit);
                this.model.set('quantity', editLineItem.quantity);
                this.model.set('quantity_changed_manually', true);// prevent quantity change in other components
            }
        },

        _createNewShoppingList: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            const dialog = new ShoppingListCreateWidget({});
            dialog.on('formSave', response => {
                urlOptions.shoppingListId = response.savedId;
                this._addLineItem(url, urlOptions, formData);
            });
            dialog.render();
        },

        _addProductToShoppingList: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            const self = this;

            mediator.execute('showLoading');
            $.ajax({
                type: 'POST',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    mediator.trigger('shopping-list:line-items:update-response', self.model, response);
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        },

        _removeProductFromShoppingList: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            const self = this;

            mediator.execute('showLoading');
            $.ajax({
                type: 'DELETE',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    mediator.trigger('shopping-list:line-items:update-response', self.model, response);
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        },

        _onUnitChanged: function() {
            this._setEditLineItem();
            if (this.rendered) {
                this._onCollectionChange();
            }
        },

        _onEditLineItem: function(lineItemId) {
            this._setEditLineItem(lineItemId);
            this.model.trigger('focus:quantity');
        },

        _addLineItem: function(url, urlOptions, formData) {
            this._addProductToShoppingList(url, urlOptions, formData);
        },

        _removeLineItem: function(url, urlOptions, formData) {
            this._removeProductFromShoppingList(url, urlOptions, formData);
        },

        _saveLineItem: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            mediator.execute('showLoading');

            const shoppingList = this.findShoppingList(urlOptions.shoppingListId);
            const lineItem = this.findShoppingListLineItemByUnit(shoppingList, this.model.get('unit'));

            const savePromise = this.saveApiAccessor.send({
                id: lineItem.id
            }, {
                quantity: QuantityHelper.formatQuantity(this.model.get('quantity')),
                unit: this.model.get('unit')
            });

            savePromise
                .done(response => {
                    lineItem.quantity = response.quantity;
                    lineItem.unit = response.unit;
                    this.shoppingListCollection.trigger('change', {
                        refresh: true
                    });
                    const messageOptions = {namespace: 'shopping_list'};
                    const flashMsg = __(this.options.messages.success, {
                        shoppinglistPath: routing.generate(this.options.shoppingListRoute, {id: shoppingList.id}),
                        shoppinglistLabel: _.escape(shoppingList.label)
                    });
                    mediator.execute('showFlashMessage', 'success', flashMsg, messageOptions);
                })
                .always(() => {
                    mediator.execute('hideLoading');
                });
        },

        dispose: function(options) {
            delete this.dropdownWidget;
            delete this.modelAttr;
            delete this.shoppingListCollection;
            delete this.editLineItem;
            delete this.$form;

            mediator.off(null, null, this);
            if (this.model) {
                this.model.off(null, null, this);
            }

            ProductAddToShoppingListView.__super__.dispose.call(this);
        }
    }));

    return ProductAddToShoppingListView;
});
