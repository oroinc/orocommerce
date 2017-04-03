define(function(require) {
    'use strict';

    var ProductShoppingListsView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    ProductShoppingListsView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            template: ''
        },

        modelAttr: {
            shopping_lists: []
        },

        modelEvents: {
            shopping_lists: ['change', 'render']
        },

        shoppingListCollection: null,

        initialize: function(options) {
            ProductShoppingListsView.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);
            this.options.template = _.template(this.options.template);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            ShoppingListCollectionService.shoppingListCollection.done((function(collection) {
                this.shoppingListCollection = collection;
                this.listenTo(collection, 'change', this.render);
                this.render();
            }).bind(this));
        },

        initModel: function(options) {
            var modelAttr = _.each(options.modelAttr, function(value, attribute) {
                    options.modelAttr[attribute] = value === 'undefined' ? undefined : value;
                }) || {};
            this.modelAttr = $.extend(true, {}, this.modelAttr, modelAttr);

            if (options.productModel) {
                this.model = options.productModel;
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute) || modelAttr[attribute] !== undefined) {
                    this.model.set(attribute, value);
                }
            }, this);

            if (this.model.get('shopping_lists') === undefined) {
                this.model.set('shopping_lists', []);
            }
        },

        dispose: function() {
            this.disposeElements();
            delete this.shoppingListCollection;
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
            mediator.trigger('layout:adjustHeight');
        },

        updateShoppingLists: function() {
            var modelShoppingLists = this.model.get('shopping_lists');
            var $el = $(this.options.template({
                currentShoppingList: this.findCurrentShoppingList(modelShoppingLists),
                shoppingListsCount: modelShoppingLists && modelShoppingLists.length || 0
            }));

            this.$el.html($el);
            this.delegateEvents();
        },

        findCurrentShoppingList: function(modelShoppingLists) {
            var current = _.find(modelShoppingLists, function(list) {
                var model = this.shoppingListCollection.get(list.id);
                return model && model.get('is_current');
            }, this) || null;
            if (!current) {
                return null;
            }
            return _.extend(
                {},
                {line_items: current.line_items},
                this.shoppingListCollection.get(current.id).toJSON()
            );
        },
    }));

    return ProductShoppingListsView;
});
