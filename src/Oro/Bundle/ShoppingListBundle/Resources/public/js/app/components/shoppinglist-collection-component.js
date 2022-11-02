define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');

    const ShoppingListCollectionComponent = BaseComponent.extend({
        /**
         * @inheritdoc
         */
        listen: {
            'shopping-list:line-items:update-response mediator': '_onLineItemsUpdate'
        },

        /**
         * @inheritdoc
         */
        constructor: function ShoppingListCollectionComponent(options) {
            ShoppingListCollectionComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.collection = new BaseCollection(options.shoppingLists);
            this.collection.comparator = this.comparator;

            this.collection.on('update', function(collection, options) {
                if (options.add) {
                    mediator.trigger('shopping-list:refresh');
                }
            });
            this.collection.on('change', function(options) {
                if (options && options.refresh) {
                    mediator.trigger('shopping-list:refresh');
                }
            });
            ShoppingListCollectionService.shoppingListCollection.resolve(this.collection);
        },

        comparator: function(model) {
            // 0 for current SL - should be first
            return model.get('is_current') ? 0 : model.get('id');
        },

        _onLineItemsUpdate: function(model, response) {
            if (!model || !response) {
                return;
            }

            if (response.message) {
                const isSuccessful = response.hasOwnProperty('successful') && response.successful;
                mediator.execute(
                    'showFlashMessage',
                    isSuccessful ? 'success' : 'error',
                    response.message,
                    isSuccessful ? {namespace: 'shopping_list'} : {}
                );
            }

            const updateShoppingListCollection = function(shoppingList) {
                if (shoppingList && !this.collection.find({id: shoppingList.id})) {
                    this.collection.add(_.defaults(shoppingList, {is_current: true}), {
                        silent: true
                    });
                }
            }.bind(this);
            const updateModel = function(model, product) {
                model.set('shopping_lists', product.shopping_lists, {silent: true});
                model.trigger('change:shopping_lists');
            };
            if (response.product && !_.isArray(model)) {
                updateModel(model, response.product);
            } else if (response.products && _.isArray(model)) {
                model = _.indexBy(model, 'id');
                _.each(response.products, function(product) {
                    if (model[product.id]) {
                        updateModel(model[product.id], product);
                    }
                    _.each(product.shopping_lists, updateShoppingListCollection);
                });
            }

            updateShoppingListCollection(response.shoppingList);

            this.collection.trigger('change', {
                refresh: true
            });
        }
    });

    return ShoppingListCollectionComponent;
});
