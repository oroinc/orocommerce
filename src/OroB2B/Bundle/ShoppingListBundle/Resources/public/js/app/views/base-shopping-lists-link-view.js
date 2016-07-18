define(function(require) {
    'use strict';

    var BaseShoppingListsLinkView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var ShoppingListsMultipleEditWidget = require('orob2bshoppinglist/js/app/widget/shopping-lists-multiple-edit-widget');
    var mediator = require('oroui/js/mediator');
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
        quantityComponentOptions: null,
        deleteLineOptions: null,

        initialize: function(options) {
            BaseShoppingListsLinkView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.quantityComponentOptions = options.quantityComponentOptions;
            this.deleteLineOptions = options.deleteLineOptions;
            this.template = _.template(options['billetTemplate']);
            this.popupTemplate = _.template(options['popupTemplate']);

            mediator.on('shopping-list:updated', this.updateShoppingListsBillet, this);
            this.model.on('change:shopping_lists', this.updateShoppingListsBillet, this);
            this.render();
        },

        dispose: function() {
            this.disposeElements();
            BaseShoppingListsLinkView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updateShoppingListsBillet();
        },

        initModel: function(options) {
            if (options.productModel) {
                this.model = options.productModel;
            }
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
                    var lineItemsLabel = __(
                        'orob2b.product.product_unit.' + lineItem.unit + '.value.short',
                        {'count': lineItem.quantity},
                        lineItem.quantity);

                    label.name = __('orob2b.shoppinglist.billet.items_in_shopping_list')
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
            this.initShoppingListsPopupButton();
        },

        initShoppingListsPopupButton: function() {
            this.delegateElementEvent('shoppingListsLink', 'click', _.bind(this.renderShoppingListsPopup, this));
        },

        renderShoppingListsPopup: function() {
            new ShoppingListsMultipleEditWidget({
                model: this.model,
                template: this.popupTemplate,
                quantityComponentOptions: this.quantityComponentOptions,
                deleteLineOptions: this.deleteLineOptions
            }).render();
        },

        renderShoppingListsBillet: function(billet) {
            this.getElement('shoppingListsBillet')
                .html(this.template({billet: billet}));
        }
    }));

    return BaseShoppingListsLinkView;
});
