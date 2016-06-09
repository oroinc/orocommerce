define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    var ShoppingListSidebarView;

    ShoppingListSidebarView = BaseView.extend({

        eventChannelId: null,

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.eventChannelId = options.eventChannelId;
            // listen to incoming title updates and re-render the whole shopping list
            mediator.on('inlineEditor:' + this.eventChannelId + ':update', this.updateCurrentTitle, this);
        },

        /**
         *
         * @param updateData
         */
        updateCurrentTitle: function(updateData) {
            this.$el.find('.current-title').text(updateData.label);
        }
    });

    return ShoppingListSidebarView;
});
