define(function(require) {
    'use strict';

    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    var ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    var mediator = require('oroui/js/mediator');
    var ShoppingListTitleInlineEditableViewComponent;

    ShoppingListTitleInlineEditableViewComponent = InlineEditableViewComponent.extend({

        eventChannelId: null,

        shoppingListCollection: null,

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListTitleInlineEditableViewComponent() {
            ShoppingListTitleInlineEditableViewComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.shoppingListId = options.metadata.broadcast_parameters.id;
            this.eventChannelId = options.eventChannelId;
            ShoppingListTitleInlineEditableViewComponent.__super__.initialize.apply(this, arguments);

            // listening to generic inline editor's events and repackaging them
            // into specific shopping list events
            mediator.on('inlineEditor:' + this.eventChannelId + ':update', this.repackageEvent, this);

            ShoppingListCollectionService.shoppingListCollection.done((function(collection) {
                this.shoppingListCollection = collection;
            }).bind(this));
        },

        getViewOptions: function() {
            var options = ShoppingListTitleInlineEditableViewComponent.__super__.getViewOptions.apply(this);

            if (!this.inlineEditingOptions.enable) {
                options.autoRender = false;
            }

            return options;
        },

        /**
         *
         * @param data
         */
        repackageEvent: function(data) {
            var shoppingListId = this.shoppingListId;
            this.shoppingListCollection.each(function(model) {
                if (model.get('id') === shoppingListId) {
                    model.set('label', data.label);
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.shoppingListCollection;
            this.$el.off();
            mediator.off('inlineEditor:' + this.eventChannelId + ':update', this.repackageEvent, this);

            ShoppingListTitleInlineEditableViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListTitleInlineEditableViewComponent;
});
