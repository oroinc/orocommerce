define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    const ProductShoppingListsView = BaseView.extend(_.extend({}, ElementsHelper, {
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

        /**
         * @inheritdoc
         */
        constructor: function ProductShoppingListsView(options) {
            ProductShoppingListsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ProductShoppingListsView.__super__.initialize.call(this, options);

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
            const modelAttr = _.each(options.modelAttr, function(value, attribute) {
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
            ProductShoppingListsView.__super__.dispose.call(this);
        },

        delegateEvents: function(events) {
            ProductShoppingListsView.__super__.delegateEvents.call(this, events);
            this.delegateElementsEvents();
        },

        undelegateEvents: function() {
            this.undelegateElementsEvents();
            return ProductShoppingListsView.__super__.undelegateEvents.call(this);
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
            const modelShoppingLists = this.model.get('shopping_lists');
            const $el = $(this.options.template({
                currentShoppingList: this.findCurrentShoppingList(modelShoppingLists),
                shoppingListsCount: modelShoppingLists && modelShoppingLists.length || 0
            }));

            this.$el.toggleClass('product-item-shopping-lists', $el.length > 0);
            this.$el.html($el);
            this.delegateEvents();
        },

        findCurrentShoppingList: function(modelShoppingLists) {
            const current = _.find(modelShoppingLists, function(list) {
                const model = this.shoppingListCollection.get(list.id);
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
        }
    }));

    return ProductShoppingListsView;
});
