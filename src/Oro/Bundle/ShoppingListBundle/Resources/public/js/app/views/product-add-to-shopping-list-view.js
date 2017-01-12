define(function(require) {
    'use strict';

    var ProductAddToShoppingListView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ShoppingListCreateWidget = require('oro/shopping-list-create-widget');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    ProductAddToShoppingListView = BaseView.extend({
        options: {
            buttonTemplate: '',
            createNewButtonTemplate: '',
            removeButtonTemplate: '',
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

        initialize: function(options) {
            ProductAddToShoppingListView.__super__.initialize.apply(this, arguments);
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
            this.$el.trigger('options:set:productModel', options);
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

            var $container = this.dropdownWidget.element.find('.btn-group:first');
            $container.html(buttons);
        },

        _onCollectionChange: function() {
            this._setEditLineItem();

            var buttons = this._collectAllButtons();

            this.findAllButtons().remove();
            this.dropdownWidget.element.prepend(buttons);
            this.dropdownWidget._renderButtons();
        },

        _collectAllButtons: function() {
            var buttons = [];

            if (!this.shoppingListCollection.length) {
                var $addNewButton = $(this.options.buttonTemplate({
                    id: null,
                    label: _.__('oro.shoppinglist.entity_label')
                })).find(this.options.buttonsSelector).addClass('btn-block btn-orange btn_lg');

                return [$addNewButton];
            }

            var currentShoppingList = this.findCurrentShoppingList();
            var $currentButton = $(this.options.buttonTemplate(currentShoppingList));
            if (this.findShoppingListById(currentShoppingList)) {
                $currentButton = this.updateLabel($currentButton, currentShoppingList);
            }
            buttons.push($currentButton);

            if (this.findShoppingListById(currentShoppingList) && this.editLineItem) {
                var $removeButton =  $(this.options.removeButtonTemplate(currentShoppingList));
                buttons.push($removeButton);
            }

            var self = this;
            this.shoppingListCollection.sort();
            this.shoppingListCollection.each(function(model) {
                var $button;
                var shoppingList = model.toJSON();
                if (shoppingList.id === currentShoppingList.id) {
                    return;
                }
                $button = $(self.options.buttonTemplate(shoppingList));
                $button = self.updateLabel($button, shoppingList);
                buttons.push($button);
            });

            var $createNewButton = $(this.options.createNewButtonTemplate({id: null, label: ''}));
            $createNewButton = this.updateLabel($createNewButton, null);
            buttons.push($createNewButton);

            return buttons;
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
            var $el = this.dropdownWidget.dropdown || this.dropdownWidget.element;
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
            return _.find(this.model.get('shopping_lists'), function(list) {
                return list.id === shoppingList.id;
            }) || null;
        },

        _onQuantityEnter: function(e) {
            var ENTER_KEY_CODE = 13;

            if (e.keyCode === ENTER_KEY_CODE && this.dropdownWidget.main.data('intention') === 'update') {
                if (!this.dropdownWidget.validateForm()) {
                    return;
                }

                this.model.set({
                    quantity: parseInt($(e.target).val(), 10)
                });
                this._saveLineItem();
            }
        },

        findCurrentShoppingList: function() {
            return this.shoppingListCollection.find(function(model) {
                return model.get('is_current') === true;
            }).toJSON() || null;
        },

        onClick: function(e) {
            var $button = $(e.currentTarget);
            var url = $button.data('url');
            var intention = $button.data('intention');
            var formData = this.$form.serialize();
            var urlOptions = {};

            if (!this.dropdownWidget.validateForm()) {
                return;
            }

            if (this.model) {
                urlOptions.productId = this.model.get('id');
                if (this.model.has('parentProduct')) {
                    urlOptions.parentProductId = this.model.get('parentProduct');
                }
            }

            if (intention === 'new') {
                this._createNewShoppingList(url, urlOptions, formData);
            } else if (intention === 'update') {
                this._saveLineItem();
            } else {
                var shoppingList = $button.data('shoppinglist');
                urlOptions.shoppingListId = shoppingList.id;
                this._addProductToShoppingList(url, urlOptions, formData);
            }
        },

        updateLabel: function($button, shoppingList) {
            var label;

            if (this.editLineItem && shoppingList && shoppingList.is_current) {
                label = _.__('oro.shoppinglist.actions.update_shopping_list', {
                    shoppingList: shoppingList.label
                });
                $button.find('a').attr('data-intention', 'update');
            } else if (!shoppingList) {
                label = _.__('oro.shoppinglist.widget.add_to_new_shopping_list');
                $button.find('a').attr('data-intention', 'new');
            } else {
                label =  _.__('oro.shoppinglist.actions.add_to_shopping_list', {
                    shoppingList: shoppingList.label
                });
                $button.find('a').attr('data-intention', 'add');
            }

            $button.find('a').attr('title', label);
            $button.find('a').text(label);

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
            } else {
                this.editLineItem = _.findWhere(
                    currentShoppingListInModel.line_items, {unit: this.model.get('unit')}
                    ) || null;
            }

            if (this.editLineItem && (lineItemId || setFirstLineItem)) {
                this.model.set({
                    quantity: this.editLineItem.quantity,
                    unit: this.editLineItem.unit
                });
            }
        },

        _createNewShoppingList: function(url, urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            var dialog = new ShoppingListCreateWidget({});
            dialog.on('formSave', _.bind(function(response) {
                urlOptions.shoppingListId = response;
                this._addProductToShoppingList(url, urlOptions, formData);
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
                    mediator.execute('hideLoading');
                    if (response && response.message) {
                        mediator.execute(
                            'showFlashMessage', (response.hasOwnProperty('successful') ? 'success' : 'error'),
                            response.message
                        );

                        self.model.set('shopping_lists', response.product.shopping_lists, {silent: true});
                        self.model.trigger('change:shopping_lists');
                        if (!self.shoppingListCollection.find({id: response.shoppingList.id})) {
                            self.shoppingListCollection.add(_.defaults(response.shoppingList, {is_current: true}), {
                                silent: true
                            });
                        }
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

        _saveLineItem: function(urlOptions, formData) {
            if (this.model && !this.model.get('line_item_form_enable')) {
                return;
            }
            mediator.execute('showLoading');

            var savePromise = this.saveApiAccessor.send({
                id: this.editLineItem.id
            }, {
                quantity: this.model.get('quantity'),
                unit: this.model.get('unit')
            });

            savePromise
                .done(_.bind(function(response) {
                    this.editLineItem.quantity = response.quantity;
                    this.editLineItem.unit = response.unit;
                    this.shoppingListCollection.trigger('change', {
                        refresh: true
                    });
                    mediator.execute('showFlashMessage', 'success', _.__(this.options.messages.success));
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
    });

    return ProductAddToShoppingListView;
});
