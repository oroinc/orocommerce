define(function(require) {
    'use strict';

    var ShoppingListWidgetViewComponent;
    var ViewComponent = require('oroui/js/app/components/view-component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var $ = require('jquery');
    var _ = require('underscore');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    ShoppingListWidgetViewComponent = ViewComponent.extend({
        shoppingListCollection: null,

        elements: {
            radio: '[data-role="set-default"]'
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;

            ShoppingListWidgetViewComponent.__super__.initialize.apply(this, arguments);

            this.$el.on('change', this.elements.radio, _.bind(this._onCurrentShoppingListChange, this));

            ShoppingListCollectionService.shoppingListCollection.done(_.bind(function(collection) {
                this.shoppingListCollection = collection;
            }, this));
        },

        /**
         * Change current shopping list event handler
         *
         * @param e
         */
        _onCurrentShoppingListChange: function(e) {
            var self = this;
            var shoppingListId = parseInt($(e.target).val(), 10);
            var shoppingListLabel = $(e.target).data('label');

            $.ajax({
                method: 'PUT',
                url: routing.generate('oro_api_set_shoppinglist_current', {
                    id: shoppingListId
                }),
                success: function() {
                    self.shoppingListCollection.each(function(model) {
                        model.set('is_current', model.get('id') === shoppingListId, {silent: true});
                    });
                    self.shoppingListCollection.trigger('change');

                    var message = _.__('oro.shoppinglist.actions.shopping_list_set_as_default', {
                        shoppingList: shoppingListLabel
                    });
                    mediator.execute('showFlashMessage', 'success', message);
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.shoppingListCollection;
            this.$el.off();

            ShoppingListWidgetViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListWidgetViewComponent;
});
