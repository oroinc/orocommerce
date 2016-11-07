define(function(require) {
    'use strict';

    var ShoppingListWidgetViewComponent;
    var ViewComponent = require('oroui/js/app/components/view-component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var Error = require('oroui/js/error');
    var $ = require('jquery');
    var _ = require('underscore');

    ShoppingListWidgetViewComponent = ViewComponent.extend({

        elements: {
            radio: '[data-role="set-default"]'
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;

            ShoppingListWidgetViewComponent.__super__.initialize.apply(this, arguments);

            mediator.on('shopping-list:change-current', this.setCurrentShoppingList, this);

            this.$el.on('change', this.elements.radio, _.bind(this._onCurrentShoppingListChange, this));
        },

        /**
         * Change current shopping list event handler
         *
         * @param e
         */
        _onCurrentShoppingListChange: function(e) {
            var shoppingListId = $(e.target).val();
            var shoppingListLabel = $(e.target).data('label');

            $.ajax({
                method: 'PUT',
                url: routing.generate('oro_api_set_shoppinglist_current', {
                    id: shoppingListId
                }),
                success: function() {
                    mediator.trigger('shopping-list:change-current', shoppingListId);
                    var message = _.__('oro.shoppinglist.actions.shopping_list_set_as_default', {
                        shoppingList: shoppingListLabel
                    });
                    mediator.execute('showFlashMessage', 'success', message);
                },
                error: function(xhr) {
                    mediator.trigger('shopping-list:updated');
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        setCurrentShoppingList: function(shoppingListId) {
            this.$el.find(this.elements.radio).filter('[value="' + shoppingListId + '"]').prop('checked', true);
         },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();
            mediator.off(null, null, this);

            ShoppingListWidgetViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListWidgetViewComponent;
});
