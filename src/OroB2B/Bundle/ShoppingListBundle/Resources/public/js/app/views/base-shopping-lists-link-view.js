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
            shoppingListsLink: '[data-name="shopping-lists-link"]'
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
                        }
                    ]
                }
            ]
        },

        labels: [],
        
        initialize: function(options) {
            BaseShoppingListsLinkView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.widgetOptions = $.extend(true, {}, this.widgetDefaultOptions, this.widgetOptions);

            this.options.templates.shoppingListsBillet = _.template(options['billetTemplate']);

            this.model.on('change:shopping_lists', this.updateShoppingListsBillet, this);

            this.render();
        },

        dispose: function() {
            delete this.modelAttr;
            this.disposeElements();
            BaseShoppingListsLinkView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updateShoppingListsBillet();
            this.initShoppingListsPopupButton();
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

        setLabels: function(lineItems) {
            this.labels = [];

            _.each(lineItems, function(count, unit) {
                var label = {};
                var lineItemsLabel = '';
                var currentShoppingListLabel = this.model.get('shopping_lists')[0].shopping_list_lable; // todo: be sure that the current shopping list is always the first shopping list

                if (_.has(lineItems, unit)) {
                    lineItemsLabel = _.__(
                        'orob2b.product.product_unit.' + unit + '.value.short',
                        {'count': count},
                        count);

                    label.name =  _.__('orob2b.shoppinglist.billet.items_in_shopping_list')
                        .replace('{{ lineItems }}', lineItemsLabel);

                    label.name = label.name.replace('{{ shoppingList }}', currentShoppingListLabel);

                    this.labels.push(label);
                }
            }, this);
        },

        updateShoppingListsBillet: function() {
            var billet = {};

            this.setLabels(this.model.get('current_shopping_list_line_items'));

            billet.currentLineItemsLabels = this.labels;
            billet.currentShoppingList = this.model.get('current_shopping_list_line_items');
            billet.shoppingLists = this.model.get('shopping_lists');

            this.renderShoppingListsBillet(billet);
        },

        initShoppingListsPopupButton: function() {
            this.delegateElementEvent('shoppingListsLink', 'click', _.bind(this.renderShoppingListsPopup, this));
        },

        renderShoppingListsPopup: function() {
            if (!this.widgetComponent) {
                this.widgetComponent = new WidgetComponent(this.widgetOptions);
            }
            this.widgetComponent.openWidget();
        },

        renderShoppingListsBillet: function(billet) {
            this.getElement('shoppingListsBillet').html(this.options.templates.shoppingListsBillet({billet: billet}));
        }
    }));

    return BaseShoppingListsLinkView;
});
