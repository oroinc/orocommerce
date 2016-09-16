/*jslint nomen:true*/
/*global define*/
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

        shoppingListId: null,

        eventChannelId: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.eventChannelId = options.eventChannelId;
            ShoppingListWidgetViewComponent.__super__.initialize.apply(this, arguments);
            mediator.on('shopping-list-event:' + this.eventChannelId + ':shopping-list-id', this.getShoppingListId, this);
            mediator.on('shopping-list-event:' + this.eventChannelId + ':update', this.updateTitle, this);
            this.$el.on({
                change: _.bind(this._onCurrentShoppingListChange, this)
            }, 'input[name="shopping-list-dropdown-radio"]');
        },

        /**
         * Updating the shoppinglist name inside the widget list
         *
         * @param updateData
         */
        updateTitle: function(updateData) {
            if (!this.shoppingListId) {
                return; // no ID, no update possible
            }
            this.$el.find('.shopping-list-dropdown__name-inner--' + this.shoppingListId)
                .text(updateData.label);
        },
        
        /**
         * Change current shopping list event handler
         *
         * @param e
         */
        _onCurrentShoppingListChange: function(e) {
            var shoppingListId = parseInt($(e.target).val(), 10);

            $.ajax({
                method: 'POST',
                url: routing.generate('orob2b_product_frontend_product_index'),
                data: {
                    id: shoppingListId
                },
                success: function(response) {
                    mediator.trigger('shopping-list:change-current', shoppingListId);

                    if (response && response.message) {
                        mediator.execute('showFlashMessage', (response.successful ? 'success' : 'error'), response.message);
                    }
                },
                error: function(xhr) {
                    mediator.trigger('shopping-list:updated');
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        /**
         * Retrieving the shopping list ID from another component.
         *
         * @param id
         */
        getShoppingListId: function(id) {
            this.shoppingListId = id;
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();
            mediator.off('shopping-list-event:' + this.eventChannelId + ':shopping-list-id', this.getShoppingListId, this);
            mediator.off('shopping-list-event:' + this.eventChannelId + ':update', this.updateTitle, this);

            ShoppingListWidgetViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListWidgetViewComponent;
});
