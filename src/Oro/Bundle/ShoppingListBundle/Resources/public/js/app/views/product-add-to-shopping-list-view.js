define(function(require) {
    'use strict';

    var ProductAddToShoppingListView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ShoppingListCreateWidget = require('oro/shopping-list-create-widget');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductAddToShoppingListView = BaseView.extend({
        options: {
            buttonTemplate: '',
            removeButtonTemplate: '',
            defaultClass: '',
            editClass: '',
            buttonsSelector: '.add-to-shopping-list-button',
            messages: {
                success: 'oro.form.inlineEditing.successMessage'
            },
            save_api_accessor: {
                http_method: 'PUT',
                route: 'oro_api_shopping_list_frontend_put_line_item',
                form_name: 'oro_product_frontend_line_item'
            }
        },

        $el: null,

        $remove: null,

        dropdownWidget: null,

        modelAttr: {
            shopping_lists: []
        },

        initialize: function(options) {
            ProductAddToShoppingListView.__super__.initialize.apply(this, arguments);
            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));

            this.initModel(options);

            this.dropdownWidget = options.dropdownWidget;

            if (this.dropdownWidget) {
                this.setElement(this.dropdownWidget.element);
            }

            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
            }
            if (this.options.removeButtonTemplate) {
                this.options.removeButtonTemplate = _.template(this.options.removeButtonTemplate);
            }

            this._setEditLineItem(null, true);
            this.saveApiAccessor = new ApiAccessor(this.options.save_api_accessor);

            mediator.on('shopping-list:updated', this._onShoppingListUpdate, this);
            mediator.on('shopping-list:created', this._onShoppingListCreate, this);
            if (this.model) {
                this.model.on('change:shopping_lists', this._onModelChanged, this);
                this.model.on('change:unit', this._onModelChanged, this);
                this.model.on('editLineItem', this._editLineItem, this);
            }
        },

        initModel: function(options) {
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }

            if (!this.model) {
                return;
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function(options) {
            delete this.dropdownWidget;
            delete this.modelAttr;
            delete this.$remove;
            delete this.editShoppingList;
            delete this.editLineItem;

            mediator.off(null, null, this);
            if (this.model) {
                this.model.off(null, null, this);
            }

            ProductAddToShoppingListView.__super__.dispose.apply(this, arguments);
        },

        findDropdownButtons: function(filter) {
            var $el = this.dropdownWidget.dropdown || this.$el;
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

        findNewButton: function() {
            return this.findAllButtons('[data-id=""]');
        },

        findAllButtons: function(filter) {
            var $buttons = this.findMainButton().add(this.findDropdownButtons());
            if (filter) {
                $buttons = $buttons.filter(filter);
            }
            return $buttons;
        },

        initButtons: function() {
            this.findAllButtons()
                .off('click' + this.eventNamespace())
                .on('click' + this.eventNamespace(), _.bind(this.onClick, this));
        },

        _afterRenderButtons: function() {
            this.updateMainButton();
        },

        _onModelChanged: function() {
            this._setEditLineItem();
            this.updateMainButton();
        },

        _onShoppingListUpdate: function(shoppingList, product) {
            if (!this.model) {
                return;
            }
            if (!product || product.id !== parseInt(this.model.get('id'), 10)) {
                return;
            }
            this.model.set('shopping_lists', product.shopping_lists);
        },

        _onShoppingListCreate: function(shoppingList, product) {
            if (this.model) {
                if (!product || product.id !== parseInt(this.model.get('id'), 10)) {
                    var modelCurrentShoppingLists = this.findCurrentShoppingList();
                    if (modelCurrentShoppingLists) {
                        modelCurrentShoppingLists.is_current = false;
                        this.model.trigger('change:shopping_lists');
                    }
                } else {
                    this.model.set('shopping_lists', product.shopping_lists);
                }
            }

            var $newMainButton = $(this.options.buttonTemplate(shoppingList));
            $newMainButton.data('shoppinglist', shoppingList);

            if (!this.dropdownWidget.main) {
                this.transformCreateNewButton();
                this.$el.prepend($newMainButton);
                this.dropdownWidget._renderButtons();
            } else {
                $newMainButton = this.dropdownWidget._collectButtons($newMainButton);
                $newMainButton = this.dropdownWidget._prepareMainButton($newMainButton);
                var $newMainButtonClone = $newMainButton.data('clone');
                $newMainButtonClone = this.dropdownWidget._prepareButtons($newMainButtonClone);

                var $oldMainButton = this.dropdownWidget.main;
                var $oldMainButtonClone = $oldMainButton.data('clone');

                this.dropdownWidget.main = $newMainButton;
                $oldMainButton.replaceWith($newMainButton);

                this.findDropdownButtons(':first').parent().before($newMainButtonClone);

                if ($oldMainButtonClone.is(this.findNewButton())) {
                    this.transformCreateNewButton();
                } else {
                    this.setButtonLabel($oldMainButtonClone);
                    this.findNewButton().parent().before($oldMainButtonClone.parent());
                }

                this.updateMainButton();
            }
        },

        _editLineItem: function(lineItemId) {
            this._setEditLineItem(lineItemId);
            this.updateMainButton();
            this.model.trigger('focus:quantity');
        },

        _setEditLineItem: function(lineItemId, setFirstLineItem) {
            this.editLineItem = null;
            this.editShoppingList = null;

            if (!this.model) {
                return ;
            }

            _.each(this.model.get('shopping_lists'), function(shoppingList) {
                if (this.editLineItem || !shoppingList.is_current) {
                    return;
                }

                if (lineItemId) {
                    this.editLineItem = _.findWhere(shoppingList.line_items, {line_item_id: lineItemId});
                } else if (setFirstLineItem) {
                    this.editLineItem = shoppingList.line_items[0] || null;
                } else {
                    this.editLineItem = _.findWhere(shoppingList.line_items, {unit: this.model.get('unit')});
                }

                if (this.editLineItem) {
                    this.editShoppingList = shoppingList;
                }
            }, this);

            if (this.editLineItem && (lineItemId || setFirstLineItem)) {
                this.model.set({
                    quantity: this.editLineItem.quantity,
                    unit: this.editLineItem.unit
                });
            }
        },

        transformCreateNewButton: function() {
            var $button = this.findNewButton();
            if ($button.length) {
                var label = _.__('oro.shoppinglist.widget.add_to_new_shopping_list');
                $button.attr('data-intention', 'new')
                    .html(label)
                    .attr('title', label);
            }
        },

        updateMainButton: function() {
            if (this.dropdownWidget.main && this.dropdownWidget.main.data('shoppinglist')) {
                this.toggleButtonsClass();

                this.setButtonLabel(this.dropdownWidget.main);
                this.setButtonLabel(this.dropdownWidget.main.data('clone'));

                this.toggleRemoveButton();
            }

            this.initButtons();
        },

        toggleButtonsClass: function() {
            if (!this.model) {
                return;
            }

            if (_.isEmpty(this.editShoppingList)) {
                this.dropdownWidget.group.removeClass(this.options.editClass).addClass(this.options.defaultClass);
            } else {
                this.dropdownWidget.group.removeClass(this.options.defaultClass).addClass(this.options.editClass);
            }
        },

        setButtonLabel: function($button) {
            if (!this.model) {
                return;
            }

            var label;
            if (_.isEmpty(this.editShoppingList)) {
                label =  _.__('oro.shoppinglist.actions.add_to_shopping_list', {
                    shoppingList: $button.data('shoppinglist').label
                });
                $button.data('intention', 'add');
            } else {
                label =  _.__('oro.shoppinglist.actions.update_shopping_list', {
                    shoppingList: this.editShoppingList.shopping_list_label
                });
                $button.data('intention', 'update');
            }

            if (this.dropdownWidget.options.truncateLength &&
                $button.get(0) === this.dropdownWidget.main.get(0)) {
                label = _.trunc(label, this.dropdownWidget.options.truncateLength, false, '...');
            }

            $button.attr('title', label).html(label);
        },

        toggleRemoveButton: function() {
            if (!this.model) {
                return;
            }
            var shoppingList = this.dropdownWidget.main.data('shoppinglist');

            if (!this.$remove && !_.isEmpty(this.editShoppingList)) {
                var $button = $(this.options.removeButtonTemplate(shoppingList));
                $button = this.dropdownWidget._collectButtons($button);
                $button = this.dropdownWidget._prepareButtons($button);
                $button.data('shoppinglist', shoppingList);

                this.$remove = $button;
                this.dropdownWidget.main.data('clone').parent().after(this.$remove);
            } else if (this.$remove && _.isEmpty(this.editShoppingList)) {
                this.$remove.remove();
                delete this.$remove;
            }
        },

        findCurrentShoppingList: function() {
            return _.find(this.model.get('shopping_lists'), function(list) {
                return list.is_current;
            }) || null;
        },

        onClick: function(e) {
            var $button = $(e.currentTarget);
            var url = $button.data('url');
            var intention = $button.data('intention');
            var formData = this.$el.closest('form').serialize();
            var urlOptions = {};

            if (!this.dropdownWidget.validateForm()) {
                return;
            }

            if (this.model) {
                urlOptions.productId = this.model.get('id');
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

        _createNewShoppingList: function(url, urlOptions, formData) {
            var dialog = new ShoppingListCreateWidget({});
            dialog.on('formSave', _.bind(function(response) {
                urlOptions.shoppingListId = response;
                this._addProductToShoppingList(url, urlOptions, formData);
            }, this));
            dialog.render();
        },

        _addProductToShoppingList: function(url, urlOptions, formData) {
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
                    }
                    var event = urlOptions.shoppingListId ? 'shopping-list:updated' : 'shopping-list:created';
                    mediator.trigger(event, response.shoppingList, response.product);
                },
                error: function(xhr) {
                    mediator.execute('hideLoading');
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        _saveLineItem: function(urlOptions, formData) {
            mediator.execute('showLoading');

            var savePromise = this.saveApiAccessor.send({
                id: this.editLineItem.line_item_id
            }, {
                quantity: this.model.get('quantity'),
                unit: this.model.get('unit')
            });

            savePromise
                .done(_.bind(function(response) {
                    this.editLineItem.quantity = response.quantity;
                    this.editLineItem.unit = response.unit;
                    this.model.trigger('change:shopping_lists');

                    mediator.execute('showFlashMessage', 'success', _.__(this.options.messages.success));
                }, this))
                .always(function() {
                    mediator.execute('hideLoading');
                });
        }
    });

    return ProductAddToShoppingListView;
});
