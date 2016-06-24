/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    var mediator = require('oroui/js/mediator');
    var ShoppingListTitleInlineEditableViewComponent;

    ShoppingListTitleInlineEditableViewComponent = InlineEditableViewComponent.extend({

        eventChannelId: null,
        
        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.eventChannelId = options.eventChannelId;
            ShoppingListTitleInlineEditableViewComponent.__super__.initialize.apply(this, arguments);
            
            // listening to generic inline editor's events and repackaging them
            // into specific shopping list events
            mediator.on('inlineEditor:' + this.eventChannelId + ':update', this.repackageEvent, this);
            
            // sending off information about the currently loaded shopping list ID 
            // to other components in aid
            mediator.trigger('shopping-list-event:' + this.eventChannelId + ':shopping-list-id', 
                options.metadata.broadcast_parameters.id);
        },

        /**
         * 
         * @param data
         */
        repackageEvent: function(data) {
            mediator.trigger('shopping-list-event:' + this.eventChannelId + ':update', data);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();
            mediator.off('inlineEditor:' + this.eventChannelId + ':update', this.repackageEvent, this);

            ShoppingListTitleInlineEditableViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListTitleInlineEditableViewComponent;
});
