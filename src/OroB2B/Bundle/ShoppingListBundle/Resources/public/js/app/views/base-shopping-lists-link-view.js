define(function(require) {
    'use strict';

    var BaseShoppingListsLinkView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var WidgetComponent = require('oroui/js/app/components/widget-component');
    var _ = require('underscore');
    var $ = require('jquery');

    BaseShoppingListsLinkView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            shoppingListsBillet: '[data-name="shopping-lists-billet"]',
            shoppingListsLink: '[data-name="shopping-lists-link"]',
            currentShoppingListBilletLabel: '[data-name="current-shopping-list-billet-label"]'
        },

        options: {
            templates: {
                shoppingListsBillet: ''
            }
        },

        widgetOptions: null,
        widgetComponent: null,
        widgetDefaultOptions: {
            type: 'content',
            options: {
                content: '#in-shopping-lists-template',
                dialogOptions: {
                    'title': 'Basic Women’s Full Length Lab Coat',
                    'modal': true,
                    'resizable': false,
                    'width': 580,
                    'autoResize': true
                }
            }
        },

        modelAttr: {
            shopping_lists: [
                {
                    shopping_list_id: 0,
                    shopping_list_lable: 'Shopping List 1',
                    line_items: [
                        {
                            unit: 'item',
                            quantity: 5
                        },
                        {
                            unit: 'set',
                            quantity: 1
                        }
                    ]
                },
                {
                    shopping_list_id: 1,
                    shopping_list_lable: 'Shopping List 2',
                    line_items: [
                        {
                            unit: 'item',
                            quantity: 10
                        },
                        {
                            unit: 'set',
                            quantity: 1
                        }
                    ]
                }
            ]
        },
        
        initialize: function(options) {
            BaseShoppingListsLinkView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }

            this.initializeElements(options);

            this.options.templates.shoppingListsBillet = _.template(options['billetTemplate']);

            this.widgetOptions = $.extend(true, {}, this.widgetDefaultOptions, this.widgetOptions);

            this.model.on('change:shopping_lists', this.updateShoppingListsBillet, this);

            this.render();
        },

        dispose: function() {
            delete this.modelAttr;
            this.disposeElements();
            BaseShoppingListsLinkView.__super__.dispose.apply(this, arguments);
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

        updateCurrentShoppingList: function() {
            var currentShoppingListLineItems = this.model.get('current_shopping_list_line_items');

            if (!currentShoppingListLineItems && !_.isObject(currentShoppingListLineItems)) {
                return null;
            }
            if (_.isEmpty(currentShoppingListLineItems)) {
                return null;
            }

            return currentShoppingListLineItems;
        },

        updateShoppingLists: function() {
            var shoppingLists = this.model.get('shopping_lists');

            if (!shoppingLists && !_.isArray(shoppingLists)) {
                return null;
            }
            if (_.isEmpty(shoppingLists)) {
                return null;
            }

            return shoppingLists;
        },

        updateShoppingListsBillet: function() {
            var billet = {};

            billet.currentShoppingList = this.updateCurrentShoppingList();
            billet.shoppingLists = this.updateShoppingLists();

            this.renderShoppingListsBillet(billet);
        },

        render: function() {
            this.updateShoppingListsBillet();
        },

        renderShoppingListsButton: function() {
            this.delegateElementEvent('shoppingListsLink', 'click', _.bind(this.renderShoppingListsModal, this));
        },

        renderShoppingListsModal: function() {
            if (!this.widgetComponent) {
                this.widgetComponent = new WidgetComponent(this.widgetOptions);
            }
            this.widgetComponent.openWidget();
        },

        renderShoppingListsBillet: function(billet) {
            this.getElement('shoppingListsBillet').empty();
            this.getElement('shoppingListsBillet').html(this.options.templates.shoppingListsBillet({billet: billet}));
            this.renderShoppingListsButton();
        }
    }));

    return BaseShoppingListsLinkView;
});
