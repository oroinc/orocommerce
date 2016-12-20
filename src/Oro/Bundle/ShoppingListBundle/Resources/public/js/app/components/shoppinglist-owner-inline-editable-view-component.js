/*jslint nomen:true*/
/*global define*/
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
                url: routing.generate('oro_api_set_shoppinglist_owner', {
                    id: this.shoppingListId
                }),
                data: {
                    ownerId: ownerId
                },
                success: function(response) {
                    mediator.execute('showFlashMessage', 'success', response);
                },
                error: function(xhr) {
                    mediator.execute('showFlashMessage', 'error', xhr.responseText);
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
