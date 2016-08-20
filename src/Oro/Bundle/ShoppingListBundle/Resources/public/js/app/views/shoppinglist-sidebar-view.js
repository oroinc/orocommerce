define(function(require) {
    'use strict';

    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    var ShoppingListSidebarView;

    ShoppingListSidebarView = BaseView.extend({
        eventChannelId: 'shopping-list-event:shopping-list-title:update',

        initialize: function(options) {
            this.eventChannelId = options.eventChannelId || this.eventChannelId;
            mediator.on(this.eventChannelId, this.updateCurrentTitle, this);
        },

        updateCurrentTitle: function(updateData) {
            this.$el.find('.current-title').text(updateData.label);
        }
    });

    return ShoppingListSidebarView;
});
