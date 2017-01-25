define(function(require) {
    'use strict';

    var ShoppingListCollectionComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    var mediator = require('oroui/js/mediator');

    ShoppingListCollectionComponent = BaseComponent.extend({
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            var collection = new BaseCollection(options.shoppingLists);
            collection.comparator = this.comparator;

            collection.on('update', function(collection, options) {
                if (options.add) {
                    mediator.trigger('shopping-list:refresh');
                }
            });
            collection.on('change', function(options) {
                if (options && options.refresh) {
                    mediator.trigger('shopping-list:refresh');
                }
            });
            ShoppingListCollectionService.shoppingListCollection.resolve(collection);
        },

        comparator: function(model) {
            return model.get('id');
        }
    });

    return ShoppingListCollectionComponent;
});
