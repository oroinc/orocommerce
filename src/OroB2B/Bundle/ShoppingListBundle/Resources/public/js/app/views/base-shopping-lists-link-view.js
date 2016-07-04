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
                    'title': '',
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
                    is_current: true,
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
        
        initialize: function(options) {
            BaseShoppingListsLinkView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.widgetOptions = $.extend(true, {}, this.widgetDefaultOptions, this.widgetOptions, {
                options: {
                    dialogOptions: {
                        'title': this.model.get('name')
                    }
                }
            });

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

        setLabels: function(currentShoppingList) {
            if (!currentShoppingList) {
                return null;
            }

            var currentShoppingListLabel = currentShoppingList.shopping_list_lable;
            var labels = [];

            if (_.has(currentShoppingList, 'line_items')) {
                _.each(currentShoppingList.line_items, function (lineItem) {
                    var label = {};
                    var lineItemsLabel = _.__(
                        'orob2b.product.product_unit.' + lineItem.unit + '.value.short',
                        {'count': lineItem.quantity},
                        lineItem.quantity);

                    label.name = _.__('orob2b.shoppinglist.billet.items_in_shopping_list')
                        .replace('{{ lineItems }}', lineItemsLabel);

                    label.name = label.name.replace('{{ shoppingList }}', currentShoppingListLabel);

                    labels.push(label);
                });
            }

            return labels;
        },

        findCurrentShoppingList: function(shoppingLists) {
            if (!shoppingLists || !_.isObject(shoppingLists)) {
                return null;
            }
            return _.find(shoppingLists, function(list) {
                return list.is_current;
            }) || null;
        },

        updateShoppingListsBillet: function() {
            var billet = {};

            if (!this.model) {
                return;
            }

            var shoppingLists = this.model.get('shopping_lists');

            billet.currentLineItemsLabels = this.setLabels(this.findCurrentShoppingList(shoppingLists));
            billet.shoppingList = this.findCurrentShoppingList(shoppingLists);
            billet.shoppingLists = shoppingLists;

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
            this.getElement('shoppingListsBillet')
                .html(this.options.templates.shoppingListsBillet({billet: billet}));
        }
    }));

    return BaseShoppingListsLinkView;
});
