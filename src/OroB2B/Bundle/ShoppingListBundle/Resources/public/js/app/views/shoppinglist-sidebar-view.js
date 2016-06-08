define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    var ShoppingListSidebarView;

    ShoppingListSidebarView = BaseView.extend({

        currentTitle : '',

        initialize: function(options) {
            this.$el = options._sourceElement;
            // listen to incoming title updates and re-render the whole shopping list
            mediator.on('inlineEditor:shopping-list-title:update',
                _.bind(this.updateCurrentTitle, this));
        },

        updateCurrentTitle: function(updateData) {
            this.$el.find('.current h3').text(updateData.label);
        }
    });

    return ShoppingListSidebarView;
});
