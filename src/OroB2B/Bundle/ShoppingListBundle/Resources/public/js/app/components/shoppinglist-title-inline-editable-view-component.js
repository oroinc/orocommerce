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
            
            // sending off information about the currently loaded shopping list ID 
            // to other components in aid
            mediator.trigger('inlineEditor:' + this.eventChannelId + ':shopping-list-id', 
                options.metadata.broadcast_parameters.id);
        }

    });

    return ShoppingListTitleInlineEditableViewComponent;
});
