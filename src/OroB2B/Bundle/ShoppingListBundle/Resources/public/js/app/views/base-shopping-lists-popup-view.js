define(function(require) {
    'use strict';

    var BaseShoppingListsPopupView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    BaseShoppingListsPopupView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            shoppingListsPopup: '[data-name="shopping-lists-popup"]',
            edit: '[data-name="shopping-list-edit"]',
            remove: '[data-name="shopping-list-remove"]',
            accept: '[data-name="shopping-list-accept"]',
            decline: '[data-name="shopping-list-decline"]'
        },

        template: '',

        modelAttr: {
            shopping_lists: [
                {
                    shopping_list_id: 0,
                    shopping_list_lable: 'Shopping List 1',
                    shopping_list_url: '#',
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
                    shopping_list_url: '#',
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
            BaseShoppingListsPopupView.__super__.initialize.apply(this, arguments);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.template = _.template(options['popupTemplate']);

            this.model.on('change:shopping_lists', this.updateShoppingListsPopup, this);

            mediator.on('widget_dialog:open', this.initShoppingListControls, this);

            this.render();
        },

        dispose: function() {
            delete this.modelAttr;
            this.disposeElements();
            BaseShoppingListsPopupView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updateShoppingListsPopup();
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

        initShoppingListControls: function() {
            //bind click events for edit, remove buttons
        },

        updateShoppingListsPopup: function() {
            if (!this.model) {
                return;
            }

            var popup = {};

            popup.shoppingLists =  this.model.get('shopping_lists');

            this.renderShoppingListsPopupContent(popup);
        },

        renderShoppingListsPopupContent: function(popup) {
            this.getElement('shoppingListsPopup')
                .html(this.template({popup: popup}));
        }
    }));

    return BaseShoppingListsPopupView;
});
