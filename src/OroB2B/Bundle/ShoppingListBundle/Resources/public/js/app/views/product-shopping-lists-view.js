define(function(require) {
    'use strict';

    var ProductShoppingListsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var _ = require('underscore');
    var $ = require('jquery');

    ProductShoppingListsView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            template: ''
        },

        elements: {
            editLineItem: '[data-role="edit-line-item"]'
        },

        elementsEvents: {
            editLineItem: ['click', 'editLineItem']
        },

        modelAttr: {
            shopping_lists: []
        },

        modelEvents: {
            shopping_lists: ['change', 'render']
        },

        initialize: function(options) {
            ProductShoppingListsView.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);
            this.options.template = _.template(this.options.template);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.model.on('change:shopping_lists', this.render, this);

            this.render();
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

        dispose: function() {
            this.disposeElements();
            ProductShoppingListsView.__super__.dispose.apply(this, arguments);
        },

        delegateEvents: function() {
            ProductShoppingListsView.__super__.delegateEvents.apply(this, arguments);
            this.delegateElementsEvents();
        },

        undelegateEvents: function() {
            this.undelegateElementsEvents();
            return ProductShoppingListsView.__super__.undelegateEvents.apply(this, arguments);
        },

        render: function() {
            this.clearElementsCache();
            this.updateShoppingLists();
            this.initLayout({
                options: {
                    productModel: this.model
                }
            });
        },

        updateShoppingLists: function() {
            var $el = $(this.options.template({
                currentShoppingList: this.findCurrentShoppingList(),
                shoppingLists: this.model.get('shopping_lists')
            }));

            this.$el.html($el);
            this.delegateEvents();
        },

        findCurrentShoppingList: function() {
            return _.find(this.model.get('shopping_lists'), function(list) {
                return list.is_current;
            }) || null;
        },

        editLineItem: function(event) {
            var lineItemId = $(event.currentTarget).data('lineItemId');
            if (lineItemId) {
                this.model.trigger('editLineItem', lineItemId);
            }
        }
    }));

    return ProductShoppingListsView;
});
