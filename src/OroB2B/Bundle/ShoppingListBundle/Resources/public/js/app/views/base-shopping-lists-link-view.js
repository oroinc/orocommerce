define(function(require) {
    'use strict';

    var BaseShoppingListsLinkView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var ShoppingListsMultipleEditWidget = require('orob2bshoppinglist/js/app/widget/shopping-lists-multiple-edit-widget');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    BaseShoppingListsLinkView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            shoppingListsBillet: '[data-name="shopping-lists-billet"]',
            shoppingListsLink: '[data-name="shopping-lists-link"]'
        },

        template: '',
        popupTemplate: '',

        demoData: {
            shopping_lists: [
                {
                    shopping_list_id: 0,
                    shopping_list_label: 'Shopping List 1',
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
                    shopping_list_label: 'Shopping List 2',
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

            this.template = _.template(options['billetTemplate']);
            this.popupTemplate = _.template(options['popupTemplate']);

            this.model.on('change:shopping_lists', this.updateShoppingListsBillet, this);

            this.render();
        },

        dispose: function() {
            delete this.demoData;
            this.disposeElements();
            BaseShoppingListsLinkView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updateShoppingListsBillet();
            this.initShoppingListsPopupButton();
        },

        initModel: function(options) {
            this.demoData = $.extend(true, {}, this.demoData, options.demoData || {});
            if (options.productModel) {
                this.model = options.productModel;
            }
            _.each(this.demoData, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        setLabels: function(currentShoppingList) {
            if (!currentShoppingList) {
                return null;
            }

            var currentShoppingListLabel = currentShoppingList.shopping_list_label;
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
            if (!this.model) {
                return;
            }

            var billet = {};
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
            new ShoppingListsMultipleEditWidget({
                model: this.model,
                template: this.popupTemplate
            }).render();
        },

        renderShoppingListsBillet: function(billet) {
            this.getElement('shoppingListsBillet')
                .html(this.template({billet: billet}));
        }
    }));

    return BaseShoppingListsLinkView;
});
