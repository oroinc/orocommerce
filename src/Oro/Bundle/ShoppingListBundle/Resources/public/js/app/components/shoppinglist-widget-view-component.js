define(function(require) {
    'use strict';

    const ViewComponent = require('oroui/js/app/components/view-component');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const $ = require('jquery');
    const _ = require('underscore');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    const ShoppingListWidgetViewComponent = ViewComponent.extend({
        shoppingListCollection: null,

        elements: {
            radio: '[data-role="set-default"]'
        },

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListWidgetViewComponent(options) {
            ShoppingListWidgetViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;

            ShoppingListWidgetViewComponent.__super__.initialize.call(this, options);

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
            const self = this;
            const shoppingListId = parseInt($(e.target).val(), 10);
            const shoppingListLabel = $(e.target).data('label');

            $.ajax({
                method: 'PUT',
                url: routing.generate('oro_api_set_shopping_list_current', {
                    id: shoppingListId
                }),
                success: function() {
                    self.shoppingListCollection.each(function(model) {
                        model.set('is_current', model.get('id') === shoppingListId, {silent: true});
                    });
                    self.shoppingListCollection.trigger('change');

                    const message = _.__('oro.shoppinglist.actions.shopping_list_set_as_default', {
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
