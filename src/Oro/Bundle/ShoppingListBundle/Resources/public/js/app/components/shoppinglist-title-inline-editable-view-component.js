define(function(require) {
    'use strict';

    const InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');
    const mediator = require('oroui/js/mediator');

    const ShoppingListTitleInlineEditableViewComponent = InlineEditableViewComponent.extend({

        eventChannelId: null,

        shoppingListCollection: null,

        /**
         * @inheritDoc
         */
        constructor: function ShoppingListTitleInlineEditableViewComponent(options) {
            ShoppingListTitleInlineEditableViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.shoppingListId = options.metadata.broadcast_parameters.id;
            this.eventChannelId = options.eventChannelId;
            ShoppingListTitleInlineEditableViewComponent.__super__.initialize.call(this, options);

            // listening to generic inline editor's events and repackaging them
            // into specific shopping list events
            mediator.on('inlineEditor:' + this.eventChannelId + ':update', this.repackageEvent, this);

            ShoppingListCollectionService.shoppingListCollection.done((function(collection) {
                this.shoppingListCollection = collection;
            }).bind(this));
        },

        getViewOptions: function() {
            const options = ShoppingListTitleInlineEditableViewComponent.__super__.getViewOptions.call(this);

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
            const shoppingListId = this.shoppingListId;
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
