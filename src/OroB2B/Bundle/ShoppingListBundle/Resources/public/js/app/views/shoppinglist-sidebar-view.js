define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    var ShoppingListSidebarView;

    ShoppingListSidebarView = BaseView.extend({

        id: null,

        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;
            this.id = options.id;
            // listen to incoming title updates and re-render the whole shopping list
            mediator.on('inlineEditor:' + this.id + ':update', this.updateCurrentTitle, this);
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
