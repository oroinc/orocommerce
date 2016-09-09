/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ViewComponent = require('oroui/js/app/components/view-component');
    var mediator = require('oroui/js/mediator');
    var ShoppingListWidgetViewComponent;

    ShoppingListWidgetViewComponent = ViewComponent.extend({

        shoppingListId: null,

        currentShoppingListId: null,

        eventChannelId: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.eventChannelId = options.eventChannelId;
            ShoppingListWidgetViewComponent.__super__.initialize.apply(this, arguments);
            this.currentShoppingList = this.getCurrentShoppingList();
            this.setCurrentShoppingListRoute = options.setCurrentShoppingListRoute;
            mediator.on('shopping-list-event:' + this.eventChannelId + ':shopping-list-id', this.getShoppingListId, this);
            mediator.on('shopping-list-event:' + this.eventChannelId + ':update', this.updateTitle, this);
            this.$el.on({
                change: this._onChangeCurrentShoppingList.bind(this)
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
        _onChangeCurrentShoppingList: function(e) {
            var shoppingList = $(e.target).data('shoppinglist'),
                _self = this;

            $.ajax({
                method: 'POST',
                url: this.setCurrentShoppingListRoute,
                //dataType: 'json',
                data: {
                    id: shoppingList.id
                },
                success: function(response) {
                    _self.currentShoppingList = shoppingList;

                    mediator.trigger('shopping-list:change-current', _self.currentShoppingList);
                    mediator.execute(
                        'showFlashMessage',
                        'success',
                        'Shopping list "<a href="' + _self.currentShoppingList.url + '">' + _self.currentShoppingList.label + '</a>" was set as default'
                    );
                },
                error: function(xhr) {
                    mediator.trigger('shopping-list:updated');
                    mediator.execute(
                        'showFlashMessage',
                        'error',
                        'Shopping list cannot be set as default'
                    );
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

        getCurrentShoppingList: function() {
            var checkedEl = this.$el.find('input[name="shopping-list-dropdown-radio"]:checked');
            return checkedEl.length ? checkedEl.data('shoppinglist') : null;
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
