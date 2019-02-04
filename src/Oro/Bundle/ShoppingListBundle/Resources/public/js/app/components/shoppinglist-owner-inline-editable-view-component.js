define(function(require) {
    'use strict';

    var ShoppingListOwnerInlineEditableViewComponent;
    var ViewComponent = require('oroui/js/app/components/view-component');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var $ = require('jquery');
    var _ = require('underscore');

    ShoppingListOwnerInlineEditableViewComponent = ViewComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ShoppingListOwnerInlineEditableViewComponent() {
            ShoppingListOwnerInlineEditableViewComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.$select = this.$el.find('select');
            this.shoppingListId = options.shoppingListId;
            this.$el.on('change', _.bind(this._onShoppingListOwnerChange, this));
        },

        /**
         * Change shopping list owner event handler
         *
         * @param e
         */
        _onShoppingListOwnerChange: function(e) {
            var ownerId = e.val;
            $.ajax({
                method: 'PUT',
                url: routing.generate('oro_api_set_shopping_list_owner', {
                    id: this.shoppingListId
                }),
                data: {
                    ownerId: ownerId
                },
                success: function(response) {
                    mediator.execute('showFlashMessage', 'success', _.escape(response));
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();
            this.$el.off('change', _.bind(this._onShoppingListOwnerChange, this));

            ShoppingListOwnerInlineEditableViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListOwnerInlineEditableViewComponent;
});
