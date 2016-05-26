define(function(require) {
    'use strict';

    var ProductAddToShoppingListView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ShoppingListCreateWidget = require('oro/shopping-list-create-widget');
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
            addedClass: '',
            buttonsSelector: '.add-to-shopping-list-button'
        },

        $el: null,

        $remove: null,

        dropdownWidget: null,

        modelAttr: {
            current_shopping_list_line_items: {}
        },

        initialize: function(options) {
            ProductAddToShoppingListView.__super__.initialize.apply(this, arguments);
            this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));

            this.initModel(options);

            this.dropdownWidget = options.dropdownWidget;
            this.setElement(this.dropdownWidget.element);

            if (this.options.buttonTemplate) {
                this.options.buttonTemplate = _.template(this.options.buttonTemplate);
            }
            if (this.options.removeButtonTemplate) {
                this.options.removeButtonTemplate = _.template(this.options.removeButtonTemplate);
            }

            mediator.on('shopping-list:updated', this._onShoppingListUpdate, this);
            mediator.on('shopping-list:created', this._onShoppingListCreate, this);
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

            mediator.off('shopping-list:updated', this._onShoppingListUpdate, this);
            mediator.off('shopping-list:created', this._onShoppingListCreate, this);

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

        _onShoppingListUpdate: function(shoppingList, product) {
            if (!this.model) {
                return;
            }
            var mainButtonShoppingList = this.dropdownWidget.main.data('shoppinglist');
            if (!product || product.id !== parseInt(this.model.get('id'), 10) ||
                !mainButtonShoppingList || shoppingList.id !== parseInt(mainButtonShoppingList.id, 10)) {
                return;
            }
            this.model.set('current_shopping_list_line_items', product.lineItems);

            this.updateMainButton();
        },

        _onShoppingListCreate: function(shoppingList, product) {
            if (this.model) {
                if (!product || product.id !== parseInt(this.model.get('id'), 10)) {
                    this.model.set('current_shopping_list_line_items', {});
                } else {
                    this.model.set('current_shopping_list_line_items', product.lineItems);
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

        transformCreateNewButton: function() {
            var $button = this.findNewButton();
            if ($button.length) {
                var label = _.__('orob2b.shoppinglist.widget.add_to_new_shopping_list');
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
            if (_.isEmpty(this.model.get('current_shopping_list_line_items'))) {
                this.dropdownWidget.group.removeClass(this.options.addedClass).addClass(this.options.defaultClass);
            } else {
                this.dropdownWidget.group.removeClass(this.options.defaultClass).addClass(this.options.addedClass);
            }
        },

        setButtonLabel: function($button) {
            if (!this.model) {
                return;
            }
            var model = this.model;
            var modelLineItems = model.get('current_shopping_list_line_items');
            var shoppingList = $button.data('shoppinglist');
            var label;

            if (_.isEmpty(modelLineItems)) {
                label =  _.__('orob2b.shoppinglist.actions.add_to_shopping_list');
            } else {
                var lineItems = '';
                if (_.size(modelLineItems) === 1) {
                    _.each(modelLineItems, function(count, unit) {
                        if (_.size(model.get('product_units')) > 1) {
                            lineItems = _.__(
                                'orob2b.product.product_unit.' + unit + '.value.short',
                                {'count': count},
                                count
                            );
                        } else {
                            lineItems = count;
                        }
                    });
                }
                label =  _.__('orob2b.shoppinglist.actions.added_to_shopping_list')
                    .replace('{{ lineItems }}', lineItems);
            }

            label = label.replace('{{ shoppingList }}', shoppingList.label);
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
            var modelLineItems = this.model.get('current_shopping_list_line_items');

            if (!this.$remove && !_.isEmpty(modelLineItems)) {
                var $button = $(this.options.removeButtonTemplate(shoppingList));
                $button = this.dropdownWidget._collectButtons($button);
                $button = this.dropdownWidget._prepareButtons($button);
                $button.data('shoppinglist', shoppingList);

                this.$remove = $button;
                this.dropdownWidget.main.data('clone').parent().after(this.$remove);
            } else if (this.$remove && _.isEmpty(modelLineItems)) {
                this.$remove.remove();
                delete this.$remove;
            }
        },

        onClick: function(e) {
            var $button = $(e.currentTarget);
            var url = $button.data('url');
            var formData = this.$el.closest('form').serialize();
            var urlOptions = {};

            if (!this.dropdownWidget.validateForm()) {
                return;
            }

            if (this.model) {
                urlOptions.productId = this.model.get('id');
            }

            if ($button.data('intention') === 'new') {
                this._createNewShoppingList(url, urlOptions, formData);
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
        }
    });

    return ProductAddToShoppingListView;
});
