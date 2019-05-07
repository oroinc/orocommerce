define(function(require) {
    'use strict';

    var ProductAddToShoppingListView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var ShoppingListCreateWidget = require('oro/shopping-list-create-widget');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    ProductAddToShoppingListView = BaseView.extend(_.extend({}, ElementsHelper, {
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
                success: 'oro.form.inlineEditing.successMessage'
            },
            save_api_accessor: {
                http_method: 'PUT',
                route: 'oro_api_shopping_list_frontend_put_line_item',
                form_name: 'oro_product_frontend_line_item'
            }
        },

        dropdownWidget: null,

        shoppingListCollection: null,

        modelAttr: {
            shopping_lists: []
        },

        rendered: false,

        /**
         * @inheritDoc
         */
        constructor: function ProductAddToShoppingListView() {
            ProductAddToShoppingListView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ProductAddToShoppingListView.__super__.initialize.apply(this, arguments);
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

            this.$form.find(this.options.quantityField).on('keydown', _.bind(this._onQuantityEnter, this));

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this._onCollectionChange);
                this.render();
            }, this));
        },

        initModel: function(options) {
            var modelAttr = _.each(options.modelAttr, function(value, attribute) {
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

            var buttons = this._collectAllButtons();

            this._getContainer().html(buttons);
        },

        _onCollectionChange: function() {
            if (arguments.length > 0 && arguments[0].shoppingListCreateEnabled !== undefined) {
                this.options.shoppingListCreateEnabled = arguments[0].shoppingListCreateEnabled;
            }
            this._setEditLineItem();

            var buttons = this._collectAllButtons();

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
            var buttons = [];

            if (!this.shoppingListCollection.length) {
                if (!this.options.showSingleAddToShoppingListButton) {
                    return [];
                }

                var $addNewButton = $(this.options.buttonTemplate({
                    id: null,
                    label: _.__('oro.shoppinglist.entity_label')
                })).find(this.options.buttonsSelector);

                return [$addNewButton];
            }

            var currentShoppingList = this.findCurrentShoppingList();
            this._addShippingListButtons(buttons, currentShoppingList);

            this.shoppingListCollection.sort();
            this.shoppingListCollection.each(function(model) {
                var shoppingList = model.toJSON();
                if (shoppingList.id === currentShoppingList.id) {
                    return;
                }

                this._addShippingListButtons(buttons, shoppingList);
            }, this);

            if (this.options.shoppingListCreateEnabled) {
                var $createNewButton = $(this.options.createNewButtonTemplate({id: null, label: ''}));
                $createNewButton = this.updateLabel($createNewButton, null);
                buttons.push($createNewButton);
            }

            if (buttons.length === 1) {
                var decoreClass = this.dropdownWidget.options.decoreClass || '';
                buttons = _.first(buttons).find(this.options.buttonsSelector).addClass(decoreClass);
            }

            return buttons;
        },

        _addShippingListButtons: function(buttons, shoppingList) {
            var $button = $(this.options.buttonTemplate(shoppingList));
            if (!this.model) {
                buttons.push($button);
                return;
            }
            var hasLineItems = this.findShoppingListByIdAndUnit(shoppingList, this.model.get('unit'));
            if (hasLineItems) {
                $button = this.updateLabel($button, shoppingList, hasLineItems);
            }
            buttons.push($button);

            if (hasLineItems) {
                var $removeButton = $(this.options.removeButtonTemplate(shoppingList));
                $removeButton.find('a').attr('data-intention', 'remove');
                buttons.push($removeButton);
            }
        },

        _afterRenderButtons: function() {
            this.initButtons();
            this.rendered = true;
        },

        initButtons: function() {
            this.findAllButtons()
                .off('click' + this.eventNamespace())
                .on('click' + this.eventNamespace(), _.bind(this.onClick, this));
        },

        findDropdownButtons: function(filter) {
            var $el = this.dropdownWidget.element || this.dropdownWidget.dropdown;
            var $buttons = $el.find(this.options.buttonsSelector);
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
            var $buttons = this.findMainButton().add(this.findDropdownButtons());
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
            var foundShoppingList = this.findShoppingListById(shoppingList);
            if (!foundShoppingList) {
                return null;
            }
            var hasUnits = _.find(foundShoppingList.line_items, {unit: unit});
            return hasUnits ? foundShoppingList : null;
        },

        findShoppingListLineItemByUnit: function(shoppingList, unit) {
            var foundShoppingList = this.findShoppingListById(shoppingList);
            if (!foundShoppingList) {
                return null;
            }
            return _.find(foundShoppingList.line_items, {unit: unit});
        },

        _onQuantityEnter: function(e) {
            var ENTER_KEY_CODE = 13;

            if (e.keyCode === ENTER_KEY_CODE) {
                this.model.set({
                    quantity: parseInt($(e.target).val(), 10)
                });

                var mainButton = this.findMainButton();

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

        validate: function(intention, url, urlOptions, formData) {
            return this.dropdownWidget.validateForm();
        },

        onClick: function(e) {
            var $button = $(e.currentTarget);
            if ($button.data('disabled')) {
                return false;
            }
            var url = $button.data('url');
            var intention = $button.data('intention');
            var formData = this.$form.serialize();

            var urlOptions = {
                shoppingListId: $button.data('shoppinglist').id
            };
            if (this.model) {
                urlOptions.productId = this.model.get('id');
                if (this.model.has('parentProduct')) {
                    urlOptions.parentProductId = this.model.get('parentProduct');
                }
            }

            if (!this.validate(intention, url, urlOptions, formData)) {
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
            var label;
            var intention;

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

            $button.find('a')
                .text(label)
                .attr('title', label)
                .attr('data-intention', intention);

            return $button;
        },

        _setEditLineItem: function(lineItemId, setFirstLineItem) {
            this.editLineItem = null;

            if (!this.model || !this.shoppingListCollection.length) {
                return;
            }

            var currentShoppingListInCollection = this.findCurrentShoppingList();
            var currentShoppingListInModel = this.findShoppingListById(currentShoppingListInCollection);

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

            if (this.editLineItem) {
                // quantity precision depend on unit, set unit first
                this.model.set('unit', this.editLineItem.unit);
                this.model.set('quantity', this.editLineItem.quantity);
                this.model.set('quantity_changed_manually', true);// prevent quantity change in other components
            }
        },

        _createNewShoppingList: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            var dialog = new ShoppingListCreateWidget({});
            dialog.on('formSave', _.bind(function(response) {
                urlOptions.shoppingListId = response.savedId;
                this._addLineItem(url, urlOptions, formData);
            }, this));
            dialog.render();
        },

        _addProductToShoppingList: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            var self = this;

            mediator.execute('showLoading');
            $.ajax({
                type: 'POST',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    mediator.trigger('shopping-list:line-items:update-response', self.model, response);

                    mediator.trigger('shopping-list:add-product', {
                        id: self.model.get("id"),
                        quantity: [self.model.get("quantity"), self.model._previousAttributes.quantity]
                    });
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
            this._addProductToShoppingList(url, urlOptions, formData);
        },

        _saveLineItem: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            mediator.execute('showLoading');

            var shoppingList = this.findShoppingList(urlOptions.shoppingListId);
            var lineItem = this.findShoppingListLineItemByUnit(shoppingList, this.model.get('unit'));

            var savePromise = this.saveApiAccessor.send({
                id: lineItem.id
            }, {
                quantity: this.model.get('quantity'),
                unit: this.model.get('unit')
            });

            savePromise
                .done(_.bind(function(response) {
                    lineItem.quantity = response.quantity;
                    lineItem.unit = response.unit;
                    this.shoppingListCollection.trigger('change', {
                        refresh: true
                    });
                    mediator.execute('showFlashMessage', 'success', _.__(this.options.messages.success));
                    mediator.trigger('shopping-list:add-product', {
                        id: this.model.get("id"),
                        quantity: [this.model.get("quantity"), this.model._previousAttributes.quantity]
                    });
                }, this))
                .always(function() {
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

            ProductAddToShoppingListView.__super__.dispose.apply(this, arguments);
        }
    }));

    return ProductAddToShoppingListView;
});
