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
            shoppingListsButton: '[data-name="shopping-lists-button"]',
            shoppingListsLength: '[data-name="shopping-lists-length"]'
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
                    shopping_list_lable: '',
                    line_items: [
                        {
                            unit: 'item',
                            quantity: 5
                        }
                    ]
                },
                {
                    shopping_list_id: 1,
                    shopping_list_lable: '',
                    line_items: [
                        {
                            unit: 'item',
                            quantity: 10
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

            this.widgetOptions = $.extend(true, {}, this.widgetDefaultOptions, this.widgetOptions);

            this.model.on('change:shopping_lists', this.updateShoppingLists, this);

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

        updateShoppingLists: function() {
            this.renderShoppingListsLength(this.model.get('shopping_lists'));
        },

        render: function() {
            this.updateShoppingLists();
            this.renderShoppingListsButton();
        },

        renderShoppingListsButton: function() {
            this.delegateElementEvent('shoppingListsButton', 'click', _.bind(this.renderShoppingListsModal, this));
        },

        renderShoppingListsModal: function() {
            if (!this.widgetComponent) {
                this.widgetComponent = new WidgetComponent(this.widgetOptions);
            }
            this.widgetComponent.openWidget();
        },

        renderShoppingListsLength: function(shoppingLists) {
            this.getElement('shoppingListsLength').html(shoppingLists.length);
        }
    }));

    return BaseShoppingListsLinkView;
});
